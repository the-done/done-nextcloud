<?php

declare(strict_types=1);

namespace OCA\Done\Service;

use OCA\Done\Models\Base_Model;
use OCA\Done\Models\PermissionsEntities_Model;
use OCP\Server;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Universal Excel/CSV export service for Done application
 */
class ExcelExportService
{
    private static ExcelExportService $instance;
    
    protected UserService $userService;
    protected TranslateService $translateService;
    protected TableService $tableService;
    
    // Export limits
    private const MAX_ROWS = 10000;
    private const MEMORY_LIMIT = '512M';
    
    // Supported formats
    public const FORMAT_EXCEL = 'excel';
    public const FORMAT_CSV = 'csv';
    
    // Cache for prepared data
    private array $dataCache = [];
    private int $cacheTimeout = 300; // 5 minutes
    
    public function __construct(
        UserService $userService,
        TranslateService $translateService,
        TableService $tableService
    ) {
        $this->userService = $userService;
        $this->translateService = $translateService;
        $this->tableService = $tableService;
    }
    
    public static function getInstance(): self
    {
        if (!isset(self::$instance)) {
            self::$instance = Server::get(ExcelExportService::class);
        }
        
        return self::$instance;
    }
    
    /**
     * Export entity data to Excel or CSV
     *
     * @param int $source Entity ID
     * @param array $filters Table filters
     * @param array $options Export options (format, includeHidden, etc.)
     * @return array Export result with file path and metadata
     */
    public function exportEntityToExcel(int $source, array $filters = [], array $options = []): array
    {
        if (!PermissionsEntities_Model::entityExists($source)) {
            return [
                'success' => false,
                'message' => $this->translateService->getTranslate('Invalid entity source')
            ];
        }
        
        $originalMemoryLimit = ini_get('memory_limit');
        ini_set('memory_limit', self::MEMORY_LIMIT);
        
        try {
            $format = $options['format'] ?? self::FORMAT_EXCEL;
            if (!in_array($format, [self::FORMAT_EXCEL, self::FORMAT_CSV])) {
                $format = self::FORMAT_EXCEL;
            }
            
            $sourceData = PermissionsEntities_Model::getPermissionsEntities($source);
            $modelClass = $sourceData[$source]['model'];
            $model = new $modelClass();
            
            $userId = $this->userService->getCurrentUserId();
            if (empty($userId)) {
                return [
                    'success' => false,
                    'message' => $this->translateService->getTranslate('User not found')
                ];
            }
            
            $tableData = $this->getTableDataWithCache($model, $source, $userId, $filters);

            if (count($tableData['data']) > self::MAX_ROWS) {
                return [
                    'success' => false,
                    'message' => $this->translateService->getTranslate('Too many rows to export. Maximum allowed: ') . self::MAX_ROWS
                ];
            }
            
            if ($format === self::FORMAT_CSV) {
                $csvResult = $this->exportToCsv($tableData, $sourceData[$source]['slug']);
                if (!$csvResult['success']) {
                    return $csvResult;
                }
                $filePath = $csvResult['filePath'];
                $fileName = $csvResult['fileName'];
            } else {
                $spreadsheet = $this->createSpreadsheet($tableData, $sourceData[$source]);
                $fileName = $this->generateFileName($sourceData[$source]['slug'], $filters, 'xlsx');
                $filePath = $this->saveSpreadsheet($spreadsheet, $fileName);
            }
            
            return [
                'success' => true,
                'filePath' => $filePath,
                'fileName' => $fileName,
                'rowsCount' => count($tableData['data']),
                'columnsCount' => count($tableData['allColumnsOrdering']),
                'format' => $format
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $this->translateService->getTranslate('Export failed: ') . $e->getMessage()
            ];
        } finally {
            ini_set('memory_limit', $originalMemoryLimit);
        }
    }
    
    /**
     * Get table data with caching
     */
    private function getTableDataWithCache(Base_Model $model, int $source, string $userId, array $filters): array
    {
        $cacheKey = $this->generateCacheKey($source, $userId, $filters);
        
        // Check cache
        if (isset($this->dataCache[$cacheKey])) {
            $cachedData = $this->dataCache[$cacheKey];
            if (time() - $cachedData['timestamp'] < $this->cacheTimeout) {
                return $cachedData['data'];
            }
            unset($this->dataCache[$cacheKey]);
        }
        
        // Get fresh data
        $tableData = $this->tableService->getTableDataForEntity($model, $source, $userId);
        
        // Cache the data
        $this->dataCache[$cacheKey] = [
            'data' => $tableData,
            'timestamp' => time()
        ];
        
        return $tableData;
    }
    
    /**
     * Generate cache key
     */
    private function generateCacheKey(int $source, string $userId, array $filters): string
    {
        return md5($source . '_' . $userId . '_' . serialize($filters));
    }
    
    
    /**
     * Create spreadsheet with data
     */
    private function createSpreadsheet(array $tableData, array $sourceConfig): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        $sheet->setTitle($this->translateService->getTranslate($sourceConfig['entity_name']));
        
        $headers = [];
        $columnIndex = 1;
        
        foreach ($tableData['allColumnsOrdering'] as $column) {
            if (!$column['hidden']) {
                $headers[] = $column['title'];
                $columnIndex++;
            }
        }
        
        $row = 1;
        foreach ($headers as $index => $header) {
            $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex((string)($index + 1));
            $sheet->setCellValue($columnLetter . $row, $header);
        }
        
        $this->styleHeaders($sheet, count($headers));
        
