<?php 
// admin/dashboard.php
require_once '../templates/header.php'; 
require_once '../templates/sidebar.php';
require_once '../config/database.php';

// Ambil Nama Pengguna Secara Dinamis Dari Session (Mengatasi Masalah Akun Kembali ke Nama Lama)
$nama_admin = $_SESSION['username'] ?? $_SESSION['nama_user'] ?? 'Administrator';

// Fitur Sapaan Otomatis Berdasarkan Jam Laptop/Server Saat Ini
date_default_timezone_set('Asia/Jakarta');
$jam = date('H');
if ($jam >= 5 && $jam < 11) { $sapaan = "Selamat Pagi"; $icon_sapaan = "fa-cloud-sun text-warning"; }
elseif ($jam >= 11 && $jam < 15) { $sapaan = "Selamat Siang"; $icon_sapaan = "fa-sun text-warning"; }
elseif ($jam >= 15 && $jam < 18) { $sapaan = "Selamat Sore"; $icon_sapaan = "fa-cloud-meatball text-info"; }
else { $sapaan = "Selamat Malam"; $icon_sapaan = "fa-moon text-light"; }

// Hitung Statistik Data Agregat
$count_mhs  = $pdo->query("SELECT COUNT(*) FROM mahasiswa")->fetchColumn() ?: 0;
$count_dsn  = $pdo->query("SELECT COUNT(*) FROM dosen")->fetchColumn() ?: 0;
$count_mk   = $pdo->query("SELECT COUNT(*) FROM mata_kuliah")->fetchColumn() ?: 0;
$count_logs = $pdo->query("SELECT COUNT(*) FROM login_logs WHERE logout_time IS NULL")->fetchColumn() ?: 0;

// --- 1. Hitung Jumlah Mahasiswa Berdasarkan Gender ---
$count_mhs_laki = $pdo->query("SELECT COUNT(*) FROM mahasiswa WHERE jenis_kelamin = 'L'")->fetchColumn() ?: 0;
$count_mhs_perempuan = $pdo->query("SELECT COUNT(*) FROM mahasiswa WHERE jenis_kelamin = 'P'")->fetchColumn() ?: 0;

