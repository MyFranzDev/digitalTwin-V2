<?php
require_once 'config.php';

// Check if already authenticated
if (isset($_SESSION['authenticated']) && $_SESSION['authenticated']) {
    header('Location: dashboard.php');
    exit;
}

// Handle login
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if ($_POST['password'] === APP_PASSWORD) {
        $_SESSION['authenticated'] = true;
        header('Location: dashboard.php');
        exit;
    } else {
        $error = "Password errata";
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Helvetica Neue", Arial, sans-serif;
            background: url('assets/3070961.jpg') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            text-align: center;
            max-width: 450px;
            width: 90%;
        }

        .logo {
            width: 150px;
            height: auto;
            margin-bottom: 20px;
            filter: drop-shadow(0 2px 8px rgba(0, 0, 0, 0.15));
        }

        h1 {
            color: #0f364c;
            margin-bottom: 10px;
            font-size: 24px;
            font-weight: 600;
        }

        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        label {
            display: block;
            color: #333;
            font-size: 14px;
            margin-bottom: 8px;
            font-weight: 500;
        }

        input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        input[type="password"]:focus {
            outline: none;
            border-color: #0f364c;
        }

        .btn-login {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #0f364c 0%, #1a5a7e 100%);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(15, 54, 76, 0.3);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .error {
            background: #fee;
            color: #c33;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 14px;
        }

        .reference {
            position: fixed;
            bottom: 10px;
            width: 100%;
            text-align: center;
            font-size: 11px;
            color: rgba(24, 190, 255, 0.8);
        }

        .reference a {
            color: inherit;
            text-decoration: none;
        }

        .reference a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <img class="logo" src="assets/newrality.png" alt="Newrality Logo">
        <h1><?= APP_NAME ?></h1>
        <p class="subtitle">Analisi pathway butirrato → acetil-CoA</p>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password"
                       placeholder="Inserisci password" required autofocus>
            </div>
            <button type="submit" class="btn-login">Accedi</button>
        </form>
    </div>

    <div class="reference">
        <a href="http://www.freepik.com">Designed by pikisuperstar / Freepik</a>
    </div>
</body>
</html>
