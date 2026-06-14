<?php
require_once __DIR__ . "/../config/conn.php";
require_admin();

$id = (int) ($_GET['id'] ?? 0);
$isEdit = $id > 0;
$error = "";
$book = [
    'judul' => '',
    'penulis' => '',
    'penerbit' => '',
    'tahun_terbit' => date('Y'),
    'kategori' => '',
    'harga' => '',
    'stok' => '',
    'gambar' => '',
    'deskripsi' => ''
];

if ($isEdit) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM buku2 WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $book = mysqli_fetch_assoc($result);

    if (!$book) {
        redirect('admin/books.php');
    }
}

function upload_cover($fieldName, $oldFile = '')
{
    if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
        return $oldFile;
    }

    if ($_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE => "Ukuran gambar melebihi batas upload server.",
            UPLOAD_ERR_FORM_SIZE => "Ukuran gambar melebihi batas form.",
            UPLOAD_ERR_PARTIAL => "Upload gambar hanya terkirim sebagian.",
            UPLOAD_ERR_NO_TMP_DIR => "Folder temporary upload tidak tersedia di server.",
            UPLOAD_ERR_CANT_WRITE => "Server tidak bisa menulis file upload.",
            UPLOAD_ERR_EXTENSION => "Upload dihentikan oleh ekstensi PHP."
        ];

        throw new RuntimeException($uploadErrors[$_FILES[$fieldName]['error']] ?? "Upload gambar gagal.");
    }

    $uploadDir = UPLOAD_DIR;
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
        throw new RuntimeException("Folder upload tidak bisa dibuat.");
    }

    if (!is_writable($uploadDir)) {
        throw new RuntimeException("Folder assets/uploads belum bisa ditulis oleh server. Ubah permission folder upload.");
    }

    $allowedMimes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp'
    ];
    $tmpFile = $_FILES[$fieldName]['tmp_name'];

    if (!is_uploaded_file($tmpFile)) {
        throw new RuntimeException("File cover tidak valid sebagai upload dari form.");
    }

    if ($_FILES[$fieldName]['size'] > 2 * 1024 * 1024) {
        throw new RuntimeException("Ukuran gambar maksimal 2 MB.");
    }

    $detectedMimes = [];
    $imageInfo = @getimagesize($tmpFile);
    if (!$imageInfo || empty($imageInfo['mime'])) {
        throw new RuntimeException("File cover bukan gambar valid. Gunakan JPG, PNG, atau WEBP asli.");
    }

    $detectedMimes[] = $imageInfo['mime'];

    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $mime = finfo_file($finfo, $tmpFile);
            finfo_close($finfo);
            $detectedMimes[] = $mime;
        }
    } elseif (function_exists('mime_content_type')) {
        $detectedMimes[] = mime_content_type($tmpFile);
    }

    $detectedMimes = array_values(array_unique(array_filter($detectedMimes)));
    $primaryMime = $imageInfo['mime'];

    if (!isset($allowedMimes[$primaryMime])) {
        throw new RuntimeException("Format gambar harus JPG, PNG, atau WEBP. Tipe terdeteksi: " . $primaryMime . ".");
    }

    foreach ($detectedMimes as $detectedMime) {
        if (!isset($allowedMimes[$detectedMime]) || $allowedMimes[$detectedMime] !== $allowedMimes[$primaryMime]) {
            throw new RuntimeException("Isi file cover tidak konsisten. Upload ulang file JPG, PNG, atau WEBP asli.");
        }
    }

    $extension = $allowedMimes[$primaryMime];
    $newFile = 'cover_' . bin2hex(random_bytes(12)) . "." . $extension;
    $target = $uploadDir . $newFile;

    if (!move_uploaded_file($tmpFile, $target)) {
        throw new RuntimeException("Gambar tidak bisa disimpan ke folder assets/uploads.");
    }

    $oldFile = basename((string) $oldFile);
    if ($oldFile && is_file($uploadDir . $oldFile)) {
        unlink($uploadDir . $oldFile);
    }

    return $newFile;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        require_csrf();

        $judul = trim($_POST['judul'] ?? '');
        $penulis = trim($_POST['penulis'] ?? '');
        $penerbit = trim($_POST['penerbit'] ?? '');
        $tahun = (int) ($_POST['tahun_terbit'] ?? date('Y'));
        $kategori = trim($_POST['kategori'] ?? '');
        $harga = (int) ($_POST['harga'] ?? 0);
        $stok = (int) ($_POST['stok'] ?? 0);
        $deskripsi = trim($_POST['deskripsi'] ?? '');

        if ($judul === '' || $penulis === '' || $penerbit === '' || $kategori === '') {
            throw new RuntimeException("Semua field wajib diisi.");
        }

        if ($tahun < 1900 || $tahun > (int) date('Y')) {
            throw new RuntimeException("Tahun terbit tidak valid.");
        }

        if ($harga < 0 || $stok < 0) {
            throw new RuntimeException("Harga dan stok tidak boleh negatif.");
        }

        $gambar = upload_cover('gambar', $book['gambar'] ?? '');

        if ($isEdit) {
            $sql = "UPDATE buku2 SET judul=?, penulis=?, penerbit=?, tahun_terbit=?, kategori=?, harga=?, stok=?, gambar=?, deskripsi=? WHERE id=?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "sssisiissi", $judul, $penulis, $penerbit, $tahun, $kategori, $harga, $stok, $gambar, $deskripsi, $id);
        } else {
            $sql = "INSERT INTO buku2 (judul, penulis, penerbit, tahun_terbit, kategori, harga, stok, gambar, deskripsi) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "sssisiiss", $judul, $penulis, $penerbit, $tahun, $kategori, $harga, $stok, $gambar, $deskripsi);
        }

        mysqli_stmt_execute($stmt);
        set_flash('success', $isEdit ? 'Data buku berhasil diperbarui.' : 'Buku baru berhasil ditambahkan.');
        redirect('admin/books.php');
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
        $book = array_merge($book, $_POST);
    }
}
$adminPage = $isEdit ? 'books' : 'add-book';
?>
<!DOCTYPE html>
<html lang="id" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isEdit ? 'Edit' : 'Tambah'; ?> Buku - Pustakata</title>
    <?= theme_bootstrap_script(); ?>
    <link rel="stylesheet" href="<?= e(asset('css/style.css')); ?>">
