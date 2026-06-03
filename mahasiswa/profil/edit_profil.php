```php
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../templates/header.php';
require_once __DIR__ . '/../templates/sidebar.php';
require_once __DIR__ . '/../../config/database.php';

$id_user = $_SESSION['id_user'] ?? 0;

$success = '';
$error = '';

/*
|--------------------------------------------------------------------------
| Ambil Data Mahasiswa
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT *
    FROM mahasiswa
    WHERE id_user = ?
");

$stmt->execute([$id_user]);

$mahasiswa = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$mahasiswa) {
    die("Data mahasiswa tidak ditemukan.");
}

/*
|--------------------------------------------------------------------------
| Simpan Profil
|--------------------------------------------------------------------------
*/
if (isset($_POST['btn_simpan_profil'])) {

    try {

        $stmtUpdate = $pdo->prepare("
            UPDATE mahasiswa
            SET
                nama_mahasiswa = ?,
                email = ?,
                tempat_lahir = ?,
                tanggal_lahir = ?,
                alamat = ?
            WHERE id_user = ?
        ");

        $stmtUpdate->execute([
            $_POST['nama_mahasiswa'],
            $_POST['email'],
            $_POST['tempat_lahir'],
            $_POST['tanggal_lahir'],
            $_POST['alamat'],
            $id_user
        ]);

        $success = "Profil berhasil diperbarui.";

        $stmt->execute([$id_user]);
        $mahasiswa = $stmt->fetch(PDO::FETCH_ASSOC);

    } catch (Exception $e) {

        $error = $e->getMessage();
    }
}

/*
|--------------------------------------------------------------------------
| Ubah Password
|--------------------------------------------------------------------------
*/
if (isset($_POST['btn_ubah_password'])) {

    $password_lama = $_POST['password_lama'];
    $password_baru = $_POST['password_baru'];
    $konfirmasi = $_POST['konfirmasi_password'];

    try {

        $stmtUser = $pdo->prepare("
            SELECT password
            FROM users
            WHERE id_user = ?
        ");

        $stmtUser->execute([$id_user]);

        $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

        if (!password_verify($password_lama, $user['password'])) {

            $error = "Password lama tidak sesuai.";

        } elseif ($password_baru != $konfirmasi) {

            $error = "Konfirmasi password tidak cocok.";

        } else {

            $hashPassword = password_hash(
                $password_baru,
                PASSWORD_DEFAULT
            );

            $stmtUpdatePass = $pdo->prepare("
                UPDATE users
                SET password = ?
                WHERE id_user = ?
            ");

            $stmtUpdatePass->execute([
                $hashPassword,
                $id_user
            ]);

            $success = "Password berhasil diubah.";
        }

    } catch (Exception $e) {

        $error = $e->getMessage();
    }
}
?>

<div class="container-fluid px-4 py-4">

    <h4 class="fw-bold mb-4" style="color:#245358;">
        Ubah Profil & Password
    </h4>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <?= $success ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <?= $error ?>
        </div>
    <?php endif; ?>

    <div class="row">

        <!-- PROFIL -->
        <div class="col-lg-8">

            <div class="card shadow-sm border-0 mb-4">

                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        Data Profil
                    </h5>
                </div>

                <div class="card-body">

                    <form method="POST">

                        <div class="mb-3">
                            <label class="form-label">
                                NIM
                            </label>

                            <input
                                type="text"
                                class="form-control"
                                value="<?= htmlspecialchars($mahasiswa['nim']) ?>"
                                readonly
                            >
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                Nama Mahasiswa
                            </label>

                            <input
                                type="text"
                                name="nama_mahasiswa"
                                class="form-control"
                                value="<?= htmlspecialchars($mahasiswa['nama_mahasiswa']) ?>"
                                required
                            >
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                Email
                            </label>

                            <input
                                type="email"
                                name="email"
                                class="form-control"
                                value="<?= htmlspecialchars($mahasiswa['email']) ?>"
                            >
                        </div>

                        <div class="row">

                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Tempat Lahir
                                </label>

                                <input
                                    type="text"
                                    name="tempat_lahir"
                                    class="form-control"
                                    value="<?= htmlspecialchars($mahasiswa['tempat_lahir']) ?>"
                                >

                            </div>

                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Tanggal Lahir
                                </label>

                                <input
                                    type="date"
                                    name="tanggal_lahir"
                                    class="form-control"
                                    value="<?= $mahasiswa['tanggal_lahir'] ?>"
                                >

                            </div>

                        </div>

                        <div class="mb-3">

                            <label class="form-label">
                                Alamat
                            </label>

                            <textarea
                                name="alamat"
                                class="form-control"
                                rows="4"
                            ><?= htmlspecialchars($mahasiswa['alamat']) ?></textarea>

                        </div>

                        <button
                            type="submit"
                            name="btn_simpan_profil"
                            class="btn text-white"
                            style="background:#245358;"
                        >
                            Simpan Profil
                        </button>

                    </form>

                </div>

            </div>

        </div>

        <!-- PASSWORD -->
        <div class="col-lg-4">

            <div class="card shadow-sm border-0">

                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        Ubah Password
                    </h5>
                </div>

                <div class="card-body">

                    <form method="POST">

                        <div class="mb-3">

                            <label class="form-label">
                                Password Lama
                            </label>

                            <input
                                type="password"
                                name="password_lama"
                                class="form-control"
                                required
                            >

                        </div>

                        <div class="mb-3">

                            <label class="form-label">
                                Password Baru
                            </label>

                            <input
                                type="password"
                                name="password_baru"
                                class="form-control"
                                required
                            >

                        </div>

                        <div class="mb-3">

                            <label class="form-label">
                                Konfirmasi Password
                            </label>

                            <input
                                type="password"
                                name="konfirmasi_password"
                                class="form-control"
                                required
                            >

                        </div>

                        <button
                            type="submit"
                            name="btn_ubah_password"
                            class="btn btn-warning w-100"
                        >
                            Ubah Password
                        </button>

                    </form>

                </div>

            </div>

        </div>

    </div>

</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
```
