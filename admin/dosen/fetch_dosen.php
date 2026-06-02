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

// Struktur dasar query SQL menggunakan sistem Named Bindings
$sql = "SELECT * FROM dosen WHERE 1=1";
$params = [];

// Filter 1: Berdasarkan Abjad Depan Nama Dosen (Jika bukan 'ALL')
if ($letter !== 'ALL' && !empty($letter)) {
    $sql .= " AND nama_dosen LIKE :letter";
    $params[':letter'] = $letter . '%';
}

// Filter 2: Berdasarkan Keyword Pencarian Nama Dosen
if (!empty($search)) {
    $sql .= " AND nama_dosen LIKE :search";
    $params[':search'] = '%' . $search . '%';
}

// Filter 3: Berdasarkan Pilihan Jabatan Fungsional
if (!empty($jabatan)) {
    $sql .= " AND jabatan = :jabatan";
    $params[':jabatan'] = $jabatan;
}

// Urutkan hasil pencarian berdasarkan nama dosen dari A ke Z
$sql .= " ORDER BY nama_dosen ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params); // Menggunakan parameter nama terikat, urutan array tidak akan bentrok lagi
$data_dosen = $stmt->fetchAll(PDO::FETCH_ASSOC);

// JIKA DATA DOSEN DITEMUKAN
if (count($data_dosen) > 0) {
    echo '<div class="row g-3">';
    foreach ($data_dosen as $dsn) {
        // Logika penentuan warna badge jabatan fungsional agar rapi
        $badgeColor = 'bg-secondary';
        if ($dsn['jabatan'] == 'Asisten Ahli') $badgeColor = 'bg-info text-dark';
        elseif ($dsn['jabatan'] == 'Lektor') $badgeColor = 'bg-primary';
        elseif ($dsn['jabatan'] == 'Lektor Kepala') $badgeColor = 'bg-warning text-dark';
        elseif ($dsn['jabatan'] == 'Profesor' || $dsn['jabatan'] == 'Professor') $badgeColor = 'bg-danger';
        ?>
        
        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12" id="card-dosen-<?= $dsn['id_dosen']; ?>">
            <div class="card border-0 shadow-sm rounded-4 h-100 card-dosen-klik style-card-hover" 
                 data-id="<?= $dsn['id_dosen']; ?>" 
                 style="cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; background: #ffffff;">
                
                <div class="card-body p-3.5 text-center d-flex flex-column justify-content-between">
                    
                    <div class="d-flex justify-content-end gap-1 posisi-tombol mb-2">
                        <button type="button" class="btn btn-light text-dark btn-sm rounded-circle p-0 btn-edit-dosen" 
                                data-id="<?= $dsn['id_dosen']; ?>" style="width: 28px; height: 28px;" title="Ubah Data">
                            <i class="fa-solid fa-user-pen text-secondary small"></i>
                        </button>
                        <button type="button" class="btn btn-light text-danger btn-sm rounded-circle p-0 btn-hapus-dosen" 
                                data-id="<?= $dsn['id_dosen']; ?>" data-nama="<?= $dsn['nama_dosen']; ?>" style="width: 28px; height: 28px;" title="Hapus Permanen">
                            <i class="fa-solid fa-trash-can small"></i>
                        </button>
                    </div>

                    <div class="mb-3">
                        <img src="../../uploads/profile/<?= $dsn['foto']; ?>" 
                             class="rounded-circle border shadow-sm" 
                             width="80" height="80" 
                             style="object-fit: cover;"
                             onerror="this.src='https://cdn-icons-png.flaticon.com/512/3135/3135715.png'">
                    </div>

                    <div>
                        <h6 class="fw-bold text-navy mb-1 text-truncate" title="<?= $dsn['nama_dosen']; ?>" style="color: #0F172A; font-size: 14px;">
                            <?= $dsn['nama_dosen']; ?>
                        </h6>
                        <p class="text-muted small mb-2" style="font-size: 11.5px;">NIDN: <b><?= $dsn['nidn']; ?></b></p>
                        
                        <span class="badge <?= $badgeColor; ?> rounded-pill px-3 py-1.5" style="font-size: 10.5px; letter-spacing: 0.3px;">
                            <?= $dsn['jabatan'] ?: 'Belum Ada Jabatan'; ?>
                        </span>
                    </div>

                    <hr class="my-3 opacity-25 border-secondary">

                    <div class="text-start small text-secondary" style="font-size: 11px;">
                        <div class="text-truncate mb-1"><i class="fa-regular fa-envelope me-1.5 opacity-70"></i><?= $dsn['email'] ?: '-'; ?></div>
                        <div class="text-truncate"><i class="fa-brands fa-whatsapp me-1.5 opacity-70"></i><?= $dsn['no_hp'] ?: '-'; ?></div>
                    </div>

                </div>
            </div>
        </div>

        <?php
    }
    echo '</div>';

    echo '<style>
            .style-card-hover:hover { transform: translateY(-4px); box-shadow: 0 10px 20px rgba(15, 23, 42, 0.08) !important; }
          </style>';

} else {
    ?>
    <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
        <div class="py-4">
            <i class="fa-solid fa-user-slash fa-4x mb-3 text-muted opacity-30"></i>
            <h5 class="fw-bold text-dark mb-1">Dosen Tidak Ditemukan</h5>
            <p class="text-secondary small mb-0 px-md-5">Tidak ada data profil dosen yang cocok dengan kombinasi filter abjad, kata kunci pencarian nama, ataupun jabatan fungsional yang Anda tentukan.</p>
        </div>
    </div>
    <?php
}
?>