        $row = 2;
        foreach ($tableData['data'] as $dataRow) {
            $columnIndex = 1;
            
            foreach ($tableData['allColumnsOrdering'] as $column) {
                if (!$column['hidden']) {
                    $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex((string)$columnIndex);
                    $value = $this->formatCellValue($dataRow[$column['key']] ?? '');
                    $sheet->setCellValue($columnLetter . $row, $value);
                    $columnIndex++;
                }
            }
            $row++;
        }
        
        $this->autoSizeColumns($sheet, count($headers));
        
        return $spreadsheet;
    }
    
    /**
     * Style headers
     */
    private function styleHeaders($sheet, int $columnCount): void
    {
        $headerRange = 'A1:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex((string)$columnCount) . '1';
        
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '366092']
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ]);
    }
    
    /**
     * Auto-size columns
     */
    private function autoSizeColumns($sheet, int $columnCount): void
    {
        for ($i = 1; $i <= $columnCount; $i++) {
            $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex((string)$i);
            $sheet->getColumnDimension($columnLetter)->setAutoSize(true);
        }
    }
    
    /**
     * Format cell value based on data type
     */
    private function formatCellValue($value): string
    {
        if (is_array($value)) {
            return implode(', ', $value);
        }
        
        if (is_bool($value)) {
            return $value ? $this->translateService->getTranslate('Yes') : $this->translateService->getTranslate('No');
        }
        
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }
        
        return (string)$value;
    }
    
    /**
     * Generate safe filename
     */
    private function generateFileName(string $entitySlug, array $filters, string $extension): string
    {
        $timestamp = date('Y-m-d_H-i-s');
        $baseName = $this->sanitizeFileName($entitySlug);
        
        // Add filter info to filename if present
        $filterSuffix = '';
        if (!empty($filters)) {
            $filterSuffix = '_filtered';
        }
        
        return "{$baseName}_export{$filterSuffix}_{$timestamp}.{$extension}";
    }
    
    /**
     * Sanitize filename
     */
    private function sanitizeFileName(string $filename): string
    {
        $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename);
        $filename = preg_replace('/_+/', '_', $filename);
        $filename = trim($filename, '_');
        
        return $filename ?: 'export';
    }
    
    /**
     * Save spreadsheet to temporary file
     */
    private function saveSpreadsheet(Spreadsheet $spreadsheet, string $fileName): string
    {
        $tempDir = sys_get_temp_dir();
        $filePath = $tempDir . DIRECTORY_SEPARATOR . $fileName;
        
        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);
        
        return $filePath;
    }
    
    /**
     * Clean up temporary file
     */
    public function cleanupTempFile(string $filePath): void
    {
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
    
    /**
     * Clear cache
     */
    public function clearCache(): void
    {
        $this->dataCache = [];
    }
    
    /**
     * Clear expired cache entries
     */
    public function clearExpiredCache(): void
    {
        $currentTime = time();
        foreach ($this->dataCache as $key => $cachedData) {
            if ($currentTime - $cachedData['timestamp'] >= $this->cacheTimeout) {
                unset($this->dataCache[$key]);
            }
        }
    }
    
    /**
     * Get cache statistics
     */
    public function getCacheStats(): array
    {
        return [
            'entries' => count($this->dataCache),
            'timeout' => $this->cacheTimeout
        ];
    }
    
    /**
     * Export data to CSV format (without PhpSpreadsheet)
     */
    private function exportToCsv(array $tableData, string $entitySlug): array
    {
        try {
            $timestamp = date('Y-m-d_H-i-s');
            $fileName = "{$entitySlug}_export_{$timestamp}.csv";
            $filePath = sys_get_temp_dir() . '/' . $fileName;
            
            $file = fopen($filePath, 'w');
            if (!$file) {
                return [
                    'success' => false,
                    'message' => $this->translateService->getTranslate('Cannot create temporary file')
                ];
            }
            
            fwrite($file, "\xEF\xBB\xBF");
            
            if (!empty($tableData['allColumnsOrdering'])) {
                $headers = [];
                foreach ($tableData['allColumnsOrdering'] as $column) {
                    if (!$column['hidden']) {
                        $headers[] = $this->translateService->getTranslate($column['title'] ?? $column['name']);
                    }
                }
                fputcsv($file, $headers, ';'); // Use semicolon as delimiter for better Excel compatibility
            }
            
            if (!empty($tableData['data'])) {
                foreach ($tableData['data'] as $row) {
                    $csvRow = [];
                    foreach ($tableData['allColumnsOrdering'] as $column) {
                        if (!$column['hidden']) {
                            $value = $row[$column['key']] ?? '';
                            
                            // Format value for CSV
                            if (is_array($value)) {
                                $value = implode(', ', $value);
                            } elseif (is_bool($value)) {
                                $value = $value ? $this->translateService->getTranslate('Yes') : $this->translateService->getTranslate('No');
                            } elseif ($value instanceof \DateTime) {
                                $value = $value->format('Y-m-d H:i:s');
                            } else {
                                $value = (string)$value;
                            }
                            
                            $csvRow[] = $value;
                        }
                    }
                    fputcsv($file, $csvRow, ';');
                }
            }
            
            fclose($file);
            
            return [
                'success' => true,
                'filePath' => $filePath,
                'fileName' => $fileName,
                'format' => 'csv'
            ];
            
        } catch (\Exception $e) {
            if (isset($file) && is_resource($file)) {
                fclose($file);
            }
            
            return [
                'success' => false,
                'message' => $this->translateService->getTranslate('CSV export failed: ') . $e->getMessage()
            ];
        }
    }
}
