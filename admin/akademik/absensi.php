<?php
// admin/akademik/absensi.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Pastikan layout template terpanggil dengan benar
require_once '../../templates/header.php';
require_once '../../templates/sidebar.php';
require_once '../../config/database.php';

// Ambil semua list jadwal mengajar prodi untuk dropdown filter (Diselaraskan dengan database Anda)
try {
    $jadwal_stmt = $pdo->query("SELECT j.id_jadwal, j.kode_mk, j.nama_matakuliah, j.id_kelas, j.dosen_pengajar 
                                FROM jadwal j 
                                ORDER BY j.nama_matakuliah ASC");
    $all_jadwal = $jadwal_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Gagal mengambil data jadwal: " . $e->getMessage());
}

$id_jadwal_terpilih = $_GET['id_jadwal'] ?? '';
$pertemuan_terpilih = $_GET['pertemuan'] ?? '1';
$mahasiswa_list = [];

// PERBAIKAN BUG #A: Memperbaiki typo variabel dari $id_jadwal_terpilled menjadi $id_jadwal_terpilih
if (!empty($id_jadwal_terpilih)) {
    try {
        // PERBAIKAN BUG #B: Relasi krs menggunakan id_mahasiswa ke tabel mahasiswa, disaring berdasarkan krs yang disetujui
        $mhs_stmt = $pdo->prepare("SELECT k.id_krs, m.id_mahasiswa, m.nim, m.nama_mahasiswa 
                                   FROM krs k
                                   JOIN mahasiswa m ON k.id_mahasiswa = m.id_mahasiswa
                                   WHERE k.id_jadwal = ? AND k.status_validasi = 'Disetujui'
                                   ORDER BY m.nim ASC");
        $mhs_stmt->execute([$id_jadwal_terpilih]);
        $mahasiswa_list = $mhs_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Gagal memuat daftar mahasiswa: " . $e->getMessage());
    }
}
?>

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; }
    .text-navy { color: #0f172a; }
    .card-premium { border: none; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.01), 0 2px 4px -1px rgba(0,0,0,0.01); }
    .table-thead { background-color: #f1f5f9; color: #475569; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; }
</style>

<div class="container-fluid py-4">
    
    <div class="row mb-4">
        <div class="col-12">
            <h3 class="fw-bold text-navy mb-1"><i class="fa-solid fa-user-check me-2 text-warning"></i>Lembar Presensi Kuliah</h3>
            <p class="text-muted small">Input, perbarui, dan tinjau rekam data kehadiran mahasiswa per sesi pertemuan tatap muka kuliah.</p>
        </div>
    </div>

    <div class="card card-premium border-0 shadow-sm mb-4 bg-white">
        <div class="card-body p-4">
            <form method="GET" action="" class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small fw-bold text-secondary">Mata Kuliah & Rombel Kelas</label>
                    <select name="id_jadwal" class="form-select rounded-3 small p-2.5 text-dark" required>
                        <option value="">-- Pilih Mata Kuliah Kelas --</option>
                        <?php foreach ($all_jadwal as $j): ?>
                            <option value="<?= $j['id_jadwal']; ?>" <?= ($id_jadwal_terpilih == $j['id_jadwal']) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($j['nama_matakuliah'] . ' - Kelas ' . $j['id_kelas'] . ' [' . $j['dosen_pengajar'] . ']'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-secondary">Pertemuan Tatap Muka</label>
                    <select name="pertemuan" class="form-select rounded-3 small p-2.5 text-dark" required>
                        <?php for ($i = 1; $i <= 16; $i++): ?>
                            <option value="<?= $i; ?>" <?= ($pertemuan_terpilih == $i) ? 'selected' : ''; ?>>Pertemuan Ke-<?= $i; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-warning w-100 rounded-pill text-white fw-bold py-2.5 shadow-sm">
                        <i class="fa-solid fa-magnifying-glass me-2"></i>Buka Lembar Presensi
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card card-premium border-0 shadow-sm bg-white">
        <div class="card-body p-4">
            <?php if (!empty($id_jadwal_terpilih)): ?>
                <form action="proses.php" method="POST">
                    <input type="hidden" name="action" value="simpan_absensi">
                    <input type="hidden" name="id_jadwal" value="<?= htmlspecialchars($id_jadwal_terpilih); ?>">
                    <input type="hidden" name="pertemuan" value="<?= htmlspecialchars($pertemuan_terpilih); ?>">

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h6 class="fw-bold text-dark m-0">
                            <i class="fa-regular fa-folder-open me-2 text-primary"></i>Lembar Kelas Kehadiran Sesi Tatap Muka <?= htmlspecialchars($pertemuan_terpilih); ?>
                        </h6>
                        <span class="badge bg-light text-dark border px-3 py-2 rounded-3 fw-bold">Total Terdaftar: <?= count($mahasiswa_list); ?></span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle text-center w-100">
                            <thead class="table-thead">
                                <tr>
                                    <th class="text-start" style="width: 15%;">NIM</th>
                                    <th class="text-start" style="width: 45%;">Nama Lengkap Mahasiswa</th>
                                    <th style="width: 10%;">Hadir (H)</th>
                                    <th style="width: 10%;">Sakit (S)</th>
                                    <th style="width: 10%;">Izin (I)</th>
                                    <th style="width: 10%;">Alpha (A)</th>
                                </tr>
                            </thead>
                            <tbody class="small text-secondary">
                                <?php if (count($mahasiswa_list) > 0): ?>
                                    <?php foreach ($mahasiswa_list as $mhs): 
                                        // PERBAIKAN BUG #C & #D: Query disesuaikan menargetkan tabel absensi dan kolom status milik Anda
                                        $check_stmt = $pdo->prepare("SELECT status FROM absensi 
                                                                     WHERE id_mahasiswa = ? AND id_jadwal = ? AND pertemuan_ke = ?");
                                        $check_stmt->execute([$mhs['id_mahasiswa'], $id_jadwal_terpilih, $pertemuan_terpilih]);
                                        $old_status = $check_stmt->fetchColumn() ?: 'A'; // Default di-set Alpha jika dosen belum mengisi
                                    ?>
                                        <tr>
                                            <td class="text-start font-monospace fw-bold text-primary"><?= htmlspecialchars($mhs['nim']); ?></td>
                                            <td class="text-start fw-bold text-dark"><?= htmlspecialchars($mhs['nama_mahasiswa']); ?></td>
                                            
                                            <td>
                                                <input type="radio" name="status_absen[<?= $mhs['id_mahasiswa']; ?>]" value="Hadir" <?= $old_status == 'Hadir' ? 'checked' : ''; ?> class="form-check-input border-success shadow-none">
                                            </td>
                                            <td>
                                                <input type="radio" name="status_absen[<?= $mhs['id_mahasiswa']; ?>]" value="Sakit" <?= $old_status == 'Sakit' ? 'checked' : ''; ?> class="form-check-input border-primary shadow-none">
                                            </td>
                                            <td>
                                                <input type="radio" name="status_absen[<?= $mhs['id_mahasiswa']; ?>]" value="Izin" <?= $old_status == 'Izin' ? 'checked' : ''; ?> class="form-check-input border-warning shadow-none">
                                            </td>
                                            <td>
                                                <input type="radio" name="status_absen[<?= $mhs['id_mahasiswa']; ?>]" value="Alpha" <?= $old_status == 'Alpha' ? 'checked' : ''; ?> class="form-check-input border-danger shadow-none">
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?> <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">Belum ada mahasiswa kelas ini di tabel data rencana studi (KRS) yang disetujui.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if (count($mahasiswa_list) > 0): ?>
                        <div class="text-end mt-4">
                            <button type="submit" class="btn btn-dark rounded-pill px-4 py-2 fw-semibold" style="background-color: #0f172a;">
                                <i class="fa-solid fa-floppy-disk me-2"></i>Simpan Lembar Presensi
                            </button>
                        </div>
                    <?php endif; ?>
                </form>
            <?php else: ?>
                <div class="text-center py-5 text-muted">
                    <i class="fa-solid fa-clipboard-user fa-4x mb-3" style="color: #cbd5e1;"></i>
                    <h6 class="fw-bold text-dark mb-1">Daftar Absen Belum Dimuat</h6>
                    <p class="small text-muted">Silakan tentukan rincian kelas mengajar dan sesi pertemuan di atas untuk mengisi absensi.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../../templates/footer.php'; ?>