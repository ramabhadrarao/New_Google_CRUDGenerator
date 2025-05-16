<?php
// run_advanced_crud_generator.php - Enhanced Version

/**
 * Advanced CRUD Generator Runner
 * 
 * A comprehensive tool for generating fully-featured CRUD interfaces 
 * with Tabler UI integration, including:
 * - Responsive dashboard pages
 * - AJAX-powered data handling
 * - File uploads and validation
 * - Import/export functionality
 * - REST API endpoints
 * - Permission-based access control
 * 
 * @version 2.0.0
 */
// Add at the top of run_advanced_crud_generator.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "Starting CRUD Generator Script...\n";

// Check if required files exist
if (!file_exists(__DIR__ . '/AdvancedCRUDGenerator.php')) {
    echo "ERROR: AdvancedCRUDGenerator.php not found!\n";
    exit(1);
}

// Required system files
echo "Loading required files...\n";
require_once __DIR__ . '/AdvancedCRUDGenerator.php';

// Test database connection
echo "Testing database connection...\n";
require_once __DIR__ . '/../includes/dbconfig.php';
if (isset($conn)) {
    echo "✓ Database connection established\n";
} else {
    echo "ERROR: Database connection failed!\n";
    exit(1);
}
/**
 * Generates a unique key for pages.
 * 
 * @param int $length Desired length of the hex string.
 * @return string Unique key
 * @throws InvalidArgumentException If length is not an even number
 */
function generate_page_unique_key(int $length = 16): string {
    if ($length % 2 !== 0) {
        throw new InvalidArgumentException("Length must be an even number for hex representation.");
    }
    
    try {
        return bin2hex(random_bytes($length / 2));
    } catch (Exception $e) {
        // Fallback for environments where random_bytes might fail (less secure)
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        return substr(md5($randomString . uniqid('', true)), 0, $length);
    }
}

// =====================================================================
// === Configuration for the CRUD generation ===
// =====================================================================

// Table name and label
$tableName = 'departments';
$tableLabel = 'Departments';

// Columns (as per your SQL schema)
$columns = [
    'id',                // Primary Key
    'name',
    'code',
    'college_id',        // Foreign Key
    'hod_id',            // Foreign Key (nullable, to faculty table)
    'logo',              // File upload
    'description',
    'email',
    'phone',
    'established_date',
    'status',
    'created_at',        // Timestamps
    'updated_at',
    'created_by',        // User tracking
    'updated_by'
];

// Detailed Column Definitions
$columnDefinitions = [
    'id' => [
        'label' => 'ID',
        'type' => 'INT',
        'attributes' => 'PRIMARY KEY AUTO_INCREMENT',
        'nullable' => false,
        'form_type' => 'hidden_pk'
    ],
    'name' => [
        'label' => 'Department Name',
        'type' => 'VARCHAR(255)',
        'nullable' => false,
        'form_type' => 'text',
        'validation' => ['required', 'min:3', 'max:255'],
        'help_text' => 'Full name of the department'
    ],
    'code' => [
        'label' => 'Department Code',
        'type' => 'VARCHAR(50)',
        'attributes' => 'UNIQUE',
        'nullable' => false,
        'form_type' => 'text',
        'validation' => ['required', 'max:50'],
        'help_text' => 'Unique code identifier for the department'
    ],
    'college_id' => [
        'label' => 'College',
        'type' => 'INT',
        'nullable' => false,
        'form_type' => 'select',
        'validation' => ['required'],
        'help_text' => 'The college this department belongs to'
    ],
    'hod_id' => [
        'label' => 'Head of Department (HOD)',
        'type' => 'INT',
        'nullable' => true,
        'form_type' => 'select',
        'help_text' => 'The faculty member who leads this department'
    ],
    'logo' => [
        'label' => 'Department Logo',
        'type' => 'VARCHAR(255)',
        'nullable' => true,
        'form_type' => 'image',
        'is_upload' => true,
        'upload_path' => "uploads/departments/logos/",
        'validation' => ['mimes:jpeg,png,gif', 'max_size:2048'],
        'help_text' => 'Department logo image (max 2MB, JPG/PNG/GIF)'
    ],
    'description' => [
        'label' => 'Description',
        'type' => 'TEXT',
        'nullable' => true,
        'form_type' => 'wysiwyg',
        'help_text' => 'Detailed description of the department'
    ],
    'email' => [
        'label' => 'Department Email',
        'type' => 'VARCHAR(100)',
        'nullable' => true,
        'form_type' => 'email',
        'validation' => ['email', 'max:100'],
        'help_text' => 'Official contact email for the department'
    ],
    'phone' => [
        'label' => 'Department Phone',
        'type' => 'VARCHAR(20)',
        'nullable' => true,
        'form_type' => 'tel',
        'validation' => ['max:20'],
        'help_text' => 'Official contact phone number'
    ],
    'established_date' => [
        'label' => 'Established Date',
        'type' => 'DATE',
        'nullable' => true,
        'form_type' => 'date',
        'help_text' => 'Date when the department was established'
    ],
    'status' => [
        'label' => 'Status',
        'type' => "VARCHAR(20) DEFAULT 'active'",
        'nullable' => false,
        'form_type' => 'select',
        'options' => ['active' => 'Active', 'inactive' => 'Inactive', 'pending' => 'Pending Approval'],
        'default' => 'active',
        'help_text' => 'Current operational status of the department'
    ],
    'created_at' => [
        'label' => 'Created At',
        'type' => 'DATETIME',
        'attributes' => 'DEFAULT CURRENT_TIMESTAMP',
        'nullable' => false,
        'form_type' => 'hidden'
    ],
    'updated_at' => [
        'label' => 'Updated At',
        'type' => 'DATETIME',
        'attributes' => 'DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        'nullable' => false,
        'form_type' => 'hidden'
    ],
    'created_by' => [
        'label' => 'Created By',
        'type' => 'INT',
        'nullable' => true,
        'form_type' => 'hidden'
    ],
    'updated_by' => [
        'label' => 'Updated By',
        'type' => 'INT',
        'nullable' => true,
        'form_type' => 'hidden'
    ]
];

