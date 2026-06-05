<div id="sidebar" class="d-flex flex-column justify-content-between">
    <div>
        <!-- Brand / Logo -->
        <div class="text-center py-4 border-bottom" style="border-color: rgba(255,255,255,0.1) !important;">
            <h4 class="text-white fw-bold mb-0">
                <i class="fa-solid fa-graduation-cap me-2 text-info"></i>SOBAT IK
            </h4>
        </div>

        <!-- Mini Profile Widget -->
        <a href="/siakad/admin/sistem/profile.php" class="user-profile-sidebar text-center py-3 mb-3 d-block text-decoration-none" style="cursor: pointer; transition: background 0.2s;">
            <?php
            // 1. Deteksi path folder uploads agar tidak pecah/broken image di halaman mana pun
            $base_url_upload = file_exists('uploads/profile/') ? 'uploads/profile/' : (file_exists('../uploads/profile/') ? '../uploads/profile/' : '../../uploads/profile/');

            // 2. Ambil nama file foto dari session (jika kosong, gunakan default.png)
            $foto_aktif = $_SESSION['foto_user'] ?? 'default.png';
            $path_foto_profil = $base_url_upload . $foto_aktif;

            // 3. Ambil nama lengkap/username dari session agar langsung sinkron
            $nama_user_aktif = $_SESSION['nama_user'] ?? $_SESSION['username'] ?? 'Administrator';
            ?>

            <img src="<?= $path_foto_profil; ?>"
                class="rounded-circle mb-2"
                style="width: 65px; height: 65px; object-fit: cover; border: 3px solid rgba(255,255,255,0.2);"
                onerror="this.src='https://cdn-icons-png.flaticon.com/512/3135/3135715.png'">

            <div class="user-info">
                <h6 class="text-white mb-0 fw-bold small"><?= htmlspecialchars($nama_user_aktif); ?></h6>
                <span class="badge bg-success" style="font-size: 10px; padding: 2px 8px;">Admin</span>
            </div>
        </a>

        <hr class="sidebar-divider my-2 mx-3" style="border-color: rgba(255,255,255,0.1);">

        <!-- Navigation Menu -->
        <ul class="nav flex-column mt-2">
            <!-- Dashboard -->
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="/siakad/<?= $_SESSION['role']; ?>/dashboard.php">
                    <i class="fa-solid fa-gauge-high me-3" style="width: 20px;"></i>Dashboard
                </a>
            </li>

            <?php if ($_SESSION['role'] == 'admin'): ?>
                <!-- Data Mahasiswa -->
                <li class="nav-item">
                    <a class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'mahasiswa') !== false && strpos($_SERVER['PHP_SELF'], 'akademik') === false ? 'active' : ''; ?>" href="/siakad/admin/mahasiswa/index.php">
                        <i class="fa-solid fa-user-graduate me-3" style="width: 20px;"></i>Data Mahasiswa
                    </a>
                </li>

                <!-- Data Dosen -->
                <li class="nav-item">
                    <a class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'dosen') !== false ? 'active' : ''; ?>" href="/siakad/admin/dosen/index.php">
                        <i class="fa-solid fa-chalkboard-user me-3" style="width: 20px;"></i>Data Dosen
                    </a>
                </li>

                <!-- Akademik Dropdown -->
                <?php
                $is_akademik_active = strpos($_SERVER['PHP_SELF'], 'akademik') !== false ||
                    strpos($_SERVER['PHP_SELF'], 'jadwal') !== false ||
                    strpos($_SERVER['PHP_SELF'], 'krs') !== false ||
                    strpos($_SERVER['PHP_SELF'], 'absensi') !== false;
                ?>
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center justify-content-between <?= $is_akademik_active ? '' : 'collapsed'; ?>"
                        data-bs-toggle="collapse" href="#menuAkademik" role="button" aria-expanded="<?= $is_akademik_active ? 'true' : 'false'; ?>">
                        <div>
                            <i class="fa-solid fa-layer-group me-3" style="width: 20px;"></i>Akademik
                        </div>
                        <i class="fa-solid fa-chevron-down small chevron-arrow" style="font-size: 10px;"></i>
                    </a>

                    <div class="collapse <?= $is_akademik_active ? 'show' : ''; ?>" id="menuAkademik" style="background: rgba(0,0,0,0.12); border-radius: 8px; margin: 2px 0;">
                        <ul class="nav flex-column pb-1">
                            <li class="nav-item">
                                <a class="nav-link py-2 <?= strpos($_SERVER['PHP_SELF'], 'jadwal') !== false || (strpos($_SERVER['PHP_SELF'], 'akademik') !== false && strpos($_SERVER['PHP_SELF'], 'index.php') !== false) ? 'active' : ''; ?>" href="/siakad/admin/akademik/index.php">
                                    <i class="fa-solid fa-calendar-days me-2"></i>Jadwal Kuliah
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link py-2 <?= strpos($_SERVER['PHP_SELF'], 'krs') !== false ? 'active' : ''; ?>" href="/siakad/admin/akademik/krs_mahasiswa.php">
                                    <i class="fa-solid fa-clipboard-list me-2"></i>KRS Mahasiswa
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link py-2 <?= strpos($_SERVER['PHP_SELF'], 'absensi') !== false ? 'active' : ''; ?>" href="/siakad/admin/akademik/absensi.php">
                                    <i class="fa-solid fa-user-check me-2"></i>Absensi Kuliah
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                <!-- Monitoring Log -->
                <li class="nav-item">
                    <a class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'logs') !== false ? 'active' : ''; ?>" href="/siakad/admin/sistem/log_aktivitas.php">
                        <i class="fa-solid fa-clock-rotate-left me-3" style="width: 20px;"></i>Monitoring Log
                    </a>
                </li>

                <!-- Menu Baru: Pusat Informasi Dropdown -->
                <?php
                $is_info_active = strpos($_SERVER['PHP_SELF'], 'profile.php') !== false;
                ?>
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center justify-content-between <?= $is_info_active ? '' : 'collapsed'; ?>"
                        data-bs-toggle="collapse" href="#menuInformasi" role="button" aria-expanded="<?= $is_info_active ? 'true' : 'false'; ?>">
                        <div>
                            <i class="fa-solid fa-circle-info me-3" style="width: 20px;"></i>Pusat Informasi
                        </div>
                        <i class="fa-solid fa-chevron-down small chevron-arrow" style="font-size: 10px;"></i>
                    </a>

                    <div class="collapse <?= $is_info_active ? 'show' : ''; ?>" id="menuInformasi" style="background: rgba(0,0,0,0.12); border-radius: 8px; margin: 2px 0;">
                        <ul class="nav flex-column pb-1">
                            <li class="nav-item">
                                <a class="nav-link py-2 <?= strpos($_SERVER['PHP_SELF'], 'profile.php') !== false ? 'active' : ''; ?>" href="/siakad/admin/sistem/profile.php">
                                    <i class="fa-solid fa-user-gear me-2"></i>Edit Profil
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

            <?php endif; ?>
        </ul>
    </div>

    <!-- Tombol Keluar -->
    <div class="p-3">
        <a class="nav-link text-white fw-bold d-flex align-items-center justify-content-center py-2 rounded-3" href="/siakad/auth/logout.php" style="background-color: rgba(239, 68, 68, 0.15) !important; color: #ffcdd2 !important; font-size: 14px; text-decoration: none;">
            <i class="fa-solid fa-power-off me-2"></i>Keluar
        </a>
    </div>
</div>

<!-- Batas Konten Utama -->
<div id="main-content">
    <nav class="navbar navbar-expand navbar-custom px-4 py-3 shadow-sm">
        <div class="container-fluid p-0 d-flex justify-content-between align-items-center">

            <span class="navbar-text fw-semibold" style="color: var(--text-dark);">
                <i class="fa-regular fa-calendar-check me-2" style="color: var(--ocean-deep);"></i><?= get_greeting() . ", " . $_SESSION['username']; ?>
            </span>

            <div class="ms-auto">
                <span class="badge border p-2 fw-semibold" style="background-color: #f8f9fa; color: var(--ocean-deep); border-color: var(--ocean-light) !important;">
                    <i class="fa-solid fa-graduation-cap me-1"></i> TA: 2026/Genap
                </span>
            </div>

        </div>
    </nav>

    <div class="p-4 flex-grow-1">