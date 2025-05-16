<?php
// This file contains the main content for the dashboard when no specific page is selected.
// Ensure $conn and check_permission() are available.
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
                        if(isset($conn)) {
                            $sql_count_users = "SELECT COUNT(*) as count FROM users";
                            $result_count_users = $conn->query($sql_count_users);
                            if($result_count_users) $row_count_users = $result_count_users->fetch_assoc();
                            echo $row_count_users['count'] ?? 0;
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
                        if(isset($conn)) {
                            $sql_count_pages = "SELECT COUNT(*) as count FROM pages WHERE is_crud_page = 1"; // Assuming a flag for CRUD pages
                            $result_count_pages = $conn->query($sql_count_pages);
                            if($result_count_pages) $row_count_pages = $result_count_pages->fetch_assoc();
                            echo $row_count_pages['count'] ?? 0;
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
                <?php if(isset($conn) && check_permission('read_core_menu_structure')): ?>
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
                            $sql_menu_overview = "SELECT m.menu_name, COUNT(s.submenu_id) as submenu_count
                                    FROM menu m
                                    LEFT JOIN submenu s ON m.menu_id = s.menu_id
                                    GROUP BY m.menu_id, m.menu_name ORDER BY m.menu_order";
                            $result_menu_overview = $conn->query($sql_menu_overview);
                            if($result_menu_overview){
                                while ($row_menu_overview = $result_menu_overview->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row_menu_overview['menu_name']) . "</td>";
                                    echo "<td>" . $row_menu_overview['submenu_count'] . "</td>";
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