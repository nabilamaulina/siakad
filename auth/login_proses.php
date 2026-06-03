<?php
// auth/login_proses.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';

// ... (Proses pengecekan input username, password, dan verifikasi ke database) ...
// Misal variabel $user adalah hasil fetch data user yang cocok dari database.

if ($user && password_verify($password_input, $user['password'])) {
    
    // 1. Set Session Utama User
    $_SESSION['id_user']   = $user['id_user'];
    $_SESSION['username']  = $user['username'];
    $_SESSION['role']      = $user['role']; 

    // 2. BLOK PENCATATAN LOG (Kode yang sudah kamu perbaiki sesuai BUG #I)
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown Device';
    $id_user    = $user['id_user'];
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    $stmt = $pdo->prepare("INSERT INTO login_logs (id_user, ip_address, user_agent, login_time) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$id_user, $ip_address, $user_agent]);

    // Simpan ID Log ke Session untuk dipanggil di logout.php
    $_SESSION['id_sesi_log'] = $pdo->lastInsertId();

    // 3. Alihkan halaman sesuai Role masing-masing
    if ($user['role'] === 'admin') {
        header("Location: ../admin/dashboard.php");
    } elseif ($user['role'] === 'dosen') {
        header("Location: ../dosen/perwalian/krs_validasi.php");
    } else {
        header("Location: ../mahasiswa/akademik/krs.php");
    }
    exit;
} else {
    // Jika login gagal
    $_SESSION['error'] = "Username atau password salah!";
    header("Location: login.php");
    exit;
}