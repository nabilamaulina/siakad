<?php
// auth/login_proses.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';

// ... (Proses pengecekan input username, password, dan verifikasi ke database tetap berjalan di atas sini) ...
// Misal variabel $user adalah hasil fetch data user yang cocok dari database.

if ($user && password_verify($password_input, $user['password'])) {
    
    // Ganti ID Session demi keamanan menghindari Session Fixation
    session_regenerate_id(true);

    // =========================================================================
    // 🗝️ 1. SET SESSION UTAMA USER
    // =========================================================================
    $_SESSION['id_user']   = $user['id_user'];
    $_SESSION['username']  = $user['username'];
    $_SESSION['role']      = strtolower(trim($user['role'])); // Menyeragamkan huruf kecil agar klop dengan krs.php

    // =========================================================================
    // 🔄 SINKRONISASI OTOMATIS JIKA YANG LOGIN ADALAH MAHASISWA
    // =========================================================================
    if ($_SESSION['role'] === 'mahasiswa') {
        try {
            // Cari id_mahasiswa berdasarkan id_user yang baru saja login
            $stmt_mhs = $pdo->prepare("SELECT id_mahasiswa FROM mahasiswa WHERE id_user = ? LIMIT 1");
            $stmt_mhs->execute([$user['id_user']]);
            $mahasiswa = $stmt_mhs->fetch();

            if ($mahasiswa) {
                $_SESSION['id_mahasiswa'] = $mahasiswa['id_mahasiswa'];
            } else {
                $_SESSION['id_mahasiswa'] = 0; // Set 0 jika relasi user ke mahasiswa tidak ditemukan
            }
        } catch (Exception $e_mhs) {
            $_SESSION['id_mahasiswa'] = 0;
        }
    }

    // =========================================================================
    // 📝 2. BLOK PENCATATAN LOG
    // =========================================================================
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown Device';
    $id_user    = $user['id_user'];
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    try {
        $stmt = $pdo->prepare("INSERT INTO login_logs (id_user, ip_address, user_agent, login_time) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$id_user, $ip_address, $user_agent]);
        
        // Simpan ID Log ke Session untuk dipanggil di logout.php
        $_SESSION['id_sesi_log'] = $pdo->lastInsertId();
    } catch (Exception $e_log) {
        // Fallback jika ada struktur log yang berbeda di sistem Anda
        $_SESSION['id_sesi_log'] = 0;
    }

    // =========================================================================
    // 🚀 3. ALIKHAN HALAMAN SESUAI ROLE MASING-MASING
    // =========================================================================
    if ($_SESSION['role'] === 'admin') {
        header("Location: ../admin/dashboard.php");
    } elseif ($_SESSION['role'] === 'dosen') {
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