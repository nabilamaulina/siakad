<?php
// auth/ganti-password.php
require_once '../config/security.php';
require_once '../config/database.php';
require_once '../config/function.php';

middleware(['mahasiswa', 'dosen']); // Proteksi Role: Hanya Mahasiswa dan Dosen

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $message = "<div class='alert alert-danger'>CSRF Token Invalid.</div>";
    } else {
        $password_lama = $_POST['password_lama'];
        $password_baru = $_POST['password_baru'];
        
        // Ambil data user saat ini
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id_user = ?");
        $stmt->execute([$_SESSION['id_user']]);
        $user = $stmt->fetch();

        if ($user && password_verify($password_lama, $user['password'])) {
            // Hash password baru
            $password_baru_hash = password_hash($password_baru, PASSWORD_BCRYPT);
            
            $update_stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id_user = ?");
            $update_stmt->execute([$password_baru_hash, $_SESSION['id_user']]);
            
            log_activity($_SESSION['id_user'], "User mengubah password login mandiri.");
            $message = "<div class='alert alert-success'>Password Anda berhasil diperbarui!</div>";
        } else {
            $message = "<div class='alert alert-danger'>Password lama yang Anda masukkan salah.</div>";
        }
    }
}
?>