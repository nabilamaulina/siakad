<?php
// File: dosen/akademik_mengajar/upload_silabus.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../templates/header.php'; 
require_once __DIR__ . '/../templates/sidebar.php'; 
require_once __DIR__ . '/../../config/database.php'; 

$id_mk = $_GET['id_mk'] ?? 0;

// Ambil data detail mata kuliah
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
?>

<div class="mb-4">
    <a href="mata_kuliah.php" class="btn btn-sm btn-secondary mb-3"><i class="fa-solid fa-arrow-left me-2"></i>Kembali</a>
    <h4 class="fw-bold text-dark">Upload Silabus Kuliah</h4>
    <p class="text-muted small">Mata Kuliah: <strong><?= htmlspecialchars($mk['nama_mk']); ?> (<?= htmlspecialchars($mk['kode_mk']); ?>)</strong></p>
</div>

<div class="card border-0 shadow-sm rounded-4 p-4 bg-white" style="max-width: 600px;">
    <form action="proses_upload_silabus.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id_mk" value="<?= $id_mk; ?>">
        
        <div class="mb-4">
            <label class="form-label fw-semibold small text-dark">Pilih File Silabus (PDF/Docx, Max 5MB)</label>
            <input type="file" name="file_silabus" class="form-control rounded-3" required>
        </div>
        
        <button type="submit" class="btn btn-primary rounded-3 px-4 fw-semibold"><i class="fa-solid fa-cloud-arrow-up me-2"></i>Mulai Unggah Dokumen</button>
    </form>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>