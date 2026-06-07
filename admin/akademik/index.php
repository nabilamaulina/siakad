<?php
// admin/akademik/index.php
require_once '../../templates/header.php';
require_once '../../templates/sidebar.php';
require_once '../../config/database.php';

// Ambil Data Master untuk Dropdown Form (Kembali menggunakan mata_kuliah)
$all_mk = $pdo->query("SELECT * FROM mata_kuliah ORDER BY semester ASC, nama_mk ASC")->fetchAll();
$all_kls = $pdo->query("SELECT * FROM kelas ORDER BY nama_kelas ASC")->fetchAll();
$all_dsn = $pdo->query("SELECT id_dosen, nama_dosen FROM dosen ORDER BY nama_dosen ASC")->fetchAll();

// SOLUSI TOTAL ERROR BARIS 13: Menggunakan SELECT * agar terhindar dari hardcode nama kolom yang salah
$all_sem = $pdo->query("SELECT * FROM semester ORDER BY id_semester DESC")->fetchAll();

// Ambil Relasi Jadwal Sesuai Skema Database (JOIN dikembalikan ke mata_kuliah)
$jadwal_stmt = $pdo->query("SELECT j.id_jadwal, j.hari, j.jam_mulai, j.jam_selesai, 
                                   mk.kode_mk, mk.nama_mk, mk.sks, mk.semester,
                                   d.nama_dosen, k.nama_kelas, k.ruang 
                            FROM jadwal j
                            JOIN mata_kuliah mk ON j.id_mk = mk.id_mk
                            JOIN kelas k ON j.id_kelas = k.id_kelas
                            JOIN dosen d ON j.id_dosen = d.id_dosen
                            ORDER BY FIELD(j.hari, 'Senin','Selasa','Rabu','Kamis','Jumat'), j.jam_mulai ASC");
$data_jadwal = $jadwal_stmt->fetchAll();
?>

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; }
    .text-navy { color: #0f172a; }
    .card-dashboard { border: none; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.01), 0 2px 4px -1px rgba(0,0,0,0.02); }
    .nav-tabs { border-bottom: 2px solid #e2e8f0; }
    .nav-tabs .nav-link { color: #64748b; border: none; padding: 14px 24px; font-weight: 600; border-bottom: 2px solid transparent; transition: all 0.2s ease; }
    .nav-tabs .nav-link.active { color: #0284c7; border-bottom: 2px solid #0284c7; background: transparent; }
    .nav-tabs .nav-link:hover:not(.active) { color: #0f172a; border-bottom: 2px solid #cbd5e1; }
    .table-thead { background-color: #f1f5f9; color: #475569; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; }
    .badge-time { background-color: rgba(2, 132, 199, 0.08); color: #0284c7; font-weight: 600; }
    .btn-action-delete { transition: all 0.2s ease; }
    .btn-action-delete:hover { background-color: #fee2e2 !important; color: #dc2626 !important; border-color: #fca5a5 !important; }
</style>

<div class="container-fluid py-4">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-navy mb-1"><i class="fa-solid fa-calendar-days me-2 text-primary"></i>Manajemen Jadwal & Perkuliahan</h3>
            <p class="text-muted small mb-0">Kelola plotting jadwal kuliah mengajar dosen, integrasi kelas mahasiswa, dan kurikulum mata kuliah.</p>
        </div>
    </div>

    <ul class="nav nav-tabs mb-4" id="academicTab" role="tablist">
        <li class="nav-item">
            <button class="nav-link active" id="jadwal-tab" data-bs-toggle="tab" data-bs-target="#panel-jadwal" type="button">
                <i class="fa-solid fa-clock-history me-2"></i>Plotting Waktu Kuliah
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" id="mk-tab" data-bs-toggle="tab" data-bs-target="#panel-mk" type="button">
                <i class="fa-solid fa-book-open me-2"></i>Master Mata Kuliah
            </button>
        </li>
    </ul>

    <div class="tab-content" id="academicTabContent">
        
        <div class="tab-pane fade show active" id="panel-jadwal" role="tabpanel">
            <div class="card card-dashboard bg-white">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-0 rounded-top-4">
                    <h6 class="mb-0 fw-bold text-navy"><i class="fa-regular fa-calendar-check me-2 text-primary"></i>Alokasi Waktu Mengajar Berjalan</h6>
                    <button class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahJadwal">
                        <i class="fa-solid fa-plus me-1"></i>Tambah Plot Jadwal
                    </button>
                </div>
                <div class="card-body p-3">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle datatable-init w-100">
                            <thead class="table-thead">
                                <tr>
                                    <th>Hari & Waktu</th>
                                    <th>Mata Kuliah (Sem.)</th>
                                    <th>SKS</th>
                                    <th>Dosen Pengampu</th>
                                    <th>Ruang Kelas</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="small text-secondary">
                                <?php foreach ($data_jadwal as $j): ?>
                                <tr>
                                    <td class="fw-bold text-dark">
                                        <span class="badge badge-time px-2.5 py-1.5 rounded-3 me-2">
                                            <?= htmlspecialchars($j['hari']); ?>
                                        </span> 
                                        <?= substr($j['jam_mulai'],0,5) . ' - ' . substr($j['jam_selesai'],0,5); ?>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-dark d-block"><?= htmlspecialchars($j['nama_mk']); ?></span> 
                                        <small class="text-muted"><?= htmlspecialchars($j['kode_mk']); ?> • Semester <?= htmlspecialchars($j['semester']); ?></small>
                                    </td>
                                    <td><span class="badge bg-light text-dark border px-2.5 py-1.5 rounded-3"><?= htmlspecialchars($j['sks']); ?> SKS</span></td>
                                    <td class="fw-medium text-dark"><i class="fa-solid fa-user-tie text-muted me-1"></i> <?= htmlspecialchars($j['nama_dosen']); ?></td>
                                    <td>
                                        <span class="badge px-2.5 py-2 text-dark border bg-light">
                                            <i class="fa-solid fa-door-open me-1 text-warning"></i> Kelas <?= htmlspecialchars($j['nama_kelas']); ?> (R. <?= htmlspecialchars($j['ruang']); ?>)
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <a href="proses.php?action=delete_jadwal&id=<?= $j['id_jadwal']; ?>" class="btn btn-sm btn-light border text-danger rounded-circle btn-action-delete" onclick="return confirm('Hapus plot perkuliahan ini?');">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="panel-mk" role="tabpanel">
            <div class="card card-dashboard bg-white">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-0 rounded-top-4">
                    <h6 class="mb-0 fw-bold text-navy"><i class="fa-solid fa-graduation-cap me-2 text-success"></i>Kurikulum Program Studi</h6>
                    <button class="btn btn-success btn-sm rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahMK">
                        <i class="fa-solid fa-plus me-1"></i>Tambah Mata Kuliah
                    </button>
                </div>
                <div class="card-body p-3">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle datatable-init w-100">
                            <thead class="table-thead">
                                <tr>
                                    <th class="text-center">Semester</th>
                                    <th>Kode MK</th>
                                    <th>Nama Mata Kuliah</th>
                                    <th>Beban SKS</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="small text-secondary">
                                <?php foreach ($all_mk as $mk): ?>
                                <tr>
                                    <td class="text-center"><span class="badge bg-dark px-2.5 py-1.5 rounded-3">Smt <?= htmlspecialchars($mk['semester']); ?></span></td>
                                    <td class="font-monospace fw-bold text-primary"><?= htmlspecialchars($mk['kode_mk']); ?></td>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($mk['nama_mk']); ?></td>
                                    <td><span class="fw-semibold"><?= htmlspecialchars($mk['sks']); ?> Kredit SKS</span></td>
                                    <td class="text-center">
                                        <a href="proses.php?action=delete_mk&id=<?= htmlspecialchars($mk['id_mk']); ?>" class="btn btn-sm btn-light border text-danger rounded-circle btn-action-delete" onclick="return confirm('Hapus mata kuliah ini secara permanen?');">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<div class="modal fade" id="modalTambahMK" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="proses.php" method="POST" class="modal-content border-0 shadow-lg rounded-4">
            <input type="hidden" name="action" value="insert_mk">
            <div class="modal-header border-0 py-3 text-white" style="background-color: #0f172a;">
                <h6 class="modal-title fw-bold text-uppercase"><i class="fa-solid fa-book me-2 text-success"></i>Tambah Kurikulum Baru</h6>
                <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label small fw-bold text-secondary">Kode Mata Kuliah</label>
                    <input type="text" name="kode_mk" class="form-control rounded-3" placeholder="Contoh: IK102" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold text-secondary">Nama Mata Kuliah</label>
                    <input type="text" name="nama_mk" class="form-control rounded-3" placeholder="Contoh: Dasar Pemrograman" required>
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label small fw-bold text-secondary">SKS</label>
                        <input type="number" name="sks" class="form-control rounded-3" min="1" max="6" value="3" required>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label small fw-bold text-secondary">Semester</label>
                        <input type="number" name="semester" class="form-control rounded-3" min="1" max="8" value="1" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-0 rounded-bottom-4">
                <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-success btn-sm rounded-pill px-4 fw-semibold">Simpan MK</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalTambahJadwal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="proses.php" method="POST" class="modal-content border-0 shadow-lg rounded-4">
            <input type="hidden" name="action" value="insert_jadwal">
            <div class="modal-header border-0 py-3 text-white" style="background-color: #0f172a;">
                <h6 class="modal-title fw-bold text-uppercase"><i class="fa-solid fa-calendar-plus me-2 text-primary"></i>Buat Plot Jadwal Kuliah</h6>
                <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label small fw-bold text-secondary">Pilih Mata Kuliah</label>
                    <select name="id_mk" class="form-select rounded-3 small text-dark" required>
                        <option value="">-- Pilih Mata Kuliah --</option>
                        <?php foreach ($all_mk as $m): ?>
                            <option value="<?= $m['id_mk']; ?>"><?= htmlspecialchars($m['kode_mk'] . ' - ' . $m['nama_mk']); ?> (Smt <?= $m['semester']; ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold text-secondary">Dosen Pengampu</label>
                    <select name="id_dosen" class="form-select rounded-3 small text-dark" required>
                        <option value="">-- Pilih Dosen --</option>
                        <?php foreach ($all_dsn as $d): ?>
                            <option value="<?= $d['id_dosen']; ?>"><?= htmlspecialchars($d['nama_dosen']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold text-secondary">Pilih Kelas</label>
                        <select name="id_kelas" class="form-select rounded-3 small text-dark" required>
                            <option value="">-- Pilih Kelas --</option>
                            <?php foreach ($all_kls as $k): ?>
                                <option value="<?= $k['id_kelas']; ?>">Kelas <?= htmlspecialchars($k['nama_kelas']); ?> (R. <?= htmlspecialchars($k['ruang']); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold text-secondary">Semester Aktif</label>
                        <select name="id_semester" class="form-select rounded-3 small text-dark" required>
                            <?php foreach ($all_sem as $s): 
                                $kolom_data = array_values($s);
                                $nama_tampilan_semester = $kolom_data[1] ?? 'Semester Terdaftar';
                            ?>
                                <option value="<?= $s['id_semester']; ?>"><?= htmlspecialchars($nama_tampilan_semester); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label small fw-bold text-secondary">Hari</label>
                        <select name="hari" class="form-select rounded-3 small text-dark" required>
                            <option value="Senin">Senin</option>
                            <option value="Selasa">Selasa</option>
                            <option value="Rabu">Rabu</option>
                            <option value="Kamis">Kamis</option>
                            <option value="Jumat">Jumat</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label small fw-bold text-secondary">Jam Mulai</label>
                        <input type="time" name="jam_mulai" class="form-control rounded-3" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label small fw-bold text-secondary">Jam Selesai</label>
                        <input type="time" name="jam_selesai" class="form-control rounded-3" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-0 rounded-bottom-4">
                <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4 fw-semibold">Plot Jadwal</button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../templates/footer.php'; ?>