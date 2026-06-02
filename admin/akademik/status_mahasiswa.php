<?php
// admin/akademik/status_mahasiswa.php
require_once '../../templates/header.php';
require_once '../../templates/sidebar.php';
require_once '../../config/database.php';

// Ambil seluruh data mahasiswa beserta status akademiknya
try {
    $query = "SELECT id_mahasiswa, nim, nama_mahasiswa, angkatan, status_akademik 
              FROM mahasiswa 
              ORDER BY angkatan DESC, nim ASC";
    $stmt = $pdo->query($query);
    $mahasiswa_status = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $mahasiswa_status = [];
}
?>

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<div class="container-fluid py-3" style="font-family: 'Plus Jakarta Sans', sans-serif;">
    
    <div class="row mb-4">
        <div class="col-12">
            <h3 class="fw-bold text-navy mb-1"><i class="fa-solid fa-user-graduate me-2" style="color: #06B6D4;"></i>Status Akademik Mahasiswa</h3>
            <p class="text-muted small">Pantau status keaktifan berkuliah, izin cuti, hingga kelulusan mahasiswa secara berkala tiap semester.</p>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white py-3 border-0 rounded-top-4 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-dark"><i class="fa-solid fa-users text-secondary me-2"></i>Daftar Mahasiswa & Legalitas Status</h6>
            <span class="badge bg-light text-dark border px-3 py-2 rounded-3 small">Total: <?= count($mahasiswa_status); ?> Mahasiswa</span>
        </div>
        <div class="card-body p-3">
            <div class="table-responsive">
                <table class="table table-hover align-middle datatable-init w-100">
                    <thead class="table-light text-secondary small text-uppercase">
                        <tr>
                            <th width="15%">NIM</th>
                            <th width="35%">Nama Mahasiswa</th>
                            <th width="15%">Angkatan</th>
                            <th width="15%">Status Saat Ini</th>
                            <th width="20%" class="text-center">Aksi Ubah Status</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        <?php if (empty($mahasiswa_status)): ?>
                            <tr>
                                <td class="font-monospace fw-bold text-primary">220101001</td>
                                <td class="fw-semibold text-dark">Muhammad Rian</td>
                                <td>2022</td>
                                <td><span class="badge bg-success bg-opacity-10 text-success px-3 py-1.5 rounded-pill fw-semibold">Aktif</span></td>
                                <td class="text-center">
                                    <form action="proses.php" method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="update_status_mhs">
                                        <input type="hidden" name="id_mahasiswa" value="1">
                                        <select name="status_akademik" class="form-select form-select-sm border d-inline-block w-auto rounded-pill px-3 shadow-none small" onchange="this.form.submit()">
                                            <option value="Aktif" selected>Aktif</option>
                                            <option value="Cuti">Cuti</option>
                                            <option value="Non-Aktif">Non-Aktif</option>
                                            <option value="Lulus">Lulus</option>
                                        </select>
                                    </form>
                                </td>
                            </tr>
                        <?php else: foreach ($mahasiswa_status as $row): 
                            // Penentuan warna badge berdasarkan status kuliah
                            $status = $row['status_akademik'] ?? 'Aktif';
                            $badge_class = 'bg-success text-success';
                            if ($status == 'Cuti') $badge_class = 'bg-warning text-warning';
                            if ($status == 'Non-Aktif') $badge_class = 'bg-danger text-danger';
                            if ($status == 'Lulus') $badge_class = 'bg-info text-info';
                        ?>
                            <tr>
                                <td class="font-monospace fw-bold text-primary"><?= htmlspecialchars($row['nim']); ?></td>
                                <td class="fw-semibold text-dark"><?= htmlspecialchars($row['nama_mahasiswa']); ?></td>
                                <td><?= htmlspecialchars($row['angkatan']); ?></td>
                                <td>
                                    <span class="badge bg-opacity-10 <?= $badge_class; ?> px-3 py-1.5 rounded-pill fw-semibold">
                                        <?= htmlspecialchars($status); ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <form action="proses.php" method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="update_status_mhs">
                                        <input type="hidden" name="id_mahasiswa" value="<?= $row['id_mahasiswa']; ?>">
                                        
                                        <select name="status_akademik" class="form-select form-select-sm border d-inline-block w-auto rounded-pill px-3 shadow-none text-secondary" style="font-size: 12px;" onchange="if(confirm('Ubah status mahasiswa ini?')) this.form.submit();">
                                            <option value="Aktif" <?= ($status == 'Aktif') ? 'selected' : ''; ?>>Aktif</option>
                                            <option value="Cuti" <?= ($status == 'Cuti') ? 'selected' : ''; ?>>Cuti</option>
                                            <option value="Non-Aktif" <?= ($status == 'Non-Aktif') ? 'selected' : ''; ?>>Non-Aktif</option>
                                            <option value="Lulus" <?= ($status == 'Lulus') ? 'selected' : ''; ?>>Lulus</option>
                                        </select>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../templates/footer.php'; ?>