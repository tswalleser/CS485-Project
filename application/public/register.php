<?php
require_once dirname(__FILE__) . "/../bootstrap.php";

$errors = [];
$success = false;

$values = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = htmlspecialchars($_POST['username'] ?? '');
    $values['username'] = trim($username);

    $email = $_POST['email'] ?? '';
    $values['email'] = trim($email);

    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    //Username Checks
    if (strlen($username) < 3 || strlen($username) > 20) {
        $errors[] = 'Username must be greater than 3 characters but no more than 20.';
    }

    if (!preg_match("/^[A-Za-z0-9]+(?:-[A-Za-z0-9]+)?$/", $username)) {
        $errors[] = 'Username must consist of only letters, numbers and a singular hyphen.';
    }

    //Email Checks
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email format invalid.';
    }

    //Password Checks
    if (strlen($password) === 0 || strlen($confirm_password) === 0) {
        $errors[] = 'Password or Confirm Password left blank.';
    }

    if ($password !== $confirm_password) {
        $errors[] = 'Password and Confirm Password must match.';
    }

    if (count($errors) === 0) { //Nothing tripped any errors
        //Check if the username is still available
        $available = $database->username_available($username);

        if (!$available) {
            $errors[] = 'Username ' . $username . ' already taken.';
        } else {
            $password = password_hash($password, PASSWORD_DEFAULT);
            $success = $database->register($username, $email, $password);

            if (!$success) {
                $errors[] = 'Error registering, try again later!';
            } else {
                $success = true;
            }
        }
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
        <h1>Register Page</h1>
        <p>Register to export generated reports.</p>
    </div>

    <?php if ($success === true): ?>
    <div style="background:var(--success);border:1px solid var(--success);border-radius:var(--radius);padding:12px 16px;margin-bottom:1.5rem;font-size:13px;color:black">
        <p>Registered! Try logging in.</p>
    </div>
    <?php endif; ?>

    <?php if (count($errors) > 0): ?>
    <div style="background:var(--danger-dim);border:1px solid var(--danger);border-radius:var(--radius);padding:12px 16px;margin-bottom:1.5rem;font-size:13px;color:var(--danger)">
        <?php
        foreach ($errors as $err) {
            echo "<p>$err</p>";
        }
        ?>
    </div>
    <?php endif; ?>

    <div style="display:flex;justify-content:center;">
        <div class="card" style="margin-bottom:1.5rem;width:30%;">
            <form method="POST" action="register.php">
                <div style="display:grid;grid-template-columns:1fr;gap:1.5rem;">
                    <div>
                        <p class="section-label">REGISTER FORM</p>
                        <div>
                            <div class="filter-group">
                                <label>Username</label>
                                <input type="text" name="username" value="<?= $values['username'] ?? '' ?>" required>
                            </div>
                            <div class="filter-group">
                                <label>Email</label>
                                <input type="email" name="email" value="<?= $values['email'] ?? '' ?>" required>
                            </div>
                            <div class="filter-group">
                                <label>Password</label>
                                <input type="password" name="password" required>
                            </div>
                            <div class="filter-group">
                                <label>Confirm Password</label>
                                <input type="password" name="confirm_password" required>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="btn-row">
                    <button type="submit" name="submit" class="btn btn-primary">Register</button>
                    <a href="login.php" class="btn btn-outline">Login</a>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>