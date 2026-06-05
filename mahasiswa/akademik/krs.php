<?php
// siakad/mahasiswa/akademik/krs.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// =========================================================================
// 📥 PANGGIL FILE HEADER & DATABASE
// =========================================================================
require_once __DIR__ . '/../../templates/header.php';
require_once __DIR__ . '/../templates/sidebar.php';

// Mengambil username/NIM yang digunakan saat login
$session_username = $_SESSION['username'] ?? '';
$id_user_login   = $_SESSION['id_user'] ?? 0;

$msg_status = '';
$msg_text = '';

// =========================================================================
// 🔍 AMBIL DATA PROFIL BERDASARKAN USERNAME/NIM LOGIN
// =========================================================================
$id_mahasiswa_asli = 0;
$semester_mahasiswa = '2'; // Default jika data tidak ditemukan

if (!empty($session_username)) {
    try {
        // Cari data berdasarkan nim yang sama dengan username login
        $stmt_profile = $pdo->prepare("SELECT id_mahasiswa, nama_mahasiswa, semester_saat_ini FROM mahasiswa WHERE nim = ? LIMIT 1");
        $stmt_profile->execute([$session_username]);
        $profile = $stmt_profile->fetch(PDO::FETCH_ASSOC);

        // Jika tidak ketemu berdasarkan NIM, cari berdasarkan id_user
        if (!$profile && $id_user_login > 0) {
            $stmt_profile = $pdo->prepare("SELECT id_mahasiswa, nama_mahasiswa, semester_saat_ini FROM mahasiswa WHERE id_user = ? LIMIT 1");
            $stmt_profile->execute([$id_user_login]);
            $profile = $stmt_profile->fetch(PDO::FETCH_ASSOC);
        }

        if ($profile) {
            $id_mahasiswa_asli = $profile['id_mahasiswa'];

            // 1. Sinkronisasi nama di header agar tetap nama asli mahasiswa
            if (!empty($profile['nama_mahasiswa'])) {
                $_SESSION['nama'] = $profile['nama_mahasiswa'];
            }
            // 2. Ambil semester aktif dari kolom 'semester_saat_ini'
            if (!empty($profile['semester_saat_ini'])) {
                $semester_mahasiswa = $profile['semester_saat_ini'];
            }
        }
    } catch (Exception $e) {
        // Abaikan jika error query agar tidak merusak template
    }
}

// =========================================================================
// 🔄 DETEKSI SEMESTER AKTIF KAMPUS (GANJIL / GENAP)
// =========================================================================
$jenis_semester_aktif = 'Genap'; // Default fallback jika tidak terdeteksi
$id_semester_valid = null;

try {
    // Ambil data semester yang berstatus aktif
    $stmt_sem_aktif = $pdo->query("SELECT id_semester, nama_semester FROM semester WHERE status = 'aktif' OR status = 'Aktif' LIMIT 1");
    $sem_aktif_data = $stmt_sem_aktif->fetch(PDO::FETCH_ASSOC);
    
    if (!$sem_aktif_data) {
        $stmt_sem_aktif = $pdo->query("SELECT id_semester, nama_semester FROM semester LIMIT 1");
        $sem_aktif_data = $stmt_sem_aktif->fetch(PDO::FETCH_ASSOC);
    }

    if ($sem_aktif_data) {
        $id_semester_valid = $sem_aktif_data['id_semester'];
        // Cek apakah nama semester mengandung kata 'Ganjil'
        if (stripos($sem_aktif_data['nama_semester'], 'Ganjil') !== false) {
            $jenis_semester_aktif = 'Ganjil';
        } else {
            $jenis_semester_aktif = 'Genap';
        }
    }
} catch (Exception $e) {
    // Biarkan fallback berjalan jika error
}

// Filter semester yang dipilih oleh mahasiswa via dropdown (1 - 8)
$sem_filter = isset($_GET['semester']) ? (int)$_GET['semester'] : (int)$semester_mahasiswa;

// 🔒 VALIDASI KESESUAIAN TIPE SEMESTER (Ganjil/Genap)
// Cek apakah pilihan mahasiswa ganjil/genap-nya klop dengan semester aktif kampus
$is_pilihan_ganjil = ($sem_filter % 2 !== 0); // true jika pilih semester 1,3,5,7

$tampilkan_data = false;
if ($jenis_semester_aktif === 'Ganjil' && $is_pilihan_ganjil) {
    $tampilkan_data = true; // Musim ganjil, mahasiswa pilih ganjil -> OK
} elseif ($jenis_semester_aktif === 'Genap' && !$is_pilihan_ganjil) {
    $tampilkan_data = true; // Musim genap, mahasiswa pilih genap -> OK
}

