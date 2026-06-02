<?php
// siakad/mahasiswa/dashboard.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// PERBAIKAN PATH JALUR DATABASE (Hanya mundur 1 tingkat ke folder siakad)
require_once __DIR__ . '/../templates/header.php'; 
require_once __DIR__ . '/templates/sidebar.php'; 
require_once __DIR__ . '/../config/database.php'; // SANGAT PENTING: Menggunakan '../' agar pas ke siakad/config/database.php

$nama_dosen = $_SESSION['nama_user'] ?? $_SESSION['username'] ?? 'Dosen Pengajar';
$id_user_dosen = $_SESSION['id_user'] ?? 0;

date_default_timezone_set('Asia/Jakarta');
$jam = date('H');
if ($jam >= 5 && $jam < 11) {
    $sapaan = "Selamat Pagi";
    $icon_sapaan = "fa-cloud-sun text-warning";
} elseif ($jam >= 11 && $jam < 15) {
    $sapaan = "Selamat Siang";
    $icon_sapaan = "fa-sun text-warning";
} elseif ($jam >= 15 && $jam < 18) {
    $sapaan = "Selamat Sore";
    $icon_sapaan = "fa-cloud-meatball text-info";
} else {
    $sapaan = "Selamat Malam";
    $icon_sapaan = "fa-moon text-light";
}

$hari_indo = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
$bulan_indo = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agt', 'Sep', 'Okt', 'Nov', 'Des'];

$hari_ini = $hari_indo[date('l')];
$tgl_ini  = date('d');
$bln_ini  = $bulan_indo[(int)date('m')];
$thn_ini  = date('Y');
$tanggal_lengkap = "$hari_ini, $tgl_ini $bln_ini $thn_ini";

$count_mk_dosen = 0;
$count_mhs_bimbingan = 0;
$count_kelas_aktif = 0;
$agenda_mengajar = [];

try {
    $stmt_dosen = $pdo->prepare("SELECT id_dosen, nama_dosen FROM dosen WHERE id_user = ?");
    $stmt_dosen->execute([$id_user_dosen]);
    $data_dosen = $stmt_dosen->fetch(PDO::FETCH_ASSOC);
    $id_dosen = $data_dosen['id_dosen'] ?? 0;

    if ($data_dosen) {
        $nama_dosen = $data_dosen['nama_dosen'];
    }

    if ($id_dosen > 0) {
        $stmt_mk = $pdo->prepare("SELECT COUNT(DISTINCT id_mk) FROM jadwal WHERE id_dosen = ?");
        $stmt_mk->execute([$id_dosen]);
        $count_mk_dosen = (int)$stmt_mk->fetchColumn();

        $stmt_mhs = $pdo->prepare("SELECT COUNT(*) FROM mahasiswa WHERE id_dosen_wali = ?");
        $stmt_mhs->execute([$id_dosen]);
        $count_mhs_bimbingan = (int)$stmt_mhs->fetchColumn();

        $query_jadwal = "SELECT j.*, mk.nama_mk, mk.kode_mk, k.nama_kelas AS kelas, j.ruang AS ruangan 
                         FROM jadwal j 
                         JOIN matakuliah mk ON j.id_mk = mk.id_mk 
                         JOIN kelas k ON j.id_kelas = k.id_kelas
                         WHERE j.id_dosen = ? 
                         ORDER BY FIELD(j.hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'), j.jam_mulai ASC";

        $stmt_jadwal = $pdo->prepare($query_jadwal);
        $stmt_jadwal->execute([$id_dosen]);
        $agenda_mengajar = $stmt_jadwal->fetchAll(PDO::FETCH_ASSOC);

        $count_kelas_aktif = count($agenda_mengajar);
    }
} catch (Exception $e) {
    // Fallback Mock Data jika database bermasalah
    $count_mk_dosen = 3;
    $count_mhs_bimbingan = 24;
    $count_kelas_aktif = 3;

    $agenda_mengajar = [
        ['id_jadwal' => 1, 'nama_mk' => 'Pemrograman Web Berbasis PHP (Offline Mode)', 'kelas' => 'IF-A Pagi', 'hari' => 'Senin', 'jam_mulai' => '08:00', 'jam_selesai' => '10:30', 'ruangan' => 'Lab Komputer 2'],
        ['id_jadwal' => 2, 'nama_mk' => 'Kecerdasan Buatan (Artificial Intelligence)', 'kelas' => 'IF-B Malam', 'hari' => 'Rabu', 'jam_mulai' => '19:00', 'jam_selesai' => '21:30', 'ruangan' => 'Ruang Teori 301']
    ];
}
?>

<style>
    :root {
        --siakad-primary: #245358;
        --siakad-secondary: #5e8281;
        --siakad-light: #f8fafc;
        --siakad-border: rgba(94, 130, 129, 0.15);
    }

    .card-clickable {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        text-decoration: none !important;
        border: 1px solid var(--siakad-border) !important;
    }

    .card-clickable:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 20px rgba(36, 83, 88, 0.08) !important;
        border-color: var(--siakad-secondary) !important;
    }

    .quick-link-btn {
        transition: all 0.2s ease;
        border: 1px solid #e2e8f0;
        background-color: #ffffff;
    }

    .quick-link-btn:hover {
        background-color: var(--siakad-primary) !important;
        color: #ffffff !important;
        border-color: var(--siakad-primary) !important;
    }

    .table-siakad thead th {
        background-color: #f1f5f9 !important;
        color: #475569 !important;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 11px;
    }
</style>

