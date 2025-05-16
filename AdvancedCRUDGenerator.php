<?php
// Summary of CRUD Generator Enhancements
// I've provided upgraded versions of all the core files needed for the enhanced Advanced CRUD Generator. Here's a summary of the improvements made to each component:
// 1. Core Generator Class (AdvancedCRUDGenerator.php)

// Added comprehensive PHPDoc documentation
// Introduced configuration options for customization
// Enhanced field type detection
// Added support for additional form field types
// Improved validation system

// 2. HTML Generator Helper (HtmlGeneratorHelper.php)

// Enhanced form card with better accessibility
// Added support for rich text editors
// Improved file upload previews
// Added batch operations interface
// Added import/export functionality
// Better mobile responsiveness

// 3. JavaScript Generator Helper (JsGeneratorHelper.php)

// Added option for both jQuery and vanilla JS
// Enhanced form validation
// Improved foreign key search
// Added batch operations support
// Better error handling and user feedback
// Enhanced file preview functionality

// 4. Actions Generator Helper (ActionsGeneratorHelper.php)

// Transaction-based database operations
// Enhanced error handling
// Better file upload security
// More robust validation
// Added batch operations support
// Enhanced import/export functionality

// 5. SQL Generator Helper (SqlGeneratorHelper.php)

// Added robust index generation
// Support for sample data generation
// Better constraint handling
// Table audit columns
// Complete DDL generation

// 6. API Generator Helper (ApiGeneratorHelper.php)

// RESTful API endpoint support
// Proper HTTP status codes
// Authentication and authorization
// Comprehensive error handling
// Robust data validation

// 7. Runner Script (run_advanced_crud_generator.php)

// Enhanced configuration options
// CLI support with interactive options
// Better menu and permission setup
// More detailed output

// Additional Suggested Files
// To fully leverage the enhanced generator, you might also want to create these support files:

// includes/api_helper.php - Functions for API authentication and responses
// includes/upload_helper.php - File upload handling and validation
// includes/validation.php - Server-side validation functions
// includes/utilities.php - Common utilities for CRUD operations
// AdvancedCRUDGenerator.php - Enhanced Version

// Include helper traits/classes
require_once __DIR__ . '/generator_helpers/HtmlGeneratorHelper.php';
require_once __DIR__ . '/generator_helpers/JsGeneratorHelper.php';
require_once __DIR__ . '/generator_helpers/ActionsGeneratorHelper.php';
require_once __DIR__ . '/generator_helpers/SqlGeneratorHelper.php';
require_once __DIR__ . '/generator_helpers/ApiGeneratorHelper.php';

/**
 * Advanced CRUD Generator for Tabler UI
 * 
 * Creates fully-featured CRUD interfaces with:
 * - Responsive Tabler UI integration
 * - Form generation with validation
 * - File uploads
 * - Foreign key relationships
 * - API endpoints
 * - Import/export functionality
 * - Permission-based access control
 * 
 * @version 2.0.0
 */
class AdvancedCRUDGenerator {
    // Use traits to include methods from helper files
    use HtmlGeneratorHelper;
    use JsGeneratorHelper;
    use ActionsGeneratorHelper;
    use SqlGeneratorHelper;
    use ApiGeneratorHelper;

    /** @var string Table name in database */
    private string $tableName;
    
    /** @var string Primary key column name */
    private string $primaryKey;
    
    /** @var array Array of column names ['id', 'name', 'email'] */
    private array $columns;
    
    /** @var array Detailed column definitions with types, attributes, etc. */
    private array $columnDefinitions;
    
    /** @var array Foreign key relationships */
    private array $foreignKeys;
    
    /** @var array Unique key constraints */
    private array $uniqueKeys;
    
    /** @var ?mysqli Database connection if needed */
    private ?mysqli $conn;
    
    /** @var string Base directory of the project */
    private string $projectBaseDir;
    
    /** @var array Additional configuration options */
    private array $config;

