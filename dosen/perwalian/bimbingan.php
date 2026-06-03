<?php 
// dosen/perwalian/bimbingan.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../templates/header.php'; 
require_once __DIR__ . '/../templates/sidebar.php'; 
require_once __DIR__ . '/../../config/database.php';

// Mengambil id_user dari session yang sedang login aktif
$id_user_dosen = $_SESSION['id_user'] ?? null;

// Tangkap data filter dari URL (Metode GET)
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_angkatan = isset($_GET['angkatan']) ? trim($_GET['angkatan']) : '';
$filter_status = isset($_GET['status']) ? trim($_GET['status']) : '';

try {
    // 1. AMBIL ID_DOSEN BERDASARKAN ID_USER YANG SEDANG LOGIN
    // Menggunakan query berelasi langsung agar id_dosen wali tidak tertukar atau bernilai 0
    $stmt_dosen = $pdo->prepare("SELECT id_dosen FROM dosen WHERE id_user = ?");
    $stmt_dosen->execute([$id_user_dosen]);
    $data_dosen = $stmt_dosen->fetch(PDO::FETCH_ASSOC);
    
    // Jika dosen ditemukan gunakan ID-nya, jika tidak ada set ke 0 agar query aman
    $id_dosen = $data_dosen['id_dosen'] ?? 0;

    // 2. Susun Query Utama Dasar
    $sql = "SELECT m.*, k.nama_kelas 
            FROM mahasiswa m
            LEFT JOIN kelas k ON m.id_kelas = k.id_kelas
            WHERE m.id_dosen_wali = :id_dosen";
    
    $params = [':id_dosen' => $id_dosen];

    // JIKA USER MENGETIK SESUATU DI KOLOM PENCARIAN
    if ($search !== '') {
        $sql .= " AND (m.nim LIKE :search OR m.nama_mahasiswa LIKE :search)";
        $params[':search'] = '%' . $search . '%';
    }

    // JIKA USER MEMILIH ANGKATAN TERTENTU
    if ($filter_angkatan !== '') {
        $sql .= " AND m.angkatan = :angkatan";
        $params[':angkatan'] = $filter_angkatan;
    }

    // JIKA USER MEMILIH STATUS TERTENTU
    if ($filter_status !== '') {
        $sql .= " AND m.status_mahasiswa = :status_mhs";
        $params[':status_mhs'] = $filter_status;
    }

    $sql .= " ORDER BY m.nim ASC";

    // Eksekusi Query terfilter menggunakan PDO
    $stmt_mhs = $pdo->prepare($sql);
    $stmt_mhs->execute($params);
    $list_mhs = $stmt_mhs->fetchAll(PDO::FETCH_ASSOC);

    $total_mhs = count($list_mhs);

} catch (Exception $e) {
    $list_mhs = [];
    $total_mhs = 0;
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Daftar Mahasiswa Perwalian</h4>
        <p class="text-muted small mb-0">Pemantauan data akademik mahasiswa bimbingan di bawah naungan Anda.</p>
    </div>
    <div class="bg-white px-3 py-2 rounded-3 border shadow-sm d-flex align-items-center gap-2">
        <span class="text-muted small fw-medium">Total Anak Wali:</span>
        <span class="badge bg-dark rounded-pill fw-bold" style="background-color: #245358 !important;"><?= $total_mhs; ?> Mhs</span>
    </div>
</div>

<form method="GET" action="" id="filterForm">
    <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
        <div class="card-body p-3">
            <div class="row g-2 align-items-center">
                <div class="col-md-6 col-12">
                    <div class="input-group" style="height: 40px;">
                        <span class="input-group-text bg-light border-0 rounded-start-3 text-muted ps-3">
                            <i class="fa-solid fa-magnifying-glass" style="font-size: 13px;"></i>
                        </span>
                        <input type="text" name="search" id="searchInput" class="form-control bg-light border-0" placeholder="Cari berdasarkan NIM atau Nama Mahasiswa..." value="<?= htmlspecialchars($search); ?>" autocomplete="off">
                        <button class="btn text-white px-3 rounded-end-3" type="submit" style="background-color: #245358; font-size: 13px; font-weight: 500;">Cari</button>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <select name="angkatan" class="form-select form-select-sm bg-light border-0 rounded-3 filter-select" style="height: 40px;">
                        <option value="">Semua Angkatan</option>
                        <option value="2022" <?= $filter_angkatan == '2022' ? 'selected' : ''; ?>>2022</option>
                        <option value="2023" <?= $filter_angkatan == '2023' ? 'selected' : ''; ?>>2023</option>
                        <option value="2024" <?= $filter_angkatan == '2024' ? 'selected' : ''; ?>>2024</option>
                        <option value="2025" <?= $filter_angkatan == '2025' ? 'selected' : ''; ?>>2025</option>
                    </select>
                </div>
                <div class="col-md-3 col-6">
                    <select name="status" class="form-select form-select-sm bg-light border-0 rounded-3 filter-select" style="height: 40px;">
                        <option value="">Status: Semua</option>
                        <option value="Aktif" <?= $filter_status == 'Aktif' ? 'selected' : ''; ?>>Aktif</option>
                        <option value="Cuti" <?= $filter_status == 'Cuti' ? 'selected' : ''; ?>>Cuti</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
</form>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="min-width: 800px;">
                <thead class="bg-light text-secondary small text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">
                    <tr>
                        <th class="py-3 ps-4" width="20%">NIM</th>
                        <th width="40%">Nama Lengkap</th>
                        <th width="15%">Kelas Reguler</th>
                        <th width="12%">Angkatan</th>
                        <th width="13%" class="text-center">Status Academic</th>
                    </tr>
                </thead>
                <tbody style="font-size: 14px;">
                    <?php if ($total_mhs > 0): ?>
                        <?php foreach ($list_mhs as $mhs): ?>
                            <tr>
                                <td class="py-3 ps-4 font-monospace fw-bold text-secondary" style="font-size: 13px;">
                                    <?= htmlspecialchars($mhs['nim']); ?>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white shadow-sm" 
                                             style="width: 36px; height: 36px; background: linear-gradient(135deg, #245358, #3b7b83); font-size: 12px; letter-spacing: 0.5px;">
                                            <?= strtoupper(substr($mhs['nama_mahasiswa'] ?? 'MM', 0, 2)); ?>
                                        </div>
                                        <div>
                                            <span class="d-block fw-semibold text-dark"><?= htmlspecialchars($mhs['nama_mahasiswa']); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge border bg-light text-dark rounded-3 px-2 py-1 fw-semibold"><?= htmlspecialchars($mhs['nama_kelas'] ?? 'Belum Diplot'); ?></span>
                                </td>
                                <td class="text-dark fw-medium">
                                    <?= htmlspecialchars($mhs['angkatan'] ?? '-'); ?>
                                </td>
                                <td class="text-center">
                                    <?php 
                                    $status = strtolower($mhs['status_mahasiswa'] ?? 'aktif');
                                    ?>
                                    <span class="badge rounded-pill px-3 py-1 text-uppercase fw-bold" style="font-size: 10px; <?= $status == 'aktif' ? 'background:#d1e7dd;color:#0f5132;' : 'background:#fff3cd;color:#664d03;'; ?>">
                                        <?= htmlspecialchars($mhs['status_mahasiswa'] ?? 'Aktif'); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <img src="https://cdn-icons-png.flaticon.com/512/9053/9053741.png" style="width: 60px; opacity: 0.3;" class="mb-3" alt="No Data">
                                <h6 class="text-muted mb-1">Data Tidak Ditemukan</h6>
                                <p class="text-muted small mb-0">Tidak ada data mahasiswa bimbingan yang sesuai dengan filter pencarian Anda.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Otomatis submit form ketika pilihan dropdown diubah
document.querySelectorAll('.filter-select').forEach(select => {
    select.addEventListener('change', () => {
        document.getElementById('filterForm').submit();
    });
});

// Menjaga fokus kursor ketikan di kolom pencarian setelah halaman reload
const searchInput = document.getElementById('searchInput');
if (searchInput.value !== '') {
    searchInput.focus();
    const val = searchInput.value;
    searchInput.value = '';
    searchInput.value = val;
}
</script>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>