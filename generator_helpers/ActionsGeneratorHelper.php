} else if (\$formType === 'password') {
                                    \$row[\$key] = ''; // Don't export passwords
                                } else if (\$formType === 'json_textarea' && !empty(\$value)) {
                                    // Simplify JSON for CSV export
                                    \$row[\$key] = '[JSON Data]';
                                }
                            }
                        }
                        
                        fputcsv(\$output, \$row);
                    }
                    
                    fclose(\$output);
                    exit; // Stop script execution after sending file
                
                case 'excel':
                    // Excel Export (requires PhpSpreadsheet)
                    if (!class_exists('\\PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
                        \$response = ['success' => false, 'message' => 'PhpSpreadsheet is not installed. Please run: composer require phpoffice/phpspreadsheet'];
                        break;
                    }
                    
                    \$spreadsheet = new \\PhpOffice\\PhpSpreadsheet\\Spreadsheet();
                    \$sheet = \$spreadsheet->getActiveSheet();
                    \$sheet->setTitle(substr('{$this->tableName}', 0, 31)); // Excel sheet name length limit
                    
                    // Set headers
                    \$columnIndex = 1;
                    foreach (\$columnLabels as \$label) {
                        \$sheet->setCellValueByColumnAndRow(\$columnIndex++, 1, \$label);
                    }
                    
                    // Add style to header row
                    \$headerStyle = \$sheet->getStyle('A1:' . \\PhpOffice\\PhpSpreadsheet\\Cell\\Coordinate::stringFromColumnIndex(count(\$columnLabels)) . '1');
                    \$headerStyle->getFont()->setBold(true);
                    \$headerStyle->getFill()
                        ->setFillType(\\PhpOffice\\PhpSpreadsheet\\Style\\Fill::FILL_SOLID)
                        ->getStartColor()->setARGB('DDDDDD');
                    
                    // Add data rows
                    \$rowIndex = 2;
                    foreach (\$data as \$row) {
                        \$columnIndex = 1;
                        
                        // Process any special fields (format dates, booleans, etc.)
                        foreach (\$row as \$key => \$value) {
                            // Handle different column types for export
                            \$colName = array_search(\$key, array_column(\$this->foreignKeys, 'field'));
                            if (\$colName === false) {
                                \$colName = \$key;
                            }
                            
                            if (isset(\$this->columnDefinitions[\$colName])) {
                                \$formType = \$this->columnDefinitions[\$colName]['form_type'] ?? 'text';
                                
                                if (\$formType === 'boolean_select' || \$formType === 'checkbox') {
                                    \$value = \$value == 1 ? 'Yes' : 'No';
                                } else if (\$formType === 'date' && !empty(\$value)) {
                                    // Use Excel date format
                                    \$sheet->getCellByColumnAndRow(\$columnIndex, \$rowIndex)
                                        ->setValueExplicit(
                                            \\PhpOffice\\PhpSpreadsheet\\Shared\\Date::PHPToExcel(strtotime(\$value)),
                                            \\PhpOffice\\PhpSpreadsheet\\Cell\\DataType::TYPE_NUMERIC
                                        );
                                    \$sheet->getStyleByColumnAndRow(\$columnIndex, \$rowIndex)
                                        ->getNumberFormat()
                                        ->setFormatCode(\\PhpOffice\\PhpSpreadsheet\\Style\\NumberFormat::FORMAT_DATE_YYYYMMDD);
                                    \$columnIndex++;
                                    continue;
                                } else if (\$formType === 'datetime-local' && !empty(\$value)) {
                                    // Use Excel datetime format
                                    \$sheet->getCellByColumnAndRow(\$columnIndex, \$rowIndex)
                                        ->setValueExplicit(
                                            \\PhpOffice\\PhpSpreadsheet\\Shared\\Date::PHPToExcel(strtotime(\$value)),
                                            \\PhpOffice\\PhpSpreadsheet\\Cell\\DataType::TYPE_NUMERIC
                                        );
                                    \$sheet->getStyleByColumnAndRow(\$columnIndex, \$rowIndex)
                                        ->getNumberFormat()
                                        ->setFormatCode(\\PhpOffice\\PhpSpreadsheet\\Style\\NumberFormat::FORMAT_DATE_DATETIME);
                                    \$columnIndex++;
                                    continue;
                                } else if (\$formType === 'password') {
                                    \$value = ''; // Don't export passwords
                                } else if (\$formType === 'json_textarea' && !empty(\$value)) {
                                    \$value = '[JSON Data]';
                                }
                            }
                            
                            \$sheet->setCellValueByColumnAndRow(\$columnIndex++, \$rowIndex, \$value);
                        }
                        
                        \$rowIndex++;
                    }
                    
                    // Auto-size columns
                    foreach (range(1, count(\$columnLabels)) as \$columnIndex) {
                        \$sheet->getColumnDimensionByColumn(\$columnIndex)->setAutoSize(true);
                    }
                    
                    // Output to browser
                    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                    header('Content-Disposition: attachment;filename=\"' . \$filename . '.xlsx\"');
                    header('Cache-Control: max-age=0');
                    
                    \$writer = new \\PhpOffice\\PhpSpreadsheet\\Writer\\Xlsx(\$spreadsheet);
                    \$writer->save('php://output');
                    exit;
                
                case 'pdf':
                    // PDF Export (implementation depends on PDF library)
                    \$response = ['success' => false, 'message' => 'PDF export not fully implemented. Please use CSV or Excel format.'];
                    break;
            }
            break;
