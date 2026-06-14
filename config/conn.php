<?php
if (session_status() === PHP_SESSION_NONE) {
    $secureCookie = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secureCookie,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

define('APP_ROOT', dirname(__DIR__));
define('UPLOAD_DIR', APP_ROOT . '/assets/uploads/');

$documentRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
$appRoot = realpath(APP_ROOT);
$appBasePath = '';

if ($documentRoot && $appRoot && strpos($appRoot, $documentRoot) === 0) {
    $appBasePath = trim(str_replace('\\', '/', substr($appRoot, strlen($documentRoot))), '/');
}

define('APP_BASE_PATH', $appBasePath === '' ? '' : '/' . $appBasePath);

/*
 * Konfigurasi database:
 * - Lokal Devilbox biasanya host `mysql`, user `root`, password kosong.
 * - Hostinger biasanya memakai host `localhost`.
 * - Nama database dan user Hostinger biasanya memiliki prefix akun, misalnya
 *   `u123456789_pustakata` dan `u123456789_admin`.
 * Isi credential asli lewat environment variable jika hosting mendukungnya,
 * atau ubah fallback di bawah sebelum upload ke public_html.
 */
$servername = getenv('PUSTAKATA_DB_HOST') ?: 'localhost';
$username = getenv('PUSTAKATA_DB_USER') ?: 'u169077025_ridho';
$password = getenv('PUSTAKATA_DB_PASS') ?: 'Wnzah2124';
$database = getenv('PUSTAKATA_DB_NAME') ?: 'u169077025_pustakata_db';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = mysqli_connect($servername, $username, $password, $database);
    mysqli_set_charset($conn, 'utf8mb4');
} catch (mysqli_sql_exception $exception) {
    http_response_code(500);
    exit('Koneksi database gagal. Periksa konfigurasi database dan pastikan database sudah diimport.');
}

function db_table_exists($table)
{
    global $conn;

    try {
        $stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?");
        mysqli_stmt_bind_param($stmt, "s", $table);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        return (int) ($row['total'] ?? 0) > 0;
    } catch (Throwable $exception) {
        return false;
    }
}

function db_column_exists($table, $column)
{
    global $conn;

    try {
        $stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
        mysqli_stmt_bind_param($stmt, "ss", $table, $column);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        return (int) ($row['total'] ?? 0) > 0;
    } catch (Throwable $exception) {
        return false;
    }
}

define('HAS_REVIEWS_TABLE', db_table_exists('reviews'));
define('HAS_COMMUNITY_POSTS_TABLE', db_table_exists('community_posts'));
define('HAS_NEWSLETTER_SUBSCRIBERS_TABLE', db_table_exists('newsletter_subscribers'));
define('HAS_USER_ROLE_COLUMN', db_column_exists('users', 'role'));

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function url($path = '')
{
    $path = ltrim((string) $path, '/');

    return APP_BASE_PATH . '/' . $path;
}

function asset($path)
{
    return url('assets/' . ltrim((string) $path, '/'));
}

function theme_bootstrap_script()
{
    return '<script>(function(){try{var theme=localStorage.getItem("pustakata-theme");if(theme==="light"||theme==="dark"){document.documentElement.setAttribute("data-theme",theme);}}catch(error){}})();</script>';
}

function upload_url($filename)
{
    return asset('uploads/' . rawurlencode((string) $filename));
}

function current_request_path()
{
    $requestUri = $_SERVER['REQUEST_URI'] ?? 'index.php';
    $path = parse_url($requestUri, PHP_URL_PATH);
    $query = parse_url($requestUri, PHP_URL_QUERY);

    if (!is_string($path) || $path === '') {
        $path = 'index.php';
    }

    $path = str_replace('\\', '/', $path);

    if (APP_BASE_PATH !== '' && strpos($path, APP_BASE_PATH . '/') === 0) {
        $path = substr($path, strlen(APP_BASE_PATH) + 1);
    } elseif (APP_BASE_PATH !== '' && $path === APP_BASE_PATH) {
        $path = 'index.php';
    } else {
        $path = ltrim($path, '/');
    }

    if ($path === '') {
        $path = 'index.php';
    }

    return is_string($query) && $query !== '' ? $path . '?' . $query : $path;
}

function safe_redirect_path($path, $fallback = 'index.php')
{
    $path = str_replace(["\r", "\n"], '', trim((string) $path));

    if ($path === '' || preg_match('/^[a-z][a-z0-9+.-]*:/i', $path) || strpos($path, '//') === 0) {
        return $fallback;
    }

    $path = str_replace('\\', '/', $path);
    $path = ltrim($path, '/');
    $appBase = trim(APP_BASE_PATH, '/');

    if ($appBase !== '' && ($path === $appBase || strpos($path, $appBase . '/') === 0)) {
        $path = ltrim(substr($path, strlen($appBase)), '/');
    }

    return $path === '' ? $fallback : $path;
}

function partial_path($filename)
{
    return APP_ROOT . '/includes/partials/' . ltrim((string) $filename, '/');
}

function redirect($path)
{
    header('Location: ' . url($path));
    exit;
}

function rupiah($angka)
{
    return 'Rp ' . number_format((int) $angka, 0, ',', '.');
}

function stock_badge($stok)
{
    $stok = (int) $stok;

    if ($stok <= 0) {
        return ['label' => 'Habis', 'class' => 'stock-empty'];
    }

    if ($stok <= 5) {
        return ['label' => 'Stok Menipis', 'class' => 'stock-low'];
    }

    return ['label' => 'Tersedia', 'class' => 'stock-ready'];
}

function is_logged_in()
{
    return isset($_SESSION['user']);
}

function current_user_name()
{
    return $_SESSION['user']['nama'] ?? 'Pengguna';
}

function current_user_role()
{
    return $_SESSION['user']['role'] ?? 'guest';
}

function is_admin()
{
    return current_user_role() === 'admin';
}

function require_login()
{
    if (!is_logged_in()) {
        set_flash('error', 'Silakan login terlebih dahulu.');
        redirect('auth/login.php');
    }
}

function require_admin()
{
    require_login();

    if (!is_admin()) {
        set_flash('error', 'Akses admin hanya untuk akun administrator.');
        redirect('index.php');
    }
}

function csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field()
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf_token($token)
{
    return is_string($token)
        && $token !== ''
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function require_csrf()
{
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        throw new RuntimeException('Sesi form tidak valid. Muat ulang halaman lalu coba lagi.');
    }
}

function set_flash($type, $message)
{
    $_SESSION['flash'][] = [
        'type' => $type,
        'message' => $message
    ];
}

function get_flashes()
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);

    return $flashes;
}

function flash_messages()
{
    $html = '';

    foreach (get_flashes() as $flash) {
        $allowedTypes = ['success', 'warning', 'error'];
        $type = in_array($flash['type'] ?? '', $allowedTypes, true) ? $flash['type'] : 'error';
        $html .= '<div class="alert alert-' . $type . ' flash-message" role="status">' . e($flash['message']) . '</div>';
    }

    return $html;
}
