<?php
// dosen/kinerja_dosen/profile.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Hubungkan koneksi database
require_once __DIR__ . '/../../config/database.php';

// Pastikan user sudah login
if (!isset($_SESSION['id_user'])) {
    header("Location: ../../auth/login.php");
    exit();
}

$id_user = $_SESSION['id_user'];
$sukses = $error = "";

// 2. INISIALISASI VARIABEL AWAL (Mencegah Undefined Variable)
$nama_dosen = $_SESSION['nama_user'] ?? 'Dosen Pengajar';
$nidn_dosen = $_SESSION['username'] ?? '-'; 
$email_dosen = "";
$telp_dosen = "";
$foto_db = "default.png";

// 3. AMBIL DATA DOSEN DARI DATABASE
if (isset($pdo)) {
    try {
        $stmt = $pdo->prepare("SELECT d.*, u.username FROM dosen d JOIN users u ON d.id_user = u.id_user WHERE d.id_user = ?");
        $stmt->execute([$id_user]);
        $data_dosen = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($data_dosen) {
            // Mengambil field sesuai screenshot database asli
            $nama_dosen  = !empty($data_dosen['nama_dosen']) ? $data_dosen['nama_dosen'] : $nama_dosen;
            $nidn_dosen  = !empty($data_dosen['nidn']) ? $data_dosen['nidn'] : $nidn_dosen;
            $email_dosen = $data_dosen['email'] ?? '';
            $telp_dosen  = $data_dosen['no_hp'] ?? ''; // Menggunakan no_hp
            $foto_db     = $data_dosen['foto'] ?? 'default.png';
        }
    } catch (Exception $e) {
        $error = "Gagal memuat data dari database: " . $e->getMessage();
    }
}

// 4. PROSES UPDATE DATA (Ketika form disubmit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // --- AKSI 1: UPDATE BIODATA & FOTO ---
    if (isset($_POST['update_profile'])) {
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $no_hp = htmlspecialchars($_POST['no_hp']);
        $foto_nama = $_POST['foto_lama']; 

        // Cek jika ada file foto baru yang diunggah
        if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['foto_profil']['tmp_name'];
            $file_name = $_FILES['foto_profil']['name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png'];

            if (in_array($file_ext, $allowed_ext)) {
                $foto_nama = $id_user . '_' . time() . '.' . $file_ext;
                $direktori_target = __DIR__ . '/../../assets/uploads/profile/';
                
                if (!is_dir($direktori_target)) {
                    mkdir($direktori_target, 0755, true);
                }

                $tujuan_upload = $direktori_target . $foto_nama;

                if (move_uploaded_file($file_tmp, $tujuan_upload)) {
                    // Hapus foto lama jika bukan default.png atau kosong
                    if ($_POST['foto_lama'] != 'default.png' && !empty($_POST['foto_lama']) && file_exists($direktori_target . $_POST['foto_lama'])) {
                        unlink($direktori_target . $_POST['foto_lama']);
                    }
                }
            } else {
                $error = "Format foto wajib berupa JPG, JPEG, atau PNG!";
            }
        }

        if (empty($error)) {
            try {
                // Query Update presisi menggunakan kolom 'no_hp' sesuai database asli
                $stmt_update = $pdo->prepare("UPDATE dosen SET email = ?, no_hp = ?, foto = ? WHERE id_user = ?");
                $stmt_update->execute([$email, $no_hp, $foto_nama, $id_user]);
                
                $sukses = "Profil Anda berhasil diperbarui!";
                
                // Set ulang variabel agar view langsung ter-refresh
                $email_dosen = $email;
                $telp_dosen = $no_hp;
                $foto_db = $foto_nama;
            } catch (Exception $e) {
                $error = "Gagal memperbarui profil: " . $e->getMessage();
            }
        }
    }

    // --- AKSI 2: GANTI PASSWORD ---
    if (isset($_POST['update_password'])) {
        $pass_lama = $_POST['pass_lama'];
        $pass_baru = $_POST['pass_baru'];
        $pass_konfirmasi = $_POST['pass_konfirmasi'];

        try {
            $stmt_pass = $pdo->prepare("SELECT password FROM users WHERE id_user = ?");
            $stmt_pass->execute([$id_user]);
            $user_pass = $stmt_pass->fetch(PDO::FETCH_ASSOC);

            if ($user_pass) {
                if ($pass_lama === $user_pass['password'] || password_verify($pass_lama, $user_pass['password'])) {
                    if ($pass_baru === $pass_konfirmasi) {
                        $password_secure = password_hash($pass_baru, PASSWORD_BCRYPT);
                        
                        $stmt_change = $pdo->prepare("UPDATE users SET password = ? WHERE id_user = ?");
                        $stmt_change->execute([$password_secure, $id_user]);
                        $sukses = "Password akun Anda berhasil diganti!";
                    } else {
                        $error = "Konfirmasi password baru tidak cocok!";
                    }
                } else {
                    $error = "Password lama yang Anda masukkan salah!";
                }
            } else {
                $error = "Akun tidak ditemukan!";
            }
        } catch (Exception $e) {
            $error = "Gagal merubah password: " . $e->getMessage();
        }
    }
}

