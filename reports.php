<?php
declare(strict_types=1);

require __DIR__ . '/includes/auth.php';
require_login();

require __DIR__ . '/config/database.php';

$activePage = 'reports';
$records = [];
$error = '';

$totalServices = 0;
$totalPayment = '0.00';

$monthNames = [
    1 => 'Januari',
    2 => 'Februari',
    3 => 'Mac',
    4 => 'April',
    5 => 'Mei',
    6 => 'Jun',
    7 => 'Julai',
    8 => 'Ogos',
    9 => 'September',
    10 => 'Oktober',
    11 => 'November',
    12 => 'Disember',
];

$yearInput = $_GET['year'] ?? date('Y');
$monthInput = $_GET['month'] ?? date('n');

$year = is_string($yearInput)
    ? filter_var(
        $yearInput,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1000, 'max_range' => 9998]]
    )
    : false;

$month = is_string($monthInput)
    ? filter_var(
        $monthInput,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 0, 'max_range' => 12]]
    )
    : false;

if ($year === false || $month === false) {
    $error = 'Sila pilih bulan dan tahun yang sah.';
    $year = (int) date('Y');
    $month = (int) date('n');
}

$periodLabel = $month === 0
    ? 'Tahun ' . $year
    : $monthNames[$month] . ' ' . $year;

if ($error === '') {
    $startDate = new DateTimeImmutable(
        sprintf('%04d-%02d-01', $year, $month === 0 ? 1 : $month)
    );

    $endDate = $month === 0
        ? $startDate->modify('+1 year')
        : $startDate->modify('+1 month');

    $params = [
        'start_date' => $startDate->format('Y-m-d'),
        'end_date' => $endDate->format('Y-m-d'),
    ];

    $fromAndWhere = "
        FROM service_requests r
        JOIN departments d ON d.id = r.department_id
        JOIN service_types t ON t.id = r.service_type_id
        JOIN handlers h ON h.id = r.handler_id
        WHERE r.status = 'completed'
          AND r.payment_date >= :start_date
          AND r.payment_date < :end_date
    ";

    try {
        $statement = $pdo->prepare(
            "SELECT
                COUNT(*) AS total_services,
                COALESCE(SUM(r.estimated_cost), 0) AS total_payment
             $fromAndWhere"
        );

        $statement->execute($params);
        $summary = $statement->fetch(PDO::FETCH_ASSOC);

        $totalServices = (int) $summary['total_services'];
        $totalPayment = $summary['total_payment'];

        $statement = $pdo->prepare(
            "SELECT
                r.id,
                r.uid,
                r.requester_name,
                d.name AS department_name,
                t.name AS service_type_name,
                r.estimated_cost,
                r.payment_date,
                r.reference_no,
                h.name AS handler_name
             $fromAndWhere
             ORDER BY r.payment_date DESC, r.id DESC"
        );

        $statement->execute($params);
        $records = $statement->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $exception) {
        error_log('Service report: ' . $exception->getMessage());
        $error = 'Laporan tidak dapat dipaparkan. Sila cuba lagi.';
    }
}
$exportRequested = ($_GET['export'] ?? null) === 'excel';

