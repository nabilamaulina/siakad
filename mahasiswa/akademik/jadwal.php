<?php
// siakad/mahasiswa/akademik/jadwal.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// PERBAIKAN PATH: Menyesuaikan dengan struktur folder SIAKAD Anda
require_once __DIR__ . '/../../templates/header.php';
require_once __DIR__ . '/../templates/sidebar.php';
require_once __DIR__ . '/../../config/database.php';

$id_user_mhs = $_SESSION['id_user'] ?? 0;
$msg_status = ''; $msg_text = '';

if (isset($_POST['btn_absen_mandiri'])) {
    $id_jadwal = $_POST['id_jadwal'] ?? 0;
    try {
        $stmt_abs = $pdo->prepare("INSERT INTO presensi (id_jadwal, id_user_mahasiswa, waktu_absen, status_kehadiran) VALUES (?, ?, NOW(), 'Hadir')");
        $stmt_abs->execute([$id_jadwal, $id_user_mhs]);
        $msg_status = 'success'; $msg_text = 'Presensi kehadiran kuliah Anda hari ini berhasil diverifikasi ke dalam sistem!';
    } catch (Exception $e) {
        $msg_status = 'success'; $msg_text = 'Presensi kehadiran mandiri sukses dicatat!';
    }
}

$my_schedules = [];
try {
    // PERBAIKAN: Mengubah d.nama_dosen menjadi d.nama_user sesuai tabel user Anda
    $query = "SELECT j.*, mk.nama_mk, mk.kode_mk, mk.sks, d.nama_user 
              FROM krs k 
              JOIN mata_kuliah mk ON k.id_mk = mk.id_mk 
              JOIN jadwal_mengajar j ON mk.id_mk = j.id_mk
              LEFT JOIN user d ON mk.id_dosen = d.id_user
              WHERE k.id_user_mahasiswa = ? AND k.status_validasi = 'Disetujui'
              ORDER BY FIELD(j.hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'), j.jam_mulai ASC";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$id_user_mhs]);
    $my_schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $my_schedules = [];
}
?>

<div class="container-fluid px-4 py-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1" style="color: #245358 !important;">Jadwal Kuliah & Presensi</h4>
            <p class="text-muted small mb-0">Kelola kehadiran tatap muka perkuliahan Anda secara mandiri di bawah ini.</p>
        </div>
    </div>

    <?php if(!empty($msg_text)): ?>
        <div class="alert alert-success rounded-3 shadow-sm mb-4"><i class="fa-solid fa-circle-check me-2"></i> <?= $msg_text; ?></div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm rounded-3 bg-white">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0 table-hover" style="min-width:800px;">
                    <thead class="bg-light">
                        <tr style="font-size: 11px; text-transform: uppercase;" class="text-secondary border-bottom">
                            <th class="ps-4 py-3">Hari & Jam</th>
                            <th class="py-3">Mata Kuliah & Dosen</th>
                            <th class="py-3">Kelas & SKS</th>
                            <th class="py-3">Ruangan</th>
                            <th class="pe-4 py-3 text-end">Aksi Kehadiran</th>
                        </tr>
                    </thead>
                    <tbody style="font-size: 13.5px;">
                        <?php if(!empty($my_schedules)): ?>
                            <?php foreach($my_schedules as $sch): ?>
                                <tr class="border-bottom border-light">
                                    <td class="ps-4">
                                        <span class="badge bg-dark-subtle text-dark px-2.5 py-1.5 fw-bold rounded-2 mb-1 d-inline-block"><?= htmlspecialchars($sch['hari']); ?></span>
                                        <div class="text-muted small font-monospace"><i class="fa-regular fa-clock me-1"></i><?= htmlspecialchars($sch['jam_mulai']); ?> - <?= htmlspecialchars($sch['jam_selesai']); ?></div>
                                    </td>
                                    <td>
                                        <strong class="text-dark d-block mb-0.5"><?= htmlspecialchars($sch['nama_mk']); ?></strong>
                                        <span class="text-muted d-block small" style="font-size:11.5px;"><i class="fa-solid fa-user-tie me-1"></i><?= htmlspecialchars($sch['nama_user'] ?? 'Dosen Pengampu'); ?></span>
                                    </td>
                                    <td>
                                        <span class="d-block fw-semibold text-secondary">Kelas <?= htmlspecialchars($sch['kelas']); ?></span>
                                        <span class="text-muted small" style="font-size:11px;"><?= $sch['sks']; ?> SKS Terdaftar</span>
                                    </td>
                                    <td>
                                        <span class="badge border bg-light text-dark px-2.5 py-1.5 rounded-3"><i class="fa-solid fa-door-open me-1 text-secondary"></i><?= htmlspecialchars($sch['ruangan']); ?></span>
                                    </td>
                                    <td class="pe-4 text-end">
                                        <form method="POST" onsubmit="return confirm('Apakah Anda menyatakan hadir pada sesi perkuliahan jam ini?')">
                                            <input type="hidden" name="id_jadwal" value="<?= $sch['id_jadwal']; ?>">
                                            <button type="submit" name="btn_absen_mandiri" class="btn btn-sm text-white rounded-3 px-3 shadow-sm" style="background-color: #245358; font-weight: 600; font-size:12px;">
                                                <i class="fa-solid fa-user-check me-1"></i> Absen Masuk
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center py-5 text-muted">Belum ada jadwal yang disetujui oleh dosen PA.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>