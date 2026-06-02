<?php
// admin/mahasiswa/proses.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Akses tidak sah!']);
    exit;
}

$csrf_token = $_POST['csrf_token'] ?? '';
if (empty($csrf_token) || $csrf_token !== ($_SESSION['csrf_token'] ?? '')) {
    echo json_encode(['status' => 'error', 'message' => 'Sesi keamanan kedaluwarsa, silakan refresh halaman.']);
    exit;
}

$action = $_POST['action'] ?? '';

// ==========================================
// A. PROSES TAMBAH MAHASISWA BARU (CREATE)
// ==========================================
if ($action === 'create') {

    $nim = trim($_POST['nim'] ?? '');
    $nama_mahasiswa = trim($_POST['nama_mahasiswa'] ?? '');
    $jenis_kelamin = $_POST['jenis_kelamin'] ?? 'L';
    $tempat_lahir = trim($_POST['tempat_lahir'] ?? '');
    $tanggal_lahir = $_POST['tanggal_lahir'] ?? null;
    $alamat = trim($_POST['alamat'] ?? '');
    $ipk = $_POST['ipk'] ?? 0.00;
    $semester = $_POST['semester_saat_ini'] ?? 1;

    $jurusan = $_POST['jurusan'] ?? 'Ilmu Komputer';
    $prodi = $_POST['prodi'] ?? '';
    $status_mahasiswa = $_POST['status_mahasiswa'] ?? 'Aktif';

    if (empty($nim) || empty($nama_mahasiswa) || empty($prodi)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'NIM, Nama Mahasiswa, dan Prodi wajib diisi!'
        ]);
        exit;
    }

    try {

        // Cek NIM mahasiswa
        $checkMhs = $pdo->prepare("
            SELECT id_mahasiswa
            FROM mahasiswa
            WHERE nim = ?
        ");
        $checkMhs->execute([$nim]);

        if ($checkMhs->rowCount() > 0) {
            echo json_encode([
                'status' => 'error',
                'message' => 'NIM sudah terdaftar!'
            ]);
            exit;
        }

        // Cek username users
        $checkUser = $pdo->prepare("
            SELECT id_user
            FROM users
            WHERE username = ?
        ");
        $checkUser->execute([$nim]);

        if ($checkUser->rowCount() > 0) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Username sudah digunakan!'
            ]);
            exit;
        }

        // Email otomatis
        $nama_clean = strtolower(str_replace(' ', '', $nama_mahasiswa));
        $nim_4_akhir = substr($nim, -4);

        $email_otomatis =
            $nama_clean .
            $nim_4_akhir .
            '@student.unri.ac.id';

        // Mulai transaksi
        $pdo->beginTransaction();

        // Password default = NIM
        $password_hash = password_hash(
            $nim,
            PASSWORD_DEFAULT
        );

        // INSERT USERS
        $stmtUser = $pdo->prepare("
            INSERT INTO users
            (
                username,
                password,
                role,
                is_active
            )
            VALUES
            (
                ?,
                ?,
                'mahasiswa',
                1
            )
        ");

        $stmtUser->execute([
            $nim,
            $password_hash
        ]);

        $id_user_baru = $pdo->lastInsertId();

        // INSERT MAHASISWA
        $stmtMhs = $pdo->prepare("
            INSERT INTO mahasiswa
            (
                id_user,
                nim,
                nama_mahasiswa,
                jenis_kelamin,
                tempat_lahir,
                tanggal_lahir,
                alamat,
                foto,
                id_semester_masuk,
                ipk,
                semester_saat_ini,
                email,
                status_mahasiswa,
                jurusan,
                prodi
            )
            VALUES
            (
                ?,?,?,?,?,?,?,?,?,?,?,?,?,?,?
            )
        ");

        $stmtMhs->execute([
            $id_user_baru,
            $nim,
            $nama_mahasiswa,
            $jenis_kelamin,
            $tempat_lahir ?: 'Pekanbaru',
            $tanggal_lahir ?: date('Y-m-d'),
            $alamat ?: '-',
            'default.png',
            1,
            $ipk,
            $semester,
            $email_otomatis,
            $status_mahasiswa,
            $jurusan,
            $prodi
        ]);

        $pdo->commit();

        echo json_encode([
            'status' => 'success',
            'message' =>
                'Mahasiswa berhasil ditambahkan. Username: '
                . $nim .
                ' | Password awal: '
                . $nim
        ]);

    } catch (Exception $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }

    exit;
}

