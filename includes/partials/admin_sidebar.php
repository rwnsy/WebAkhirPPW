<?php $adminPage = $adminPage ?? ''; ?>
<aside class="admin-sidebar">
    <a href="<?= e(url('admin/dashboard.php')); ?>" class="admin-logo">
        <img src="<?= e(asset('img/logo-pustakata-transparent.png')); ?>" alt="Pustakata">
    </a>

    <div class="admin-user">
        <span aria-hidden="true"><?= e(substr(current_user_name(), 0, 1)); ?></span>
        <div>
            <strong><?= e(current_user_name()); ?></strong>
            <small>Administrator</small>
        </div>
    </div>

    <nav class="admin-menu">
        <a href="<?= e(url('admin/dashboard.php')); ?>" class="<?= $adminPage === 'dashboard' ? 'active' : ''; ?>"><span class="admin-menu-icon">D</span>Dashboard</a>
        <a href="<?= e(url('admin/books.php')); ?>" class="<?= $adminPage === 'books' ? 'active' : ''; ?>"><span class="admin-menu-icon">B</span>Kelola Buku</a>
        <a href="<?= e(url('admin/book-form.php')); ?>" class="<?= $adminPage === 'add-book' ? 'active' : ''; ?>"><span class="admin-menu-icon">+</span>Tambah Buku</a>
        <a href="<?= e(url('admin/community-posts.php')); ?>" class="<?= $adminPage === 'community-posts' ? 'active' : ''; ?>"><span class="admin-menu-icon">M</span>Moderasi Komunitas</a>
        <a href="<?= e(url('admin/profile.php')); ?>" class="<?= $adminPage === 'profile' ? 'active' : ''; ?>"><span class="admin-menu-icon">P</span>Profil Admin</a>
        <a href="<?= e(url('index.php')); ?>"><span class="admin-menu-icon">W</span>Lihat Website</a>
        <a href="<?= e(url('auth/logout.php')); ?>" class="admin-menu-logout"><span class="admin-menu-icon">L</span>Logout</a>
    </nav>
</aside>