<div class="row mb-4">
    <div class="col-12">
        <div class="p-4 p-md-5 text-white shadow-sm d-flex align-items-center justify-content-between" style="background: linear-gradient(135deg, #043e52 0%, #025864 100%); border-radius: 24px;">
            <div>
                <span class="badge bg-white bg-opacity-20 px-3 py-2 rounded-pill small fw-bold mb-3 text-uppercase tracking-wider" style="font-size: 11px; color: #043e52;">
                    <i class="fa-solid fa-graduation-cap me-1"></i> SIAKAD PORTAL UTAMA
                </span>
                <h2 class="fw-extrabold mb-1" style="font-size: calc(1.3rem + 0.6vw); font-weight: 800;">
                    <i class="fa-solid <?= $icon_sapaan; ?> me-2"></i><?= $sapaan; ?>, <?= htmlspecialchars($nama_dosen); ?>!
                </h2>
                <p class="text-white-50 m-0 small" style="font-style: italic;">
                    <i class="fa-solid fa-quote-left me-1 opacity-50"></i> Mendidik dengan hati, mencetak generasi berprestasi.
                </p>
            </div>
            <div class="d-none d-md-block text-end">
                <span class="badge bg-white bg-opacity-10 text-white p-2 px-4 rounded-pill shadow-sm" style="font-size: 14px; backdrop-filter: blur(4px);">
                    <i class="fa-regular fa-calendar-days me-2 text-info"></i><?= $tanggal_lengkap; ?>
                </span>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-4">
        <a href="akademik/jadwal.php" class="card card-custom card-clickable shadow-sm h-100 bg-white">
            <div class="card-body p-4 d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted text-uppercase fw-bold d-block mb-1" style="font-size: 11px;">Mata Kuliah Diampu</span>
                    <h2 class="fw-bold mb-0" style="color: var(--siakad-primary);"><?= $count_mk_dosen; ?> <span style="font-size: 14px;" class="text-muted fw-normal">Sesi</span></h2>
                </div>
                <div class="rounded-3 p-3" style="background-color: rgba(36, 83, 88, 0.08); color: var(--siakad-primary);">
                    <i class="fa-solid fa-book-open fs-3"></i>
                </div>
            </div>
        </a>
    </div>

    <div class="col-12 col-sm-6 col-xl-4">
        <a href="akademik/krs.php" class="card card-custom card-clickable shadow-sm h-100 bg-white">
            <div class="card-body p-4 d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted text-uppercase fw-bold d-block mb-1" style="font-size: 11px;">Anak Bimbingan Wali</span>
                    <h2 class="fw-bold mb-0" style="color: var(--siakad-primary);"><?= $count_mhs_bimbingan; ?> <span style="font-size: 14px;" class="text-muted fw-normal">Siswa</span></h2>
                </div>
                <div class="rounded-3 p-3 text-info" style="background-color: rgba(13, 202, 240, 0.08);">
                    <i class="fa-solid fa-users fs-3"></i>
                </div>
            </div>
        </a>
    </div>

    <div class="col-12 col-sm-6 col-xl-4">
        <a href="akademik/jadwal.php" class="card card-custom card-clickable shadow-sm h-100 bg-white">
            <div class="card-body p-4 d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted text-uppercase fw-bold d-block mb-1" style="font-size: 11px;">Jadwal Kelas Aktif</span>
                    <h2 class="fw-bold mb-0" style="color: var(--siakad-primary);"><?= $count_kelas_aktif; ?> <span style="font-size: 14px;" class="text-muted fw-normal">Sesi/Minggu</span></h2>
                </div>
                <div class="rounded-3 p-3 text-warning" style="background-color: rgba(255, 193, 7, 0.08);">
                    <i class="fa-solid fa-calendar-days fs-3"></i>
                </div>
            </div>
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card card-custom shadow-sm border-0 h-100 bg-white">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <h5 class="mb-0 fw-bold text-dark" style="font-size: 16px;">
                    <i class="fa-solid fa-list-check me-2 text-secondary"></i>Agenda Jadwal Mengajar Anda
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0 table-hover table-siakad">
                        <thead>
                            <tr>
                                <th class="ps-4 py-3">Mata Kuliah</th>
                                <th class="py-3">Kelas</th>
                                <th class="py-3">Hari & Waktu</th>
                                <th class="py-3">Ruangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($agenda_mengajar)): ?>
                                <?php foreach ($agenda_mengajar as $row): ?>
                                    <tr>
                                        <td class="ps-4 fw-bold text-dark"><?= htmlspecialchars($row['nama_mk']); ?></td>
                                        <td><?= htmlspecialchars($row['kelas']); ?></td>
                                        <td><?= htmlspecialchars($row['hari']); ?> (<?= htmlspecialchars($row['jam_mulai']); ?>)</td>
                                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($row['ruangan']); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">Tidak ada jadwal mengajar aktif.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card card-custom shadow-sm border-0 mb-4 bg-white">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="mb-0 fw-bold text-dark" style="font-size: 15px;">Aksi Pengolahan Instan</h5>
            </div>
            <div class="card-body pt-3">
                <div class="d-flex flex-column gap-2">
                    <a href="akademik/khs.php" class="btn quick-link-btn text-start p-3 rounded-3 text-dark fw-semibold" style="font-size: 13px; text-decoration:none;">
                        <i class="fa-solid fa-square-poll-horizontal me-2 text-success"></i> Input & Koreksi Nilai Akhir
                    </a>
                    <a href="akademik/krs.php" class="btn quick-link-btn text-start p-3 rounded-3 text-dark fw-semibold" style="font-size: 13px; text-decoration:none;">
                        <i class="fa-solid fa-file-shield me-2 text-info"></i> Validasi KRS Mahasiswa
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

</div> </div> <?php
require_once __DIR__ . '/../templates/footer.php';
?>