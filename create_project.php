<?php

// Function to prompt user for input (remains the same)
function prompt($prompt_msg) {
    echo $prompt_msg . ": ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    return trim($line);
}

function createDirectory($path) {
    if (!file_exists($path)) {
        if (mkdir($path, 0755, true)) { // Changed permissions to 0755
            echo "Created directory: $path\n";
        } else {
            echo "ERROR: Failed to create directory: $path\n";
        }
    } else {
        // echo "Directory already exists: $path\n"; // Less verbose
    }
}

function createFileWithContent($path, $content = '') {
    if (!file_exists($path)) {
        if (file_put_contents($path, $content) !== false) {
            echo "Created file: $path\n";
        } else {
            echo "ERROR: Failed to create file: $path\n";
        }
    } else {
        // echo "File already exists: $path\n"; // Less verbose
    }
}

function copyTemplateFile($sourcePath, $destinationPath) {
    if (file_exists($sourcePath)) {
        if (!file_exists($destinationPath)) {
            if (copy($sourcePath, $destinationPath)) {
                echo "Copied file: $destinationPath\n";
            } else {
                echo "ERROR: Failed to copy $sourcePath to $destinationPath\n";
            }
        } else {
            // echo "File already exists: $destinationPath\n";
        }
    } else {
        echo "ERROR: Template source file not found: $sourcePath\n";
    }
}