// ==========================================
// B. PROSES EDIT MAHASISWA (UPDATE)
// ==========================================
if ($action === 'update') {
    $id_mahasiswa = $_POST['id_mahasiswa'] ?? 0;
    $nama_mahasiswa = trim($_POST['nama_mahasiswa'] ?? '');
    $jenis_kelamin = $_POST['jenis_kelamin'] ?? 'L';
    $tempat_lahir = trim($_POST['tempat_lahir'] ?? '');
    $tanggal_lahir = $_POST['tanggal_lahir'] ?? null;
    $alamat = trim($_POST['alamat'] ?? '');
    $ipk = $_POST['ipk'] ?? 0.00;
    $semester_saat_ini = $_POST['semester_saat_ini'] ?? 1;
    $status_mahasiswa = $_POST['status_mahasiswa'] ?? 'Aktif';
    $prodi = $_POST['prodi'] ?? '';

    if (empty($id_mahasiswa) || empty($nama_mahasiswa) || empty($prodi)) {
        echo json_encode(['status' => 'error', 'message' => 'Data wajib edit tidak boleh kosong!']);
        exit;
    }

    try {
        // Ambil data lama mahasiswa untuk mengecek berkas foto / NIM lama
        $stmtOld = $pdo->prepare("SELECT foto, nim FROM mahasiswa WHERE id_mahasiswa = ?");
        $stmtOld->execute([$id_mahasiswa]);
        $oldData = $stmtOld->fetch();

        if (!$oldData) {
            echo json_encode(['status' => 'error', 'message' => 'Data mahasiswa tidak ditemukan!']);
            exit;
        }

        $nama_file_foto = $oldData['foto'] ?: 'default.png';

        // Kelola Unggah Foto Baru jika dikirimkan oleh form
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['foto']['tmp_name'];
            $fileName = $_FILES['foto']['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
            if (in_array($fileExtension, $allowedExtensions)) {
                $newFileName = 'mhs_' . $oldData['nim'] . '_' . time() . '.' . $fileExtension;
                $uploadFileDir = '../../uploads/mahasiswa/';
                
                if(!is_dir($uploadFileDir)){
                    mkdir($uploadFileDir, 0775, true);
                }

                $dest_path = $uploadFileDir . $newFileName;
                if(move_uploaded_file($fileTmpPath, $dest_path)) {
                    // Hapus foto lama jika bukan default.png
                    if ($nama_file_foto !== 'default.png' && file_exists($uploadFileDir . $nama_file_foto)) {
                        @unlink($uploadFileDir . $nama_file_foto);
                    }
                    $nama_file_foto = $newFileName;
                }
            }
        }

        // Generate ulang email otomatis berdasarkan perubahan nama baru agar tetap selaras
        $nama_clean = strtolower(str_replace(' ', '', $nama_mahasiswa));
        $nim_4_akhir = substr($oldData['nim'], -4);
        $email_otomatis = $nama_clean . $nim_4_akhir . '@student.unri.ac.id';

        $sqlUp = "UPDATE mahasiswa SET 
                    nama_mahasiswa = ?, jenis_kelamin = ?, tempat_lahir = ?, 
                    tanggal_lahir = ?, alamat = ?, foto = ?, 
                    ipk = ?, semester_saat_ini = ?, email = ?,
                    status_mahasiswa = ?, prodi = ?
                  WHERE id_mahasiswa = ?";
        
        $stmtUp = $pdo->prepare($sqlUp);
        $stmtUp->execute([
            $nama_mahasiswa, $jenis_kelamin, $tempat_lahir ?: 'Pekanbaru', 
            $tanggal_lahir ?: null, $alamat ?: '-', $nama_file_foto, 
            $ipk, $semester_saat_ini, $email_otomatis, 
            $status_mahasiswa, $prodi, $id_mahasiswa
        ]);

        echo json_encode(['status' => 'success', 'message' => 'Perubahan berhasil disimpan!']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui data: ' . $e->getMessage()]);
    }
    exit;
}

// ==========================================
// C. PROSES HAPUS MAHASISWA (DELETE)
// ==========================================
if ($action === 'delete') {
    $id_mahasiswa = $_POST['id_mahasiswa'] ?? 0;

    try {
        // Ambil info nama berkas foto sebelum dihapus untuk dibersihkan dari penyimpanan
        $stmtImg = $pdo->prepare("SELECT foto FROM mahasiswa WHERE id_mahasiswa = ?");
        $stmtImg->execute([$id_mahasiswa]);
        $mhsFoto = $stmtImg->fetchColumn();

        $delMhs = $pdo->prepare("DELETE FROM mahasiswa WHERE id_mahasiswa = ?");
        $delMhs->execute([$id_mahasiswa]);

        if ($mhsFoto && $mhsFoto !== 'default.png') {
            $pathFileFoto = '../../uploads/mahasiswa/' . $mhsFoto;
            if (file_exists($pathFileFoto)) {
                @unlink($pathFileFoto);
            }
        }

        echo json_encode(['status' => 'success', 'message' => 'Data mahasiswa dan berkas terkait berhasil dihapus permanen.']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus data: ' . $e->getMessage()]);
    }
    exit;
}