<?php
// dosen/kinerja_dosen/bkd_lkd.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../templates/header.php'; 
require_once __DIR__ . '/../templates/sidebar.php'; 
?>
<div class="mb-4">
    <h4 class="fw-bold text-dark mb-1">Laporan Beban Kerja Dosen (BKD / LKD)</h4>
    <p class="text-muted small mb-0">Instrumen evaluasi pemenuhan kewajiban Tridharma Perguruan Tinggi.</p>
</div>
<div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
    <div class="p-3 bg-light rounded-circle mb-3 d-inline-block mx-auto text-success"><i class="fa-solid fa-file-invoice fa-xl"></i></div>
    <h6 class="text-muted fw-bold mb-1">Integrasi Sistem Sinkron</h6>
    <p class="text-muted small mb-0">Laporan pemenuhan SKS pendidikan terhitung otomatis dari manajemen jadwal perkuliahan Anda.</p>
</div>
<?php require_once __DIR__ . '/../templates/footer.php'; ?>