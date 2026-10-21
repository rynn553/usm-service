<?php

declare(strict_types=1);
require __DIR__ . '/../includes/auth.php';
require_login();
require __DIR__ . '/../config/database.php';
date_default_timezone_set('Asia/Kuala_Lumpur');

function active_redirect(string $query = ''): never
{
    header('Location: ' . APP_BASE . '/services/index.php' . ($query !== '' ? '?' . $query : ''), true, 303);
    exit;
}
function active_date(?string $date, bool $time = false): string
{
    if (!$date) return '—';
    try {
        return (new DateTimeImmutable($date))->format($time ? 'd M Y, h:i A' : 'd M Y');
    } catch (Throwable $e) {
        return '—';
    }
}
function active_allowed(array $items, string $id): bool
{
    if (!ctype_digit($id) || (int)$id < 1) return false;
    foreach ($items as $item) if ((int)$item['id'] === (int)$id) return true;
    return false;
}
$errors = [];
try {
    $departments = $pdo->query('SELECT id,name,is_active FROM departments ORDER BY name')->fetchAll();
    $types = $pdo->query('SELECT id,name,is_active FROM service_types ORDER BY name')->fetchAll();
    $handlers = $pdo->query('SELECT id,name,is_active FROM handlers ORDER BY name')->fetchAll();
} catch (PDOException $e) {
    error_log('Active requests lookup error: ' . $e->getMessage());
    http_response_code(503);
    exit('Tidak dapat memuatkan pilihan. Semak sambungan pangkalan data.');
}
$search = trim((string)($_GET['q'] ?? ''));
if (mb_strlen($search) > 120) $search = mb_substr($search, 0, 120);
$status = (string)($_GET['status'] ?? 'all');
if (!in_array($status, ['all', 'in_progress', 'ready'], true)) $status = 'all';
$page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 1;
$back = http_build_query(array_filter(['q' => $search, 'status' => $status === 'all' ? '' : $status, 'page' => $page], static fn($v) => $v !== ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $return = (string)($_POST['return'] ?? '');
    parse_str($return, $ret);
    $safeReturn = http_build_query(array_filter([
        'q' => mb_substr(trim(is_string($ret['q'] ?? null) ? $ret['q'] : ''), 0, 120),
        'status' => in_array($ret['status'] ?? '', ['in_progress', 'ready'], true) ? $ret['status'] : '',
        'page' => filter_var($ret['page'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 1,
    ], static fn($v) => $v !== ''));
    if (!valid_csrf() || !$id || !in_array($action, ['ready', 'edit'], true)) {
        $_SESSION['active_flash'] = ['error', 'Permintaan tidak sah. Sila cuba lagi.'];
        active_redirect($safeReturn);
    }
    try {
        if ($action === 'ready') {
            $stmt = $pdo->prepare("UPDATE service_requests SET status='ready', ready_at=NOW() WHERE id=:id AND status='in_progress'");
            $stmt->execute(['id' => $id]);
            $_SESSION['active_flash'] = $stmt->rowCount() ? ['success', 'Status berjaya ditukar kepada Ready.'] : ['error', 'Status tidak berubah. Rekod mungkin telah dikemas kini oleh staf lain.'];
        } else {
            $fields = [];
            foreach (['requester_name', 'department_id', 'service_type_id', 'service_details', 'estimated_cost', 'requested_at', 'handler_id'] as $field) {
                $fields[$field] = is_string($_POST[$field] ?? null) ? trim($_POST[$field]) : '';
            }
            $valid = $fields['requester_name'] !== '' && mb_strlen($fields['requester_name']) <= 150
                && $fields['service_details'] !== '' && mb_strlen($fields['service_details']) <= 5000
                && active_allowed($departments, $fields['department_id'])
                && active_allowed($types, $fields['service_type_id'])
                && active_allowed($handlers, $fields['handler_id'])
                && preg_match('/^\d{1,8}(?:\.\d{1,2})?$/D', $fields['estimated_cost']);
            $when = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $fields['requested_at']);
            $valid = $valid && $when && $when->format('Y-m-d\TH:i') === $fields['requested_at'];
            if (!$valid) {
                $_SESSION['active_flash'] = ['error', 'Sila lengkapkan semua medan dengan maklumat yang sah.'];
            } else {
                $stmt = $pdo->prepare("UPDATE service_requests SET requester_name=:name, department_id=:department, service_type_id=:type, service_details=:details, estimated_cost=:cost, requested_at=:requested, handler_id=:handler WHERE id=:id AND status IN ('in_progress','ready')");
                $stmt->execute(['name' => $fields['requester_name'], 'department' => (int)$fields['department_id'], 'type' => (int)$fields['service_type_id'], 'details' => $fields['service_details'], 'cost' => $fields['estimated_cost'], 'requested' => $when->format('Y-m-d H:i:s'), 'handler' => (int)$fields['handler_id'], 'id' => $id]);
                // rowCount can be zero when data is unchanged; check if the record is still active.
                $check = $pdo->prepare("SELECT COUNT(*) FROM service_requests WHERE id=? AND status IN ('in_progress','ready')");
                $check->execute([$id]);
                $_SESSION['active_flash'] = (int)$check->fetchColumn() === 1 ? ['success', 'Butiran perkhidmatan berjaya disimpan.'] : ['error', 'Rekod sudah selesai atau tidak ditemui.'];
            }
        }
    } catch (Throwable $e) {
        error_log('Active request update error: ' . $e->getMessage());
        $_SESSION['active_flash'] = ['error', 'Perubahan gagal disimpan. Sila cuba lagi.'];
    }
    active_redirect($safeReturn);
}

$conditions = ["r.status IN ('in_progress','ready')"];
$params = [];
if ($status !== 'all') {
    $conditions[] = 'r.status=:status';
    $params['status'] = $status;
}
if ($search !== '') {
    $conditions[] = "(r.uid LIKE :uid OR r.requester_name LIKE :requester OR d.name LIKE :department OR t.name LIKE :type OR h.name LIKE :handler)";
    $needle = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search) . '%';
    // MySQL default LIKE escaping with backslash; bound input avoids SQL injection.
    $needle = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search) . '%';
    foreach (['uid', 'requester', 'department', 'type', 'handler'] as $key) $params[$key] = $needle;
}
$where = implode(' AND ', $conditions);
$from = ' FROM service_requests r JOIN departments d ON d.id=r.department_id JOIN service_types t ON t.id=r.service_type_id JOIN handlers h ON h.id=r.handler_id ';
try {
    $count = $pdo->prepare('SELECT COUNT(*)' . $from . ' WHERE ' . $where);
    $count->execute($params);
    $total = (int)$count->fetchColumn();
    $pages = max(1, (int)ceil($total / 10));
    $page = min($page, $pages);
    $offset = ($page - 1) * 10;
    $stmt = $pdo->prepare('SELECT r.id,r.uid,r.requester_name,r.department_id,r.service_type_id,r.service_details,r.estimated_cost,r.requested_at,r.handler_id,r.status,r.ready_at,d.name AS department_name,t.name AS service_type_name,h.name AS handler_name' . $from . ' WHERE ' . $where . ' ORDER BY r.requested_at DESC,r.id DESC LIMIT 10 OFFSET ' . $offset);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Active requests list error: ' . $e->getMessage());
    http_response_code(503);
    exit('Senarai permintaan tidak dapat dimuatkan.');
}
$flash = $_SESSION['active_flash'] ?? null;
unset($_SESSION['active_flash']);
$created = $_SESSION['service_created_uid'] ?? null;
unset($_SESSION['service_created_uid']);
function page_url(int $target, string $q, string $status): string
{
    return APP_BASE . '/services/index.php?' . http_build_query(['q' => $q, 'status' => $status, 'page' => $target]);
}
?>
<!doctype html>
<html lang="ms">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Permintaan Aktif | Perkhidmatan USM</title>
    <link rel="stylesheet" href="<?= APP_BASE ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= APP_BASE ?>/assets/css/sidebar.css">
    <link rel="stylesheet" href="<?= APP_BASE ?>/assets/css/active-requests.css">
    <script src="<?= APP_BASE ?>/assets/js/sidebar.js" defer></script>
    <script src="<?= APP_BASE ?>/assets/js/active-requests.js" defer></script>