if ($exportRequested && $error === '') {
    require __DIR__ . '/vendor/autoload.php';

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Laporan Perkhidmatan');

    // Tajuk laporan
    $sheet->mergeCells('A1:F1');
    $sheet->setCellValue('A1', 'Laporan Perkhidmatan USM');

    $sheet->mergeCells('A2:F2');
    $sheet->setCellValue('A2', $periodLabel);

    $sheet->setCellValue('A3', 'Bilangan servis selesai');
    $sheet->setCellValue('B3', $totalServices);

    $sheet->setCellValue('D3', 'Jumlah bayaran (RM)');
    $sheet->setCellValue('E3', (float) $totalPayment);
    $sheet->getStyle('E3')
        ->getNumberFormat()
        ->setFormatCode('#,##0.00');

    // Tajuk lajur
    $sheet->fromArray([
        'Tarikh',
        'Nama',
        'Jabatan',
        'Jenis Perkhidmatan',
        'No. Rujukan',
        'Jumlah (RM)',
    ], null, 'A5');

    $row = 6;

    foreach ($records as $record) {
        // Simpan teks sebagai teks, termasuk No. Rujukan.
        $textCells = [
            'B' => $record['requester_name'],
            'C' => $record['department_name'],
            'D' => $record['service_type_name'],
            'E' => $record['reference_no'] ?? '',
        ];

        foreach ($textCells as $column => $value) {
            $sheet->setCellValueExplicit(
                $column . $row,
                (string) $value,
                \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
            );
        }

        $sheet->setCellValue(
            'F' . $row,
            (float) $record['estimated_cost']
        );

        $sheet->setCellValue(
            'A' . $row,
            \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(
                new DateTimeImmutable($record['payment_date'])
            )
        );

        $row++;
    }

    $lastDataRow = $row - 1;

    if ($records) {
        $sheet->getStyle('F6:F' . $lastDataRow)
            ->getNumberFormat()
            ->setFormatCode('#,##0.00');

        $sheet->getStyle('A6:A' . $lastDataRow)
            ->getNumberFormat()
            ->setFormatCode('dd/mm/yyyy');

        $sheet->setAutoFilter('A5:F' . $lastDataRow);

        $sheet->getStyle('A6:F' . $lastDataRow)
            ->getAlignment()
            ->setVertical(
                \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP
            );
    }

    // Jumlah di bawah jadual
    $sheet->mergeCells('A' . $row . ':E' . $row);
    $sheet->setCellValue('A' . $row, 'JUMLAH · ' . $totalServices . ' rekod');
    $sheet->setCellValue('F' . $row, (float) $totalPayment);

    $sheet->getStyle('A' . $row . ':F' . $row)
        ->getFont()
        ->setBold(true);

    $sheet->getStyle('F' . $row)
        ->getNumberFormat()
        ->setFormatCode('#,##0.00');

    // Kemasan
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(18);

    $sheet->getStyle('A5:F5')->getFont()
        ->setBold(true)
        ->getColor()
        ->setARGB('FFFFFFFF');

    $sheet->getStyle('A5:F5')->getFill()
        ->setFillType(
            \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID
        )
        ->getStartColor()
        ->setARGB('FF6026A4');

    $sheet->getRowDimension(5)->setRowHeight(28);
    $sheet->freezePane('A6');

    $widths = [
        'A' => 18,
        'B' => 28,
        'C' => 32,
        'D' => 42,
        'E' => 28,
        'F' => 20,
    ];

    foreach ($widths as $column => $width) {
        $sheet->getColumnDimension($column)->setWidth($width);
    }

    $sheet->getStyle('A5:F' . $row)
        ->getAlignment()
        ->setWrapText(true);

    $filename = $month === 0
        ? sprintf('laporan-perkhidmatan-%04d.xlsx', $year)
        : sprintf(
            'laporan-perkhidmatan-%04d-%02d.xlsx',
            $year,
            $month
        );

    // Sediakan fail sepenuhnya sebelum menghantarnya.
    $temporaryFile = tempnam(sys_get_temp_dir(), 'usm_excel_');

    if ($temporaryFile === false) {
        $error = 'Fail Excel tidak dapat disediakan.';
    } else {
        try {
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx(
                $spreadsheet
            );

            $writer->save($temporaryFile);

            header(
                'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            );
            header(
                'Content-Disposition: attachment; filename="' .
                $filename . '"'
            );
            header('Content-Length: ' . filesize($temporaryFile));

            readfile($temporaryFile);
            exit;
        } catch (Throwable $exception) {
            error_log('Excel export: ' . $exception->getMessage());
            $error = 'Eksport Excel gagal. Sila cuba lagi.';
        } finally {
            if (is_file($temporaryFile)) {
                unlink($temporaryFile);
            }

            $spreadsheet->disconnectWorksheets();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Laporan | Perkhidmatan USM</title>

    <link rel="stylesheet"
          href="<?= APP_BASE ?>/assets/css/style.css">
    <link rel="stylesheet"
          href="<?= APP_BASE ?>/assets/css/sidebar.css">
    <link rel="stylesheet"
          href="<?= APP_BASE ?>/assets/css/dashboard.css">
    <link rel="stylesheet"
          href="<?= APP_BASE ?>/assets/css/reports.css">

    <script src="<?= APP_BASE ?>/assets/js/sidebar.js" defer></script>
</head>

<body class="workspace">
    <?php require __DIR__ . '/includes/sidebar.php'; ?>

    <div class="dashboard-area">
        <header class="dashboard-header">
            <h1>Laporan Perkhidmatan</h1>

            <form method="post" action="<?= APP_BASE ?>/logout.php">
                <input type="hidden"
                       name="csrf_token"
                       value="<?= e(csrf_token()) ?>">

                <button type="submit" class="logout-button">
                    Log Keluar ↗
                </button>
            </form>
        </header>

        <main class="dashboard-main" id="main-content" tabindex="-1">


            <form method="get"
                  action="<?= APP_BASE ?>/reports.php"
                  class="report-filter">

                <div>
                    <label for="month">Bulan</label>

                    <select id="month" name="month">
                        <option value="0" <?= $month === 0 ? 'selected' : '' ?>>
                            Semua bulan
                        </option>

                        <?php foreach ($monthNames as $number => $name): ?>
                            <option
                                value="<?= $number ?>"
                                <?= $month === $number ? 'selected' : '' ?>
                            >
                                <?= e($name) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="year">Tahun</label>

                    <input
                        type="number"
                        id="year"
                        name="year"
                        value="<?= $year ?>"
                        min="1000"
                        max="9998"
                        required
                    >
                </div>

<div class="report-actions">
    <button type="submit" class="report-button">
        Papar Laporan
    </button>

    <a href="<?= APP_BASE ?>/reports.php" class="report-reset">
        Set Semula
    </a>
</div>
            </form>

            <?php if ($error !== ''): ?>
                <p class="report-error" role="alert">
                    <?= e($error) ?>
                </p>
            <?php else: ?>
<div class="report-period">
    <h2><?= e($periodLabel) ?></h2>

    <a
        class="report-export"
        href="<?= e(APP_BASE . '/reports.php?' . http_build_query([
            'year' => $year,
            'month' => $month,
            'export' => 'excel',
        ])) ?>"
    >
        Eksport Excel
    </a>
</div>

                <section class="report-summary" aria-label="Ringkasan laporan">
                    <div class="report-stat">
                        <span>Bilangan servis selesai</span>
                        <strong><?= $totalServices ?></strong>
                        
                    </div>

                    <div class="report-stat">
                        <span>Jumlah bayaran</span>
                        <strong>
                            RM <?= number_format((float) $totalPayment, 2) ?>
                        </strong>
                        
                    </div>
                </section>

                <section class="report-table-panel"
                         aria-label="Rekod laporan perkhidmatan">

                    <div class="table-scroll">
                        <table>
                            <thead>
                                <tr>
                                    <th>UID</th>
                                    <th>NAMA STAF/PELAJAR</th>
                                    <th>JABATAN</th>
                                    <th>JENIS SERVIS</th>
                                    <th>TARIKH BAYARAN</th>
                                    <th>NO. RUJUKAN</th>
                                    <th>PENGENDALI</th>
                                    <th>KOS</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach ($records as $record): ?>
                                    <tr>
                                        <td class="uid">
    <?= (int) $record['id'] ?>
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

                                        <td class="report-nowrap">
                                            <?= e(date(
                                                'd/m/Y',
                                                strtotime($record['payment_date'])
                                            )) ?>
                                        </td>

                                        <td>
                                            <?= e($record['reference_no'] ?? '—') ?>
                                        </td>

                                        <td>
                                            <?= e($record['handler_name']) ?>
                                        </td>
                                        <td class="report-nowrap">
                                            RM <?= number_format(
                                                (float) $record['estimated_cost'],
                                                2
                                            ) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>

                                <?php if (!$records): ?>
                                    <tr>
                                        <td colspan="8" class="report-empty">
                                            Tiada rekod bayaran untuk tempoh ini.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>

<tfoot>
    <tr>
        <th colspan="7">
            JUMLAH KOS · <?= $totalServices ?> rekod
        </th>

        <td class="report-nowrap">
            <strong>
                RM <?= number_format((float) $totalPayment, 2) ?>
            </strong>
        </td>
    </tr>
</tfoot>
                        </table>
                    </div>
                </section>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
