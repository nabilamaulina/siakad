<?php
// siakad/mahasiswa/templates/sidebar.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';

$current_page = basename($_SERVER['PHP_SELF']);

//Ambil nama mahasiswa dari database
$nama_mhs_aktif = $_SESSION['username'] ?? 'Mahasiswa';

try {
   $stmt = $pdo->prepare("
    SELECT nama_mahasiswa, foto
    FROM mahasiswa
    WHERE id_user = ?
    LIMIT 1
");
    
    $stmt->execute([$_SESSION['id_user']]);
    $mhs = $stmt->fetch(PDO::FETCH_ASSOC);

if ($mhs) {
    $nama_mhs_aktif = $mhs['nama_mahasiswa'];

    if (!empty($mhs['foto'])) {
        $foto_mhs = $mhs['foto'];
    }
}
} catch (Exception $e) {
    $nama_mhs_aktif = $_SESSION['username'] ?? 'Mahasiswa';
    $foto_mhs = 'default.png';
}

$nim_mhs_aktif = $_SESSION['username'] ?? 'NIM';

// Deteksi lokasi folder saat ini berdasarkan struktur gambar Anda
$request_uri = $_SERVER['REQUEST_URI'];
$is_inside_folder = (strpos($request_uri, '/akademik/') !== false || 
                     strpos($request_uri, '/profil/') !== false);

// Path acuan mundur ke folder 'mahasiswa' jika sedang berada di dalam sub-folder
$base_path = $is_inside_folder ? '../' : '';
?>
<style>
#sidebar-wrapper {
    height: 100vh;
    width: 260px;
    background-color: #245358;
    border-right: 1px solid rgba(255, 255, 255, 0.05);
    transition: all 0.3s;
    flex-shrink: 0;

    position: sticky;
    top: 0;
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
        padding: 0.75rem 1.25rem;
        font-size: 14px;
        font-weight: 500;
        color: rgba(255, 255, 255, 0.75) !important;
        background: transparent;
        border-radius: 10px;
        margin: 4px 16px;
        width: auto;
        display: flex;
        align-items: center;
        transition: all 0.2s ease;
        text-decoration: none !important;
        cursor: pointer;
    }
    
    .list-group-item-action:hover,
    .list-group-item-action.active-menu {
        background-color: rgba(255, 255, 255, 0.15) !important;
        color: #ffffff !important;
        font-weight: 600;
    }
    
    .list-group-item-action[aria-expanded="true"] {
        background-color: rgba(255, 255, 255, 0.1) !important;
        color: #ffffff !important;
    }
    
    .list-group-item-action i {
        color: rgba(255, 255, 255, 0.75);
    }
    .list-group-item-action:hover i,
    .list-group-item-action.active-menu i,
    .list-group-item-action[aria-expanded="true"] i {
        color: #ffffff !important;
    }

    .submenu-box .list-group-item-action {
        margin: 2px 8px !important;
        padding: 0.6rem 1rem;
        font-size: 13px;
        color: rgba(255, 255, 255, 0.8) !important;
    }
    .submenu-box .list-group-item-action:hover,
    .submenu-box .list-group-item-action.active-submenu {
        background-color: rgba(255, 255, 255, 0.12) !important;
        color: #ffffff !important;
        font-weight: 600;
    }

    .chevron-arrow {
        transition: transform 0.2s ease;
    }
    .list-group-item-action:not(.collapsed) .chevron-arrow {
        transform: rotate(180deg);
    }

    .menu-container-clean {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }
    .menu-container-clean::-webkit-scrollbar {
        display: none;
    }

.sidebar-footer {
    margin-top: auto;
    width: 100%;
    padding: 1rem;
    background: #245358;
    border-top: 1px solid rgba(255,255,255,.08);
}
</style>

<div id="sidebar-wrapper" class="d-flex flex-column">
    <div>
        <div class="text-center py-4 border-bottom" style="border-color: rgba(255,255,255,0.1) !important;">
            <h4 class="text-white fw-bold mb-0">
                <i class="fa-solid fa-graduation-cap me-2 text-info"></i>SOBAT IK
            </h4>
        </div>

<a href="<?= $base_path; ?>profil/edit_profil.php"
   class="user-profile-sidebar text-center py-3 mb-3 d-block text-decoration-none">
           <img src="../../assets/uploads/foto_mahasiswa/<?= htmlspecialchars($foto_mhs); ?>"
                 class="rounded-circle mb-2" 
                 style="width: 65px; height: 65px; object-fit: cover; border: 3px solid rgba(255,255,255,0.2);">
            
<div class="user-info">
    <h6 class="text-white fw-bold mb-1" style="font-size:14px;">
        <?= htmlspecialchars($nama_mhs_aktif); ?>
    </h6>

    <span class="badge bg-info text-dark px-2 py-1"
          style="font-size:11px;">
        <?= htmlspecialchars($nim_mhs_aktif); ?>
    </span>
