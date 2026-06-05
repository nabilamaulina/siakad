<?php
// siakad/mahasiswa/akademik/absensi.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../templates/header.php';
require_once __DIR__ . '/../templates/sidebar.php';
require_once __DIR__ . '/../../config/database.php';

// 1. Set Zona Waktu Indonesia (WIB) dan Ambil Waktu Sistem Saat Ini
date_default_timezone_set('Asia/Jakarta');
$hari_indo = [
    'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 
    'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
];
$hari_sekarang = $hari_indo[date('l')];
$jam_sekarang  = date('H:i:s');

$id_user_mhs = $_SESSION['id_user'] ?? 0;
$msg_status = '';
$msg_text = '';

/*
|--------------------------------------------------------------------------
| 2. Ambil Data Mahasiswa Login
|--------------------------------------------------------------------------
*/
$stmtMhs = $pdo->prepare("SELECT * FROM mahasiswa WHERE id_user = ? LIMIT 1");
$stmtMhs->execute([$id_user_mhs]);
$mahasiswa = $stmtMhs->fetch(PDO::FETCH_ASSOC);
$id_mahasiswa = $mahasiswa['id_mahasiswa'] ?? 0;

/*
|--------------------------------------------------------------------------
| 3. Proses Aksi Kirim Absensi Mandiri (POST) + Validasi Hari & Jam
|--------------------------------------------------------------------------
*/
if (isset($_POST['btn_absen_mandiri'])) {
    $id_jadwal = (int)$_POST['id_jadwal'];

    try {
        $stmtCekJadwal = $pdo->prepare("SELECT hari, jam_mulai, jam_selesai FROM jadwal WHERE id_jadwal = ?");
        $stmtCekJadwal->execute([$id_jadwal]);
        $detil_jadwal = $stmtCekJadwal->fetch(PDO::FETCH_ASSOC);

        if (!$detil_jadwal) {
            throw new Exception("Jadwal kuliah tidak ditemukan.");
        }

        $is_hari_valid = (strcasecmp($hari_sekarang, trim($detil_jadwal['hari'])) === 0);
        $is_jam_valid  = ($jam_sekarang >= $detil_jadwal['jam_mulai'] && $jam_sekarang <= $detil_jadwal['jam_selesai']);

        if (!$is_hari_valid || !$is_jam_valid) {
            $msg_status = 'danger';
            $msg_text = "Gagal Absen! Kelas ini hanya dibuka pada hari " . $detil_jadwal['hari'] . " pukul " . date('H:i', strtotime($detil_jadwal['jam_mulai'])) . " - " . date('H:i', strtotime($detil_jadwal['jam_selesai'])) . " WIB.";
        } else {
            $cek = $pdo->prepare("SELECT * FROM presensi WHERE id_mahasiswa = ? AND id_jadwal = ? AND tanggal = CURDATE()");
            $cek->execute([$id_mahasiswa, $id_jadwal]);

            if ($cek->rowCount() > 0) {
                $msg_status = 'warning';
                $msg_text = 'Anda sudah melakukan absensi untuk mata kuliah ini hari ini.';
            } else {
                $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM presensi WHERE id_jadwal = ? AND id_mahasiswa = ?");
                $stmtCount->execute([$id_jadwal, $id_mahasiswa]);
                $pertemuan_ke = ((int)$stmtCount->fetchColumn()) + 1;

                $insert = $pdo->prepare("
                    INSERT INTO presensi (id_jadwal, id_mahasiswa, pertemuan_ke, tanggal, status_hadir)
                    VALUES (?, ?, ?, CURDATE(), 'Hadir')
                ");
                $insert->execute([$id_jadwal, $id_mahasiswa, $pertemuan_ke]);

                $msg_status = 'success';
                $msg_text = 'Absensi berhasil disimpan. Selamat belajar!';
            }
        }
    } catch (Exception $e) {
        $msg_status = 'danger';
        $msg_text = $e->getMessage();
    }
}

/*
|--------------------------------------------------------------------------
| 4. Data Presensi Hari Ini
|--------------------------------------------------------------------------
*/
$stmtPresensiHariIni = $pdo->prepare("SELECT id_jadwal FROM presensi WHERE id_mahasiswa = ? AND tanggal = CURDATE()");
$stmtPresensiHariIni->execute([$id_mahasiswa]);
$dataPresensiHariIni = $stmtPresensiHariIni->fetchAll(PDO::FETCH_COLUMN);

/*
|--------------------------------------------------------------------------
| 5. Ambil Jadwal Kuliah KRS Mahasiswa
|--------------------------------------------------------------------------
*/
$queryJadwal = "
SELECT j.*, mk.nama_mk, mk.kode_mk, mk.sks, d.nama_dosen, kls.nama_kelas
FROM krs k
JOIN jadwal j ON k.id_jadwal = j.id_jadwal
JOIN mata_kuliah mk ON j.id_mk = mk.id_mk
LEFT JOIN dosen d ON j.id_dosen = d.id_dosen
LEFT JOIN kelas kls ON j.id_kelas = kls.id_kelas
WHERE k.id_user = ? AND k.status_validasi = 'Disetujui'
ORDER BY FIELD(j.hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'), j.jam_mulai
";
$stmtJadwal = $pdo->prepare($queryJadwal);
$stmtJadwal->execute([$id_user_mhs]);
$my_schedules = $stmtJadwal->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold" style="color:#245358;">Menu Utama Absensi</h4>
            <small class="text-muted">Lakukan absensi mandiri sesuai jadwal aktif kelas Anda.</small>
        </div>
        <div class="text-end">
            <span class="badge bg-light text-dark border p-2 shadow-sm small">
                <i class="fa-solid fa-clock text-secondary me-1"></i> Waktu Server: <strong><?= $hari_sekarang ?>, <?= date('d/m/Y H:i') ?> WIB</strong>
            </span>
        </div>
    </div>

    <?php if($msg_text): ?>
        <div class="alert alert-<?= $msg_status ?> alert-dismissible fade show shadow-sm" role="alert">
            <?= $msg_text ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm mb-5">
        <div class="card-header bg-white py-3 border-0">
            <h5 class="fw-bold mb-0 text-dark" style="font-size: 1.1rem;">
                <i class="fa-solid fa-calendar-day me-2" style="color: #245358;"></i> Jadwal Kuliah & Absensi Hari Ini / Minggu Ini
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Hari & Jam</th>
                            <th>Mata Kuliah</th>
                            <th>Dosen</th>
                            <th>Kelas</th>
                            <th>Ruangan</th>
                            <th>Status Hari Ini</th>
                            <th class="text-center" style="width: 140px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($my_schedules): ?>
                            <?php foreach($my_schedules as $sch): ?>
                                <?php 
                                    $is_hari_ini = (strcasecmp($hari_sekarang, trim($sch['hari'])) === 0);
                                    $is_jam_masuk = ($jam_sekarang >= $sch['jam_mulai'] && $jam_sekarang <= $sch['jam_selesai']);
                                    $tombol_absen_dibuka = ($is_hari_ini && $is_jam_masuk);
                                    $sudah_absen_hari_ini = in_array($sch['id_jadwal'], $dataPresensiHariIni);
                                ?>
                                <tr>
                                    <td class="ps-4">
                                        <strong><?= htmlspecialchars($sch['hari']) ?></strong><br>
                                        <small class="text-muted"><?= date('H:i', strtotime($sch['jam_mulai'])) ?> - <?= date('H:i', strtotime($sch['jam_selesai'])) ?></small>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($sch['nama_mk']) ?></strong><br>
                                        <small class="text-muted"><?= htmlspecialchars($sch['kode_mk']) ?> (<?= $sch['sks'] ?> SKS)</small>
                                    </td>
                                    <td><small><?= htmlspecialchars($sch['nama_dosen'] ?? '-') ?></small></td>
                                    <td><?= htmlspecialchars($sch['nama_kelas'] ?? '-') ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($sch['ruangan']) ?></span></td>
                                    <td>
                                        <?php if($sudah_absen_hari_ini): ?>
                                            <span class="badge bg-success"><i class="fa-solid fa-circle-check me-1"></i> Hadir</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger"><i class="fa-solid fa-circle-xmark me-1"></i> Belum Absen</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center pe-3">
                                        <?php if($sudah_absen_hari_ini): ?>
                                            <button class="btn btn-sm btn-light text-success border w-100 fw-bold" disabled>
                                                <i class="fa-solid fa-check me-1"></i> Selesai
                                            </button>
                                        <?php elseif($tombol_absen_dibuka): ?>
                                            <form method="POST">
                                                <input type="hidden" name="id_jadwal" value="<?= $sch['id_jadwal']; ?>">
                                                <button type="submit" name="btn_absen_mandiri" class="btn btn-sm text-white w-100 fw-bold shadow-sm" style="background:#245358;">
                                                    Absen Now
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-light text-muted border w-100 small" style="cursor: not-allowed;" title="Absen hanya bisa diklik saat jam pelajaran berlangsung" disabled>
                                                <i class="fa-solid fa-lock me-1" style="font-size: 0.75rem;"></i> Terkunci
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">Belum ada daftar KRS mata kuliah yang disetujui.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>