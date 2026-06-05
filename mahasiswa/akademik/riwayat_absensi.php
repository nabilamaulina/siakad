<?php
// siakad/mahasiswa/akademik/riwayat_absensi.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../templates/header.php';
require_once __DIR__ . '/../templates/sidebar.php';
require_once __DIR__ . '/../../config/database.php';

$id_user_mhs = $_SESSION['id_user'] ?? 0;

/*
|--------------------------------------------------------------------------
| Ambil Identitas Mahasiswa Berdasarkan Akun Login
|--------------------------------------------------------------------------
*/
$stmtMhs = $pdo->prepare("SELECT id_mahasiswa FROM mahasiswa WHERE id_user = ? LIMIT 1");
$stmtMhs->execute([$id_user_mhs]);
$mahasiswa = $stmtMhs->fetch(PDO::FETCH_ASSOC);
$id_mahasiswa = $mahasiswa['id_mahasiswa'] ?? 0;

/*
|--------------------------------------------------------------------------
| Ambil Semua Rekam Jejak Log Historis Absensi
|--------------------------------------------------------------------------
*/
$queryRiwayat = "
SELECT p.*, j.hari, mk.nama_mk, mk.kode_mk
FROM presensi p
JOIN jadwal j ON p.id_jadwal = j.id_jadwal
JOIN mata_kuliah mk ON j.id_mk = mk.id_mk
WHERE p.id_mahasiswa = ?
ORDER BY p.tanggal DESC, p.pertemuan_ke DESC
";
$stmtRiwayat = $pdo->prepare($queryRiwayat);
$stmtRiwayat->execute([$id_mahasiswa]);
$data_absensi = $stmtRiwayat->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid px-4 py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold" style="color:#245358;">Riwayat Absensi</h4>
            <small class="text-muted">Pantau rekam jejak log historis kehadiran mata kuliah Anda sepanjang semester.</small>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-0">
            <h5 class="fw-bold mb-0 text-dark" style="font-size: 1.1rem;">
                <i class="fa-solid fa-clock-history me-2" style="color: #245358;"></i> Log Historis Riwayat Absensi Anda
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">No</th>
                            <th>Kode MK</th>
                            <th>Mata Kuliah</th>
                            <th>Hari</th>
                            <th>Pertemuan</th>
                            <th>Tanggal Absen</th>
                            <th class="pe-4">Status Log</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($data_absensi)): ?>
                            <?php $no = 1; ?>
                            <?php foreach($data_absensi as $row): ?>
                                <tr>
                                    <td class="ps-4"><?= $no++ ?></td>
                                    <td><?= htmlspecialchars($row['kode_mk']) ?></td>
                                    <td class="fw-bold"><?= htmlspecialchars($row['nama_mk']) ?></td>
                                    <td><?= htmlspecialchars($row['hari']) ?></td>
                                    <td><span class="text-secondary small fw-bold">Pertemuan ke-<?= $row['pertemuan_ke'] ?></span></td>
                                    <td><?= date('d-m-Y', strtotime($row['tanggal'])) ?></td>
                                    <td class="pe-4">
                                        <?php
                                        $badge = 'secondary';
                                        if($row['status_hadir'] == 'Hadir') $badge = 'success';
                                        elseif($row['status_hadir'] == 'Izin') $badge = 'warning';
                                        elseif($row['status_hadir'] == 'Sakit') $badge = 'info';
                                        elseif($row['status_hadir'] == 'Alfa') $badge = 'danger';
                                        ?>
                                        <span class="badge bg-<?= $badge ?>"><?= $row['status_hadir'] ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">Belum ada rekam jejak absensi perkuliahan pada akun Anda.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>