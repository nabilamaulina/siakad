<?php
// admin/sistem/profile.php
require_once '../../templates/header.php';
require_once '../../templates/sidebar.php';
require_once '../../config/database.php'; 

// 1. AMBIL ID USER DARI SESSION LOGIN
$id_user = $_SESSION['id_user'] ?? $_SESSION['id'] ?? null;

if (!$id_user) {
    echo "<div class='alert alert-danger m-4'>Sesi login tidak ditemukan. Silakan login kembali.</div>";
    require_once '../../templates/footer.php';
    exit;
}

// 2. AMBIL DATA TERBARU ADMIN DARI TABEL 'users'
$stmt = $pdo->prepare("SELECT * FROM users WHERE id_user = ?");
$stmt->execute([$id_user]);
$user = $stmt->fetch();

$pesan = "";

// 3. PROSES KETIKA FORMULIR DIKIRIM (POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username      = htmlspecialchars(trim($_POST['username']));
    $password_lama = $_POST['password_lama'];
    $password_baru = $_POST['password_baru'];
    
    $proses_update = true;
    $password_fix  = $user['password']; // Default password lama jika tidak diganti

    // --- PROSES 1: VALIDASI GANTI PASSWORD ---
    if (!empty($password_baru)) {
        // Mendukung enkripsi BCRYPT bawaan database Anda ($2y$10$...)
        if (password_verify($password_lama, $user['password']) || md5($password_lama) === $user['password'] || $password_lama === $user['password']) {
            $password_fix = password_hash($password_baru, PASSWORD_DEFAULT); 
        } else {
            $pesan = "<div class='alert alert-danger border-0 shadow-sm rounded-3'><i class='fa-solid fa-circle-xmark me-2'></i>Password lama yang Anda masukkan salah! Perubahan dibatalkan.</div>";
            $proses_update = false;
        }
    }

    // --- PROSES 2: PENGATURAN FOTO PROFIL (SIMPAN VIA SESSION KARENA DB TIDAK ADA KOLOM FOTO) ---
    if ($proses_update && !empty($_FILES['foto']['name'])) {
        $nama_file = $_FILES['foto']['name'];
        $tmp_file  = $_FILES['foto']['tmp_name'];
        $ekstensi  = strtolower(pathinfo($nama_file, PATHINFO_EXTENSION));
        
        if (in_array($ekstensi, ['jpg', 'jpeg', 'png'])) {
            $foto_baru = 'profile_' . $id_user . '_' . time() . '.' . $ekstensi;
            $folder_tujuan = __DIR__ . '/../../uploads/profile/';
            
            // Otomatis buat folder fisik jika belum ada di laptop Anda
            if (!is_dir($folder_tujuan)) {
                mkdir($folder_tujuan, 0777, true);
            }
            
            if (move_uploaded_file($tmp_file, $folder_tujuan . $foto_baru)) {
                // Hapus foto session lama jika ada
                if (isset($_SESSION['foto_user']) && $_SESSION['foto_user'] != 'default.png' && file_exists($folder_tujuan . $_SESSION['foto_user'])) {
                    @unlink($folder_tujuan . $_SESSION['foto_user']);
                }
                // Simpan nama file foto ke session agar bisa dibaca langsung oleh Header & Sidebar
                $_SESSION['foto_user'] = $foto_baru;
                $_SESSION['foto']      = $foto_baru;
                $_SESSION['avatar']    = $foto_baru;
            }
        } else {
            $pesan = "<div class='alert alert-danger border-0 shadow-sm rounded-3'>Format gambar harus JPG, JPEG, atau PNG!</div>";
            $proses_update = false;
        }
    }

    // --- PROSES 3: EKSEKUSI UPDATE KE DATABASE (HANYA KOLOM YANG ADA DI DB KAMU) ---
    if ($proses_update) {
        $sql = "UPDATE users SET username = ?, password = ? WHERE id_user = ?";
        $update_stmt = $pdo->prepare($sql);
        
        if ($update_stmt->execute([$username, $password_fix, $id_user])) {
            
            // SINKRONISASI SESSION INSTAN: Agar nama di Dashboard & Sidebar langsung berubah saat itu juga
            $_SESSION['username']     = $username;  
            $_SESSION['nama_user']    = $username; 
            $_SESSION['nama']         = $username;
            $_SESSION['nama_lengkap'] = $username;
            
            $pesan = "<div class='alert alert-success border-0 shadow-sm rounded-3'><i class='fa-solid fa-circle-check me-2'></i>Profil dan data akun berhasil diperbarui!</div>";
            
            // Ambil ulang data segar dari database
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id_user = ?");
            $stmt->execute([$id_user]);
            $user = $stmt->fetch();
        } else {
            $pesan = "<div class='alert alert-danger border-0 shadow-sm rounded-3'>Gagal memperbarui data ke database.</div>";
        }
    }
}

