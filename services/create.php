<?php
declare(strict_types=1);
require __DIR__ . '/../includes/auth.php';
require_login();
date_default_timezone_set('Asia/Kuala_Lumpur');
$errors = [];
$departments = $types = $handlers = [];
$available = false;
$values = ['requester_name' => '', 'department_id' => '', 'service_type_id' => '', 'service_details' => '', 'estimated_cost' => '0.00', 'requested_at' => date('Y-m-d\TH:i'), 'handler_id' => ''];
$success = $_SESSION['service_created'] ?? null;
unset($_SESSION['service_created']);
try {
    require __DIR__ . '/../config/database.php';
    $departments = $pdo->query('SELECT id, name FROM departments WHERE is_active=1 ORDER BY name')->fetchAll();
    $types = $pdo->query('SELECT id, name FROM service_types WHERE is_active=1 ORDER BY id')->fetchAll();
    $handlers = $pdo->query('SELECT id, name FROM handlers WHERE is_active=1 ORDER BY name')->fetchAll();
    $available = (bool) ($departments && $types && $handlers);
    if (!$available) $errors[] = 'Senarai jabatan, servis atau pengendali belum tersedia. Sila hubungi pentadbir.';
} catch (PDOException $exception) {
    error_log('Service form database error: ' . $exception->getMessage());
    $errors[] = 'Sambungan pangkalan data tidak tersedia. Sila cuba lagi.';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($values as $key => $default) {
        $values[$key] = is_string($_POST[$key] ?? null) ? trim($_POST[$key]) : '';
    }
    if (!valid_csrf()) $errors[] = 'Sesi borang tidak sah. Sila cuba lagi.';
    if ($values['requester_name'] === '' || mb_strlen($values['requester_name'], 'UTF-8') > 150) $errors[] = 'Nama staf/pelajar diperlukan (maksimum 150 aksara).';
    foreach (['department_id' => [$departments, 'jabatan/pusat pengajian'], 'service_type_id' => [$types, 'jenis servis'], 'handler_id' => [$handlers, 'pengendali IT']] as $key => [$options, $label]) {
        if (!in_array($values[$key], array_map('strval', array_column($options, 'id')), true)) $errors[] = 'Sila pilih ' . $label . ' yang sah.';
    }
    if (strlen($values['service_details']) > 10000) $errors[] = 'Maklumat servis terlalu panjang (maksimum 10,000 bait).';
    if (!preg_match('/^\d{1,8}(?:\.\d{1,2})?$/D', $values['estimated_cost'])) $errors[] = 'Anggaran kos mestilah antara RM0.00 hingga RM99,999,999.99, dengan maksimum dua tempat perpuluhan.';
    $requested = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $values['requested_at']);
    if (!$requested || $requested->format('Y-m-d\TH:i') !== $values['requested_at'] || (int) $requested->format('Y') < 1000) $errors[] = 'Sila masukkan tarikh dan masa permintaan yang sah.';
    $formToken = is_string($_POST['form_token'] ?? null) ? $_POST['form_token'] : '';
    if (!isset($_SESSION['service_forms'][$formToken])) $errors[] = 'Borang ini telah dihantar atau tamat tempoh. Sila semak dan hantar semula.';
    if (!$errors && $available) {
        try {
            $statement = $pdo->prepare("INSERT INTO service_requests (uid, requester_name, department_id, service_type_id, service_details, estimated_cost, requested_at, handler_id, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'in_progress', ?)");
            for ($attempt = 0; $attempt < 3; $attempt++) {
                $uid = 'USM-' . $requested->format('Y') . '-' . strtoupper(bin2hex(random_bytes(8)));
                try {
                    $statement->execute([$uid, $values['requester_name'], $values['department_id'], $values['service_type_id'], $values['service_details'], $values['estimated_cost'], $requested->format('Y-m-d H:i:s'), $values['handler_id'], (int) $_SESSION['user_id']]);
                    break;
                } catch (PDOException $exception) {
                    if (($exception->errorInfo[1] ?? null) !== 1062 || $attempt === 2) throw $exception;
                }
            }
            unset($_SESSION['service_forms'][$formToken]);
            $_SESSION['service_created'] = $uid;
            header('Location: ' . APP_BASE . '/services/create.php', true, 303);
            exit;
        } catch (PDOException $exception) {
            error_log('Create service error: ' . $exception->getMessage());
            $errors[] = 'Rekod tidak dapat disimpan. Sila semak pilihan anda dan cuba lagi.';
        }
    }
}
$formToken = bin2hex(random_bytes(24));
$_SESSION['service_forms'] = array_slice($_SESSION['service_forms'] ?? [], -19, null, true);
$_SESSION['service_forms'][$formToken] = true;
function service_options(array $options, string $selected): void {
    foreach ($options as $option) {
        echo '<option value="' . e((string) $option['id']) . '"' . ((string) $option['id'] === $selected ? ' selected' : '') . '>' . e($option['name']) . '</option>';
    }
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rekod Servis | Perkhidmatan USM</title>
    <link rel="stylesheet" href="<?= APP_BASE ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= APP_BASE ?>/assets/css/services.css">
    <link rel="stylesheet" href="<?= APP_BASE ?>/assets/css/sidebar.css">
    <script src="<?= APP_BASE ?>/assets/js/sidebar.js" defer></script>
</head>
<body class="workspace">
<?php $activePage = 'create'; require __DIR__ . '/../includes/sidebar.php'; ?>
<main class="workspace-content" id="main-content" tabindex="-1">
    <p class="eyebrow">PERKHIDMATAN IT</p>
    <h1>Rekod Servis</h1>
    <p class="intro">Daftar permintaan servis baharu. UID dijana secara automatik dan status bermula sebagai In Progress.</p>
    <?php if (is_string($success)): ?><div class="notice" role="status">Rekod berjaya disimpan. UID: <strong><?= e($success) ?></strong> · Status: In Progress.</div><?php endif; ?>
    <?php if ($errors): ?><div class="alert" role="alert"><strong>Sila semak maklumat berikut:</strong><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <form method="post" class="home-card service-form">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="form_token" value="<?= e($formToken) ?>">
        <h2>Maklumat permintaan</h2>
        <p>Semua medan wajib diisi kecuali maklumat tambahan.</p>
        <div class="service-grid">
            <div><label for="requester_name">Nama Staf / Pelajar</label><input id="requester_name" name="requester_name" maxlength="150" required value="<?= e($values['requester_name']) ?>"></div>
            <div><label for="department_id">Jabatan / Pusat Pengajian</label><select id="department_id" name="department_id" required><option value="">Pilih jabatan / pusat pengajian</option><?php service_options($departments, $values['department_id']); ?></select></div>
            <div><label for="service_type_id">Jenis Servis</label><select id="service_type_id" name="service_type_id" required><option value="">Pilih jenis servis</option><?php service_options($types, $values['service_type_id']); ?></select></div>
            <div><label for="handler_id">Pengendali IT</label><select id="handler_id" name="handler_id" required><option value="">Pilih pengendali</option><?php service_options($handlers, $values['handler_id']); ?></select></div>
            <div class="full-width"><label for="service_details">Maklumat Tambahan <span>(pilihan)</span></label><textarea id="service_details" name="service_details" rows="4" maxlength="2500" placeholder="Contoh: Komputer perlahan dan perlu pemasangan semula aplikasi."><?= e($values['service_details']) ?></textarea></div>
            <div><label for="estimated_cost">Anggaran Kos (RM)</label><input id="estimated_cost" name="estimated_cost" type="number" min="0" max="99999999.99" step="0.01" required value="<?= e($values['estimated_cost']) ?>"></div>
            <div><label for="requested_at">Tarikh &amp; Masa Permintaan</label><input id="requested_at" name="requested_at" type="datetime-local" min="1000-01-01T00:00" max="9999-12-31T23:59" required value="<?= e($values['requested_at']) ?>"><small>Waktu Malaysia (UTC+08:00)</small></div>
        </div>
        <div class="service-actions"><button class="primary" type="submit" <?= !$available ? 'disabled' : '' ?>>Simpan Rekod</button><a href="<?= APP_BASE ?>/home.php">Kembali</a></div>
    </form>
</main>
</body>
</html>
