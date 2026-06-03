<?php
// dosen/akademik_mengajar/nilai.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../templates/header.php'; 
require_once __DIR__ . '/../templates/sidebar.php'; 
require_once __DIR__ . '/../../config/database.php'; 

$id_user_dosen = $_SESSION['id_user'] ?? 1;

try {
    // 1. Ambil ID Dosen (Menggunakan PDO)
    $stmt_dosen = $pdo->prepare("SELECT id_dosen FROM dosen WHERE id_user = ?");
    $stmt_dosen->execute([$id_user_dosen]);
    $data_dosen = $stmt_dosen->fetch(PDO::FETCH_ASSOC);
    $id_dosen = $data_dosen['id_dosen'] ?? 0;

    // 2. Mengambil list mata kuliah & kelas yang diampu untuk dropdown (Menggunakan PDO)
    $stmt_opsi = $pdo->prepare("
        SELECT j.id_jadwal, mk.nama_mk, k.nama_kelas 
        FROM jadwal j
        JOIN mata_kuliah mk ON j.id_mk = mk.id_mk
        JOIN kelas k ON j.id_kelas = k.id_kelas
        WHERE j.id_dosen = ?
    ");
    $stmt_opsi->execute([$id_dosen]);
    $list_opsi = $stmt_opsi->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $list_opsi = [];
}
?>

<div class="row mb-4">
    <div class="col-12">
        <h4 class="fw-bold text-dark mb-1">Input Nilai Akhir Mahasiswa</h4>
        <p class="text-muted small mb-0">Otorisasi penuh pengisian nilai formatif, UTS, UAS, dan konversi Grade Mutu.</p>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
    <div class="card-body p-4">
        <label class="form-label fw-bold text-secondary small text-uppercase mb-2">Pilih Mata Kuliah & Kelas Terlebih Dahulu :</label>
        <div class="row g-2">
            <div class="col-md-9">
                <select class="form-select border-0 bg-light rounded-3 font-medium" style="height: 45px;">
                    <option value="">-- Pilih Rencana Penilaian Kelas --</option>
                    <?php if (!empty($list_opsi)): ?>
                        <?php foreach ($list_opsi as $opsi): ?>
                            <option value="<?= $opsi['id_jadwal']; ?>"><?= htmlspecialchars($opsi['nama_mk']); ?> ( Kelas <?= htmlspecialchars($opsi['nama_kelas']); ?> )</option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-md-3">
                <button class="btn text-white w-100 rounded-3 fw-bold" style="height: 45px; background-color: #245358;"><i class="fa-solid fa-magnifying-glass me-2"></i> Buka Lembar Nilai</button>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white text-muted small">
    <i class="fa-solid fa-lock fa-2x mb-3 text-secondary" style="opacity: 0.4;"></i>
    <p class="mb-0 fw-medium">Silakan pilih kombinasi mata kuliah untuk membuka entri nilai mahasiswa secara real-time.</p>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>