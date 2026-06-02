<?php
// C:\xampp\htdocs\siakad\auth\logout.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// PERBAIKAN: Ditambahkan ../ agar sistem keluar dari folder 'auth' dan masuk ke 'config/database.php'
require_once '../config/database.php'; 

try {
    // 1. Periksa apakah ID sesi log pembuatan login tersimpan di session
    if (isset($_SESSION['id_sesi_log'])) {
        
        // Update jam logout berdasarkan id log yang disimpan sewaktu login tadi
        // DISESUAIKAN: Berdasarkan file log_aktivitas.php milikmu, nama tabelnya adalah 'login_logs'
        $stmt = $pdo->prepare("UPDATE login_logs SET logout_time = NOW() WHERE id_log = ?");
        $stmt->execute([$_SESSION['id_sesi_log']]);
        
    }
} catch (Exception $e) {
    // Dilewati jika database bermasalah agar proses logout tidak ikut macet
}

// 2. Bersihkan semua data session
$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Hancurkan session secara permanen
session_destroy();

// 4. Alihkan perhatian user kembali ke halaman login utama
// Karena logout.php di dalam folder 'auth', arahkan ke login.php di tingkat yang sama
header("Location: login.php");
exit;
?>