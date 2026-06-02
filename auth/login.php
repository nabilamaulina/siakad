<?php
// 1. Mengaktifkan Output Buffering untuk mencegah error 'headers already sent' akibat spasi liar
ob_start();

// 2. Pastikan session dimulai paling atas tanpa terhalang spasi/HTML
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 3. Load file konfigurasi sistem
try {
    require_once '../config/security.php';
    require_once '../config/database.php';
    require_once '../config/function.php';
} catch (Exception $e) {
    die("<div style='font-family:sans-serif;padding:20px;background:#fff5f5;color:#c53030;border-left:5px solid #e53e3e;'>
            <h4>Gagal Memuat File Konfigurasi!</h4>
            <p>Pastikan file database.php, security.php, dan function.php berada di folder <strong>siakad/config/</strong></p>
         </div>");
}

// 4. Fungsi Pengalihan Sesi secara Absolut & Aman
function alihkanKeDashboard($role) {
    $role_bersih = strtolower(trim($role));
    
    switch ($role_bersih) {
        case 'admin':
            header("Location: ../admin/dashboard.php");
            exit();
        case 'dosen':
            header("Location: ../dosen/dashboard.php");
            exit();
        case 'mahasiswa':
            header("Location: ../mahasiswa/dashboard.php");
            exit();
        default:
            session_destroy();
            header("Location: login.php?error=role_tidak_dikenali");
            exit();
    }
}

// Jika user sudah login sebelumnya, langsung lempar ke dashboardnya secara otomatis
if (isset($_SESSION['id_user']) && isset($_SESSION['role'])) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        alihkanKeDashboard($_SESSION['role']);
    }
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $username = function_exists('sanitize') ? sanitize($_POST['username']) : filter_var(trim($_POST['username']), FILTER_SANITIZE_SPECIAL_CHARS);
    $password = $_POST['password'];

    try {
        // Cari user berdasarkan username
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user) {
            // Cek apakah akun aktif
            if (isset($user['is_active']) && $user['is_active'] != 1) {
                $error = "Akun Anda berstatus tidak aktif. Silakan hubungi pihak IT.";
                
            // VERIFIKASI PASSWORD
            } elseif (password_verify($password, $user['password']) || md5($password) === $user['password'] || $password === $user['password']) {

                // Ganti ID Session demi keamanan
                session_regenerate_id(true);

                // =========================================================================
                // 🗝️ DAFTARKAN SESSION UTAMA APLIKASI (SUDAH DIHUBUNGKAN KE MAHASISWA)
                // =========================================================================
                $_SESSION['id_user']       = $user['id_user'];
                $_SESSION['username']     = $user['username'];
                $_SESSION['role']         = strtolower(trim($user['role']));
                $_SESSION['last_activity'] = time();

                // SINKRONISASI OTOMATIS JIKA YANG LOGIN ADALAH MAHASISWA
                if ($_SESSION['role'] === 'mahasiswa') {
                    try {
                        // Cari id_mahasiswa berdasarkan id_user yang baru saja login
                        $stmt_mhs = $pdo->prepare("SELECT id_mahasiswa FROM mahasiswa WHERE id_user = ? LIMIT 1");
                        $stmt_mhs->execute([$user['id_user']]);
                        $mahasiswa = $stmt_mhs->fetch();

                        if ($mahasiswa) {
                            $_SESSION['id_mahasiswa'] = $mahasiswa['id_mahasiswa'];
                        } else {
                            $_SESSION['id_mahasiswa'] = 0;
                        }
                    } catch (Exception $e_mhs) {
                        $_SESSION['id_mahasiswa'] = 0;
                    }
                }

                // Proses Insert Logs Aktivitas Sesi
                try {
                    $stmt_log = $pdo->prepare("INSERT INTO user_logs (id_user, login_time, user_agent) VALUES (?, NOW(), ?)");
                    $stmt_log->execute([$user['id_user'], $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown Device']);
                    $_SESSION['id_sesi_log'] = $pdo->lastInsertId();
                } catch (Exception $e_log) {
                    try {
                        $stmt_log2 = $pdo->prepare("INSERT INTO login_logs (id_user, ip_address, user_agent) VALUES (?, ?, ?)");
                        $stmt_log2->execute([$user['id_user'], $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown']);
                        $_SESSION['id_sesi_log'] = $pdo->lastInsertId();
                    } catch (Exception $e_log2) {}
                }

                if (function_exists('log_activity')) {
                    try { log_activity($user['id_user'], "User " . $user['username'] . " berhasil masuk ke sistem."); } catch(Exception $e){}
                }

                // Bersihkan buffer output sebelum melakukan redirect header
                ob_end_clean();

                // Eksekusi pengalihan halaman
                alihkanKeDashboard($user['role']);
                
            } else {
                $error = "Password yang Anda masukkan salah.";
            }
        } else {
            $error = "Username '" . htmlspecialchars($username) . "' tidak terdaftar.";
        }
    } catch (PDOException $e) {
        $error = "Terjadi kendala pada sistem database: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login SOBAT IK - Portal Akademik</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --ocean-muted: #5e8281;
            --ocean-deep: #245358;
            --text-dark: #2c3e40;
            --accent-cyan: #0d778a;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background-color: #f4f7f6;
            height: 100vh;
            margin: 0;
            overflow: hidden;
        }

        .login-container {
            height: 100vh;
            width: 100vw;
        }

        .brand-side {
            background: linear-gradient(135deg, rgba(36, 83, 88, 0.98), rgba(94, 130, 129, 0.92)), 
                        url('https://images.unsplash.com/photo-1541339907198-e08756dedf3f?q=80&w=1000&auto=format&fit=crop');
            background-size: cover;
            background-position: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white;
            padding: 3rem;
            position: relative;
        }

        .brand-logo-display {
            font-size: 3.6rem;
            font-weight: 800;
            letter-spacing: 0.5px;
            color: #ffffff;
            margin-bottom: 0.6rem;
            text-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .brand-logo-display span {
            color: var(--accent-cyan);
        }

        .brand-logo-display i {
            color: var(--accent-cyan);
            filter: drop-shadow(0 0 15px rgba(2, 78, 93, 0.6));
            animation: floatAnimation 3s ease-in-out infinite;
        }

        @keyframes floatAnimation {
            0%, 100% { transform: translateY(0) rotate(-4deg); }
            50% { transform: translateY(-6px) rotate(4deg); }
        }

        .sub-title-display {
            font-size: 0.95rem;
            font-weight: 500;
            letter-spacing: 1px;
            color: rgba(255, 255, 255, 0.8);
            text-shadow: 0 2px 4px rgba(0,0,0,0.15);
        }

        .form-side {
            background-color: #ffffff;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2.5rem;
            box-shadow: -10px 0 35px rgba(0,0,0,0.05);
        }

        .form-wrapper {
            width: 100%;
            max-width: 400px;
        }

        .form-logo {
            font-size: 2.3rem;
            font-weight: 800;
            color: var(--ocean-deep);
            letter-spacing: -0.5px;
        }

        .form-logo span {
            color: var(--accent-cyan);
            filter: drop-shadow(0 1px 2px rgba(13, 202, 240, 0.2));
        }

        .form-control {
            padding: 0.75rem 1rem;
            border-radius: 10px;
            border: 1.5px solid #e2e8f0;
            background-color: #f8fafc;
            color: var(--text-dark);
            font-weight: 500;
        }

        .form-control:focus {
            background-color: #fff;
            border-color: var(--ocean-deep);
            box-shadow: 0 0 0 3.5px rgba(36, 83, 88, 0.15);
        }

        .input-group-text {
            border-radius: 10px 0 0 10px;
            border: 1.5px solid #e2e8f0;
            background-color: #f8fafc;
            color: var(--ocean-muted);
        }

        .input-group .form-control {
            border-left: none;
            border-radius: 0 10px 10px 0;
        }

        .password-toggle {
            border: 1.5px solid #e2e8f0;
            border-left: none;
            background-color: #f8fafc;
            color: var(--ocean-muted);
            border-radius: 0 10px 10px 0;
            cursor: pointer;
            transition: color 0.2s;
        }

        .password-toggle:hover {
            color: var(--accent-cyan);
        }

        .btn-login {
            background: linear-gradient(135deg, var(--ocean-deep), var(--ocean-muted));
            color: white;
            border: none;
            padding: 0.85rem;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1.05rem;
            box-shadow: 0 4px 14px rgba(36, 83, 88, 0.25);
            transition: all 0.25s ease;
        }

        .btn-login:hover {
            transform: translateY(-1.5px);
            box-shadow: 0 6px 20px rgba(36, 83, 88, 0.4);
            color: white;
            opacity: 0.95;
        }

        .footer-text {
            font-size: 0.8rem;
            color: var(--ocean-muted);
            text-align: center;
            margin-top: 3.5rem;
            opacity: 0.8;
        }

        @media (max-width: 767.98px) {
            .brand-side { display: none !important; }
            .form-side { height: 100vh; }
        }
    </style>
</head>
<body>

    <div class="container-fluid p-0 login-container">
        <div class="row g-0 h-100">
            
            <div class="col-md-6 col-lg-7 brand-side">
                <div class="text-center">
                    <div class="brand-logo-display">
                        <i class="fa-solid fa-graduation-cap"></i>SOBAT<span>IK</span>
                    </div>
                    <div class="sub-title-display text-uppercase tracking-wider mb-2">
                        Sistem Operasional & Basis Akademik Terpadu Ilmu Komputer
                    </div>
                    <div class="badge bg-white bg-opacity-10 text-white px-3 py-2 rounded-pill small fw-normal border border-white border-opacity-20">
                        <i class="fa-solid fa-circle-check me-1 text-info"></i> Portal Utama SIAKAD SOBAT IK
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-5 form-side">
                <div class="form-wrapper">
                    
                    <div class="text-center mb-4">
                        <div class="form-logo mb-1">SOBAT<span>IK</span></div>
                        <p class="text-muted small">Silahkan Login ke Akun Anda</p>
                    </div>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger d-flex align-items-center border-0 rounded-3 py-2.5 small" role="alert">
                            <i class="fa-solid fa-circle-exclamation me-2 fs-5"></i>
                            <div><?= htmlspecialchars($error); ?></div>
                        </div>
                    <?php endif; ?>

                    <form action="" method="POST" class="mt-4">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Username / NIM / NIP</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-user small"></i></span>
                                <input type="text" name="username" class="form-control" placeholder="Masukkan ID Pengguna" required autocomplete="off">
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label small fw-semibold text-secondary">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-lock small"></i></span>
                                <input type="password" name="password" id="password" class="form-control" placeholder="Masukkan Kata Sandi" required>
                                <button class="input-group-text password-toggle" type="button" onclick="toggleLoginPassword()">
                                    <i class="fa-regular fa-eye" id="icon-toggle"></i>
                                </button>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-login w-100">
                            Masuk Portal Aplikasi <i class="fa-solid fa-right-to-bracket ms-1 small"></i>
                        </button>
                    </form>

                    <div class="footer-text">
                        &copy; 2026 Portal Terpadu SOBAT IK. <br>All Rights Reserved.
                    </div>

                </div>
            </div>

        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function toggleLoginPassword() {
        const passwordField = document.getElementById("password");
        const toggleIcon = document.getElementById("icon-toggle");
        if (passwordField.type === "password") {
            passwordField.type = "text";
            toggleIcon.classList.replace("fa-eye", "fa-eye-slash");
        } else {
            passwordField.type = "password";
            toggleIcon.classList.replace("fa-eye-slash", "fa-eye");
        }
    }
    </script>
</body>
</html>