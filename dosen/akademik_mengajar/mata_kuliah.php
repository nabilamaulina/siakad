<?php
// dosen/akademik_mengajar/mata_kuliah.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../templates/header.php'; 
require_once __DIR__ . '/../templates/sidebar.php'; 
require_once __DIR__ . '/../../config/database.php'; 

$id_user_dosen = $_SESSION['id_user'] ?? 1;

try {
    // 1. Ambil ID Dosen dari session (Menggunakan PDO)
    $stmt_dosen = $pdo->prepare("SELECT id_dosen FROM dosen WHERE id_user = ?");
    $stmt_dosen->execute([$id_user_dosen]);
    $data_dosen = $stmt_dosen->fetch(PDO::FETCH_ASSOC);
    $id_dosen = $data_dosen['id_dosen'] ?? 0;

    // 2. Query mengambil matakuliah unik yang diampu dosen (Menggunakan PDO)
    $stmt_mk = $pdo->prepare("
        SELECT DISTINCT mk.* FROM jadwal j
        JOIN matakuliah mk ON j.id_mk = mk.id_mk
        WHERE j.id_dosen = ?
        ORDER BY mk.kode_mk ASC
    ");
    $stmt_mk->execute([$id_dosen]);
    $list_mk = $stmt_mk->fetchAll(PDO::FETCH_ASSOC);

    // 3. Hitung total matakuliah
    $total_mk = count($list_mk);

} catch (Exception $e) {
    $list_mk = [];
    $total_mk = 0;
}
?>

<style>
    .transition-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .transition-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 15px rgba(0,0,0,0.1) !important;
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Materi & Silabus Kuliah</h4>
        <p class="text-muted small mb-0">Daftar mata kuliah mandiri yang Anda ampu pada semester berjalan.</p>
    </div>
    <div class="bg-white px-3 py-2 rounded-3 border shadow-sm d-flex align-items-center gap-2">
        <span class="text-muted small fw-medium">Total Diampu:</span>
        <span class="badge bg-warning text-dark rounded-pill fw-bold"><?= $total_mk; ?> Mata Kuliah</span>
    </div>
</div>

<div class="row g-4">
    <?php if ($total_mk > 0): ?>
        <?php foreach ($list_mk as $mk): ?>
            <div class="col-md-6 col-xl-4">
                <div class="card border-0 shadow-sm rounded-4 h-100 bg-white overflow-hidden transition-card">
                    <div class="p-4 border-bottom bg-light-subtle position-relative">
                        <span class="badge bg-secondary font-monospace mb-2" style="font-size: 11px;"><?= htmlspecialchars($mk['kode_mk']); ?></span>
                        <h5 class="fw-bold text-dark mb-3 text-truncate" style="line-height: 1.4; height: 48px; white-space: normal; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;"><?= htmlspecialchars($mk['nama_mk']); ?></h5>
                        <div class="d-flex align-items-center justify-content-between text-muted small mt-2">
                            <span><i class="fa-solid fa-graduation-cap me-1 text-secondary"></i> Bobot SKS</span>
                            <span class="fw-bold text-dark"><?= htmlspecialchars($mk['sks']); ?> SKS</span>
                        </div>
                    </div>
                    <div class="card-body p-3 bg-white d-flex gap-2">
                        <button class="btn btn-sm btn-light border w-50 rounded-3 fw-semibold small py-2"><i class="fa-solid fa-cloud-arrow-up me-1 text-primary"></i> Upload Silabus</button>
                        <button class="btn btn-sm btn-light border w-50 rounded-3 fw-semibold small py-2"><i class="fa-solid fa-folder-plus me-1 text-success"></i> Sesi Materi</button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
                <img src="https://cdn-icons-png.flaticon.com/512/7486/7486744.png" style="width: 70px; opacity: 0.3;" class="mb-3 mx-auto" alt="No Data">
                <h6 class="text-muted fw-bold mb-1">Belum Ada Kelas Mata Kuliah</h6>
                <p class="text-muted small mb-0">Sistem mendeteksi NIDN Anda belum dipetakan ke jadwal mengajar manapun.</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>