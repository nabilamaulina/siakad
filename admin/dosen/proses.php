<?php
// admin/dosen/proses.php
require_once '../../config/security.php';
require_once '../../config/database.php';
require_once '../../config/function.php';

middleware(['admin']);

// Cek Method Request dan Token CSRF jika diperlukan
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi token CSRF opsional sesuai function.php Anda
    // verify_csrf_token($_POST['csrf_token'] ?? '');
}

// 1. CREATE DOSEN BARU
if ($action === 'create') {
    $nidn       = sanitize($_POST['nidn']);
    $nama_dosen = sanitize($_POST['nama_dosen']);
    $jabatan    = sanitize($_POST['jabatan']);
    $email      = sanitize($_POST['email']);
    $no_hp      = sanitize($_POST['no_hp']);
    $password   = password_hash($nidn . "123", PASSWORD_DEFAULT);

    try {
        $pdo->beginTransaction();

        // Buat Akun User login terlebih dahulu
        $stmt_user = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'dosen')");
        $stmt_user->execute([$nidn, $password]);
        $id_user = $pdo->lastInsertId();

        // Buat Data Profile Dosen terikat dengan id_user
        $stmt_dosen = $pdo->prepare("INSERT INTO dosen (nidn, nama_dosen, jabatan, email, no_hp, id_user, foto) VALUES (?, ?, ?, ?, ?, ?, 'default.png')");
        $stmt_dosen->execute([$nidn, $nama_dosen, $jabatan, $email, $no_hp, $id_user]);

        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => 'Data dosen & akun login berhasil dibuat.']);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan data: ' . $e->getMessage()]);
    }
    exit();
}

// 2. UPDATE DOSEN
if ($action === 'update') {
    $id_dosen   = $_POST['id_dosen'];
    $nama_dosen = sanitize($_POST['nama_dosen']);
    $jabatan    = sanitize($_POST['jabatan']);
    $email      = sanitize($_POST['email']);
    $no_hp      = sanitize($_POST['no_hp']);

    try {
        $stmt = $pdo->prepare("UPDATE dosen SET nama_dosen = ?, jabatan = ?, email = ?, no_hp = ? WHERE id_dosen = ?");
        $stmt->execute([$nama_dosen, $jabatan, $email, $no_hp, $id_dosen]);

        echo json_encode(['status' => 'success', 'message' => 'Profil dosen berhasil diperbarui.']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal mengubah data: ' . $e->getMessage()]);
    }
    exit();
}

// 3. DELETE DOSEN
if ($action === 'delete') {
    $id_dosen = $_POST['id_dosen'] ?? '';

    try {
        $pdo->beginTransaction();

        // Ambil id_user milik dosen bersangkutan terlebih dahulu
        $stmt_find = $pdo->prepare("SELECT id_user FROM dosen WHERE id_dosen = ?");
        $stmt_find->execute([$id_dosen]);
        $id_user = $stmt_find->fetchColumn();

        if ($id_user) {
            // Hapus baris data dosen (Cascading relation manual jika belum diset foreign key di db)
            $stmt_del_dosen = $pdo->prepare("DELETE FROM dosen WHERE id_dosen = ?");
            $stmt_del_dosen->execute([$id_dosen]);

            // Hapus baris akun login user
            $stmt_del_user = $pdo->prepare("DELETE FROM users WHERE id_user = ?");
            $stmt_del_user->execute([$id_user]);
        }

        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => 'Data dosen beserta akun loginnya berhasil dihapus secara permanen.']);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus data: ' . $e->getMessage()]);
    }
    exit();
}