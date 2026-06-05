<?php
// dosen/perwalian/krs_validasi.php
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}
require_once __DIR__ . '/../templates/header.php'; 
require_once __DIR__ . '/../templates/sidebar.php'; 
require_once __DIR__ . '/../../config/database.php';

// Ambil data session login dosen
$session_username = $_SESSION['username'] ?? '';
$id_user_login   = $_SESSION['id_user'] ?? 0;
$role_login      = $_SESSION['role'] ?? ''; 

// Proteksi Halaman: Pastikan yang masuk adalah akun dosen resmi
if ($role_login !== 'dosen' && empty($session_username)) {
    die("<div class='alert alert-danger m-4'>Akses ditolak. Halaman ini hanya untuk Akun Dosen resmi.</div>");
}

try {
    // 1. Cari id_dosen berdasarkan username (NIDN) atau id_user relasi login
    $stmt_dosen = $pdo->prepare("SELECT id_dosen FROM dosen WHERE nidn = ? OR id_user = ? LIMIT 1");
    $stmt_dosen->execute([$session_username, $id_user_login]);
    $data_dosen = $stmt_dosen->fetch(PDO::FETCH_ASSOC);
    $id_dosen = $data_dosen['id_dosen'] ?? 0;

    // 2. Aksi: Setujui Seluruh KRS Mahasiswa Bimbingan
    if (isset($_POST['setujui_krs'])) {
        $id_mhs = (int)$_POST['id_mahasiswa'];
        
        // Update status_validasi dan status_krs di tabel KRS sesuai format ENUM database Anda
        $stmt_update = $pdo->prepare("UPDATE krs SET status_validasi = 'Disetujui', status_krs = 'disetujui' WHERE id_mahasiswa = ?");
        $stmt_update->execute([$id_mhs]);
        
        echo "<script>alert('Seluruh usulan KRS mahasiswa tersebut berhasil DISETUJUI!'); window.location='krs_validasi.php';</script>";
        exit;
    }

    // 3. Aksi: Batalkan Seluruh KRS Mahasiswa Bimbingan
    if (isset($_POST['batalkan_krs'])) {
        $id_mhs = (int)$_POST['id_mahasiswa'];
        
        // Kembalikan status ke 'Pending' / 'pending'
        $stmt_batal = $pdo->prepare("UPDATE krs SET status_validasi = 'Pending', status_krs = 'pending' WHERE id_mahasiswa = ?");
        $stmt_batal->execute([$id_mhs]);
        
        echo "<script>alert('Persetujuan KRS mahasiswa berhasil dibatalkan.'); window.location='krs_validasi.php';</script>";
        exit;
    }

    // 4. Ambil ringkasan ajuan KRS mahasiswa yang dibimbing oleh dosen ini (berdasarkan id_dosen_wali)
    $query_krs = "
        SELECT 
            m.id_mahasiswa,
            m.nama_mahasiswa, 
            m.nim, 
            kls.nama_kelas,
            k.tahun_akademik,
            k.status_validasi,
            COUNT(k.id_krs) as total_matakuliah
        FROM krs k
        JOIN mahasiswa m ON k.id_mahasiswa = m.id_mahasiswa
        LEFT JOIN kelas kls ON m.id_kelas = kls.id_kelas
        WHERE m.id_dosen_wali = ?
        GROUP BY m.id_mahasiswa
        ORDER BY k.status_validasi DESC, m.nim ASC
    ";
    $stmt_krs = $pdo->prepare($query_krs);
    $stmt_krs->execute([$id_dosen]);
    $list_krs = $stmt_krs->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $list_krs = [];
    $error_msg = $e->getMessage();
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Validasi KRS Mahasiswa (Dosen PA)</h4>
        <p class="text-muted small mb-0">Persetujuan Kartu Rencana Studi khusus untuk mahasiswa bimbingan akademik Anda.</p>
    </div>
</div>

<?php if (isset($error_msg)): ?>
    <div class="alert alert-danger rounded-3"><?= htmlspecialchars($error_msg); ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
    <div class="card-header bg-white py-3 border-bottom">
        <h6 class="fw-bold text-dark mb-0"><i class="fa-solid fa-file-signature me-2 text-primary"></i>Daftar Ajuan KRS Mahasiswa Bimbingan</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="min-width: 800px;">
                <thead class="bg-light text-secondary small text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">
                    <tr>
                        <th class="py-3 ps-4" width="25%">Mahasiswa</th>
                        <th width="15%">Kelas</th>
                        <th width="20%">Tahun Akademik</th>
                        <th width="15%" class="text-center">Jumlah MK</th>
                        <th width="15%" class="text-center">Status Validasi</th>
                        <th width="10%" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody style="font-size: 14px;">
                    <?php if (!empty($list_krs)): ?>
                        <?php foreach ($list_krs as $row): ?>
                            <tr>
                                <td class="py-3 ps-4">
                                    <span class="d-block fw-bold text-dark"><?= htmlspecialchars($row['nama_mahasiswa']); ?></span>
                                    <span class="text-muted font-monospace small"><?= htmlspecialchars($row['nim']); ?></span>
                                </td>
                                <td>
                                    <span class="badge border bg-light text-dark rounded-3 px-2 py-1 fw-semibold">Kelas <?= htmlspecialchars($row['nama_kelas'] ?? 'Belum Diplot'); ?></span>
                                </td>
                                <td class="text-dark">
                                    <div class="fw-medium"><?= htmlspecialchars($row['tahun_akademik']); ?></div>
                                </td>
                                <td class="text-center fw-bold text-primary">
                                    <?= htmlspecialchars($row['total_matakuliah']); ?> MK
                                </td>
                                <td class="text-center">
                                    <?php if ($row['status_validasi'] == 'Disetujui'): ?>
                                        <span class="badge bg-success-subtle text-success rounded-pill px-3 py-1.5 fw-bold" style="font-size: 11px;">DISETUJUI</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning-subtle text-warning rounded-pill px-3 py-1.5 fw-bold" style="font-size: 11px;">BELUM DISETUJUI</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($row['status_validasi'] != 'Disetujui'): ?>
                                        <form method="POST" action="" onsubmit="return confirm('Apakah Anda yakin ingin menyetujui seluruh ajuan KRS mahasiswa ini?');">
                                            <input type="hidden" name="id_mahasiswa" value="<?= $row['id_mahasiswa']; ?>">
                                            <button type="submit" name="setujui_krs" class="btn btn-sm btn-success px-3 rounded-3 fw-semibold shadow-sm">
                                                <i class="fa-solid fa-check me-1"></i> Setujui
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" action="" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan persetujuan KRS mahasiswa ini?');">
                                            <input type="hidden" name="id_mahasiswa" value="<?= $row['id_mahasiswa']; ?>">
                                            <button type="submit" name="batalkan_krs" class="btn btn-sm btn-outline-danger px-2 rounded-3 fw-semibold">
                                                Batalkan
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-inbox fa-2x mb-2" style="opacity: 0.3;"></i>
                                <p class="mb-0 small">Tidak ada ajuan pengisian KRS dari mahasiswa perwalian Anda.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>