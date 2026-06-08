<?php
// File: dosen/akademik_mengajar/proses_upload_materi.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_mk = isset($_POST['id_mk']) ? (int)$_POST['id_mk'] : 0;
    $pertemuan_ke = isset($_POST['pertemuan_ke']) ? (int)$_POST['pertemuan_ke'] : 1;
    $judul_materi = isset($_POST['judul_materi']) ? trim($_POST['judul_materi']) : '';
    $file = $_FILES['file_materi'] ?? null;

    // Validasi data input awal
    if ($id_mk === 0 || empty($judul_materi) || !$file || $file['error'] !== 0) {
        $error_msg = "Data formulir tidak lengkap atau file gagal diunggah oleh server browser.";
        if ($file && $file['error'] !== 0) {
            $error_msg .= " Kode Error PHP File: " . $file['error'];
        }
        die($error_msg);
    }

    // Tentukan direktori penyimpanan fisik berkas materi
    // Diarahkan ke: htdocs/siakad/assets/uploads/materi/
    $target_dir = __DIR__ . '/../../assets/uploads/materi/';
    
    // Buat folder secara otomatis jika belum ada di dalam server XAMPP
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0775, true);
    }

    // Ambil ekstensi file asli dan generate nama acak agar tidak saling timpa
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = time() . '_' . uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $filename;

    // Daftar format berkas dokumen yang diperbolehkan
    $allowed_types = ['pdf', 'pptx', 'ppt', 'zip', 'rar', 'doc', 'docx'];

    if (!in_array($file_extension, $allowed_types)) {
        die("Format berkas ditolak! Hanya file berkas PDF, PPTX, DOCX, atau ZIP/RAR yang diperbolehkan.");
    }

    // Batasi ukuran file bahan ajar maksimal 10MB
    if ($file['size'] > 10 * 1024 * 1024) {
        die("Ukuran berkas terlalu besar! Maksimal batas ukuran yang diperbolehkan adalah 10MB.");
    }

    // Proses pemindahan file dari temporary local server ke folder target asset
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        try {
            // EKSEKUSI INSERT DATABASE: Menyesuaikan persis dengan tabel `materi` yang telah dibuat
            $stmt = $pdo->prepare("INSERT INTO materi (id_mk, pertemuan_ke, judul_materi, file_path) VALUES (?, ?, ?, ?)");
            $stmt->execute([$id_mk, $pertemuan_ke, $judul_materi, $filename]);

            // Alihkan kembali ke halaman manajemen dengan status sukses
            header("Location: sesi_materi.php?id_mk=" . $id_mk . "&status=success_materi");
            exit;
        } catch (Exception $e) {
            // Jika database gagal menyimpan, hapus file fisik yang tadi terlanjur dipindahkan agar tidak nyampah
            if (file_exists($target_file)) {
                unlink($target_file);
            }
            die("Gagal mencatat data ke database: " . $e->getMessage());
        }
    } else {
        die("Gagal memindahkan file bahan ajar ke server. Pastikan folder htdocs memiliki hak akses baca-tulis (write permission).");
    }
} else {
    header("Location: mata_kuliah.php");
    exit;
}