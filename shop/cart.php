<?php
require_once __DIR__ . "/../config/conn.php";
$activePage = 'cart';

$_SESSION['cart'] = $_SESSION['cart'] ?? [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        require_csrf();

        $action = $_POST['action'] ?? '';

        if ($action === 'add') {
            $bookId = (int) ($_POST['book_id'] ?? 0);
            $buyNow = ($_POST['buy_now'] ?? '') === '1';
            $stmt = mysqli_prepare($conn, "SELECT id, judul, stok FROM buku2 WHERE id = ? LIMIT 1");
            mysqli_stmt_bind_param($stmt, "i", $bookId);
            mysqli_stmt_execute($stmt);
            $book = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

            if (!$book) {
                throw new RuntimeException('Buku tidak ditemukan.');
            }

            if ((int) $book['stok'] <= 0) {
                throw new RuntimeException('Stok buku sedang kosong.');
            }

            $currentQty = (int) ($_SESSION['cart'][$bookId] ?? 0);
            $availableStock = (int) $book['stok'];

            if ($currentQty >= $availableStock) {
                $_SESSION['cart'][$bookId] = $availableStock;
                set_flash(
                    'warning',
                    'Jumlah "' . $book['judul'] . '" sudah mencapai stok tersedia.' . ($buyNow ? ' Silakan lanjutkan dari keranjang.' : '')
                );
            } else {
                $_SESSION['cart'][$bookId] = $currentQty + 1;
                set_flash(
                    'success',
                    '"' . $book['judul'] . '" ditambahkan ke keranjang.' . ($buyNow ? ' Silakan lanjutkan dari keranjang.' : '')
                );
            }
        } elseif ($action === 'update') {
            $hadStockWarning = false;
            $requestedQuantities = [];

            foreach (($_POST['qty'] ?? []) as $bookId => $qty) {
                $bookId = (int) $bookId;
                $qty = max(0, (int) $qty);

                if ($bookId <= 0 || $qty === 0) {
                    unset($_SESSION['cart'][$bookId]);
                    continue;
                }

                $requestedQuantities[$bookId] = $qty;
            }

            $stockById = [];
            if ($requestedQuantities) {
                $ids = array_keys($requestedQuantities);
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $stockTypes = str_repeat('i', count($ids));
                $stockStmt = mysqli_prepare($conn, "SELECT id, judul, stok FROM buku2 WHERE id IN ($placeholders)");
                mysqli_stmt_bind_param($stockStmt, $stockTypes, ...$ids);
                mysqli_stmt_execute($stockStmt);
                $stockResult = mysqli_stmt_get_result($stockStmt);

                while ($stockBook = mysqli_fetch_assoc($stockResult)) {
                    $stockById[(int) $stockBook['id']] = $stockBook;
                }
            }

            foreach ($requestedQuantities as $bookId => $qty) {
                $stockBook = $stockById[$bookId] ?? null;

                if (!$stockBook || (int) $stockBook['stok'] <= 0) {
                    unset($_SESSION['cart'][$bookId]);
                    $hadStockWarning = true;
                    continue;
                }

                if ($qty > (int) $stockBook['stok']) {
                    $qty = (int) $stockBook['stok'];
                    $hadStockWarning = true;
                }

                $_SESSION['cart'][$bookId] = $qty;
            }

            set_flash($hadStockWarning ? 'warning' : 'success', $hadStockWarning ? 'Sebagian jumlah keranjang disesuaikan dengan stok yang tersedia.' : 'Jumlah keranjang diperbarui.');
        } elseif ($action === 'remove') {
            $bookId = (int) ($_POST['book_id'] ?? 0);
            unset($_SESSION['cart'][$bookId]);
            set_flash('success', 'Item berhasil dihapus dari keranjang.');
        } elseif ($action === 'clear') {
            $_SESSION['cart'] = [];
            set_flash('success', 'Keranjang berhasil dikosongkan.');
        } elseif ($action === 'checkout') {
            if (empty($_SESSION['cart'])) {
                throw new RuntimeException('Keranjang masih kosong.');
            }

            require_login('shop/cart.php');

            $_SESSION['cart'] = [];
            set_flash('success', 'Checkout berhasil. Pembayaran belum diproses pada versi ini.');
        }
    } catch (Throwable $exception) {
        set_flash('error', $exception->getMessage());
    }

    redirect('shop/cart.php');
}

$cartItems = [];
$totalHarga = 0;
$cartIds = array_map('intval', array_keys($_SESSION['cart']));
$cartIds = array_values(array_filter($cartIds, function ($id) {
    return $id > 0;
}));

