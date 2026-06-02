<?php
// siakad/auth/reg-admin.php
require_once '../config/database.php';

echo "<h3>--- Sistem Diagnosis & Registrasi Darurat ---</h3>";

try {
    // 1. Tes Koneksi Database
    if ($pdo) {
        echo "<span style='color:green;'>✔ Koneksi ke database MySQL BERHASIL.</span><br>";
    }

    // 2. Bersihkan user lama bernama 'admin' agar tidak duplikat
    $pdo->query("DELETE FROM users WHERE username = 'admin'");

    // 3. Generate Password Baru menggunakan PHP Native Engine
    $username = 'admin';
    $password_polos = 'password123';
    $password_hash = password_hash($password_polos, PASSWORD_BCRYPT);

    // 4. Insert ke database menggunakan Prepared Statement
    $stmt = $pdo->prepare("INSERT INTO users (username, password, role, is_active) VALUES (?, ?, 'admin', 1)");
    $stmt->execute([$username, $password_hash]);

    echo "<span style='color:green;'>✔ Akun Admin berhasil didaftarkan ulang melalui PHP BCRYPT.</span><br><br>";
    echo "<b>Kredensial Login Anda:</b><br>";
    echo "Username: <code style='background:#eee;padding:2px 6px;'>admin</code><br>";
    echo "Password: <code style='background:#eee;padding:2px 6px;'>password123</code><br><br>";
    echo "<a href='login.php'>👉 Klik di sini untuk kembali ke halaman login</a>";

} catch (PDOException $e) {
    echo "<span style='color:red;'>❌ Gagal menjalankan instruksi: " . $e->getMessage() . "</span><br>";
    echo "Harap periksa apakah tabel 'users' benar-benar ada di phpMyAdmin.";
}
?>