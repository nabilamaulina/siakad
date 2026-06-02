<?php
// admin/mahasiswa/fetch_mahasiswa.php
require_once '../../config/database.php';

$letter   = $_GET['letter'] ?? 'ALL';
$search   = $_GET['search'] ?? '';
$angkatan = $_GET['angkatan'] ?? '';
$page     = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit    = 8; 
$offset   = ($page - 1) * $limit;

$sql = "SELECT * FROM mahasiswa WHERE 1=1";
$params = [];

if ($letter !== 'ALL' && !empty($letter)) {
    $sql .= " AND nama_mahasiswa LIKE ?";
    $params[] = $letter . '%';
}

if (!empty($search)) {
    $sql .= " AND (nama_mahasiswa LIKE ? OR nim LIKE ?)";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

// FIX FILTER ANGKATAN: Menggunakan pencocokan NIM 2 digit tahun di depan (contoh NIM 22xxxxxx)
if (!empty($angkatan)) {
    $thn_dua_digit = strlen($angkatan) == 4 ? substr($angkatan, 2, 2) : $angkatan;
    $sql .= " AND nim LIKE ?";
    $params[] = $thn_dua_digit . '%';
}

$count_sql = str_replace("SELECT *", "SELECT COUNT(*)", $sql);
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_rows = $count_stmt->fetchColumn();
$total_pages = ceil($total_rows / $limit);

$sql .= " ORDER BY nim DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$mahasiswa = $stmt->fetchAll();

if (count($mahasiswa) > 0) {
    echo '<div class="row g-3">';
    foreach ($mahasiswa as $mhs) {
        $foto = !empty($mhs['foto']) ? $mhs['foto'] : 'default.png';
        $path_foto = file_exists('../../uploads/mahasiswa/' . $foto) ? '../../uploads/mahasiswa/' . $foto : 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png';
        ?>
        <div class="col-12 col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 text-center p-3 h-100 bg-white style-card-mhs-hover btn-detail-mhs style-pointer" 
                 data-id="<?= $mhs['id_mahasiswa']; ?>" id="card-mhs-<?= $mhs['id_mahasiswa']; ?>">
                 
                <div class="position-absolute top-0 end-0 p-2">
                    <span class="badge rounded-pill bg-light text-dark shadow-xs border text-uppercase" style="font-size: 9px;">
                        <?= htmlspecialchars($mhs['status_mahasiswa'] ?? 'Aktif'); ?>
                    </span>
                </div>

                <img src="<?= $path_foto; ?>" class="rounded-circle mx-auto my-2 border shadow-xs" style="width: 72px; height: 72px; object-fit: cover;">
                
                <h6 class="fw-bold text-navy text-truncate px-2 mb-0 mt-1" style="font-size: 13.5px;" title="<?= htmlspecialchars($mhs['nama_mahasiswa']); ?>">
                    <?= htmlspecialchars($mhs['nama_mahasiswa']); ?>
                </h6>
                <small class="text-secondary font-monospace d-block mb-2" style="font-size: 11px;">NIM. <?= htmlspecialchars($mhs['nim']); ?></small>
                
                <div class="bg-light rounded-3 p-2 mb-3">
                    <div class="row g-0 text-start small" style="font-size: 10.5px;">
                        <div class="col-6 border-end text-center">
                            <span class="text-muted d-block" style="font-size: 9px; font-weight: 700;">IPK TERAKHIR</span>
                            <span class="fw-bold text-success"><i class="fa-solid fa-star text-warning me-0.5"></i> <?= number_format($mhs['ipk'] ?? 0.00, 2); ?></span>
                        </div>
                        <div class="col-6 text-center">
                            <span class="text-muted d-block" style="font-size: 9px; font-weight: 700;">SEMESTER</span>
                            <span class="fw-bold text-navy"><?= htmlspecialchars($mhs['semester_saat_ini'] ?? '1'); ?></span>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-1.5 mt-auto">
                    <button type="button" class="btn btn-light text-secondary border rounded-pill w-100 py-1.5 btn-detail-mhs fw-semibold" style="font-size: 11px;" data-id="<?= $mhs['id_mahasiswa']; ?>">
                        <i class="fa-solid fa-address-card me-1 text-muted"></i> Detail Profil
                    </button>
                    <button type="button" class="btn btn-outline-danger border rounded-circle p-0 d-flex align-items-center justify-content-center btn-hapus-mhs" 
                            style="width: 30px; height: 30px; flex-shrink: 0;" data-id="<?= $mhs['id_mahasiswa']; ?>" data-nama="<?= htmlspecialchars($mhs['nama_mahasiswa']); ?>">
                        <i class="fa-solid fa-trash fa-xs"></i>
                    </button>
                </div>
            </div>
        </div>
        <?php
    }
    echo '</div>';

    // Pagination
    if ($total_pages > 1) {
        echo '<div class="d-flex justify-content-center mt-4">';
        echo '<nav><ul class="pagination pagination-sm shadow-xs rounded-pill">';
        for ($i = 1; $i <= $total_pages; $i++) {
            $active_class = ($i == $page) ? 'active bg-navy' : '';
            echo "<li class='page-item'><a class='page-link px-3 py-1.5 border-0 mhs-page-link $active_class' href='#' data-page='$i'>$i</a></li>";
        }
        echo '</ul></nav></div>';
    }
} else {
    ?>
    <div class="text-center p-5 bg-white rounded-4 border shadow-sm">
        <i class="fa-solid fa-user-slash fa-3x text-muted mb-3 opacity-50"></i>
        <h6 class="text-secondary fw-semibold">Tidak ada data mahasiswa ditemukan.</h6>
    </div>
    <?php
}

echo '<style>
        .style-card-mhs-hover { transition: all 0.22s ease-in-out; border: 1px solid rgba(0,0,0,0.03) !important; }
        .style-card-mhs-hover:hover { transform: translateY(-4px); box-shadow: 0 12px 20px -4px rgba(15, 23, 42, 0.08) !important; border-color: rgba(36,83,88, 0.15) !important; }
        .bg-navy.active { background-color: #245358 !important; color: white !important; }
        .shadow-xs { box-shadow: 0 1px 2px 0 rgba(0,0,0,0.05) !important; }
        .bg-success-subtle { background-color: #f0fdf4 !important; }
        .text-success { color: #16a34a !important; }
        .bg-warning-subtle { background-color: #fffbeb !important; }
        .text-warning { color: #d97706 !important; }
        .bg-danger-subtle { background-color: #fef2f2 !important; }
        .text-danger { color: #dc2626 !important; }
        .style-pointer { cursor: pointer; }
      </style>';
?>