if ($cartIds) {
    $placeholders = implode(',', array_fill(0, count($cartIds), '?'));
    $types = str_repeat('i', count($cartIds));
    $sql = "SELECT id, judul, penulis, harga, stok, gambar FROM buku2 WHERE id IN ($placeholders)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, $types, ...$cartIds);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($book = mysqli_fetch_assoc($result)) {
        $qty = min((int) ($_SESSION['cart'][$book['id']] ?? 0), max(0, (int) $book['stok']));

        if ($qty <= 0) {
            unset($_SESSION['cart'][$book['id']]);
            continue;
        }

        $_SESSION['cart'][$book['id']] = $qty;
        $book['qty'] = $qty;
        $book['subtotal'] = $qty * (int) $book['harga'];
        $totalHarga += $book['subtotal'];
        $cartItems[] = $book;
    }
}
?>

<!DOCTYPE html>
<html lang="id" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang - Pustakata</title>
    <?= theme_bootstrap_script(); ?>
    <link rel="stylesheet" href="<?= e(asset('css/style.css')); ?>">
</head>
<body>

<?php include partial_path('public_navbar.php'); ?>

<main class="cart-page">
    <div class="container">
        <?= flash_messages(); ?>

        <div class="cart-box reveal reveal-up">
            <div class="section-header compact reveal reveal-up">
                <div>
                    <h1>Keranjang Belanja</h1>
                    <p>Cek kembali buku pilihan Anda. Checkout akan mengosongkan keranjang dan belum terhubung ke pembayaran online.</p>
                </div>
                <a href="<?= e(url('shop/')); ?>" class="btn btn-outline">Lanjut Belanja</a>
            </div>

            <?php if (!$cartItems): ?>
                <section class="empty-state compact reveal reveal-up">
                    <h2>Keranjang kosong</h2>
                    <p>Tambahkan buku dari halaman detail untuk melihat item di sini.</p>
                    <a href="<?= e(url('shop/')); ?>" class="btn btn-primary">Jelajahi Toko</a>
                </section>
            <?php else: ?>
                <form method="POST" data-validate>
                    <?= csrf_field(); ?>

                    <div class="cart-table-wrap reveal reveal-up">
                        <table class="cart-table">
                            <thead>
                                <tr>
                                    <th>Buku</th>
                                    <th>Harga</th>
                                    <th>Jumlah</th>
                                    <th>Subtotal</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cartItems as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="cart-product">
                                                <?php if (!empty($item['gambar'])): ?>
                                                    <img src="<?= e(upload_url($item['gambar'])); ?>" alt="<?= e($item['judul']); ?>" loading="lazy" decoding="async">
                                                <?php else: ?>
                                                    <span class="thumb thumb-empty">B</span>
                                                <?php endif; ?>
                                                <div>
                                                    <strong><?= e($item['judul']); ?></strong>
                                                    <small><?= e($item['penulis']); ?> · stok <?= e($item['stok']); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= rupiah($item['harga']); ?></td>
                                        <td>
                                            <input class="qty-input" type="number" min="0" max="<?= e($item['stok']); ?>" name="qty[<?= e($item['id']); ?>]" value="<?= e($item['qty']); ?>">
                                        </td>
                                        <td><?= rupiah($item['subtotal']); ?></td>
                                        <td>
                                            <button type="submit" form="removeBook<?= e($item['id']); ?>" class="link-button danger">
                                                Hapus
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="total-row">
                                    <td colspan="3">Total Harga</td>
                                    <td colspan="2"><?= rupiah($totalHarga); ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="cart-actions reveal reveal-up">
                        <button type="submit" name="action" value="update" class="btn btn-outline">Update Jumlah</button>
                        <button type="submit" name="action" value="clear" class="btn btn-outline" data-confirm="Kosongkan seluruh isi keranjang?">Kosongkan</button>
                        <button type="submit" name="action" value="checkout" class="btn btn-primary" data-confirm="Lanjutkan checkout? Pembayaran belum diproses pada versi ini.">Checkout</button>
                    </div>
                </form>

                <?php foreach ($cartItems as $item): ?>
                    <form id="removeBook<?= e($item['id']); ?>" method="POST" action="<?= e(url('shop/cart.php')); ?>" data-confirm="Hapus &quot;<?= e($item['judul']); ?>&quot; dari keranjang?">
                        <?= csrf_field(); ?>
                        <input type="hidden" name="action" value="remove">
                        <input type="hidden" name="book_id" value="<?= e($item['id']); ?>">
                    </form>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include partial_path('footer.php'); ?>
<script src="<?= e(asset('js/script.js')); ?>"></script>
</body>
</html>
