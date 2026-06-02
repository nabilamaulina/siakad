<?php
// dosen/tugas_akhir/bimbingan_skripsi.php
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}
require_once __DIR__ . '/../templates/header.php'; 
require_once __DIR__ . '/../templates/sidebar.php'; 
?>
<div class="mb-4">
    <h4 class="fw-bold text-dark mb-1">Monitoring Bimbingan Skripsi & TA</h4>
    <p class="text-muted small mb-0">Pantau progres penulisan bab, berkas bimbingan, dan syarat kelayakan sidang.</p>
</div>
<div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
    <img src="https://cdn-icons-png.flaticon.com/512/9053/9053741.png" style="width: 65px; opacity: 0.3;" class="mb-3 mx-auto" alt="No Data">
    <h6 class="text-muted fw-bold mb-1">Data Mahasiswa TA Belum Tersedia</h6>
    <p class="text-muted small mb-0">Belum ada rekap pengajuan judul atau mahasiswa tingkat akhir yang memilih Anda sebagai pembimbing.</p>
</div>
<?php require_once __DIR__ . '/../templates/footer.php'; ?>