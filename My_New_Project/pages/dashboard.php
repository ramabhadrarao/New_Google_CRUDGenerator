<?php
// $pageTitle should be set before including header.php
// This will be determined by the specific page being loaded.
// Default page title if no specific page is loaded
$pageTitle = "Dashboard Overview";


// Determine page key and fetch page data
$current_page_details = null;
if (isset($_GET['page_key'])) {
    // Ensure dbconfig is included to have $conn available
    // header.php includes session.php, which should set up $conn if it also includes dbconfig.php
    // For safety, ensure $conn is available here or include dbconfig.php explicitly if needed.
    if (!isset($conn) && file_exists(__DIR__ . '/../includes/dbconfig.php')) {
        require_once(__DIR__ . '/../includes/dbconfig.php');
    }

    $page_key = $_GET['page_key'];
    if (isset($conn)) {
        $stmt = $conn->prepare("SELECT page_title, file_path, permission_required FROM pages WHERE page_unique_key = ? AND is_active = 1");
        if ($stmt) {
            $stmt->bind_param("s", $page_key);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($page_data = $result->fetch_assoc()) {
                $current_page_details = $page_data;
                $pageTitle = htmlspecialchars($current_page_details['page_title']); // Set page title for header
            }
            $stmt->close();
        } else {
            error_log("Dashboard page query prepare failed: " . $conn->error);
            // Handle error, maybe set $pageTitle to "Error"
        }
    }
}

include('../includes/header.php'); // Header now uses $pageTitle
?>

<?php
if ($current_page_details) {
    // Check permission for the page
    if (check_permission($current_page_details['permission_required'])) {
        $filePath = __DIR__ . '/' . $current_page_details['file_path']; // file_path should be like 'manage_users.php'
        if (file_exists($filePath)) {
            include($filePath);
        } else {
            echo '<div class="alert alert-danger" role="alert">Page file not found: ' . htmlspecialchars($current_page_details['file_path']) . '</div>';
        }
    } else {
        set_flash_message('danger', 'You do not have permission to access this page.');
        // Option 1: Show error on dashboard
        echo '<div class="alert alert-danger" role="alert">Access Denied: You do not have permission for this page.</div>';
        // Option 2: Redirect to dashboard home (if preferred, but might hide the flash message if not handled carefully)
        // header('Location: dashboard.php'); exit();
    }
} else if (isset($_GET['page_key'])) {
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