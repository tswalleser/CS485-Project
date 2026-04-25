<?php
require_once dirname(__FILE__) . "/../bootstrap.php";

$error = false;

if ($session->is_logged_in()) {
    header("Location: /index.php", true, 301);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (strlen($username) !== 0 && strlen($password) !== 0) {
        $user_id = $database->verify_login($username, $password);

        if ($user_id !== false) {
            $session->login($user_id, $username);
            header("Location: /index.php", true, 301);
            exit();
        } else {
            $error = true;
        }
    } else {
        $error = true;
    }

}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <link rel="apple-touch-icon" sizes="180x180" href="/favicon_io/apple-touch-icon.png">
  <link rel="icon" type="image/png" sizes="32x32" href="/favicon_io/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="/favicon_io/favicon-16x16.png">
  <link rel="manifest" href="/favicon_io/site.webmanifest">
  
  <title>Login - SocioEconomic Insights</title>
  <link rel="stylesheet" href="css/style.css">
  <script src = "https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
  <script src = "js/compare.js"></script>
</head>
<body>
<?php include dirname(__FILE__) . '/includes/navigation.php' ?>

<div class="page">
    <div class="page-header">
        <h1>Login Page</h1>
        <p>Log in to export generated reports.</p>
    </div>

    <?php if ($error): ?>
        <div style="background:var(--danger-dim);border:1px solid var(--danger);border-radius:var(--radius);padding:12px 16px;margin-bottom:1.5rem;font-size:13px;color:var(--danger)">
            <p>Wrong username or password.</p>
        </div>
    <?php endif; ?>

    <div style="display:flex;justify-content:center;">
        <div class="card" style="margin-bottom:1.5rem;width:30%;">
            <form method="POST" action="login.php">
                <div style="display:grid;grid-template-columns:1fr;gap:1.5rem;">
                    <div>
                        <p class="section-label">LOGIN FORM</p>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                            <div class="filter-group">
                                <label>Username</label>
                                <input type="text" name="username" required>
                            </div>
                            <div class="filter-group">
                                <label>Password</label>
                                <input type="password" name="password"required>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="btn-row">
                    <button type="submit" name="submit" class="btn btn-primary">Login</button>
                    <a href="register.php" class="btn btn-outline">Register</a>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>