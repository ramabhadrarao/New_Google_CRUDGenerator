<?php
// generator_helpers/SqlGeneratorHelper.php - Enhanced Version

/**
 * SQL Generator Helper Trait
 * 
 * Provides methods for generating SQL statements for database operations
 * such as creating tables, adding foreign keys, etc.
 */
trait SqlGeneratorHelper {
    /**
     * Generate SQL statement to create the table
     * 
     * @return string The SQL CREATE TABLE statement
     */
    public function generateCreateTableSQL(): string {
        $sql = "-- SQL for creating {$this->tableName} table\n";
        $sql .= "CREATE TABLE IF NOT EXISTS `{$this->tableName}` (\n";
        $columnSQLs = [];
        $primaryKeyColumn = null;

        // Define primary key first if specified in attributes
        foreach ($this->columns as $columnName) {
            $def = $this->getColumnDef($columnName);
            if (isset($def['attributes']) && strpos(strtoupper($def['attributes']), 'PRIMARY KEY') !== false) {
                $primaryKeyColumn = $columnName;
                $colType = $def['type'] ?? 'INT';
                $attributes = $def['attributes'];
                $columnSQLs[] = "    `{$columnName}` {$colType} {$attributes}"; // Nullability handled by PK
                break; // Assuming only one PK definition
            }
        }
        
        // If PK was not found via attributes, use the default $this->primaryKey
        if (!$primaryKeyColumn && in_array($this->primaryKey, $this->columns)) {
            $def = $this->getColumnDef($this->primaryKey);
            $colType = $def['type'] ?? 'INT';
            $attributes = $def['attributes'] ?? 'PRIMARY KEY AUTO_INCREMENT'; // Default PK attributes
            if (strpos(strtoupper($attributes), 'PRIMARY KEY') === false) {
                $attributes = 'PRIMARY KEY ' . $attributes;
            }
            $columnSQLs[] = "    `{$this->primaryKey}` {$colType} {$attributes}";
            $primaryKeyColumn = $this->primaryKey;
        }

        // Process the rest of the columns
        foreach ($this->columns as $columnName) {
            if ($columnName === $primaryKeyColumn) continue; // Skip if already processed as PK

            $def = $this->getColumnDef($columnName);
            $colType = $def['type'] ?? 'VARCHAR(255)';
            $attributes = $def['attributes'] ?? '';
            $isNullable = $this->isColumnNullable($columnName);
            $defaultValue = $this->getColumnDefaultValue($columnName);
            $defaultClause = '';

            if ($defaultValue !== null) {
                $defaultClause = " DEFAULT " . $this->quoteDefaultForSQL($defaultValue, $colType);
            } elseif ($isNullable) {
                $defaultClause = " DEFAULT NULL";
            }

            $columnSQLs[] = "    `{$columnName}` {$colType}" .
                          (!$isNullable ? " NOT NULL" : "") .
                          (!empty($attributes) ? " {$attributes}" : "") .
                          $defaultClause;
        }

        // Add default timestamp columns if not already defined by user
        if (!in_array('created_at', $this->columns) && !isset($this->columnDefinitions['created_at'])) {
            $columnSQLs[] = "    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP";
        }
        if (!in_array('updated_at', $this->columns) && !isset($this->columnDefinitions['updated_at'])) {
            $columnSQLs[] = "    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
        }
        
        // Add user tracking columns if not already defined
        if (!in_array('created_by', $this->columns) && !isset($this->columnDefinitions['created_by'])) {
            $columnSQLs[] = "    `created_by` INT NULL";
        }
        if (!in_array('updated_by', $this->columns) && !isset($this->columnDefinitions['updated_by'])) {
            $columnSQLs[] = "    `updated_by` INT NULL";
        }

        $sql .= implode(",\n", $columnSQLs);

        // Add unique keys
        if (!empty($this->uniqueKeys)) {
            foreach ($this->uniqueKeys as $ukSet) {
                if (is_array($ukSet) && !empty($ukSet)) {
                    $ukName = "uk_{$this->tableName}_" . implode('_', $ukSet);
                    // Sanitize ukName further if column names can have special chars
                    $ukName = preg_replace('/[^a-zA-Z0-9_]/', '', $ukName);
                    $ukName = substr($ukName, 0, 64); // Max index name length
                    $sql .= ",\n    UNIQUE KEY `{$ukName}` (`" . implode('`, `', $ukSet) . "`)";
                }
            }
        }

        // Add foreign keys
        if (!empty($this->foreignKeys)) {
            foreach ($this->foreignKeys as $fkColumn => $fkDetails) {
                $constraintName = "fk_{$this->tableName}_{$fkColumn}";
                $constraintName = preg_replace('/[^a-zA-Z0-9_]/', '', $constraintName);
                $constraintName = substr($constraintName, 0, 64);

                $sql .= ",\n    CONSTRAINT `{$constraintName}` FOREIGN KEY (`{$fkColumn}`) " .
                      "REFERENCES `{$fkDetails['table']}`(`{$fkDetails['key']}`)";
                
                if (!empty($fkDetails['on_delete'])) {
                    $sql .= " ON DELETE " . strtoupper($fkDetails['on_delete']);
                }
                if (!empty($fkDetails['on_update'])) {
                    $sql .= " ON UPDATE " . strtoupper($fkDetails['on_update']);
                }
            }
        }

        $sql .= "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n";
        
        // Add indexes for common query patterns
        $sql .= $this->generateIndexSQL();
        
        return $sql;
    }
    