// Foreign Key Definitions
$foreignKeys = [
    'college_id' => [
        'table' => 'colleges',
        'key'   => 'id',
        'field' => 'name',
        'on_delete' => 'CASCADE',
        'on_update' => 'CASCADE'
    ],
    'hod_id' => [
        'table' => 'faculty',
        'key'   => 'id',
        'field' => 'full_name',
        'on_delete' => 'SET NULL',
        'on_update' => 'CASCADE'
    ],
    'created_by' => [
        'table' => 'users',
        'key'   => 'id',
        'field' => 'username',
        'on_delete' => 'SET NULL',
        'on_update' => 'CASCADE'
    ],
    'updated_by' => [
        'table' => 'users',
        'key'   => 'id',
        'field' => 'username',
        'on_delete' => 'SET NULL',
        'on_update' => 'CASCADE'
    ]
];

// Unique Key Definitions
$uniqueKeys = [
    ['code'], // Department code must be unique
];

// Additional CRUD Configuration
$crudConfig = [
    'enableApi' => true,              // Generate REST API endpoints
    'itemsPerPage' => 15,             // Default items per page
    'enableBatchOperations' => true,  // Enable batch delete/update
    'dateFormat' => 'Y-m-d',          // PHP date format
    'timeFormat' => 'H:i:s',          // PHP time format
    'dateTimeFormat' => 'Y-m-d H:i:s', // PHP datetime format
    'uploadMaxSize' => 2048,          // Max file upload size in KB
    'allowedFileTypes' => 'jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,zip', // Allowed file types
    'generateVanillaJs' => false,     // Use vanilla JS instead of jQuery
    'jsFramework' => 'jquery',        // 'jquery', 'alpine', 'react', 'vue'
    'enableColumnSorting' => true,    // Allow sorting by columns
    'searchableFields' => ['name', 'code', 'email'], // Specific fields to search
    'cacheBreaker' => 'v=' . time(),  // Cache breaker for assets
    'enableExports' => ['csv', 'excel', 'pdf'] // Export formats
];

// Menu, Page, and Permission Configuration
$targetMenuName = 'Academic Setup';   // Main menu to add this CRUD link to (must exist or be creatable)
$submenuItemName = $tableLabel;       // Menu label
$pageUniqueKey = generate_page_unique_key(16); // Unique key for the page
$pageFilePath = "manage_{$tableName}.php";
$basePermissionName = "manage_{$tableName}";

// Admin Group ID (assuming it's 1 from your create_tables.php)
$adminGroupId = 1;
// Other Group ID(s) to grant read permission (e.g., 'User Group' might be ID 2)
$readAccessGroupIds = [2, 3]; // Adjust based on your actual group IDs

// =====================================================================
// === Run the Generator ===
// =====================================================================

echo "Starting CRUD generation for table: {$tableName}\n";

