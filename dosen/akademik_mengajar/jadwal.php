<?php 
// dosen/akademik_mengajar/jadwal.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../templates/header.php'; 
require_once __DIR__ . '/../templates/sidebar.php'; 
require_once __DIR__ . '/../../config/database.php'; 

$id_user_dosen = $_SESSION['id_user'] ?? 1;

try {
    // 1. Ambil ID Dosen berdasarkan session user aktif (Menggunakan PDO)
    $stmt_dosen = $pdo->prepare("SELECT id_dosen FROM dosen WHERE id_user = ?");
    $stmt_dosen->execute([$id_user_dosen]);
    $data_dosen = $stmt_dosen->fetch(PDO::FETCH_ASSOC);
    $id_dosen = $data_dosen['id_dosen'] ?? 0;

    // 2. Query Utama: Ambil Jadwal Mengajar Asli (Menggunakan PDO)
    $stmt_jadwal = $pdo->prepare("
        SELECT j.*, mk.nama_mk, mk.kode_mk, mk.sks, k.nama_kelas 
        FROM jadwal j
        JOIN mata_kuliah mk ON j.id_mk = mk.id_mk
        JOIN kelas k ON j.id_kelas = k.id_kelas
        WHERE j.id_dosen = ?
        ORDER BY FIELD(j.hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu')
    ");
    $stmt_jadwal->execute([$id_dosen]);
    $list_jadwal = $stmt_jadwal->fetchAll(PDO::FETCH_ASSOC);

    // 3. Hitung Counter Statistik
    $total_jadwal = count($list_jadwal);

    $stmt_sks = $pdo->prepare("
        SELECT SUM(mk.sks) as total_sks 
        FROM jadwal j 
        JOIN mata_kuliah mk ON j.id_mk = mk.id_mk 
        WHERE j.id_dosen = ?
    ");
    $stmt_sks->execute([$id_dosen]);
    $data_sks = $stmt_sks->fetch(PDO::FETCH_ASSOC);
    $total_sks = $data_sks['total_sks'] ?? 0;

} catch (Exception $e) {
    $list_jadwal = [];
    $total_jadwal = 0;
    $total_sks = 0;
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Jadwal Mengajar & Presensi</h4>
        <p class="text-muted small mb-0">Manajemen kelas, waktu perkuliahan, dan kontrol absensi mahasiswa.</p>
    </div>
    <button onclick="window.print()" class="btn btn-sm btn-light border fw-semibold px-3 py-2 rounded-3 shadow-sm">
        <i class="fa-solid fa-print me-2 text-secondary"></i>Cetak Jadwal
    </button>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white" style="border-left: 4px solid #245358 !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-bold">Total Pertemuan / Minggu</span>
                    <h3 class="fw-bold text-dark mb-0 mt-1"><?= $total_jadwal; ?> <span class="fs-6 fw-normal text-muted">Kelas</span></h3>
                </div>
                <div class="p-3 bg-light text-success rounded-4"><i class="fa-solid fa-calendar-check fa-lg" style="color: #245358;"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white" style="border-left: 4px solid #ffc107 !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-bold">Beban SKS Diajar</span>
                    <h3 class="fw-bold text-dark mb-0 mt-1"><?= $total_sks; ?> <span class="fs-6 fw-normal text-muted">SKS</span></h3>
                </div>
                <div class="p-3 bg-light text-warning rounded-4"><i class="fa-solid fa-book-bookmark fa-lg"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white" style="border-left: 4px solid #0d6efd !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-bold">Tahun Akademik</span>
                    <h3 class="fw-bold text-dark mb-0 mt-1" style="font-size: 20px;">2026 / Ganjil</h3>
                </div>
                <div class="p-3 bg-light text-primary rounded-4"><i class="fa-solid fa-graduation-cap fa-lg"></i></div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
        <h6 class="fw-bold text-dark mb-0"><i class="fa-solid fa-list me-2 text-secondary"></i>Agenda Mingguan Anda</h6>
        <span class="badge bg-light text-dark border font-monospace small px-2 py-1">REAL-TIME DB CONNECTED</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="min-width: 800px;">
                <thead class="bg-light text-secondary small text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">
                    <tr>
                        <th class="py-3 ps-4" width="15%">Waktu & Hari</th>
                        <th width="35%">Mata Kuliah</th>
                        <th width="12%">Kelas</th>
                        <th width="15%">Ruangan</th>
                        <th width="15%" class="text-center">Aksi Manajemen</th>
                    </tr>
                </thead>
                <tbody style="font-size: 14px;">
                    <?php if ($total_jadwal > 0): ?>
                        <?php foreach ($list_jadwal as $row): ?>
                            <tr>
                                <td class="py-3 ps-4">
                                    <span class="d-block fw-bold text-dark mb-1"><?= htmlspecialchars($row['hari']); ?></span>
                                    <span class="text-muted font-monospace small"><i class="fa-regular fa-clock me-1"></i><?= date('H:i', strtotime($row['jam_mulai'])) . ' - ' . date('H:i', strtotime($row['jam_selesai'])); ?></span>
                                </td>
                                <td>
                                    <span class="d-block fw-semibold text-dark mb-0"><?= htmlspecialchars($row['nama_mk']); ?></span>
                                    <span class="text-muted font-monospace" style="font-size: 12px;"><?= htmlspecialchars($row['kode_mk']); ?> &bull; <?= htmlspecialchars($row['sks']); ?> SKS</span>
                                </td>
                                <td>
                                    <span class="badge rounded-pill px-3 py-2 border text-dark bg-light fw-bold"><?= htmlspecialchars($row['nama_kelas']); ?></span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center text-dark">
                                        <i class="fa-solid fa-door-open me-2 text-secondary small"></i>
                                        <span><?= htmlspecialchars($row['ruang'] ?? 'R. Teori'); ?></span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <a href="presensi.php?id_jadwal=<?= $row['id_jadwal']; ?>" class="btn btn-sm btn-success px-3 rounded-3 fw-medium shadow-sm" style="background-color: #245358; border-color: #245358;">
                                        <i class="fa-solid fa-clipboard-user me-1"></i> Absensi
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <img src="https://cdn-icons-png.flaticon.com/512/7486/7486744.png" style="width: 60px; opacity: 0.3;" class="mb-3" alt="No Data">
                                <h6 class="text-muted mb-1">Tidak ada data mengajar terdaftar</h6>
                                <p class="text-muted small mb-0">Pastikan relasi data pada tabel `jadwal` terhubung dengan NIDN Anda.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>