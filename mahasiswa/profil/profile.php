<?php
// siakad/mahasiswa/profil/profile.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../dosen/templates/header.php';
require_once __DIR__ . '/../templates/sidebar.php';
require_once __DIR__ . '/../../config/database.php';

// =========================================================================
// ⚙️ KONFIGURASI PEMETAAN DATABASE (Sesuaikan dengan gambar database Anda)
// =========================================================================
$tabel_mahasiswa    = 'mahasiswa';        // Nama tabel profil mahasiswa Anda
$tabel_user         = 'user';             // Nama tabel kredensial login user Anda
$kolom_id_mhs       = 'id_user';          // Kolom primary key / foreign key penghubung mahasiswa
$kolom_nama_mhs     = 'nama_mahasiswa';   // Kolom nama mahasiswa
$kolom_nim_mhs      = 'nim';              // Kolom NIM mahasiswa
$kolom_email_mhs    = 'email';            // Kolom email mahasiswa
$kolom_hp_mhs       = 'no_hp';            // Kolom nomor handphone mahasiswa
// =========================================================================

$id_user_mhs = $_SESSION['id_user'] ?? 0;
$msg_status = ''; $msg_text = '';

// [UPDATE]: Memproses Form Edit Profil Akun
if (isset($_POST['btn_update_profil'])) {
    $email = trim($_POST['email']);
    $no_hp = trim($_POST['no_hp']);
    $pass_baru = trim($_POST['pass_baru']);

    try {
        $pdo->beginTransaction(); // Proteksi transaksi data ganda

        // Update kontak detail mahasiswa
        $stmt_up_mhs = $pdo->prepare("UPDATE $tabel_mahasiswa SET $kolom_email_mhs = ?, $kolom_hp_mhs = ? WHERE $kolom_id_mhs = ?");
        $stmt_up_mhs->execute([$email, $no_hp, $id_user_mhs]);

        // Jika form password baru diisi, enkripsi dan simpan ke tabel user
        if (!empty($pass_baru)) {
            $hashed_password = password_hash($pass_baru, PASSWORD_DEFAULT);
            $stmt_up_user = $pdo->prepare("UPDATE $tabel_user SET password = ? WHERE id_user = ?");
            $stmt_up_user->execute([$hashed_password, $id_user_mhs]);
        }

        $pdo->commit();
        $msg_status = 'success'; $msg_text = 'Profil dan password akun Anda berhasil diperbarui!';
    } catch (Exception $e) {
        $pdo->rollBack();
        $msg_status = 'danger'; $msg_text = 'Gagal menyimpan pembaruan data profil.';
    }
}

// [READ]: Mengambil Data Terkini Profil Mahasiswa
try {
    $stmt_get = $pdo->prepare("SELECT m.*, u.username FROM $tabel_mahasiswa m JOIN $tabel_user u ON m.$kolom_id_mhs = u.id_user WHERE m.$kolom_id_mhs = ?");
    $stmt_get->execute([$id_user_mhs]);
    $profil = $stmt_get->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $profil = [];
}
?>

<div class="container-fluid px-4 py-4">
    <div class="row g-4 max-w-4xl mx-auto" style="max-width: 900px;">
        <div class="col-md-4 text-center">
            <div class="card border-0 shadow-sm p-4 bg-white rounded-3">
                <img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" class="rounded-circle img-thumbnail mb-3 mx-auto" style="width:100px; height:100px; object-fit: cover;">
                <h6 class="fw-bold mb-1 text-dark"><?= htmlspecialchars($profil[$kolom_nama_mhs] ?? 'Mahasiswa'); ?></h6>
                <span class="badge bg-secondary-subtle text-dark px-3 py-1 rounded-pill small"><?= htmlspecialchars($profil[$kolom_nim_mhs] ?? 'NIM'); ?></span>
            </div>
        </div>

        <div class="col-md-8">
            <?php if(!empty($msg_text)): ?>
                <div class="alert alert-<?= $msg_status; ?> rounded-3 shadow-sm mb-4"><i class="fa-solid fa-circle-check me-2"></i><?= $msg_text; ?></div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm rounded-3 bg-white">
                <div class="card-header bg-white py-3 border-bottom"><h6 class="mb-0 fw-bold" style="color:#245358;"><i class="fa-solid fa-user-gear me-2"></i>Edit Informasi Kontak & Kredensial</h6></div>
                <div class="card-body p-4">
                    <form method="POST">
                        <div class="row g-3 mb-3">
                            <div class="col-sm-6">
                                <label class="form-label small fw-bold text-secondary">NIM (Terkunci)</label>
                                <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($profil[$kolom_nim_mhs] ?? ''); ?>" readonly>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label small fw-bold text-secondary">Nama Lengkap</label>
                                <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($profil[$kolom_nama_mhs] ?? ''); ?>" readonly>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Alamat Email Resmi</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($profil[$kolom_email_mhs] ?? ''); ?>" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-secondary">No Handphone / Nomor WA</label>
                            <input type="text" name="no_hp" class="form-control" value="<?= htmlspecialchars($profil[$kolom_hp_mhs] ?? ''); ?>" required>
                        </div>
                        <div class="mb-4 bg-light p-3 rounded-3 border">
                            <label class="form-label small fw-bold text-dark"><i class="fa-solid fa-key me-1 text-warning"></i>Ganti Kata Sandi Baru</label>
                            <input type="password" name="pass_baru" class="form-control bg-white" placeholder="Biarkan kosong jika tidak ingin mengganti password lama">
                        </div>
                        <div class="text-end">
                            <button type="submit" name="btn_update_profil" class="btn text-white rounded-3 px-4 shadow-sm" style="background-color:#245358;"><i class="fa-solid fa-floppy-disk me-1"></i> Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>