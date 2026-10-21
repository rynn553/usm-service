<?php

declare(strict_types=1);
require __DIR__ . '/includes/auth.php';
require_login();
require __DIR__ . '/config/database.php';
$summary = $pdo->query("SELECT COALESCE(
    SUM(CASE WHEN status = 'completed' THEN estimated_cost ELSE 0 END),
    0
) AS total_cost, SUM(status='in_progress') AS in_progress, SUM(status='ready') AS ready, SUM(status='completed') AS completed FROM service_requests")->fetch();
$recent = $pdo->query("SELECT r.uid,r.requester_name,r.requested_at,r.status,r.estimated_cost,r.service_details,h.name AS handler_name,d.name AS department_name,t.name AS service_type_name FROM service_requests r JOIN handlers h ON h.id=r.handler_id JOIN departments d ON d.id=r.department_id JOIN service_types t ON t.id=r.service_type_id ORDER BY r.requested_at DESC,r.id DESC LIMIT 8")->fetchAll();
$labels = ['in_progress' => 'In Progress', 'ready' => 'Ready', 'completed' => 'Completed'];
?>
<!DOCTYPE html>
<html lang="ms">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Papan Pemuka | Perkhidmatan USM</title>
    <link rel="stylesheet" href="<?= APP_BASE ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= APP_BASE ?>/assets/css/sidebar.css">
    <link rel="stylesheet" href="<?= APP_BASE ?>/assets/css/dashboard.css">
    <script src="<?= APP_BASE ?>/assets/js/sidebar.js" defer></script>
    <script src="<?= APP_BASE ?>/assets/js/dashboard.js" defer></script>
</head>

<body class="workspace"><?php $activePage = 'home';
                        require __DIR__ . '/includes/sidebar.php'; ?>
    <div class="dashboard-area">
        <header class="dashboard-header">
            <div>
                <p class="eyebrow">SISTEM PENGURUSAN PERKHIDMATAN IT</p>
                <h1>Papan Pemuka</h1>
            </div>
            <form method="post" action="<?= APP_BASE ?>/logout.php"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><button class="logout-button" type="submit">Log Keluar ↗</button></form>
        </header>
        <main class="dashboard-main" id="main-content" tabindex="-1">
            <div class="dashboard-intro">
                <div>
                    <h2>Ringkasan perkhidmatan</h2>
                    <p>Ringkasan terkini permintaan perkhidmatan yang direkodkan.</p>
                </div><a class="dashboard-primary" href="<?= APP_BASE ?>/services/create.php">+ Rekod perkhidmatan</a>
            </div>
            <section class="stats-grid" aria-label="Statistik perkhidmatan">
                <div class="stat-card"><span>Jumlah Bayaran Diterima</span><strong>RM <?= number_format((float)$summary['total_cost'], 2) ?></strong><small>Bagi perkhidmatan yang selesai dan dibayar</small></div>
                <div class="stat-card"><span>Dalam proses</span><strong><?= (int)$summary['in_progress'] ?></strong><small>Sedang dikendalikan</small></div>
                <div class="stat-card"><span>Sedia untuk diambil</span><strong><?= (int)$summary['ready'] ?></strong><small>Menunggu pengambilan oleh staf/pelajar</small></div>
                <div class="stat-card"><span>Selesai</span><strong><?= (int)$summary['completed'] ?></strong><small>Perkhidmatan selesai</small></div>
            </section>
            <section class="recent-panel">
                <div class="recent-heading">
                    <h2>Permintaan terkini</h2><a href="<?= APP_BASE ?>/services/index.php">Lihat semua permintaan</a>
                </div>
                <div class="table-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th>UID</th>
                                <th>NAMA STAF/PELAJAR</th>
                                <th>TARIKH PERMINTAAN</th>
                                <th>STATUS PERKHIDMATAN</th>
                                <th>KOS</th>
                                <th>PENGENDALI</th>
                                <th>TINDAKAN</th>
                            </tr>
                        </thead>
                        <tbody><?php foreach ($recent as $r): ?><tr>
                                    <td class="uid"><?= e($r['uid']) ?></td>
                                    <td><?= e($r['requester_name']) ?></td>
                                    <td><?= e(date('d M Y', strtotime($r['requested_at']))) ?></td>
                                    <td><span class="status status-<?= e($r['status']) ?>"><?= e($labels[$r['status']] ?? $r['status']) ?></span></td>
                                    <td>RM <?= number_format((float)$r['estimated_cost'], 2) ?></td>
                                    <td><?= e($r['handler_name']) ?></td>
                                    <td>
                                        <button type="button" class="details-button" data-details
                                            aria-haspopup="dialog" aria-controls="request-details"
                                            aria-label="Lihat butiran <?= e($r['uid']) ?>"
                                            data-record="<?= e(json_encode($r, JSON_THROW_ON_ERROR)) ?>">Butiran</button>
                                    </td>
                                </tr><?php endforeach; ?><?php if (!$recent): ?><tr>
                                    <td colspan="7">Belum ada permintaan direkodkan.</td>
                                </tr><?php endif; ?></tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
    <dialog id="request-details" class="details-dialog" aria-labelledby="details-title">
        <div class="details-heading">
            <h2 id="details-title">Butiran permintaan</h2>
            <button type="button" class="details-close" aria-label="Tutup butiran" autofocus>×</button>
        </div>
        <dl class="details-grid">
            <div>
                <dt>UID</dt>
                <dd data-field="uid"></dd>
            </div>
            <div>
                <dt>Nama staf/pelajar</dt>
                <dd data-field="requester_name"></dd>
            </div>
            <div>
                <dt>Jabatan</dt>
                <dd data-field="department_name"></dd>
            </div>
            <div>
                <dt>Tarikh permintaan</dt>
                <dd data-field="requested_at"></dd>
            </div>
            <div>
                <dt>Status perkhidmatan</dt>
                <dd data-field="status"></dd>
            </div>
            <div>
                <dt>Kos</dt>
                <dd data-field="estimated_cost"></dd>
            </div>
            <div>
                <dt>Pengendali</dt>
                <dd data-field="handler_name"></dd>
            </div>
            <div>
                <dt>Jenis servis</dt>
                <dd data-field="service_type_name"></dd>
            </div>
            <div class="details-wide">
                <dt>Butiran servis</dt>
                <dd data-field="service_details"></dd>
            </div>
        </dl>
    </dialog>
</body>

</html>