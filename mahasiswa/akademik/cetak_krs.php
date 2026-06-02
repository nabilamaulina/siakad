<?php
// siakad/mahasiswa/akademik/cetak_krs.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Pembacaan Database Utama Mundur 2 Tingkat
require_once __DIR__ . '/../../config/database.php';

$id_user_mhs = $_SESSION['id_user'] ?? 0;

try {
    // Tarik data profil Mahasiswa
    $stmt_mhs = $pdo->prepare("SELECT m.*, u.nama_user FROM mahasiswa m JOIN user u ON m.id_user = u.id_user WHERE m.id_user = ?");
    $stmt_mhs->execute([$id_user_mhs]);
    $mhs = $stmt_mhs->fetch(PDO::FETCH_ASSOC);

    // Ambil data draf KRS yang sudah 'Disetujui' oleh dosen wali
    $stmt_krs = $pdo->prepare("SELECT mk.kode_mk, mk.nama_mk, mk.sks, mk.semester FROM krs k JOIN mata_kuliah mk ON k.id_mk = mk.id_mk WHERE k.id_user_mahasiswa = ? AND k.status_validasi = 'Disetujui'");
    $stmt_krs->execute([$id_user_mhs]);
    $items = $stmt_krs->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Fallback Mock Data jika query database kosong / error
    $mhs = ['nama_mahasiswa' => $_SESSION['nama_user'] ?? 'Mahasiswa Demo', 'nim' => $_SESSION['username'] ?? '12345678'];
    $items = [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>KRS_<?= $mhs['nim'] ?? 'MHS'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Times New Roman', Times, serif; background-color: #fff; color: #000; }
        .kop-surat { border-bottom: 3px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
        @media print {
            .no-print { display: none !important; }
            body { margin: 0; padding: 0; }
        }
    </style>
</head>
<body onload="window.print()">

<div class="container my-4 no-print text-center">
    <button onclick="window.print()" class="btn btn-dark px-4 py-2"><i class="bi bi-printer"></i> Cetak Sekarang</button>
    <hr>
</div>

<div class="container" style="max-width: 900px;">
    <div class="text-center kop-surat">
        <h4 class="fw-bold mb-0">UNIVERSITAS SISTER INFORMASI AKADEMIK</h4>
        <h5 class="fw-bold mb-1">FAKULTAS ILMU KOMPUTER</h5>
        <p class="mb-0 small text-muted">Jl. Kampus Terpadu, Gedung Rekayasa Sistem, Cloud Computing Center</p>
    </div>

    <h5 class="text-center fw-bold text-uppercase mb-4">KARTU RENCANA STUDI (KRS)</h5>

    <table class="table table-borderless sm-table mb-3" style="font-size: 14px;">
        <tr>
            <td width="15%">NAMA</td><td>: <strong><?= htmlspecialchars($mhs['nama_mahasiswa'] ?? ($mhs['nama_user'] ?? '')); ?></strong></td>
            <td width="15%">SEMESTER</td><td>: GENAP</td>
        </tr>
        <tr>
            <td>NIM</td><td>: <?= htmlspecialchars($mhs['nim'] ?? '-'); ?></td>
            <td>PRODI</td><td>: SISTEM INFORMASI (S1)</td>
        </tr>
    </table>

    <table class="table table-bordered align-middle" style="font-size: 13px;">
        <thead class="table-light text-center">
            <tr>
                <th width="5%">NO</th>
                <th width="15%">KODE</th>
                <th>MATA KULIAH</th>
                <th width="10%">SEMESTER</th>
                <th width="10%">KREDIT</th>
            </tr>
        </thead>
        <tbody>
            <?php $no=1; $total_sks=0; if(!empty($items)): foreach($items as $i): $total_sks+=$i['sks']; ?>
                <tr>
                    <td class="text-center"><?= $no++; ?></td>
                    <td class="text-center"><?= htmlspecialchars($i['kode_mk']); ?></td>
                    <td><?= htmlspecialchars($i['nama_mk']); ?></td>
                    <td class="text-center"><?= $i['semester']; ?></td>
                    <td class="text-center fw-bold"><?= $i['sks']; ?></td>
                </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="5" class="text-center py-3 text-muted">Belum ada mata kuliah yang disetujui untuk semester ini.</td></tr>
            <?php endif; ?>
            <tr class="table-light fw-bold">
                <td colspan="4" class="text-end pe-3">TOTAL KREDIT SKS YANG DIAMBIL:</td>
                <td class="text-center text-primary"><?= $total_sks; ?> SKS</td>
            </tr>
        </tbody>
    </table>

    <div class="row mt-5 text-center" style="font-size: 14px;">
        <div class="col-4">
            <p class="mb-5">Dosen Penasihat Akademik,</p>
            <p class="mb-0 text-decoration-underline fw-bold">_______________________</p>
            <small class="text-muted">NIDN. </small>
        </div>
        <div class="col-4"></div>
        <div class="col-4">
            <p class="mb-5">Pekanbaru, <?= date('d F Y'); ?><br>Mahasiswa Bersangkutan,</p>
            <p class="mb-0 text-decoration-underline fw-bold"><?= htmlspecialchars($mhs['nama_mahasiswa'] ?? ''); ?></p>
            <small class="text-muted">NIM. <?= htmlspecialchars($mhs['nim'] ?? ''); ?></small>
        </div>
    </div>
</div>

</body>
</html>