    /**
     * Generate SQL for indexes based on common query patterns
     * 
     * @return string SQL for indexes
     */
    private function generateIndexSQL(): string {
        $sql = "\n-- Indexes for common query patterns\n";
        $indexes = [];
        
        // Add index for foreign keys if not already part of a unique constraint
        foreach ($this->foreignKeys as $fkColumn => $fkDetails) {
            $isPartOfUnique = false;
            foreach ($this->uniqueKeys as $ukSet) {
                if (in_array($fkColumn, $ukSet)) {
                    $isPartOfUnique = true;
                    break;
                }
            }
            
            if (!$isPartOfUnique) {
                $indexName = "idx_{$this->tableName}_{$fkColumn}";
                $indexes[] = "CREATE INDEX `{$indexName}` ON `{$this->tableName}` (`{$fkColumn}`);";
            }
        }
        
        // Add index for created_at and updated_at for efficient sorting
        if (in_array('created_at', $this->columns)) {
            $indexes[] = "CREATE INDEX `idx_{$this->tableName}_created_at` ON `{$this->tableName}` (`created_at`);";
        }
        
        if (in_array('updated_at', $this->columns)) {
            $indexes[] = "CREATE INDEX `idx_{$this->tableName}_updated_at` ON `{$this->tableName}` (`updated_at`);";
        }
        
        // Add compound index for common search patterns
        // This is just an example - would need to be customized based on actual usage patterns
        $searchableColumns = array_filter($this->columns, function($col) {
            return $col !== $this->primaryKey && 
                   !in_array($col, ['created_at', 'updated_at', 'created_by', 'updated_by']) &&
                   $this->getColumnFormType($col) !== 'password' &&
                   $this->getColumnFormType($col) !== 'file' &&
                   $this->getColumnFormType($col) !== 'image';
        });
        
        // Take up to 3 columns for a compound index
        $searchColumns = array_slice($searchableColumns, 0, 3);
        if (count($searchColumns) >= 2) {
            $indexName = "idx_{$this->tableName}_" . implode('_', $searchColumns);
            $indexName = substr($indexName, 0, 64); // Max index name length
            $indexes[] = "CREATE INDEX `{$indexName}` ON `{$this->tableName}` (`" . implode('`, `', $searchColumns) . "`);";
        }
        
        return empty($indexes) ? "" : implode("\n", $indexes) . "\n";
    }