// --- 2. Hitung Jumlah Dosen Berdasarkan Gender (BARU) ---
$count_dsn_laki = $pdo->query("SELECT COUNT(*) FROM dosen WHERE jenis_kelamin = 'L'")->fetchColumn() ?: 0;
$count_dsn_perempuan = $pdo->query("SELECT COUNT(*) FROM dosen WHERE jenis_kelamin = 'P'")->fetchColumn() ?: 0;
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
    .dashboard-scope {
        font-family: 'Plus Jakarta Sans', sans-serif;
        color: #1e293b;
        background-color: #f8fafc;
    }
    
    /* GAYA UTAMA BANNER KEPALA */
    .welcome-banner {
        background: linear-gradient(135deg, #043e52 0%, #025864 100%);
        border-radius: 24px;
        position: relative;
        overflow: hidden;
    }
    .welcome-banner::before {
        content: '';
        position: absolute;
        top: -30%;
        right: -10%;
        width: 300px;
        height: 300px;
        background: radial-gradient(circle, rgba(46, 194, 253, 0.15) 0%, rgba(0,0,0,0) 70%);
        border-radius: 50%;
    }

    /* KARTU STATISTIK GRID METRICS */
    .card-stat {
        border: 1px solid #e2e8f0;
        border-radius: 20px;
        background: #ffffff;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
        cursor: pointer; /* Memberikan efek kursor tangan saat diarahkan ke kartu */
    }
    .card-stat:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 25px -5px rgba(0,0,0,0.05), 0 10px 10px -5px rgba(0,0,0,0.01);
    }
    .icon-shape {
        width: 56px;
        height: 56px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        font-weight: bold;
    }
    
    /* VARIANT WARNA AKADEMIK */
    .stat-mhs { border-top: 4px solid #3b82f6; }
    .stat-mhs .icon-shape { background: rgba(59, 130, 246, 0.08); color: #3b82f6; }
    
    .stat-dosen { border-top: 4px solid #10b981; }
    .stat-dosen .icon-shape { background: rgba(16, 185, 129, 0.08); color: #10b981; }
    
    .stat-matkul { border-top: 4px solid #f59e0b; }
    .stat-matkul .icon-shape { background: rgba(245, 158, 11, 0.08); color: #f59e0b; }
    
    .stat-online { border-top: 4px solid #ef4444; }
    .stat-online .icon-shape { background: rgba(239, 68, 68, 0.08); color: #ef4444; }

    /* CARD DOCK UNTUK GRAFIK / CHART */
    .card-chart {
        border: 1px solid #e2e8f0;
        border-radius: 24px;
        background: #ffffff;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
    }
    .text-stat-title {
        font-size: 13px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.75px;
        color: #64748b;
    }
    .text-stat-value {
        font-size: 32px;
        font-weight: 800;
        color: #0f172a;
        line-height: 1;
    }
</style>

<div class="container-fluid py-4 dashboard-scope">
    
    <div class="row mb-4">
        <div class="col-12">
            <div class="welcome-banner p-4 p-md-5 text-white shadow-sm d-flex align-items-center justify-content-between">
                <div>
                    <span class="badge bg-white bg-opacity-20 px-3 py-2 rounded-pill small fw-bold mb-3 text-uppercase tracking-wider" style="font-size: 11px; color: #043e52;">
                        <i class="fa-solid fa-circle-check text-success me-1"></i> Sistem Berjalan Normal
                    </span>
                    <h2 class="fw-extrabold mb-1 tracking-tight">
                        <i class="fa-solid <?= $icon_sapaan; ?> me-2"></i><?= $sapaan; ?>, <?= htmlspecialchars($nama_admin); ?>!
                    </h2>
                    <p class="text-slate-300 m-0 opacity-75 small">Anda masuk ke pusat kendali data akademik prodi Ilmu Komputer <strong>SOBAT IK</strong>.</p>
                </div>
                <div class="d-none d-md-block text-end">
                    <span class="badge bg-white bg-opacity-10 text-white p-2 px-4 rounded-pill shadow-sm fs-6 fw-medium border border-white border-opacity-20">
                        <i class="fa-regular fa-calendar-days me-2 text-info"></i><?= date('d F Y'); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-3 col-md-6">
            <a href="/siakad/admin/mahasiswa/index.php" class="text-decoration-none">
                <div class="card card-stat stat-mhs p-4 h-100 shadow-sm">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-stat-title mb-2">Total Mahasiswa</div>
                            <div class="text-stat-value mb-1"><?= number_format($count_mhs); ?></div>
                            <span class="text-muted" style="font-size: 11px;"><i class="fa-solid fa-graduation-cap me-1"></i>Mahasiswa Terdaftar</span>
                        </div>
                        <div class="icon-shape"><i class="fa-solid fa-user-graduate"></i></div>
                    </div>
                </div>
            </a>
        </div>
        
        <div class="col-xl-3 col-md-6">
            <a href="/siakad/admin/dosen/index.php" class="text-decoration-none">
                <div class="card card-stat stat-dosen p-4 h-100 shadow-sm">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-stat-title mb-2">Dosen Aktif</div>
                            <div class="text-stat-value mb-1"><?= number_format($count_dsn); ?></div>
                            <span class="text-muted" style="font-size: 11px;"><i class="fa-solid fa-id-card-clip me-1"></i>Tenaga Pengajar</span>
                        </div>
                        <div class="icon-shape"><i class="fa-solid fa-chalkboard-user"></i></div>
                    </div>
                </div>
            </a>
        </div>
        
        <div class="col-xl-3 col-md-6">
            <a href="/siakad/admin/akademik/index.php#panel-mk" class="text-decoration-none">
                <div class="card card-stat stat-matkul p-4 h-100 shadow-sm">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-stat-title mb-2">Mata Kuliah</div>
                            <div class="text-stat-value mb-1"><?= number_format($count_mk); ?></div>
                            <span class="text-muted" style="font-size: 11px;"><i class="fa-solid fa-layer-group me-1"></i>Kurikulum Aktif</span>
                        </div>
                        <div class="icon-shape"><i class="fa-solid fa-book-bookmark"></i></div>
                    </div>
                </div>
            </a>
        </div>
        
        <div class="col-xl-3 col-md-6">
            <a href="/siakad/admin/sistem/log_aktivitas.php" class="text-decoration-none">
                <div class="card card-stat stat-online p-4 h-100 shadow-sm">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-stat-title mb-2">Sesi Online</div>
                            <div class="text-stat-value mb-1"><?= number_format($count_logs); ?></div>
                            <span class="text-success" style="font-size: 11px;"><i class="fa-solid fa-circle text-success me-1 animate-pulse"></i>Real-time Monitoring</span>
                        </div>
                        <div class="icon-shape"><i class="fa-solid fa-bolt"></i></div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-6 col-lg-12 mb-4">
            <div class="card card-chart p-4 h-100 shadow-sm">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <h5 class="fw-bold text-slate-800 m-0"><i class="fa-solid fa-chart-area text-primary me-2"></i>Log Lalu Lintas Sistem</h5>
                        <p class="text-muted small m-0">Frekuensi manajemen data mahasiswa & dosen</p>
                    </div>
                    <span class="badge bg-light text-dark border px-3 py-2 rounded-pill small fw-medium">Tahun 2026</span>
                </div>
                <div style="position: relative; height: 260px;">
                    <canvas id="activityChart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card card-chart p-4 h-100 shadow-sm">
                <div class="mb-3">
                    <h5 class="fw-bold text-slate-800 m-0"><i class="fa-solid fa-chart-pie text-primary me-2"></i>Gender Mahasiswa</h5>
                    <p class="text-muted small m-0">Persentase jender mahasiswa</p>
                </div>
                <div style="position: relative; height: 200px;" class="d-flex align-items-center justify-content-center my-auto">
                    <canvas id="mhsGenderChart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card card-chart p-4 h-100 shadow-sm">
                <div class="mb-3">
                    <h5 class="fw-bold text-slate-800 m-0"><i class="fa-solid fa-chart-pie text-emerald-500 me-2"></i>Gender Dosen</h5>
                    <p class="text-muted small m-0">Persentase jender dosen aktif</p>
                </div>
                <div style="position: relative; height: 200px;" class="d-flex align-items-center justify-content-center my-auto">
                    <canvas id="dsnGenderChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        // Line Chart - Konfigurasi Grafik Tren Aktivitas
        const ctxActivity = document.getElementById('activityChart').getContext('2d');
        const gradientLine = ctxActivity.createLinearGradient(0, 0, 0, 240);
        gradientLine.addColorStop(0, 'rgba(59, 130, 246, 0.25)');
        gradientLine.addColorStop(1, 'rgba(59, 130, 246, 0.00)');

        new Chart(ctxActivity, {
            type: 'line',
            data: {
                labels: ['Januari', 'Februari', 'Maret', 'April', 'Mei'],
                datasets: [{
                    label: 'Aktivitas Operasi Data',
                    data: [35, 48, 65, 30, 85],
                    borderColor: '#3b82f6',
                    borderWidth: 3.5,
                    tension: 0.38,
                    fill: true,
                    backgroundColor: gradientLine,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#3b82f6',
                    pointBorderWidth: 2.5,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { grid: { borderDash: [5, 5] }, ticks: { color: '#64748b', font: { family: 'Plus Jakarta Sans' } } },
                    x: { grid: { display: false }, ticks: { color: '#64748b', font: { family: 'Plus Jakarta Sans', weight: 500 } } }
                }
            }
        });

        // Doughnut Chart 1 - Data Komparasi Gender Mahasiswa
        new Chart(document.getElementById('mhsGenderChart'), {
            type: 'doughnut',
            data: {
                labels: ['Laki-laki', 'Perempuan'],
                datasets: [{
                    data: [<?= $count_mhs_laki; ?>, <?= $count_mhs_perempuan; ?>],
                    backgroundColor: ['#0f172a', '#3b82f6'],
                    borderWidth: 3,
                    borderColor: '#ffffff',
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '75%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 10,
                            padding: 15,
                            font: { family: 'Plus Jakarta Sans', weight: 600, size: 11 },
                            color: '#334155'
                        }
                    }
                }
            }
        });

        // Doughnut Chart 2 - Data Komparasi Gender Dosen (BARU)
        new Chart(document.getElementById('dsnGenderChart'), {
            type: 'doughnut',
            data: {
                labels: ['Laki-laki', 'Perempuan'],
                datasets: [{
                    data: [<?= $count_dsn_laki; ?>, <?= $count_dsn_perempuan; ?>],
                    backgroundColor: ['#111827', '#10b981'], 
                    borderWidth: 3,
                    borderColor: '#ffffff',
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '75%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 10,
                            padding: 15,
                            font: { family: 'Plus Jakarta Sans', weight: 600, size: 11 },
                            color: '#334155'
                        }
                    }
                }
            }
        });
    });
</script>

<?php require_once '../templates/footer.php'; ?>