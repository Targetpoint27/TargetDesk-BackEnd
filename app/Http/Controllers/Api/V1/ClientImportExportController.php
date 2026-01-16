<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Client;
use App\Exports\ClientTemplateExport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class ClientImportExportController extends BaseApiController
{
    /**
     * @OA\Post(
     *     path="/v1/clients/import/preview",
     *     tags={"Client Import/Export"},
     *     summary="Preview CSV/Excel import",
     *     description="Upload and preview client data before import",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="file",
     *                     type="string",
     *                     format="binary",
     *                     description="CSV or Excel file"
     *                 ),
     *                 @OA\Property(
     *                     property="mapping",
     *                     type="object",
     *                     description="Column mapping configuration",
     *                     example={"name": "A", "email": "B", "phone": "C"}
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Preview data generated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Prévisualisation générée"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="preview", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="stats", type="object"),
     *                 @OA\Property(property="duplicates", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="errors", type="array", @OA\Items(type="object"))
     *             )
     *         )
     *     )
     * )
     */
    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240', // 10MB max
            'mapping' => 'sometimes|array'
        ]);

        $file = $request->file('file');
        $mapping = $request->input('mapping', []);

        try {
            $data = $this->parseFile($file, $mapping);
            $preview = $this->generatePreview($data);

            Log::info('Import preview generated', [
                'filename' => $file->getClientOriginalName(),
                'rows_count' => count($data),
                'user_id' => auth()->id()
            ]);

            return $this->successResponse($preview, 'Prévisualisation générée avec succès');
        } catch (\Exception $e) {
            Log::error('Import preview failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return $this->errorResponse('Erreur lors de la prévisualisation: ' . $e->getMessage(), 422);
        }
    }

    /**
     * @OA\Post(
     *     path="/v1/clients/import",
     *     tags={"Client Import/Export"},
     *     summary="Import clients from CSV/Excel",
     *     description="Execute client import with duplicate handling",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="file",
     *                     type="string",
     *                     format="binary"
     *                 ),
     *                 @OA\Property(
     *                     property="mapping",
     *                     type="object",
     *                     description="Column mapping"
     *                 ),
     *                 @OA\Property(
     *                     property="duplicate_action",
     *                     type="string",
     *                     enum={"ignore", "replace", "update"},
     *                     description="Action for duplicates"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Import completed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Import terminé"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="report", type="object")
     *             )
     *         )
     *     )
     * )
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240',
            'mapping' => 'required|array',
            'duplicate_action' => 'required|in:ignore,replace,update'
        ]);

        $file = $request->file('file');
        $mapping = $request->input('mapping');
        $duplicateAction = $request->input('duplicate_action');

        try {
            $data = $this->parseFile($file, $mapping);
            $report = $this->executeImport($data, $duplicateAction);

            Log::info('Client import completed', [
                'filename' => $file->getClientOriginalName(),
                'imported' => $report['imported'],
                'errors' => count($report['errors']),
                'user_id' => auth()->id()
            ]);

            return $this->successResponse(['report' => $report], 'Import terminé avec succès');
        } catch (\Exception $e) {
            Log::error('Client import failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return $this->errorResponse('Erreur lors de l\'import: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/v1/clients/export/template",
     *     tags={"Client Import/Export"},
     *     summary="Download import template",
     *     description="Get CSV template file for client import",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Template file",
     *         @OA\MediaType(
     *             mediaType="text/csv",
     *             @OA\Schema(type="string")
     *         )
     *     )
     * )
     */
    public function downloadTemplate($format = 'csv')
    {
        $headers = [
            'name',
            'type',
            'email',
            'phone',
            'address',
            'siret',
            'sector',
            'website',
            'notes'
        ];

        $sample = [
            'Entreprise ACME',
            'entreprise',
            'contact@acme.com',
            '0123456789',
            '123 Rue de la Paix, 75001 Paris',
            '12345678901234',
            'Technologie',
            'https://acme.com',
            'Client important'
        ];

        if ($format === 'excel') {
            $filename = 'template_import_clients_' . date('Y-m-d') . '.xlsx';
            return Excel::download(new ClientTemplateExport(), $filename);
        }

        // CSV format (default)
        $csv = fopen('php://temp', 'w');
        fputcsv($csv, $headers);
        fputcsv($csv, $sample);
        rewind($csv);
        $content = stream_get_contents($csv);
        fclose($csv);

        $filename = 'template_import_clients_' . date('Y-m-d') . '.csv';

        return response($content)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * @OA\Get(
     *     path="/v1/clients/export/template/excel",
     *     tags={"Client Import/Export"},
     *     summary="Download Excel import template",
     *     description="Get Excel template file for client import",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Excel template file",
     *         @OA\MediaType(
     *             mediaType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
     *             @OA\Schema(type="string")
     *         )
     *     )
     * )
     */
    public function downloadExcelTemplate()
    {
        return $this->downloadTemplate('excel');
    }

    /**
     * @OA\Post(
     *     path="/v1/clients/export",
     *     tags={"Client Import/Export"},
     *     summary="Export clients",
     *     description="Export clients list to CSV/Excel with filters",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="format", type="string", enum={"csv", "excel"}, example="csv"),
     *             @OA\Property(property="columns", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="filters", type="object"),
     *             @OA\Property(property="client_ids", type="array", @OA\Items(type="integer")),
     *             @OA\Property(property="limit", type="integer", example=10000)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Export completed",
     *         @OA\MediaType(
     *             mediaType="text/csv",
     *             @OA\Schema(type="string")
     *         )
     *     )
     * )
     */
    public function export(Request $request)
    {
        $request->validate([
            'format' => 'sometimes|in:csv,excel',
            'columns' => 'sometimes|array',
            'filters' => 'sometimes|array',
            'client_ids' => 'sometimes|array',
            'client_ids.*' => 'integer|exists:clients,id',
            'limit' => 'sometimes|integer|min:1|max:10000'
        ]);

        $format = $request->input('format', 'csv');
        $columns = $request->input('columns', [
            'client_id', 'name', 'type', 'email', 'phone',
            'address', 'siret', 'sector', 'website', 'notes',
            'is_active', 'created_at'
        ]);
        $filters = $request->input('filters', []);
        $clientIds = $request->input('client_ids');
        $limit = $request->input('limit', 10000);

        try {
            $query = Client::with(['creator:id,name', 'categories:id,name,color,type']);

            // Apply filters
            if ($clientIds) {
                $query->whereIn('id', $clientIds);
            }

            if (isset($filters['type'])) {
                $query->where('type', $filters['type']);
            }

            if (isset($filters['is_active'])) {
                $query->where('is_active', $filters['is_active']);
            }

            if (isset($filters['search'])) {
                $search = $filters['search'];
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('client_id', 'like', "%{$search}%");
            }

            $clients = $query->orderBy('created_at', 'desc')->limit($limit)->get();

            $filename = 'export_clients_' . date('Y-m-d_H-i-s') . '.' . $format;

            if ($format === 'csv') {
                $content = $this->generateCsvContent($clients, $columns);
                $contentType = 'text/csv';
            } else {
                // Excel export
                $content = $this->generateExcelContent($clients, $columns);
                $contentType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
                $filename = str_replace('.excel', '.xlsx', $filename);
            }

            Log::info('Client export completed', [
                'format' => $format,
                'count' => $clients->count(),
                'columns' => $columns,
                'user_id' => auth()->id()
            ]);

            return response($content)
                ->header('Content-Type', $contentType)
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');

        } catch (\Exception $e) {
            Log::error('Client export failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return $this->errorResponse('Erreur lors de l\'export: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Parse uploaded file (CSV or Excel)
     */
    private function parseFile($file, array $mapping = []): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $path = $file->getRealPath();

        if (in_array($extension, ['csv', 'txt'])) {
            return $this->parseCsv($path, $mapping);
        } elseif (in_array($extension, ['xlsx', 'xls'])) {
            return $this->parseExcel($path, $mapping);
        } else {
            throw new \Exception('Format de fichier non supporté: ' . $extension);
        }
    }

    /**
     * Parse CSV file
     */
    private function parseCsv(string $path, array $mapping = []): array
    {
        $data = [];
        $headers = [];
        $rowIndex = 0;

        if (($handle = fopen($path, 'r')) !== false) {
            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                if ($rowIndex === 0) {
                    $headers = $row;
                    $rowIndex++;
                    continue;
                }

                $rowData = [];
                foreach ($headers as $index => $header) {
                    $value = isset($row[$index]) ? trim($row[$index]) : '';

                    // Apply mapping if provided
                    if (!empty($mapping)) {
                        $mappedField = array_search($header, $mapping);
                        if ($mappedField) {
                            $rowData[$mappedField] = $value;
                        }
                    } else {
                        // Direct mapping by header name
                        $rowData[$header] = $value;
                    }
                }

                $rowData['_row_number'] = $rowIndex + 1;
                $data[] = $rowData;
                $rowIndex++;
            }
            fclose($handle);
        }

        return $data;
    }

    /**
     * Parse Excel file
     */
    private function parseExcel(string $path, array $mapping = []): array
    {
        try {
            $spreadsheet = IOFactory::load($path);
            $worksheet = $spreadsheet->getActiveSheet();
            $data = [];
            $headers = [];
            $rowIndex = 0;

            foreach ($worksheet->getRowIterator() as $row) {
                $rowData = [];
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);

                foreach ($cellIterator as $cell) {
                    $rowData[] = $cell->getCalculatedValue();
                }

                if ($rowIndex === 0) {
                    $headers = $rowData;
                    $rowIndex++;
                    continue;
                }

                $parsedRow = [];
                foreach ($headers as $index => $header) {
                    $value = isset($rowData[$index]) ? trim((string)$rowData[$index]) : '';

                    // Apply mapping if provided
                    if (!empty($mapping)) {
                        $mappedField = array_search($header, $mapping);
                        if ($mappedField) {
                            $parsedRow[$mappedField] = $value;
                        }
                    } else {
                        // Direct mapping by header name
                        $parsedRow[$header] = $value;
                    }
                }

                $parsedRow['_row_number'] = $rowIndex + 1;
                $data[] = $parsedRow;
                $rowIndex++;
            }

            return $data;
        } catch (\Exception $e) {
            throw new \Exception('Erreur lors de la lecture du fichier Excel: ' . $e->getMessage());
        }
    }

    /**
     * Generate preview with validation and duplicate detection
     */
    private function generatePreview(array $data): array
    {
        $preview = [];
        $errors = [];
        $duplicates = [];
        $stats = [
            'total_rows' => count($data),
            'valid_rows' => 0,
            'invalid_rows' => 0,
            'duplicates_found' => 0
        ];

        foreach ($data as $index => $row) {
            $validationResult = $this->validateRow($row);
            $duplicateCheck = $this->checkDuplicates($row);

            $previewRow = [
                'row_number' => $row['_row_number'] ?? $index + 1,
                'data' => $row,
                'validation' => $validationResult,
                'duplicate' => $duplicateCheck
            ];

            if (!empty($validationResult['errors'])) {
                $stats['invalid_rows']++;
                $errors[] = $previewRow;
            } else {
                $stats['valid_rows']++;
            }

            if ($duplicateCheck['is_duplicate']) {
                $stats['duplicates_found']++;
                $duplicates[] = $previewRow;
            }

            $preview[] = $previewRow;

            // Limit preview to first 50 rows
            if (count($preview) >= 50) {
                break;
            }
        }

        return [
            'preview' => $preview,
            'stats' => $stats,
            'errors' => $errors,
            'duplicates' => $duplicates
        ];
    }

    /**
     * Validate a single row
     */
    private function validateRow(array $row): array
    {
        $validator = Validator::make($row, [
            'name' => 'required|string|max:255',
            'type' => 'required|in:particulier,entreprise',
            'email' => 'required|email',
            'phone' => 'nullable|string|max:20',
            'siret' => 'nullable|string|size:14',
            'website' => 'nullable|url'
        ], [
            'name.required' => 'Le nom est requis',
            'type.required' => 'Le type est requis',
            'type.in' => 'Le type doit être "particulier" ou "entreprise"',
            'email.required' => 'L\'email est requis',
            'email.email' => 'L\'email doit être valide',
            'siret.size' => 'Le SIRET doit contenir 14 caractères',
            'website.url' => 'Le site web doit être une URL valide'
        ]);

        return [
            'is_valid' => !$validator->fails(),
            'errors' => $validator->errors()->toArray()
        ];
    }

    /**
     * Check for duplicates in database
     */
    private function checkDuplicates(array $row): array
    {
        $duplicates = [];

        if (!empty($row['email'])) {
            $emailExists = Client::where('email', $row['email'])
                                ->where('is_active', true)
                                ->first();
            if ($emailExists) {
                $duplicates[] = [
                    'field' => 'email',
                    'value' => $row['email'],
                    'existing_client' => $emailExists->only(['id', 'client_id', 'name', 'email'])
                ];
            }
        }

        if (!empty($row['siret'])) {
            $siretExists = Client::where('siret', $row['siret'])
                                ->where('is_active', true)
                                ->first();
            if ($siretExists) {
                $duplicates[] = [
                    'field' => 'siret',
                    'value' => $row['siret'],
                    'existing_client' => $siretExists->only(['id', 'client_id', 'name', 'siret'])
                ];
            }
        }

        return [
            'is_duplicate' => !empty($duplicates),
            'conflicts' => $duplicates
        ];
    }

    /**
     * Execute the import process
     */
    private function executeImport(array $data, string $duplicateAction): array
    {
        $report = [
            'total_rows' => count($data),
            'imported' => 0,
            'updated' => 0,
            'ignored' => 0,
            'errors' => [],
            'warnings' => []
        ];

        foreach ($data as $index => $row) {
            try {
                $validation = $this->validateRow($row);
                if (!$validation['is_valid']) {
                    $report['errors'][] = [
                        'row' => $row['_row_number'] ?? $index + 1,
                        'errors' => $validation['errors']
                    ];
                    continue;
                }

                $duplicateCheck = $this->checkDuplicates($row);

                if ($duplicateCheck['is_duplicate']) {
                    $result = $this->handleDuplicate($row, $duplicateAction, $duplicateCheck);
                    $report[$result['action']]++;

                    if (!empty($result['message'])) {
                        $report['warnings'][] = $result['message'];
                    }
                } else {
                    // Create new client
                    $clientData = $this->prepareClientData($row);
                    Client::create($clientData);
                    $report['imported']++;

                    Log::info('Client imported', [
                        'name' => $clientData['name'],
                        'email' => $clientData['email'],
                        'created_by' => auth()->id()
                    ]);
                }
            } catch (\Exception $e) {
                $report['errors'][] = [
                    'row' => $row['_row_number'] ?? $index + 1,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $report;
    }

    /**
     * Handle duplicate records
     */
    private function handleDuplicate(array $row, string $action, array $duplicateCheck): array
    {
        switch ($action) {
            case 'ignore':
                return [
                    'action' => 'ignored',
                    'message' => "Ligne {$row['_row_number']}: Client ignoré (doublon)"
                ];

            case 'replace':
            case 'update':
                // Find existing client
                $existing = null;
                foreach ($duplicateCheck['conflicts'] as $conflict) {
                    $existing = Client::find($conflict['existing_client']['id']);
                    if ($existing) break;
                }

                if ($existing) {
                    $clientData = $this->prepareClientData($row);
                    unset($clientData['created_by']); // Keep original creator

                    if ($action === 'replace') {
                        $existing->update($clientData);
                    } else { // update - only non-empty fields
                        $updateData = [];
                        foreach ($clientData as $key => $value) {
                            if (!empty($value)) {
                                $updateData[$key] = $value;
                            }
                        }
                        $existing->update($updateData);
                    }

                    Log::info('Client updated from import', [
                        'client_id' => $existing->client_id,
                        'action' => $action,
                        'updated_by' => auth()->id()
                    ]);

                    return [
                        'action' => 'updated',
                        'message' => "Ligne {$row['_row_number']}: Client mis à jour"
                    ];
                }
                break;
        }

        return [
            'action' => 'ignored',
            'message' => "Ligne {$row['_row_number']}: Erreur lors du traitement du doublon"
        ];
    }

    /**
     * Prepare client data for database
     */
    private function prepareClientData(array $row): array
    {
        return [
            'name' => $row['name'] ?? '',
            'type' => $row['type'] ?? 'particulier',
            'email' => $row['email'] ?? '',
            'phone' => $row['phone'] ?? null,
            'address' => $row['address'] ?? null,
            'siret' => $row['siret'] ?? null,
            'sector' => $row['sector'] ?? null,
            'website' => $row['website'] ?? null,
            'notes' => $row['notes'] ?? null,
            'created_by' => auth()->id()
        ];
    }

    /**
     * Generate CSV content for export
     */
    private function generateCsvContent($clients, array $columns): string
    {
        $csv = fopen('php://temp', 'w');

        // Write headers
        fputcsv($csv, $columns);

        // Write data
        foreach ($clients as $client) {
            $row = [];
            foreach ($columns as $column) {
                switch ($column) {
                    case 'creator':
                        $row[] = $client->creator ? $client->creator->name : '';
                        break;
                    case 'categories':
                        $categoryNames = $client->categories->pluck('name')->toArray();
                        $row[] = implode(', ', $categoryNames);
                        break;
                    case 'created_at':
                    case 'updated_at':
                        $row[] = $client->{$column} ? $client->{$column}->format('Y-m-d H:i:s') : '';
                        break;
                    default:
                        $row[] = $client->{$column} ?? '';
                        break;
                }
            }
            fputcsv($csv, $row);
        }

        rewind($csv);
        $content = stream_get_contents($csv);
        fclose($csv);

        return $content;
    }

    /**
     * Generate Excel content for export
     */
    private function generateExcelContent($clients, array $columns): string
    {
        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();

        // Write headers
        $col = 1;
        foreach ($columns as $column) {
            $worksheet->setCellValueByColumnAndRow($col, 1, ucfirst(str_replace('_', ' ', $column)));
            $col++;
        }

        // Style headers
        $headerStyle = [
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E2E8F0']
            ]
        ];
        $lastCol = chr(64 + count($columns));
        $worksheet->getStyle('A1:' . $lastCol . '1')->applyFromArray($headerStyle);

        // Write data
        $row = 2;
        foreach ($clients as $client) {
            $col = 1;
            foreach ($columns as $column) {
                $value = '';

                switch ($column) {
                    case 'creator':
                        $value = $client->creator ? $client->creator->name : '';
                        break;
                    case 'categories':
                        $categoryNames = $client->categories->pluck('name')->toArray();
                        $value = implode(', ', $categoryNames);
                        break;
                    case 'created_at':
                    case 'updated_at':
                        $value = $client->{$column} ? $client->{$column}->format('Y-m-d H:i:s') : '';
                        break;
                    case 'is_active':
                        $value = $client->{$column} ? 'Actif' : 'Inactif';
                        break;
                    default:
                        $value = $client->{$column} ?? '';
                        break;
                }

                $worksheet->setCellValueByColumnAndRow($col, $row, $value);
                $col++;
            }
            $row++;
        }

        // Auto-size columns
        $lastColumn = chr(64 + count($columns));
        foreach (range('A', $lastColumn) as $col) {
            $worksheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Create Excel file in memory
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();

        return $content;
    }
}
