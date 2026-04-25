<?php

header('Content-Type: application/json');

require_once "./../../bootstrap.php";

$state = isset($_GET['state']) ? $_GET['state'] : null;
if ($state == null) exit;

echo json_encode($database->get_counties($state));

exit;

?>