    /**
     * Constructor for the CRUD Generator
     * 
     * @param string $tableName Table name in database
     * @param array $columns Simple list of column names
     * @param array $columnDefinitions Detailed definitions for each column
     * @param array $foreignKeys Foreign key relationships
     * @param array $uniqueKeys Unique key constraints
     * @param string $projectBaseDir Path to project root
     * @param ?mysqli $dbConnection Database connection
     * @param array $config Additional configuration options
     */
    public function __construct(
        string $tableName,
        array $columns,
        array $columnDefinitions,
        array $foreignKeys = [],
        array $uniqueKeys = [],
        string $projectBaseDir = '../',
        ?mysqli $dbConnection = null,
        array $config = []
    ) {
        $this->tableName = $tableName;
        $this->columns = $columns;
        $this->columnDefinitions = $columnDefinitions;

        // Apply default configuration options
        $this->config = array_merge([
            'enableApi' => true,              // Generate REST API endpoints
            'itemsPerPage' => 10,             // Default items per page
            'enableBatchOperations' => true,  // Enable batch delete/update
            'dateFormat' => 'Y-m-d',          // PHP date format
            'timeFormat' => 'H:i:s',          // PHP time format
            'dateTimeFormat' => 'Y-m-d H:i:s', // PHP datetime format
            'uploadMaxSize' => 2048,          // Max file upload size in KB
            'allowedFileTypes' => 'jpg,jpeg,png,pdf,doc,docx,xls,xlsx,zip', // Allowed file types
            'generateVanillaJs' => false,     // Use vanilla JS instead of jQuery
            'jsFramework' => 'jquery',        // 'jquery', 'alpine', 'react', 'vue'
            'enableColumnSorting' => true,    // Allow sorting by columns
            'searchableFields' => [],         // Specific fields to search, empty means all
            'cacheBreaker' => 'v=' . time(),  // Cache breaker for assets
            'enableExports' => ['csv', 'excel', 'pdf'], // Export formats
        ], $config);

        // Determine Primary Key: either the first in $columns or from $columnDefinitions if marked
        $this->primaryKey = $columns[0]; // Fallback
        foreach ($columnDefinitions as $colName => $def) {
            if (isset($def['attributes']) && strpos(strtoupper($def['attributes']), 'PRIMARY KEY') !== false) {
                $this->primaryKey = $colName;
                break;
            }
        }

        $this->foreignKeys = $foreignKeys;
        $this->uniqueKeys = $uniqueKeys;
        $this->projectBaseDir = rtrim($projectBaseDir, DIRECTORY_SEPARATOR);
        $this->conn = $dbConnection;
    }

    /**
     * Main method to generate all necessary files for the CRUD module.
     * 
     * @param bool $verbose Whether to output progress messages
     * @return array Paths to the generated files.
     */
    public function generateAll(bool $verbose = true): array {
        $generatedFiles = [];

        // Ensure target directories exist
        $this->ensureDirectoryExists($this->projectBaseDir . "/pages");
        $this->ensureDirectoryExists($this->projectBaseDir . "/js");
        $this->ensureDirectoryExists($this->projectBaseDir . "/actions");
        if ($this->config['enableApi']) {
            $this->ensureDirectoryExists($this->projectBaseDir . "/api");
        }

        if ($verbose) {
            echo "Generating files for table: {$this->tableName}\n";
        }

        $generatedFiles['manage_php'] = $this->generateManagePHPFile();
        $generatedFiles['manage_js'] = $this->generateManageJSFile();
        $generatedFiles['actions_php'] = $this->generateActionsPHPFile();
        
        if ($this->config['enableApi']) {
            $generatedFiles['api_php'] = $this->generateRestApiFile();
        }

        return $generatedFiles;
    }

    /**
     * Ensure directory exists and is writable
     * 
     * @param string $path Directory path
     * @throws Exception If directory cannot be created
     * @return void
     */
    private function ensureDirectoryExists(string $path): void {
        if (!is_dir($path)) {
            if (mkdir($path, 0755, true)) {
                echo "Created directory: $path\n";
            } else {
                throw new Exception("Failed to create directory: $path");
            }
        } else if (!is_writable($path)) {
            throw new Exception("Directory exists but is not writable: $path");
        }
    }

    /**
     * Get column definition by name and optional key
     * 
     * @param string $columnName Column name
     * @param string|null $key Optional key to retrieve specific property
     * @param mixed $default Default value if key doesn't exist
     * @return mixed Column definition or specific property
     */
    private function getColumnDef(string $columnName, string $key = null, $default = null) {
        if ($key === null) {
            return $this->columnDefinitions[$columnName] ?? [];
        }
        return $this->columnDefinitions[$columnName][$key] ?? $default;
    }

