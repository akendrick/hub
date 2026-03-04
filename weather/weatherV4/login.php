<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';

// If already logged in, go straight to the dashboard (or requested page)
if (auth_is_logged_in()) {
    $redirect = $_GET['redirect'] ?? '/kaslo-weather.php';
    header('Location: ' . $redirect);
    exit;
}

$error    = null;
$redirect = $_GET['redirect'] ?? '/kaslo-weather.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = (string)($_POST['password'] ?? '');

    if (auth_attempt_login($password)) {
        header('Location: ' . $redirect);
        exit;
    }

    $error = 'Incorrect password. Please try again.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kaslo Dashboard · Login</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      background: #111;
      color: #fff;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px 16px;
    }
    .card {
      background: #181818;
      border: 1px solid #333;
      padding: 24px 22px 26px;
      width: 100%;
      max-width: 360px;
      border-radius: 6px;
      box-shadow: 0 18px 45px rgba(0,0,0,0.6);
    }
    .title {
      font-size: 14px;
      letter-spacing: 2px;
      text-transform: uppercase;
      margin-bottom: 4px;
    }
    .subtitle {
      font-size: 11px;
      color: #888;
      margin-bottom: 18px;
    }
    label {
      display: block;
      font-size: 9px;
      font-weight: 700;
      letter-spacing: 1.5px;
      text-transform: uppercase;
      color: #777;
      margin-bottom: 4px;
    }
    input[type=password] {
      width: 100%;
      border: none;
      border-bottom: 1px solid #444;
      padding: 7px 0;
      font-size: 15px;
      font-family: inherit;
      background: transparent;
      color: #fff;
      outline: none;
    }
    input[type=password]:focus {
      border-bottom-color: #fff;
    }
    .btn-row {
      margin-top: 18px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
    }
    button[type=submit] {
      background: #fff;
      color: #000;
      border: none;
      padding: 8px 20px;
      font-size: 12px;
      font-weight: 700;
      letter-spacing: 1px;
      text-transform: uppercase;
      cursor: pointer;
    }
    button[type=submit]:hover {
      background: #f0f0f0;
    }
    .hint {
      font-size: 10px;
      color: #777;
    }
    .error {
      margin-top: 8px;
      font-size: 11px;
      color: #ff6b6b;
    }
  </style>
</head>
<body>
  <div class="card">
    <div class="title">Kaslo Dashboard</div>
    <div class="subtitle">Private login</div>

    <form method="post" action="login.php?<?php echo htmlspecialchars(http_build_query(['redirect' => $redirect]), ENT_QUOTES, 'UTF-8'); ?>">
      <label for="password">Password</label>
      <input
        type="password"
        id="password"
        name="password"
        autocomplete="current-password"
        autofocus
        required
      >

      <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
      <?php endif; ?>

      <div class="btn-row">
        <div class="hint">Set the password in <code>auth.php</code>.</div>
        <button type="submit">Enter</button>
      </div>
    </form>
  </div>
</body>
</html>

