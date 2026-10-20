<?php
declare(strict_types=1);
require __DIR__ . '/../includes/auth.php';
require_login();
require __DIR__ . '/../config/database.php';
date_default_timezone_set('Asia/Kuala_Lumpur');
$errors = [];
$old = ['requester_name'=>'','department_id'=>'','service_type_id'=>'','service_details'=>'','estimated_cost'=>'','requested_at'=>date('Y-m-d\TH:i'),'handler_id'=>''];
try {
    $departments = $pdo->query('SELECT id,name FROM departments WHERE is_active=1 ORDER BY name')->fetchAll();
    $types = $pdo->query('SELECT id,name FROM service_types WHERE is_active=1 ORDER BY id')->fetchAll();
    $handlers = $pdo->query('SELECT id,name FROM handlers WHERE is_active=1 ORDER BY id')->fetchAll();
} catch (PDOException $ex) {
    error_log('Service form lookup: '.$ex->getMessage());
    http_response_code(503);
    exit('Tidak dapat memuatkan pilihan borang. Sila semak sambungan pangkalan data.');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($old as $key=>$value) {
        $old[$key] = is_string($_POST[$key] ?? null) ? trim($_POST[$key]) : '';
    }
    if (!valid_csrf()) $errors[] = 'Sesi borang tidak sah. Muat semula halaman.';
    if ($old['requester_name']==='' || mb_strlen($old['requester_name'])>150) $errors[]='Nama staf/pelajar wajib diisi (maksimum 150 aksara).';
    $allowed = function(array $items, string $id): bool {
        if (!ctype_digit($id) || (int)$id < 1) return false;
        foreach ($items as $item) if ((int)$item['id'] === (int)$id) return true;
        return false;
    };
    if (!$allowed($departments,$old['department_id'])) $errors[]='Sila pilih jabatan/pusat pengajian yang sah.';
    if (!$allowed($types,$old['service_type_id'])) $errors[]='Sila pilih jenis perkhidmatan yang sah.';
    if ($old['service_details']==='' || mb_strlen($old['service_details'])>5000) $errors[]='Butiran perkhidmatan wajib diisi (maksimum 5000 aksara).';
    if ($old['estimated_cost']==='' || !preg_match('/^\d{1,8}(?:\.\d{1,2})?$/D',$old['estimated_cost']) || (float)$old['estimated_cost']>99999999.99) $errors[]='Masukkan kos yang sah (RM0.00 atau lebih).';
    $when = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i',$old['requested_at']);
    if (!$when || $when->format('Y-m-d\TH:i')!==$old['requested_at']) $errors[]='Tarikh dan masa permintaan tidak sah.';
    if (!$allowed($handlers,$old['handler_id'])) $errors[]='Sila pilih pengendali staf IT yang sah.';
    if (!$errors) {
        try {
            $stmt = $pdo->prepare('INSERT INTO service_requests (uid,requester_name,department_id,service_type_id,service_details,estimated_cost,requested_at,handler_id,status,created_by) VALUES (:uid,:requester_name,:department_id,:service_type_id,:service_details,:estimated_cost,:requested_at,:handler_id,\'in_progress\',:created_by)');
            $saved = false;
            for ($attempt=0; $attempt<4 && !$saved; $attempt++) {
                $uid = 'USM-'.$when->format('Y').'-'.strtoupper(bin2hex(random_bytes(5)));
                try {
                    $stmt->execute(['uid'=>$uid,'requester_name'=>$old['requester_name'],'department_id'=>(int)$old['department_id'],'service_type_id'=>(int)$old['service_type_id'],'service_details'=>$old['service_details'],'estimated_cost'=>number_format((float)$old['estimated_cost'],2,'.',''),'requested_at'=>$when->format('Y-m-d H:i:s'),'handler_id'=>(int)$old['handler_id'],'created_by'=>(int)$_SESSION['user_id']]);
                    $saved = true;
                } catch (PDOException $ex) {
                    if ($ex->getCode()!=='23000' || !str_contains($ex->getMessage(),'uid')) throw $ex;
                }
            }
            if (!$saved) throw new RuntimeException('Tidak dapat menjana UID unik.');
            $_SESSION['service_created_uid']=$uid;
            header('Location: '.APP_BASE.'/services/index.php?created=1',true,303);
            exit;
        } catch (Throwable $ex) {
            error_log('Create service failed: '.$ex->getMessage());
            $errors[]='Rekod tidak dapat disimpan. Sila cuba lagi atau hubungi penyelenggara.';
        }
    }
}
?>
<!doctype html><html lang="ms"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Rekod Perkhidmatan | USM</title>
<link rel="stylesheet" href="<?= APP_BASE ?>/assets/css/style.css"><link rel="stylesheet" href="<?= APP_BASE ?>/assets/css/sidebar.css"><link rel="stylesheet" href="<?= APP_BASE ?>/assets/css/record-service.css"><script src="<?= APP_BASE ?>/assets/js/sidebar.js" defer></script></head>
<body class="workspace record-page">
<?php $activePage='create'; require __DIR__.'/../includes/sidebar.php'; ?>
<div class="record-area"><header class="record-header"><div><p class="eyebrow">REKOD &amp; JEJAK PERKHIDMATAN</p><h1>Record Service</h1></div><form action="<?= APP_BASE ?>/logout.php" method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><button class="record-logout" type="submit">Log Keluar ↗</button></form></header>
<main class="record-main" id="main-content" tabindex="-1"><p class="record-hint">Masukkan butiran perkhidmatan di bawah. Medan bertanda * diperlukan.</p>
<?php if ($errors): ?><div class="record-errors" role="alert"><strong>Sila semak borang:</strong><ul><?php foreach($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<section class="record-card"><h2>Ketibaan Staf &amp; Rekod Perkhidmatan</h2>
<form method="post" action="<?= APP_BASE ?>/services/create.php" class="record-form"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<div class="record-grid"><div><label for="requester_name">Nama Staf/Pelajar *</label><input id="requester_name" name="requester_name" maxlength="150" required value="<?= e($old['requester_name']) ?>"></div>
<div><label for="department_id">Jabatan/Pusat Pengajian *</label><select id="department_id" name="department_id" required><option value="">Pilih jabatan/pusat pengajian</option><?php foreach($departments as $item): ?><option value="<?= (int)$item['id'] ?>" <?= (string)$item['id']===$old['department_id']?'selected':'' ?>><?= e($item['name']) ?></option><?php endforeach; ?></select></div>
<div class="record-full"><label for="service_type_id">Jenis Perkhidmatan *</label><select id="service_type_id" name="service_type_id" required><option value="">Pilih jenis perkhidmatan</option><?php foreach($types as $item): ?><option value="<?= (int)$item['id'] ?>" <?= (string)$item['id']===$old['service_type_id']?'selected':'' ?>><?= e($item['name']) ?></option><?php endforeach; ?></select></div>
<div class="record-full"><label for="service_details">Butiran Jenis Perkhidmatan *</label><textarea id="service_details" name="service_details" rows="4" maxlength="5000" required><?= e($old['service_details']) ?></textarea></div>
<div><label for="estimated_cost">Kos (RM) *</label><input id="estimated_cost" name="estimated_cost" type="number" min="0" max="99999999.99" step="0.01" placeholder="0.00" value="<?= e($old['estimated_cost']) ?>" required></div>
<div><label for="requested_at">Tarikh/Masa Permintaan *</label><input id="requested_at" name="requested_at" type="datetime-local" value="<?= e($old['requested_at']) ?>" required></div>
<div class="record-full"><label for="handler_id">Nama Pengendali Staf IT *</label><select id="handler_id" name="handler_id" required><option value="">Pilih pengendali staf IT</option><?php foreach($handlers as $item): ?><option value="<?= (int)$item['id'] ?>" <?= (string)$item['id']===$old['handler_id']?'selected':'' ?>><?= e($item['name']) ?></option><?php endforeach; ?></select></div></div>
<div class="record-actions"><button type="submit">Simpan / Rekod Perkhidmatan</button><a href="<?= APP_BASE ?>/home.php">Batal</a></div>
</form></section></main></div></body></html>