// Mengambil foto profil aktif dari session (jika belum ada, gunakan default)
$foto_profil_aktif = $_SESSION['foto_user'] ?? 'default.png';
?>

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<div class="container-fluid py-3" style="font-family: 'Plus Jakarta Sans', sans-serif;">
    
    <div class="row mb-4">
        <div class="col-12">
            <h3 class="fw-bold text-navy mb-1"><i class="fa-solid fa-user-gear me-2" style="color: #0a192f;"></i>Pengaturan Akun & Profil</h3>
            <p class="text-muted small">Perbarui data username login, foto profil visual, serta ubah kata sandi sistem Anda.</p>
        </div>
    </div>

    <?= $pesan; ?>

    <div class="row">
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm rounded-4 text-center p-4 bg-white">
                <div class="card-body">
                    <div class="position-relative d-inline-block mb-3">
                        <img src="../../uploads/profile/<?= $foto_profil_aktif; ?>"
                             class="rounded-circle border border-4 border-light shadow"
                             style="width: 130px; height: 130px; object-fit: cover;"
                             id="avatar-preview"
                             onerror="this.src='https://cdn-icons-png.flaticon.com/512/3135/3135715.png'">
                    </div>
                    <h5 class="fw-bold text-dark mb-1">@<?= htmlspecialchars($user['username']); ?></h5>
                    <p class="text-muted small mb-3">Grup Akses: <span class="text-uppercase fw-bold text-primary"><?= htmlspecialchars($user['role']); ?></span></p>
                    <span class="badge bg-opacity-10 text-success px-3 py-2 rounded-pill small fw-semibold" style="background-color: #e8f5e9;">
                        <i class="fa-solid fa-circle-check me-1"></i> Akun Status: Aktif
                    </span>
                </div>
            </div>
        </div>

        <div class="col-lg-8 mb-4">
            <div class="card border-0 shadow-sm rounded-4 bg-white">
                <div class="card-header bg-white py-3 border-0 rounded-top-4">
                    <h5 class="mb-0 fw-bold text-navy text-uppercase tracking-wider small">
                        <i class="fa-regular fa-id-card me-2 text-primary"></i> Formulir Perubahan Data
                    </h5>
                </div>
                <div class="card-body p-4 pt-2">
                    <form action="" method="POST" enctype="multipart/form-data">
                        
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Username / Nama Tampilan Akun</label>
                            <input type="text" name="username" class="form-control rounded-3 py-2" value="<?= htmlspecialchars($user['username']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Ganti Foto Profil (Format: JPG, PNG)</label>
                            <input type="file" name="foto" class="form-control rounded-3 py-2" id="input-foto" accept="image/*">
                            <div class="form-text text-muted" style="font-size: 11px;">Foto disimpan dalam sesi untuk mempercantik antarmuka dashboard Anda.</div>
                        </div>

                        <hr class="my-4" style="border-top: 1px dashed #ced4da;">
                        <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-lock text-danger me-2"></i>Ubah Kata Sandi (Password)</h6>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Masukkan Password Lama</label>
                            <div class="input-group">
                                <input type="password" name="password_lama" id="password_lama" class="form-control rounded-start-3 py-2" placeholder="Masukkan password saat ini untuk validasi...">
                                <button class="btn btn-outline-secondary rounded-end-3" type="button" onclick="togglePassword('password_lama', 'icon_lama')">
                                    <i class="fa-regular fa-eye" id="icon_lama"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold text-secondary">Masukkan Password Baru</label>
                            <div class="input-group">
                                <input type="password" name="password_baru" id="password_baru" class="form-control rounded-start-3 py-2" placeholder="Ketik password baru Anda di sini...">
                                <button class="btn btn-outline-secondary rounded-end-3" type="button" onclick="togglePassword('password_baru', 'icon_baru')">
                                    <i class="fa-regular fa-eye" id="icon_baru"></i>
                                </button>
                            </div>
                            <div class="form-text text-muted" style="font-size: 11px;">*Kosongkan kolom sandi lama & baru jika Anda hanya ingin mengubah username atau foto saja.</div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="../dashboard.php" class="btn btn-light border btn-sm rounded-pill px-4 py-2 fw-semibold">Batal</a>
                            <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4 py-2 fw-semibold text-white" style="background-color: #0a192f; border: none;">
                                <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Perubahan
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Fungsi Fitur Mengintip Password (Show/Hide)
function togglePassword(inputId, iconId) {
    const passwordInput = document.getElementById(inputId);
    const toggleIcon = document.getElementById(iconId);
    
    if (passwordInput.type === "password") {
        passwordInput.type = "text";
        toggleIcon.classList.remove("fa-eye");
        toggleIcon.classList.add("fa-eye-slash");
    } else {
        passwordInput.type = "password";
        toggleIcon.classList.remove("fa-eye-slash");
        toggleIcon.classList.add("fa-eye");
    }
}

// Fitur Instant Preview Gambar Saat Pilih File Foto Baru
document.getElementById('input-foto').onchange = function (evt) {
    const [file] = this.files;
    if (file) {
        document.getElementById('avatar-preview').src = URL.createObjectURL(file);
    }
}
</script>

<?php require_once '../../templates/footer.php'; ?>