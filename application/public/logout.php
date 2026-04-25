<?php

require_once dirname(__FILE__) . "/../bootstrap.php";
$session->logout();

header("Location: /login.php", true, 301);
exit();

?>
