<?php
declare(strict_types=1);

require __DIR__ . '/includes/auth.php';
require_login();

require __DIR__ . '/config/database.php';

$activePage = 'history';
$records = [];
$error = '';
$perPageInput = $_GET['per_page'] ?? '5';

$perPage = is_string($perPageInput)
    && in_array($perPageInput, ['5', '10', '25', '50'], true)
        ? (int) $perPageInput
        : 5;

$pageInput = $_GET['page'] ?? '1';

$page = is_string($pageInput)
    ? filter_var(
        $pageInput,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    )
    : false;

$page = $page === false ? 1 : $page;

$totalRecords = 0;
$totalPages = 1;
$sortOptions = [
    'newest' => 'Terbaharu dahulu',
    'oldest' => 'Terlama dahulu',
    'name' => 'Nama A–Z',
    'cost_high' => 'Kos tertinggi',
    'cost_low' => 'Kos terendah',
];

$sortInput = $_GET['sort'] ?? 'newest';

$sort = is_string($sortInput)
    && array_key_exists($sortInput, $sortOptions)
        ? $sortInput
        : 'newest';

$orderOptions = [
    'newest' => 'r.completed_at DESC, r.id DESC',
    'oldest' => 'r.completed_at ASC, r.id ASC',
    'name' => 'r.requester_name ASC, r.id DESC',
    'cost_high' => 'r.estimated_cost DESC, r.id DESC',
    'cost_low' => 'r.estimated_cost ASC, r.id DESC',
];

$orderBy = $orderOptions[$sort];
$search = is_string($_GET['search'] ?? null)
    ? trim($_GET['search'])
    : '';

$completedDate = is_string($_GET['completed_date'] ?? null)
    ? trim($_GET['completed_date'])
    : '';

if ($completedDate !== '') {
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $completedDate);

    if (
        !$date ||
        $date->format('Y-m-d') !== $completedDate ||
        $completedDate < '1000-01-01' ||
        $completedDate > '9999-12-31'
    ) {
        $error = 'Sila pilih tarikh selesai yang sah.';
    }
}

if ($error === '') {
    try {
        $conditions = ["r.status = 'completed'"];
        $params = [];

        if ($search !== '') {
            $conditions[] = '(
                r.uid LIKE :uid_search
                OR r.requester_name LIKE :name_search
                OR r.reference_no LIKE :reference_search
            )';

            $searchValue = '%' . $search . '%';

            $params['uid_search'] = $searchValue;
            $params['name_search'] = $searchValue;
            $params['reference_search'] = $searchValue;
        }

        if ($completedDate !== '') {
            $conditions[] =
                'r.completed_at >= :date_start
                 AND r.completed_at <= :date_end';

            $params['date_start'] = $completedDate . ' 00:00:00';
            $params['date_end'] = $completedDate . ' 23:59:59';
        }

        $where = implode(' AND ', $conditions);

$countStatement = $pdo->prepare(
    "SELECT COUNT(*)
     FROM service_requests r
     JOIN departments d ON d.id = r.department_id
     JOIN service_types t ON t.id = r.service_type_id
     JOIN handlers h ON h.id = r.handler_id
     WHERE $where"
);

$countStatement->execute($params);

$totalRecords = (int) $countStatement->fetchColumn();

$totalPages = max(
    1,
    (int) ceil($totalRecords / $perPage)
);

$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

        $sql = "
            SELECT
                r.uid,
                r.requester_name,
                r.requested_at,
                r.service_details,
                r.picked_up_at,
                d.name AS department_name,
                r.estimated_cost,
                r.payment_date,
                r.reference_no,
                r.completed_at,
                t.name AS service_type_name,
                h.name AS handler_name
            FROM service_requests r
            JOIN departments d ON d.id = r.department_id
            JOIN service_types t ON t.id = r.service_type_id
            JOIN handlers h ON h.id = r.handler_id
            WHERE $where
            ORDER BY $orderBy
            LIMIT $perPage OFFSET $offset
        ";

        $statement = $pdo->prepare($sql);
        $statement->execute($params);

        $records = $statement->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $exception) {
        error_log('Service history: ' . $exception->getMessage());
        $error = 'Sejarah perkhidmatan tidak dapat dipaparkan.';
    }
