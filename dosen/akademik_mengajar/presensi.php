<?php
// File: dosen/akademik_mengajar/presensi.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load template & koneksi database
require_once __DIR__ . '/../templates/header.php';
require_once __DIR__ . '/../templates/sidebar.php';
require_once __DIR__ . '/../../config/database.php';

// Set zona waktu agar sinkron dengan jam lokal komputer/server
date_default_timezone_set('Asia/Jakarta');

$id_jadwal = $_GET['id_jadwal'] ?? 0;
$id_user_dosen = $_SESSION['id_user'] ?? 0;

$pertemuan_ke_sekarang = 1; 
$sudah_absen_hari_ini = false;
$pesan_sukses = false;
$pesan_error_waktu = "";

try {
    // 1. Ambil detail data jadwal, kelas, dan nama mata kuliahnya
    $stmt_detail = $pdo->prepare("
        SELECT j.*, mk.nama_mk, mk.kode_mk, k.nama_kelas 
        FROM jadwal j
        JOIN mata_kuliah mk ON j.id_mk = mk.id_mk
        JOIN kelas k ON j.id_kelas = k.id_kelas
        JOIN dosen d ON j.id_dosen = d.id_dosen
        WHERE j.id_jadwal = ? AND d.id_user = ?
    ");
    $stmt_detail->execute([$id_jadwal, $id_user_dosen]);
    $jadwal = $stmt_detail->fetch(PDO::FETCH_ASSOC);

    if (!$jadwal) {
        echo "<div class='alert alert-danger rounded-4 m-3'><i class='fa-solid fa-circle-xmark me-2'></i>Jadwal tidak ditemukan atau Anda tidak berwenang di kelas ini.</div>";
        require_once __DIR__ . '/../templates/footer.php';
        exit;
    }

    // 2. Cek otomatis pertemuan ke berapa yang sedang berjalan di database
    $stmt_cek_pertemuan = $pdo->prepare("
        SELECT MAX(pertemuan_ke) as terakhir 
        FROM absensi 
        WHERE id_jadwal = ?
    ");
    $stmt_cek_pertemuan->execute([$id_jadwal]);
    $data_pertemuan = $stmt_cek_pertemuan->fetch(PDO::FETCH_ASSOC);
    
    if ($data_pertemuan['terakhir']) {
        $pertemuan_ke_sekarang = $data_pertemuan['terakhir'] + 1;
    }

    // 3. VALIDASI HARI: Cek apakah hari ini sudah pernah absen untuk kelas ini
    $stmt_cek_hari_ini = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM absensi 
        WHERE id_jadwal = ? AND tanggal = CURDATE()
    ");
    $stmt_cek_hari_ini->execute([$id_jadwal]);
    $cek_hari = $stmt_cek_hari_ini->fetch(PDO::FETCH_ASSOC);

    if ($cek_hari['total'] > 0) {
        $sudah_absen_hari_ini = true;
        $pertemuan_ke_sekarang = $data_pertemuan['terakhir'] ?? 1; // kunci tampilan pada pertemuan terakhir
    }

    // 4. Ambil daftar mahasiswa yang berada di kelas yang bersangkutan
    $stmt_mhs = $pdo->prepare("
        SELECT id_mahasiswa, nim, nama_mahasiswa 
        FROM mahasiswa 
        WHERE id_kelas = ? 
        ORDER BY nim ASC
    ");
    $stmt_mhs->execute([$jadwal['id_kelas']]);
    $list_mahasiswa = $stmt_mhs->fetchAll(PDO::FETCH_ASSOC);

    // 5. PROSES SIMPAN ABSENSI (Hanya dieksekusi jika ditekan & memenuhi syarat)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_absen'])) {
        
        $jam_sekarang = date('H:i:s');
        $jam_mulai_kuliah = $jadwal['jam_mulai'];
        $jam_selesai_kuliah = $jadwal['jam_selesai'];

        // CEK VALIDASI JAM PELAJARAN
        if ($jam_sekarang < $jam_mulai_kuliah || $jam_sekarang > $jam_selesai_kuliah) {
            $pesan_error_waktu = "Absensi ditolak! Pengisian hanya diizinkan saat jam pelajaran berlangsung (" . date('H:i', strtotime($jam_mulai_kuliah)) . " s/d " . date('H:i', strtotime($jam_selesai_kuliah)) . " WIB).";
        } 
        // CEK VALIDASI STATUS ABAILABILITAS HARIAN
        elseif ($sudah_absen_hari_ini) {
            $pesan_error_waktu = "Absensi ditolak! Kelas ini telah melakukan pengisian absen untuk hari ini.";
        }

        // JIKA LOLOS VALIDASI, PROSES MEMASUKKAN DATA KE DATABASE
        if (empty($pesan_error_waktu)) {
            $pertemuan_ke = $_POST['pertemuan_ke'] ?? 1;
            $materi_pokok = $_POST['materi_pokok'] ?? '';
            $status_absen = $_POST['status'] ?? [];

            $kode_mk = $jadwal['kode_mk'];
            $nama_mk = $jadwal['nama_mk'];

            $pdo->beginTransaction();

            foreach ($list_mahasiswa as $mhs) {
                $id_mhs = $mhs['id_mahasiswa'];
                $status = $status_absen[$id_mhs] ?? 'Alfa';

                // Query menyimpan Kode MK dan Nama MK langsung ke tabel absensi
                $stmt_save = $pdo->prepare("
                    INSERT INTO absensi (id_jadwal, kode_mk, nama_mk, id_mahasiswa, pertemuan_ke, tanggal, status, materi_pertemuan)
                    VALUES (?, ?, ?, ?, ?, CURDATE(), ?, ?)
                ");
                $stmt_save->execute([$id_jadwal, $kode_mk, $nama_mk, $id_mhs, $pertemuan_ke, $status, $materi_pokok]);
            }

            $pdo->commit();
            $pesan_sukses = true;
            $sudah_absen_hari_ini = true; // Kunci form sesaat setelah berhasil submit
        }
    }

    // 6. Jika absensi terkunci (sudah absen), ambil history data dari DB untuk ditampilkan sebagai review
    $history_absen = [];
    if ($sudah_absen_hari_ini) {
        $stmt_histori = $pdo->prepare("
            SELECT id_mahasiswa, status, materi_pertemuan 
            FROM absensi 
            WHERE id_jadwal = ? AND pertemuan_ke = ?
        ");
        $stmt_histori->execute([$id_jadwal, $pertemuan_ke_sekarang]);
        $rows_histori = $stmt_histori->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows_histori as $h) {
            $history_absen[$h['id_mahasiswa']] = $h['status'];
            $materi_terkunci = $h['materi_pertemuan'];
        }
    }

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "<div class='alert alert-danger m-3'>Terjadi kesalahan sistem: " . htmlspecialchars($e->getMessage()) . "</div>";
    require_once __DIR__ . '/../templates/footer.php';
    exit;
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Isi Presensi Perkuliahan</h4>
        <p class="text-muted small mb-0">Kelola kehadiran mahasiswa secara real-time untuk kelas yang sedang berlangsung.</p>
    </div>
    <a href="jadwal.php" class="btn btn-sm btn-light border fw-semibold px-3 py-2 rounded-3 shadow-sm">
        <i class="fa-solid fa-arrow-left me-2"></i>Kembali ke Jadwal
    </a>
</div>

<?php if (!empty($pesan_error_waktu)): ?>
    <div class="alert alert-danger border-0 shadow-sm rounded-4 d-flex align-items-center p-3 mb-4">
        <i class="fa-solid fa-triangle-exclamation fa-lg me-3 text-danger"></i>
        <div>
            <strong class="d-block">Gagal Menyimpan Absensi!</strong>
            <span class="small text-dark"><?= $pesan_error_waktu; ?></span>
        </div>
    </div>
<?php endif; ?>

<?php if ($sudah_absen_hari_ini && !$pesan_sukses): ?>
    <div class="alert alert-warning border-0 shadow-sm rounded-4 d-flex align-items-center p-3 mb-4">
        <i class="fa-solid fa-lock fa-lg me-3 text-warning"></i>
        <div>
            <strong class="d-block">Sesi Absensi Terkunci!</strong>
            <span class="small text-muted">Anda sudah mengisi absensi kelas ini hari ini. Pengisian hanya diizinkan 1 kali selama jam sesi kuliah.</span>
        </div>
    </div>
<?php endif; ?>

<?php if ($pesan_sukses): ?>
    <div class="alert alert-success border-0 shadow-sm rounded-4 d-flex align-items-center p-3 mb-4">
        <i class="fa-solid fa-circle-check fa-lg me-3 text-success"></i>
        <div>
            <strong class="d-block">Presensi Berhasil Disimpan!</strong>
            <span class="small text-muted">Data kehadiran mahasiswa pertemuan hari ini serta info mata kuliah terkait telah sukses dikunci ke database.</span>
        </div>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 bg-white p-4">
            <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-circle-info me-2 text-secondary"></i>Informasi Kelas</h6>
            <hr class="mt-0 mb-3" style="opacity: 0.1;">
            
            <div class="mb-3">
                <label class="text-muted small text-uppercase fw-bold d-block">Mata Kuliah</label>
                <span class="fw-semibold text-dark"><?= htmlspecialchars($jadwal['nama_mk']); ?></span>
                <span class="d-block font-monospace text-muted small"><?= htmlspecialchars($jadwal['kode_mk']); ?></span>
            </div>
            
            <div class="row mb-3">
                <div class="col-6">
                    <label class="text-muted small text-uppercase fw-bold d-block">Kelas</label>
                    <span class="badge bg-light text-dark border fw-bold px-3 py-2 rounded-pill mt-1"><?= htmlspecialchars($jadwal['nama_kelas']); ?></span>
                </div>
                <div class="col-6">
                    <label class="text-muted small text-uppercase fw-bold d-block">Ruangan</label>
                    <span class="text-dark fw-medium mt-1 d-inline-block"><i class="fa-solid fa-door-open me-1 text-secondary"></i> <?= htmlspecialchars($jadwal['ruangan'] ?? 'R. Teori'); ?></span>
                </div>
            </div>

            <div class="mb-1">
                <label class="text-muted small text-uppercase fw-bold d-block">Waktu Sesi</label>
                <span class="text-dark font-monospace small"><i class="fa-regular fa-clock me-1 text-secondary"></i> <?= date('H:i', strtotime($jadwal['jam_mulai'])) . ' - ' . date('H:i', strtotime($jadwal['jam_selesai'])); ?> WIB</span>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <form method="POST" action="">
            <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-4">
                <div class="card-body p-4 bg-light border-bottom">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-dark">Pertemuan Ke-</label>
                            <input type="hidden" name="pertemuan_ke" value="<?= $pertemuan_ke_sekarang; ?>">
                            <input type="text" class="form-control rounded-3 bg-white shadow-sm fw-bold text-secondary" value="Pertemuan <?= $pertemuan_ke_sekarang; ?>" readonly>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-bold text-dark">Materi Pokok / Bahasan Perkuliahan</label>
                            <input type="text" name="materi_pokok" class="form-control bg-white rounded-3 shadow-sm" placeholder="Contoh: Pengenalan Arsitektur Komputer" value="<?= $materi_terkunci ?? ''; ?>" required <?= $sudah_absen_hari_ini ? 'readonly' : ''; ?>>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-secondary small text-uppercase" style="font-size: 11px;">
                            <tr>
                                <th class="py-3 ps-4" width="25%">NIM</th>
                                <th width="45%">Nama Mahasiswa</th>
                                <th width="30%" class="text-center">Status Kehadiran</th>
                            </tr>
                        </thead>
                        <tbody style="font-size: 14px;">
                            <?php if (count($list_mahasiswa) > 0): ?>
                                <?php foreach ($list_mahasiswa as $mhs): ?>
                                    <?php $status_aktif = $history_absen[$mhs['id_mahasiswa']] ?? 'Hadir'; ?>
                                    <tr>
                                        <td class="font-monospace ps-4 text-dark fw-medium"><?= htmlspecialchars($mhs['nim']); ?></td>
                                        <td class="fw-semibold text-dark"><?= htmlspecialchars($mhs['nama_mahasiswa']); ?></td>
                                        <td>
                                            <div class="d-flex justify-content-center gap-2">
                                                <input type="radio" class="btn-check" name="status[<?= $mhs['id_mahasiswa']; ?>]" id="H_<?= $mhs['id_mahasiswa']; ?>" value="Hadir" <?= $status_aktif == 'Hadir' ? 'checked' : ''; ?> <?= $sudah_absen_hari_ini ? 'disabled' : ''; ?>>
                                                <label class="btn btn-sm btn-outline-success rounded-pill px-3 py-1 font-monospace small" for="H_<?= $mhs['id_mahasiswa']; ?>">H</label>

                                                <input type="radio" class="btn-check" name="status[<?= $mhs['id_mahasiswa']; ?>]" id="S_<?= $mhs['id_mahasiswa']; ?>" value="Sakit" <?= $status_aktif == 'Sakit' ? 'checked' : ''; ?> <?= $sudah_absen_hari_ini ? 'disabled' : ''; ?>>
                                                <label class="btn btn-sm btn-outline-warning rounded-pill px-3 py-1 font-monospace small" for="S_<?= $mhs['id_mahasiswa']; ?>">S</label>

                                                <input type="radio" class="btn-check" name="status[<?= $mhs['id_mahasiswa']; ?>]" id="I_<?= $mhs['id_mahasiswa']; ?>" value="Izin" <?= $status_aktif == 'Izin' ? 'checked' : ''; ?> <?= $sudah_absen_hari_ini ? 'disabled' : ''; ?>>
                                                <label class="btn btn-sm btn-outline-info rounded-pill px-3 py-1 font-monospace small" for="I_<?= $mhs['id_mahasiswa']; ?>">I</label>

                                                <input type="radio" class="btn-check" name="status[<?= $mhs['id_mahasiswa']; ?>]" id="A_<?= $mhs['id_mahasiswa']; ?>" value="Alfa" <?= $status_aktif == 'Alfa' ? 'checked' : ''; ?> <?= $sudah_absen_hari_ini ? 'disabled' : ''; ?>>
                                                <label class="btn btn-sm btn-outline-danger rounded-pill px-3 py-1 font-monospace small" for="A_<?= $mhs['id_mahasiswa']; ?>">A</label>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center py-5 text-muted">
                                        <i class="fa-solid fa-users-slash fa-2x mb-2 opacity-50"></i>
                                        <p class="mb-0 small">Belum ada mahasiswa terdaftar di dalam kelas ini.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="card-footer bg-white py-3 border-top d-flex justify-content-end">
                    <?php if ($sudah_absen_hari_ini): ?>
                        <button type="button" class="btn btn-secondary px-4 rounded-3 fw-bold shadow-sm disabled" style="cursor: not-allowed;">
                            <i class="fa-solid fa-lock me-2"></i> Presensi Hari Ini Selesai
                        </button>
                    <?php else: ?>
                        <button type="submit" name="simpan_absen" class="btn btn-success px-4 rounded-3 fw-bold shadow-sm" style="background-color: #245358; border-color: #245358;" <?= count($list_mahasiswa) === 0 ? 'disabled' : ''; ?>>
                            <i class="fa-solid fa-floppy-disk me-2"></i> Simpan Presensi Hari Ini
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>