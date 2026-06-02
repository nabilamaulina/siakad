<?php
// dosen/tugas_akhir/jadwal_sidang.php
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}
require_once __DIR__ . '/../templates/header.php'; 
require_once __DIR__ . '/../templates/sidebar.php'; 
?>
<div class="mb-4">
    <h4 class="fw-bold text-dark mb-1">Jadwal Penguji Sidang & Seminar</h4>
    <p class="text-muted small mb-0">Daftar agenda uji komprehensif, seminar hasil, dan sidang meja hijau.</p>
</div>
<div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
    <div class="p-3 bg-light rounded-circle mb-3 d-inline-block mx-auto text-secondary"><i class="fa-solid fa-gavel fa-xl"></i></div>
    <h6 class="text-muted fw-bold mb-1">Belum Ada Plot Penguji</h6>
    <p class="text-muted small mb-0">Jadwal pengujian dari program studi belum diterbitkan untuk periode wisuda ini.</p>
</div>
<?php require_once __DIR__ . '/../templates/footer.php'; ?>