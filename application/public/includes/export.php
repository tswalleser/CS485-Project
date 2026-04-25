<?php

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

require_once "./../../bootstrap.php";

if (!$session->is_logged_in()) {
    exit;
}

$spreadsheet = $report->generate();

if (!$spreadsheet) {
    exit;
}

$title = $_SESSION['Report']['Title'];

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $title . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
$writer->save('php://output');
exit;

?>
