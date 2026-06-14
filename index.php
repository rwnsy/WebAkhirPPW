<?php
require_once __DIR__ . "/config/conn.php";
$activePage = 'home';

$topRatedBooks = [];
if (HAS_REVIEWS_TABLE) {
    $bookResult = mysqli_query($conn, "SELECT b.*, COUNT(r.id) AS review_count, COALESCE(ROUND(AVG(r.rating), 1), 0) AS avg_rating
        FROM buku2 b
        LEFT JOIN reviews r ON r.buku_id = b.id
        GROUP BY b.id, b.judul, b.penulis, b.penerbit, b.tahun_terbit, b.kategori, b.harga, b.stok, b.gambar, b.deskripsi, b.created_at
        ORDER BY avg_rating DESC, review_count DESC, b.id DESC
        LIMIT 4");
} else {
    $bookResult = mysqli_query($conn, "SELECT b.*, 0 AS review_count, 0 AS avg_rating
        FROM buku2 b
        ORDER BY b.id DESC
        LIMIT 4");
}

while ($row = mysqli_fetch_assoc($bookResult)) {
    $topRatedBooks[] = [
        "id" => $row['id'],
        "title" => $row['judul'],
        "author" => $row['penulis'],
        "rating" => $row['avg_rating'] > 0 ? $row['avg_rating'] : 'Baru',
        "reviews" => $row['review_count'],
        "price" => rupiah($row['harga']),
        "category" => $row['kategori'],
        "cover_class" => "cover-" . ((($row['id'] ?? 1) % 4) + 1),
        "image" => $row['gambar']
    ];
}

$genres = [];
$genreResult = mysqli_query($conn, "SELECT kategori, COUNT(*) AS total FROM buku2 GROUP BY kategori ORDER BY total DESC, kategori ASC LIMIT 6");

while ($row = mysqli_fetch_assoc($genreResult)) {
    $genres[] = [
        "icon" => categoryIconKey($row['kategori']),
        "name" => $row['kategori'],
        "total" => $row['total']
    ];
}

$latestBooks = [];
$latestResult = mysqli_query($conn, "SELECT id, judul, penulis, kategori, harga, stok, gambar
    FROM buku2
    ORDER BY id DESC
    LIMIT 4");

while ($row = mysqli_fetch_assoc($latestResult)) {
    $latestBooks[] = $row;
}

$homeStats = mysqli_fetch_assoc(mysqli_query($conn, "SELECT
        COUNT(*) AS total_books,
        COUNT(DISTINCT kategori) AS total_categories,
        COALESCE(SUM(stok), 0) AS total_stock,
        SUM(CASE WHEN stok > 0 THEN 1 ELSE 0 END) AS available_books
    FROM buku2"));
$totalBooks = (int) ($homeStats['total_books'] ?? 0);
$totalCategories = (int) ($homeStats['total_categories'] ?? 0);
$totalStock = (int) ($homeStats['total_stock'] ?? 0);
$availableBooks = (int) ($homeStats['available_books'] ?? 0);
$totalReviews = HAS_REVIEWS_TABLE ? (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM reviews"))['total'] : 0;
$showcaseBooks = array_slice($latestBooks, 0, 3);

$communityReviews = [];
if (HAS_REVIEWS_TABLE) {
    $communityResult = mysqli_query($conn, "SELECT r.reviewer_name, r.rating, r.komentar, r.created_at, b.id AS buku_id, b.judul
        FROM reviews r
        INNER JOIN buku2 b ON b.id = r.buku_id
        ORDER BY r.id DESC
        LIMIT 3");

    while ($row = mysqli_fetch_assoc($communityResult)) {
        $communityReviews[] = $row;
    }
}

function categoryIconKey($category)
{
    $category = strtolower((string) $category);

    if (strpos($category, 'sejarah') !== false || strpos($category, 'klasik') !== false) {
        return 'landmark';
    }

    if (strpos($category, 'psikologi') !== false || strpos($category, 'diri') !== false || strpos($category, 'filsafat') !== false) {
        return 'mind';
    }

    if (strpos($category, 'sci') !== false) {
        return 'rocket';
    }

    if (strpos($category, 'seni') !== false || strpos($category, 'puisi') !== false) {
        return 'brush';
    }

    return 'book';
}

function genreIcon($icon)
{
    $paths = [
        'book' => '<path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21.5z"/><path d="M4 5.5v16"/><path d="M8 7h8"/>',
        'layers' => '<path d="m12 3 9 5-9 5-9-5z"/><path d="m3 12 9 5 9-5"/><path d="m3 16 9 5 9-5"/>',
        'rocket' => '<path d="M4.5 16.5c-1 1-1.5 2.5-1.5 4.5 2 0 3.5-.5 4.5-1.5"/><path d="M9 15 6 18l-1-4 9-9c2-2 4.8-2.4 7-2-0.4 2.2-1 5-3 7l-9 9z"/><path d="M15 9h.01"/>',
        'mind' => '<path d="M9 18h6"/><path d="M10 22h4"/><path d="M8.5 14.5A6 6 0 1 1 15.5 14c-.9.6-1.5 1.6-1.5 2.7V17h-4v-.3c0-1-.5-1.8-1.5-2.2Z"/>',
        'landmark' => '<path d="M3 21h18"/><path d="M5 10h14"/><path d="M6 10v8"/><path d="M10 10v8"/><path d="M14 10v8"/><path d="M18 10v8"/><path d="m12 3 8 5H4z"/>',
        'brush' => '<path d="M18.5 3.5a2.1 2.1 0 0 1 3 3L10 18l-4 1 1-4z"/><path d="M4 21c2 0 3-1 3-3"/>'
    ];

    return '<svg viewBox="0 0 24 24" aria-hidden="true">' . ($paths[$icon] ?? $paths['book']) . '</svg>';
}
?>
<!DOCTYPE html>
<html lang="id" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pustakata - Beranda</title>
    <?= theme_bootstrap_script(); ?>
    <link rel="stylesheet" href="<?= e(asset('css/style.css') . '?v=home-community-empty'); ?>">
</head>
<body>
    <?php include partial_path('public_navbar.php'); ?>
    <div class="container"><?= flash_messages(); ?></div>

    <main>
        <section class="hero-section">
            <div class="container hero-grid">
                <div class="hero-text reveal reveal-left">
                    <p class="eyebrow">KURASI SASTRA TERPILIH</p>
                    <h1>Buku terbaik, untuk pembaca terbaik.</h1>
                    <p class="hero-desc">
                        Temukan bacaan klasik dan kontemporer pilihan yang disusun untuk
                        menemani waktu membaca dengan lebih hangat, tenang, dan berkesan.
                    </p>
                    <div class="hero-buttons">
                        <a href="<?= e(url('shop/')); ?>" class="btn btn-primary">Jelajahi Toko</a>
                        <a href="#rating" class="btn btn-outline">Lihat Rating</a>
                    </div>
                    <form class="hero-search" method="GET" action="<?= e(url('shop/')); ?>" data-search-feedback>
                        <input type="text" name="q" placeholder="Cari buku, penulis, atau penerbit...">
                        <button type="submit" class="btn btn-primary">Cari Buku</button>
                    </form>
                </div>

                <div class="hero-feature reveal reveal-right" data-reveal-delay="120">
                    <img class="hero-book-image" src="<?= e(asset('img/hero-books.png')); ?>" alt="Tumpukan buku Pustakata">
                </div>
            </div>
        </section>

        <section class="section container reveal reveal-up" id="terbaru">
            <div class="section-header reveal reveal-up">
                <div>
                    <p class="eyebrow">BARU MASUK</p>
                    <h2>Buku Terbaru</h2>
                    <p>Koleksi segar yang baru masuk rak Pustakata dan siap Anda jelajahi.</p>
                </div>
                <a href="<?= e(url('shop/')); ?>" class="text-link">Belanja Sekarang →</a>
            </div>

            <div class="mini-book-grid" data-reveal-stagger>
                <?php foreach ($latestBooks as $book): ?>
                    <?php $stock = stock_badge($book['stok']); ?>
                    <article class="mini-book-card reveal reveal-up">
                        <?php if (!empty($book['gambar'])): ?>
                            <img src="<?= e(upload_url($book['gambar'])); ?>" alt="<?= e($book['judul']); ?>" loading="lazy" decoding="async">
                        <?php else: ?>
                            <div class="mini-cover cover-<?= (($book['id'] ?? 1) % 4) + 1; ?>"><?= e(substr($book['judul'], 0, 1)); ?></div>
                        <?php endif; ?>
                        <div>
                            <span class="badge"><?= e($book['kategori']); ?></span>
                            <h3><a href="<?= e(url('shop/detail.php?id=' . urlencode($book['id']))); ?>"><?= e($book['judul']); ?></a></h3>
                            <p><?= e($book['penulis']); ?></p>
                            <div class="mini-meta">
                                <strong><?= rupiah($book['harga']); ?></strong>
                                <span class="stock-badge <?= e($stock['class']); ?>"><?= e($stock['label']); ?></span>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="section container reveal reveal-up" id="rating">
            <div class="section-header reveal reveal-up">
                <div>
                    <h2>Buku Rating Tertinggi</h2>
                    <p>Pilihan yang paling disukai pembaca, lengkap dengan kesan dan ulasan mereka.</p>
                </div>
                <a href="<?= e(url('shop/')); ?>" class="text-link">Lihat Semua →</a>
            </div>

            <div class="book-grid" data-reveal-stagger>
                <?php foreach ($topRatedBooks as $book): ?>
                    <article class="book-card reveal reveal-scale">
                        <a href="<?= e(url('shop/detail.php?id=' . urlencode($book['id']))); ?>" class="book-media-link">
                            <?php if (!empty($book['image'])): ?>
                                <img class="real-cover" src="<?= e(upload_url($book['image'])); ?>" alt="<?= e($book['title']); ?>" loading="lazy" decoding="async">
                            <?php else: ?>
                                <div class="book-cover <?= e($book['cover_class']); ?>">
                                    <span class="book-category"><?= e($book['category']); ?></span>
                                    <strong><?= e($book['title']); ?></strong>
                                    <small><?= e($book['author']); ?></small>
                                </div>
                            <?php endif; ?>
                        </a>
                        <div class="book-info">
                            <h3><a href="<?= e(url('shop/detail.php?id=' . urlencode($book['id']))); ?>"><?= e($book['title']); ?></a></h3>
                            <p><?= e($book['author']); ?></p>
                            <div class="rating">★ <?= e($book['rating']); ?> <span>(<?= e($book['reviews']); ?> review)</span></div>
                            <div class="price-row">
                                <strong><?= e($book['price']); ?></strong>
                                <a href="<?= e(url('shop/detail.php?id=' . urlencode($book['id']))); ?>" title="Lihat detail">Detail</a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="section genre-section container reveal reveal-up">
            <h2 class="center-title reveal reveal-up">Genre Populer</h2>
            <div class="genre-grid" data-reveal-stagger>
                <?php foreach ($genres as $genre): ?>
                    <a href="<?= e(url('shop/?kategori=' . urlencode($genre['name']))); ?>" class="genre-card reveal reveal-scale">
                        <span class="genre-icon"><?= genreIcon($genre['icon']); ?></span>
                        <strong><?= e($genre['name']); ?></strong>
                        <small><?= e($genre['total']); ?> buku</small>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="section container showcase-section reveal reveal-up">
            <div class="showcase-panel reveal reveal-up">
                <div class="showcase-copy">
                    <p class="eyebrow">TOKO BUKU ONLINE</p>
                    <h2>Temukan Buku Pilihan dalam Satu Katalog Digital</h2>
                    <p class="showcase-lead">
                        Pustakata membantu Anda menemukan buku berdasarkan genre, rekomendasi pembaca,
                        ketersediaan stok, dan detail yang mudah dipahami sebelum membeli.
                    </p>

                    <div class="showcase-actions">
                        <a href="<?= e(url('shop/')); ?>" class="btn btn-primary">Jelajahi Katalog</a>
                        <a href="<?= e(url('index.php#terbaru')); ?>" class="btn btn-outline">Lihat Buku Terbaru</a>
                    </div>

                    <div class="showcase-stats" aria-label="Ringkasan katalog Pustakata" data-reveal-stagger>
                        <div class="reveal reveal-up">
                            <strong><?= e($totalBooks); ?></strong>
                            <span>Total Buku</span>
                        </div>
                        <div class="reveal reveal-up">
                            <strong><?= e($totalCategories); ?></strong>
                            <span>Total Kategori</span>
                        </div>
                        <div class="reveal reveal-up">
                            <strong><?= e($totalReviews); ?></strong>
                            <span>Total Ulasan</span>
                        </div>
                        <div class="reveal reveal-up">
                            <strong><?= e($availableBooks); ?></strong>
                            <span>Buku Tersedia</span>
                        </div>
                    </div>
                </div>

                <div class="showcase-visual" aria-label="Visual buku terbaru Pustakata">
                    <div class="showcase-book-stack" data-reveal-stagger="90">
                        <?php if ($showcaseBooks): ?>
                            <?php foreach ($showcaseBooks as $index => $book): ?>
                                <a class="showcase-book showcase-book-<?= e($index + 1); ?> reveal reveal-scale" href="<?= e(url('shop/detail.php?id=' . urlencode($book['id']))); ?>">
                                    <?php if (!empty($book['gambar'])): ?>
                                        <img src="<?= e(upload_url($book['gambar'])); ?>" alt="<?= e($book['judul']); ?>" loading="lazy" decoding="async">
                                    <?php else: ?>
                                        <div class="showcase-cover-fallback cover-<?= (($book['id'] ?? 1) % 4) + 1; ?>">
                                            <span><?= e($book['kategori']); ?></span>
                                            <strong><?= e($book['judul']); ?></strong>
                                        </div>
                                    <?php endif; ?>
                                    <span class="showcase-book-meta">
                                        <strong><?= e($book['judul']); ?></strong>
                                        <small><?= e($book['penulis']); ?></small>
                                    </span>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="showcase-book showcase-book-1 reveal reveal-scale">
                                <div class="showcase-cover-fallback cover-1">
                                    <span>Katalog</span>
                                    <strong>Pustakata</strong>
                                </div>
                                <span class="showcase-book-meta">
                                    <strong>Katalog Pustakata</strong>
                                    <small>Koleksi akan tampil setelah data buku tersedia</small>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="showcase-note reveal reveal-up" data-reveal-delay="220">
                        <span>Update katalog</span>
                        <strong><?= e($totalBooks); ?> buku aktif</strong>
                    </div>
                </div>
            </div>

            <div class="service-grid showcase-feature-grid" data-reveal-stagger>
                <article class="service-card reveal reveal-up">
                    <span>Katalog Dinamis</span>
                    <h3>Koleksi selalu tertata</h3>
                    <p>Judul, harga, stok, dan genre disajikan rapi agar Anda cepat menemukan bacaan yang cocok.</p>
                </article>
                <article class="service-card reveal reveal-up">
                    <span>Rating Pembaca</span>
                    <h3>Ulasan terlihat di katalog</h3>
                    <p>Lihat penilaian dan kesan pembaca lain sebelum memilih buku berikutnya.</p>
                </article>
                <article class="service-card reveal reveal-up">
                    <span>Keranjang Praktis</span>
                    <h3>Simpan pilihan sebelum checkout</h3>
                    <p>Kumpulkan buku incaran di keranjang, cek kembali pilihan, lalu lanjutkan checkout dengan alur belanja yang ringkas.</p>
                </article>
            </div>
        </section>

        <section class="community-band reveal reveal-up" id="komunitas">
            <div class="container section">
                <div class="section-header reveal reveal-up">
                    <div>
                        <p class="eyebrow">KOMUNITAS PEMBACA</p>
                        <h2>Ulasan dari Pembaca</h2>
                        <p>Temukan kesan pembaca lain dan cerita kecil di balik buku-buku pilihan mereka.</p>
                    </div>
                    <a href="<?= e(url('community.php')); ?>" class="text-link">Buka Komunitas →</a>
                </div>

                <?php if ($communityReviews): ?>
                    <div class="community-grid" data-reveal-stagger>
                        <?php foreach ($communityReviews as $review): ?>
                            <article class="community-card reveal reveal-up">
                                <div class="community-card-top">
                                    <div class="avatar"><?= e(substr($review['reviewer_name'], 0, 1)); ?></div>
                                    <div>
                                        <strong><?= e($review['reviewer_name']); ?></strong>
                                        <small>★ <?= e($review['rating']); ?>/5 untuk <a href="<?= e(url('shop/detail.php?id=' . urlencode($review['buku_id']))); ?>"><?= e($review['judul']); ?></a></small>
                                    </div>
                                </div>
                                <p><?= e($review['komentar'] ?: 'Pembaca ini memberi rating untuk buku tersebut.'); ?></p>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="community-empty home-community-empty reveal reveal-up">
                        <div class="home-community-copy">
                            <h3>Belum ada ulasan komunitas</h3>
                            <p>Ulasan pembaca akan tampil di sini saat komunitas mulai berbagi kesan membaca. Mulai dari katalog, pilih buku, lalu bagikan pengalaman membaca Anda.</p>
                            <div class="home-community-actions">
                                <a href="<?= e(url('shop/')); ?>" class="btn btn-primary">Jelajahi Katalog</a>
                                <a href="<?= e(url('community.php')); ?>" class="btn btn-outline">Buka Komunitas</a>
                            </div>
                        </div>
                        <aside class="home-community-prompt" aria-label="Ringkasan komunitas Pustakata">
                            <span>Ruang Pembaca</span>
                            <strong><?= e($totalReviews); ?> ulasan</strong>
                            <p><?= e($totalBooks); ?> buku menunggu cerita pertama dari pembaca Pustakata.</p>
                        </aside>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="cta-band reveal reveal-up">
            <div class="container">
                <p class="eyebrow">SIAP MEMBACA?</p>
                <h2>Temukan buku berikutnya dari katalog Pustakata.</h2>
                <a href="<?= e(url('shop/')); ?>" class="btn btn-primary">Buka Toko</a>
            </div>
        </section>
    </main>

    <?php include partial_path('footer.php'); ?>

    <script src="<?= e(asset('js/script.js')); ?>"></script>
</body>
</html>
