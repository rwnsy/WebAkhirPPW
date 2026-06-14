<footer class="footer reveal reveal-up">
    <div class="container footer-grid">
        <div>
            <a href="<?= e(url('index.php')); ?>" class="footer-logo">
                <img src="<?= e(asset('img/logo-pustakata-transparent.png')); ?>" alt="Pustakata" loading="lazy" decoding="async">
            </a>
            <p>Platform kurasi buku digital dan fisik terbaik yang menemani antara pembaca dan karya-karya sastra terpilih dari seluruh dunia.</p>
        </div>

        <div>
            <h4>Jelajahi</h4>
            <a href="<?= e(url('shop/')); ?>">Katalog Buku</a>
            <a href="<?= e(url('shop/?kategori=' . urlencode('Novel'))); ?>">Novel</a>
            <a href="<?= e(url('shop/?kategori=' . urlencode('Teknologi'))); ?>">Teknologi</a>
            <a href="<?= e(url('index.php#rating')); ?>">Rating Tertinggi</a>
            <a href="<?= e(url('community.php')); ?>">Komunitas Pembaca</a>
        </div>

        <div>
            <h4>Dukungan</h4>
            <a href="<?= e(url('shop/cart.php')); ?>">Keranjang</a>
            <a href="<?= e(url('auth/login.php')); ?>">Login</a>
            <a href="<?= e(url('auth/signup.php')); ?>">Sign Up</a>
            <a href="<?= e(url('shop/')); ?>">Panduan Belanja</a>
        </div>

        <div>
            <h4>Newsletter</h4>
            <p>Dapatkan update koleksi terbaru dan promo eksklusif.</p>
            <form class="newsletter" action="<?= e(url('newsletter-subscribe.php')); ?>" method="POST" data-validate>
                <?= csrf_field(); ?>
                <input type="hidden" name="redirect_to" value="<?= e(current_request_path()); ?>">
                <input type="email" name="email" placeholder="Email Anda" required>
                <button type="submit">Berlangganan</button>
            </form>
        </div>
    </div>

    <div class="container footer-bottom">
        <p>© 2026 Pustakata. Curating Heritage, One Page at a Time.</p>
        <div>
            <a href="<?= e(url('index.php')); ?>">Kebijakan Privasi</a>
            <a href="<?= e(url('index.php')); ?>">Syarat & Ketentuan</a>
        </div>
    </div>
</footer>