function history_page_url(
    int $targetPage,
    int $perPage,
    string $search,
    string $completedDate,
    string $sort
): string {
    return APP_BASE . '/history.php?' . http_build_query([
        'search' => $search,
        'completed_date' => $completedDate,
        'per_page' => $perPage,
        'page' => $targetPage,
        'sort' => $sort,
    ]);
}
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Sejarah Perkhidmatan | Perkhidmatan USM</title>

    <link rel="stylesheet"
          href="<?= APP_BASE ?>/assets/css/style.css">

    <link rel="stylesheet"
          href="<?= APP_BASE ?>/assets/css/sidebar.css">

    <link rel="stylesheet"
          href="<?= APP_BASE ?>/assets/css/dashboard.css">

    <link rel="stylesheet"
          href="<?= APP_BASE ?>/assets/css/history.css">

    <script src="<?= APP_BASE ?>/assets/js/sidebar.js" defer></script>
    <script src="<?= APP_BASE ?>/assets/js/history.js" defer></script>
</head>

<body class="workspace">
    <?php require __DIR__ . '/includes/sidebar.php'; ?>

    <div class="dashboard-area">
        <header class="dashboard-header">
            <h1>Sejarah Perkhidmatan</h1>

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


            <form method="get"
      action="<?= APP_BASE ?>/history.php"
      class="history-filter">

    <input type="hidden"
           name="per_page"
           value="<?= $perPage ?>">

    <div class="history-filter-field">
        <label for="search">Cari perkhidmatan yang selesai</label>

        <input
            type="search"
            id="search"
            name="search"
            value="<?= e($search) ?>"
            placeholder="Cari UID, nama staf/pelajar atau No. Rujukan"
        >
    </div>

    <div class="history-filter-field">
        <label for="completed_date">Tarikh Selesai</label>

        <input
            type="date"
            id="completed_date"
            name="completed_date"
            value="<?= e($completedDate) ?>"
            min="1000-01-01"
            max="9999-12-31"
        >
    </div>

    <div class="history-filter-field">
    <label for="sort">Tapisan</label>

    <select id="sort" name="sort">
        <?php foreach ($sortOptions as $value => $label): ?>
            <option
                value="<?= e($value) ?>"
                <?= $sort === $value ? 'selected' : '' ?>
            >
                <?= e($label) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>

    <div class="history-filter-actions">
        <button type="submit">Cari</button>

        <a href="<?= APP_BASE ?>/history.php">
            Set Semula
        </a>
    </div>
</form>

            <?php if ($error !== ''): ?>
                <p class="history-error" role="alert">
                    <?= e($error) ?>
                </p>
            <?php else: ?>
                <section
                    class="recent-panel"
                    aria-label="Senarai sejarah perkhidmatan"
                >
                    <div class="table-scroll">
                        <table>
                            <thead>
                                <tr>
                                    <th>UID</th>
                                    <th>NAMA STAF/PELAJAR</th>
                                    <th>JABATAN</th>
                                    <th>JENIS SERVIS</th>
                                    <th>KOS</th>
                                    <th>TARIKH BAYARAN</th>
                                    <th>NO. RUJUKAN</th>
                                    <th>TARIKH SELESAI</th>
                                    <th>PENGENDALI</th>
                                    <th>TINDAKAN</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach ($records as $record): ?>
                                    <tr>
                                        <td class="uid">
                                            <?= e($record['uid']) ?>
                                        </td>

                                        <td>
                                            <?= e($record['requester_name']) ?>
                                        </td>

                                        <td>
                                            <?= e($record['department_name']) ?>
                                        </td>

                                        <td>
                                            <?= e($record['service_type_name']) ?>
                                        </td>

                                        <td class="history-nowrap">
                                            RM <?= number_format(
                                                (float) $record['estimated_cost'],
                                                2
                                            ) ?>
                                        </td>

                                        <td class="history-nowrap">
                                            <?= $record['payment_date']
                                                ? e(date(
                                                    'd/m/Y',
                                                    strtotime($record['payment_date'])
                                                ))
                                                : '—' ?>
                                        </td>

                                        <td>
                                            <?= e($record['reference_no'] ?? '—') ?>
                                        </td>

                                        <td class="history-nowrap">
                                            <?= $record['completed_at']
                                                ? e(date(
                                                    'd/m/Y H:i',
                                                    strtotime($record['completed_at'])
                                                ))
                                                : '—' ?>
                                        </td>

                                        <td>
                                            <?= e($record['handler_name']) ?>
                                        </td>
                                        <td>
    <button
        type="button"
        class="details-button"
        data-history-details
        data-record="<?= e(json_encode($record, JSON_THROW_ON_ERROR)) ?>"
        aria-haspopup="dialog"
        aria-controls="history-details"
        aria-label="Lihat butiran <?= e($record['uid']) ?>"
    >
        Butiran
    </button>
