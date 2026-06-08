<?php
// admin/dosen/fetch_dosen.php
require_once '../../config/database.php';
require_once '../../config/security.php';

// Memastikan hanya admin yang bisa memanggil file ini via Ajax
middleware(['admin']);

// 1. LOGIKA UNTUK MENAMPILKAN DETAIL DOSEN (MODAL CLICK)
if (isset($_GET['get_detail']) && isset($_GET['id_dosen'])) {
    $id_dosen = $_GET['id_dosen'];
    $stmt = $pdo->prepare("SELECT * FROM dosen WHERE id_dosen = ?");
    $stmt->execute([$id_dosen]);
    $dosen = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode($dosen);
    exit();
}

// 2. LOGIKA UTAMA: FILTER DATA DOSEN (ABJAD, SEARCH, & JABATAN)
$letter = $_GET['letter'] ?? 'ALL';
$search = $_GET['search'] ?? '';
$jabatan = $_GET['jabatan'] ?? '';

$sql = "SELECT * FROM dosen WHERE 1=1";
$params = [];

if ($letter !== 'ALL' && !empty($letter)) {
    $sql .= " AND nama_dosen LIKE :letter";
    $params[':letter'] = $letter . '%';
}

if (!empty($search)) {
    $sql .= " AND (nama_dosen LIKE :search OR nidn LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if (!empty($jabatan)) {
    $sql .= " AND jabatan = :jabatan";
    $params[':jabatan'] = $jabatan;
}

$sql .= " ORDER BY nama_dosen ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$data_dosen = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($data_dosen) > 0) {
    ?>
    <div class="table-responsive border rounded-4 bg-white shadow-sm">
        <table class="table table-hover align-middle mb-0 text-dark" style="font-size: 13px;">
            <thead class="table-light text-secondary border-bottom" style="font-size: 11px; font-weight: 700; letter-spacing: 0.5px;">
                <tr>
                    <th width="50" class="ps-3 text-center">PILIH</th>
                    <th width="70" class="text-center">FOTO</th>
                    <th>NAMA / NIDN</th>
                    <th>JABATAN FUNGSIONAL</th>
                    <th>EMAIL RESMI</th>
                    <th>NO. HANDPHONE</th>
                    <th width="150" class="pe-3 text-center">AKSI</th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($data_dosen as $dsn) {
                    $badgeColor = 'bg-secondary';
                    if ($dsn['jabatan'] == 'Asisten Ahli') $badgeColor = 'bg-info text-dark';
                    elseif ($dsn['jabatan'] == 'Lektor') $badgeColor = 'bg-primary text-white';
                    elseif ($dsn['jabatan'] == 'Lektor Kepala') $badgeColor = 'bg-warning text-dark';
                    elseif ($dsn['jabatan'] == 'Profesor' || $dsn['jabatan'] == 'Professor') $badgeColor = 'bg-danger text-white';
                    
                    $foto_dosen = !empty($dsn['foto']) ? $dsn['foto'] : 'default.png';
                    ?>
                    <tr>
                        <td class="ps-3 text-center">
                            <input type="checkbox" class="form-check-input check-item-dosen style-pointer border border-secondary" 
                                   style="width: 16px; height: 16px;" value="<?= $dsn['id_dosen']; ?>">
                        </td>
                        <td class="text-center">
                            <img src="../../assets/uploads/profile/<?= $foto_dosen; ?>" class="rounded-circle border shadow-xs" 
                                 style="width: 38px; height: 38px; object-fit: cover;" 
                                 onerror="this.src='https://cdn-icons-png.flaticon.com/512/3135/3135715.png'">
                        </td>
                        <td>
                            <span class="fw-bold text-navy d-block mb-0 text-truncate" style="max-width: 250px;" title="<?= htmlspecialchars($dsn['nama_dosen']); ?>">
                                <?= htmlspecialchars($dsn['nama_dosen']); ?>
                            </span>
                            <small class="text-secondary font-monospace" style="font-size: 11px;">NIDN. <?= htmlspecialchars($dsn['nidn']); ?></small>
                        </td>
                        <td>
                            <span class="badge <?= $badgeColor; ?> rounded-pill px-2.5 py-1.5 small text-uppercase" style="font-size: 9.5px; font-weight: 700;">
                                <?= htmlspecialchars($dsn['jabatan'] ?: 'Belum Ada Jabatan'); ?>
                            </span>
                        </td>
                        <td class="text-muted font-monospace" style="font-size: 12px;">
                            <?= htmlspecialchars($dsn['email'] ?: '-'); ?>
                        </td>
                        <td class="text-secondary">
                            <?= htmlspecialchars($dsn['no_hp'] ?: '-'); ?>
                        </td>
                        <td class="pe-3 text-center">
                            <div class="d-flex gap-1 justify-content-center">
                                <button type="button" class="btn btn-light text-secondary border btn-sm rounded-pill btn-detail-dosen px-2.5 py-1" 
                                        style="font-size: 11px; font-weight: 600;" data-id="<?= $dsn['id_dosen']; ?>">
                                    <i class="fa-solid fa-address-card me-1 text-muted"></i> Detail
                                </button>
                                <button type="button" class="btn btn-light text-dark border btn-sm rounded-circle p-0 d-flex align-items-center justify-content-center btn-edit-dosen" 
                                        style="width: 28px; height: 28px; flex-shrink: 0;" data-id="<?= $dsn['id_dosen']; ?>">
                                    <i class="fa-solid fa-user-pen fa-xs text-secondary"></i>
                                </button>
                                <button type="button" class="btn btn-outline-danger btn-sm border rounded-circle p-0 d-flex align-items-center justify-content-center btn-hapus-dosen" 
                                        style="width: 28px; height: 28px; flex-shrink: 0;" data-id="<?= $dsn['id_dosen']; ?>" data-nama="<?= htmlspecialchars($dsn['nama_dosen']); ?>">
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
    echo '<style>
            .table-hover tbody tr:hover { background-color: rgba(36,83,88, 0.03) !important; }
            .shadow-xs { box-shadow: 0 1px 2px 0 rgba(0,0,0,0.05) !important; }
            .style-pointer { cursor: pointer; }
          </style>';
} else {
    ?>
    <div class="text-center p-5 bg-white rounded-4 border shadow-sm">
        <i class="fa-solid fa-user-slash fa-3x text-muted mb-3 opacity-50"></i>
        <h6 class="text-secondary fw-semibold">Tidak ada data dosen ditemukan.</h6>
    </div>
    <?php
}
?>