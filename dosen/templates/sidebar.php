<?php
// dosen/templates/sidebar.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current_page = basename($_SERVER['PHP_SELF']);

// Deteksi lokasi folder saat ini agar link navigasi tidak pecah/salah jalur
$request_uri = $_SERVER['REQUEST_URI'];
$is_inside_folder = (strpos($request_uri, '/akademik_mengajar/') !== false || 
                     strpos($request_uri, '/perwalian/') !== false);

// Path acuan mundur ke folder utama jika sedang berada di dalam sub-folder
$base_path = $is_inside_folder ? '../' : '';

// Hubungkan koneksi database PDO jika belum terdefinisi
if (!isset($pdo)) {
    $config_path = $is_inside_folder ? __DIR__ . '/../../config/database.php' : __DIR__ . '/../config/database.php';
    if (file_exists($config_path)) {
        require_once $config_path;
    }
}

// AMBIL DATA DOSEN DENGAN DRIVER PDO AGAR MENU TIDAK BERHENTI MERENDER (FATAL ERROR)
if (isset($_SESSION['id_user']) && isset($pdo)) {
    try {
        $stmt_side = $pdo->prepare("SELECT nama_dosen FROM dosen WHERE id_user = ?");
        $stmt_side->execute([$_SESSION['id_user']]);
        $user_dosen = $stmt_side->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $user_dosen = null;
    }
}

// Ambil info nama dosen dan data session aktif
$nama_dosen_aktif = $user_dosen['nama_dosen'] ?? $_SESSION['nama_user'] ?? 'Dosen Pengajar';
?>
<style>
    #sidebar-wrapper {
        min-height: 100vh;
        width: 260px;
        background-color: #245358; 
        border-right: 1px solid rgba(255, 255, 255, 0.05);
        transition: all 0.3s;
        flex-shrink: 0;
    }
    
    .user-profile-sidebar {
        display: block;
        text-decoration: none !important;
        transition: background 0.2s;
    }
    .user-profile-sidebar:hover {
        background-color: rgba(255, 255, 255, 0.05);
    }

    .list-group-item-action {
        border: none !important;
        padding: 0.6rem 1.25rem;
        font-size: 14px;
        font-weight: 500;
        color: rgba(255, 255, 255, 0.75) !important;
        background: transparent;
        border-radius: 10px;
        margin: 2px 16px;
        width: auto;
        display: flex;
        align-items: center;
        text-decoration: none !important;
    }
    
    .list-group-item-action:hover,
    .list-group-item-action.active-menu {
        background-color: rgba(255, 255, 255, 0.15) !important;
        color: #ffffff !important;
        font-weight: 600;
    }
    
    .list-group-item-action i {
        color: rgba(255, 255, 255, 0.6);
    }
    .list-group-item-action:hover i,
    .list-group-item-action.active-menu i {
        color: #ffffff !important;
    }

    .menu-header-text {
        color: rgba(255, 255, 255, 0.4);
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.8px;
        padding: 0.75rem 1.25rem 0.25rem 25px;
        text-transform: uppercase;
        display: block;
    }

    .sidebar-footer {
        position: absolute;
        bottom: 0;
        width: 260px;
        padding: 1rem;
        background: #245358;
        border-top: 1px solid rgba(255, 255, 255, 0.08);
    }
</style>