try {
    // Ensure DB connection is available
    if (!$conn) {
        throw new Exception("Database connection is not available. Check includes/dbconfig.php.");
    }

    $projectBaseDir = realpath(__DIR__ . '/..'); // Assuming this script is in YourProjectName/generator/
    if (!$projectBaseDir) {
        throw new Exception("Could not determine project base directory. Ensure this script is in a 'generator' subdirectory of your project.");
    }

    // Instantiate the generator
    $generator = new AdvancedCRUDGenerator(
        $tableName,
        $columns,
        $columnDefinitions,
        $foreignKeys,
        $uniqueKeys,
        $projectBaseDir,
        $conn,
        $crudConfig
    );

    // A. Generate SQL for table creation if needed
    if (isset($_GET['create_table']) || (PHP_SAPI === 'cli' && in_array('--create-table', $_SERVER['argv']))) {
        $createTableSql = $generator->generateCreateTableSQL();
        echo "--------------------------------SQL to Create Table--------------------------------\n";
        echo $createTableSql;
        echo "-----------------------------------------------------------------------------------\n";
        
        // Ask for confirmation
        if (PHP_SAPI === 'cli') {
            echo "Execute this SQL? (y/n): ";
            $confirm = trim(fgets(STDIN));
            
            if (strtolower($confirm) === 'y') {
                if ($conn->multi_query($createTableSql)) {
                    echo "Table '{$tableName}' SQL executed successfully.\n";
                    while ($conn->next_result()) {;} // flush multi_queries
                } else {
                    throw new Exception("Error executing CREATE TABLE SQL for '{$tableName}': " . $conn->error);
                }
            }
        }
    }

    // B. Generate PHP, JS, Actions, and API files
    $generatedFiles = $generator->generateAll();
    echo "CRUD files generated:\n";
    foreach ($generatedFiles as $type => $path) {
        if ($path) echo "- {$type}: {$path}\n";
    }

    // C. Setup Menu, Page, and Permissions (Database Operations)
    echo "\nSetting up menu, page, and permissions...\n";
    $conn->begin_transaction();

    // 1. Ensure Main Menu exists or create it
    $menuId = null;
    $stmt = $conn->prepare("SELECT menu_id FROM menu WHERE menu_name = ?");
    $stmt->bind_param("s", $targetMenuName);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $menuId = $row['menu_id'];
    } else {
        $stmt_insert_menu = $conn->prepare("INSERT INTO menu (menu_name, menu_order, menu_icon) VALUES (?, 10, ?)"); // Default order and icon
        $defaultMenuIcon = '<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-building-community" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M8 9l5 -5l5 5" /><path d="M8 9l0 12" /><path d="M13 4l0 17" /><path d="M18 9l0 12" /><path d="M8 17l10 0" /></svg>';
        $stmt_insert_menu->bind_param("ss", $targetMenuName, $defaultMenuIcon);
        
        if(!$stmt_insert_menu->execute()) {
            throw new Exception("Failed to create main menu '{$targetMenuName}': " . $stmt_insert_menu->error);
        }
        
        $menuId = $stmt_insert_menu->insert_id;
        echo "Created main menu: '{$targetMenuName}' (ID: {$menuId})\n";
        $stmt_insert_menu->close();
    }
    $stmt->close();

    // 2. Create or update Page entry
    $pageTitleForDB = $submenuItemName;
    $permissionNameForPageRead = "read_{$basePermissionName}";

    $stmt_page = $conn->prepare("INSERT INTO pages (page_unique_key, page_title, file_path, permission_required, is_crud_page, is_active) 
                                VALUES (?, ?, ?, ?, 1, 1) 
                                ON DUPLICATE KEY UPDATE 
                                    page_title=VALUES(page_title), 
                                    file_path=VALUES(file_path), 
                                    permission_required=VALUES(permission_required), 
                                    is_active=1");
    
    $stmt_page->bind_param("ssss", $pageUniqueKey, $pageTitleForDB, $pageFilePath, $permissionNameForPageRead);
    
    if(!$stmt_page->execute()) {
        throw new Exception("Failed to create/update page entry '{$pageUniqueKey}': " . $stmt_page->error);
    }
    
    $pageId = $stmt_page->insert_id;
    if ($pageId === 0 && $stmt_page->affected_rows > 0) {
        // It was an update, need to get existing ID
        $stmt_get_id = $conn->prepare("SELECT page_id FROM pages WHERE page_unique_key = ?");
        $stmt_get_id->bind_param("s", $pageUniqueKey);
        $stmt_get_id->execute();
        $res_get_id = $stmt_get_id->get_result();
        
        if($row_get_id = $res_get_id->fetch_assoc()) {
            $pageId = $row_get_id['page_id'];
        }
        
        $stmt_get_id->close();
    }
    
    echo "Page entry '{$pageUniqueKey}' (ID: {$pageId}) created/updated for file '{$pageFilePath}'.\n";
    $stmt_page->close();

    // 3. Create or update Submenu entry
    if ($menuId && $pageId) {
        $stmt_submenu = $conn->prepare("INSERT INTO submenu (menu_id, page_id, submenu_name, submenu_order) 
                                      VALUES (?, ?, ?, 10) 
                                      ON DUPLICATE KEY UPDATE 
                                          submenu_name=VALUES(submenu_name), 
                                          submenu_order=VALUES(submenu_order)"); // Default order
        
        $stmt_submenu->bind_param("iis", $menuId, $pageId, $submenuItemName);
        
        if(!$stmt_submenu->execute()) {
            throw new Exception("Failed to create/update submenu entry '{$submenuItemName}': " . $stmt_submenu->error);
        }
        
        echo "Submenu entry '{$submenuItemName}' created/updated under menu ID '{$menuId}'.\n";
        $stmt_submenu->close();
    } else {
        echo "Warning: Could not create submenu entry due to missing menuId or pageId.\n";
    }

    // 4. Create or update Permissions
    $crudActions = ['create', 'read', 'update', 'delete', 'import', 'export'];
    $permissionIdsToAssign = [];

    foreach ($crudActions as $action) {
        $permissionFullName = "{$action}_{$basePermissionName}";
        $permissionDesc = ucfirst($action) . " " . strtolower($tableLabel);
        
        $stmt_perm = $conn->prepare("INSERT INTO permissions (permission_name, permission_description) 
                                    VALUES (?, ?) 
                                    ON DUPLICATE KEY UPDATE 
                                        permission_description=VALUES(permission_description)");
                                        
        $stmt_perm->bind_param("ss", $permissionFullName, $permissionDesc);
        
        if(!$stmt_perm->execute()) {
            throw new Exception("Failed to create/update permission '{$permissionFullName}': " . $stmt_perm->error);
        }

        $permId = $stmt_perm->insert_id;
        if ($permId === 0 && $stmt_perm->affected_rows >= 0) {
            // It was an update or no change, get existing ID
            $stmt_get_perm_id = $conn->prepare("SELECT permission_id FROM permissions WHERE permission_name = ?");
            $stmt_get_perm_id->bind_param("s", $permissionFullName);
            $stmt_get_perm_id->execute();
            $res_get_perm_id = $stmt_get_perm_id->get_result();
            
            if($row_get_perm_id = $res_get_perm_id->fetch_assoc()) {
                $permId = $row_get_perm_id['permission_id'];
            }
            
            $stmt_get_perm_id->close();
        }
        
        if($permId) {
            $permissionIdsToAssign[$action] = $permId;
        }
        
        $stmt_perm->close();
    }
    
    echo "Permissions for '{$basePermissionName}' created/updated.\n";

    // 5. Assign All Permissions to Admin Group
    if ($adminGroupId && !empty($permissionIdsToAssign)) {
        $stmt_admin_assign = $conn->prepare("INSERT IGNORE INTO permission_group_permissions (group_id, permission_id) VALUES (?, ?)");
        
        foreach ($permissionIdsToAssign as $action => $permId) {
            $stmt_admin_assign->bind_param("ii", $adminGroupId, $permId);
            
            if(!$stmt_admin_assign->execute()) {
                error_log("Failed to assign perm ID {$permId} to admin group: " . $stmt_admin_assign->error);
            }
        }
        
        $stmt_admin_assign->close();
        echo "All CRUD permissions for '{$basePermissionName}' assigned to Admin Group (ID: {$adminGroupId}).\n";
    }

    // 6. Assign Read Permission to Other Groups
    if (isset($permissionIdsToAssign['read']) && !empty($readAccessGroupIds)) {
        $readPermId = $permissionIdsToAssign['read'];
        $stmt_read_assign = $conn->prepare("INSERT IGNORE INTO permission_group_permissions (group_id, permission_id) VALUES (?, ?)");
        
        foreach ($readAccessGroupIds as $groupId) {
            $stmt_read_assign->bind_param("ii", $groupId, $readPermId);
            
            if(!$stmt_read_assign->execute()) {
                error_log("Failed to assign read perm to group ID {$groupId}: " . $stmt_read_assign->error);
            }
        }
        
        $stmt_read_assign->close();
        echo "Read permission for '{$basePermissionName}' assigned to other specified groups.\n";
    }

    // 7. Commit all database changes
    $conn->commit();
    
    echo "\nCRUD generation and setup for '{$tableName}' completed successfully!\n";
    echo "Access it via page key: {$pageUniqueKey} (e.g., dashboard.php?page_key={$pageUniqueKey})\n";

} catch (Exception $e) {
    if (isset($conn) && $conn->connect_errno === 0 && method_exists($conn, 'in_transaction') && $conn->in_transaction()) {
        $conn->rollback();
    }
    
    echo "\nERROR: " . $e->getMessage() . "\n";
    
    // For debugging, show the trace in CLI mode
    if (PHP_SAPI === 'cli') {
        echo "Trace: " . $e->getTraceAsString() . "\n";
    }
} finally {
    // Close connection if this script is standalone
    if (PHP_SAPI === 'cli' && isset($conn)) {
        $conn->close();
    }
}
?>