    /**
     * Generate SQL for inserting sample data
     * 
     * @param int $count Number of sample records to generate
     * @return string SQL for sample data
     */
    public function generateSampleDataSQL(int $count = 10): string {
        $sql = "\n-- Sample data for {$this->tableName} table\n";
        
        // Check if we should generate sample data
        if ($count <= 0) {
            return $sql . "-- No sample data requested\n";
        }
        
        // List of insertable columns (exclude PK if auto-increment)
        $insertableColumns = array_filter($this->columns, function($column) {
            if ($column === $this->primaryKey) {
                $def = $this->getColumnDef($column);
                return strpos(strtoupper($def['attributes'] ?? ''), 'AUTO_INCREMENT') === false;
            }
            return true;
        });
        
        // Create multi-value insert
        $sql .= "INSERT INTO `{$this->tableName}` (`" . implode("`, `", $insertableColumns) . "`) VALUES\n";
        
        $values = [];
        for ($i = 1; $i <= $count; $i++) {
            $rowValues = [];
            foreach ($insertableColumns as $column) {
                $def = $this->getColumnDef($column);
                $formType = $this->getColumnFormType($column);
                
                // Generate sample value based on column type
                switch ($formType) {
                    case 'boolean_select':
                    case 'checkbox':
                        $rowValues[] = rand(0, 1);
                        break;
                    
                    case 'date':
                        $date = date('Y-m-d', strtotime("-" . rand(0, 365) . " days"));
                        $rowValues[] = "'{$date}'";
                        break;
                    
                    case 'datetime-local':
                        $date = date('Y-m-d H:i:s', strtotime("-" . rand(0, 365) . " days -" . rand(0, 23) . " hours"));
                        $rowValues[] = "'{$date}'";
                        break;
                    
                    case 'number':
                        if (strpos(strtoupper($def['type'] ?? ''), 'INT') !== false) {
                            $rowValues[] = rand(1, 1000);
                        } else {
                            $rowValues[] = rand(1, 1000) / 10;
                        }
                        break;
                    
                    case 'select':
                        if (isset($this->foreignKeys[$column])) {
                            // For foreign keys, we would need to know valid IDs from the referenced table
                            // This is just a placeholder
                            $rowValues[] = rand(1, 5);
                        } else if (isset($def['options']) && !empty($def['options'])) {
                            // For select fields with predefined options
                            $options = array_keys($def['options']);
                            $rowValues[] = "'" . addslashes($options[array_rand($options)]) . "'";
                        } else {
                            $rowValues[] = "NULL";
                        }
                        break;
                    
                    case 'password':
                        // Generate a simple hashed password for sample data
                        $rowValues[] = "'$2y$10$abcdefghijklmnopqrstuuWqoTFPwWj/hniYMQFHuJ.QHQQz1p0i6'"; // hash of 'password'
                        break;
                    
                    case 'email':
                        $rowValues[] = "'user{$i}@example.com'";
                        break;
                    
                    case 'json_textarea':
                        $rowValues[] = "'{\"id\": {$i}, \"sample\": true}'";
                        break;
                    
                    default:
                        // Text fields
                        if (in_array($column, ['created_at', 'updated_at'])) {
                            $rowValues[] = "CURRENT_TIMESTAMP";
                        } else if (in_array($column, ['created_by', 'updated_by'])) {
                            $rowValues[] = rand(1, 5); // Assuming user IDs 1-5 exist
                        } else {
                            $rowValues[] = "'{$this->tableName} {$column} " . str_pad($i, 3, '0', STR_PAD_LEFT) . "'";
                        }
                        break;
                }
            }
            
            $values[] = "(" . implode(", ", $rowValues) . ")";
        }
        
        $sql .= implode(",\n", $values) . ";\n";
        
        return $sql;
    }
    
    /**
     * Generate SQL DDL for all database operations including create table, 
     * indexes, and sample data
     * 
     * @param int $sampleDataCount Number of sample records to generate
     * @return string Complete SQL script
     */
    public function generateCompleteDDL(int $sampleDataCount = 10): string {
        $sql = "-- Complete SQL script for {$this->tableName}\n";
        $sql .= "-- Generated by AdvancedCRUDGenerator 2.0\n\n";
        
        // Add table creation
        $sql .= $this->generateCreateTableSQL();
        
        // Add sample data if requested
        if ($sampleDataCount > 0) {
            $sql .= $this->generateSampleDataSQL($sampleDataCount);
        }
        
        return $sql;
    }
}
?>