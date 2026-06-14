<?php
require_once __DIR__ . "/../config/conn.php";
require_admin();

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new RuntimeException('Metode hapus tidak valid.');
    }

    require_csrf();

    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0) {
        throw new RuntimeException('Data buku tidak ditemukan.');
    }

    $stmt = mysqli_prepare($conn, "SELECT gambar FROM buku2 WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $book = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if (!$book) {
        throw new RuntimeException('Buku sudah tidak tersedia.');
    }

    $delete = mysqli_prepare($conn, "DELETE FROM buku2 WHERE id = ?");
    mysqli_stmt_bind_param($delete, "i", $id);
    mysqli_stmt_execute($delete);

    if (!empty($book['gambar']) && is_file(UPLOAD_DIR . $book['gambar'])) {
        unlink(UPLOAD_DIR . $book['gambar']);
    }

    set_flash('success', 'Data buku berhasil dihapus.');
} catch (Throwable $exception) {
    set_flash('error', $exception->getMessage());
}

redirect('admin/books.php');
