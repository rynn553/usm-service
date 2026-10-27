<?php
declare(strict_types=1);

require __DIR__ . '/../includes/auth.php';
require_login();

require __DIR__ . '/../config/database.php';

$activePage = 'payment';
    date_default_timezone_set('Asia/Kuala_Lumpur');

$paymentError = '';
$paymentDate = date('Y-m-d');
$referenceNo = '';
$pickupConfirmed = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedUid = is_string($_POST['uid'] ?? null)
        ? trim($_POST['uid'])
        : '';

    $paymentDate = is_string($_POST['payment_date'] ?? null)
        ? trim($_POST['payment_date'])
        : '';

    $referenceNo = is_string($_POST['reference_no'] ?? null)
        ? trim($_POST['reference_no'])
        : '';

    $pickupConfirmed = ($_POST['pickup_confirmed'] ?? null) === '1';

    $parsedDate = DateTimeImmutable::createFromFormat(
        '!Y-m-d',
        $paymentDate
    );

    if (!valid_csrf()) {
        $paymentError = 'Sesi borang tidak sah. Sila cuba lagi.';
    } elseif ($submittedUid === '' || strlen($submittedUid) > 40) {
        $paymentError = 'UID tidak sah.';
    } elseif (!$pickupConfirmed) {
        $paymentError = 'Sila sahkan pengambilan terlebih dahulu.';
    } elseif (
        !$parsedDate ||
        $parsedDate->format('Y-m-d') !== $paymentDate ||
        $paymentDate < '1000-01-01' ||
        $paymentDate > date('Y-m-d')
    ) {
        $paymentError = 'Tarikh bayaran tidak sah atau melebihi hari ini.';
    } elseif (
        $referenceNo === '' ||
        mb_strlen($referenceNo, 'UTF-8') > 100
    ) {
        $paymentError = 'No. Rujukan diperlukan, maksimum 100 aksara.';
    } else {
        try {
            $statement = $pdo->prepare(
                "UPDATE service_requests
                 SET status = 'completed',
                     picked_up_at = NOW(),
                     payment_date = :payment_date,
                     reference_no = :reference_no,
                     completed_at = NOW(),
                     completed_by = :completed_by
                 WHERE uid = :uid
                   AND status = 'ready'"
            );

            $statement->execute([
                'payment_date' => $paymentDate,
                'reference_no' => $referenceNo,
                'completed_by' => (int) $_SESSION['user_id'],
                'uid' => $submittedUid,
            ]);

            if ($statement->rowCount() === 1) {
                $_SESSION['payment_success'] =
                    'Pengambilan dan bayaran berjaya disahkan.';

                header(
                    'Location: ' . APP_BASE .
                    '/payments/index.php?uid=' .
                    rawurlencode($submittedUid),
                    true,
                    303
                );
                exit;
            }

            $paymentError =
                'Rekod tidak ditemui atau statusnya bukan Ready. Sila cari semula.';
        } catch (PDOException $exception) {
            error_log('Complete payment: ' . $exception->getMessage());

            $paymentError =
                'Maklumat tidak dapat disimpan. Sila cuba lagi.';
        }
    }
}

$successMessage = $_SESSION['payment_success'] ?? '';
unset($_SESSION['payment_success']);
    

$uidInput = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? ($_POST['uid'] ?? '')
    : ($_GET['uid'] ?? '');

$uid = is_string($uidInput) ? trim($uidInput) : '';

$request = null;
$error = '';

$labels = [
    'in_progress' => 'In Progress',
    'ready' => 'Ready',
    'completed' => 'Completed',
];

