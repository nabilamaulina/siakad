<?php
// admin/mahasiswa/fetch_mahasiswa.php
require_once '../../config/database.php';

$letter   = $_GET['letter'] ?? 'ALL';
$search   = $_GET['search'] ?? '';
$angkatan = $_GET['angkatan'] ?? '';
$page     = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit    = 10; // Dinaikkan ke 10 agar ruang layar tabel terisi padat & proporsional
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
    ?>
    <div class="table-responsive border rounded-4 bg-white shadow-sm">
        <table class="table table-hover align-middle mb-0 text-dark" style="font-size: 13px;">
            <thead class="table-light text-secondary border-bottom" style="font-size: 11px; font-weight: 700; letter-spacing: 0.5px;">
                <tr>
                    <th width="50" class="ps-3 text-center">PILIH</th>
                    <th width="70" class="text-center">FOTO</th>
                    <th>NAMA / NIM</th>
                    <th width="140" class="text-center">IPK TERAKHIR</th>
                    <th width="120" class="text-center">SEMESTER</th>
                    <th width="120" class="text-center">STATUS</th>
                    <th width="150" class="pe-3 text-center">AKSI</th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($mahasiswa as $mhs) {
                    $foto = !empty($mhs['foto']) ? $mhs['foto'] : 'default.png';
                    $path_foto = file_exists('../../assets/uploads/foto_mahasiswa/' . $foto) ? '../../assets/uploads/foto_mahasiswa/' . $foto : 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png';
                    
                    $status = htmlspecialchars($mhs['status_mahasiswa'] ?? 'Aktif');
                    $badge_class = 'bg-light text-dark';
                    if ($status === 'Aktif') $badge_class = 'bg-success-subtle text-success';
                    elseif ($status === 'Cuti') $badge_class = 'bg-warning-subtle text-warning-dark';
                    elseif ($status === 'Non-Aktif') $badge_class = 'bg-danger-subtle text-danger';
                    ?>
                    <tr>
                        <td class="ps-3 text-center">
                            <input type="checkbox" class="form-check-input check-item-mhs style-pointer border border-secondary" 
                                   style="width: 16px; height: 16px;" value="<?= $mhs['id_mahasiswa']; ?>">
                        </td>
                        <td class="text-center">
                            <img src="<?= $path_foto; ?>" class="rounded-circle border shadow-xs" 
                                 style="width: 38px; height: 38px; object-fit: cover;">
                        </td>
                        <td>
                            <span class="fw-bold text-navy d-block mb-0 text-truncate" style="max-width: 260px;" title="<?= htmlspecialchars($mhs['nama_mahasiswa']); ?>">
                                <?= htmlspecialchars($mhs['nama_mahasiswa']); ?>
                            </span>
                            <small class="text-secondary font-monospace" style="font-size: 11px;">NIM. <?= htmlspecialchars($mhs['nim']); ?></small>
                        </td>
                        <td class="text-center">
                            <span class="fw-bold text-success">
                                <i class="fa-solid fa-star text-warning me-1" style="font-size: 11px;"></i><?= number_format($mhs['ipk'] ?? 0.00, 2); ?>
                            </span>
                        </td>
                        <td class="text-center fw-semibold text-navy">
                            Semester <?= htmlspecialchars($mhs['id_semester_masuk'] ?? '1'); ?>
                        </td>
                        <td class="text-center">
                            <span class="badge rounded-pill text-uppercase border-0 px-2.5 py-1.5 <?= $badge_class; ?>" style="font-size: 9.5px; font-weight: 700;">
                                <?= $status; ?>
                            </span>
                        </td>
                        <td class="pe-3 text-center">
                            <div class="d-flex gap-1 justify-content-center">
                                <button type="button" class="btn btn-light text-secondary border btn-sm rounded-pill btn-detail-mhs px-2.5 py-1" 
                                        style="font-size: 11px; font-weight: 600;" data-id="<?= $mhs['id_mahasiswa']; ?>">
                                    <i class="fa-solid fa-address-card me-1 text-muted"></i> Detail
                                </button>
                                <button type="button" class="btn btn-outline-danger btn-sm border rounded-circle p-0 d-flex align-items-center justify-content-center btn-hapus-mhs" 
                                        style="width: 28px; height: 28px; flex-shrink: 0;" data-id="<?= $mhs['id_mahasiswa']; ?>" data-nama="<?= htmlspecialchars($mhs['nama_mahasiswa']); ?>">
                                    <i class="fa-solid fa-trash fa-xs"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php
                }
                ?>
            </tbody>
        </table>
    </div>
    <?php

    // Pagination links
    if ($total_pages > 1) {
        echo '<div class="d-flex justify-content-center mt-4">';
        echo '<nav><ul class="pagination pagination-sm shadow-xs rounded-pill border-0">';
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
        .table-hover tbody tr:hover { background-color: rgba(36,83,88, 0.03) !important; }
        .bg-navy.active { background-color: #245358 !important; color: white !important; }
        .shadow-xs { box-shadow: 0 1px 2px 0 rgba(0,0,0,0.05) !important; }
        .text-success { color: #16a34a !important; }
        .bg-success-subtle { background-color: #e8f5e9 !important; }
        .bg-warning-subtle { background-color: #fff3e0 !important; }
        .bg-danger-subtle { background-color: #ffebee !important; }
        .text-warning-dark { color: #b78103 !important; }
        .style-pointer { cursor: pointer; }
      </style>';
?>