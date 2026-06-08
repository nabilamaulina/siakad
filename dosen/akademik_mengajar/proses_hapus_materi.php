<?php
// File: dosen/akademik_mengajar/proses_hapus_materi.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';

// Ambil parameter dari URL
$id_materi = isset($_GET['id_materi']) ? (int)$_GET['id_materi'] : 0;
$id_mk = isset($_GET['id_mk']) ? (int)$_GET['id_mk'] : 0;

if ($id_materi > 0 && $id_mk > 0) {
    try {
        // 1. Ambil nama file terlebih dahulu untuk dihapus dari folder htdocs
        $stmt_select = $pdo->prepare("SELECT file_path FROM materi WHERE id_materi = ?");
        $stmt_select->execute([$id_materi]);
        $materi = $stmt_select->fetch(PDO::FETCH_ASSOC);

        if (!$materi) {
            // Jika data materi tidak ditemukan sama sekali di database
            die("Error: Data materi dengan ID tersebut tidak ditemukan di database. Silakan periksa struktur tabel Anda.");
        }

        $nama_file = $materi['file_path'];
        $target_file = __DIR__ . '/../../assets/uploads/materi/' . $nama_file;

        // 2. Jalankan perintah hapus data di database
        $stmt_delete = $pdo->prepare("DELETE FROM materi WHERE id_materi = ?");
        $stmt_delete->execute([$id_materi]);

        // PERBAIKAN UTAMA: Cek apakah ada baris data yang benar-benar terhapus di database
        if ($stmt_delete->rowCount() > 0) {
            
            // 3. Jika data di database berhasil dihapus, baru hapus file fisiknya
            if (!empty($nama_file) && file_exists($target_file)) {
                unlink($target_file);
            }

            // Alihkan halaman dengan status benar-benar sukses terhapus
            header("Location: sesi_materi.php?id_mk=" . $id_mk . "&status=success_delete");
            exit;
            
        } else {
            // Jika query berjalan tapi 0 baris terhapus (karena ID tidak cocok atau nama kolom salah)
            die("Gagal: Database tidak menghapus data apa pun. Pastikan kolom Primary Key di tabel Anda bernama 'id_materi'.");
        }

    } catch (Exception $e) {
        die("Gagal total menghapus data dari sistem: " . $e->getMessage());
    }
} else {
    header("Location: mata_kuliah.php");
    exit;
}