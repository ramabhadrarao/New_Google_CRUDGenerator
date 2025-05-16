<header class="navbar navbar-expand-md d-print-none">
    <div class="container-xl">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu" aria-controls="navbar-menu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <h1 class="navbar-brand navbar-brand-autodark d-none-navbar-horizontal pe-0 pe-md-3">
            <a href="../pages/dashboard.php">
                <?php echo htmlspecialchars($appName ?? 'App'); ?>
            </a>
        </h1>
        <div class="navbar-nav flex-row order-md-last">
            <div class="nav-item dropdown">
                <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown" aria-label="Open user menu">
                    <span class="avatar avatar-sm" style="background-image: url(../images/user-icon.gif)"></span>
                    <div class="d-none d-xl-block ps-2">
                        <div><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></div>
                        <div class="mt-1 small text-muted"><?php // echo htmlspecialchars($_SESSION['role_name'] ?? 'Role'); // You'd need to fetch role name ?></div>
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