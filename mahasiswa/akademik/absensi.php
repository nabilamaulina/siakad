```php
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../templates/header.php';
require_once __DIR__ . '/../templates/sidebar.php';
require_once __DIR__ . '/../../config/database.php';

$id_user = $_SESSION['id_user'] ?? 0;

/*
|--------------------------------------------------------------------------
| Ambil ID Mahasiswa
|--------------------------------------------------------------------------
*/
$stmtMhs = $pdo->prepare("
    SELECT id_mahasiswa
    FROM mahasiswa
    WHERE id_user=?
");

$stmtMhs->execute([$id_user]);

$mhs = $stmtMhs->fetch(PDO::FETCH_ASSOC);

$id_mahasiswa = $mhs['id_mahasiswa'] ?? 0;

/*
|--------------------------------------------------------------------------
| Ambil Riwayat Absensi
|--------------------------------------------------------------------------
*/
$query = "
SELECT
    p.*,
    j.hari,
    mk.nama_mk,
    mk.kode_mk
FROM presensi p
JOIN jadwal j
    ON p.id_jadwal = j.id_jadwal
JOIN mata_kuliah mk
    ON j.id_mk = mk.id_mk
WHERE p.id_mahasiswa = ?
ORDER BY p.tanggal DESC
";

$stmt = $pdo->prepare($query);
$stmt->execute([$id_mahasiswa]);

$data_absensi = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid px-4 py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold" style="color:#245358;">
                Riwayat Absensi
            </h4>
            <small class="text-muted">
                Daftar kehadiran perkuliahan mahasiswa.
            </small>
        </div>
    </div>

    <div class="card border-0 shadow-sm">

        <div class="card-body p-0">

            <div class="table-responsive">

                <table class="table table-hover align-middle mb-0">

                    <thead class="table-light">

                    <tr>
                        <th>No</th>
                        <th>Kode MK</th>
                        <th>Mata Kuliah</th>
                        <th>Hari</th>
                        <th>Pertemuan</th>
                        <th>Tanggal</th>
                        <th>Status</th>
                    </tr>

                    </thead>

                    <tbody>

                    <?php if(!empty($data_absensi)): ?>

                        <?php $no = 1; ?>

                        <?php foreach($data_absensi as $row): ?>

                            <tr>

                                <td><?= $no++ ?></td>

                                <td>
                                    <?= htmlspecialchars($row['kode_mk']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($row['nama_mk']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($row['hari']) ?>
                                </td>

                                <td>
                                    <?= $row['pertemuan_ke'] ?>
                                </td>

                                <td>
                                    <?= date('d-m-Y', strtotime($row['tanggal'])) ?>
                                </td>

                                <td>

                                    <?php
                                    $badge = 'secondary';

                                    if($row['status_hadir'] == 'Hadir'){
                                        $badge = 'success';
                                    }elseif($row['status_hadir'] == 'Izin'){
                                        $badge = 'warning';
                                    }elseif($row['status_hadir'] == 'Sakit'){
                                        $badge = 'info';
                                    }elseif($row['status_hadir'] == 'Alfa'){
                                        $badge = 'danger';
                                    }
                                    ?>

                                    <span class="badge bg-<?= $badge ?>">
                                        <?= $row['status_hadir'] ?>
                                    </span>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="7" class="text-center py-5">
                                Belum ada data absensi.
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
```
