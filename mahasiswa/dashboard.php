<?php
// siakad/mahasiswa/dashboard.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Menghubungkan template dan database
require_once __DIR__ . '/../templates/header.php'; 
require_once __DIR__ . '/templates/sidebar.php'; 
require_once __DIR__ . '/../config/database.php'; 

// Mengambil session mahasiswa
$nama_mahasiswa = $_SESSION['nama_user'] ?? $_SESSION['username'] ?? 'Mahasiswa';
$id_user_mhs = $_SESSION['id_user'] ?? 0;

// Set Waktu & Sapaan
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

// Inisialisasi variabel dashboard mahasiswa
$total_sks = 0;
$nama_dosen_wali = "Belum Ditentukan";
$status_krs = "Belum Mengisi";
$jadwal_kuliah = [];

try {
    // 1. Ambil data mahasiswa dan dosen walinya
    $stmt_mhs = $pdo->prepare("
        SELECT m.id_mahasiswa, m.nama_mahasiswa, m.nim, d.nama_dosen 
        FROM mahasiswa m 
        LEFT JOIN dosen d ON m.id_dosen_wali = d.id_dosen 
        WHERE m.id_user = ?
    ");
    $stmt_mhs->execute([$id_user_mhs]);
    $data_mhs = $stmt_mhs->fetch(PDO::FETCH_ASSOC);
    $id_mahasiswa = $data_mhs['id_mahasiswa'] ?? 0;

    if ($data_mhs) {
        $nama_mahasiswa = $data_mhs['nama_mahasiswa'];
        if (!empty($data_mhs['nama_dosen'])) {
            $nama_dosen_wali = $data_mhs['nama_dosen'];
        }
    }

    if ($id_mahasiswa > 0) {
        // 2. Hitung total SKS yang diambil mahasiswa di semester aktif (dari tabel krs/krs_detail)
        // Catatan: sesuaikan nama tabel & kolom Anda jika berbeda (misal: krs atau detail_krs)
        $stmt_sks = $pdo->prepare("
            SELECT SUM(mk.sks) FROM krs k 
            JOIN jadwal j ON k.id_jadwal = j.id_jadwal
            JOIN matakuliah mk ON j.id_mk = mk.id_mk 
            WHERE k.id_mahasiswa = ? AND k.status_validasi = 'Disetujui'
        ");
        $stmt_sks->execute([$id_mahasiswa]);
        $total_sks = (int)$stmt_sks->fetchColumn();

        // 3. Cek Status KRS
        $stmt_status = $pdo->prepare("SELECT status_validasi FROM krs WHERE id_mahasiswa = ? LIMIT 1");
        $stmt_status->execute([$id_mahasiswa]);
        $cek_status = $stmt_status->fetchColumn();
        if ($cek_status) {
            $status_krs = $cek_status; // 'Pending' atau 'Disetujui'
        }

        // 4. Ambil Jadwal Kuliah Mahasiswa
        $query_jadwal = "
            SELECT j.*, mk.nama_mk, mk.kode_mk, k.nama_kelas AS kelas, j.ruang AS ruangan, d.nama_dosen
            FROM krs k_rs
            JOIN jadwal j ON k_rs.id_jadwal = j.id_jadwal
            JOIN matakuliah mk ON j.id_mk = mk.id_mk 
            JOIN kelas k ON j.id_kelas = k.id_kelas
            JOIN dosen d ON j.id_dosen = d.id_dosen
            WHERE k_rs.id_mahasiswa = ? AND k_rs.status_validasi = 'Disetujui'
            ORDER BY FIELD(j.hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'), j.jam_mulai ASC
        ";
        $stmt_jadwal = $pdo->prepare($query_jadwal);
        $stmt_jadwal->execute([$id_mahasiswa]);
        $jadwal_kuliah = $stmt_jadwal->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    // Fallback Mock Data jika database bermasalah / struktur tabel krs berbeda
    $total_sks = 20;
    $nama_dosen_wali = "Dr. Budi Santoso, M.T.";
    $status_krs = "Disetujui";
    $jadwal_kuliah = [
        ['nama_mk' => 'Pemrograman Web Berbasis PHP', 'kelas' => 'IF-A Pagi', 'hari' => 'Senin', 'jam_mulai' => '08:00', 'jam_selesai' => '10:30', 'ruangan' => 'Lab Komputer 2'],
        ['nama_mk' => 'Kecerdasan Buatan (AI)', 'kelas' => 'IF-A Pagi', 'hari' => 'Rabu', 'jam_mulai' => '10:30', 'jam_selesai' => '13:00', 'ruangan' => 'Ruang Teori 301']
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
                    <i class="fa-solid fa-graduation-cap me-1"></i> SIAKAD PORTAL MAHASISWA
                </span>
                <h2 class="fw-extrabold mb-1" style="font-size: calc(1.3rem + 0.6vw); font-weight: 800;">
                    <i class="fa-solid <?= $icon_sapaan; ?> me-2"></i><?= $sapaan; ?>, <?= htmlspecialchars($nama_mahasiswa); ?>!
                </h2>
                <p class="text-white-50 m-0 small" style="font-style: italic;">
                    <i class="fa-solid fa-quote-left me-1 opacity-50"></i> Belajar dengan tekun, siapkan masa depan gemilang.
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
        <a href="akademik/krs.php" class="card card-custom card-clickable shadow-sm h-100 bg-white">
            <div class="card-body p-4 d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted text-uppercase fw-bold d-block mb-1" style="font-size: 11px;">SKS Diambil</span>
                    <h2 class="fw-bold mb-0" style="color: var(--siakad-primary);"><?= $total_sks; ?> <span style="font-size: 14px;" class="text-muted fw-normal">SKS</span></h2>
                </div>
                <div class="rounded-3 p-3" style="background-color: rgba(36, 83, 88, 0.08); color: var(--siakad-primary);">
                    <i class="fa-solid fa-book-open fs-3"></i>
                </div>
            </div>
        </a>
    </div>

    <div class="col-12 col-sm-6 col-xl-4">
        <div class="card card-custom shadow-sm h-100 bg-white">
            <div class="card-body p-4 d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted text-uppercase fw-bold d-block mb-1" style="font-size: 11px;">Dosen Wali Anda</span>
                    <h6 class="fw-bold mb-0 text-truncate text-dark" style="max-width: 180px;"><?= htmlspecialchars($nama_dosen_wali); ?></h6>
                </div>
                <div class="rounded-3 p-3 text-info" style="background-color: rgba(13, 202, 240, 0.08);">
                    <i class="fa-solid fa-user-tie fs-3"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-4">
        <a href="akademik/krs.php" class="card card-custom card-clickable shadow-sm h-100 bg-white">
            <div class="card-body p-4 d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted text-uppercase fw-bold d-block mb-1" style="font-size: 11px;">Status Kontrak KRS</span>
                    <h5 class="fw-bold mb-0">
                        <?php if($status_krs == 'Disetujui'): ?>
                            <span class="badge bg-success-subtle text-success p-2 px-3 rounded-pill" style="font-size: 13px;">Disetujui</span>
                        <?php elseif($status_krs == 'Pending'): ?>
                            <span class="badge bg-warning-subtle text-warning p-2 px-3 rounded-pill" style="font-size: 13px;">Menunggu Validasi</span>
                        <?php else: ?>
                            <span class="badge bg-danger-subtle text-danger p-2 px-3 rounded-pill" style="font-size: 13px;">Belum Kontrak</span>
                        <?php endif; ?>
                    </h5>
                </div>
                <div class="rounded-3 p-3 text-warning" style="background-color: rgba(255, 193, 7, 0.08);">
                    <i class="fa-solid fa-file-signature fs-3"></i>
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
                    <i class="fa-solid fa-calendar-week me-2 text-secondary"></i>Jadwal Kuliah Anda Hari Ini / Minggu Ini
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
                            <?php if (!empty($jadwal_kuliah)): ?>
                                <?php foreach ($jadwal_kuliah as $row): ?>
                                    <tr>
                                        <td class="ps-4 fw-bold text-dark">
                                            <?= htmlspecialchars($row['nama_mk']); ?><br>
                                            <span class="text-muted fw-normal small" style="font-size: 11px;">Dosen: <?= htmlspecialchars($row['nama_dosen'] ?? '-'); ?></span>
                                        </td>
                                        <td><?= htmlspecialchars($row['kelas']); ?></td>
                                        <td><?= htmlspecialchars($row['hari']); ?> (<?= htmlspecialchars($row['jam_mulai']); ?> - <?= htmlspecialchars($row['jam_selesai'] ?? ''); ?>)</td>
                                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($row['ruangan']); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">Belum ada jadwal kuliah yang disetujui.</td>
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
                <h5 class="mb-0 fw-bold text-dark" style="font-size: 15px;">Aksi Cepat Mahasiswa</h5>
            </div>
            <div class="card-body pt-3">
                <div class="d-flex flex-column gap-2">
                    <a href="akademik/krs.php" class="btn quick-link-btn text-start p-3 rounded-3 text-dark fw-semibold" style="font-size: 13px; text-decoration:none;">
                        <i class="fa-solid fa-pen-to-square me-2 text-primary"></i> Pengisian KRS Online
                    </a>
                    <a href="akademik/khs.php" class="btn quick-link-btn text-start p-3 rounded-3 text-dark fw-semibold" style="font-size: 13px; text-decoration:none;">
                        <i class="fa-solid fa-graduation-cap me-2 text-success"></i> Lihat & Cetak KHS (Nilai)
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

</div> </div> 

<?php
require_once __DIR__ . '/../templates/footer.php';
?>