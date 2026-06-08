<?php
// File: siakad/mahasiswa/akademik/riwayat_absensi.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Memuat layout header, sidebar, dan koneksi database
require_once __DIR__ . '/../templates/header.php'; 
require_once __DIR__ . '/../templates/sidebar.php'; 
require_once __DIR__ . '/../../config/database.php'; 

// Proteksi halaman: Ambil id_user mahasiswa dari session login
$id_user_mahasiswa = $_SESSION['id_user'] ?? 0;
$list_absensi = [];

if ($id_user_mahasiswa > 0) {
    try {
        // A. Ambil id_mahasiswa berdasarkan id_user dari session
        $stmt_mhs = $pdo->prepare("SELECT id_mahasiswa FROM mahasiswa WHERE id_user = ? LIMIT 1");
        $stmt_mhs->execute([$id_user_mahasiswa]);
        $data_mhs = $stmt_mhs->fetch(PDO::FETCH_ASSOC);
        $id_mahasiswa = $data_mhs['id_mahasiswa'] ?? 0;

        // B. Jalankan Query Riwayat Absensi Riil milik Mahasiswa tersebut
        if ($id_mahasiswa > 0) {
            $query = "
                SELECT 
                    mk.kode_mk, 
                    mk.nama_mk, 
                    j.hari, 
                    a.pertemuan_ke, 
                    DATE_FORMAT(a.tanggal, '%d-%m-%Y') AS tanggal_absen, 
                    a.status
                FROM absensi a
                JOIN jadwal j ON a.id_jadwal = j.id_jadwal
                JOIN mata_kuliah mk ON j.id_mk = mk.id_mk
                WHERE a.id_mahasiswa = ?
                ORDER BY a.tanggal DESC, a.pertemuan_ke DESC
            ";
            
            $stmt_absensi = $pdo->prepare($query);
            $stmt_absensi->execute([$id_mahasiswa]);
            $list_absensi = $stmt_absensi->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        $list_absensi = [];
    }
}
?>

<div class="mb-4">
    <h4 class="fw-bold text-dark mb-1">Riwayat Absensi</h4>
    <p class="text-muted small mb-0">Pantau rekam jejak log historis kehadiran mata kuliah Anda sepanjang semester.</p>
</div>

<div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
    <div class="card-header bg-white py-3 border-bottom">
        <h6 class="fw-bold text-dark mb-0">
            <i class="fa-solid fa-clock-rotate-left text-secondary me-2"></i>Log Historis Riwayat Absensi Anda
        </h6>
    </div>
    
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light text-secondary fw-semibold">
                    <tr>
                        <th style="width: 60px;" class="text-center py-3">No</th>
                        <th style="width: 110px;">Kode MK</th>
                        <th>Mata Kuliah</th>
                        <th style="width: 100px;">Hari</th>
                        <th style="width: 140px;">Pertemuan</th>
                        <th style="width: 140px;">Tanggal Absen</th>
                        <th style="width: 120px;" class="text-center">Status Log</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($list_absensi) > 0): ?>
                        <?php $no = 1; foreach ($list_absensi as $row): ?>
                            <tr>
                                <td class="text-center text-muted"><?= $no++; ?></td>
                                <td class="font-monospace text-secondary fw-medium"><?= htmlspecialchars($row['kode_mk']); ?></td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($row['nama_mk']); ?></td>
                                <td class="text-capitalize"><?= htmlspecialchars($row['hari']); ?></td>
                                <td class="text-muted fw-medium">Pertemuan ke-<?= htmlspecialchars($row['pertemuan_ke']); ?></td>
                                <td class="text-secondary"><?= htmlspecialchars($row['tanggal_absen']); ?></td>
                                <td class="text-center">
                                    <?php 
                                    $status = strtolower($row['status']);
                                    if ($status === 'hadir'): ?>
                                        <span class="badge bg-success rounded-pill px-3 py-1 fw-semibold">Hadir</span>
                                    <?php elseif ($status === 'sakit'): ?>
                                        <span class="badge bg-info text-white rounded-pill px-3 py-1 fw-semibold">Sakit</span>
                                    <?php elseif ($status === 'izin'): ?>
                                        <span class="badge bg-warning text-dark rounded-pill px-3 py-1 fw-semibold">Izin</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger rounded-pill px-3 py-1 fw-semibold">Alpa</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 bg-light-subtle">
                                <div class="py-4">
                                    <i class="fa-solid fa-folder-open text-muted fs-1 mb-3 opacity-50"></i>
                                    <h6 class="text-muted fw-bold mb-1">Belum Ada Rekam Kehadiran</h6>
                                    <p class="text-muted small mb-0">Sistem belum menemukan riwayat absensi atau dosen belum mengunci log kelas Anda.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="flex-grow-1"></div>

<?php 
// Memanggil template footer global (tag </div> penutup halaman diatur di dalam file ini)
require_once __DIR__ . '/../templates/footer.php'; 
?>