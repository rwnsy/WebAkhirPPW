<?php
require_once __DIR__ . "/config/conn.php";
$activePage = 'community';

$topic = $_GET['topik'] ?? 'umum';
$allowedTopics = ['umum', 'review', 'rekomendasi', 'klub', 'acara'];
$topic = in_array($topic, $allowedTopics, true) ? $topic : 'umum';

$communityPosts = [];
$publishedPostCount = 0;
$totalReviewCount = 0;
$totalBookCount = 0;
$availableBookCount = 0;
$totalCategoryCount = 0;

if (HAS_COMMUNITY_POSTS_TABLE) {
    $postResult = mysqli_query($conn, "SELECT cp.id, cp.title, cp.content, cp.status, cp.created_at, cp.updated_at, u.nama
        FROM community_posts cp
        INNER JOIN users u ON u.id = cp.user_id
        WHERE cp.status = 'published'
        ORDER BY cp.created_at DESC
        LIMIT 6");

    while ($row = mysqli_fetch_assoc($postResult)) {
        $communityPosts[] = $row;
    }

}

$publishedPostSelect = HAS_COMMUNITY_POSTS_TABLE ? "(SELECT COUNT(*) FROM community_posts WHERE status = 'published')" : "0";
$reviewCountSelect = HAS_REVIEWS_TABLE ? "(SELECT COUNT(*) FROM reviews)" : "0";
$communityStats = mysqli_fetch_assoc(mysqli_query($conn, "SELECT
        (SELECT COUNT(*) FROM buku2) AS total_books,
        (SELECT COUNT(DISTINCT kategori) FROM buku2) AS total_categories,
        (SELECT SUM(CASE WHEN stok > 0 THEN 1 ELSE 0 END) FROM buku2) AS available_books,
        $publishedPostSelect AS published_posts,
        $reviewCountSelect AS total_reviews"));
$totalBookCount = (int) ($communityStats['total_books'] ?? 0);
$totalCategoryCount = (int) ($communityStats['total_categories'] ?? 0);
$availableBookCount = (int) ($communityStats['available_books'] ?? 0);
$publishedPostCount = (int) ($communityStats['published_posts'] ?? 0);
$totalReviewCount = (int) ($communityStats['total_reviews'] ?? 0);

$featuredBook = null;
if (HAS_REVIEWS_TABLE) {
    $featuredResult = mysqli_query($conn, "SELECT b.id, b.judul, b.penulis, b.kategori, b.gambar,
            COUNT(r.id) AS review_count, COALESCE(ROUND(AVG(r.rating), 1), 0) AS avg_rating
        FROM buku2 b
        LEFT JOIN reviews r ON r.buku_id = b.id
        GROUP BY b.id, b.judul, b.penulis, b.penerbit, b.tahun_terbit, b.kategori, b.harga, b.stok, b.gambar, b.deskripsi, b.created_at
        ORDER BY review_count DESC, avg_rating DESC, b.id DESC
        LIMIT 1");
} else {
    $featuredResult = mysqli_query($conn, "SELECT id, judul, penulis, kategori, gambar, 0 AS review_count, 0 AS avg_rating
        FROM buku2
        ORDER BY id DESC
        LIMIT 1");
}
$featuredBook = mysqli_fetch_assoc($featuredResult);

$trendItems = [];
if (HAS_REVIEWS_TABLE) {
    $trendResult = mysqli_query($conn, "SELECT b.kategori, COUNT(DISTINCT b.id) AS total_books,
            COUNT(r.id) AS review_count, COALESCE(ROUND(AVG(r.rating), 1), 0) AS avg_rating
        FROM buku2 b
        LEFT JOIN reviews r ON r.buku_id = b.id
        GROUP BY b.kategori
        ORDER BY review_count DESC, total_books DESC, b.kategori ASC
        LIMIT 4");
} else {
    $trendResult = mysqli_query($conn, "SELECT kategori, COUNT(*) AS total_books, 0 AS review_count, 0 AS avg_rating
        FROM buku2
        GROUP BY kategori
        ORDER BY total_books DESC, kategori ASC
        LIMIT 4");
}

while ($row = mysqli_fetch_assoc($trendResult)) {
    $trendItems[] = $row;
}

$topTrend = $trendItems[0] ?? null;

$topicLinks = [
    ['key' => 'umum', 'icon' => 'DU', 'label' => 'Diskusi Umum', 'count' => $publishedPostCount . ' tulisan', 'href' => 'community.php?topik=umum#communityPostsTitle'],
    ['key' => 'review', 'icon' => 'RB', 'label' => 'Ulasan Buku', 'count' => $totalReviewCount . ' ulasan', 'href' => 'community.php?topik=review#reviewFeedTitle'],
    ['key' => 'rekomendasi', 'icon' => 'RK', 'label' => 'Rekomendasi', 'count' => $availableBookCount . ' tersedia', 'href' => 'shop/'],
    ['key' => 'klub', 'icon' => 'KB', 'label' => 'Klub Baca', 'count' => $totalCategoryCount . ' kategori', 'href' => 'community.php?topik=klub#communityPostsTitle'],
    ['key' => 'acara', 'icon' => 'AP', 'label' => 'Agenda Literasi', 'count' => $totalBookCount . ' koleksi', 'href' => 'community.php?topik=acara#trendCommunity'],
];

$reviewFeedItems = [];
if (HAS_REVIEWS_TABLE) {
    $reviewResult = mysqli_query($conn, "SELECT r.id, r.reviewer_name, r.rating, r.komentar, r.created_at,
            b.id AS buku_id, b.judul, b.penulis, b.kategori, b.gambar
        FROM reviews r
        INNER JOIN buku2 b ON b.id = r.buku_id
        ORDER BY r.id DESC
        LIMIT 4");

    while ($row = mysqli_fetch_assoc($reviewResult)) {
        $reviewFeedItems[] = $row;
    }
}

function community_initial($name)
{
    $name = trim((string) $name);

    return $name !== '' ? strtoupper(substr($name, 0, 1)) : 'P';
}

function community_handle($name)
{
    $slug = preg_replace('/[^a-z0-9]+/', '', strtolower((string) $name));

    return '@' . ($slug !== '' ? $slug : 'reader');
}

function community_time_label($datetime)
{
    $timestamp = strtotime((string) $datetime);

    if (!$timestamp) {
        return 'baru saja';
    }

    $diff = time() - $timestamp;

    if ($diff < 60) {
        return 'baru saja';
    }

    if ($diff < 3600) {
        return floor($diff / 60) . ' menit lalu';
    }

    if ($diff < 86400) {
        return floor($diff / 3600) . ' jam lalu';
    }

    if ($diff < 604800) {
        return floor($diff / 86400) . ' hari lalu';
    }

    return date('d M Y', $timestamp);
}

function community_excerpt($text, $limit = 190)
{
    $text = trim(preg_replace('/\s+/', ' ', strip_tags((string) $text)));

    if (strlen($text) <= $limit) {
        return $text;
    }

    return rtrim(substr($text, 0, $limit - 3)) . '...';
}

$writeUrl = is_logged_in() ? url('community-create.php') : url('auth/login.php');
$writeLabel = is_logged_in() ? 'Tulis Cerita' : 'Masuk untuk Menulis';
?>
<!DOCTYPE html>
<html lang="id" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Komunitas - Pustakata</title>
    <?= theme_bootstrap_script(); ?>
    <link rel="stylesheet" href="<?= e(asset('css/style.css') . '?v=community-layout-2'); ?>">
</head>
<body>
    <?php include partial_path('public_navbar.php'); ?>
    <div class="container"><?= flash_messages(); ?></div>

    <main class="community-page">
        <div class="container community-layout">
            <aside class="community-sidebar reveal reveal-left" aria-label="Kategori komunitas">
                <div class="community-panel community-panel-plain">
                    <h2>Kategori Topik</h2>
                    <nav class="community-topic-list" data-reveal-stagger>
                        <?php foreach ($topicLinks as $item): ?>
                            <a class="topic-link reveal reveal-up <?= $topic === $item['key'] ? 'active' : ''; ?>" href="<?= e(url($item['href'])); ?>">
                                <span class="topic-icon"><?= e($item['icon']); ?></span>
                                <span class="topic-copy">
                                    <strong><?= e($item['label']); ?></strong>
                                    <small><?= e($item['count']); ?></small>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    </nav>
                </div>

                <article class="community-panel community-side-summary reveal reveal-up">
                    <span>Ringkasan Ruang Baca</span>
                    <div>
                        <strong><?= e($publishedPostCount); ?></strong>
                        <small>tulisan</small>
                    </div>
                    <div>
                        <strong><?= e($totalReviewCount); ?></strong>
                        <small>ulasan</small>
                    </div>
                </article>
            </aside>

            <section class="community-main" aria-labelledby="communityTitle">
                <div class="community-hero community-panel reveal reveal-up">
                    <div class="community-hero-copy">
                        <p class="eyebrow">KOMUNITAS PEMBACA</p>
                        <h1 id="communityTitle">Tempat Pembaca Berbagi Cerita dan Rekomendasi Buku</h1>
                        <p>Bagikan pengalaman membaca, rekomendasi, dan sudut pandang baru bersama komunitas pembaca Pustakata.</p>
                        <div class="community-hero-actions">
                            <a class="btn btn-primary community-hero-cta" href="<?= e($writeUrl); ?>">
                                <span><?= e($writeLabel); ?></span>
                            </a>
                            <a class="btn btn-outline" href="#communityPostsTitle">Jelajahi Tulisan</a>
                        </div>
                        <div class="community-stats" aria-label="Statistik komunitas">
                            <div>
                                <strong><?= e($publishedPostCount); ?></strong>
                                <span>Tulisan</span>
                            </div>
                            <div>
                                <strong><?= e($totalReviewCount); ?></strong>
                                <span>Ulasan</span>
                            </div>
                            <div>
                                <strong><?= e($totalCategoryCount); ?></strong>
                                <span>Kategori</span>
                            </div>
                        </div>
                        <?php if (!is_logged_in()): ?>
                            <small class="community-login-note">Masuk terlebih dahulu agar tulisan tampil atas nama akun pembaca Anda.</small>
                        <?php endif; ?>
                    </div>
                    <div class="community-hero-visual" aria-label="Sorotan komunitas">
                        <div class="community-highlight-card">
                            <span>Topik ramai</span>
                            <strong><?= e($topTrend['kategori'] ?? 'Diskusi Buku'); ?></strong>
                            <small><?= e($topTrend ? $topTrend['review_count'] . ' ulasan pembaca' : 'Mulai percakapan pertama'); ?></small>
                        </div>
                        <div class="community-avatar-stack" aria-hidden="true">
                            <?php
                            $avatarNames = array_slice(array_column($communityPosts, 'nama'), 0, 4);
                            $avatarNames = $avatarNames ?: ['Pustakata', 'Pembaca', 'Katalog'];
                            foreach ($avatarNames as $avatarName):
                            ?>
                                <span><?= e(community_initial($avatarName)); ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php if ($featuredBook): ?>
                            <a class="community-hero-book" href="<?= e(url('shop/detail.php?id=' . urlencode($featuredBook['id']))); ?>">
                                <?php if (!empty($featuredBook['gambar'])): ?>
                                    <img src="<?= e(upload_url($featuredBook['gambar'])); ?>" alt="<?= e($featuredBook['judul']); ?>" decoding="async">
                                <?php else: ?>
                                    <span><?= e(community_initial($featuredBook['judul'])); ?></span>
                                <?php endif; ?>
                                <div>
                                    <small>Buku dibahas</small>
                                    <strong><?= e($featuredBook['judul']); ?></strong>
                                </div>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <section class="community-section reveal reveal-up" aria-labelledby="communityPostsTitle">
                    <div class="community-section-head">
                        <div>
                            <p class="eyebrow">TULISAN KOMUNITAS</p>
                            <h2 id="communityPostsTitle">Tulisan Terbaru</h2>
                            <p>Cerita terbaru dari pembaca Pustakata, ditulis langsung oleh anggota komunitas.</p>
                        </div>
                        <a href="<?= e($writeUrl); ?>" class="text-link"><?= e($writeLabel); ?> →</a>
                    </div>

                    <?php if (!HAS_COMMUNITY_POSTS_TABLE): ?>
                        <div class="community-empty community-empty-wide reveal reveal-up">
                            <h3>Ruang cerita sedang disiapkan</h3>
                            <p>Tulisan pembaca akan tampil di sini setelah fitur komunitas diaktifkan oleh pengelola.</p>
                        </div>
                    <?php elseif ($communityPosts): ?>
                        <div class="community-post-list" data-reveal-stagger>
                            <?php foreach ($communityPosts as $post): ?>
                                <article class="community-post-card reveal reveal-up">
                                    <div class="community-post-card-head">
                                        <div class="community-post-meta">
                                            <div class="avatar"><?= e(community_initial($post['nama'])); ?></div>
                                            <div>
                                                <strong><?= e($post['nama']); ?></strong>
                                                <small><?= e(community_time_label($post['created_at'])); ?></small>
                                            </div>
                                        </div>
                                        <span class="community-post-badge">Cerita Pembaca</span>
                                    </div>
                                    <h3><a href="<?= e(url('community-detail.php?id=' . urlencode($post['id']))); ?>"><?= e($post['title']); ?></a></h3>
                                    <p><?= e(community_excerpt($post['content'])); ?></p>
                                    <div class="community-post-footer">
                                        <span><?= e(strlen(strip_tags($post['content']))); ?> karakter</span>
                                        <a class="community-read-link" href="<?= e(url('community-detail.php?id=' . urlencode($post['id']))); ?>">Baca Selengkapnya</a>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="community-empty community-empty-wide reveal reveal-up">
                            <h3>Belum ada tulisan komunitas</h3>
                            <p>Jadilah pembaca pertama yang membagikan cerita membaca di Pustakata.</p>
                            <a href="<?= e($writeUrl); ?>" class="btn btn-primary"><?= e($writeLabel); ?></a>
                        </div>
                    <?php endif; ?>
                </section>

                <section class="community-section reveal reveal-up" aria-labelledby="reviewFeedTitle">
                    <div class="community-section-head">
                        <div>
                            <p class="eyebrow">ULASAN PEMBACA</p>
                            <h2 id="reviewFeedTitle">Review Buku Terbaru</h2>
                            <p>Kesan singkat pembaca terhadap buku yang sedang mereka baca dan rekomendasikan.</p>
                        </div>
                    </div>

                    <?php if ($reviewFeedItems): ?>
                        <div class="community-feed" data-reveal-stagger>
                            <?php foreach ($reviewFeedItems as $item): ?>
                                <article class="community-feed-card reveal reveal-up">
                                    <div class="feed-card-head">
                                        <div class="feed-author">
                                            <div class="avatar"><?= e(community_initial($item['reviewer_name'])); ?></div>
                                            <div>
                                                <strong><?= e($item['reviewer_name']); ?></strong>
                                                <small><?= e(community_handle($item['reviewer_name'])); ?> • <?= e(community_time_label($item['created_at'])); ?></small>
                                            </div>
                                        </div>
                                        <span class="feed-rating">★ <?= e($item['rating']); ?>/5</span>
                                    </div>

                                    <div class="feed-card-body">
                                        <p><?= e($item['komentar']); ?></p>
                                        <a href="<?= e(url('shop/detail.php?id=' . urlencode($item['buku_id']))); ?>" class="feed-book-link">
                                            <?= e($item['judul']); ?> <span>oleh <?= e($item['penulis']); ?></span>
                                        </a>
                                        <div class="feed-tags">
                                            <span>#<?= e(str_replace(' ', '', strtolower($item['kategori']))); ?></span>
                                            <span>#reviewbuku</span>
                                        </div>
                                        <div class="feed-card-footer">
                                            <a class="feed-detail-link" href="<?= e(url('shop/detail.php?id=' . urlencode($item['buku_id']))); ?>">Lihat Detail Buku</a>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="community-empty community-empty-wide reveal reveal-up">
                            <h3>Belum ada ulasan pembaca</h3>
                            <p>Ulasan buku akan tampil di sini saat pembaca mulai membagikan kesannya.</p>
                        </div>
                    <?php endif; ?>
                </section>
            </section>

            <aside class="community-rightbar reveal reveal-right" aria-label="Ringkasan komunitas">
                <section class="trend-card community-panel reveal reveal-up" id="trendCommunity">
                    <h2>Tren Komunitas</h2>
                    <?php if ($trendItems): ?>
                        <div class="trend-list">
                            <?php foreach ($trendItems as $trend): ?>
                                <a href="<?= e(url('shop/?kategori=' . urlencode($trend['kategori']))); ?>">
                                    <span><?= e($trend['kategori']); ?></span>
                                    <strong><?= e($trend['total_books']); ?> buku</strong>
                                    <small><?= e($trend['review_count']); ?> ulasan<?= $trend['avg_rating'] > 0 ? ' • ★ ' . e($trend['avg_rating']) : ''; ?></small>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p>Tren akan muncul setelah katalog buku diisi.</p>
                    <?php endif; ?>
                </section>

                <?php if ($featuredBook): ?>
                    <article class="community-featured community-panel reveal reveal-up" id="featuredBook">
                        <span>Buku Paling Dibahas</span>
                        <h3><?= e($featuredBook['judul']); ?></h3>
                        <p><?= e($featuredBook['penulis']); ?></p>
                        <a href="<?= e(url('shop/detail.php?id=' . urlencode($featuredBook['id']))); ?>" class="featured-cover-link">
                            <?php if (!empty($featuredBook['gambar'])): ?>
                                <img src="<?= e(upload_url($featuredBook['gambar'])); ?>" alt="<?= e($featuredBook['judul']); ?>" loading="lazy" decoding="async">
                            <?php else: ?>
                                <div class="featured-cover-fallback"><?= e(community_initial($featuredBook['judul'])); ?></div>
                            <?php endif; ?>
                        </a>
                        <div class="featured-meta">
                            <strong>★ <?= e($featuredBook['avg_rating'] > 0 ? $featuredBook['avg_rating'] : 'Baru'); ?></strong>
                            <small><?= e($featuredBook['review_count']); ?> ulasan</small>
                        </div>
                    </article>
                <?php endif; ?>

                <section class="newsletter-card community-panel reveal reveal-up" id="newsletterCommunity">
                    <h2>Ikuti Update Buku</h2>
                    <p>Dapatkan kabar koleksi baru, ulasan pilihan, dan percakapan menarik dari pembaca Pustakata.</p>
                    <form action="<?= e(url('newsletter-subscribe.php')); ?>" method="POST" data-validate>
                        <?= csrf_field(); ?>
                        <input type="hidden" name="redirect_to" value="<?= e(current_request_path()); ?>">
                        <input type="email" name="email" placeholder="Email Anda" aria-label="Email update komunitas" required>
                        <button type="submit" class="btn btn-primary">Ikuti Update</button>
                    </form>
                </section>
            </aside>
        </div>
    </main>

    <?php include partial_path('footer.php'); ?>
    <script src="<?= e(asset('js/script.js')); ?>"></script>
</body>
</html>