    /**
     * Get user-friendly column label
     * 
     * @param string $columnName Column name
     * @return string User-friendly label
     */
    private function getColumnLabel(string $columnName): string {
        $label = $this->getColumnDef($columnName, 'label', ucfirst(str_replace('_', ' ', $columnName)));
        return htmlspecialchars($label);
    }

    /**
     * Get appropriate form field type based on column definition
     * 
     * @param string $columnName Column name
     * @return string Form field type (text, select, date, etc.)
     */
    private function getColumnFormType(string $columnName): string {
        $def = $this->getColumnDef($columnName);
        if (isset($def['form_type'])) {
            return $def['form_type'];
        }
        
        // Infer from DB type if not specified
        $dbType = strtoupper($def['type'] ?? 'VARCHAR');
        
        // First check for foreign keys
        if (array_key_exists($columnName, $this->foreignKeys)) {
            return 'select';
        }
        
        // Check for column name hints
        $colNameLower = strtolower($columnName);
        if (strpos($colNameLower, 'email') !== false) {
            return 'email';
        }
        if (strpos($colNameLower, 'password') !== false) {
            return 'password';
        }
        if (strpos($colNameLower, 'color') !== false) {
            return 'color';
        }
        if (strpos($colNameLower, 'url') !== false || strpos($colNameLower, 'website') !== false) {
            return 'url';
        }
        if (strpos($colNameLower, 'phone') !== false || strpos($colNameLower, 'mobile') !== false || strpos($colNameLower, 'tel') !== false) {
            return 'tel';
        }
        if (strpos($colNameLower, 'image') !== false || strpos($colNameLower, 'photo') !== false || strpos($colNameLower, 'picture') !== false) {
            return 'file';
        }
        
        // Check for DB types
        if (strpos($dbType, 'DATE') !== false && strpos($dbType, 'DATETIME') === false) {
            return 'date';
        }
        if (strpos($dbType, 'DATETIME') !== false || strpos($dbType, 'TIMESTAMP') !== false) {
            return 'datetime-local';
        }
        if (strpos($dbType, 'TIME') !== false && strpos($dbType, 'TIMESTAMP') === false) {
            return 'time';
        }
        if (strpos($dbType, 'TEXT') !== false) {
            return 'textarea';
        }
        if (strpos($dbType, 'ENUM') !== false || strpos($dbType, 'SET') !== false) {
            return 'select';
        }
        if (strpos($dbType, 'TINYINT(1)') !== false || strpos($dbType, 'BOOLEAN') !== false) {
            return 'boolean_select';
        }
        if (strpos($dbType, 'INT') !== false || strpos($dbType, 'DECIMAL') !== false || strpos($dbType, 'FLOAT') !== false || strpos($dbType, 'DOUBLE') !== false) {
            return 'number';
        }
        if (isset($def['is_upload']) && $def['is_upload']) {
            return 'file';
        }
        
        // Default to text input
        return 'text';
    }
    
    /**
     * Check if column can be null
     * 
     * @param string $columnName Column name
     * @return bool True if nullable
     */
    private function isColumnNullable(string $columnName): bool {
        $def = $this->getColumnDef($columnName);
        
        // Primary key is never nullable by definition for AUTO_INCREMENT
        if ($columnName === $this->primaryKey && strpos(strtoupper($def['attributes'] ?? ''), 'AUTO_INCREMENT') !== false) {
            return false;
        }
        
        // Check for NOT NULL attribute
        if (isset($def['attributes']) && strpos(strtoupper($def['attributes']), 'NOT NULL') !== false) {
            return false;
        }
        
        // Check explicit nullable setting
        return $def['nullable'] ?? true;
    }

    /**
     * Get default value for column
     * 
     * @param string $columnName Column name
     * @return mixed Default value
     */
    private function getColumnDefaultValue(string $columnName) {
        return $this->getColumnDef($columnName, 'default');
    }

