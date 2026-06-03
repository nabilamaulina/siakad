<?php
// dosen/perwalian/krs_validasi.php
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}
require_once __DIR__ . '/../templates/header.php'; 
require_once __DIR__ . '/../templates/sidebar.php'; 
require_once __DIR__ . '/../../config/database.php';

$id_user_dosen = $_SESSION['id_user'] ?? 1;

try {
    // 1. Ambil ID Dosen menggunakan PDO
    $stmt_dosen = $pdo->prepare("SELECT id_dosen FROM dosen WHERE id_user = ?");
    $stmt_dosen->execute([$id_user_dosen]);
    $data_dosen = $stmt_dosen->fetch(PDO::FETCH_ASSOC);
    $id_dosen = $data_dosen['id_dosen'] ?? 0;

    // 2. Eksekusi Tombol Setujui KRS dengan Prepared Statement PDO (Aman dari SQL Injection)
    if (isset($_POST['setujui_krs'])) {
        $id_krs = $_POST['id_krs'];
        $stmt_update = $pdo->prepare("UPDATE krs SET status_validasi = 'Disetujui' WHERE id_krs = ?");
        $stmt_update->execute([$id_krs]);
        echo "<script>alert('KRS Mahasiswa Telah Berhasil Disetujui!'); window.location='krs_validasi.php';</script>";
        exit;
    }

    // 3. Ambil data ajuan KRS mahasiswa bimbingan wali aktif menggunakan PDO (FIX BUG #F)
    $query_krs = "
        SELECT k.id_krs, k.status_validasi, k.tahun_akademik, m.nama_mahasiswa, m.nim, kelas.nama_kelas
        FROM krs k
        JOIN mahasiswa m ON k.id_mahasiswa = m.id_mahasiswa
        LEFT JOIN kelas ON m.id_kelas = kelas.id_kelas
        WHERE m.id_dosen_wali = ?
        GROUP BY k.id_mahasiswa
        ORDER BY k.status_validasi DESC, m.nim ASC
    ";
    $stmt_krs = $pdo->prepare($query_krs);
    $stmt_krs->execute([$id_dosen]);
    $list_krs = $stmt_krs->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $list_krs = [];
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Validasi KRS Mahasiswa</h4>
        <p class="text-muted small mb-0">Persetujuan Kartu Rencana Studi mahasiswa bimbingan akademik Anda.</p>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
    <div class="card-header bg-white py-3 border-bottom">
        <h6 class="fw-bold text-dark mb-0"><i class="fa-solid fa-file-signature me-2 text-secondary"></i>Daftar Ajuan KRS</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="min-width: 800px;">
                <thead class="bg-light text-secondary small text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">
                    <tr>
                        <th class="py-3 ps-4" width="20%">Mahasiswa</th>
                        <th width="15%">Kelas</th>
                        <th width="20%">Tahun Akademik</th>
                        <th width="20%" class="text-center">Status Validasi</th>
                        <th width="25%" class="text-center">Aksi Operasional</th>
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
                                    <span class="badge border bg-light text-dark rounded-3 px-2 py-1 fw-semibold"><?= htmlspecialchars($row['nama_kelas'] ?? 'Belum Diplot'); ?></span>
                                </td>
                                <td class="text-dark">
                                    <div class="fw-medium"><?= htmlspecialchars($row['tahun_akademik'] ?? '-'); ?></div>
                                </td>
                                <td class="text-center">
                                    <?php if (($row['status_validasi'] ?? 'Pending') == 'Disetujui'): ?>
                                        <span class="badge bg-success-subtle text-success rounded-pill px-3 py-1.5 fw-bold" style="font-size: 11px;">DISETUJUI</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning-subtle text-warning rounded-pill px-3 py-1.5 fw-bold" style="font-size: 11px;">BELUM DISETUJUI</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if (($row['status_validasi'] ?? 'Pending') != 'Disetujui'): ?>
                                        <form method="POST" action="" onsubmit="return confirm('Apakah Anda yakin ingin menyetujui ajuan KRS mahasiswa ini?');">
                                            <input type="hidden" name="id_krs" value="<?= $row['id_krs']; ?>">
                                            <button type="submit" name="setujui_krs" class="btn btn-sm btn-success px-3 rounded-3 fw-semibold shadow-sm">
                                                <i class="fa-solid fa-check-double me-1"></i> Setujui KRS
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-light border text-muted px-3 rounded-3 disabled"><i class="fa-solid fa-circle-check text-success me-1"></i> Selesai disetujui</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-inbox fa-2x mb-2" style="opacity: 0.3;"></i>
                                <p class="mb-0 small">Belum ada ajuan pengisian KRS dari mahasiswa perwalian Anda.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>