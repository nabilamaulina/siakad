<?php
// admin/mahasiswa/get_mahasiswa.php
require_once '../../config/database.php';

$id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("SELECT * FROM mahasiswa WHERE id_mahasiswa = ?");
$stmt->execute([$id]);
$mhs = $stmt->fetch();

if (!$mhs) {
    echo "<div class='alert alert-danger small m-0'>Data mahasiswa tidak ditemukan.</div>";
    exit;
}

$gender = (strtoupper($mhs['jenis_kelamin'] ?? 'L') === 'L') ? 'Laki-laki' : 'Perempuan';
$warna_gender = ($gender === 'Laki-laki') ? 'text-primary' : 'text-danger';

$raw_tgl = $mhs['tanggal_lahir'] ?? '';
$tgl_format = (!empty($raw_tgl)) ? date('d F Y', strtotime($raw_tgl)) : '-';
$ttl = htmlspecialchars($mhs['tempat_lahir'] ?? '-') . ', ' . $tgl_format;

$angkatan_mhs = '20' . substr($mhs['nim'], 0, 2);
$ipk = number_format($mhs['ipk'] ?? 0.00, 2);
$semester = $mhs['semester_saat_ini'] ?? '1'; 
?>

<div class="text-center mb-4">
    <img src="../../uploads/profile/<?= htmlspecialchars($mhs['foto'] ?: 'default.png'); ?>" 
         class="rounded-circle img-thumbnail shadow-sm" 
         style="width: 100px; height: 100px; object-fit: cover;"
         onerror="this.src='../../uploads/profile/default.png'">
    <h6 class="fw-bold text-navy mt-3 mb-0"><?= htmlspecialchars($mhs['nama_mahasiswa']); ?></h6>
    <span class="badge bg-light text-secondary border px-2 py-1 mt-1 small">NIM. <?= htmlspecialchars($mhs['nim']); ?></span>
</div>

<div class="row g-3 border-top pt-3 text-start">
    <div class="col-6">
        <label class="text-muted d-block small mb-0" style="font-size: 10px; font-weight: 700;">JENIS KELAMIN</label>
        <span class="fw-semibold <?= $warna_gender; ?> small"><i class="fa-solid fa-venus-mars me-1"></i> <?= $gender; ?></span>
    </div>
    <div class="col-6">
        <label class="text-muted d-block small mb-0" style="font-size: 10px; font-weight: 700;">IPK TERAKHIR</label>
        <span class="fw-semibold text-success small"><i class="fa-solid fa-star text-warning me-1"></i> <?= $ipk; ?></span>
    </div>
    <div class="col-6">
        <label class="text-muted d-block small mb-0" style="font-size: 10px; font-weight: 700;">SEMESTER BERJALAN</label>
        <span class="fw-semibold text-dark small"><i class="fa-solid fa-graduation-cap text-muted me-1"></i> Semester <?= $semester; ?></span>
    </div>
    <div class="col-6">
        <label class="text-muted d-block small mb-0" style="font-size: 10px; font-weight: 700;">ANGKATAN KULIAH</label>
        <span class="fw-semibold text-dark small"><i class="fa-solid fa-calendar-days text-muted me-1"></i> Tahun <?= $angkatan_mhs; ?></span>
    </div>
    <div class="col-12">
        <label class="text-muted d-block small mb-0" style="font-size: 10px; font-weight: 700;">PROGRAM STUDI</label>
        <span class="fw-semibold text-dark small"><i class="fa-solid fa-book-bookmark text-muted me-1"></i> <?= htmlspecialchars($mhs['prodi'] ?? '-'); ?></span>
    </div>
    <div class="col-12">
        <label class="text-muted d-block small mb-0" style="font-size: 10px; font-weight: 700;">EMAIL INSTITUSI</label>
        <span class="fw-semibold text-dark small font-monospace" style="font-size: 12px;"><i class="fa-solid fa-envelope text-muted me-1"></i> <?= htmlspecialchars($mhs['email'] ?? '-'); ?></span>
    </div>

    <div class="col-12">
        <label class="text-muted d-block small mb-0" style="font-size: 10px; font-weight: 700;">TEMPAT, TANGGAL LAHIR</label>
        <span class="fw-semibold text-dark small"><i class="fa-solid fa-cake-candles text-warning me-1"></i> <?= $ttl; ?></span>
    </div>
    <div class="col-12">
        <label class="text-muted d-block small mb-0" style="font-size: 10px; font-weight: 700;">ALAMAT RUMAH</label>
        <span class="fw-semibold text-dark small"><i class="fa-solid fa-map-location-dot text-danger me-1"></i> <?= htmlspecialchars($mhs['alamat'] ?? '-'); ?></span>
    </div>
</div>

<div class="text-end border-top pt-3 mt-3">
    <button type="button" class="btn btn-warning text-dark btn-sm rounded-pill px-4 fw-bold shadow-sm" id="btn-buka-edit-modal"
            data-id="<?= $mhs['id_mahasiswa']; ?>"
            data-nama="<?= htmlspecialchars($mhs['nama_mahasiswa']); ?>"
            data-jk="<?= htmlspecialchars($mhs['jenis_kelamin'] ?? 'L'); ?>"
            data-tempat="<?= htmlspecialchars($mhs['tempat_lahir'] ?? ''); ?>"
            data-tgl="<?= htmlspecialchars($mhs['tanggal_lahir'] ?? ''); ?>"
            data-alamat="<?= htmlspecialchars($mhs['alamat'] ?? ''); ?>"
            data-semester="<?= htmlspecialchars($mhs['semester_saat_ini'] ?? '1'); ?>"
            data-ipk="<?= htmlspecialchars($mhs['ipk'] ?? '0.00'); ?>"
            data-status="<?= htmlspecialchars($mhs['status_mahasiswa'] ?? 'Aktif'); ?>"
            data-prodi="<?= htmlspecialchars($mhs['prodi'] ?? ''); ?>">
        <i class="fa-solid fa-user-gear me-1"></i> Edit Data
    </button>
</div>