</head>
<body>
    <div class="admin-shell">
        <?php include partial_path('admin_sidebar.php'); ?>

        <main class="admin-main narrow">
        <div class="admin-topbar reveal reveal-up">
            <div>
                <p class="eyebrow"><?= $isEdit ? 'EDIT KOLEKSI' : 'TAMBAH KOLEKSI'; ?></p>
                <h1><?= $isEdit ? 'Edit Buku' : 'Tambah Buku'; ?></h1>
                <p>Isi data buku dan pilih gambar cover jika tersedia.</p>
            </div>
            <a href="<?= e(url('admin/books.php')); ?>" class="btn btn-outline">Kembali</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error); ?></div>
        <?php endif; ?>
        <?= flash_messages(); ?>

        <form method="POST" enctype="multipart/form-data" class="form-card reveal reveal-up" data-validate>
            <?= csrf_field(); ?>
            <div class="form-grid">
                <label>Judul Buku
                    <input type="text" name="judul" required value="<?= e($book['judul']); ?>">
                </label>
                <label>Penulis
                    <input type="text" name="penulis" required value="<?= e($book['penulis']); ?>">
                </label>
                <label>Penerbit
                    <input type="text" name="penerbit" required value="<?= e($book['penerbit']); ?>">
                </label>
                <label>Tahun Terbit
                    <input type="number" name="tahun_terbit" min="1900" max="<?= date('Y'); ?>" required value="<?= e($book['tahun_terbit']); ?>">
                </label>
                <label>Kategori
                    <input type="text" name="kategori" required value="<?= e($book['kategori']); ?>">
                </label>
                <label>Harga
                    <input type="number" name="harga" min="0" required value="<?= e($book['harga']); ?>">
                </label>
                <label>Stok
                    <input type="number" name="stok" min="0" required value="<?= e($book['stok']); ?>">
                </label>
                <label>Gambar Cover
                    <input type="file" name="gambar" accept="image/jpeg,image/png,image/webp" data-image-preview="#coverPreview">
                </label>
            </div>

            <div class="cover-preview-box">
                <img
                    id="coverPreview"
                    class="preview-cover<?= empty($book['gambar']) ? ' is-hidden' : ''; ?>"
                    src="<?= !empty($book['gambar']) ? e(upload_url($book['gambar'])) : ''; ?>"
                    alt="Preview cover"
                >
                <span><?= !empty($book['gambar']) ? 'Cover saat ini. Pilih file baru untuk mengganti.' : 'Preview cover akan muncul setelah file dipilih.'; ?></span>
            </div>

            <label>Deskripsi
                <textarea name="deskripsi" rows="5" maxlength="800" data-character-counter="#descriptionCounter"><?= e($book['deskripsi']); ?></textarea>
                <small class="field-hint" id="descriptionCounter">0/800 karakter</small>
            </label>

            <button type="submit" class="btn btn-primary">Simpan Data</button>
        </form>
        </main>
    </div>
    <script src="<?= e(asset('js/script.js')); ?>"></script>
</body>
</html>
