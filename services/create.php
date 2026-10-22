<?php
declare(strict_types=1);
require __DIR__ . '/../includes/auth.php';
require_login();
require __DIR__ . '/../config/database.php';
date_default_timezone_set('Asia/Kuala_Lumpur');
require __DIR__ . '/service_multi.php';

$errors = [];
$selectedTypeIds = [];
$postedHardware = [];
$old = ['requester_name'=>'','department_id'=>'','service_type_id'=>'','service_details'=>'','estimated_cost'=>'','requested_at'=>date('Y-m-d\TH:i'),'handler_id'=>''];
try {
    $departments = $pdo->query('SELECT id,name FROM departments WHERE is_active=1 ORDER BY name')->fetchAll();
    $types = $pdo->query('SELECT id,name FROM service_types WHERE is_active=1 ORDER BY id')->fetchAll();
    $handlers = $pdo->query('SELECT id,name FROM handlers WHERE is_active=1 ORDER BY id')->fetchAll();
    $hardwareTypeId = hardware_service_id($types);
} catch (PDOException $ex) {
    error_log('Service form lookup: '.$ex->getMessage());
    http_response_code(503);
    exit('Tidak dapat memuatkan pilihan borang. Sila semak sambungan pangkalan data.');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $deviceData=[];
    try { $deviceData=device_input($_POST); } catch (InvalidArgumentException $ex) { $errors[]=$ex->getMessage(); }
    $postedHardware = is_array($_POST['hardware'] ?? null) ? $_POST['hardware'] : [];
    try {
        $selectedTypeIds = multi_ids($_POST['service_type_ids'] ?? null, $types);
        $hardwareRows = multi_hardware($postedHardware, $hardwareTypeId !== null && in_array($hardwareTypeId,$selectedTypeIds,true));
        $old['service_type_id'] = (string)$selectedTypeIds[0];
    } catch (InvalidArgumentException $ex) { $errors[] = $ex->getMessage(); $hardwareRows = []; }
    foreach ($old as $key=>$value) {
        if ($key === 'service_type_id') continue;
        $old[$key] = is_string($_POST[$key] ?? null) ? trim($_POST[$key]) : '';
    }
    if ($old['estimated_cost'] === '') $old['estimated_cost'] = '0.00'; // Kos kosong dianggap RM0.00
    if (!valid_csrf()) $errors[] = 'Sesi borang tidak sah. Muat semula halaman.';
    if ($old['requester_name']==='' || mb_strlen($old['requester_name'])>150) $errors[]='Nama staf/pelajar wajib diisi (maksimum 150 aksara).';
    $allowed = function(array $items, string $id): bool {
        if (!ctype_digit($id) || (int)$id < 1) return false;
        foreach ($items as $item) if ((int)$item['id'] === (int)$id) return true;
        return false;
    };
    if (!$allowed($departments,$old['department_id'])) $errors[]='Sila pilih jabatan/pusat pengajian yang sah.';
    if (!$allowed($types,$old['service_type_id'])) $errors[]='Sila pilih jenis perkhidmatan yang sah.';
    if (mb_strlen($old['service_details'])>5000) $errors[]='Butiran perkhidmatan terlalu panjang (maksimum 5000 aksara).';
    if ($old['estimated_cost']==='' || !preg_match('/^\d{1,8}(?:\.\d{1,2})?$/D',$old['estimated_cost']) || (float)$old['estimated_cost']>99999999.99) $errors[]='Masukkan kos yang sah (RM0.00 atau lebih).';
    $when = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i',$old['requested_at']);
    if (!$when || $when->format('Y-m-d\TH:i')!==$old['requested_at']) $errors[]='Tarikh dan masa permintaan tidak sah.';
    if (!$allowed($handlers,$old['handler_id'])) $errors[]='Sila pilih pengendali staf IT yang sah.';
    if (!$errors) {
        try {
            // Serialize UID allocation across simultaneous service submissions.
            // Existing legacy UIDs are preserved; only all-digit UIDs count.
            $lockName = 'usm_service_numeric_uid_v1';
            $lockStmt = $pdo->prepare('SELECT GET_LOCK(:lock_name, 10)');
            $lockStmt->execute(['lock_name'=>$lockName]);
            if ((int)$lockStmt->fetchColumn() !== 1) {
                throw new RuntimeException('Tidak dapat mengunci penjana UID. Sila cuba lagi.');
            }
            $uidLockHeld = true;
            $maxUid = $pdo->query("SELECT COALESCE(MAX(CAST(uid AS UNSIGNED)), 0) FROM service_requests WHERE uid REGEXP '^[0-9]+$'")->fetchColumn();
            $uid = (string)((int)$maxUid + 1);
            if (strlen($uid) > 40) throw new RuntimeException('Had UID telah dicapai.');
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('INSERT INTO service_requests (uid,requester_name,department_id,service_type_id,service_details,estimated_cost,requested_at,handler_id,status,created_by) VALUES (:uid,:requester_name,:department_id,:service_type_id,:service_details,:estimated_cost,:requested_at,:handler_id,\'in_progress\',:created_by)');
            $stmt->execute(['uid'=>$uid,'requester_name'=>$old['requester_name'],'department_id'=>(int)$old['department_id'],'service_type_id'=>(int)$old['service_type_id'],'service_details'=>$old['service_details'],'estimated_cost'=>number_format((float)$old['estimated_cost'],2,'.',''),'requested_at'=>$when->format('Y-m-d H:i:s'),'handler_id'=>(int)$old['handler_id'],'created_by'=>(int)$_SESSION['user_id']]);
            $requestId=(int)$pdo->lastInsertId();
            save_multi($pdo,$requestId,$selectedTypeIds,$hardwareRows);
            save_device($pdo,$requestId,$deviceData);
            $pdo->commit();
            $release = $pdo->prepare('SELECT RELEASE_LOCK(:lock_name)');
            $release->execute(['lock_name'=>$lockName]);
            $uidLockHeld = false;
            $_SESSION['service_created_uid']=$uid;
            header('Location: '.APP_BASE.'/services/index.php?created=1',true,303);
            exit;
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('Create service failed: '.$ex->getMessage());
            $errors[]='Rekod tidak dapat disimpan. Sila cuba lagi atau hubungi penyelenggara.';
        } finally {
            if (!empty($uidLockHeld)) {
                try {
                    $release = $pdo->prepare('SELECT RELEASE_LOCK(:lock_name)');
                    $release->execute(['lock_name'=>$lockName]);
                } catch (Throwable $releaseError) {
                    error_log('Release UID lock failed: '.$releaseError->getMessage());
                }
            }
        }
    }
}
?>
<!doctype html><html lang="ms"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Rekod Perkhidmatan | e-Servis IT@AMDI</title>
<link rel="stylesheet" href="<?= APP_BASE ?>/assets/css/style.css"><link rel="stylesheet" href="<?= APP_BASE ?>/assets/css/sidebar.css"><link rel="stylesheet" href="<?= APP_BASE ?>/assets/css/record-service.css"><link rel="stylesheet" href="<?= APP_BASE ?>/assets/css/active-requests.css"><script src="<?= APP_BASE ?>/assets/js/active-requests.js" defer></script><script src="<?= APP_BASE ?>/assets/js/sidebar.js" defer></script><style>
/* Wizard layout scoped to create.php; shared sidebar and Izzah's pages unchanged. */
.stepper-card{max-width:1060px;margin-inline:auto;border:1px solid #eae5f2!important;border-radius:22px!important;box-shadow:0 15px 38px rgba(44,25,76,.055)!important;overflow:hidden}
.stepper-intro{display:flex;align-items:center;justify-content:space-between;gap:15px;margin-bottom:24px}
.stepper-intro h2{margin:4px 0 7px!important;font-size:clamp(20px,2.5vw,26px)!important;color:#30204c}
.stepper-intro p{margin:0;color:#81778d;font-size:13px}
.stepper-intro .stepper-kicker{color:#67389d;font-weight:750;font-size:11px;letter-spacing:1.2px;text-transform:uppercase}
.stepper-counter{white-space:nowrap;border:1px solid #e7ddf4;background:#f7f2fc;border-radius:30px;padding:8px 13px;color:#61368e;font-weight:750;font-size:12px}
.service-stepper{display:flex;align-items:center;background:#faf8fd;border:1px solid #eee8f5;border-radius:16px;padding:18px 22px;margin:0 0 30px;gap:8px}
.stepper-item{display:flex;align-items:center;gap:10px;border:0;background:none;padding:4px;color:#8a8095;cursor:pointer;font:inherit;font-size:13px;font-weight:700;white-space:nowrap}
.stepper-dot{width:37px;height:37px;display:grid;place-items:center;border:2px solid #e5ddeb;border-radius:50%;background:#fff;color:#8b8096;font-size:14px;transition:all .2s}
.stepper-item.is-active .stepper-dot{background:#56308b;color:#fff;border-color:#56308b;box-shadow:0 0 0 5px #ede4f7}
.stepper-item.is-active{color:#3c235b}.stepper-item.is-done .stepper-dot{background:#ede5f8;color:#4c287d;border-color:#d9c5ed}
.stepper-line{flex:1;height:2px;min-width:12px;background:#e8dfef}
.stepper-line.is-done{background:#7d50b0}
.wizard-panel{animation:wizardIn .22s ease-out}
.wizard-panel[hidden],.wizard-review[hidden],.wizard-actions [hidden]{display:none!important}
.wizard-review{padding:5px 0 14px}.wizard-review h3{font-size:21px;color:#32204f;margin:0 0 8px}.wizard-review>p{font-size:13px;color:#81778d;margin:0 0 20px}
.review-group{padding:17px 19px;border:1px solid #ebe5f3;border-radius:13px;margin:0 0 13px;background:#fdfcff}
.review-group h4{margin:0 0 12px;color:#56308b;font-size:14px}
.review-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px 20px}
.review-cell{min-width:0;overflow-wrap:anywhere}.review-cell span{display:block;color:#81778d;font-size:11px;margin-bottom:4px}.review-cell strong{display:block;color:#322440;font-size:13px;font-weight:650}
.wizard-actions{display:flex;align-items:center;justify-content:flex-end;gap:10px;margin-top:28px;padding-top:21px;border-top:1px solid #ede8f4}
.wizard-actions button{border:0;border-radius:11px;padding:12px 20px;font:inherit;font-weight:700;cursor:pointer}
.wizard-actions .wizard-back{background:#f2edf8;color:#4d287b}.wizard-actions .wizard-next,.wizard-actions .wizard-submit{background:#56308b;color:#fff}
.wizard-actions .wizard-cancel{margin-right:auto;text-decoration:none;color:#81778d;font-size:13px}
.record-form .wizard-panel :is(input,select,textarea):focus{outline:2px solid #b998e0;outline-offset:1px}
@keyframes wizardIn{from{opacity:.4;transform:translateY(6px)}to{opacity:1;transform:translateY(0)}}
@media(max-width:640px){.stepper-intro{align-items:flex-start;flex-direction:column}.service-stepper{padding:15px 9px;gap:3px}.stepper-item{flex-direction:column;gap:7px;font-size:10px;flex:0 1 100px;white-space:normal;text-align:center}.stepper-dot{width:32px;height:32px}.stepper-line{min-width:5px}.review-grid{grid-template-columns:1fr}.wizard-actions{flex-wrap:wrap}.wizard-actions .wizard-cancel{width:100%;text-align:center;order:3;margin-top:8px}}
@media(prefers-reduced-motion:reduce){.wizard-panel{animation:none}}
</style></head>
<body class="workspace record-page">
<?php $activePage='create'; require __DIR__.'/../includes/sidebar.php'; ?>
<div class="record-area"><header class="record-header"><div><p class="eyebrow">REKOD &amp; JEJAK PERKHIDMATAN</p><h1>Rekod Perkhidmatan</h1></div><form action="<?= APP_BASE ?>/logout.php" method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><button class="record-logout" type="submit">Log Keluar ↗</button></form></header>
<main class="record-main" id="main-content" tabindex="-1"><p class="record-hint">Masukkan butiran perkhidmatan di bawah. Medan bertanda * diperlukan.</p>
<?php if ($errors): ?><div class="record-errors" role="alert"><strong>Sila semak borang:</strong><ul><?php foreach($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<section class="record-card stepper-card">
<div class="stepper-intro"><div><p class="stepper-kicker">e-Servis IT@AMDI</p><h2>Rekod Perkhidmatan Baharu</h2><p>Lengkapkan tiga langkah di bawah untuk merekod permintaan.</p></div><span class="stepper-counter" id="step-counter">Langkah 1 / 3</span></div>
<nav class="service-stepper" aria-label="Langkah rekod perkhidmatan">
<button type="button" class="stepper-item is-active" data-step-target="0" aria-current="step"><span class="stepper-dot">1</span><span class="stepper-label">Pemohon</span></button>
<span class="stepper-line" aria-hidden="true"></span>
<button type="button" class="stepper-item" data-step-target="1"><span class="stepper-dot">2</span><span class="stepper-label">Peranti</span></button>
<span class="stepper-line" aria-hidden="true"></span>
<button type="button" class="stepper-item" data-step-target="2"><span class="stepper-dot">3</span><span class="stepper-label">Perkhidmatan</span></button>
</nav>
<form method="post" action="<?= APP_BASE ?>/services/create.php" class="record-form" id="service-wizard" novalidate><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<section class="wizard-panel" data-step="0"><div class="record-section-heading"><span>01</span><h3>Maklumat Pemohon</h3></div><div class="record-grid"><div><label for="requester_name">Nama Staf/Pelajar *</label><input id="requester_name" name="requester_name" maxlength="150" required value="<?= e($old['requester_name']) ?>"></div>
<div><label for="department_id">Jabatan/Pusat Pengajian *</label><select id="department_id" name="department_id" required><option value="">Pilih jabatan/pusat pengajian</option><?php foreach($departments as $item): ?><option value="<?= (int)$item['id'] ?>" <?= (string)$item['id']===$old['department_id']?'selected':'' ?>><?= e($item['name']) ?></option><?php endforeach; ?></select></div>
<div><label for="requester_phone">No. Telefon (Pilihan)</label><input id="requester_phone" name="requester_phone" maxlength="30" value="<?= e($deviceData['requester_phone']??'') ?>"></div><div><label for="requester_email">Emel (Pilihan)</label><input type="email" id="requester_email" name="requester_email" maxlength="190" value="<?= e($deviceData['requester_email']??'') ?>"></div></div>
</section><section class="wizard-panel" data-step="1" hidden><div class="record-section-heading"><span>02</span><h3>Maklumat Peranti</h3></div>
<div class="record-grid device-grid">
<div><label>Pemilikan Peranti *</label><select name="device_ownership" data-ownership required><option value="usm">Aset USM</option><option value="personal">Peribadi</option></select></div>
<div><label>Jenis Peranti *</label><select name="device_type" data-device-type required><option value="">Pilih jenis</option><option value="Laptop">Laptop</option><option value="Desktop">Desktop</option><option value="Printer">Printer</option><option value="Other">Lain-lain</option></select></div>
<div data-other-device hidden><label>Nyatakan Jenis Peranti *</label><input name="device_type_other" maxlength="100"></div>
<div><label>Jenama *</label><input name="device_brand" maxlength="120" required placeholder="Contoh: Dell"></div>
<div><label>Model *</label><input name="device_model" maxlength="150" required placeholder="Contoh: Latitude 5420"></div>
<div><label>Serial Number (Jika ada)</label><input name="device_serial" maxlength="150"></div>
<div data-asset-field><label>No. Aset USM (Jika ada)</label><input name="asset_number" maxlength="150"></div>
<div class="record-full"><label>Aksesori Diterima (Pilihan)</label><div class="device-accessories"><label><input type="checkbox" name="accessories[]" value="Charger"> Charger</label><label><input type="checkbox" name="accessories[]" value="Mouse"> Mouse</label><label><input type="checkbox" name="accessories[]" value="Beg Laptop"> Beg Laptop</label><label><input type="checkbox" name="accessories[]" value="Lain-lain" data-other-accessory> Lain-lain</label></div><input name="accessory_other" data-accessory-other maxlength="255" placeholder="Nyatakan aksesori lain" hidden></div>
<div><label>Keadaan Peranti</label><select name="device_condition"><option value="unchecked">Tidak Diperiksa</option><option value="good">Baik</option><option value="damaged">Kerosakan Fizikal</option><option value="off">Tidak Dapat Dihidupkan</option></select></div>
<div><label>Catatan Keadaan (Pilihan)</label><textarea name="condition_notes" maxlength="1000" rows="2"></textarea></div>
</div>
</section><section class="wizard-panel" data-step="2" hidden><div class="record-section-heading"><span>03</span><h3>Maklumat Perkhidmatan</h3></div><div class="record-grid"><div class="record-full"><label>Jenis Perkhidmatan *</label><?php /* Include inside a form. Set $multiPrefix to 'edit-' for edit dialog. */ ?>
<div class="service-multi" data-service-multi data-hardware-id="<?= (int)($hardwareTypeId ?? 0) ?>">
  <details><summary data-service-summary>Pilih jenis perkhidmatan</summary><div class="service-options">
    <?php foreach ($types as $type): ?>
    <label><input type="checkbox" data-service-check data-label="<?= e($type['name']) ?>" name="service_type_ids[]" value="<?= (int)$type['id'] ?>" <?= in_array((int)$type['id'], $selectedTypeIds ?? [], true) ? 'checked' : '' ?>><?= e($type['name']) ?></label>
    <?php endforeach; ?>
  </div></details>
  <section class="hardware-section" data-hardware-section hidden>
    <h3>Maklumat Penukaran/Pemasangan Perkakasan</h3>
    <div data-hardware-list></div>
    <button type="button" data-add-hardware>+ Tambah Perkakasan</button>
  </section>
</div>
</div>
<div class="record-full"><label for="service_details">Butiran Jenis Perkhidmatan (Pilihan)</label><textarea id="service_details" name="service_details" rows="4" maxlength="5000"><?= e($old['service_details']) ?></textarea></div>
<div><label for="estimated_cost">Kos (RM) *</label><input id="estimated_cost" name="estimated_cost" type="number" min="0" max="99999999.99" step="0.01" placeholder="0.00" value="<?= e($old['estimated_cost']) ?>" required></div>
<div><label for="requested_at">Tarikh/Masa Permintaan *</label><input id="requested_at" name="requested_at" type="datetime-local" value="<?= e($old['requested_at']) ?>" required></div>
<div class="record-full"><label for="handler_id">Nama Pengendali Staf IT *</label><select id="handler_id" name="handler_id" required><option value="">Pilih pengendali staf IT</option><?php foreach($handlers as $item): ?><option value="<?= (int)$item['id'] ?>" <?= (string)$item['id']===$old['handler_id']?'selected':'' ?>><?= e($item['name']) ?></option><?php endforeach; ?></select></div></div>
</section>
<div class="wizard-review" id="wizard-review" hidden aria-live="polite">
<h3>Semak Butiran</h3><p>Pastikan semua maklumat betul sebelum simpan.</p>
<div id="wizard-review-content"></div>
</div>
<div class="record-actions wizard-actions">
<a href="<?= APP_BASE ?>/home.php" class="wizard-cancel">Batal</a>
<button type="button" class="wizard-back" id="wizard-back" hidden>← Kembali</button>
<button type="button" class="wizard-next" id="wizard-next">Seterusnya →</button>
<button type="submit" class="wizard-submit" id="wizard-submit" hidden>Simpan Rekod ✓</button>
</div>
</form></section></main></div><template id="hardware-row-template"><div class="hardware-row">
 <div class="hardware-grid">
  <div><label>Jenis Kerja *</label><select data-field="work_type" required><option value="replacement">Penukaran perkakasan</option><option value="installation">Pemasangan baharu</option></select></div>
  <div><label>Jenis Perkakasan *</label><input data-field="hardware_type" maxlength="120" placeholder="RAM, SSD, HDD..." required></div>
  <div data-old-fields style="display:contents"><div><label>Model Lama</label><input data-field="old_model" maxlength="255"></div><div><label>No. Siri Lama</label><input data-field="old_serial" maxlength="150"></div></div>
  <div><label>Model Baharu *</label><input data-field="new_model" maxlength="255" required></div>
  <div><label>No. Siri Baharu</label><input data-field="new_serial" maxlength="150"></div>
  <div style="grid-column:1/-1"><label>Catatan</label><textarea data-field="notes" maxlength="1000" rows="2"></textarea></div>
 </div><button type="button" data-remove-hardware>Buang Perkakasan</button>
</div></template>
<script>document.addEventListener("DOMContentLoaded",()=>{const root=document.querySelector("[data-service-multi]");if(root && root.multiSet)root.multiSet(<?= json_encode($selectedTypeIds) ?>,<?= json_encode($postedHardware,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>);const form=document.querySelector(".record-form");if(form&&window.setDeviceForm)window.setDeviceForm(form,<?= json_encode($deviceData,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>);});</script><script>
document.addEventListener('DOMContentLoaded', function(){
 const form=document.getElementById('service-wizard');
 if(!form)return;
 const panels=[...form.querySelectorAll('.wizard-panel')], steps=[...document.querySelectorAll('[data-step-target]')];
 const review=document.getElementById('wizard-review'), reviewContent=document.getElementById('wizard-review-content');
 const back=document.getElementById('wizard-back'), next=document.getElementById('wizard-next'), submit=document.getElementById('wizard-submit'), counter=document.getElementById('step-counter');
 let current=0, highest=0, reviewing=false;
 const costInput=document.getElementById('estimated_cost');
 if(costInput){
  // Kos bermula kosong supaya boleh terus menaip; jika dibiarkan kosong, RM0.00 digunakan.
  costInput.addEventListener('blur',()=>{if(costInput.value.trim()==='')costInput.value='0.00';});
  costInput.addEventListener('focus',()=>{if(costInput.value==='0.00')costInput.select();});
 }

 function visibleFields(panel){return [...panel.querySelectorAll('input,select,textarea')].filter(el=>!el.disabled&&!el.closest('[hidden]')&&el.getClientRects().length>0);}
 function validation(step){
  if(step===2 && costInput && costInput.value.trim()==='') costInput.value='0.00';
  const fields=visibleFields(panels[step]);
  for(const el of fields){
   if(!el.checkValidity()){el.reportValidity();el.focus();return false;}
  }
  if(step===2){
   const choices=form.querySelectorAll('input[name="service_type_ids[]"]:checked');
   if(!choices.length){const d=form.querySelector('[data-service-multi] details');d.open=true;d.scrollIntoView({behavior:'smooth',block:'center'});alert('Sila pilih sekurang-kurangnya satu jenis perkhidmatan.');return false;}
  }
  return true;
 }
 function show(n,summary=false){
  current=n;reviewing=summary;
  panels.forEach((p,i)=>p.hidden=summary||i!==n);
  review.hidden=!summary;
  steps.forEach((el,i)=>{
   el.classList.toggle('is-active',!summary&&i===n);
   el.classList.toggle('is-done',summary||i<n);
   if(!summary&&i===n)el.setAttribute('aria-current','step');else el.removeAttribute('aria-current');
   el.querySelector('.stepper-dot').textContent=summary||i<n?'✓':String(i+1);
  });
  document.querySelectorAll('.stepper-line').forEach((line,i)=>line.classList.toggle('is-done',summary||i<n));
  counter.textContent=summary?'Semak Butiran':'Langkah '+(n+1)+' / 3';
  back.hidden=!summary&&n===0;next.hidden=summary;submit.hidden=!summary;
  next.textContent=n===2?'Semak Butiran →':'Seterusnya →';
  document.querySelector('.stepper-card').scrollIntoView({block:'start',behavior:'smooth'});
 }
 function safeValue(field){
  if(field.type==='checkbox')return field.checked?(field.closest('label')?.textContent.trim()||field.parentElement?.textContent.trim()||field.value):'';
  if(field.tagName==='SELECT')return field.selectedOptions[0]?.textContent.trim()||'—';
  return field.value.trim()||'—';
 }
 function buildReview(){
  reviewContent.replaceChildren();
  const sections=['Maklumat Pemohon','Maklumat Peranti','Maklumat Perkhidmatan'];
  const labelOverrides={device_ownership:'Pemilikan Peranti',device_type:'Jenis Peranti',device_type_other:'Jenis Peranti (Lain-lain)',device_brand:'Jenama',device_model:'Model',device_serial:'Serial Number',asset_number:'No. Aset USM',accessories:'Aksesori Diterima',accessory_other:'Aksesori Lain-lain',device_condition:'Keadaan Peranti',condition_notes:'Catatan Keadaan',service_type_ids:'Jenis Perkhidmatan',requester_name:'Nama Staf/Pelajar',department_id:'Jabatan/Pusat Pengajian',requester_phone:'No. Telefon',requester_email:'Emel',service_details:'Butiran Jenis Perkhidmatan',estimated_cost:'Kos (RM)',requested_at:'Tarikh/Masa Permintaan',handler_id:'Nama Pengendali Staf IT'};
  panels.forEach((panel,i)=>{
   const group=document.createElement('section');group.className='review-group';
   const h=document.createElement('h4');h.textContent=String(i+1).padStart(2,'0')+' — '+sections[i];group.append(h);
   const grid=document.createElement('div');grid.className='review-grid';
   // Read all panels, including steps hidden by the wizard. Ignore conditional fields that are hidden within a step.
   const fields=[...panel.querySelectorAll('input,select,textarea')].filter(el=>{
    if(!el.name||el.type==='hidden'||el.disabled)return false;
    if(el.closest('[data-other-device][hidden], [data-asset-field][hidden], [data-hardware-section][hidden]'))return false;
    if(el.hasAttribute('hidden'))return false;
    if(['checkbox','radio'].includes(el.type)&&!el.checked)return false;
    return true;
   });
   const grouped=new Map();
   fields.forEach(field=>{
    const name=field.name.replace(/\[\]$/,'');
    if(!grouped.has(name))grouped.set(name,[]);
    grouped.get(name).push(field);
   });
   grouped.forEach((items,name)=>{
    const values=items.map(field=>{
     if(field.type==='checkbox'||field.type==='radio')return field.dataset.label||field.closest('label')?.textContent.trim()||field.value;
     if(field.tagName==='SELECT')return field.selectedOptions[0]?.textContent.trim()||'—';
     return field.value.trim()||'—';
    }).filter(v=>v&&v!=='—');
    const cell=document.createElement('div');cell.className='review-cell';
    const small=document.createElement('span');small.textContent=labelOverrides[name]||items[0].closest('div')?.querySelector('label')?.textContent.trim()||name.replaceAll('_',' ');
    const strong=document.createElement('strong');strong.textContent=values.join(', ')||'—';
    cell.append(small,strong);grid.append(cell);
   });
   group.append(grid);reviewContent.append(group);
  });
 }
 next.addEventListener('click',()=>{
  if(!validation(current))return;
  if(current<2){highest=Math.max(highest,current+1);show(current+1);}
  else{buildReview();show(2,true);}
 });
 back.addEventListener('click',()=>show(reviewing?2:Math.max(0,current-1)));
 steps.forEach(btn=>btn.addEventListener('click',()=>{
  const target=Number(btn.dataset.stepTarget);
  if(reviewing){show(target);return;}
  if(target<=highest){show(target);return;}
  if(target===current+1&&validation(current)){highest=target;show(target);}
 }));
 form.addEventListener('submit',function(e){
  if(!reviewing){e.preventDefault();if(validation(current)){buildReview();show(2,true);}return;}
  for(let i=0;i<3;i++){show(i);if(!validation(i)){e.preventDefault();return;}}
  reviewing=true; // native POST is allowed; PHP remains source of truth
 });
 show(0);
});
</script></body></html>
