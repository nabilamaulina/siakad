<?php
// File: dosen/akademik_mengajar/sesi_materi.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../templates/header.php'; 
require_once __DIR__ . '/../templates/sidebar.php'; 
require_once __DIR__ . '/../../config/database.php'; 

$id_mk = $_GET['id_mk'] ?? 0;

// 1. Ambil data detail mata kuliah
try {
    $stmt = $pdo->prepare("SELECT * FROM mata_kuliah WHERE id_mk = ?");
    $stmt->execute([$id_mk]);
    $mk = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $mk = null;
}

if (!$mk) {
    echo "<div class='alert alert-danger m-4'>Mata kuliah tidak ditemukan.</div>";
    require_once __DIR__ . '/../templates/footer.php';
    exit;
}

// 2. Ambil daftar materi yang sudah pernah diupload sebelumnya
$list_materi = [];
try {
    $stmt_materi = $pdo->prepare("SELECT * FROM materi WHERE id_mk = ? ORDER BY pertemuan_ke ASC");
    $stmt_materi->execute([$id_mk]);
    $list_materi = $stmt_materi->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $list_materi = [];
}
?>

<div class="mb-4">
    <a href="mata_kuliah.php" class="btn btn-sm btn-secondary mb-3"><i class="fa-solid fa-arrow-left me-2"></i>Kembali</a>
    <h4 class="fw-bold text-dark">Manajemen Sesi Materi Kuliah</h4>
    <p class="text-muted small">Mata Kuliah: <strong><?= htmlspecialchars($mk['nama_mk']); ?> (<?= htmlspecialchars($mk['kode_mk']); ?>)</strong></p>
</div>

<?php if (isset($_GET['status'])): ?>
    <?php if ($_GET['status'] === 'success_materi'): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-3 mb-4" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i><strong>Berhasil!</strong> Bahan ajar sesi materi baru telah disimpan.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php elseif ($_GET['status'] === 'success_delete'): ?>
        <div class="alert alert-warning alert-dismissible fade show rounded-3 mb-4" role="alert">
            <i class="fa-solid fa-trash-can me-2"></i><strong>Berhasil!</strong> Materi kuliah telah dihapus dari sistem.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
            <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-square-plus text-success me-2"></i>Tambah Sesi Materi Baru</h6>
            <form action="proses_upload_materi.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id_mk" value="<?= $id_mk; ?>">
                
                <div class="mb-3">
                    <label class="form-label fw-semibold small text-dark">Pertemuan Ke-</label>
                    <select name="pertemuan_ke" class="form-select rounded-3" required>
                        <?php for($i = 1; $i <= 16; $i++): ?>
                            <option value="<?= $i; ?>">Pertemuan <?= $i; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small text-dark">Judul / Topik Materi</label>
                    <input type="text" name="judul_materi" class="form-control rounded-3" placeholder="Contoh: Pengenalan OOP" required>
                </div>
                
                <div class="mb-4">
                    <label class="form-label fw-semibold small text-dark">File Bahan Ajar (PDF, PPTX, ZIP)</label>
                    <input type="file" name="file_materi" class="form-control rounded-3" required>
                </div>
                
                <button type="submit" class="btn btn-success w-100 rounded-3 fw-semibold"><i class="fa-solid fa-plus me-2"></i>Simpan Sesi Materi</button>
            </form>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
            <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-folder-open text-warning me-2"></i>Daftar Materi Kuliah Terupload</h6>
            
            <div class="table-responsive">
                <table class="table table-hover align-middle small">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 100px;" class="text-center">Pertemuan</th>
                            <th>Topik / Judul Materi</th>
                            <th style="width: 160px;" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($list_materi) > 0): ?>
                            <?php foreach ($list_materi as $materi): ?>
                                <tr>
                                    <td class="fw-bold text-center bg-light rounded">P-<?= $materi['pertemuan_ke']; ?></td>
                                    <td><?= htmlspecialchars($materi['judul_materi']); ?></td>
                                    <td class="text-center">
                                        <div class="d-flex gap-1 justify-content-center">
                                            <a href="../../assets/uploads/materi/<?= $materi['file_path']; ?>" target="_blank" class="btn btn-sm btn-outline-primary rounded-2 py-1 px-2" title="Unduh File">
                                                <i class="fa-solid fa-download"></i>
                                            </a>
                                            <a href="proses_hapus_materi.php?id_materi=<?= $materi['id_materi']; ?>&id_mk=<?= $id_mk; ?>" 
                                               class="btn btn-sm btn-outline-danger rounded-2 py-1 px-2" 
                                               onclick="return confirm('Apakah Anda yakin ingin menghapus materi pertemuan ke-<?= $materi['pertemuan_ke']; ?> ini? Berkas fisik juga akan dihapus permanen.');"
                                               title="Hapus Materi">
                                                <i class="fa-solid fa-trash-can"></i> Hapus
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">Belum ada materi perkuliahan yang diunggah untuk kelas ini.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>