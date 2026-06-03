```php
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../templates/header.php';
require_once __DIR__ . '/../templates/sidebar.php';
require_once __DIR__ . '/../../config/database.php';

$id_user_mhs = $_SESSION['id_user'] ?? 0;

$msg_status = '';
$msg_text = '';

/*
|--------------------------------------------------------------------------
| Ambil Data Mahasiswa Login
|--------------------------------------------------------------------------
*/
$stmtMhs = $pdo->prepare("
    SELECT *
    FROM mahasiswa
    WHERE id_user = ?
    LIMIT 1
");

$stmtMhs->execute([$id_user_mhs]);

$mahasiswa = $stmtMhs->fetch(PDO::FETCH_ASSOC);

$id_mahasiswa = $mahasiswa['id_mahasiswa'] ?? 0;

/*
|--------------------------------------------------------------------------
| Proses Absensi
|--------------------------------------------------------------------------
*/
if(isset($_POST['btn_absen_mandiri'])){

    $id_jadwal = (int)$_POST['id_jadwal'];

    try{

        $cek = $pdo->prepare("
            SELECT *
            FROM presensi
            WHERE id_mahasiswa=?
            AND id_jadwal=?
            AND tanggal=CURDATE()
        ");

        $cek->execute([
            $id_mahasiswa,
            $id_jadwal
        ]);

        if($cek->rowCount()>0){

            $msg_status = 'warning';
            $msg_text = 'Anda sudah melakukan absensi hari ini.';

        }else{

            $insert = $pdo->prepare("
                INSERT INTO presensi
                (
                    id_jadwal,
                    id_mahasiswa,
                    pertemuan_ke,
                    tanggal,
                    status_hadir
                )
                VALUES
                (
                    ?,
                    ?,
                    1,
                    CURDATE(),
                    'Hadir'
                )
            ");

            $insert->execute([
                $id_jadwal,
                $id_mahasiswa
            ]);

            $msg_status = 'success';
            $msg_text = 'Absensi berhasil disimpan.';
        }

    }catch(Exception $e){

        $msg_status = 'danger';
        $msg_text = $e->getMessage();

    }
}

/*
|--------------------------------------------------------------------------
| Data Presensi
|--------------------------------------------------------------------------
*/
$stmtPresensi = $pdo->prepare("
    SELECT id_jadwal
    FROM presensi
    WHERE id_mahasiswa=?
");

$stmtPresensi->execute([$id_mahasiswa]);

$dataPresensi = $stmtPresensi->fetchAll(PDO::FETCH_COLUMN);

/*
|--------------------------------------------------------------------------
| Jadwal Mahasiswa
|--------------------------------------------------------------------------
*/
$query = "
SELECT
    j.*,
    mk.nama_mk,
    mk.kode_mk,
    mk.sks,
    d.nama_dosen,
    kls.nama_kelas
FROM krs k
JOIN jadwal j
    ON k.id_jadwal=j.id_jadwal
JOIN mata_kuliah mk
    ON j.id_mk=mk.id_mk
LEFT JOIN dosen d
    ON j.id_dosen=d.id_dosen
LEFT JOIN kelas kls
    ON j.id_kelas=kls.id_kelas
WHERE k.id_user=?
AND k.status_validasi='Disetujui'
ORDER BY
FIELD(
j.hari,
'Senin',
'Selasa',
'Rabu',
'Kamis',
'Jumat',
'Sabtu'
),
j.jam_mulai
";

$stmt = $pdo->prepare($query);
$stmt->execute([$id_user_mhs]);

$my_schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid px-4 py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold" style="color:#245358;">
                Jadwal Kuliah & Absensi
            </h4>
            <small class="text-muted">
                Daftar jadwal kuliah yang telah disetujui.
            </small>
        </div>
    </div>

    <?php if($msg_text): ?>

        <div class="alert alert-<?= $msg_status ?>">
            <?= $msg_text ?>
        </div>

    <?php endif; ?>

    <div class="card border-0 shadow-sm">

        <div class="card-body p-0">

            <div class="table-responsive">

                <table class="table table-hover align-middle mb-0">

                    <thead class="table-light">

                    <tr>
                        <th>Hari & Jam</th>
                        <th>Mata Kuliah</th>
                        <th>Dosen</th>
                        <th>Kelas</th>
                        <th>Ruangan</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>

                    </thead>

                    <tbody>

                    <?php if($my_schedules): ?>

                        <?php foreach($my_schedules as $sch): ?>

                            <tr>

                                <td>
                                    <strong>
                                        <?= htmlspecialchars($sch['hari']) ?>
                                    </strong>
                                    <br>

                                    <small class="text-muted">
                                        <?= $sch['jam_mulai'] ?>
                                        -
                                        <?= $sch['jam_selesai'] ?>
                                    </small>
                                </td>

                                <td>
                                    <strong>
                                        <?= htmlspecialchars($sch['nama_mk']) ?>
                                    </strong>

                                    <br>

                                    <small class="text-muted">
                                        <?= htmlspecialchars($sch['kode_mk']) ?>
                                        (<?= $sch['sks'] ?> SKS)
                                    </small>
                                </td>

                                <td>
                                    <?= htmlspecialchars($sch['nama_dosen'] ?? '-') ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($sch['nama_kelas'] ?? '-') ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($sch['ruangan']) ?>
                                </td>

                                <td>

                                    <?php if(in_array($sch['id_jadwal'],$dataPresensi)): ?>

                                        <span class="badge bg-success">
                                            Hadir
                                        </span>

                                    <?php else: ?>

                                        <span class="badge bg-danger">
                                            Belum Absen
                                        </span>

                                    <?php endif; ?>

                                </td>

                                <td class="text-end">

                                    <?php if(!in_array($sch['id_jadwal'],$dataPresensi)): ?>

                                        <form method="POST">

                                            <input
                                                type="hidden"
                                                name="id_jadwal"
                                                value="<?= $sch['id_jadwal']; ?>"
                                            >

                                            <button
                                                type="submit"
                                                name="btn_absen_mandiri"
                                                class="btn btn-sm text-white"
                                                style="background:#245358;"
                                            >
                                                Absen
                                            </button>

                                        </form>

                                    <?php else: ?>

                                        <button
                                            class="btn btn-success btn-sm"
                                            disabled
                                        >
                                            Sudah Hadir
                                        </button>

                                    <?php endif; ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="7" class="text-center py-5">
                                Belum ada jadwal yang disetujui.
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