</head>

<body class="workspace active-page">
    <?php $activePage = 'active';
    require __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="active-area">
        <header class="active-header">
            <div>
                <p class="eyebrow">REKOD &amp; JEJAK PERKHIDMATAN</p>
                <h1>Active Requests</h1>
            </div>
            <form action="<?= APP_BASE ?>/logout.php" method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><button class="active-logout" type="submit">Log Keluar ↗</button></form>
        </header>
        <main id="main-content" class="active-main" tabindex="-1">
            <p class="active-intro">Cari permintaan dan buka rekod untuk menyemak atau mengemas kini butiran perkhidmatan.</p>
            <?php if ($flash): ?><div class="active-alert <?= $flash[0] === 'success' ? 'success' : 'error' ?>" role="status"><?= e($flash[1]) ?></div><?php endif; ?>
            <?php if ($created): ?><div class="active-alert success" role="status">Rekod baharu berjaya disimpan. UID: <strong><?= e((string)$created) ?></strong></div><?php endif; ?>
            <form method="get" class="active-filter" action="<?= APP_BASE ?>/services/index.php">
                <div class="active-search"><label for="q">Cari permintaan</label><input id="q" type="search" name="q" value="<?= e($search) ?>" placeholder="Cari UID, nama staf/pelajar, jabatan, jenis servis atau pengendali"></div>
                <div><label for="status">Status Perkhidmatan</label><select id="status" name="status">
                        <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>All Active</option>
                        <option value="in_progress" <?= $status === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                        <option value="ready" <?= $status === 'ready' ? 'selected' : '' ?>>Ready</option>
                    </select></div><button class="active-search-btn" type="submit">Cari</button><a class="active-clear" href="<?= APP_BASE ?>/services/index.php">Reset</a>
            </form>
            <div class="active-table-wrap">
                <table class="active-table">
                    <thead>
                        <tr>
                            <th>UID</th>
                            <th>NAMA STAF/PELAJAR</th>
                            <th>TARIKH PERMINTAAN</th>
                            <th>STATUS PERKHIDMATAN</th>
                            <th>ANGGARAN KOS</th>
                            <th>PENGENDALI</th>
                            <th>TINDAKAN</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $r): ?><tr>
                                <td class="active-uid"><?= e($r['uid']) ?></td>
                                <td><?= e($r['requester_name']) ?></td>
                                <td><?= e(active_date($r['requested_at'])) ?></td>
                                <td><span class="active-badge <?= $r['status'] === 'ready' ? 'ready' : 'progress' ?>"><?= $r['status'] === 'ready' ? 'Ready' : 'In Progress' ?></span></td>
                                <td>RM <?= number_format((float)$r['estimated_cost'], 2) ?></td>
                                <td><?= e($r['handler_name']) ?></td>
                                <td>
                                    <div class="active-actions"><button type="button" class="active-outline details-trigger" data-id="<?= (int)$r['id'] ?>">Details</button><button type="button" class="active-outline edit-trigger" data-id="<?= (int)$r['id'] ?>">Edit</button><?php if ($r['status'] === 'in_progress'): ?><button type="button" class="active-primary ready-trigger" data-id="<?= (int)$r['id'] ?>" data-uid="<?= e($r['uid']) ?>">Ready</button><?php else: ?>
                                            <a
                                                href="<?= APP_BASE ?>/payments/index.php?uid=<?= urlencode($r['uid']) ?>"
                                                class="active-primary active-complete-btn">
                                                Complete
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr><?php endforeach; ?>
                        <?php if (!$rows): ?><tr>
                                <td colspan="7" class="active-empty">Tiada permintaan aktif dijumpai.</td>
                            </tr><?php endif; ?></tbody>
                </table>
                <div class="active-pagination"><span><?= $total ? ($offset + 1) . '–' . ($offset + count($rows)) : '0' ?> daripada <?= $total ?> rekod</span>
                    <div><a class="<?= $page === 1 ? 'disabled' : '' ?>" <?= $page > 1 ? 'href="' . e(page_url($page - 1, $search, $status)) . '"' : 'aria-disabled="true"' ?>>Sebelumnya</a><span>Halaman <?= $page ?> / <?= $pages ?></span><a class="<?= $page === $pages ? 'disabled' : '' ?>" <?= $page < $pages ? 'href="' . e(page_url($page + 1, $search, $status)) . '"' : 'aria-disabled="true"' ?>>Seterusnya</a></div>
                </div>
            </div>
        </main>
    </div>
    <?php foreach ($rows as $r): ?><template id="active-data-<?= (int)$r['id'] ?>">
            <div class="active-detail-grid">
                <div><small>UID</small><strong><?= e($r['uid']) ?></strong></div>
                <div><small>Status</small><strong><?= $r['status'] === 'ready' ? 'Ready' : 'In Progress' ?></strong></div>
                <div><small>Nama Staf/Pelajar</small><strong><?= e($r['requester_name']) ?></strong></div>
                <div><small>Jabatan/Pusat Pengajian</small><strong><?= e($r['department_name']) ?></strong></div>
                <div><small>Jenis Perkhidmatan</small><strong><?= e($r['service_type_name']) ?></strong></div>
                <div><small>Pengendali Staf IT</small><strong><?= e($r['handler_name']) ?></strong></div>
                <div><small>Anggaran Kos</small><strong>RM <?= number_format((float)$r['estimated_cost'], 2) ?></strong></div>
                <div><small>Tarikh/Masa Permintaan</small><strong><?= e(active_date($r['requested_at'], true)) ?></strong></div>
                <div><small>Tarikh/Masa Ready</small><strong><?= e(active_date($r['ready_at'], true)) ?></strong></div>
                <div class="active-full"><small>Butiran Perkhidmatan</small>
                    <p><?= nl2br(e($r['service_details'])) ?></p>
                </div>
            </div>
        </template><?php endforeach; ?>
    <dialog id="details-dialog" class="active-dialog">
        <div class="active-dialog-head">
            <h2>Butiran Permintaan</h2><button type="button" class="dialog-close" aria-label="Tutup">×</button>
        </div>
        <div id="details-body"></div>
        <div class="active-dialog-foot"><button type="button" class="active-outline dialog-close">Tutup</button></div>
    </dialog>
    <dialog id="edit-dialog" class="active-dialog">
        <div class="active-dialog-head">
            <h2>Edit Rekod Perkhidmatan</h2><button type="button" class="dialog-close" aria-label="Tutup">×</button>
        </div>
        <form id="edit-form" method="post" action="<?= APP_BASE ?>/services/index.php"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="edit"><input type="hidden" name="id" id="edit-id"><input type="hidden" name="return" value="<?= e($back) ?>">
            <div class="active-edit-grid">
                <div><label for="edit-name">Nama Staf/Pelajar *</label><input id="edit-name" name="requester_name" maxlength="150" required></div>
                <div><label for="edit-department">Jabatan/Pusat Pengajian *</label><select id="edit-department" name="department_id" required><?php foreach ($departments as $item): ?><option value="<?= (int)$item['id'] ?>"><?= e($item['name']) ?></option><?php endforeach; ?></select></div>
                <div class="active-full"><label for="edit-type">Jenis Perkhidmatan *</label><select id="edit-type" name="service_type_id" required><?php foreach ($types as $item): ?><option value="<?= (int)$item['id'] ?>"><?= e($item['name']) ?></option><?php endforeach; ?></select></div>
                <div class="active-full"><label for="edit-details">Butiran Perkhidmatan *</label><textarea id="edit-details" name="service_details" rows="4" maxlength="5000" required></textarea></div>
                <div><label for="edit-cost">Anggaran Kos (RM) *</label><input id="edit-cost" name="estimated_cost" type="number" min="0" max="99999999.99" step="0.01" required></div>
                <div><label for="edit-date">Tarikh/Masa Permintaan *</label><input id="edit-date" name="requested_at" type="datetime-local" required></div>
                <div class="active-full"><label for="edit-handler">Pengendali Staf IT *</label><select id="edit-handler" name="handler_id" required><?php foreach ($handlers as $item): ?><option value="<?= (int)$item['id'] ?>"><?= e($item['name']) ?></option><?php endforeach; ?></select></div>
            </div>
            <div class="active-dialog-foot"><button type="button" class="active-outline dialog-close">Batal</button><button class="active-primary" type="submit">Simpan Perubahan</button></div>
        </form>
    </dialog>
    <dialog id="ready-dialog" class="active-dialog active-confirm">
        <div class="active-dialog-head">
            <h2>Sahkan Status Ready</h2><button type="button" class="dialog-close" aria-label="Tutup">×</button>
        </div>
        <p>Adakah anda pasti perkhidmatan <strong id="ready-uid"></strong> telah siap dan sedia untuk diambil?</p>
        <form method="post" action="<?= APP_BASE ?>/services/index.php"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="ready"><input type="hidden" name="id" id="ready-id"><input type="hidden" name="return" value="<?= e($back) ?>">
            <div class="active-dialog-foot"><button type="button" class="active-outline dialog-close">Batal</button><button type="submit" class="active-primary">Ya, Tandakan Ready</button></div>
        </form>
    </dialog>
    <script type="application/json" id="active-records-json">
        <?= json_encode(array_column($rows, null, 'id'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE) ?>
    </script>
</body>

</html>