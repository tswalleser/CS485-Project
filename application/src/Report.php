<?php

namespace App;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Table;
use PhpOffice\PhpSpreadsheet\Worksheet\Table\TableStyle;

//session_start();

class Report {
    private static $report = null;

    private function __construct() {}

    public static function getReport() {
        if (self::$report === null) {
            self::$report = new self();
        }

        return self::$report;
    }

    public function set_report($title, $header, $data, $limit) {
        if (!isset($_SESSION['Report'])) {
            $_SESSION['Report'] = [];
        }

        $_SESSION['Report']['Title'] = $title;
        $_SESSION['Report']['Header'] = $header;
        $_SESSION['Report']['Data'] = $data;
        $_SESSION['Report']['Limit'] = $limit;
    }

    public function generate() {
        if (!isset($_SESSION['Report'])) {
            return;
        }

        $title = $_SESSION['Report']['Title'];
        $header = $_SESSION['Report']['Header'];
        $data = $_SESSION['Report']['Data'];
        $limit = $_SESSION['Report']['Limit'];

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()->setCreator('cs485-project')
                    ->setLastModifiedBy('cs485-project')
                    ->setTitle($title)
                    ->setSubject($title)
                    ->setDescription('Auto Generated Report.')
                    ->setKeywords('report ' . $title)
                    ->setCategory('Table');

        $spreadsheet->setActiveSheetIndex(0);

        $spreadsheet->getActiveSheet()->fromArray($header, null, 'A1');
        $spreadsheet->getActiveSheet()->fromArray($data, null, 'A2');
        
        foreach ($spreadsheet->getActiveSheet()->getColumnIterator() as $column) {
            $spreadsheet->getActiveSheet()->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
        }

        $spreadsheet->getActiveSheet()->getStyle($limit)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        $table = new Table($limit, $title);
        $tableStyle = new TableStyle();
        $tableStyle->setTheme(TableStyle::TABLE_STYLE_MEDIUM2);
        $tableStyle->setShowRowStripes(true);
        $tableStyle->setShowColumnStripes(true);
        $tableStyle->setShowFirstColumn(true);
        $tableStyle->setShowLastColumn(true);
        $table->setStyle($tableStyle);

        $spreadsheet->getActiveSheet()->addTable($table);

        return $spreadsheet;
    }
}
