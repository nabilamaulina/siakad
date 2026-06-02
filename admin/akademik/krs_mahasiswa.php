<?php
// admin/akademik/krs_mahasiswa.php
require_once '../../templates/header.php';
require_once '../../templates/sidebar.php';
require_once '../../config/database.php';

// Ambil data seluruh mahasiswa
$all_mhs = $pdo->query("SELECT id_mahasiswa, nim, nama_mahasiswa FROM mahasiswa ORDER BY nim ASC")->fetchAll();

// Ambil data jadwal lengkap untuk penambahan manual
$data_jadwal = $pdo->query("SELECT j.id_jadwal, j.hari, j.jam_mulai, mk.nama_mk, mk.sks, k.nama_kelas, k.ruang 
                            FROM jadwal j
                            JOIN mata_kuliah mk ON j.id_mk = mk.id_mk
                            JOIN kelas k ON j.id_kelas = k.id_kelas
                            ORDER BY FIELD(j.hari, 'Senin','Selasa','Rabu','Kamis','Jumat'), j.jam_mulai ASC")->fetchAll();
?>

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; }
    .text-navy { color: #0f172a; }
    .card-premium { border: none; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.01), 0 2px 4px -1px rgba(0,0,0,0.01); }
    .table-thead { background-color: #f1f5f9; color: #475569; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; }
    .btn-action-delete { transition: all 0.2s ease; }
    .btn-action-delete:hover { background-color: #fee2e2 !important; color: #dc2626 !important; border-color: #fca5a5 !important; }
</style>

<div class="container-fluid py-4">
    
    <div class="row mb-4">
        <div class="col-12">
            <h3 class="fw-bold text-navy mb-1"><i class="fa-regular fa-file-lines me-2 text-success"></i>Audit Rencana Studi (KRS)</h3>
            <p class="text-muted small">Validasi beban pengambilan SKS mahasiswa serta kontrol penambahan atau pembatalan mata kuliah.</p>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card card-premium border-0 shadow-sm mb-4 bg-white">
                <div class="card-header bg-white py-3 border-0 rounded-top-4">
                    <h6 class="mb-0 fw-bold text-dark"><i class="fa-solid fa-search me-2 text-secondary"></i>Cari Mahasiswa</h6>
                </div>
                <div class="card-body pt-0">
                    <form method="GET">
                        <div class="mb-3">
                            <select name="id_mhs_krs" class="form-select rounded-3 p-2.5 small text-dark" required>
                                <option value="">-- Pilih NIM / Nama --</option>
                                <?php foreach ($all_mhs as $m): ?>
                                    <option value="<?= $m['id_mahasiswa']; ?>" <?= (isset($_GET['id_mhs_krs']) && $_GET['id_mhs_krs'] == $m['id_mahasiswa']) ? 'selected' : ''; ?>><?= $m['nim'] . ' - ' . $m['nama_mahasiswa']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-dark w-100 rounded-pill py-2.5 fw-semibold" style="background-color: #0f172a;"><i class="fa-solid fa-folder-open me-2"></i>Buka Kartu Studi</button>
                    </form>
                </div>
            </div>

            <?php if (isset($_GET['id_mhs_krs']) && !empty($_GET['id_mhs_krs'])): ?>
            <div class="card card-premium border-0 shadow-sm border-start border-primary border-4 bg-white">
                <div class="card-body p-4">
                    <form action="proses.php" method="POST">
                        <input type="hidden" name="action" value="insert_krs_manual">
                        <input type="hidden" name="id_mahasiswa" value="<?= htmlspecialchars($_GET['id_mhs_krs']); ?>">
                        
                        <h6 class="fw-bold mb-1 text-dark">Daftarkan KRS Manual</h6>
                        <p class="text-muted small mb-3">Paksa tambahkan plotting mata kuliah ke dalam data mahasiswa ini.</p>
                        
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Pilih Mata Kuliah & Jadwal</label>
                            <select name="id_jadwal" class="form-select rounded-3 p-2 small text-dark" required>
                                <?php foreach ($data_jadwal as $jd): ?>
                                    <option value="<?= $jd['id_jadwal']; ?>"><?= htmlspecialchars($jd['nama_mk']); ?> (<?= $jd['sks']; ?> SKS) [<?= $jd['hari'] . ' - ' . $jd['nama_kelas']; ?>]</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary w-100 rounded-pill py-2 fw-semibold">Masukkan ke Rencana Studi</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="col-md-8">
            <div class="card card-premium border-0 shadow-sm h-100 bg-white">
                <div class="card-header bg-white py-3 border-0 rounded-top-4">
                    <h6 class="mb-0 fw-bold text-dark"><i class="fa-solid fa-list-check me-2 text-success"></i>Daftar Isian Kartu Rencana Studi</h6>
                </div>
                <div class="card-body">
                    <?php 
                    if (isset($_GET['id_mhs_krs']) && !empty($_GET['id_mhs_krs'])): 
                        $id_mhs_krs = $_GET['id_mhs_krs'];
                        $krs_stmt = $pdo->prepare("SELECT krs.id_krs, mk.nama_mk, mk.kode_mk, mk.sks, j.hari, j.jam_mulai, k.nama_kelas, k.ruang 
                                                   FROM krs 
                                                   JOIN jadwal j ON krs.id_jadwal = j.id_jadwal
                                                   JOIN mata_kuliah mk ON j.id_mk = mk.id_mk
                                                   JOIN kelas k ON j.id_kelas = k.id_kelas
                                                   WHERE krs.id_mahasiswa = ?");
                        $krs_stmt->execute([$id_mhs_krs]);
                        $data_krs = $krs_stmt->fetchAll();
                    ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle w-100">
                                <thead class="table-thead">
                                    <tr>
                                        <th>Mata Kuliah</th>
                                        <th class="text-center">SKS</th>
                                        <th>Waktu & Tempat</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="small text-secondary">
                                    <?php 
                                    $total_sks = 0;
                                    if (count($data_krs) > 0):
                                        foreach ($data_krs as $k): 
                                            $total_sks += $k['sks'];
                                    ?>
                                    <tr>
                                        <td>
                                            <span class="fw-bold text-dark d-block"><?= htmlspecialchars($k['nama_mk']); ?></span>
                                            <small class="text-muted font-monospace"><?= htmlspecialchars($k['kode_mk']); ?></small>
                                        </td>
                                        <td class="text-center"><span class="badge bg-light text-dark border px-2.5 py-1.5 rounded-3"><?= $k['sks']; ?></span></td>
                                        <td>
                                            <span class="badge bg-primary bg-opacity-10 text-primary border-0 px-2 py-1 small mb-1"><?= $k['hari'] . ', ' . substr($k['jam_mulai'],0,5); ?></span> 
                                            <br><small class="text-muted"><i class="fa-solid fa-door-open me-1"></i> Kelas <?= htmlspecialchars($k['nama_kelas']); ?> (R. <?= htmlspecialchars($k['ruang']); ?>)</small>
                                        </td>
                                        <td class="text-center">
                                            <a href="proses.php?action=drop_krs&id=<?= $k['id_krs']; ?>&mhs=<?= $id_mhs_krs; ?>" class="btn btn-sm btn-light text-danger rounded-circle border btn-action-delete" onclick="return confirm('Batalkan pengambilan mata kuliah ini dari KRS mahasiswa?');">
                                                <i class="fa-solid fa-circle-minus"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php 
                                        endforeach;
                                    else: 
                                    ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-5 text-muted">
                                            <i class="fa-regular fa-folder-open fa-2x mb-2 d-block text-secondary"></i>
                                            Belum ada mata kuliah yang diambil untuk semester ini.
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                                <tfoot class="table-light fw-bold small border-top">
                                    <tr>
                                        <td class="text-end text-uppercase text-secondary" style="font-size: 11px;">Total SKS Diambil:</td>
                                        <td class="text-center text-primary fs-6 fw-bold"><?= $total_sks; ?> SKS</td>
                                        <td colspan="2">
                                            Status Dokumen: 
                                            <?= ($total_sks > 24) ? '<span class="badge bg-danger rounded-pill px-3 py-1.5 ms-1">Overload Limit</span>' : ($total_sks == 0 ? '<span class="badge bg-light text-muted border rounded-pill px-3 py-1.5 ms-1">Kosong</span>' : '<span class="badge bg-success rounded-pill px-3 py-1.5 ms-1"><i class="fa-solid fa-check me-1"></i> Terverifikasi</span>'); ?>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5 text-muted my-auto">
                            <i class="fa-solid fa-id-card fa-4x mb-3" style="color: #cbd5e1;"></i>
                            <h6 class="fw-bold text-dark mb-1">Data Belum Dipilih</h6>
                            <p class="small text-muted px-4">Pilih salah satu mahasiswa dari panel kiri untuk membuka lembar KRS aktif.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../templates/footer.php'; ?>