</div>
        </a>

        <hr class="sidebar-divider my-2 mx-3" style="border-color: rgba(255,255,255,0.1);">

        <div class="list-group list-group-flush mt-2 menu-container-clean" style="max-height: calc(100vh - 280px); overflow-y: scroll; padding-bottom: 4rem;">
            
            <a href="<?= $is_inside_folder ? '../dashboard.php' : 'dashboard.php'; ?>" class="list-group-item list-group-item-action <?= ($current_page == 'dashboard.php') ? 'active-menu' : ''; ?>">
                <i class="fa-solid fa-chart-pie me-3" style="width: 20px;"></i>Dashboard Utama
            </a>

            <?php $is_akademik_active =
(
    $current_page == 'jadwal.php' ||
    $current_page == 'absensi.php' ||
    $current_page == 'krs.php'
); ?>
            <a class="list-group-item list-group-item-action justify-content-between <?= $is_akademik_active ? '' : 'collapsed'; ?>"
               data-bs-toggle="collapse" data-bs-target="#menuAkademikMhs" role="button" aria-expanded="<?= $is_akademik_active ? 'true' : 'false'; ?>">
                <div class="d-flex align-items-center">
                    <i class="fa-solid fa-layer-group me-3" style="width: 20px;"></i>Layanan Akademik
                </div>
                <i class="fa-solid fa-chevron-down small chevron-arrow" style="font-size: 10px;"></i>
            </a>
            <div class="collapse <?= $is_akademik_active ? 'show' : ''; ?>" id="menuAkademikMhs" style="background: rgba(0,0,0,0.15); border-radius: 8px; margin: 2px 16px;">
                <div class="nav flex-column pb-1 submenu-box">
                    <a class="list-group-item list-group-item-action <?= ($current_page == 'krs.php') ? 'active-submenu' : ''; ?>" href="<?= $base_path; ?>akademik/krs.php"><i class="fa-solid fa-file-signature me-2"></i>Pengisian KRS Online</a>
                    <a class="list-group-item list-group-item-action <?= ($current_page == 'jadwal.php') ? 'active-submenu' : ''; ?>" href="<?= $base_path; ?>akademik/jadwal.php"><i class="fa-regular fa-calendar me-2"></i>Jadwal Kuliah & Absen</a>
                    <a class="list-group-item list-group-item-action <?= ($current_page == 'absensi.php') ? 'active-submenu' : ''; ?>" href="<?= $base_path; ?>akademik/absensi.php">
    <i class="fa-solid fa-user-check me-2"></i>Riwayat Absensi
</a>
                </div>
            </div>

           <?php
$is_profil_active =
(
    $current_page == 'profile.php' ||
    $current_page == 'edit_profil.php'
);
?>
            <a class="list-group-item list-group-item-action justify-content-between <?= $is_profil_active ? '' : 'collapsed'; ?>"
               data-bs-toggle="collapse" data-bs-target="#menuProfilMhs" role="button" aria-expanded="<?= $is_profil_active ? 'true' : 'false'; ?>">
                <div class="d-flex align-items-center">
                    <i class="fa-solid fa-user-gear me-3" style="width: 20px;"></i>Pusat Informasi
                </div>
                <i class="fa-solid fa-chevron-down small chevron-arrow" style="font-size: 10px;"></i>
            </a>
            <div class="collapse <?= $is_profil_active ? 'show' : ''; ?>" id="menuProfilMhs" style="background: rgba(0,0,0,0.15); border-radius: 8px; margin: 2px 16px;">
                <div class="nav flex-column pb-1 submenu-box">
<a class="list-group-item list-group-item-action <?= ($current_page == 'edit_profil.php') ? 'active-submenu' : ''; ?>"
   href="<?= $base_path; ?>profil/edit_profil.php">
    <i class="fa-solid fa-user-pen me-2"></i>
    Ubah Profil & Sandi
</a>
                </div>
            </div>

        </div>
    </div>

    <div class="sidebar-footer">
        <a href="<?= $is_inside_folder ? '../../auth/logout.php' : '../auth/logout.php'; ?>" class="btn btn-light text-danger w-100 rounded-pill py-2 small fw-bold border-0 d-flex align-items-center justify-content-center gap-2" style="font-size: 12px; background: #fef2f2;">
            <i class="fa-solid fa-right-from-bracket"></i> Keluar Sistem
        </a>
    </div>
</div>

<div id="page-content-wrapper"
     class="d-flex flex-column flex-grow-1"
     style="min-height:100vh;">
    
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
                
                echo $sapaan . ", " . htmlspecialchars($nama_mhs_aktif); 
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