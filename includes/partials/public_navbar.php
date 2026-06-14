<?php
$activePage = $activePage ?? '';
$searchValue = $searchValue ?? ($_GET['q'] ?? '');
$cartCount = array_sum($_SESSION['cart'] ?? []);
?>
<header class="site-header">
    <nav class="navbar container">
        <a href="<?= e(url('index.php')); ?>" class="brand">
            <img src="<?= e(asset('img/logo-pustakata-transparent.png')); ?>" alt="Pustakata">
        </a>

        <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation" aria-expanded="false">☰</button>

        <div class="nav-menu" id="navMenu">
            <a href="<?= e(url('index.php')); ?>" class="<?= $activePage === 'home' ? 'active' : ''; ?>">Beranda</a>
            <a href="<?= e(url('shop/')); ?>" class="<?= $activePage === 'shop' ? 'active' : ''; ?>">Toko</a>
            <a href="<?= e(url('community.php')); ?>" class="<?= $activePage === 'community' ? 'active' : ''; ?>">Komunitas</a>
            <a href="<?= e(url('index.php#terbaru')); ?>">Terbaru</a>
        </div>

        <div class="nav-actions">
            <form class="search-box" method="GET" action="<?= e(url('shop/')); ?>" data-search-feedback>
                <span>⌕</span>
                <input type="text" name="q" placeholder="Cari buku, penulis..." value="<?= e($searchValue); ?>">
            </form>
            <a class="icon-btn cart-icon" title="Keranjang" href="<?= e(url('shop/cart.php')); ?>">🛒<?php if ($cartCount > 0): ?><span><?= e($cartCount); ?></span><?php endif; ?></a>
            <?php if (is_admin()): ?>
                <a class="btn btn-outline btn-small" href="<?= e(url('admin/dashboard.php')); ?>">Dashboard</a>
            <?php elseif (is_logged_in()): ?>
                <a class="btn btn-outline btn-small" href="<?= e(url('auth/logout.php')); ?>">Logout</a>
            <?php else: ?>
                <a class="btn btn-outline btn-small" href="<?= e(url('auth/login.php')); ?>">Login</a>
                <a class="btn btn-primary btn-small" href="<?= e(url('auth/signup.php')); ?>">Sign Up</a>
            <?php endif; ?>
            <button class="theme-btn" id="themeToggle" title="Ganti tema">◐</button>
            <div class="profile-dot"><?= is_logged_in() ? e(substr(current_user_name(), 0, 1)) : 'P'; ?></div>
        </div>
    </nav>
</header>
