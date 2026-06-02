// Ambil device/browser yang dipakai user
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown Device';
$id_user = $user['id_user']; // ID setelah dicek dari database

// Insert sesi baru ke user_logs
$stmt = $pdo->prepare("INSERT INTO user_logs (id_user, login_time, user_agent) VALUES (?, NOW(), ?)");
$stmt->execute([$id_user, $user_agent]);

// Simpan ID Log yang baru ini ke dalam Session agar nanti bisa dipakai saat logout
$_SESSION['id_sesi_log'] = $pdo->lastInsertId();