// =========================================================================
// 📥 PROSES AKSI: TAMBAH MATAKULIAH (CREATE)
// =========================================================================
if (isset($_POST['action_ambil_mk'])) {
    $id_jadwal = (int)($_POST['id_jadwal'] ?? 0);

    if ($id_mahasiswa_asli == 0 || $id_user_login == 0) {
        $msg_status = 'danger';
        $msg_text = 'Gagal mengambil data autentikasi. Sesi login Anda tidak terelasi dengan benar di database.';
    } else {
        try {
            if (!$id_semester_valid) {
                throw new Exception("Data master semester aktif tidak ditemukan di database.");
            }

            // Cek duplikasi KRS berdasarkan id_mahasiswa & id_jadwal
            $stmt_cek = $pdo->prepare("SELECT id_krs FROM krs WHERE id_mahasiswa = ? AND id_jadwal = ?");
            $stmt_cek->execute([$id_mahasiswa_asli, $id_jadwal]);

            if ($stmt_cek->rowCount() > 0) {
                $msg_status = 'warning';
                $msg_text = 'Jadwal mata kuliah ini sudah ada di dalam lembar KRS Anda.';
            } else {
                $stmt_add = $pdo->prepare("
                    INSERT INTO krs (
                        id_user,
                        id_mahasiswa,
                        id_jadwal,
                        tahun_akademik,
                        id_semester,
                        status_krs,
                        status_validasi
                    ) VALUES (?, ?, ?, ?, ?, ?, ?)
                ");

                $stmt_add->execute([
                    $id_user_login,
                    $id_mahasiswa_asli,
                    $id_jadwal,
                    '2025/2026-' . $jenis_semester_aktif,
                    $id_semester_valid,
                    'pending',
                    'Pending'
                ]);

                $msg_status = 'success';
                $msg_text = 'Mata kuliah berhasil ditambahkan ke KRS.';
            }
        } catch (Exception $e) {
            $msg_status = 'danger';
            $msg_text = 'Gagal menambahkan mata kuliah: ' . $e->getMessage();
        }
    }
}

// =========================================================================
// 📥 PROSES AKSI: BATAL / HAPUS MATAKULIAH (DELETE)
// =========================================================================
if (isset($_POST['action_batal_mk'])) {
    $id_krs = (int)($_POST['id_krs'] ?? 0);
    try {
        $stmt_del = $pdo->prepare("DELETE FROM krs WHERE id_krs = ? AND id_mahasiswa = ?");
        $stmt_del->execute([$id_krs, $id_mahasiswa_asli]);
        
        $msg_status = 'success';
        $msg_text = 'Mata kuliah berhasil dihapus dari KRS Anda.';
    } catch (Exception $e) {
        $msg_status = 'danger';
        $msg_text = 'Gagal membatalkan mata kuliah: ' . $e->getMessage();
    }
}

// =========================================================================
// 📥 AMBIL DATA MATA KULIAH DITAWARKAN & KRS Diambil
// =========================================================================
$available_mk = [];
$taken_krs = [];
try {
    // Hanya lakukan query ke database JIKA pilihan semester klop dengan tahun pembelajaran berjalan
    if ($tampilkan_data === true) {
        $stmt_av = $pdo->prepare("
            SELECT
                j.id_jadwal,
                j.hari,
                j.jam_mulai,
                j.jam_selesai,
                j.ruangan,
                d.nama_dosen,
                mk.id_mk,
                mk.kode_mk,
                mk.nama_mk,
                mk.sks,
                mk.semester
            FROM jadwal j
            JOIN mata_kuliah mk ON j.id_mk = mk.id_mk
            LEFT JOIN dosen d ON j.id_dosen = d.id_dosen
            WHERE mk.semester = ?
            ORDER BY mk.nama_mk ASC
        ");
        $stmt_av->execute([$sem_filter]);
        $available_mk = $stmt_av->fetchAll(PDO::FETCH_ASSOC);
    }

    // Ambil data KRS yang sudah diambil oleh mahasiswa (ini tetap muncul)
    if ($id_mahasiswa_asli > 0) {
        $stmt_tk = $pdo->prepare("
            SELECT
                k.id_krs,
                k.status_validasi,
                mk.nama_mk,
                mk.kode_mk,
                mk.sks
            FROM krs k
            JOIN jadwal j ON k.id_jadwal = j.id_jadwal
            JOIN mata_kuliah mk ON j.id_mk = mk.id_mk
            WHERE k.id_mahasiswa = ?
        ");
        $stmt_tk->execute([$id_mahasiswa_asli]);
        $taken_krs = $stmt_tk->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $msg_status = 'danger';
    $msg_text = 'Terjadi kesalahan pemuatan data database: ' . $e->getMessage();
}
?>

<?php if (!empty($msg_text)): ?>
    <div class="alert alert-<?= $msg_status; ?> alert-dismissible fade show rounded-3 shadow-sm mb-4">
        <i class="fa-solid fa-circle-info me-2"></i><?= $msg_text; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-xl-7">
        <div class="card border-0 shadow-sm bg-white rounded-3">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <h6 class="mb-0 fw-bold" style="color:#245358;"><i class="fa-solid fa-list-check me-2"></i>Mata Kuliah Ditawarkan</h6>
                    <span class="badge bg-success-subtle text-success border border-success-subtle">Periode <?= $jenis_semester_aktif; ?></span>
                </div>
                <form method="GET" id="formSemester">
                    <select name="semester" class="form-select form-select-sm fw-bold" onchange="document.getElementById('formSemester').submit();" style="width: 140px; border-color: #245358;">
                        <?php for ($i = 1; $i <= 8; $i++): ?>
                            <option value="<?= $i; ?>" <?= $sem_filter === $i ? 'selected' : ''; ?>>Semester <?= $i; ?></option>
                        <?php endfor; ?>
                    </select>
                </form>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 520px;">
                    <table class="table align-middle mb-0">
                        <thead class="table-light text-muted" style="font-size:11px;">
                            <tr>
                                <th class="ps-4">Mata Kuliah</th>
                                <th class="text-center">SKS</th>
                                <th class="pe-4 text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody style="font-size:13px;">
                            <?php if ($tampilkan_data === true && !empty($available_mk)): foreach ($available_mk as $row_mk): ?>
                                    <tr class="border-bottom border-light">
                                        <td class="ps-4">
                                            <span class="fw-bold text-dark d-block"><?= htmlspecialchars($row_mk['nama_mk']); ?></span>
                                            <small class="text-muted">Kode: <?= htmlspecialchars($row_mk['kode_mk']); ?> | Dosen: <?= htmlspecialchars($row_mk['nama_dosen'] ?? 'Belum Ditentukan'); ?> | Ruang: <?= htmlspecialchars($row_mk['ruangan'] ?? '-'); ?></small>
                                        </td>
                                        <td class="text-center fw-bold" style="color: #245358;"><?= (int)$row_mk['sks']; ?> SKS</td>
                                        <td class="pe-4 text-end">
                                            <form method="POST">
                                                <input type="hidden" name="id_jadwal" value="<?= $row_mk['id_jadwal']; ?>">
                                                <button type="submit" name="action_ambil_mk" class="btn btn-sm text-white rounded-3 px-3 fw-semibold shadow-sm" style="background-color:#245358;"><i class="fa-solid fa-plus me-1"></i> Ambil</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach;
                            else: ?>
                                <tr>
                                    <td colspan="3" class="text-center py-5 text-muted">
                                        <i class="fa-solid fa-folder-open d-block fs-2 mb-2 text-muted"></i>
                                        Mata kuliah tidak ditawarkan pada periode semester <strong><?= $jenis_semester_aktif; ?></strong> saat ini.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-5">
        <div class="card border-0 shadow-sm bg-white rounded-3">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="mb-0 fw-bold text-dark"><i class="fa-solid fa-basket-shopping me-2"></i>KRS Diambil</h6>
            </div>
            <div class="card-body p-0">
                <table class="table align-middle mb-0">
                    <tbody style="font-size:13px;">
                        <?php $total_sks = 0;
                        if (!empty($taken_krs)): foreach ($taken_krs as $tk): $total_sks += $tk['sks']; ?>
                                <tr class="border-bottom border-light">
                                    <td class="ps-3">
                                        <strong><?= htmlspecialchars($tk['nama_mk']); ?></strong><br>
                                        <small class="fw-bold text-primary"><?= $tk['sks']; ?> SKS</small>
                                    </td>
                                    <td>
                                        <?php if (strtolower($tk['status_validasi'] ?? '') == 'disetujui'): ?>
                                            <span class="badge rounded-pill bg-success-subtle text-success">Disetujui</span>
                                        <?php else: ?>
                                            <span class="badge rounded-pill bg-warning-subtle text-warning">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="pe-3 text-end">
                                        <form method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan mata kuliah ini?');">
                                            <input type="hidden" name="id_krs" value="<?= $tk['id_krs']; ?>">
                                            <button type="submit" name="action_batal_mk" class="btn btn-link text-danger p-0 border-0"><i class="fa-solid fa-trash-can"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach;
                        else: ?>
                            <tr>
                                <td colspan="3" class="text-center py-5 text-muted">Belum ada mata kuliah yang diambil.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-light fw-bold text-end p-3">Total Kredit: <span class="text-success"><?= $total_sks; ?> / 24 SKS</span></div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../templates/footer.php';
?>