<?php
    require_once "./../bootstrap.php";
    $page = basename($_SERVER['SCRIPT_NAME'], '.php');
?>

<nav>
  <div class="left">
    <span class="brand">
        <span style="width:22px;height:22px;background:#2563eb;color:#fff;font-size:11px;font-weight:700;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;font-family:var(--mono)">SEI</span>
        <span>SocioEconomic Insights</span>
    </span>
    <a href="index.php" id="index">Dashboard</a>
    <a href="search.php" id="search">Search</a>
    <a href="compare.php" id="compare">Compare</a>
    <!--<a href="rankings.php">Rankings</a>-->
    <a href="demographics.php" id="demographics">Demographics</a>
    <!--<a href="education.php">Education</a>-->
    <!--<a href="cost.php">Cost of Living</a>-->
  </div>

  <div class="right">
    <span class="user-nav">
        <?php
            if ($session->is_logged_in()):
        ?>
            <span style="font-weight: bold;"><?= $_SESSION['username'] ?></span>
            <a href="logout.php" id="logout">Logout</a>
        <?php else: ?>
            <a href="login.php" id="login">Login</a>
        <?php endif; ?>
        
    </span>
  </div>
</nav>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const element = document.getElementById("<?= $page ?>");

        if (element !== null) {
            element.classList.add("active");
        }
    });
</script>
