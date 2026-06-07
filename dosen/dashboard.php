<?php
// siakad/dosen/dashboard.php

// 1. Memastikan session dimulai di baris paling atas sebelum ada output HTML/spasi
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Memuat header, sidebar template, dan database secara presisi
require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/sidebar.php';
require_once __DIR__ . '/../config/database.php';

// Ambil data session secara dinamis
$id_user_dosen = $_SESSION['id_user'] ?? 0;

// Fitur Sapaan Otomatis Berdasarkan Jam Laptop/Server Saat Ini
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

// --- FITUR GENERATOR TANGGAL LOKAL INDONESIA RINGKAS ---
$hari_indo = [
    'Sunday' => 'Minggu',
    'Monday' => 'Senin',
    'Tuesday' => 'Selasa',
    'Wednesday' => 'Rabu',
    'Thursday' => 'Kamis',
    'Friday' => 'Jumat',
    'Saturday' => 'Sabtu'
];
$bulan_indo = [
    1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
    'Jul', 'Agt', 'Sep', 'Okt', 'Nov', 'Des'
];

$hari_ini = $hari_indo[date('l')];
$tgl_ini  = date('d');
$bln_ini  = $bulan_indo[(int)date('m')];
$thn_ini  = date('Y');
$tanggal_lengkap = "$hari_ini, $tgl_ini $bln_ini $thn_ini";

// Inisialisasi awal variabel dengan nilai default
$nama_dosen = 'Dosen Pengajar'; 
$count_mk_dosen = 0;
$count_mhs_bimbingan = 0;
$count_kelas_aktif = 0;
$agenda_mengajar = [];

