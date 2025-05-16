<?php
// No session_start() here if it's already in dbconfig or if this page should not have a session initially.
// However, to set flash messages on error, session_start might be needed.
// Let's assume dbconfig.php does NOT start session, and session.php (which does) is NOT included on login page directly.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once('../includes/dbconfig.php'); // For $conn
require_once('../includes/session_functions_standalone.php'); // For set_flash_message, get_flash_messages

$app_config = include(__DIR__ . '/../settings.php');
$appName = $app_config['app']['name'] ?? 'My Application';


// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: ../pages/dashboard.php");
    exit();
}

$error_message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['login_csrf_token'] ?? '', $_POST['csrf_token'])) {
        $error_message = "Invalid CSRF token. Please try reloading the page.";
    } else {
        $username = trim($_POST['username']);
        $password = $_POST['password']; // No trim on password

        if (empty($username) || empty($password)) {
            $error_message = "Username and password are required.";
        } else {
            $sql = "SELECT user_id, username, password, role_id FROM users WHERE username = ? AND is_active = 1"; // Added is_active check
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                $error_message = "Login error. Please try again later. (DBP)";
                error_log("Login SQL prepare failed: " . $conn->error);
            } else {
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows === 1) {
                    $user = $result->fetch_assoc();
                    if (password_verify($password, $user['password'])) {
                        // Password is correct, regenerate session ID for security
                        session_regenerate_id(true);
                        $_SESSION['user_id'] = $user['user_id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['role_id'] = $user['role_id']; // Store role_id
                        // $_SESSION['role_name'] = ... // Fetch role_name if needed for display

                        // Unset the login CSRF token
                        unset($_SESSION['login_csrf_token']);

                        // Redirect to dashboard or intended URL
                        $redirect_url = $_SESSION['redirect_url'] ?? '../pages/dashboard.php';
                        unset($_SESSION['redirect_url']);
                        header("Location: " . $redirect_url);
                        exit();
                    } else {
                        $error_message = "Invalid username or password.";
                    }
                } else {
                    $error_message = "Invalid username or password.";
                }
                $stmt->close();
            }
        }
    }
}
// Generate a new CSRF token for the login form
$_SESSION['login_csrf_token'] = bin2hex(random_bytes(32));
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
    <title>Login - <?php echo htmlspecialchars($appName); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler.min.css">
    <link rel="stylesheet" href="../css/style.css"> <style>
      body {
        display: flex;
        flex-direction: column;
        min-height: 100vh;
        justify-content: center; /* Center vertically */
        align-items: center; /* Center horizontally */
      }
    </style>
  </head>
  <body class="d-flex flex-column">
    <div class="page page-center">
      <div class="container container-tight py-4">
        <div class="text-center mb-4">
          <a href="." class="navbar-brand navbar-brand-autodark">
            <h2><?php echo htmlspecialchars($appName); ?></h2>
          </a>
        </div>
        <div class="card card-md">
          <div class="card-body">
            <h2 class="h2 text-center mb-4">Login to your account</h2>
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            <?php
            // Display flash messages if redirected here with one
            // This requires session_functions_standalone.php to be included
            $flash_messages = get_flash_messages_standalone(); // Use the standalone getter
            if (!empty($flash_messages)) {
                foreach ($flash_messages as $type => $msgs) {
                    foreach ($msgs as $msg) {
                        echo '<div class="alert alert-' . ($type == 'success' ? 'success' : 'danger') . '">' . htmlspecialchars($msg) . '</div>';
                    }
                }
            }
            ?>
            <form action="login.php" method="post" autocomplete="off" novalidate>
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['login_csrf_token']); ?>">
              <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" placeholder="Your username" required autofocus>
              </div>
              <div class="mb-2">
                <label class="form-label">
                  Password
                  </label>
                <div class="input-group input-group-flat">
                  <input type="password" name="password" class="form-control" placeholder="Your password" autocomplete="current-password" required>
                  </div>
              </div>
              <div class="form-footer">
                <button type="submit" class="btn btn-primary w-100">Sign in</button>
              </div>
            </form>
          </div>
        </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/js/tabler.min.js" defer></script>
  </body>
</html>