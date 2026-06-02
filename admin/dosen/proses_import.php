<?php
// admin/dosen/proses_import.php
require_once '../../config/security.php';
require_once '../../config/database.php';
require_once '../../config/function.php';

middleware(['admin']);

if (isset($_POST['btn_import_dosen'])) {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        die("CSRF Token Invalid.");
    }

    $fileName = $_FILES['file_csv']['tmp_name'];

    if ($_FILES['file_csv']['size'] > 0) {
        $file = fopen($fileName, "r");
        
        // Lewati Baris Judul Header CSV
        fgetcsv($file, 1000, ",");

        $sukses = 0;
        $gagal = 0;

        try {
            $pdo->beginTransaction();

            $stmt_user  = $pdo->prepare("INSERT INTO users (username, password, role, is_active) VALUES (?, ?, 'dosen', 1)");
            $stmt_dsn   = $pdo->prepare("INSERT INTO dosen (id_user, nidn, nama_dosen, email, no_hp) VALUES (?, ?, ?, ?, ?)");
            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM dosen WHERE nidn = ?");

            while (($column = fgetcsv($file, 1000, ",")) !== FALSE) {
                if (empty($column[0])) continue;

                $nidn  = sanitize($column[0]);
                $nama  = sanitize($column[1]);
                $email = sanitize($column[2]);
                $no_hp = sanitize($column[3]);

                // Filter validasi duplikasi NIDN
                $stmt_check->execute([$nidn]);
                if ($stmt_check->fetchColumn() > 0) {
                    $gagal++;
                    continue; 
                }

                $pass_default = md5($nidn . "123");

                $stmt_user->execute([$nidn, $pass_default]);
                $id_user_baru = $pdo->lastInsertId();
                $stmt_dsn->execute([$id_user_baru, $nidn, $nama, $email, $no_hp]);
                
                $sukses++;
            }

            log_activity($_SESSION['id_user'], "Admin melakukan Impor Massal Dosen. Sukses: $sukses, Lewat: $gagal");

            $pdo->commit();
            fclose($file);

            echo "<script>
                alert('Proses Impor Dosen Selesai!\\n- Terinput: $sukses Dosen\\n- Dilewati (NIDN Duplikat): $gagal');
                window.location.href = 'index.php';
            </script>";
            exit();

        } catch (Exception $e) {
            $pdo->rollBack();
            fclose($file);
            echo "Terjadi kendala sistem impor dosen: " . $e->getMessage();
        }
    } else {
        echo "<script>alert('Ukuran file tidak valid!'); window.location.href = 'index.php';</script>";
    }
}