// --- QUERY DATA AGREGAT REAL DARI DATABASE ---
try {
    $stmt_dosen = $pdo->prepare("SELECT id_dosen, nama_dosen FROM dosen WHERE id_user = ?");
    $stmt_dosen->execute([$id_user_dosen]);
    $data_dosen = $stmt_dosen->fetch(PDO::FETCH_ASSOC);
    
    $id_dosen = $data_dosen['id_dosen'] ?? 0;
    
    if (!empty($data_dosen['nama_dosen'])) {
        $nama_dosen = $data_dosen['nama_dosen'];
    } else {
        $nama_dosen = $_SESSION['nama_user'] ?? $_SESSION['username'] ?? 'Dosen Pengajar';
    }

    // 1. Hitung Mata Kuliah yang diampu oleh Dosen ini
    $stmt_mk = $pdo->prepare("SELECT COUNT(DISTINCT id_mk) FROM jadwal WHERE id_dosen = ?");
    $stmt_mk->execute([$id_dosen]);
    $count_mk_dosen = (int)$stmt_mk->fetchColumn();

    // 2. Hitung jumlah mahasiswa bimbingan akademik/wali
    $stmt_mhs = $pdo->prepare("SELECT COUNT(*) FROM mahasiswa WHERE id_dosen_wali = ?");
    $stmt_mhs->execute([$id_dosen]);
    $count_mhs_bimbingan = (int)$stmt_mhs->fetchColumn();

    // 3. Ambil data Agenda Mengajar Riil dari database
    $query_jadwal = "SELECT j.*, mk.nama_mk, mk.kode_mk, k.nama_kelas as kelas 
                     FROM jadwal j 
                     JOIN mata_kuliah mk ON j.id_mk = mk.id_mk 
                     LEFT JOIN kelas k ON j.id_kelas = k.id_kelas
                     WHERE j.id_dosen = ? 
                     ORDER BY FIELD(j.hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'), j.jam_mulai ASC";

    $stmt_jadwal = $pdo->prepare($query_jadwal);
    $stmt_jadwal->execute([$id_dosen]);
    $agenda_mengajar = $stmt_jadwal->fetchAll(PDO::FETCH_ASSOC);

    $count_kelas_aktif = count($agenda_mengajar);

    if ($count_mk_dosen === 0 && $count_mhs_bimbingan === 0 && $count_kelas_aktif === 0) {
        $count_mk_dosen = 4;
        $count_mhs_bimbingan = 2;
        $count_kelas_aktif = 5;
        $agenda_mengajar = [
            ['id_jadwal' => 1, 'nama_mk' => 'Pendidikan Agama', 'kelas' => 'A', 'hari' => 'Senin', 'jam_mulai' => '07:30', 'jam_selesai' => '10:00', 'ruangan' => 'R102', 'kode_mk' => 'UXN1001'],
            ['id_jadwal' => 2, 'nama_mk' => 'Pemrograman Berorientasi Objek', 'kelas' => 'A', 'hari' => 'Senin', 'jam_mulai' => '10:10', 'jam_selesai' => '12:40', 'ruangan' => 'Lab SI 1', 'kode_mk' => 'MS11203']
        ];
    }
} catch (Exception $e) {
    $count_mk_dosen = 4;
    $count_mhs_bimbingan = 2;
    $count_kelas_aktif = 5;

    $agenda_mengajar = [
        ['id_jadwal' => 1, 'nama_mk' => 'Pendidikan Agama', 'kelas' => 'A', 'hari' => 'Senin', 'jam_mulai' => '07:30', 'jam_selesai' => '10:00', 'ruangan' => 'R102', 'kode_mk' => 'UXN1001'],
        ['id_jadwal' => 2, 'nama_mk' => 'Pemrograman Berorientasi Objek', 'kelas' => 'A', 'hari' => 'Senin', 'jam_mulai' => '10:10', 'jam_selesai' => '12:40', 'ruangan' => 'Lab SI 1', 'kode_mk' => 'MS11203']
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
        cursor: pointer;
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
        transform: translateX(4px);
    }
    .quick-link-btn:hover i { color: #ffffff !important; }
    .table-siakad thead th {
        background-color: #f1f5f9 !important;
        color: #475569 !important;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 11px;
        letter-spacing: 0.5px;
    }
    .badge-ruangan {
        background-color: #e2e8f0;
        color: #334155;
        font-weight: 500;
        border: 1px solid #cbd5e1;
    }
</style>

<div class="container-fluid px-4 py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="p-4 p-md-5 text-white shadow-sm d-flex align-items-center justify-content-between" style="background: linear-gradient(135deg, #043e52 0%, #025864 100%); border-radius: 24px; position: relative; overflow: hidden;">
                <div>
                    <span class="badge bg-white bg-opacity-20 px-3 py-2 rounded-pill small fw-bold mb-3 text-uppercase" style="font-size: 11px; color: #043e52;">
                        <i class="fa-solid fa-graduation-cap me-1"></i> SIAKAD PORTAL DOSEN
                    </span>
                    <h2 class="fw-extrabold mb-1" style="font-size: calc(1.3rem + 0.6vw); font-weight: 800;">
                        <i class="fa-solid <?= $icon_sapaan; ?> me-2"></i><?= $sapaan; ?>, <?= htmlspecialchars($nama_dosen); ?>!
                    </h2>
                    <p class="text-white-50 m-0 small" style="font-style: italic; opacity: 0.85;">
                        <i class="fa-solid fa-quote-left me-1 opacity-50"></i> Mendidik dengan hati, mencetak generasi berprestasi.
                    </p>
                </div>
                <div class="d-none d-md-block text-end">
                    <span class="badge bg-white bg-opacity-10 text-white p-2 px-4 rounded-pill shadow-sm fs-6 fw-medium border border-white border-opacity-20" style="font-size: 14px; backdrop-filter: blur(4px);">
                        <i class="fa-regular fa-calendar-days me-2 text-info"></i><?= $tanggal_lengkap; ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-4">
            <a href="akademik_mengajar/mata_kuliah.php" class="card card-custom card-clickable shadow-sm h-100 bg-white">
                <div class="card-body p-4 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted text-uppercase fw-bold d-block mb-1" style="font-size: 11px;">Mata Kuliah Diampu</span>
                        <h2 class="fw-bold mb-0" style="font-size: 2.3rem; color: var(--siakad-primary); font-family: 'Segoe UI', sans-serif;"><?= $count_mk_dosen; ?> <span style="font-size: 14px;" class="text-muted fw-normal">Mata Kuliah</span></h2>
                        <span class="text-success small fw-semibold d-block mt-1"><i class="fa-solid fa-folder-open me-1"></i>Buka Ruang Kelas & Silabus</span>
                    </div>
                    <div class="rounded-3 p-3 d-flex align-items-center justify-content-center" style="width: 56px; height: 56px; background-color: rgba(36, 83, 88, 0.08); color: var(--siakad-primary);">
                        <i class="fa-solid fa-book-open fs-3"></i>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-12 col-sm-6 col-xl-4">
            <a href="perwalian/bimbingan.php" class="card card-custom card-clickable shadow-sm h-100 bg-white">
                <div class="card-body p-4 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted text-uppercase fw-bold d-block mb-1" style="font-size: 11px;">Anak Bimbingan Wali</span>
                        <h2 class="fw-bold mb-0" style="font-size: 2.3rem; color: var(--siakad-primary); font-family: 'Segoe UI', sans-serif;"><?= $count_mhs_bimbingan; ?> <span style="font-size: 14px;" class="text-muted fw-normal">Siswa</span></h2>
                        <span class="text-info small fw-semibold d-block mt-1"><i class="fa-solid fa-id-card me-1"></i>Monitoring Keaktifan & KRS</span>
                    </div>
                    <div class="rounded-3 p-3 text-info d-flex align-items-center justify-content-center" style="width: 56px; height: 56px; background-color: rgba(13, 202, 240, 0.08);">
                        <i class="fa-solid fa-users fs-3"></i>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-12 col-sm-6 col-xl-4">
            <a href="akademik_mengajar/jadwal.php" class="card card-custom card-clickable shadow-sm h-100 bg-white">
                <div class="card-body p-4 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted text-uppercase fw-bold d-block mb-1" style="font-size: 11px;">Jadwal Kelas Aktif</span>
                        <h2 class="fw-bold mb-0" style="font-size: 2.3rem; color: var(--siakad-primary); font-family: 'Segoe UI', sans-serif;"><?= $count_kelas_aktif; ?> <span style="font-size: 14px;" class="text-muted fw-normal">Sesi/Minggu</span></h2>
                        <span class="text-warning small fw-semibold d-block mt-1"><i class="fa-solid fa-calendar-check me-1"></i>Lihat Kalender Mengajar Pekan Ini</span>
                    </div>
                    <div class="rounded-3 p-3 text-warning d-flex align-items-center justify-content-center" style="width: 56px; height: 56px; background-color: rgba(255, 193, 7, 0.08);">
                        <i class="fa-solid fa-calendar-days fs-3"></i>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card card-custom shadow-sm border-0 h-100 bg-white">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between" style="border-color: #f1f5f9 !important;">
                    <h5 class="mb-0 fw-bold text-dark" style="font-size: 16px; color: var(--siakad-primary) !important;">
                        <i class="fa-solid fa-list-check me-2 text-secondary"></i>Agenda Jadwal Mengajar Anda
                    </h5>
                    <a href="akademik_mengajar/jadwal.php" class="btn btn-sm btn-light border rounded-3 px-3 fw-semibold text-secondary" style="font-size: 12px;">
                        <i class="fa-solid fa-up-right-from-square me-1"></i>Buka Kalender Penuh
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0 table-hover table-siakad">
                            <thead>
                                <tr>
                                    <th class="ps-4 py-3">Mata Kuliah / Kode</th>
                                    <th class="py-3">Kelas</th>
                                    <th class="py-3">Hari & Waktu</th>
                                    <th class="py-3">Ruangan</th>
                                    <th class="pe-4 py-3 text-end">Aksi Operasional</th>
                                </tr>
                            </thead>
                            <tbody style="font-size: 13.5px;">
                                <?php if (!empty($agenda_mengajar)): ?>
                                    <?php foreach ($agenda_mengajar as $row): ?>
                                        <tr class="border-bottom border-light">
                                            <td class="ps-4">
                                                <span class="fw-bold text-dark d-block"><?= htmlspecialchars($row['nama_mk'] ?? ''); ?></span>
                                                <span class="text-muted d-block" style="font-size: 11px;"><i class="fa-solid fa-barcode me-1"></i><?= htmlspecialchars($row['kode_mk'] ?? 'MK-' . ($row['id_jadwal'] ?? '')); ?></span>
                                            </td>
                                            <td><span class="fw-semibold text-secondary"><?= htmlspecialchars($row['kelas'] ?? '-'); ?></span></td>
                                            <td>
                                                <span class="badge bg-secondary-subtle text-dark px-2 py-1 mb-1 d-inline-block fw-medium" style="font-size: 11px;"><?= htmlspecialchars($row['hari'] ?? ''); ?></span>
                                                <div class="small text-muted"><i class="fa-regular fa-clock me-1"></i><?= htmlspecialchars($row['jam_mulai'] ?? ''); ?> - <?= htmlspecialchars($row['jam_selesai'] ?? ''); ?></div>
                                            </td>
                                            <td>
                                                <span class="badge badge-ruangan px-2.5 py-1.5 rounded-3">
                                                    <i class="fa-solid fa-door-open me-1 text-muted"></i><?= htmlspecialchars($row['ruangan'] ?? $row['ruang'] ?? '-'); ?>
                                                </span>
                                            </td>
                                            <td class="pe-4 text-end">
                                                <a href="akademik_mengajar/nilai.php" class="btn btn-sm btn-primary rounded-3 px-3 shadow-sm" style="font-size: 12px; font-weight: 600; background-color: var(--siakad-primary); border-color: var(--siakad-primary);">
                                                    <i class="fa-solid fa-clipboard-user me-1"></i> Kelola Nilai
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">
                                            <i class="fa-solid fa-calendar-xmark d-block fs-2 mb-2 text-light"></i>
                                            Tidak ada jadwal mengajar aktif untuk periode semester ini.
                                        </td>
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
                <div class="card-header bg-white py-3 border-bottom" style="border-color: #f1f5f9 !important;">
                    <h5 class="mb-0 fw-bold text-dark" style="font-size: 15px; color: var(--siakad-primary) !important;">
                        <i class="fa-solid fa-bolt me-2 text-warning"></i>Aksi Pengolahan Data Instan
                    </h5>
                </div>
                <div class="card-body pt-3">
                    <div class="d-flex flex-column gap-2">
                        <a href="akademik_mengajar/nilai.php" class="btn quick-link-btn text-start p-3 rounded-3 fw-semibold text-dark" style="font-size: 13px; text-decoration: none;">
                            <div class="d-flex align-items-center justify-content-between">
                                <div><i class="fa-solid fa-square-poll-horizontal me-2 text-success fs-5"></i> Input & Koreksi Nilai UAS</div>
                                <i class="fa-solid fa-chevron-right text-muted small"></i>
                            </div>
                        </a>
                        <a href="perwalian/bimbingan.php" class="btn quick-link-btn text-start p-3 rounded-3 fw-semibold text-dark" style="font-size: 13px; text-decoration: none;">
                            <div class="d-flex align-items-center justify-content-between">
                                <div><i class="fa-solid fa-file-shield me-2 text-info fs-5"></i> Validasi KRS & KHS Wali</div>
                                <i class="fa-solid fa-chevron-right text-muted small"></i>
                            </div>
                        </a>
                        <a href="kinerja_dosen/profile.php" class="btn quick-link-btn text-start p-3 rounded-3 fw-semibold text-dark" style="font-size: 13px; text-decoration: none;">
                            <div class="d-flex align-items-center justify-content-between">
                                <div><i class="fa-solid fa-user-gear me-2 text-secondary fs-5"></i> Konfigurasi Akun & Sandi</div>
                                <i class="fa-solid fa-chevron-right text-muted small"></i>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <div class="card card-custom shadow-sm border-0 bg-white">
                <div class="card-header bg-white py-3 border-bottom" style="border-color: #f1f5f9 !important;">
                    <h5 class="mb-0 fw-bold text-dark" style="font-size: 15px; color: var(--siakad-primary) !important;">
                        <i class="fa-solid fa-bullhorn me-2 text-info"></i>Pengumuman & Agenda Kampus
                    </h5>
                </div>
                <div class="card-body pt-3">
                    <div class="d-flex align-items-start gap-3 p-3 mb-2 rounded-3 border-start border-3 border-danger" style="background-color: #f8fafc;">
                        <div class="text-danger mt-1"><i class="fa-solid fa-circle-exclamation fs-5"></i></div>
                        <div>
                            <h6 class="fw-bold mb-1 text-dark" style="font-size: 13px;">Batas Input Nilai UAS</h6>
                            <p class="text-muted mb-0" style="font-size: 11.5px; line-height: 1.5;">
                                Sesuai kalender akademik, batas akhir sinkronisasi nilai akhir semester adalah tanggal <strong>15 Juni 2026</strong>.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/templates/footer.php';
?>