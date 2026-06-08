<?php
// File: dosen/akademik_mengajar/mata_kuliah.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ambil template layout dan koneksi database
require_once __DIR__ . '/../templates/header.php'; 
require_once __DIR__ . '/../templates/sidebar.php'; 
require_once __DIR__ . '/../../config/database.php'; 

// Mengambil id_user dari session login dosen
$id_user_dosen = $_SESSION['id_user'] ?? 0;
$nama_user_dosen = $_SESSION['nama_user'] ?? '';

$list_mk = [];
$total_mk = 0;

try {
    // 1. LANGKAH UTAMA: Cari id_dosen berdasarkan id_user dari session
    $stmt_dosen = $pdo->prepare("SELECT id_dosen FROM dosen WHERE id_user = ?");
    $stmt_dosen->execute([$id_user_dosen]);
    $data_dosen = $stmt_dosen->fetch(PDO::FETCH_ASSOC);
    $id_dosen = $data_dosen['id_dosen'] ?? 0;

    // 2. STRATEGI FALLBACK: Jika id_user di tabel dosen belum dipetakan
    if ($id_dosen == 0 && !empty($nama_user_dosen)) {
        $stmt_fallback = $pdo->prepare("SELECT id_dosen FROM dosen WHERE nama_dosen LIKE ? LIMIT 1");
        $stmt_fallback->execute(['%' . $nama_user_dosen . '%']);
        $data_fallback = $stmt_fallback->fetch(PDO::FETCH_ASSOC);
        if ($data_fallback) {
            $id_dosen = $data_fallback['id_dosen'];
        }
    }

    // 3. JALANKAN QUERY AMBIL MATA KULIAH JIKA ID DOSEN BERHASIL DIDAPATKAN
    if ($id_dosen > 0) {
        // Ambil juga kolom `silabus` yang baru saja ditambahkan ke database
        $stmt_mk = $pdo->prepare("
            SELECT DISTINCT mk.id_mk, mk.kode_mk, mk.nama_mk, mk.sks, mk.silabus 
            FROM jadwal j
            JOIN mata_kuliah mk ON j.id_mk = mk.id_mk
            WHERE j.id_dosen = ?
            ORDER BY mk.kode_mk ASC
        ");
        $stmt_mk->execute([$id_dosen]);
        $list_mk = $stmt_mk->fetchAll(PDO::FETCH_ASSOC);
        $total_mk = count($list_mk);
    }

} catch (Exception $e) {
    echo "<div class='alert alert-danger rounded-4 m-3'><i class='fa-solid fa-triangle-exclamation me-2'></i>Gagal memuat data: " . htmlspecialchars($e->getMessage()) . "</div>";
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
    .btn-action-mk {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none !important;
        font-size: 13px;
    }
</style>

<?php if (isset($_GET['status']) && $_GET['status'] === 'success_upload'): ?>
    <div class="alert alert-success alert-dismissible fade show rounded-3 mb-4 shadow-sm border-0 d-flex align-items-center" role="alert" style="background-color: #d1e7dd; color: #0f5132;">
        <i class="fa-solid fa-circle-check me-3 fs-4"></i>
        <div>
            <strong>Berhasil!</strong> Dokumen silabus perkuliahan telah berhasil diunggah dan diperbarui ke sistem.
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Materi & Silabus Kuliah</h4>
        <p class="text-muted small mb-0">Daftar mata kuliah mandiri yang Anda ampu pada semester berjalan.</p>
    </div>
    <div class="bg-white px-3 py-2 rounded-3 border shadow-sm d-flex align-items-center gap-2">
        <span class="text-muted small fw-medium">Total Diampu:</span>
        <span class="badge bg-warning text-dark rounded-pill fw-bold" style="padding: 6px 12px; font-size: 12px;"><?= $total_mk; ?> Mata Kuliah</span>
    </div>
</div>

<div class="row g-4">
    <?php if ($total_mk > 0): ?>
        <?php foreach ($list_mk as $mk): ?>
            <div class="col-md-6 col-xl-4">
                <div class="card border-0 shadow-sm rounded-4 h-100 bg-white overflow-hidden transition-card">
                    
                    <div class="p-4 border-bottom bg-light-subtle position-relative">
                        <span class="badge bg-secondary font-monospace mb-2" style="font-size: 11px; padding: 5px 10px;"><?= htmlspecialchars($mk['kode_mk']); ?></span>
                        
                        <h5 class="fw-bold text-dark mb-3" style="line-height: 1.4; height: 48px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; white-space: normal;">
                            <?= htmlspecialchars($mk['nama_mk']); ?>
                        </h5>
                        
                        <div class="d-flex align-items-center justify-content-between text-muted small mt-2">
                            <span><i class="fa-solid fa-graduation-cap me-1 text-secondary"></i> Bobot SKS</span>
                            <span class="fw-bold text-dark"><?= htmlspecialchars($mk['sks']); ?> SKS</span>
                        </div>
                    </div>
                    
                    <div class="card-body p-3 bg-white d-flex flex-column gap-2">
                        
                        <?php if (!empty($mk['silabus'])): ?>
                            <div class="d-flex gap-2 w-100">
                                <a href="../../assets/uploads/silabus/<?= $mk['silabus']; ?>" target="_blank" class="btn btn-sm btn-info text-white border w-50 rounded-3 fw-semibold py-2 btn-action-mk">
                                    <i class="fa-solid fa-file-pdf me-2"></i> Lihat Silabus
                                </a>
                                <a href="upload_silabus.php?id_mk=<?= $mk['id_mk']; ?>" class="btn btn-sm btn-light border w-50 rounded-3 fw-semibold py-2 btn-action-mk text-secondary">
                                    <i class="fa-solid fa-arrows-rotate me-2"></i> Ganti Baru
                                </a>
                            </div>
                        <?php else: ?>
                            <a href="upload_silabus.php?id_mk=<?= $mk['id_mk']; ?>" class="btn btn-sm btn-light border w-100 rounded-3 fw-semibold py-2 btn-action-mk text-primary">
                                <i class="fa-solid fa-cloud-arrow-up me-2"></i> Upload Silabus
                            </a>
                        <?php endif; ?>

                        <a href="sesi_materi.php?id_mk=<?= $mk['id_mk']; ?>" class="btn btn-sm btn-light border w-100 rounded-3 fw-semibold py-2 btn-action-mk text-success">
                            <i class="fa-solid fa-folder-plus me-2"></i> Sesi Materi
                        </a>
                    </div>

                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
                <img src="https://cdn-icons-png.flaticon.com/512/7486/7486744.png" style="width: 70px; opacity: 0.3;" class="mb-3 mx-auto" alt="No Data">
                <h6 class="text-muted fw-bold mb-1">Belum Ada Kelas Mata Kuliah</h6>
                <p class="text-muted small mb-0">Sistem mendeteksi NIDN atau Akun Anda belum dipetakan ke jadwal mengajar manapun.</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>