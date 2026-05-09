<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'donasiku_db');

$koneksi = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$koneksi) {
    die('<div style="font-family:sans-serif;padding:20px;background:#fee2e2;
         border-left:4px solid #dc2626;border-radius:8px;margin:20px;">
         <strong>Koneksi database gagal:</strong> ' . mysqli_connect_error() . '
         </div>');
}
mysqli_set_charset($koneksi, 'utf8mb4');

function rupiah(int $n): string {
    return 'Rp ' . number_format($n, 0, ',', '.');
}

function pct(int $terkumpul, int $target): int {
    if ($target <= 0) return 0;
    return min(100, (int) round($terkumpul / $target * 100));
}

function uploadGambar(array $file, string $subdir): string|false {
    $allowed = ['image/jpeg','image/png','image/webp','image/jpg'];
    if ($file['error'] !== UPLOAD_ERR_OK) return false;
    if (!in_array($file['type'], $allowed))  return false;
    if ($file['size'] > 5 * 1024 * 1024)    return false;
    $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $name = uniqid('img_', true) . '.' . $ext;
    $dir  = __DIR__ . "/../uploads/{$subdir}/";
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    if (!move_uploaded_file($file['tmp_name'], $dir . $name)) return false;
    return "uploads/{$subdir}/{$name}";
}

function esc(string $s): string {
    global $koneksi;
    return mysqli_real_escape_string($koneksi, $s);
}
?>
