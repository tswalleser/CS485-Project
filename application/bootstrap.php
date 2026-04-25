<?php

require_once dirname(__FILE__) . '/../vendor/autoload.php';

use App\Database;
use App\Session;
use App\Report;

$database = Database::getDatabase();
$session = Session::getSession();
$report = Report::getReport();

?>