<div class="d-flex" id="wrapper" style="min-height: 100vh; width: 100%;">
    <div id="sidebar-wrapper" class="position-relative d-flex flex-column justify-content-between">
        <div>
            <div class="text-center py-4 border-bottom" style="border-color: rgba(255,255,255,0.1) !important;">
                <h4 class="text-white fw-bold mb-0">
                    <i class="fa-solid fa-graduation-cap me-2 text-info"></i>SOBAT IK
                </h4>
            </div>

            <div class="text-center py-3 mb-3">
                <img src="<?= $base_path; ?>../assets/uploads/foto_dosen/<?= htmlspecialchars($foto_dosen ?? ''); ?>"
                class="rounded-circle mb-2"
                style="width:65px;height:65px;object-fit:cover;border:3px solid rgba(255,255,255,.2);"
                onerror="this.src='<?= $base_path; ?>../assets/uploads/foto_dosen/default.png';">

                <h6 class="text-white fw-bold mb-1">
                <?= htmlspecialchars($nama_dosen_aktif); ?>
                </h6>
            </div>
            <hr class="sidebar-divider my-1 mx-3" style="border-color: rgba(255,255,255,0.1);">

            <div class="list-group list-group-flush mt-1" style="max-height: calc(100vh - 260px); overflow-y: auto; padding-bottom: 5rem;">
                
                <a href="<?= $is_inside_folder ? '../dashboard.php' : 'dashboard.php'; ?>" class="list-group-item list-group-item-action <?= ($current_page == 'dashboard.php') ? 'active-menu' : ''; ?>">
                    <i class="fa-solid fa-chart-pie me-3" style="width: 20px;"></i>Dashboard Utama
                </a>

                <?php 
                // Cek apakah URL mengandung folder akademik_mengajar untuk membuka dropdown otomatis
                $is_akademik_active = (strpos($request_uri, '/akademik_mengajar/') !== false); 
                ?>
                <a class="list-group-item list-group-item-action" data-bs-toggle="collapse" href="#menuAkademik" aria-expanded="<?= $is_akademik_active ? 'true' : 'false'; ?>">
                    <i class="fa-solid fa-book me-3"></i>Akademik Mengajar
                    <i class="fa-solid fa-chevron-down ms-auto"></i>
                </a>

                <div class="collapse <?= $is_akademik_active ? 'show' : ''; ?>" id="menuAkademik">
                    <a class="list-group-item list-group-item-action ps-5 <?= ($current_page == 'jadwal.php') ? 'active-menu' : ''; ?>" href="<?= $base_path; ?>akademik_mengajar/jadwal.php">
                        Jadwal & Absensi
                    </a>
                    <a class="list-group-item list-group-item-action ps-5 <?= ($current_page == 'mata_kuliah.php') ? 'active-menu' : ''; ?>" href="<?= $base_path; ?>akademik_mengajar/mata_kuliah.php">
                        Materi & Silabus
                    </a>
                    <a class="list-group-item list-group-item-action ps-5 <?= ($current_page == 'nilai.php') ? 'active-menu' : ''; ?>" href="<?= $base_path; ?>akademik_mengajar/nilai.php">
                        Input Nilai
                    </a>
                </div>

                <?php 
                // Cek apakah URL mengandung folder perwalian untuk membuka dropdown otomatis
                $is_perwalian_active = (strpos($request_uri, '/perwalian/') !== false); 
                ?>
                <a class="list-group-item list-group-item-action" data-bs-toggle="collapse" href="#menuPA" aria-expanded="<?= $is_perwalian_active ? 'true' : 'false'; ?>">
                    <i class="fa-solid fa-users me-3"></i>Perwalian
                    <i class="fa-solid fa-chevron-down ms-auto"></i>
                </a>

                <div class="collapse <?= $is_perwalian_active ? 'show' : ''; ?>" id="menuPA">
                    <a href="<?= $base_path; ?>perwalian/bimbingan.php" class="list-group-item list-group-item-action ps-5 <?= ($current_page == 'bimbingan.php') ? 'active-menu' : ''; ?>">
                        Data Mahasiswa PA
                    </a>
                    <a href="<?= $base_path; ?>perwalian/krs_validasi.php" class="list-group-item list-group-item-action ps-5 <?= ($current_page == 'krs_validasi.php') ? 'active-menu' : ''; ?>">
                        Persetujuan KRS
                    </a>
                </div>

                <a class="list-group-item list-group-item-action <?= ($current_page == 'profile.php') ? 'active-menu' : ''; ?>" href="<?= $base_path; ?>kinerja_dosen/profile.php">
                    <i class="fa-solid fa-sliders me-3" style="width: 20px;"></i>Profil & Akun
                </a>

            </div>
        </div>

        <div class="sidebar-footer">
            <a href="<?= $is_inside_folder ? '../../auth/logout.php' : '../auth/logout.php'; ?>" class="btn btn-light text-danger w-100 rounded-pill py-2 small fw-bold border-0 d-flex align-items-center justify-content-center gap-2" style="font-size: 12px; background: #fef2f2;">
                <i class="fa-solid fa-right-from-bracket"></i> Keluar Sistem
            </a>
        </div>
    </div>

    <div id="page-content-wrapper" class="d-flex flex-column flex-grow-1">
        
        <nav class="navbar navbar-expand navbar-light bg-white px-4 py-3 border-bottom shadow-sm" style="min-height: 65px;">
            <div class="container-fluid p-0 d-flex justify-content-between align-items-center">
                
                <span class="navbar-text fw-semibold text-dark">
                    <i class="fa-regular fa-calendar-check me-2" style="color: #245358;"></i>
                    <?php 
                    date_default_timezone_set('Asia/Jakarta');
                    $jam = date('H');
                    $sapaan = "Selamat Malam";
                    if ($jam >= 5 && $jam < 11) $sapaan = "Selamat Pagi";
                    elseif ($jam >= 11 && $jam < 15) $sapaan = "Selamat Siang";
                    elseif ($jam >= 15 && $jam < 18) $sapaan = "Selamat Sore";
                    
                    echo $sapaan . ", " . htmlspecialchars($nama_dosen_aktif); 
                    ?>
                </span>
                
                <div class="ms-auto">
                    <span class="badge border p-2 fw-semibold" style="background-color: #f8f9fa; color: #245358; border-color: rgba(36, 83, 88, 0.2) !important;">
                        <i class="fa-solid fa-graduation-cap me-1"></i> TA: 2026/Ganjil
                    </span>
                </div>
            </div>
        </nav>
        
        <div class="container-fluid p-4 flex-grow-1" style="background-color: #f8fafc;">