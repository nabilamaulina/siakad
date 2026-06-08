<?php
// admin/dosen/proses.php
require_once '../../config/security.php';
require_once '../../config/database.php';
require_once '../../config/function.php';

middleware(['admin']);

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ==========================================
// 1. CREATE DOSEN BARU
// ==========================================
if ($action === 'create') {
    $nidn       = sanitize($_POST['nidn']);
    $nama_dosen = sanitize($_POST['nama_dosen']);
    $jabatan    = sanitize($_POST['jabatan']);
    $email      = sanitize($_POST['email']);
    $no_hp      = sanitize($_POST['no_hp']);
    $password   = password_hash($nidn . "123", PASSWORD_DEFAULT);

    if (empty($nidn) || empty($nama_dosen) || empty($jabatan)) {
        echo json_encode(['status' => 'error', 'message' => 'NIDN, Nama Dosen, dan Jabatan wajib diisi!']);
        exit();
    }

    try {
        $pdo->beginTransaction();

        $stmt_user = $pdo->prepare("INSERT INTO users (username, password, role, is_active) VALUES (?, ?, 'dosen', 1)");
        $stmt_user->execute([$nidn, $password]);
        $id_user = $pdo->lastInsertId();

        $stmt_dosen = $pdo->prepare("INSERT INTO dosen (nidn, nama_dosen, jabatan, email, no_hp, id_user, foto) VALUES (?, ?, ?, ?, ?, ?, 'default.png')");
        $stmt_dosen->execute([$nidn, $nama_dosen, $jabatan, $email, $no_hp, $id_user]);

        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => 'Data dosen & akun login berhasil dibuat.']);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan data: ' . $e->getMessage()]);
    }
    exit();
}

// ==========================================
// 2. UPDATE DOSEN
// ==========================================
if ($action === 'update') {
    $id_dosen   = $_POST['id_dosen'];
    $nama_dosen = sanitize($_POST['nama_dosen']);
    $jabatan    = sanitize($_POST['jabatan']);
    $email      = sanitize($_POST['email']);
    $no_hp      = sanitize($_POST['no_hp']);

    try {
        $stmtOld = $pdo->prepare("SELECT foto, nidn FROM dosen WHERE id_dosen = ?");
        $stmtOld->execute([$id_dosen]);
        $oldData = $stmtOld->fetch(PDO::FETCH_ASSOC);

        if (!$oldData) {
            echo json_encode(['status' => 'error', 'message' => 'Data dosen tidak ditemukan!']);
            exit();
        }

        $nama_file_foto = !empty($oldData['foto']) ? $oldData['foto'] : 'default.png';
        $uploadFileDir  = '../../assets/uploads/profile/';

        if (isset($_FILES['foto_input']) && $_FILES['foto_input']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath   = $_FILES['foto_input']['tmp_name'];
            $fileName      = $_FILES['foto_input']['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
            if (in_array($fileExtension, $allowedExtensions)) {
                $newFileName = 'dsn_' . $oldData['nidn'] . '_' . time() . '.' . $fileExtension;
                
                if (!is_dir($uploadFileDir)) {
                    mkdir($uploadFileDir, 0755, true);
                }

                if (move_uploaded_file($fileTmpPath, $uploadFileDir . $newFileName)) {
                    if ($nama_file_foto !== 'default.png' && file_exists($uploadFileDir . $nama_file_foto)) {
                        @unlink($uploadFileDir . $nama_file_foto);
                    }
                    $nama_file_foto = $newFileName;
                }
            }
        }

        $stmt = $pdo->prepare("UPDATE dosen SET nama_dosen = ?, jabatan = ?, email = ?, no_hp = ?, foto = ? WHERE id_dosen = ?");
        $stmt->execute([$nama_dosen, $jabatan, $email, $no_hp, $nama_file_foto, $id_dosen]);

        echo json_encode(['status' => 'success', 'message' => 'Profil dosen berhasil diperbarui.']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal mengubah data: ' . $e->getMessage()]);
    }
    exit();
}

// ==========================================
// 3. DELETE DOSEN INDIVIDUAL
// ==========================================
if ($action === 'delete') {
    $id_dosen = $_POST['id_dosen'] ?? '';

    try {
        $stmt_find = $pdo->prepare("SELECT id_user, foto FROM dosen WHERE id_dosen = ?");
        $stmt_find->execute([$id_dosen]);
        $oldData = $stmt_find->fetch(PDO::FETCH_ASSOC);

        $id_user   = $oldData['id_user'] ?? null;
        $file_foto = $oldData['foto'] ?? 'default.png';

        $pdo->beginTransaction();

        $stmt_del_dosen = $pdo->prepare("DELETE FROM dosen WHERE id_dosen = ?");
        $stmt_del_dosen->execute([$id_dosen]);

        if ($id_user) {
            $stmt_del_user = $pdo->prepare("DELETE FROM users WHERE id_user = ?");
            $stmt_del_user->execute([$id_user]);
        }

        $pdo->commit();

        if ($file_foto && $file_foto !== 'default.png') {
            $pathFileFoto = '../../assets/uploads/profile/' . $file_foto;
            if (file_exists($pathFileFoto)) {
                @unlink($pathFileFoto);
            }
        }

        echo json_encode(['status' => 'success', 'message' => 'Data dosen beserta akun loginnya berhasil dihapus secara permanen.']);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus data: ' . $e->getMessage()]);
    }
    exit();
}

// ==========================================
// 4. DELETE MASSAL DOSEN
// ==========================================
if ($action === 'delete_massal_dosen') {
    $ids = $_POST['ids'] ?? [];

    if (empty($ids) || !is_array($ids)) {
        echo json_encode(['status' => 'error', 'message' => 'Tidak ada data dosen terpilih untuk dihapus!']);
        exit();
    }

    try {
        $sukses = 0;
        foreach ($ids as $id_dosen) {
            $id_dosen = (int)$id_dosen;

            $stmtData = $pdo->prepare("SELECT id_user, foto FROM dosen WHERE id_dosen = ?");
            $stmtData->execute([$id_dosen]);
            $dsn = $stmtData->fetch(PDO::FETCH_ASSOC);

            if ($dsn) {
                $id_user = $dsn['id_user'];
                $dsnFoto = $dsn['foto'];

                $pdo->beginTransaction();

                $delDsn = $pdo->prepare("DELETE FROM dosen WHERE id_dosen = ?");
                $delDsn->execute([$id_dosen]);

                if ($id_user) {
                    $delUser = $pdo->prepare("DELETE FROM users WHERE id_user = ?");
                    $delUser->execute([$id_user]);
                }

                $pdo->commit();

                if ($dsnFoto && $dsnFoto !== 'default.png') {
                    $pathFileFoto = '../../assets/uploads/profile/' . $dsnFoto;
                    if (file_exists($pathFileFoto)) {
                        @unlink($pathFileFoto);
                    }
                }
                $sukses++;
            }
        }

        echo json_encode([
            'status' => 'success', 
            'message' => 'Sebanyak ' . $sukses . ' data dosen beserta akun loginnya berhasil dihapus secara bersih.'
        ]);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus beberapa data: ' . $e->getMessage()]);
    }
    exit();
}