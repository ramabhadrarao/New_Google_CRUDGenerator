<?php
// This script generates the menu structure based on the database and user permissions.
// Ensure $conn (database connection) and check_permission() are available.

if (!isset($conn)) {
    // Attempt to include dbconfig if not already included and $conn is not set
    // This is a fallback, ideally dbconfig is included earlier
    if(file_exists(__DIR__ . '/dbconfig.php')) {
        include_once __DIR__ . '/dbconfig.php';
    } else if (file_exists(__DIR__ . '/../includes/dbconfig.php')) { // If menu.php is in pages
         include_once __DIR__ . '/../includes/dbconfig.php';
    }
}
if (!isset($conn)) {
    echo '<li class="nav-item"><span class="nav-link text-danger">Error: DB Connection for menu.</span></li>';
    return; // Exit script if no DB connection
}


// Fetch main menu items user has access to at least one submenu of
$sql_main_menu = "SELECT DISTINCT m.menu_id, m.menu_name, m.menu_icon, m.menu_order
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

$user_id_for_menu = $_SESSION['user_id'] ?? 0;
$stmt_main = $conn->prepare($sql_main_menu);
if (!$stmt_main) {
    error_log("Menu Main Prepare failed: (" . $conn->errno . ") " . $conn->error);
    echo '<li class="nav-item"><span class="nav-link text-danger">Error preparing menu.</span></li>';
    return;
}
$stmt_main->bind_param("i", $user_id_for_menu);
$stmt_main->execute();
$result_main_menu = $stmt_main->get_result();

while ($main_row = $result_main_menu->fetch_assoc()) {
    // Fetch submenus for this main menu that the user has permission for
    $sql_submenu = "SELECT sm.submenu_name, sm.submenu_icon, p.page_unique_key, p.page_title
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

    $stmt_sub = $conn->prepare($sql_submenu);
    if (!$stmt_sub) {
        error_log("Menu Sub Prepare failed: (" . $conn->errno . ") " . $conn->error);
        continue;
    }
    $stmt_sub->bind_param("ii", $main_row['menu_id'], $user_id_for_menu);
    $stmt_sub->execute();
    $result_submenu = $stmt_sub->get_result();

    $sub_items_html = '';
    $has_visible_submenus = false;
    while ($sub_row = $result_submenu->fetch_assoc()) {
        $has_visible_submenus = true;
        $page_title_attr = htmlspecialchars($sub_row['page_title'] ?: $sub_row['submenu_name']);
        $sub_items_html .= '<a class="dropdown-item" title="' . $page_title_attr . '" href="../pages/dashboard.php?page_key=' . htmlspecialchars($sub_row['page_unique_key']) . '">';
        if (!empty($sub_row['submenu_icon'])) {
            $sub_items_html .= $sub_row['submenu_icon'] . ' '; // Assuming submenu_icon stores full SVG or icon class
        }
        $sub_items_html .= htmlspecialchars($sub_row['submenu_name']) . '</a>';
    }
    $stmt_sub->close();

    if ($has_visible_submenus) {
        echo '<li class="nav-item dropdown">';
        echo '<a class="nav-link dropdown-toggle" href="#navbar-' . htmlspecialchars(strtolower(str_replace(' ', '-', $main_row['menu_name']))) . '" data-bs-toggle="dropdown" data-bs-auto-close="outside" role="button" aria-expanded="false">';
        if (!empty($main_row['menu_icon'])) {
             echo '<span class="nav-link-icon d-md-none d-lg-inline-block">' . $main_row['menu_icon'] . '</span>'; // Icon
        }
        echo '<span class="nav-link-title">' . htmlspecialchars($main_row['menu_name']) . '</span>';
        echo '</a>';
        echo '<div class="dropdown-menu" id="navbar-' . htmlspecialchars(strtolower(str_replace(' ', '-', $main_row['menu_name']))) . '">';
        echo $sub_items_html;
        echo '</div>';
        echo '</li>';
    }
}
$stmt_main->close();
?>