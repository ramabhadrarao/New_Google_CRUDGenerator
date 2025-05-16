<?php
// Standalone session functions for pages that might not include the full session.php
// (e.g., login page before full session is established)

function set_flash_message_standalone($type, $message) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash_messages'][$type][] = $message;
}

function get_flash_messages_standalone() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']); // Clear after displaying
    return $messages;
}
?>