    /**
     * Format default value for SQL query
     * 
     * @param mixed $default Default value
     * @param string $colDBType DB column type
     * @return string Quoted default value for SQL
     */
    private function quoteDefaultForSQL($default, string $colDBType): string {
        $colDBType = strtoupper($colDBType);
        
        // Handle NULL
        if (is_null($default) && $this->isColumnNullable($this->getColumnDef($default, 'name', $default))) {
            return 'NULL';
        }
        
        // Handle booleans
        if (is_bool($default)) {
            return $default ? '1' : '0';
        }

        // Check for numeric types
        $numericTypes = ['INT', 'TINYINT', 'SMALLINT', 'MEDIUMINT', 'BIGINT', 'DECIMAL', 'FLOAT', 'DOUBLE', 'REAL'];
        foreach ($numericTypes as $numType) {
            if (strpos($colDBType, $numType) !== false) {
                return is_numeric($default) ? (string)$default : '0';
            }
        }
        
        // Handle CURRENT_TIMESTAMP for timestamp/datetime fields
        if (strtoupper((string)$default) === 'CURRENT_TIMESTAMP' && 
            (strpos($colDBType, 'TIMESTAMP') !== false || strpos($colDBType, 'DATETIME') !== false)) {
            return 'CURRENT_TIMESTAMP';
        }
        
        // Default to quoted string
        return "'" . addslashes((string)$default) . "'";
    }

    /**
     * Get upload path for file fields
     * 
     * @param string $columnName Column name
     * @return string Upload path
     */
    private function getUploadPath(string $columnName): string {
        $path = $this->getColumnDef($columnName, 'upload_path', "uploads/{$this->tableName}/");
        
        // Ensure trailing slash
        if (substr($path, -1) !== '/') {
            $path .= '/';
        }
        
        return $path;
    }

    /**
     * Get list of options for select fields
     * 
     * @param string $columnName Column name
     * @return array Options as key-value pairs
     */
    private function getColumnOptions(string $columnName): array {
        $options = $this->getColumnDef($columnName, 'options', []);
        
        // If no options defined but it's an ENUM, try to parse from DB type
        if (empty($options)) {
            $dbType = strtoupper($this->getColumnDef($columnName, 'type', ''));
            if (strpos($dbType, 'ENUM') !== false || strpos($dbType, 'SET') !== false) {
                preg_match('/\((.*)\)/', $dbType, $matches);
                if (isset($matches[1])) {
                    $values = explode(',', str_replace("'", '', $matches[1]));
                    foreach ($values as $value) {
                        $value = trim($value);
                        $options[$value] = ucfirst($value);
                    }
                }
            }
        }
        
        return $options;
    }

    /**
     * Get validation rules for a column
     * 
     * @param string $columnName Column name
     * @return array Validation rules
     */
    private function getValidationRules(string $columnName): array {
        $rules = $this->getColumnDef($columnName, 'validation', []);
        
        // Add required rule if not nullable
        if (!$this->isColumnNullable($columnName) && !in_array('required', $rules)) {
            array_unshift($rules, 'required');
        }
        
        return $rules;
    }
    
    /**
     * Get JavaScript validation rules for a column
     * 
     * @param string $columnName Column name
     * @return string JavaScript validation code
     */
    private function getJsValidation(string $columnName): string {
        $rules = $this->getValidationRules($columnName);
        $jsValidation = '';
        
        if (empty($rules)) {
            return $jsValidation;
        }
        
        $jsValidation .= "// Validation for {$columnName}\n";
        
        foreach ($rules as $rule) {
            if ($rule === 'required') {
                $jsValidation .= "if (!\$(this).val()) { isValid = false; }\n";
            } else if (strpos($rule, 'min:') === 0) {
                $min = substr($rule, 4);
                $jsValidation .= "if (\$(this).val() && \$(this).val().length < {$min}) { isValid = false; }\n";
            } else if (strpos($rule, 'max:') === 0) {
                $max = substr($rule, 4);
                $jsValidation .= "if (\$(this).val() && \$(this).val().length > {$max}) { isValid = false; }\n";
            } else if ($rule === 'email') {
                $jsValidation .= "if (\$(this).val() && !/^[^\\s@]+@[^\\s@]+\\.[^\\s@]+\$/.test(\$(this).val())) { isValid = false; }\n";
            }
        }
        
        return $jsValidation;
    }
}
?>