</td>
                                    </tr>
                                <?php endforeach; ?>

                                <?php if (!$records): ?>
                                    <tr>
                                        <td colspan="10" class="history-empty">
                                            Tiada rekod perkhidmatan selesai ditemui.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="history-pagination">
        <form method="get"
          action="<?= APP_BASE ?>/history.php"
          class="history-page-size">

        <input type="hidden"
               name="search"
               value="<?= e($search) ?>">

        <input type="hidden"
               name="completed_date"
               value="<?= e($completedDate) ?>">

        <label for="per_page">Baris setiap halaman</label>

        <select id="per_page"
                name="per_page"
                onchange="this.form.submit()">

            <?php foreach ([5, 10, 25, 50] as $size): ?>
                <option
                    value="<?= $size ?>"
                    <?= $perPage === $size ? 'selected' : '' ?>
                >
                    <?= $size ?>
                </option>
            <?php endforeach; ?>
        </select>

        <noscript>
            <button type="submit">Papar</button>
        </noscript>
    </form>

    <nav class="history-page-navigation"
         aria-label="Halaman sejarah perkhidmatan">

        <span class="history-page-summary" aria-live="polite">
            Halaman <?= $page ?> daripada <?= $totalPages ?>
            · <?= $totalRecords ?> hasil
        </span>

        <?php if ($page > 1): ?>
<a href="<?= e(history_page_url(
    $page - 1,
    $perPage,
    $search,
    $completedDate,
    $sort
)) ?>">
    Sebelumnya
</a>
        <?php else: ?>
            <span class="history-page-disabled" aria-disabled="true">
                Sebelumnya
            </span>
        <?php endif; ?>

        <?php if ($page < $totalPages): ?>
            
                <a href="<?= e(history_page_url(
    $page + 1,
    $perPage,
    $search,
    $completedDate,
    $sort
)) ?>">
    Seterusnya
</a>
            </a>
        <?php else: ?>
            <span class="history-page-disabled" aria-disabled="true">
                Seterusnya
            </span>
        <?php endif; ?>
    </nav>
</div>
                </section>
            <?php endif; ?>
        </main>
    </div>
    <dialog
    id="history-details"
    class="details-dialog"
    aria-labelledby="history-details-title"
>
    <div class="details-heading">
        <h2 id="history-details-title">Butiran perkhidmatan</h2>

        <button
            type="button"
            class="details-close"
            aria-label="Tutup butiran"
            autofocus
        >
            ×
        </button>
    </div>

<dl class="details-grid history-detail-grid">
    <div class="history-detail-heading">
        Maklumat Pemohon
    </div>

    <div>
        <dt>UID</dt>
        <dd data-field="uid"></dd>
    </div>

    <div>
        <dt>Nama staf/pelajar</dt>
        <dd data-field="requester_name"></dd>
    </div>

    <div class="details-wide">
        <dt>Jabatan</dt>
        <dd data-field="department_name"></dd>
    </div>

    <div class="history-detail-heading">
        Perkhidmatan
    </div>

    <div>
        <dt>Jenis servis</dt>
        <dd data-field="service_type_name"></dd>
    </div>

    <div>
        <dt>Pengendali</dt>
        <dd data-field="handler_name"></dd>
    </div>

    <div>
        <dt>Tarikh permintaan</dt>
        <dd data-field="requested_at"></dd>
    </div>

    <div>
        <dt>Tarikh selesai</dt>
        <dd data-field="completed_at"></dd>
    </div>

    <div>
        <dt>Status perkhidmatan</dt>
        <dd><span class="status status-completed">Completed</span></dd>
    </div>

    <div>
        <dt>Tarikh pengambilan</dt>
        <dd data-field="picked_up_at"></dd>
    </div>

    <div class="details-wide">
        <dt>Butiran servis</dt>
        <dd data-field="service_details"></dd>
    </div>

    <div class="history-detail-heading">
        Bayaran
    </div>

    <div>
        <dt>Kos</dt>
        <dd data-field="estimated_cost"></dd>
    </div>

    <div>
        <dt>Tarikh bayaran</dt>
        <dd data-field="payment_date"></dd>
    </div>

    <div class="details-wide">
        <dt>No. Rujukan</dt>
        <dd data-field="reference_no"></dd>
    </div>
</dl>
</dialog>
</body>
</html>