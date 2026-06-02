<?php
// admin/sistem/log_aktivitas.php
require_once '../../templates/header.php';
require_once '../../templates/sidebar.php';
require_once '../../config/database.php';

// Ambil data dari tabel login_logs asli milikmu, di-JOIN ke tabel users agar muncul nama aslinya
try {
    // Catatan: Sesuaikan nama tabel 'users' dan kolom 'username'/'nama' dengan database aslimu
    $query = "SELECT ul.id_log, ul.login_time, ul.logout_time, ul.user_agent, u.username 
              FROM login_logs ul
              LEFT JOIN users u ON ul.id_user = u.id_user 
              ORDER BY ul.login_time DESC";
              
    $stmt = $pdo->query($query);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Fallback aman jika tabel relasi 'users' berbeda strukturnya, agar halaman tidak crash
    $query = "SELECT id_log, id_user, login_time, logout_time, user_agent FROM login_logs ORDER BY login_time DESC";
    $stmt = $pdo->query($query);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght=400;500;600;700&display=swap" rel="stylesheet">
<div class="container-fluid py-3" style="font-family: 'Plus Jakarta Sans', sans-serif;">
    
    <div class="row mb-4">
        <div class="col-12">
            <h3 class="fw-bold text-navy mb-1"><i class="fa-solid fa-shield-halved me-2" style="color: #0284c7;"></i>User Session Logs</h3>
            <p class="text-muted small">Memantau durasi akses, rekam jejak jam login/logout, serta perangkat yang digunakan oleh user.</p>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white py-3 border-0 rounded-top-4 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-dark"><i class="fa-solid fa-key text-secondary me-2"></i>Data `login_logs` Real-Time</h6>
            <span class="badge bg-light text-secondary border px-3 py-2 rounded-3">Total Sesi: <?= count($logs); ?> Terekam</span>
        </div>
        <div class="card-body p-3">
            <div class="table-responsive">
                <table class="table table-hover align-middle datatable-init w-100">
                    <thead class="table-light text-secondary small text-uppercase" style="font-size: 11px;">
                        <tr>
                            <th width="8%">ID Log</th>
                            <th width="20%">Pengguna (User)</th>
                            <th width="18%">Waktu Masuk (Login)</th>
                            <th width="18%">Waktu Keluar (Logout)</th>
                            <th width="36%">Perangkat / Browser (User Agent)</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        <?php if (count($logs) == 0): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="fa-regular fa-folder-open fa-2x mb-2"></i><br>
                                    Tabel `login_logs` masih kosong. Belum ada riwayat login/logout.
                                </td>
                            </tr>
                        <?php else: foreach ($logs as $log): ?>
                            <tr>
                                <td class="text-muted font-monospace">#<?= $log['id_log']; ?></td>
                                <td>
                                    <span class="fw-semibold text-dark">
                                        <?= isset($log['username']) ? htmlspecialchars($log['username']) : 'ID User: ' . $log['id_user']; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-success bg-opacity-10 text-success border-0 px-2 py-1.5 font-monospace" style="font-size: 11px;">
                                        <i class="fa-solid fa-right-to-bracket me-1"></i>
                                        <?= date('d-m-Y H:i:s', strtotime($log['login_time'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($log['logout_time']) && $log['logout_time'] != '0000-00-00 00:00:00'): ?>
                                        <span class="badge bg-danger bg-opacity-10 text-danger border-0 px-2 py-1.5 font-monospace" style="font-size: 11px;">
                                            <i class="fa-solid fa-right-from-bracket me-1"></i>
                                            <?= date('d-m-Y H:i:s', strtotime($log['logout_time'])); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-warning bg-opacity-10 text-warning border-0 px-2 py-1.5 fw-bold animate-pulse">
                                            <i class="fa-solid fa-spinner fa-spin me-1"></i> Sesi Aktif / Belum Logout
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-muted text-truncate small" style="max-width: 250px;" title="<?= htmlspecialchars($log['user_agent']); ?>">
                                    <i class="fa-solid fa-laptop me-1" style="font-size: 11px;"></i> 
                                    <?= htmlspecialchars($log['user_agent']); ?>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../templates/footer.php'; ?>