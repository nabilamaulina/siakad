<?php
// admin/akademik/proses.php
require_once '../../config/security.php';
require_once '../../config/database.php';
require_once '../../config/function.php';

middleware(['admin']);

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// 1. TAMBAH MATA KULIAH BARU
if ($action === 'insert_mk' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode_mk  = sanitize($_POST['kode_mk']);
    $nama_mk  = sanitize($_POST['nama_mk']);
    $sks      = (int)$_POST['sks'];
    $semester = (int)$_POST['semester'];

    $stmt = $pdo->prepare("INSERT INTO mata_kuliah (kode_mk, nama_mk, sks, semester) VALUES (?, ?, ?, ?)");
    $stmt->execute([$kode_mk, $nama_mk, $sks, $semester]);

    log_activity($_SESSION['id_user'], "Menambah data mata kuliah baru: $nama_mk ($kode_mk)");
    header("Location: index.php#panel-mk");
    exit();
}

// 2. HAPUS DATA MATA KULIAH
if ($action === 'delete_mk') {
    $id_mk = $_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM mata_kuliah WHERE id_mk = ?");
    $stmt->execute([$id_mk]);

    log_activity($_SESSION['id_user'], "Menghapus data mata kuliah ID: $id_mk");
    header("Location: index.php#panel-mk");
    exit();
}

// 3. TAMBAH JADWAL / PLOTTING KULIAH
if ($action === 'insert_jadwal' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_mk       = $_POST['id_mk'];
    $id_dosen    = $_POST['id_dosen'];
    $id_kelas    = $_POST['id_kelas'];
    $id_semester = $_POST['id_semester'];
    $hari        = $_POST['hari'];
    $jam_mulai   = $_POST['jam_mulai'];
    $jam_selesai = $_POST['jam_selesai'];

    // Sesuai konfirmasi awal, kolom ruangan bawaan di tabel jadwal diset default "-"
    $ruangan = "-"; 

    $stmt = $pdo->prepare("INSERT INTO jadwal (id_mk, id_kelas, id_dosen, id_semester, hari, jam_mulai, jam_selesai, ruangan) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$id_mk, $id_kelas, $id_dosen, $id_semester, $hari, $jam_mulai, $jam_selesai, $ruangan]);

    log_activity($_SESSION['id_user'], "Membuat alokasi plotting jadwal mengajar baru");
    header("Location: index.php");
    exit();
}

// 4. HAPUS DATA PLOT JADWAL KULIAH
if ($action === 'delete_jadwal') {
    $id_jadwal = $_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM jadwal WHERE id_jadwal = ?");
    $stmt->execute([$id_jadwal]);

    log_activity($_SESSION['id_user'], "Menghapus alokasi jadwal mengajar ID: $id_jadwal");
    header("Location: index.php");
    exit();
}

// 5. REGISTRASI KRS MANUAL OLEH ADMIN
if ($action === 'insert_krs_manual' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_mhs    = $_POST['id_mahasiswa'];
    $id_jadwal = $_POST['id_jadwal'];

    // Ambil data ID Semester dari relasi tabel jadwal yang dipilih
    $get_sem = $pdo->prepare("SELECT id_semester FROM jadwal WHERE id_jadwal = ?");
    $get_sem->execute([$id_jadwal]);
    $id_semester = $get_sem->fetchColumn();

    try {
        $stmt = $pdo->prepare("INSERT INTO krs (id_mahasiswa, id_jadwal, id_semester, status_krs) VALUES (?, ?, ?, 'Disetujui')");
        $stmt->execute([$id_mhs, $id_jadwal, $id_semester]);
        log_activity($_SESSION['id_user'], "Mendaftarkan KRS manual mahasiswa ID: $id_mhs");
    } catch (Exception $e) {
        // Mencegah error duplikasi data jika di klik berulang kali
    }

    header("Location: krs_mahasiswa.php?id_mhs_krs=" . $id_mhs);
    exit();
}

// 6. PEMBATALAN / DROP MATA KULIAH DARI KRS
if ($action === 'drop_krs') {
    $id_krs = $_GET['id'];
    $mhs    = $_GET['mhs'];

    $stmt = $pdo->prepare("DELETE FROM krs WHERE id_krs = ?");
    $stmt->execute([$id_krs]);

    log_activity($_SESSION['id_user'], "Membatalkan salah satu isian mata kuliah KRS Mahasiswa ID: $mhs");
    header("Location: krs_mahasiswa.php?id_mhs_krs=" . $mhs);
    exit();
}

// 7. SIMPAN / UPDATE LEMBAR PRESENSI MAHASISWA
if ($action === 'simpan_absensi' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_jadwal = $_POST['id_jadwal'];
    $pertemuan = (int)$_POST['pertemuan'];
    $status_absen = $_POST['status_absen'] ?? []; 
    $tanggal_sekarang = date('Y-m-d');

    foreach ($status_absen as $id_krs => $status) {
        $check = $pdo->prepare("SELECT id_presensi FROM presensi WHERE id_krs = ? AND pertemuan_ke = ?");
        $check->execute([$id_krs, $pertemuan]);
        $id_presensi = $check->fetchColumn();

        if ($id_presensi) {
            $update = $pdo->prepare("UPDATE presensi SET status_hadir = ?, tanggal = ? WHERE id_presensi = ?");
            $update->execute([$status, $tanggal_sekarang, $id_presensi]);
        } else {
            $insert = $pdo->prepare("INSERT INTO presensi (id_krs, pertemuan_ke, tanggal, status_hadir) VALUES (?, ?, ?, ?)");
            $insert->execute([$id_krs, $pertemuan, $tanggal_sekarang, $status]);
        }
    }

    log_activity($_SESSION['id_user'], "Memperbarui modul presensi kelas mengajar ID: $id_jadwal Tatap Muka $pertemuan");
    header("Location: absensi.php?id_jadwal=" . $id_jadwal . "&pertemuan=" . $pertemuan);
    exit();
}