function createProject($projectName) {
    $baseDir = rtrim(getcwd(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $projectName; // Use getcwd() for base
    $templateDir = __DIR__ . "/project_template_files"; // Assume template files are in a subfolder

    echo "Creating project in: $baseDir\n";
    createDirectory($baseDir); // Create project root first

    $directories = [
        "$baseDir/actions",
        "$baseDir/css",
        "$baseDir/generator",
        "$baseDir/images",
        "$baseDir/includes",
        "$baseDir/js",
        "$baseDir/pages",
        "$baseDir/uploads"
    ];

    foreach ($directories as $directory) {
        createDirectory($directory);
    }

    // --- Settings.php ---
    $settingsContent = <<<EOD
<?php
// Database settings
return [
    'db' => [
        'host' => 'localhost',
        'dbname' => '$projectName',
        'user' => 'root',
        'password' => '', // Prompt or suggest changing this
    ],
    'app' => [
        'name' => '$projectName',
        'url' => 'http://localhost/$projectName', // Basic URL
    ]
];
EOD;
    createFileWithContent("$baseDir/settings.php", $settingsContent);

    // --- dbconfig.php ---
    $dbconfigContent = <<<EOD
<?php
// Ensure settings.php is in the project root, adjust path if it's elsewhere
\$configPath = __DIR__ . '/../settings.php';
if (!file_exists(\$configPath)) {
    die("Configuration file not found. Please ensure settings.php exists in the project root.");
}
\$config = include(\$configPath);

\$servername = \$config['db']['host'];
\$username = \$config['db']['user'];
\$password = \$config['db']['password'];
\$dbname = \$config['db']['dbname'];

try {
    \$conn = new mysqli(\$servername, \$username, \$password, \$dbname);
    if (\$conn->connect_error) {
        // In a real app, log this error and show a user-friendly message
        die("Connection failed: " . \$conn->connect_error);
    }
    \$conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception \$e) {
    // Catch connection errors if DB doesn't exist or credentials are wrong
    die("Database connection error: " . \$e->getMessage() . " (Check your database server and credentials in settings.php)");
}

// Optional: Set mysqli error reporting mode after successful connection
// mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
?>
EOD;
    createFileWithContent("$baseDir/includes/dbconfig.php", $dbconfigContent);


    // --- header.php (with Tabler UI) ---
    $headerContent = <<<EOD
<?php
// It's crucial that session_start() is called before any output,
// and ideally dbconfig.php (if needed by session for DB-backed sessions)
// is included before session_start or right after.
// For simple file-based sessions, ensure session.php is included first.
require_once('session.php'); // session_start() is in here
require_once('dbconfig.php'); // For other DB operations if needed in header/menu
\$app_config = include(__DIR__ . '/../settings.php');
\$appName = \$app_config['app']['name'] ?? 'My Application';
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
    <title><?php echo isset(\$pageTitle) ? htmlspecialchars(\$pageTitle) . ' - ' : ''; ?><?php echo htmlspecialchars(\$appName); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler-flags.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler-payments.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler-vendors.min.css">
    <link rel="stylesheet" href="../css/style.css"> <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />


    <style>
      body {
        display: flex;
        flex-direction: column;
        min-height: 100vh;
      }
      .page-wrapper {
        flex-grow: 1;
      }
    </style>
  </head>
  <body>
    <div class="page">
      <?php include('menu.php'); ?>
      <div class="page-wrapper">
        <div class="page-body">
          <div class="container-xl">
EOD;
    createFileWithContent("$baseDir/includes/header.php", $headerContent);

    // --- footer.php (with Tabler UI) ---
    $footerContent = <<<EOD
          </div> </div> <footer class="footer footer-transparent d-print-none">
          <div class="container-xl">
            <div class="row text-center align-items-center flex-row-reverse">
              <div class="col-lg-auto ms-lg-auto">
                <ul class="list-inline list-inline-dots mb-0">
                  <li class="list-inline-item"><a href="#" class="link-secondary">Documentation</a></li>
                  <li class="list-inline-item"><a href="#" class="link-secondary">License</a></li>
                </ul>
              </div>
              <div class="col-12 col-lg-auto mt-3 mt-lg-0">
                <ul class="list-inline list-inline-dots mb-0">
                  <li class="list-inline-item">
                    Copyright &copy; <?php echo date("Y"); ?>
                    <a href="." class="link-secondary"><?php echo htmlspecialchars(\$appName ?? 'My App'); ?></a>.
                    All rights reserved.
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </footer>
      </div> </div> <script src="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/js/tabler.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="../js/custom_script.js"></script> <?php
    // Flash messages display using Tabler Alerts
    \$messages = get_flash_messages();
    if (!empty(\$messages)): ?>
    <div style="position: fixed; top: 1rem; right: 1rem; z-index: 1050;">
        <?php foreach (\$messages as \$type => \$msgs): ?>
            <?php foreach (\$msgs as \$msg): ?>
                <div class="alert alert-<?php echo (\$type == 'success' ? 'success' : 'danger'); ?> alert-dismissible" role="alert">
                    <div class="d-flex">
                        <div>
                            <?php if (\$type == 'success'): ?>
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"></path><path d="M5 12l5 5l10 -10"></path></svg>
                            <?php else: ?>
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"></path><path d="M12 9v2m0 4v.01"></path><path d="M5 19h14a2 2 0 0 0 1.84 -2.75l-7.1 -12.25a2 2 0 0 0 -3.5 0l-7.1 12.25a2 2 0 0 0 1.75 2.75"></path></svg>
                            <?php endif; ?>
                        </div>
                        <div>
                            <h4 class="alert-title"><?php echo ucfirst(\$type); ?>!</h4>
                            <div class="text-muted"><?php echo htmlspecialchars(\$msg); ?></div>
                        </div>
                    </div>
                    <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
                </div>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </div>
    <script>
    // Auto-dismiss alerts after 5 seconds
    window.setTimeout(function() {
        $(".alert-dismissible").fadeTo(500, 0).slideUp(500, function(){
            $(this).remove();
        });
    }, 5000);
    </script>
    <?php endif; ?>
  </body>
</html>
EOD;
    createFileWithContent("$baseDir/includes/footer.php", $footerContent);

    // --- menu.php (Adapted for Tabler) ---
    // This will be dynamically built later, but a basic structure:
    $menuContent = <<<EOD
<header class="navbar navbar-expand-md d-print-none">
    <div class="container-xl">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu" aria-controls="navbar-menu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <h1 class="navbar-brand navbar-brand-autodark d-none-navbar-horizontal pe-0 pe-md-3">
            <a href="../pages/dashboard.php">
                <?php echo htmlspecialchars(\$appName ?? 'App'); ?>
            </a>
        </h1>
        <div class="navbar-nav flex-row order-md-last">
            <div class="nav-item dropdown">
                <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown" aria-label="Open user menu">
                    <span class="avatar avatar-sm" style="background-image: url(../images/user-icon.gif)"></span>
                    <div class="d-none d-xl-block ps-2">
                        <div><?php echo htmlspecialchars(\$_SESSION['username'] ?? 'User'); ?></div>
                        <div class="mt-1 small text-muted"><?php // echo htmlspecialchars(\$_SESSION['role_name'] ?? 'Role'); // You'd need to fetch role name ?></div>
                    </div>
                </a>
                <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                    <a href="../pages/dashboard.php?page_key=change_password" class="dropdown-item">Change Password</a>
                    <div class="dropdown-divider"></div>
                    <a href="../pages/logout.php" class="dropdown-item">Logout</a>
                </div>
            </div>
        </div>
        <div class="collapse navbar-collapse" id="navbar-menu">
            <div class="d-flex flex-column flex-md-row flex-fill align-items-stretch align-items-md-center">
                <ul class="navbar-nav">
                    <?php
                    // This part will be replaced by dynamic menu generation based on DB
                    // For now, a placeholder:
                    // echo '<li class="nav-item"><a class="nav-link" href="../pages/dashboard.php">Dashboard Home</a></li>';

                    // Fetch and display dynamic menu (from includes/dynamic_menu_builder.php or similar)
                    if (file_exists(__DIR__ . '/dynamic_menu_builder.php')) {
                        include(__DIR__ . '/dynamic_menu_builder.php');
                    } else {
                         echo '<li class="nav-item"><a class="nav-link" href="../pages/dashboard.php"><svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-home" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l-2 0l9 -9l9 9l-2 0" /><path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-7" /><path d="M9 21v-6a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v6" /></svg>Dashboard</a></li>';
                    }
                    ?>
                </ul>
            </div>
        </div>
    </div>
</header>
EOD;
    createFileWithContent("$baseDir/includes/menu.php", $menuContent);

    // --- dynamic_menu_builder.php (New file for menu logic) ---
    $dynamicMenuBuilderContent = <<<EOD
<?php
// This script generates the menu structure based on the database and user permissions.
// Ensure \$conn (database connection) and check_permission() are available.

if (!isset(\$conn)) {
    // Attempt to include dbconfig if not already included and \$conn is not set
    // This is a fallback, ideally dbconfig is included earlier
    if(file_exists(__DIR__ . '/dbconfig.php')) {
        include_once __DIR__ . '/dbconfig.php';
    } else if (file_exists(__DIR__ . '/../includes/dbconfig.php')) { // If menu.php is in pages
         include_once __DIR__ . '/../includes/dbconfig.php';
    }
}
if (!isset(\$conn)) {
    echo '<li class="nav-item"><span class="nav-link text-danger">Error: DB Connection for menu.</span></li>';
    return; // Exit script if no DB connection
}


// Fetch main menu items user has access to at least one submenu of
\$sql_main_menu = "SELECT DISTINCT m.menu_id, m.menu_name, m.menu_icon, m.menu_order
                  FROM menu m
                  JOIN submenu sm ON m.menu_id = sm.menu_id
                  JOIN pages p ON sm.page_id = p.page_id
                  WHERE EXISTS (
                      SELECT 1
                      FROM user_permission_groups upg
                      JOIN permission_group_permissions pgp ON upg.group_id = pgp.group_id
                      JOIN permissions perm ON pgp.permission_id = perm.permission_id
                      WHERE upg.user_id = ? AND perm.permission_name = CONCAT('read_manage_', p.page_unique_key)
                  ) OR m.is_public = 1
                  ORDER BY m.menu_order, m.menu_name";

\$user_id_for_menu = \$_SESSION['user_id'] ?? 0;
\$stmt_main = \$conn->prepare(\$sql_main_menu);
if (!\$stmt_main) {
    error_log("Menu Main Prepare failed: (" . \$conn->errno . ") " . \$conn->error);
    echo '<li class="nav-item"><span class="nav-link text-danger">Error preparing menu.</span></li>';
    return;
}
\$stmt_main->bind_param("i", \$user_id_for_menu);
\$stmt_main->execute();
\$result_main_menu = \$stmt_main->get_result();

while (\$main_row = \$result_main_menu->fetch_assoc()) {
    // Fetch submenus for this main menu that the user has permission for
    \$sql_submenu = "SELECT sm.submenu_name, sm.submenu_icon, p.page_unique_key, p.page_title
                    FROM submenu sm
                    JOIN pages p ON sm.page_id = p.page_id
                    WHERE sm.menu_id = ? AND (EXISTS (
                        SELECT 1
                        FROM user_permission_groups upg
                        JOIN permission_group_permissions pgp ON upg.group_id = pgp.group_id
                        JOIN permissions perm ON pgp.permission_id = perm.permission_id
                        WHERE upg.user_id = ? AND perm.permission_name = CONCAT('read_manage_', p.page_unique_key)
                    ) OR sm.is_public = 1)
                    ORDER BY sm.submenu_order, sm.submenu_name";

    \$stmt_sub = \$conn->prepare(\$sql_submenu);
    if (!\$stmt_sub) {
        error_log("Menu Sub Prepare failed: (" . \$conn->errno . ") " . \$conn->error);
        continue;
    }
    \$stmt_sub->bind_param("ii", \$main_row['menu_id'], \$user_id_for_menu);
    \$stmt_sub->execute();
    \$result_submenu = \$stmt_sub->get_result();

    \$sub_items_html = '';
    \$has_visible_submenus = false;
    while (\$sub_row = \$result_submenu->fetch_assoc()) {
        \$has_visible_submenus = true;
        \$page_title_attr = htmlspecialchars(\$sub_row['page_title'] ?: \$sub_row['submenu_name']);
        \$sub_items_html .= '<a class="dropdown-item" title="' . \$page_title_attr . '" href="../pages/dashboard.php?page_key=' . htmlspecialchars(\$sub_row['page_unique_key']) . '">';
        if (!empty(\$sub_row['submenu_icon'])) {
            \$sub_items_html .= \$sub_row['submenu_icon'] . ' '; // Assuming submenu_icon stores full SVG or icon class
        }
        \$sub_items_html .= htmlspecialchars(\$sub_row['submenu_name']) . '</a>';
    }
    \$stmt_sub->close();

    if (\$has_visible_submenus) {
        echo '<li class="nav-item dropdown">';
        echo '<a class="nav-link dropdown-toggle" href="#navbar-' . htmlspecialchars(strtolower(str_replace(' ', '-', \$main_row['menu_name']))) . '" data-bs-toggle="dropdown" data-bs-auto-close="outside" role="button" aria-expanded="false">';
        if (!empty(\$main_row['menu_icon'])) {
             echo '<span class="nav-link-icon d-md-none d-lg-inline-block">' . \$main_row['menu_icon'] . '</span>'; // Icon
        }
        echo '<span class="nav-link-title">' . htmlspecialchars(\$main_row['menu_name']) . '</span>';
        echo '</a>';
        echo '<div class="dropdown-menu" id="navbar-' . htmlspecialchars(strtolower(str_replace(' ', '-', \$main_row['menu_name']))) . '">';
        echo \$sub_items_html;
        echo '</div>';
        echo '</li>';
    }
}
\$stmt_main->close();
?>
EOD;
    createFileWithContent("$baseDir/includes/dynamic_menu_builder.php", $dynamicMenuBuilderContent);


    // --- session.php (Small update for role_name if desired, or ensure role_id is enough) ---
    $sessionContent = <<<EOD
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Regenerate ID periodically or on login for security
if (!isset(\$_SESSION['initiated'])) {
    session_regenerate_id(true);
    \$_SESSION['initiated'] = true;
}

// Basic CSRF token generation
if (empty(\$_SESSION['csrf_token'])) {
    \$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


// Check if user is logged in, except for specific pages like login.php itself
// Get the current script name
\$current_page = basename(\$_SERVER['PHP_SELF']);
\$allowed_unauthenticated_pages = ['login.php']; // Add any other public pages

if (!isset(\$_SESSION['user_id']) && !in_array(\$current_page, \$allowed_unauthenticated_pages)) {
    // Store the intended destination
    // \$_SESSION['redirect_url'] = \$_SERVER['REQUEST_URI']; // Optional: for redirecting after login
    header("Location: ../pages/login.php"); // Adjust path if session.php is in a different location relative to login.php
    exit();
}

function check_permission(\$permission_name) {
    if (!isset(\$_SESSION['user_id'])) {
        return false;
    }
    // This function needs a database connection.
    // It's better to pass \$conn as a parameter or use a global/singleton if you must.
    global \$conn; // Assuming \$conn is available globally from dbconfig.php

    if (!\$conn) {
        // Log this error. Cannot check permission without DB.
        error_log("check_permission: Database connection is not available.");
        return false; // Fail safe: deny permission
    }

    \$user_id = \$_SESSION['user_id'];

    // Updated SQL to join through roles and user_roles if that's your new structure,
    // or directly through user_permission_groups if that's still the case.
    // This example assumes user_permission_groups is still the direct link.
    \$sql = "SELECT 1
            FROM user_permission_groups upg
            JOIN permission_group_permissions pgp ON upg.group_id = pgp.group_id
            JOIN permissions p ON pgp.permission_id = p.permission_id
            WHERE upg.user_id = ? AND p.permission_name = ?";
    \$stmt = \$conn->prepare(\$sql);
    if (!\$stmt) {
        error_log("Permission check SQL prepare failed: " . \$conn->error);
        return false;
    }
    \$stmt->bind_param("is", \$user_id, \$permission_name);
    \$stmt->execute();
    \$result = \$stmt->get_result();
    \$stmt->close();

    return \$result->num_rows > 0;
}

function set_flash_message(\$type, \$message) {
    if (session_status() === PHP_SESSION_NONE) { // Ensure session is started
        session_start();
    }
    \$_SESSION['flash_messages'][\$type][] = \$message;
}

function get_flash_messages() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    \$messages = \$_SESSION['flash_messages'] ?? [];
    unset(\$_SESSION['flash_messages']); // Clear after displaying
    return \$messages;
}

// Function to verify CSRF token
function verify_csrf_token(\$token_from_form, \$action_name = 'default') {
    if (empty(\$_SESSION['csrf_token_'.\$action_name]) || !hash_equals(\$_SESSION['csrf_token_'.\$action_name], \$token_from_form)) {
        // Token mismatch - handle error (e.g., log, show error, redirect)
        set_flash_message('danger', 'Invalid security token. Please try again.');
        return false;
    }
    // Invalidate the token after use for this specific action
    unset(\$_SESSION['csrf_token_'.\$action_name]);
    return true;
}

// Function to generate a CSRF token for a specific action form
function generate_csrf_token_field(\$action_name = 'default') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    \$_SESSION['csrf_token_'.\$action_name] = bin2hex(random_bytes(32));
    return '<input type="hidden" name="csrf_token" value="' . \$_SESSION['csrf_token_'.\$action_name] . '">';
}
?>
EOD;
    createFileWithContent("$baseDir/includes/session.php", $sessionContent);


    // --- dashboard.php (Modified for dynamic routing by page_key) ---
    $dashboardContent = <<<EOD
<?php
// \$pageTitle should be set before including header.php
// This will be determined by the specific page being loaded.
// Default page title if no specific page is loaded
\$pageTitle = "Dashboard Overview";


// Determine page key and fetch page data
\$current_page_details = null;
if (isset(\$_GET['page_key'])) {
    // Ensure dbconfig is included to have \$conn available
    // header.php includes session.php, which should set up \$conn if it also includes dbconfig.php
    // For safety, ensure \$conn is available here or include dbconfig.php explicitly if needed.
    if (!isset(\$conn) && file_exists(__DIR__ . '/../includes/dbconfig.php')) {
        require_once(__DIR__ . '/../includes/dbconfig.php');
    }

    \$page_key = \$_GET['page_key'];
    if (isset(\$conn)) {
        \$stmt = \$conn->prepare("SELECT page_title, file_path, permission_required FROM pages WHERE page_unique_key = ? AND is_active = 1");
        if (\$stmt) {
            \$stmt->bind_param("s", \$page_key);
            \$stmt->execute();
            \$result = \$stmt->get_result();
            if (\$page_data = \$result->fetch_assoc()) {
                \$current_page_details = \$page_data;
                \$pageTitle = htmlspecialchars(\$current_page_details['page_title']); // Set page title for header
            }
            \$stmt->close();
        } else {
            error_log("Dashboard page query prepare failed: " . \$conn->error);
            // Handle error, maybe set \$pageTitle to "Error"
        }
    }
}

include('../includes/header.php'); // Header now uses \$pageTitle
?>

<?php
if (\$current_page_details) {
    // Check permission for the page
    if (check_permission(\$current_page_details['permission_required'])) {
        \$filePath = __DIR__ . '/' . \$current_page_details['file_path']; // file_path should be like 'manage_users.php'
        if (file_exists(\$filePath)) {
            include(\$filePath);
        } else {
            echo '<div class="alert alert-danger" role="alert">Page file not found: ' . htmlspecialchars(\$current_page_details['file_path']) . '</div>';
        }
    } else {
        set_flash_message('danger', 'You do not have permission to access this page.');
        // Option 1: Show error on dashboard
        echo '<div class="alert alert-danger" role="alert">Access Denied: You do not have permission for this page.</div>';
        // Option 2: Redirect to dashboard home (if preferred, but might hide the flash message if not handled carefully)
        // header('Location: dashboard.php'); exit();
    }
} else if (isset(\$_GET['page_key'])) {
    // A page_key was provided but no matching active page found
    echo '<div class="alert alert-danger" role="alert">The requested page could not be found or is inactive.</div>';
} else {
    // No page_key, show default dashboard content (your Tabler cards and stats)
    // This can be moved to a separate file e.g., includes/dashboard_overview.php
    if (file_exists(__DIR__ . '/../includes/dashboard_overview_content.php')) {
        include(__DIR__ . '/../includes/dashboard_overview_content.php');
    } else {
        echo "Welcome to the dashboard!"; // Fallback
    }
}
?>
<?php include('../includes/footer.php'); ?>
EOD;
    createFileWithContent("$baseDir/pages/dashboard.php", $dashboardContent);

    // --- Create dashboard_overview_content.php ---
    // (Move the statistical cards from your uploaded dashboard.php to this new file)
    $dashboardOverviewContent = <<<EOD
<?php
// This file contains the main content for the dashboard when no specific page is selected.
// Ensure \$conn and check_permission() are available.
// Example: (Counts for users, roles, etc. - adapt from your provided dashboard.php)
?>
<div class="row row-deck row-cards">
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Users</div>
                    </div>
                <div class="h1 mb-3 mt-1">
                    <?php
                        if(isset(\$conn)) {
                            \$sql_count_users = "SELECT COUNT(*) as count FROM users";
                            \$result_count_users = \$conn->query(\$sql_count_users);
                            if(\$result_count_users) \$row_count_users = \$result_count_users->fetch_assoc();
                            echo \$row_count_users['count'] ?? 0;
                        } else { echo 0; }
                    ?>
                </div>
                <div class="d-flex mb-2">
                    <div>Total registered users</div>
                </div>
                <div class="progress progress-sm">
                    <div class="progress-bar bg-primary" style="width: 100%" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Modules Managed</div>
                </div>
                <div class="h1 mb-3 mt-1">
                    <?php
                        if(isset(\$conn)) {
                            \$sql_count_pages = "SELECT COUNT(*) as count FROM pages WHERE is_crud_page = 1"; // Assuming a flag for CRUD pages
                            \$result_count_pages = \$conn->query(\$sql_count_pages);
                            if(\$result_count_pages) \$row_count_pages = \$result_count_pages->fetch_assoc();
                            echo \$row_count_pages['count'] ?? 0;
                        } else { echo 0; }
                    ?>
                </div>
                 <div class="d-flex mb-2">
                    <div>Active CRUD Modules</div>
                </div>
                <div class="progress progress-sm">
                    <div class="progress-bar bg-success" style="width: 100%" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>
        </div>
    </div>

</div>

<div class="row row-cards mt-4">
     <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Menu Structure</h3>
            </div>
            <div class="card-body">
                <?php if(isset(\$conn) && check_permission('read_core_menu_structure')): ?>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>Menu</th>
                                <th>Submenus</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            \$sql_menu_overview = "SELECT m.menu_name, COUNT(s.submenu_id) as submenu_count
                                    FROM menu m
                                    LEFT JOIN submenu s ON m.menu_id = s.menu_id
                                    GROUP BY m.menu_id, m.menu_name ORDER BY m.menu_order";
                            \$result_menu_overview = \$conn->query(\$sql_menu_overview);
                            if(\$result_menu_overview){
                                while (\$row_menu_overview = \$result_menu_overview->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars(\$row_menu_overview['menu_name']) . "</td>";
                                    echo "<td>" . \$row_menu_overview['submenu_count'] . "</td>";
                                    echo "</tr>";
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted">You do not have permission to view menu structure details.</p>
                <?php endif; ?>
            </div>
            <?php if (check_permission('read_manage_menu')): // Assuming 'manage_menu' is the page_unique_key for menu management ?>
            <div class="card-footer text-end">
                <a href="dashboard.php?page_key=manage_menu" class="btn btn-primary btn-sm">
                    Manage Menus
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    </div>


<div class="text-center mt-4">
    <h4>Quick Actions</h4>
     <?php
        // Example: Link to a page that lists all available modules
        // if (check_permission('read_module_overview')) {
        //    echo '<a href="dashboard.php?page_key=all_modules" class="btn btn-outline-primary">View All Modules</a>';
        // }
    ?>
</div>
EOD;
    createFileWithContent("$baseDir/includes/dashboard_overview_content.php", $dashboardOverviewContent);


    // --- login.php (Adapted for Tabler) ---
    $loginContent = <<<EOD
<?php
// No session_start() here if it's already in dbconfig or if this page should not have a session initially.
// However, to set flash messages on error, session_start might be needed.
// Let's assume dbconfig.php does NOT start session, and session.php (which does) is NOT included on login page directly.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once('../includes/dbconfig.php'); // For \$conn
require_once('../includes/session_functions_standalone.php'); // For set_flash_message, get_flash_messages

\$app_config = include(__DIR__ . '/../settings.php');
\$appName = \$app_config['app']['name'] ?? 'My Application';


// Redirect if already logged in
if (isset(\$_SESSION['user_id'])) {
    header("Location: ../pages/dashboard.php");
    exit();
}

\$error_message = '';
if (\$_SERVER['REQUEST_METHOD'] == 'POST') {
    if (empty(\$_POST['csrf_token']) || !hash_equals(\$_SESSION['login_csrf_token'] ?? '', \$_POST['csrf_token'])) {
        \$error_message = "Invalid CSRF token. Please try reloading the page.";
    } else {
        \$username = trim(\$_POST['username']);
        \$password = \$_POST['password']; // No trim on password

        if (empty(\$username) || empty(\$password)) {
            \$error_message = "Username and password are required.";
        } else {
            \$sql = "SELECT user_id, username, password, role_id FROM users WHERE username = ? AND is_active = 1"; // Added is_active check
            \$stmt = \$conn->prepare(\$sql);
            if (!\$stmt) {
                \$error_message = "Login error. Please try again later. (DBP)";
                error_log("Login SQL prepare failed: " . \$conn->error);
            } else {
                \$stmt->bind_param("s", \$username);
                \$stmt->execute();
                \$result = \$stmt->get_result();
                if (\$result->num_rows === 1) {
                    \$user = \$result->fetch_assoc();
                    if (password_verify(\$password, \$user['password'])) {
                        // Password is correct, regenerate session ID for security
                        session_regenerate_id(true);
                        \$_SESSION['user_id'] = \$user['user_id'];
                        \$_SESSION['username'] = \$user['username'];
                        \$_SESSION['role_id'] = \$user['role_id']; // Store role_id
                        // \$_SESSION['role_name'] = ... // Fetch role_name if needed for display

                        // Unset the login CSRF token
                        unset(\$_SESSION['login_csrf_token']);

                        // Redirect to dashboard or intended URL
                        \$redirect_url = \$_SESSION['redirect_url'] ?? '../pages/dashboard.php';
                        unset(\$_SESSION['redirect_url']);
                        header("Location: " . \$redirect_url);
                        exit();
                    } else {
                        \$error_message = "Invalid username or password.";
                    }
                } else {
                    \$error_message = "Invalid username or password.";
                }
                \$stmt->close();
            }
        }
    }
}
// Generate a new CSRF token for the login form
\$_SESSION['login_csrf_token'] = bin2hex(random_bytes(32));
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
    <title>Login - <?php echo htmlspecialchars(\$appName); ?></title>
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
            <h2><?php echo htmlspecialchars(\$appName); ?></h2>
          </a>
        </div>
        <div class="card card-md">
          <div class="card-body">
            <h2 class="h2 text-center mb-4">Login to your account</h2>
            <?php if (!empty(\$error_message)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars(\$error_message); ?>
                </div>
            <?php endif; ?>
            <?php
            // Display flash messages if redirected here with one
            // This requires session_functions_standalone.php to be included
            \$flash_messages = get_flash_messages_standalone(); // Use the standalone getter
            if (!empty(\$flash_messages)) {
                foreach (\$flash_messages as \$type => \$msgs) {
                    foreach (\$msgs as \$msg) {
                        echo '<div class="alert alert-' . (\$type == 'success' ? 'success' : 'danger') . '">' . htmlspecialchars(\$msg) . '</div>';
                    }
                }
            }
            ?>
            <form action="login.php" method="post" autocomplete="off" novalidate>
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(\$_SESSION['login_csrf_token']); ?>">
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
EOD;
    createFileWithContent("$baseDir/pages/login.php", $loginContent);

    // --- Create includes/session_functions_standalone.php (for login page flash messages) ---
    $sessionFunctionsStandalone = <<<EOD
<?php
// Standalone session functions for pages that might not include the full session.php
// (e.g., login page before full session is established)

function set_flash_message_standalone(\$type, \$message) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    \$_SESSION['flash_messages'][\$type][] = \$message;
}

function get_flash_messages_standalone() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    \$messages = \$_SESSION['flash_messages'] ?? [];
    unset(\$_SESSION['flash_messages']); // Clear after displaying
    return \$messages;
}
?>
EOD;
    createFileWithContent("$baseDir/includes/session_functions_standalone.php", $sessionFunctionsStandalone);


    // --- logout.php (remains simple) ---
    $logoutContent = <<<EOD
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Standard session destruction
\$_SESSION = array(); // Unset all session variables
if (ini_get("session.use_cookies")) {
    \$params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        \$params["path"], \$params["domain"],
        \$params["secure"], \$params["httponly"]
    );
}
session_destroy();
header("Location: ../pages/login.php?status=logged_out");
exit();
?>
EOD;
    createFileWithContent("$baseDir/pages/logout.php", $logoutContent);


    // --- index.php (remains simple) ---
    $indexContent = <<<EOD
<?php
// Redirect to the main application page, typically login or dashboard
header("Location: pages/login.php");
exit();
?>
EOD;
    createFileWithContent("$baseDir/index.php", $indexContent);

    // --- Copying template files (Replaces downloadFile) ---
    $templateFilesToCopy = [
        // Generator Scripts (Assuming you have v13 locally)
        'generator/CRUDGeneratorv13.php' => "$baseDir/generator/CRUDGenerator.php", // Standardize name
        'generator/create_tables_base.php' => "$baseDir/generator/create_tables_base.php", // The script that creates menu/user/role tables
        'generator/create_crud_runner.php' => "$baseDir/generator/create_crud_runner.php", // The script that will run CRUDGenerator for app tables

        // CSS
        'css/style.css' => "$baseDir/css/style.css", // Your main custom stylesheet
        // 'css/fileuploadmodel.css' => "$baseDir/css/fileuploadmodel.css", // If still needed

        // JS
        // 'js/cdn.js' => "$baseDir/js/cdn.js", // If this was a local bundle, otherwise rely on CDNs in footer
        'js/custom_script.js' => "$baseDir/js/custom_script.js", // For global custom JS
        'js/image_upload_plugin.js' => "$baseDir/js/image_upload_plugin.js", // If still used

        // Images
        'images/user-icon.gif' => "$baseDir/images/user-icon.gif",
        // Add your project logo here if you have one
        // 'images/logo.svg' => "$baseDir/images/logo.svg",
    ];

    foreach ($templateFilesToCopy as $sourceRelative => $destAbsolute) {
        copyTemplateFile("$templateDir/$sourceRelative", $destAbsolute);
    }

    // Create an empty custom_script.js and style.css if not copied from template
    createFileWithContent("$baseDir/js/custom_script.js", "// Custom JavaScript for your project\n\$(document).ready(function() {\n    // Initialize Select2 for all select elements with class .select2-basic\n    // \$('.select2-basic').select2({\n    //     theme: 'bootstrap-5'\n    // });\n});");
    createFileWithContent("$baseDir/css/style.css", "/* Custom CSS for your project */\nbody {\n    /* font-family: 'Inter', sans-serif; */ /* Example if Tabler's default is overridden */\n}\n");


    echo "\nProject '$projectName' structure created successfully.\n";
    echo "Next steps:\n";
    echo "1. Create a database named '$projectName' (or update settings.php if different).\n";
    echo "2. Navigate to '$baseDir/generator/' and run 'php create_tables_base.php' to set up core admin tables.\n";
    echo "   (You will be prompted for database credentials for this script to connect and create tables).\n";
    echo "3. Configure your web server to point to '$baseDir' as the document root.\n";
    echo "4. Access the application in your browser.\n";
    echo "5. Use 'php create_crud_runner.php' (after configuring it) to generate CRUDs for your application tables.\n";
    echo "IMPORTANT: Change the default admin password in 'create_tables_base.php' before running it, or change it immediately after via the application.\n";

}

// --- Main Execution ---
if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.");
}

if ($argc > 1) {
    $projectName = $argv[1];
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $projectName)) {
        die("Error: Project name can only contain alphanumeric characters, underscores, and hyphens.\n");
    }
    createProject($projectName);
} else {
    echo "Usage: php create_project.php <ProjectName>\n";
}

?>