<?php
// siakad/mahasiswa/akademik/khs.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// PERBAIKAN PATH: Menyesuaikan dengan struktur folder SIAKAD Anda
require_once __DIR__ . '/../../templates/header.php';
require_once __DIR__ . '/../templates/sidebar.php';
require_once __DIR__ . '/../../config/database.php';

$tabel_khs          = 'khs';              
$tabel_mk           = 'mata_kuliah';      
$kolom_id_mhs_khs   = 'id_user_mahasiswa';
$kolom_id_mk_khs    = 'id_mk';            
$kolom_nilai_huruf  = 'nilai_huruf';      

$id_user_mhs = $_SESSION['id_user'] ?? 0;
$sem_aktif = isset($_GET['semester']) ? (int)$_GET['semester'] : 1;

$daftar_nilai = [];
$total_sks_semester = 0;
$total_bobot_semester = 0;
$total_sks_kumulatif = 0;
$total_bobot_kumulatif = 0;

try {
    $query_khs = "SELECT k.*, mk.nama_mk, mk.kode_mk, mk.sks 
                  FROM $tabel_khs k 
                  JOIN $tabel_mk mk ON k.$kolom_id_mk_khs = mk.id_mk 
                  WHERE k.$kolom_id_mhs_khs = ? AND mk.semester = ?";
    $stmt_khs = $pdo->prepare($query_khs);
    $stmt_khs->execute([$id_user_mhs, $sem_aktif]);
    $daftar_nilai = $stmt_khs->fetchAll(PDO::FETCH_ASSOC);

    $query_all = "SELECT k.$kolom_nilai_huruf, mk.sks 
                  FROM $tabel_khs k 
                  JOIN $tabel_mk mk ON k.$kolom_id_mk_khs = mk.id_mk 
                  WHERE k.$kolom_id_mhs_khs = ?";
    $stmt_all = $pdo->prepare($query_all);
    $stmt_all->execute([$id_user_mhs]);
    $semua_nilai = $stmt_all->fetchAll(PDO::FETCH_ASSOC);

    function konversiBobot($huruf) {
        switch (strtoupper(trim($huruf))) {
            case 'A': return 4.0;
            case 'B': return 3.0;
            case 'C': return 2.0;
            case 'D': return 1.0;
            default: return 0.0;
        }
    }

    foreach ($daftar_nilai as $n) {
        $bobot = konversiBobot($n[$kolom_nilai_huruf]);
        $total_sks_semester += $n['sks'];
        $total_bobot_semester += ($bobot * $n['sks']);
    }
    $ips = $total_sks_semester > 0 ? round($total_bobot_semester / $total_sks_semester, 2) : 0.00;

    foreach ($semua_nilai as $sn) {
        $bobot_k = konversiBobot($sn[$kolom_nilai_huruf]);
        $total_sks_kumulatif += $sn['sks'];
        $total_bobot_kumulatif += ($bobot_k * $sn['sks']);
    }
    $ipk = $total_sks_kumulatif > 0 ? round($total_bobot_kumulatif / $total_sks_kumulatif, 2) : 0.00;

} catch (Exception $e) {
    $ips = 0.00; $ipk = 0.00;
}
?>

<div class="container-fluid px-4 py-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold mb-1" style="color: #245358;">Kartu Hasil Studi (KHS)</h4>
            <p class="text-muted small mb-0">Rincian perolehan nilai akademik resmi Anda per semester.</p>
        </div>
        <button onclick="window.print()" class="btn text-white rounded-3 shadow-sm px-4" style="background-color: #245358;">
            <i class="fa-solid fa-print me-2"></i> Cetak KHS
        </button>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm p-3 bg-white rounded-3 border-start border-4 border-success">
                <small class="text-muted text-uppercase d-block mb-1" style="font-size: 10px; font-weight:700;">IP Semester (IPS)</small>
                <h3 class="fw-bold m-0 text-success"><?= number_format($ips, 2); ?></h3>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm p-3 bg-white rounded-3 border-start border-4 border-primary">
                <small class="text-muted text-uppercase d-block mb-1" style="font-size: 10px; font-weight:700;">IP Kumulatif (IPK)</small>
                <h3 class="fw-bold m-0 text-primary"><?= number_format($ipk, 2); ?></h3>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm p-3 bg-white rounded-3 border-start border-4 border-warning">
                <small class="text-muted text-uppercase d-block mb-1" style="font-size: 10px; font-weight:700;">SKS Semester</small>
                <h3 class="fw-bold m-0 text-warning"><?= $total_sks_semester; ?> SKS</h3>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm p-3 bg-white rounded-3 border-start border-4 border-info">
                <small class="text-muted text-uppercase d-block mb-1" style="font-size: 10px; font-weight:700;">Total SKS Lulus</small>
                <h3 class="fw-bold m-0 text-info"><?= $total_sks_kumulatif; ?> SKS</h3>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-3 bg-white">
        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
            <h6 class="mb-0 fw-bold" style="color: #245358;"><i class="fa-solid fa-graduation-cap me-2"></i>Daftar Nilai</h6>
            <form method="GET">
                <select name="semester" class="form-select form-select-sm rounded-3" onchange="this.form.submit()" style="width: 140px; border-color: #245358;">
                    <?php for($i=1; $i<=8; $i++): ?>
                        <option value="<?= $i; ?>" <?= $sem_aktif == $i ? 'selected' : ''; ?>>Semester <?= $i; ?></option>
                    <?php endfor; ?>
                </select>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0 table-hover">
                    <thead class="table-light text-uppercase" style="font-size: 11px;">
                        <tr>
                            <th class="ps-4 py-3">No</th>
                            <th>Kode MK</th>
                            <th>Nama Mata Kuliah</th>
                            <th class="text-center">SKS</th>
                            <th class="text-center">Nilai Huruf</th>
                        </tr>
                    </thead>
                    <tbody style="font-size: 13.5px;">
                        <?php if(!empty($daftar_nilai)): $no=1; foreach($daftar_nilai as $row): ?>
                            <tr class="border-bottom border-light">
                                <td class="ps-4 text-muted"><?= $no++; ?></td>
                                <td class="text-secondary fw-mono"><?= htmlspecialchars($row['kode_mk']); ?></td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($row['nama_mk']); ?></td>
                                <td class="text-center"><?= $row['sks']; ?> SKS</td>
                                <td class="text-center">
                                    <span class="badge px-3 py-2 rounded-3 <?= in_array($row[$kolom_nilai_huruf], ['A','B','C']) ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'; ?>">
                                        <?= htmlspecialchars($row[$kolom_nilai_huruf]); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-folder-open d-block fs-2 mb-2 opacity-25"></i>
                                    Data nilai untuk semester <?= $sem_aktif; ?> belum tersedia.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>