if (isset($_GET['uid']) || $_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($uid === '' || strlen($uid) > 40) {
        $error = 'Sila masukkan UID yang sah.';
    } else {
        try {
            $statement = $pdo->prepare(
                'SELECT
                    r.*,
                    d.name AS department_name,
                    t.name AS service_type_name,
                    h.name AS handler_name
                 FROM service_requests r
                 JOIN departments d ON d.id = r.department_id
                 JOIN service_types t ON t.id = r.service_type_id
                 JOIN handlers h ON h.id = r.handler_id
                 WHERE r.uid = :uid'
            );

            $statement->execute(['uid' => $uid]);
            $request = $statement->fetch(PDO::FETCH_ASSOC);

            if (!$request) {
                $error = 'Tiada permintaan ditemui untuk UID tersebut.';
            }
        } catch (PDOException $exception) {
            error_log('Payment search: ' . $exception->getMessage());
            $error = 'Carian gagal. Sila cuba lagi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Penyelesaian & Bayaran | Perkhidmatan USM</title>

    <link rel="stylesheet"
          href="<?= APP_BASE ?>/assets/css/style.css">
    <link rel="stylesheet"
          href="<?= APP_BASE ?>/assets/css/sidebar.css">
    <link rel="stylesheet"
          href="<?= APP_BASE ?>/assets/css/dashboard.css">
    <link rel="stylesheet"
          href="<?= APP_BASE ?>/assets/css/payments.css?v=20261027c">
    <script src="<?= APP_BASE ?>/assets/js/sidebar.js" defer></script>
</head>

<body class="workspace amdi-type">
    <?php require __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="dashboard-area">
        <header class="dashboard-header">
            <h1>Penyelesaian &amp; Bayaran</h1>

            <form method="post" action="<?= APP_BASE ?>/logout.php">
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e(csrf_token()) ?>"
                >

                <button type="submit" class="logout-button">
                    Log Keluar ↗
                </button>
            </form>
        </header>

        <main class="dashboard-main" id="main-content" tabindex="-1">
            <section class="payment-panel">
                <h2>Cari permintaan</h2>

                <p>
                    Masukkan UID untuk menyemak permintaan
                    dan mengurus pengambilan serta bayaran.
                </p>

                <form method="get" class="uid-search">
                    <div class="uid-field">
                        <label for="uid">UID permintaan</label>

                        <input
                            type="text"
                            id="uid"
                            name="uid"
                            
                            maxlength="40"
                            required
                        >
                    </div>

                    <button type="submit" class="payment-primary">
                        Cari
                    </button>
                </form>

            <?php if ($successMessage !== ''): ?>
    <p class="payment-message success" role="status">
        <?= e($successMessage) ?>
    </p>
<?php endif; ?>

<?php if ($paymentError !== ''): ?>
    <p class="payment-message error" role="alert">
        <?= e($paymentError) ?>
    </p>
<?php endif; ?>

                <?php if ($error !== ''): ?>
    <p class="payment-message error" role="alert">
        <?= e($error) ?>
    </p>
<?php endif; ?>

<?php if ($request): ?>
    <h3 class="payment-result-title">Butiran permintaan</h3>

    <dl class="payment-details">
        <div>
            <dt>UID</dt>
            <dd><?= e($request['uid']) ?></dd>
        </div>

        <div>
            <dt>Nama staf/pelajar</dt>
            <dd><?= e($request['requester_name']) ?></dd>
        </div>

        <div>
            <dt>Jabatan</dt>
            <dd><?= e($request['department_name']) ?></dd>
        </div>

        <div>
            <dt>Jenis servis</dt>
            <dd><?= e($request['service_type_name']) ?></dd>
        </div>

        <div>
            <dt>Pengendali</dt>
            <dd><?= e($request['handler_name']) ?></dd>
        </div>

        <div>
            <dt>Kos</dt>
            <dd>
                RM <?= number_format(
                    (float) $request['estimated_cost'],
                    2
                ) ?>
            </dd>
        </div>

        <div>
            <dt>Status perkhidmatan</dt>
            <dd>
                <span class="status status-<?= e($request['status']) ?>">
                    <?= e($labels[$request['status']] ?? $request['status']) ?>
                </span>
            </dd>
        </div>

        <div>
            <dt>Tarikh permintaan</dt>
            <dd>
                <?= e(date(
                    'd/m/Y H:i',
                    strtotime($request['requested_at'])
                )) ?>
            </dd>
        </div>

        <div class="payment-wide">
            <dt>Butiran servis</dt>
            <dd><?= nl2br(e($request['service_details'])) ?></dd>
        </div>
    </dl>

    <?php if ($request['status'] === 'in_progress'): ?>
        <p class="payment-message">
            Servis masih dalam proses dan belum sedia untuk diambil.
        </p>

    <?php elseif ($request['status'] === 'ready'): ?>
    <h3 class="payment-result-title">
        Pengesahan pengambilan &amp; bayaran
    </h3>

    <form method="post"
          action="<?= APP_BASE ?>/payments/index.php"
          class="completion-form">

        <input type="hidden"
               name="csrf_token"
               value="<?= e(csrf_token()) ?>">

        <input type="hidden"
               name="uid"
               value="<?= e($request['uid']) ?>">

        <label class="pickup-check">
            <input
                type="checkbox"
                name="pickup_confirmed"
                value="1"
                <?= $pickupConfirmed ? 'checked' : '' ?>
                required
            >
            <span>Pengambilan telah disahkan</span>
        </label>

        <div class="payment-details">
            <div class="uid-field">
                <label for="payment_date">Tarikh bayaran</label>

                <input
                    type="date"
                    id="payment_date"
                    name="payment_date"
                    value="<?= e($paymentDate) ?>"
                    min="1000-01-01"
                    max="<?= e(date('Y-m-d')) ?>"
                    required
                >
            </div>

            <div class="uid-field">
                <label for="reference_no">No. Rujukan</label>

                <input
                    type="text"
                    id="reference_no"
                    name="reference_no"
                    value="<?= e($referenceNo) ?>"
                    placeholder="Masukkan nombor rujukan bayaran"
                    maxlength="100"
                    required
                >
            </div>
        </div>

        <button type="submit" class="payment-primary">
            Sahkan Penyelesaian
        </button>
    </form>

    <?php elseif ($request['status'] === 'completed'): ?>
        <p class="payment-message">
            Permintaan ini telah selesai dan dibayar.
        </p>
        <dl class="payment-details">
    <div>
        <dt>Tarikh bayaran</dt>
        <dd><?= e($request['payment_date'] ?? '—') ?></dd>
    </div>

    <div>
        <dt>No. Rujukan</dt>
        <dd><?= e($request['reference_no'] ?? '—') ?></dd>
    </div>

    <div>
        <dt>Tarikh dan masa pengambilan</dt>
        <dd><?= e($request['picked_up_at'] ?? '—') ?></dd>
    </div>
</dl>
    <?php endif; ?>
<?php endif; ?>
            </section>
        </main>
    </div>
</body>
</html>