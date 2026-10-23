<?php

declare(strict_types=1);
require __DIR__ . '/includes/auth.php';
require_login();
require __DIR__ . '/config/database.php';
require __DIR__ . '/service_multi.php';

date_default_timezone_set('Asia/Kuala_Lumpur');
function go_back(string $query = ''): never
{
  header('Location: ' . APP_BASE . '/aktif.php' . ($query ? '?' . $query : ''), true, 303);
  exit;
}
function display_date(?string $date, bool $time = false): string
{
  if (!$date) return '—';
  try {
    return (new DateTimeImmutable($date))->format($time ? 'd M Y, h:i A' : 'd M Y');
  } catch (Throwable) {
    return '—';
  }
}
function valid_lookup(array $items, string $value): bool
{
  if (!ctype_digit($value)) return false;
  foreach ($items as $i) if ((int)$i['id'] === (int)$value) return true;
  return false;
}
try {
  $departments = $pdo->query('SELECT id,name,is_active FROM departments ORDER BY name')->fetchAll();
  $types = $pdo->query('SELECT id,name,is_active FROM service_types ORDER BY id')->fetchAll();
  $handlers = $pdo->query('SELECT id,name,is_active FROM handlers ORDER BY name')->fetchAll();
  $hardwareTypeId = hardware_service_id($types);
} catch (PDOException $e) {
  error_log($e->getMessage());
  http_response_code(503);
  exit('Tidak dapat memuatkan pilihan.');
}
$search = mb_substr(trim(is_string($_GET['q'] ?? null) ? $_GET['q'] : ''), 0, 120);
$status = (string)($_GET['status'] ?? 'all');
if (!in_array($status, ['all', 'in_progress', 'ready'], true)) $status = 'all';
$sort = (string)($_GET['sort'] ?? 'status_first');
if (!in_array($sort, ['status_first', 'newest', 'oldest', 'ready_first', 'uid_asc', 'uid_desc'], true)) $sort = 'status_first';
$date = is_string($_GET['date'] ?? null) ? trim($_GET['date']) : '';
if ($date !== '' && (!preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/D', $date) || !DateTimeImmutable::createFromFormat('!Y-m-d', $date) || DateTimeImmutable::createFromFormat('!Y-m-d', $date)->format('Y-m-d') !== $date)) $date = '';
$page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 1;
$back = http_build_query(['q' => $search, 'status' => $status, 'date' => $date, 'sort' => $sort, 'page' => $page]);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = (string)($_POST['action'] ?? '');
  $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
  parse_str(is_string($_POST['return'] ?? null) ? $_POST['return'] : '', $ret);
  $safeReturn = http_build_query(['q' => mb_substr(trim(is_string($ret['q'] ?? null) ? $ret['q'] : ''), 0, 120), 'status' => in_array($ret['status'] ?? '', ['in_progress', 'ready'], true) ? $ret['status'] : 'all', 'date' => is_string($ret['date'] ?? null) ? $ret['date'] : '', 'sort' => in_array($ret['sort'] ?? '', ['status_first', 'newest', 'oldest', 'ready_first', 'uid_asc', 'uid_desc'], true) ? $ret['sort'] : 'status_first', 'page' => filter_var($ret['page'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 1]);
  if (!valid_csrf() || !$id || !in_array($action, ['ready', 'edit'], true)) {
    $_SESSION['active_flash'] = ['error', 'Permintaan tidak sah.'];
    go_back($safeReturn);
  }
  try {
    if ($action === 'ready') {
      $stmt = $pdo->prepare("UPDATE service_requests SET status='ready',ready_at=NOW() WHERE id=? AND status='in_progress'");
      $stmt->execute([$id]);
      $_SESSION['active_flash'] = $stmt->rowCount() ? ['success', 'Status berjaya ditukar kepada Ready.'] : ['error', 'Status tidak berubah.'];
    } else {
      $fields = [];
      foreach (['requester_name', 'department_id', 'service_details', 'estimated_cost', 'requested_at', 'handler_id'] as $field) $fields[$field] = is_string($_POST[$field] ?? null) ? trim($_POST[$field]) : '';
      $ids = multi_ids($_POST['service_type_ids'] ?? null, $types);
      $hardware = multi_hardware($_POST['hardware'] ?? null, $hardwareTypeId !== null && in_array($hardwareTypeId, $ids, true));
      $deviceData = device_input($_POST);
      $when = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $fields['requested_at']);
      if ($fields['requester_name'] === '' || mb_strlen($fields['requester_name']) > 150 || mb_strlen($fields['service_details']) > 5000 || !valid_lookup($departments, $fields['department_id']) || !valid_lookup($handlers, $fields['handler_id']) || !preg_match('/^\d{1,8}(?:\.\d{1,2})?$/D', $fields['estimated_cost']) || !$when || $when->format('Y-m-d\TH:i') !== $fields['requested_at']) throw new InvalidArgumentException('Sila lengkapkan semua medan dengan maklumat yang sah.');
      $pdo->beginTransaction();
      $stmt = $pdo->prepare("UPDATE service_requests SET requester_name=?,department_id=?,service_type_id=?,service_details=?,estimated_cost=?,requested_at=?,handler_id=? WHERE id=? AND status IN ('in_progress','ready')");
      $stmt->execute([$fields['requester_name'], (int)$fields['department_id'], $ids[0], $fields['service_details'], $fields['estimated_cost'], $when->format('Y-m-d H:i:s'), (int)$fields['handler_id'], $id]);
      $check = $pdo->prepare("SELECT COUNT(*) FROM service_requests WHERE id=? AND status IN ('in_progress','ready')");
      $check->execute([$id]);
      if (!(int)$check->fetchColumn()) throw new RuntimeException('Rekod sudah selesai atau tidak ditemui.');
      save_multi($pdo, (int)$id, $ids, $hardware);
      save_device($pdo, (int)$id, $deviceData);
      $pdo->commit();
      $_SESSION['active_flash'] = ['success', 'Butiran perkhidmatan berjaya disimpan.'];
    }
  } catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Active update: ' . $e->getMessage());
    $_SESSION['active_flash'] = ['error', $e instanceof InvalidArgumentException ? $e->getMessage() : 'Perubahan gagal disimpan.'];
  }
  go_back($safeReturn);
}
$conditions = ["r.status IN ('in_progress','ready')"];
$params = [];
if ($status !== 'all') {
  $conditions[] = 'r.status=:status';
  $params['status'] = $status;
}
if ($date !== '') {
  $conditions[] = 'r.requested_at >= :date_start AND r.requested_at < :date_end';
  $params['date_start'] = $date . ' 00:00:00';
  $params['date_end'] = (new DateTimeImmutable($date))->modify('+1 day')->format('Y-m-d 00:00:00');
}
if ($search !== '') {
  $conditions[] = "(CAST(r.id AS CHAR) LIKE :numeric_id OR r.requester_name LIKE :requester OR h.name LIKE :handler)";
  $needle = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search) . '%';
  foreach (['numeric_id', 'requester', 'handler'] as $key) $params[$key] = $needle;
}
$from = ' FROM service_requests r JOIN departments d ON d.id=r.department_id JOIN service_types t ON t.id=r.service_type_id JOIN handlers h ON h.id=r.handler_id ';
try {
  $count = $pdo->prepare('SELECT COUNT(*)' . $from . ' WHERE ' . implode(' AND ', $conditions));
  $count->execute($params);
  $total = (int)$count->fetchColumn();
  $pages = max(1, (int)ceil($total / 10));
  $page = min($page, $pages);
  $offset = ($page - 1) * 10;
  $sortSql = match ($sort) {
    'status_first' => "CASE WHEN r.status='in_progress' THEN 0 ELSE 1 END,r.id ASC",
    'oldest' => 'r.requested_at ASC,r.id ASC',
    'ready_first' => "CASE WHEN r.status='ready' THEN 0 ELSE 1 END,r.requested_at DESC,r.id DESC",
    'uid_asc' => 'r.id ASC',
    'uid_desc' => 'r.id DESC',
    default => "CASE WHEN r.status='in_progress' THEN 0 ELSE 1 END,r.id ASC",
  };
  $stmt = $pdo->prepare('SELECT r.*,d.name AS department_name,t.name AS service_type_name,h.name AS handler_name' . $from . ' WHERE ' . implode(' AND ', $conditions) . ' ORDER BY ' . $sortSql . ' LIMIT 10 OFFSET ' . $offset);
  $stmt->execute($params);
  $rows = fetch_device($pdo, fetch_multi($pdo, $stmt->fetchAll()));
} catch (PDOException $e) {
  error_log($e->getMessage());
  http_response_code(503);
  exit('Senarai permintaan tidak dapat dimuatkan. Jalankan migration SQL dahulu.');
}
$typeNames = [];
foreach ($types as $type) $typeNames[(int)$type['id']] = $type['name'];
$flash = $_SESSION['active_flash'] ?? null;
unset($_SESSION['active_flash']);
$created = $_SESSION['service_created_uid'] ?? null;
unset($_SESSION['service_created_uid']);
function page_url(int $page, string $q, string $status, string $date, string $sort): string
{
  return APP_BASE . '/aktif.php?' . http_build_query(['q' => $q, 'status' => $status, 'date' => $date, 'sort' => $sort, 'page' => $page]);
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

<body class="workspace active-page"><?php $activePage = 'active';
                                    require __DIR__ . '/includes/sidebar.php'; ?>
  <div class="active-area">
    <header class="active-header">
      <div>
        <p class="eyebrow">SISTEM PENGURUSAN PERKHIDMATAN IT</p>
        <h1>Permintaan Aktif</h1>
      </div>
      <form action="<?= APP_BASE ?>/logout.php" method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><button class="active-logout" type="submit">Log Keluar ↗</button></form>
    </header>
    <main id="main-content" class="active-main" tabindex="-1">
      <?php if ($flash): ?><div class="active-alert <?= $flash[0] === 'success' ? 'success' : 'error' ?>" role="status"><?= e($flash[1]) ?></div><?php endif; ?>
      <?php if ($created): ?><div class="active-alert success" role="status">Rekod baharu berjaya disimpan. Rujukan: <strong><?= e((string)$created) ?></strong></div><?php endif; ?>
      <section class="active-list-card" aria-label="Senarai permintaan aktif">
        <form method="get" class="active-filter active-filter-two" action="<?= APP_BASE ?>/aktif.php">
          <div class="active-filter-search-row">
            <div class="active-search"><label for="q">Cari Permintaan</label>
              <div class="active-search-field"><span aria-hidden="true">⌕</span><input id="q" type="search" name="q" value="<?= e($search) ?>" placeholder="Cari UID, nama staf/pelajar atau pengendali"></div>
            </div>
            <button class="active-search-btn" type="submit">Cari</button>
          </div>
          <div class="active-filter-options-row">
            <div class="active-filter-status"><label for="status">Status</label><select id="status" name="status">
                <option value="all">Semua Status</option>
                <option value="in_progress" <?= $status === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                <option value="ready" <?= $status === 'ready' ? 'selected' : '' ?>>Ready</option>
              </select></div>
            <div class="active-filter-date"><label for="date">Tarikh</label><input type="date" id="date" name="date" value="<?= e($date) ?>"></div>
            <div class="active-filter-sort"><label for="sort">Susun Ikut</label><select id="sort" name="sort">
                <option value="status_first" <?= $sort === 'status_first' ? 'selected' : '' ?>>Pilih</option>
                <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Terbaharu</option>
                <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Terlama</option>
                <option value="ready_first" <?= $sort === 'ready_first' ? 'selected' : '' ?>>Ready Dahulu</option>
                <option value="uid_asc" <?= $sort === 'uid_asc' ? 'selected' : '' ?>>UID Menaik</option>
                <option value="uid_desc" <?= $sort === 'uid_desc' ? 'selected' : '' ?>>UID Menurun</option>
              </select></div>
            <a class="active-clear" href="<?= APP_BASE ?>/aktif.php">Reset</a>
          </div>
        </form>
        <div class="active-table-wrap">
          <table class="active-table">
            <thead>
              <tr>
                <th>UID</th>
                <th>NAMA STAF/PELAJAR</th>
                <th>TARIKH</th>
                <th>STATUS</th>
                <th>KOS</th>
                <th>PENGENDALI</th>
                <th class="active-action-head">TINDAKAN</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rows as $r): ?><tr>
                  <td data-label="UID"><span class="active-uid">#<?= (int)$r['id'] ?></span></td>
                  <td data-label="Nama Staf/Pelajar"><strong class="active-person"><?= e($r['requester_name']) ?></strong><small class="active-subtext"><?= e($r['department_name']) ?></small></td>
                  <td data-label="Tarikh"><span class="active-nowrap"><?= e(display_date($r['requested_at'])) ?></span></td>
                  <td data-label="Status"><span class="active-badge <?= $r['status'] === 'ready' ? 'ready' : 'progress' ?>"><?= $r['status'] === 'ready' ? 'Sedia Diambil' : 'Dalam Proses' ?></span></td>
                  <td data-label="Kos"><span class="active-nowrap">RM <?= number_format((float)$r['estimated_cost'], 2) ?></span></td>
                  <td data-label="Pengendali"><?= e($r['handler_name']) ?></td>
                  <td data-label="Tindakan" class="active-action-cell">
                    <details class="active-action-menu">
                      <summary aria-label="Tindakan untuk UID <?= (int)$r['id'] ?>">Tindakan <span aria-hidden="true">⌄</span></summary>
                      <div class="active-action-popover"><button type="button" class="details-trigger" data-id="<?= (int)$r['id'] ?>">Butiran</button><button type="button" class="edit-trigger" data-id="<?= (int)$r['id'] ?>">Edit</button><?php if ($r['status'] === 'in_progress'): ?><button type="button" class="ready-trigger" data-id="<?= (int)$r['id'] ?>" data-uid="UID #<?= (int)$r['id'] ?>">Ready</button><?php else: ?><a href="<?= APP_BASE ?>/payments/index.php?uid=<?= urlencode((string)$r['uid']) ?>">Complete ↗</a><?php endif; ?></div>
                    </details>
                  </td>
                </tr><?php endforeach; ?>
              <?php if (!$rows): ?><tr>
                  <td colspan="7" class="active-empty"><strong>Tiada permintaan dijumpai</strong><span>Cuba ubah carian atau filter yang dipilih.</span></td>
                </tr><?php endif; ?>
            </tbody>
          </table>
        </div>
        <div class="active-pagination"><span><?= $total ? ($offset + 1) . '–' . ($offset + count($rows)) : '0' ?> daripada <?= $total ?> rekod</span>
          <div><a class="<?= $page === 1 ? 'disabled' : '' ?>" <?= $page > 1 ? 'href="' . e(page_url($page - 1, $search, $status, $date, $sort)) . '"' : 'aria-disabled="true"' ?>>← Sebelumnya</a><span>Halaman <?= $page ?> / <?= $pages ?></span><a class="<?= $page === $pages ? 'disabled' : '' ?>" <?= $page < $pages ? 'href="' . e(page_url($page + 1, $search, $status, $date, $sort)) . '"' : 'aria-disabled="true"' ?>>Seterusnya →</a></div>
        </div>
      </section>
    </main>
  </div>
  <?php foreach ($rows as $r): ?><template id="active-data-<?= (int)$r['id'] ?>">
      <div class="active-detail-grid">
        <div><small>UID</small><strong>UID #<?= (int)$r['id'] ?></strong></div>
        <div><small>Status</small><strong><?= $r['status'] === 'ready' ? 'Ready' : 'In Progress' ?></strong></div>
        <div><small>Nama Staf/Pelajar</small><strong><?= e($r['requester_name']) ?></strong></div>
        <div><small>Jabatan/Pusat Pengajian</small><strong><?= e($r['department_name']) ?></strong></div>
        <div><small>Telefon</small><strong><?= e($r['requester_phone'] ?: '—') ?></strong></div>
        <div><small>Emel</small><strong><?= e($r['requester_email'] ?: '—') ?></strong></div>
        <div><small>Pemilikan</small><strong><?= ($r['device_ownership'] ?? '') === 'usm' ? 'Aset USM' : (($r['device_ownership'] ?? '') === 'personal' ? 'Peribadi' : '—') ?></strong></div>
        <div><small>Jenis Peranti</small><strong><?= e(($r['device_type'] ?? '') === 'Other' ? $r['device_type_other'] : ($r['device_type'] ?: '—')) ?></strong></div>
        <div><small>Jenama / Model</small><strong><?= e(trim(($r['device_brand'] ?? '') . ' ' . ($r['device_model'] ?? '')) ?: '—') ?></strong></div>
        <div><small>Serial Number</small><strong><?= e($r['device_serial'] ?: '—') ?></strong></div>
        <div><small>No. Aset USM</small><strong><?= e($r['asset_number'] ?: '—') ?></strong></div>
        <div><small>Aksesori</small><strong><?= e(implode(', ', $r['accessories']) ?: '—') ?></strong></div>
        <div><small>Keadaan Peranti</small><strong><?= e($r['device_condition'] ?: '—') ?></strong></div>
        <div class="active-full"><small>Catatan Keadaan</small>
          <p><?= nl2br(e($r['condition_notes'] ?: '—')) ?></p>
        </div>
        <div class="active-full"><small>Jenis Perkhidmatan</small><strong><?= e(implode(', ', array_map(static fn($id) => $typeNames[$id] ?? 'Tidak diketahui', $r['service_type_ids']))) ?></strong></div>
        <div><small>Pengendali Staf IT</small><strong><?= e($r['handler_name']) ?></strong></div>
        <div><small>Kos</small><strong>RM <?= number_format((float)$r['estimated_cost'], 2) ?></strong></div>
        <div><small>Tarikh/Masa Permintaan</small><strong><?= e(display_date($r['requested_at'], true)) ?></strong></div>
        <div><small>Tarikh/Masa Ready</small><strong><?= e(display_date($r['ready_at'], true)) ?></strong></div>
        <div class="active-full"><small>Butiran Perkhidmatan</small>
          <p><?= nl2br(e($r['service_details'])) ?></p>
        </div>
        <?php foreach ($r['hardware'] as $i => $h): ?><div class="active-full"><small>Perkakasan <?= $i + 1 ?></small><strong><?= e($h['hardware_type']) ?> — <?= $h['work_type'] === 'replacement' ? 'Penukaran' : 'Pemasangan baharu' ?></strong>
            <p>Model Lama: <?= e($h['old_model'] ?: '—') ?> | Model Baharu: <?= e($h['new_model']) ?></p>
            <p>No. Siri Lama: <?= e($h['old_serial'] ?: '—') ?> | No. Siri Baharu: <?= e($h['new_serial'] ?: '—') ?></p>
            <p>Catatan: <?= e($h['notes'] ?: '—') ?></p>
          </div><?php endforeach; ?>
      </div>
    </template><?php endforeach; ?>
  <dialog id="details-dialog" class="active-dialog">
    <div class="active-dialog-head">
      <div><span class="active-dialog-kicker">REKOD PERKHIDMATAN</span>
        <h2>Butiran Permintaan</h2>
      </div><button type="button" class="dialog-close" aria-label="Tutup">×</button>
    </div>
    <div id="details-body"></div>
    <div class="active-dialog-foot"><button type="button" class="active-outline dialog-close">Tutup</button></div>
  </dialog>
  <dialog id="edit-dialog" class="active-dialog">
    <div class="active-dialog-head">
      <div><span class="active-dialog-kicker">KEMAS KINI MAKLUMAT</span>
        <h2>Edit Rekod Perkhidmatan</h2>
      </div><button type="button" class="dialog-close" aria-label="Tutup">×</button>
    </div>
    <form id="edit-form" method="post" action="<?= APP_BASE ?>/aktif.php"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="edit"><input type="hidden" name="id" id="edit-id"><input type="hidden" name="return" value="<?= e($back) ?>">
      <div class="record-section-heading"><span>01</span>
        <h3>Maklumat Pemohon</h3>
      </div>
      <div class="active-edit-grid">
        <div><label for="edit-name">Nama Staf/Pelajar *</label><input id="edit-name" name="requester_name" maxlength="150" required></div>
        <div><label for="edit-department">Jabatan/Pusat Pengajian *</label><select id="edit-department" name="department_id" required><?php foreach ($departments as $item): ?><option value="<?= (int)$item['id'] ?>"><?= e($item['name']) ?></option><?php endforeach; ?></select></div>
        <div><label>No. Telefon (Pilihan)</label><input name="requester_phone" maxlength="30"></div>
        <div><label>Emel (Pilihan)</label><input type="email" name="requester_email" maxlength="190"></div>
      </div>
      <div class="record-section-heading"><span>02</span>
        <h3>Maklumat Peranti</h3>
      </div>
      <div class="record-grid device-grid">
        <div><label>Pemilikan Peranti *</label><select name="device_ownership" data-ownership required>
            <option value="usm">Aset USM</option>
            <option value="personal">Peribadi</option>
          </select></div>
        <div><label>Jenis Peranti *</label><select name="device_type" data-device-type required>
            <option value="">Pilih jenis</option>
            <option value="Laptop">Laptop</option>
            <option value="Desktop">Desktop</option>
            <option value="Printer">Printer</option>
            <option value="Other">Lain-lain</option>
          </select></div>
        <div data-other-device hidden><label>Nyatakan Jenis Peranti *</label><input name="device_type_other" maxlength="100"></div>
        <div><label>Jenama *</label><input name="device_brand" maxlength="120" required placeholder="Contoh: Dell"></div>
        <div><label>Model *</label><input name="device_model" maxlength="150" required placeholder="Contoh: Latitude 5420"></div>
        <div><label>Serial Number (Jika ada)</label><input name="device_serial" maxlength="150"></div>
        <div data-asset-field><label>No. Aset USM (Jika ada)</label><input name="asset_number" maxlength="150"></div>
        <div class="record-full"><label>Aksesori Diterima (Pilihan)</label>
          <div class="device-accessories"><label><input type="checkbox" name="accessories[]" value="Charger"> Charger</label><label><input type="checkbox" name="accessories[]" value="Mouse"> Mouse</label><label><input type="checkbox" name="accessories[]" value="Beg Laptop"> Beg Laptop</label><label><input type="checkbox" name="accessories[]" value="Lain-lain" data-other-accessory> Lain-lain</label></div><input name="accessory_other" data-accessory-other maxlength="255" placeholder="Nyatakan aksesori lain" hidden>
        </div>
        <div><label>Keadaan Peranti</label><select name="device_condition">
            <option value="unchecked">Tidak Diperiksa</option>
            <option value="good">Baik</option>
            <option value="damaged">Kerosakan Fizikal</option>
            <option value="off">Tidak Dapat Dihidupkan</option>
          </select></div>
        <div><label>Catatan Keadaan (Pilihan)</label><textarea name="condition_notes" maxlength="1000" rows="2"></textarea></div>
      </div>
      <div class="record-section-heading"><span>03</span>
        <h3>Maklumat Perkhidmatan</h3>
      </div>
      <div class="active-edit-grid">
        <div class="active-full"><label>Jenis Perkhidmatan *</label><?php $selectedTypeIds = []; ?><?php /* Include inside a form. Set $multiPrefix to 'edit-' for edit dialog. */ ?>
          <div class="service-multi" data-service-multi data-hardware-id="<?= (int)($hardwareTypeId ?? 0) ?>">
            <details>
              <summary data-service-summary>Pilih jenis perkhidmatan</summary>
              <div class="service-options">
                <?php foreach ($types as $type): ?>
                  <label><input type="checkbox" data-service-check data-label="<?= e($type['name']) ?>" name="service_type_ids[]" value="<?= (int)$type['id'] ?>" <?= in_array((int)$type['id'], $selectedTypeIds ?? [], true) ? 'checked' : '' ?>><?= e($type['name']) ?></label>
                <?php endforeach; ?>
              </div>
            </details>
            <section class="hardware-section" data-hardware-section hidden>
              <h3>Maklumat Penukaran/Pemasangan Perkakasan</h3>
              <div data-hardware-list></div>
              <button type="button" data-add-hardware>+ Tambah Perkakasan</button>
            </section>
          </div>
        </div>
        <div class="active-full"><label for="edit-details">Butiran Perkhidmatan (Pilihan)</label><textarea id="edit-details" name="service_details" rows="4" maxlength="5000"></textarea></div>
        <div><label for="edit-cost">Kos (RM) *</label><input id="edit-cost" name="estimated_cost" type="number" placeholder="0.00" min="0" max="99999999.99" step="0.01" required></div>
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
    <form method="post" action="<?= APP_BASE ?>/aktif.php"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="ready"><input type="hidden" name="id" id="ready-id"><input type="hidden" name="return" value="<?= e($back) ?>">
      <div class="active-dialog-foot"><button type="button" class="active-outline dialog-close">Batal</button><button type="submit" class="active-primary">Ya, Tandakan Ready</button></div>
    </form>
  </dialog>
  <template id="hardware-row-template">
    <div class="hardware-row">
      <div class="hardware-grid">
        <div><label>Jenis Kerja *</label><select data-field="work_type" required>
            <option value="replacement">Penukaran perkakasan</option>
            <option value="installation">Pemasangan baharu</option>
          </select></div>
        <div><label>Jenis Perkakasan *</label><input data-field="hardware_type" maxlength="120" placeholder="RAM, SSD, HDD..." required></div>
        <div data-old-fields style="display:contents">
          <div><label>Model Lama</label><input data-field="old_model" maxlength="255"></div>
          <div><label>No. Siri Lama</label><input data-field="old_serial" maxlength="150"></div>
        </div>
        <div><label>Model Baharu *</label><input data-field="new_model" maxlength="255" required></div>
        <div><label>No. Siri Baharu</label><input data-field="new_serial" maxlength="150"></div>
        <div style="grid-column:1/-1"><label>Catatan</label><textarea data-field="notes" maxlength="1000" rows="2"></textarea></div>
      </div><button type="button" data-remove-hardware>Buang Perkakasan</button>
    </div>
  </template>

  <script type="application/json" id="active-records-json">
    <?= json_encode(array_column($rows, null, 'id'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE) ?>
  </script>
</body>

</html>