";
    }

    /**
     * Generate CSV import action case
     * 
     * @return string PHP code for CSV import action
     */
    private function generateImportCsvActionCase(): string {
        $tableLabel = htmlspecialchars(ucfirst(str_replace('_', ' ', $this->tableName)));
        
        return "
        case 'import_csv':
            if (!check_permission('create_manage_{$this->tableName}') && !check_permission('update_manage_{$this->tableName}')) {
                http_response_code(403);
                \$response = ['success' => false, 'message' => 'Permission denied for import.'];
                break;
            }
            
            if (!isset(\$_FILES['import_file']) || \$_FILES['import_file']['error'] != UPLOAD_ERR_OK) {
                http_response_code(400);
                \$response = ['success' => false, 'message' => 'No file uploaded or upload error.'];
                break;
            }
            
            // Check file type
            \$fileInfo = pathinfo(\$_FILES['import_file']['name']);
            \$fileExtension = strtolower(\$fileInfo['extension'] ?? '');
            
            if (\$fileExtension !== 'csv') {
                \$response = ['success' => false, 'message' => 'Only CSV files are supported for import.'];
                break;
            }
            
            \$file = \$_FILES['import_file']['tmp_name'];
            \$fileHandle = fopen(\$file, 'r');
            
            if (!\$fileHandle) {
                \$response = ['success' => false, 'message' => 'Could not open uploaded file.'];
                break;
            }
            
            // Check if first row contains headers
            \$hasHeader = isset(\$_POST['has_header']) && \$_POST['has_header'] === 'on';
            
            // Get update key if provided
            \$updateKey = \$_POST['update_key'] ?? '';
            \$allowUpdate = !empty(\$updateKey) && (
                \$updateKey === '{$this->primaryKey}' || 
                in_array([\$updateKey], \$this->uniqueKeys) || 
                array_search(\$updateKey, array_column(\$this->uniqueKeys, 0)) !== false
            );
            
            // Read headers
            \$headers = \$hasHeader ? fgetcsv(\$fileHandle) : null;
            
            if (\$hasHeader && !\$headers) {
                \$response = ['success' => false, 'message' => 'CSV file is empty or not readable.'];
                fclose(\$fileHandle);
                break;
            }
            
            // If no headers provided, use columns as headers (except primary key if auto-increment)
            if (!\$hasHeader) {
                \$headers = array_diff(\$this->columns, \$this->getColumnDef('{$this->primaryKey}', 'attributes', '') === 'AUTO_INCREMENT' ? ['{$this->primaryKey}'] : []);
                // Reset file pointer to beginning
                rewind(\$fileHandle);
            }
            
            // Normalize headers (lowercase, replace space with underscore)
            \$normalizedHeaders = array_map(function(\$h) {
                return strtolower(str_replace(' ', '_', trim(\$h)));
            }, \$headers);
            
            // Map CSV headers to DB columns
            \$columnMap = [];
            \$dbColumnsForImport = array_diff(\$this->columns, ['{$this->primaryKey}']);
            
            foreach (\$normalizedHeaders as \$index => \$csvHeader) {
                // Try direct match
                if (in_array(\$csvHeader, \$dbColumnsForImport)) {
                    \$columnMap[\$index] = \$csvHeader;
                    continue;
                }
                
                // Try matching based on column labels
                foreach (\$this->columnDefinitions as \$dbCol => \$def) {
                    if (isset(\$def['label'])) {
                        \$normalizedLabel = strtolower(str_replace(' ', '_', trim(\$def['label'])));
                        if (\$normalizedLabel === \$csvHeader && in_array(\$dbCol, \$dbColumnsForImport)) {
                            \$columnMap[\$index] = \$dbCol;
                            break;
                        }
                    }
                }
            }
            
            if (empty(\$columnMap)) {
                \$response = [
                    'success' => false, 
                    'message' => 'Could not map CSV headers to any known table columns. Please ensure headers are correct.',
                    'expected_columns' => \$dbColumnsForImport,
                    'found_headers' => \$normalizedHeaders
                ];
                fclose(\$fileHandle);
                break;
            }
            
            // Process the data
            \$insertedRows = 0;
            \$updatedRows = 0;
            \$errorRows = 0;
            \$processedRows = 0;
            \$errorsDetails = [];
            \$rowNum = \$hasHeader ? 1 : 0; // For error reporting (start at 1 if header row exists)
            
            // Start transaction for better data integrity
            \$conn->begin_transaction();
            
            try {
                while ((\$rowData = fgetcsv(\$fileHandle)) !== FALSE) {
                    \$rowNum++;
                    \$processedRows++;
                    \$dataToSave = [];
                    \$errors = [];
                    
                    // Extract data from CSV row based on column mapping
                    foreach (\$columnMap as \$csvIndex => \$dbColumn) {
                        if (!isset(\$rowData[\$csvIndex])) {
                            continue; // Skip if column doesn't exist in this row
                        }
                        
                        \$value = trim(\$rowData[\$csvIndex]);
                        
                        // Basic data type conversion/validation
                        \$def = \$this->getColumnDef(\$dbColumn);
                        \$formType = \$this->getColumnFormType(\$dbColumn);
                        
                        // Handle different data types
                        if (\$this->isColumnNullable(\$dbColumn) && \$value === '') {
                            \$dataToSave[\$dbColumn] = null;
                        } else if (\$formType === 'boolean_select' || \$formType === 'checkbox') {
                            // Convert various representations of boolean values
                            \$dataToSave[\$dbColumn] = (strtolower(\$value) === 'yes' || 
                                                     strtolower(\$value) === 'true' || 
                                                     \$value === '1' || 
                                                     \$value === 1 || 
                                                     \$value === true) ? 1 : 0;
                        } else if (\$formType === 'date' || \$formType === 'datetime-local') {
                            // Try to parse dates
                            if (empty(\$value)) {
                                \$dataToSave[\$dbColumn] = \$this->isColumnNullable(\$dbColumn) ? null : '';
                            } else {
                                try {
                                    \$date = new DateTime(\$value);
                                    \$dataToSave[\$dbColumn] = \$date->format(\$formType === 'date' ? 'Y-m-d' : 'Y-m-d H:i:s');
                                } catch (Exception \$e) {
                                    \$errors[] = \"Invalid date format for column '\$dbColumn'. Expected format: \" . 
                                              (\$formType === 'date' ? 'YYYY-MM-DD' : 'YYYY-MM-DD HH:MM:SS');
                                }
                            }
                        } else if (\$formType === 'number' || strpos(strtoupper(\$def['type'] ?? ''), 'INT') !== false) {
                            // Validate numeric values
                            if (\$value === '') {
                                \$dataToSave[\$dbColumn] = \$this->isColumnNullable(\$dbColumn) ? null : 0;
                            } else if (!is_numeric(\$value)) {
                                \$errors[] = \"Invalid numeric value for column '\$dbColumn': \$value\";
                            } else {
                                \$dataToSave[\$dbColumn] = strpos(strtoupper(\$def['type'] ?? ''), 'INT') !== false ? 
                                                       intval(\$value) : floatval(\$value);
                            }
                        } else if (\$formType === 'json_textarea') {
                            // Basic JSON validation
                            if (\$value === '') {
                                \$dataToSave[\$dbColumn] = \$this->isColumnNullable(\$dbColumn) ? null : '{}';
                            } else {
                                \$jsonData = json_decode(\$value);
                                if (json_last_error() !== JSON_ERROR_NONE) {
                                    \$errors[] = \"Invalid JSON format for column '\$dbColumn'\";
                                } else {
                                    \$dataToSave[\$dbColumn] = \$value;
                                }
                            }
                        } else {
                            // Default: store as string
                            \$dataToSave[\$dbColumn] = \$value;
                        }
                    }
                    
                    // Skip row if there are errors
                    if (!empty(\$errors)) {
                        \$errorRows++;
                        \$errorsDetails[] = [
                            'row' => \$rowNum,
                            'error' => implode('; ', \$errors)
                        ];
                        continue;
                    }
                    
                    // Check if we need to do an update instead of insert
                    \$isUpdate = false;
                    \$updateId = null;
                    
                    if (\$allowUpdate && isset(\$dataToSave[\$updateKey])) {
                        // Try to find existing record based on update key
                        \$findSql = \"SELECT `{$this->primaryKey}` FROM `{$this->tableName}` WHERE `\$updateKey` = ?\";
                        \$findStmt = \$conn->prepare(\$findSql);
                        
                        if (\$findStmt) {
                            \$findValue = \$dataToSave[\$updateKey];
                            \$findType = \$this->getBindTypeForColumn(\$updateKey);
                            \$findStmt->bind_param(\$findType, \$findValue);
                            
                            if (\$findStmt->execute()) {
                                \$findResult = \$findStmt->get_result();
                                if (\$findRow = \$findResult->fetch_assoc()) {
                                    \$isUpdate = true;
                                    \$updateId = \$findRow['{$this->primaryKey}'];
                                }
                            }
                            
                            \$findStmt->close();
                        }
                    }
                    
                    // Now do the insert or update
                    if (\$isUpdate) {
                        // Build UPDATE statement
                        \$updateSql = \"UPDATE `{$this->tableName}` SET \";
                        \$updateFields = [];
                        \$updateTypes = '';
                        \$updateValues = [];
                        
                        foreach (\$dataToSave as \$field => \$value) {
                            \$updateFields[] = \"`\$field` = ?\";
                            \$updateTypes .= \$this->getBindTypeForColumn(\$field);
                            \$updateValues[] = \$value;
                        }
                        
                        // Add updated_at timestamp
                        \$updateFields[] = \"`updated_at` = CURRENT_TIMESTAMP\";
                        
                        // Add updated_by if applicable
                        if (in_array('updated_by', \$this->columns) && function_exists('get_current_user_id')) {
                            \$current_user_id = get_current_user_id();
                            \$updateFields[] = \"`updated_by` = \$current_user_id\";
                        }
                        
                        \$updateSql .= implode(', ', \$updateFields) . \" WHERE `{$this->primaryKey}` = ?\";
                        \$updateTypes .= 'i'; // Add type for primary key
                        \$updateValues[] = \$updateId;
                        
                        \$updateStmt = \$conn->prepare(\$updateSql);
                        if (!\$updateStmt) {
                            throw new Exception(\"Failed to prepare update query: \" . \$conn->error);
                        }
                        
                        \$updateStmt->bind_param(\$updateTypes, ...\$updateValues);
                        
                        if (!\$updateStmt->execute()) {
                            throw new Exception(\"Failed to execute update query: \" . \$updateStmt->error);
                        }
                        
                        \$updateStmt->close();
                        \$updatedRows++;
                    } else {
                        // Build INSERT statement
                        \$insertSql = \"INSERT INTO `{$this->tableName}` (\" . 
                                  implode(', ', array_map(function(\$field) { return \"`\$field`\"; }, array_keys(\$dataToSave)));
                        
                        // Add timestamps
                        \$insertSql .= \", `created_at`\";
                        if (in_array('updated_at', \$this->columns)) {
                            \$insertSql .= \", `updated_at`\";
                        }
                        
                        // Add user fields if applicable
                        if (in_array('created_by', \$this->columns) && function_exists('get_current_user_id')) {
                            \$insertSql .= \", `created_by`\";
                        }
                        if (in_array('updated_by', \$this->columns) && function_exists('get_current_user_id')) {
                            \$insertSql .= \", `updated_by`\";
                        }
                        
                        \$insertSql .= \") VALUES (\" . implode(', ', array_fill(0, count(\$dataToSave), '?'));
                        
                        // Add timestamp values
                        \$insertSql .= \", CURRENT_TIMESTAMP\";
                        if (in_array('updated_at', \$this->columns)) {
                            \$insertSql .= \", CURRENT_TIMESTAMP\";
                        }
                        
                        // Add user values if applicable
                        if (in_array('created_by', \$this->columns) && function_exists('get_current_user_id')) {
                            \$current_user_id = get_current_user_id();
                            \$insertSql .= \", \$current_user_id\";
                        }
                        if (in_array('updated_by', \$this->columns) && function_exists('get_current_user_id')) {
                            \$current_user_id = get_current_user_id();
                            \$insertSql .= \", \$current_user_id\";
                        }
                        
                        \$insertSql .= \")\";
                        
                        \$insertTypes = '';
                        \$insertValues = [];
                        
                        foreach (\$dataToSave as \$field => \$value) {
                            \$insertTypes .= \$this->getBindTypeForColumn(\$field);
                            \$insertValues[] = \$value;
                        }
                        
                        \$insertStmt = \$conn->prepare(\$insertSql);
                        if (!\$insertStmt) {
                            throw new Exception(\"Failed to prepare insert query: \" . \$conn->error);
                        }
                        
                        if (!empty(\$insertTypes)) {
                            \$insertStmt->bind_param(\$insertTypes, ...\$insertValues);
                        }
                        
                        if (!\$insertStmt->execute()) {
                            throw new Exception(\"Failed to execute insert query: \" . \$insertStmt->error);
                        }
                        
                        \$insertStmt->close();
                        \$insertedRows++;
                    }
                }
                
                // Commit the transaction
                \$conn->commit();
                
                \$response = [
                    'success' => true,
                    'message' => 'CSV imported successfully.',
                    'processed_rows' => \$processedRows,
                    'inserted_rows' => \$insertedRows,
                    'updated_rows' => \$updatedRows,
                    'error_rows' => \$errorRows
                ];
                
                // Include error details if any
                if (!empty(\$errorsDetails)) {
                    \$response['errors_details'] = \$errorsDetails;
                }
            } catch (Exception \$e) {
                // Roll back the transaction on error
                \$conn->rollback();
                
                error_log(\"Import CSV failed for {$this->tableName}: \" . \$e->getMessage());
                \$response = [
                    'success' => false,
                    'message' => 'Import failed: ' . \$e->getMessage(),
                    'processed_rows' => \$processedRows,
                    'inserted_rows' => 0,
                    'updated_rows' => 0,
                    'error_rows' => \$processedRows
                ];
            } finally {
                fclose(\$fileHandle);
            }
            break;
";
    }
}
?>