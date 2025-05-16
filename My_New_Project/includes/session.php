<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Regenerate ID periodically or on login for security
if (!isset($_SESSION['initiated'])) {
    session_regenerate_id(true);
    $_SESSION['initiated'] = true;
}

// Basic CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


// Check if user is logged in, except for specific pages like login.php itself
// Get the current script name
$current_page = basename($_SERVER['PHP_SELF']);
$allowed_unauthenticated_pages = ['login.php']; // Add any other public pages

if (!isset($_SESSION['user_id']) && !in_array($current_page, $allowed_unauthenticated_pages)) {
    // Store the intended destination
    // $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI']; // Optional: for redirecting after login
    header("Location: ../pages/login.php"); // Adjust path if session.php is in a different location relative to login.php
    exit();
}

function check_permission($permission_name) {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    // This function needs a database connection.
    // It's better to pass $conn as a parameter or use a global/singleton if you must.
    global $conn; // Assuming $conn is available globally from dbconfig.php

    if (!$conn) {
        // Log this error. Cannot check permission without DB.
        error_log("check_permission: Database connection is not available.");
        return false; // Fail safe: deny permission
    }

    $user_id = $_SESSION['user_id'];

    // Updated SQL to join through roles and user_roles if that's your new structure,
    // or directly through user_permission_groups if that's still the case.
    // This example assumes user_permission_groups is still the direct link.
    $sql = "SELECT 1
            FROM user_permission_groups upg
            JOIN permission_group_permissions pgp ON upg.group_id = pgp.group_id
            JOIN permissions p ON pgp.permission_id = p.permission_id
            WHERE upg.user_id = ? AND p.permission_name = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Permission check SQL prepare failed: " . $conn->error);
        return false;
    }
    $stmt->bind_param("is", $user_id, $permission_name);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    return $result->num_rows > 0;
}

function set_flash_message($type, $message) {
    if (session_status() === PHP_SESSION_NONE) { // Ensure session is started
        session_start();
    }
    $_SESSION['flash_messages'][$type][] = $message;
}

function get_flash_messages() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']); // Clear after displaying
    return $messages;
}

// Function to verify CSRF token
function verify_csrf_token($token_from_form, $action_name = 'default') {
    if (empty($_SESSION['csrf_token_'.$action_name]) || !hash_equals($_SESSION['csrf_token_'.$action_name], $token_from_form)) {
        // Token mismatch - handle error (e.g., log, show error, redirect)
        set_flash_message('danger', 'Invalid security token. Please try again.');
        return false;
    }
    // Invalidate the token after use for this specific action
    unset($_SESSION['csrf_token_'.$action_name]);
    return true;
}

// Function to generate a CSRF token for a specific action form
function generate_csrf_token_field($action_name = 'default') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['csrf_token_'.$action_name] = bin2hex(random_bytes(32));
    return '<input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token_'.$action_name] . '">';
}
?>