// Konfigurasi visual render foto profil
$path_foto_profil = "../../assets/uploads/profile/" . $foto_db;
if (!file_exists($path_foto_profil) || empty($foto_db) || $foto_db == 'default.png') {
    $path_foto_profil = "https://cdn-icons-png.flaticon.com/512/3135/3135715.png"; 
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil & Akun Dosen - SOBAT IK</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        .nav-tabs .nav-link {
            color: #4b5563;
            font-weight: 500;
            border: none;
            padding: 1rem 1.25rem;
        }
        .nav-tabs .nav-link.active {
            color: #245358 !important;
            border-bottom: 3px solid #245358 !important;
            font-weight: 600;
            background: transparent;
        }
        .form-control:focus {
            border-color: #245358;
            box-shadow: 0 0 0 0.25rem rgba(36, 83, 88, 0.15);
        }
    </style>
</head>
<body style="background-color: #f8fafc;">

    <?php include '../templates/sidebar.php'; ?>

    <div class="row">
        <div class="col-12 mb-3">
            <?php if (!empty($sukses)): ?>
                <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
                    <i class="fa-solid fa-circle-check me-2"></i> <?= $sukses; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
                    <i class="fa-solid fa-circle-xmark me-2"></i> <?= $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="d-flex align-items-center justify-content-between bg-white p-3 rounded shadow-sm">
                <div>
                    <h4 class="fw-bold text-dark mb-0">Pengaturan Profil & Akun</h4>
                    <p class="text-muted small mb-0">Kelola kredensial akun, unggah foto, dan modifikasi data personal Anda.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm bg-white rounded-3 overflow-hidden">
        <div class="card-header bg-white p-0 border-bottom">
            <ul class="nav nav-tabs" id="profileTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="biodata-tab" data-bs-toggle="tab" data-bs-target="#biodata" type="button" role="tab" aria-controls="biodata" aria-selected="true">
                        <i class="fa-solid fa-id-card me-2"></i>Data Diri & Foto
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="keamanan-tab" data-bs-toggle="tab" data-bs-target="#keamanan" type="button" role="tab" aria-controls="keamanan" aria-selected="false">
                        <i class="fa-solid fa-shield-halved me-2"></i>Keamanan Akun (Sandi)
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body p-4">
            <div class="tab-content" id="profileTabContent">
                
                <div class="tab-pane fade show active" id="biodata" role="tabpanel" aria-labelledby="biodata-tab">
                    <form action="" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="foto_lama" value="<?= htmlspecialchars($foto_db); ?>">
                        
                        <div class="row g-4 align-items-center">
                            <div class="col-md-3 text-center border-end">
                                <img src="<?= $path_foto_profil; ?>" class="rounded-circle img-thumbnail mb-3 shadow-sm" style="width: 140px; height: 140px; object-fit: cover;">
                                <div class="mb-2">
                                    <label for="upload-foto" class="btn btn-sm btn-outline-secondary px-3 rounded-pill">
                                        <i class="fa-solid fa-camera me-1"></i> Pilih Foto Baru
                                    </label>
                                    <input type="file" id="upload-foto" name="foto_profil" accept="image/*" class="d-none" onchange="previewImage(event)">
                                </div>
                                <span class="text-muted" style="font-size: 10px;">Format: PNG, JPG, JPEG (Maks 2MB)</span>
                            </div>
                            
                            <div class="col-md-9 ps-md-4">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-secondary">Nama Lengkap beserta Gelar</label>
                                        <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($nama_dosen); ?>" readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-secondary">Nomor Induk Dosen Nasional (NIDN)</label>
                                        <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($nidn_dosen); ?>" readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-secondary">Email Terhubung</label>
                                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($email_dosen); ?>" placeholder="dosen@kampus.ac.id" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-secondary">Nomor Handphone / WhatsApp</label>
                                        <input type="text" name="no_hp" class="form-control" value="<?= htmlspecialchars($telp_dosen); ?>" placeholder="08xxxxxxxxxx" required>
                                    </div>
                                </div>
                                <div class="mt-4 d-flex justify-content-end">
                                    <button type="submit" name="update_profile" class="btn text-white px-4 shadow-sm" style="background-color: #245358;">
                                        <i class="fa-solid fa-floppy-disk me-2"></i>Simpan Perubahan Data
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="tab-pane fade" id="keamanan" role="tabpanel" aria-labelledby="keamanan-tab">
                    <div class="row justify-content-center py-2">
                        <div class="col-md-8">
                            <h5 class="fw-bold text-dark mb-1"><i class="fa-solid fa-lock-open me-2 text-warning"></i>Perbarui Kata Sandi</h5>
                            <p class="text-muted small mb-4">Amankan akun Anda dengan melakukan perubahan sandi secara berkala.</p>
                            
                            <form action="" method="POST">
                                <div class="mb-3">
                                    <label class="form-label small fw-bold text-secondary">Kata Sandi Saat Ini</label>
                                    <input type="password" name="pass_lama" class="form-control" placeholder="••••••••" required>
                                </div>
                                <hr class="my-3" style="opacity: 0.1;">
                                <div class="mb-3">
                                    <label class="form-label small fw-bold text-secondary">Kata Sandi Baru</label>
                                    <input type="password" name="pass_baru" class="form-control" placeholder="Minimal 6 karakter" required>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label small fw-bold text-secondary">Ulangi Kata Sandi Baru</label>
                                    <input type="password" name="pass_konfirmasi" class="form-control" placeholder="Pastikan ketikan sama persis" required>
                                </div>
                                <div class="d-flex justify-content-end">
                                    <button type="submit" name="update_password" class="btn btn-warning px-4 fw-semibold text-dark shadow-sm">
                                        <i class="fa-solid fa-key me-2"></i>Perbarui Password Akun
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    </div> 
    </div> 
    </div> 

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function previewImage(event) {
            var reader = new FileReader();
            reader.onload = function(){
                var output = document.querySelector('.img-thumbnail');
                output.src = reader.result;
            }
            reader.readAsDataURL(event.target.files[0]);
        }
    </script>
</body>
</html>