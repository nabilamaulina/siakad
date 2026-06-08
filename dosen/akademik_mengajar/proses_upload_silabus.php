<?php
// File: dosen/akademik_mengajar/proses_upload_silabus.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_mk = $_POST['id_mk'] ?? 0;
    $file = $_FILES['file_silabus'] ?? null;

    if ($id_mk == 0 || !$file || $file['error'] !== 0) {
        die("Terjadi kesalahan: File tidak valid atau tidak ditemukan.");
    }

    // Tentukan direktori penyimpanan berkas silabus
    $target_dir = __DIR__ . '/../../assets/uploads/silabus/';
    
    // Buat folder otomatis jika folder belum ada di sistem
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0775, true);
    }

    // Filter ekstensi berkas yang diperbolehkan
    $filename = time() . '_' . basename($file['name']);
    $target_file = $target_dir . $filename;
    $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    $allowed_types = ['pdf', 'doc', 'docx'];

    if (!in_array($file_type, $allowed_types)) {
        die("Format ditolak: Hanya berkas PDF, DOC, dan DOCX yang diperbolehkan.");
    }

    // Batasi ukuran berkas maksimal 5MB
    if ($file['size'] > 5 * 1024 * 1024) {
        die("Ukuran berkas terlalu besar: Maksimal batas ukuran adalah 5MB.");
    }

    // Proses pemindahan file dari temporary server ke folder target
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        try {
            // Update nama file silabus ke dalam tabel mata_kuliah Anda
            $stmt = $pdo->prepare("UPDATE mata_kuliah SET silabus = ? WHERE id_mk = ?");
            $stmt->execute([$filename, $id_mk]);

            // Alihkan kembali ke halaman utama dengan tanda sukses
            header("Location: mata_kuliah.php?status=success_upload");
            exit;
        } catch (Exception $e) {
            die("Gagal menyimpan data ke database: " . $e->getMessage());
        }
    } else {
        die("Gagal mengunggah berkas: Terjadi kesalahan internal server saat memindahkan file.");
    }
} else {
    header("Location: mata_kuliah.php");
    exit;
}