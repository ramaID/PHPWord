<?php

namespace App\Http\Controllers;

use App\Services\DocumentConverter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Html;
use PhpOffice\PhpWord\TemplateProcessor;
use DOMDocument;
use DOMXPath;
use App\Models\Tenant;
use App\Models\TenantTheme;
use App\Services\SentryEnhancedLogger;

class EnhancedDocumentController extends Controller
{
    private DocumentConverter $documentConverter;

    public function __construct(DocumentConverter $documentConverter)
    {
        $this->documentConverter = $documentConverter;
    }

    public function generateDocument(Request $request)
    {
        // Log the incoming request payload to Sentry for debugging
        SentryEnhancedLogger::logMessage(
            'Document generation request received',
            $request,
            'info',
            [
                'process_name' => $request->input('additionalData.fileName', 'Unknown'),
                'has_image_data' => !empty($request->input('imgData')),
                'has_qr_code' => !empty($request->input('qrCodeElement')),
                'has_general_explanation' => !empty($request->input('generalExplanation')),
                'has_step_explanations' => !empty($request->input('stepExplanations')),
                'intent' => $request->input('intent', 'docx'),
            ]
        );

        // Determine the output format
        $outputFormat = $request->input('intent', 'docx');
        $exportToDoc = $outputFormat === 'doc' || $request->boolean('convert_to_doc', false);

        try {
            // 1. Define the path to your template file.
            $templatePath = resource_path('template.docx');
            $templateProcessor = new TemplateProcessor($templatePath);
            $additionalData = $request->input('additionalData', []);
            $processName = $additionalData['fileName'] ?? 'Default Process';

            // Strip .bpmn and .dmn extensions if they exist
            if (str_ends_with($processName, '.bpmn') || str_ends_with($processName, '.dmn')) {
                $processName = pathinfo($processName, PATHINFO_FILENAME);
            }

            $logoPath = null;

            if ($additionalData['tenantUuid'] ?? false) {
                $tenant = Tenant::query()->whereUuid($additionalData['tenantUuid'])->first();
                $tenantTheme = TenantTheme::query()->whereTenantId($tenant?->id)->first();
                $logoPath = $tenantTheme?->logo_path ?? null;
            }

            // 2. Replace simple text placeholders
            $templateProcessor->setValue('headerInfo', "SOP {$processName} - ");
            $templateProcessor->setValue('processName', $processName);
            $templateProcessor->setValue('currentDate', $request->input('currentDate', now()->format('j F Y')));
            $templateProcessor->setValue('userName', $additionalData['userName'] ?? 'System User');
            $templateProcessor->setValue('userEmail', $additionalData['userEmail'] ?? 'user@example.com');

            // 3. Handle image placeholders from base64 data
            $this->processLogoImage($templateProcessor, $logoPath, $request, $additionalData, $processName);
            $this->processMainImage($templateProcessor, $request);
            $this->processQrCode($templateProcessor, $request);

            // 4. Create a dummy PhpWord instance to provide context for HTML parsing.
            $dummyPhpWord = new PhpWord;
            $dummySection = $dummyPhpWord->addSection();

            // 5. Process the general explanation section
            $this->processGeneralExplanation($templateProcessor, $dummySection, $request);

            // 6. Process the repeating steps section
            $this->processStepExplanations($templateProcessor, $dummySection, $request, $processName);

            // 7. Generate the initial DOCX file
            $baseName = Str::slug($processName);
            $docxFileName = $request->input('fileName', $baseName) . '-' . time() . '.docx';
            $docxFilePath = storage_path('app/public/' . $docxFileName);

            $templateProcessor->saveAs($docxFilePath);

            // 8. Handle DOC conversion if requested
            if ($exportToDoc) {
                return $this->handleDocConversion($docxFilePath, $request, $baseName);
            }

            // Return DOCX file response
            return $this->createFileResponse($docxFilePath, $docxFileName);

        } catch (\Exception $e) {
            SentryEnhancedLogger::logError(
                $e,
                $request,
                'document_generation_error',
                [
                    'process_name' => $processName ?? 'Unknown',
                    'output_format' => $outputFormat,
                    'export_to_doc' => $exportToDoc ?? false,
                ]
            );

            return response()->json([
                'error' => 'Document generation failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function processLogoImage(TemplateProcessor $templateProcessor, ?string $logoPath, Request $request, array $additionalData, string $processName): void
    {
        try {
            if ($logoPath) {
                $logoURL = config('filesystems.disks.minio.url') . '/' . $logoPath;

                // Convert logo URL to base64 data
                $logoContent = file_get_contents($logoURL);
                if ($logoContent !== false) {
                    // Get the file extension from the path
                    $extension = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
                    $mimeType = match ($extension) {
                        'png' => 'image/png',
                        'jpg', 'jpeg' => 'image/jpeg',
                        'gif' => 'image/gif',
                        'webp' => 'image/webp',
                        default => 'image/png'
                    };

                    $logoBase64 = 'data:' . $mimeType . ';base64,' . base64_encode($logoContent);

                    $templateProcessor->setImageValue('logo', [
                        'path' => $logoBase64,
                        'height' => '3cm',
                        'width' => '3cm',
                        'ratio' => true,
                    ]);
                } else {
                    $templateProcessor->setValue('logo', '');
                }
            } else {
                $templateProcessor->setValue('logo', '');
            }
        } catch (\Throwable $th) {
            SentryEnhancedLogger::logError(
                $th,
                $request,
                'logo_processing_error',
                [
                    'tenant_uuid' => $additionalData['tenantUuid'] ?? null,
                    'logo_path' => $logoPath,
                    'process_name' => $processName,
                ]
            );
            $templateProcessor->setValue('logo', '');
        }
    }

    private function processMainImage(TemplateProcessor $templateProcessor, Request $request): void
    {
        $imgData = $request->input('imgData', '');
        if ($imgData) {
            $templateProcessor->setImageValue('imgData', [
                'path' => $imgData,
                'height' => '15cm',
                'width' => '15cm',
                'ratio' => true,
            ]);
        } else {
            $templateProcessor->setValue('imgData', '');
        }
    }

    private function processQrCode(TemplateProcessor $templateProcessor, Request $request): void
    {
        $qrCodeHtml = $request->input('qrCodeElement', '');
        if (preg_match('/src="data:image\/png;base64,([^"]+)"/', $qrCodeHtml, $matches)) {
            $qrCodeBase64 = $matches[1];
            $templateProcessor->setImageValue('qrCodeElement', [
                'path' => "data:image/png;base64,{$qrCodeBase64}",
                'width' => 96,
                'height' => 96
            ]);
        }
    }

    private function processGeneralExplanation(TemplateProcessor $templateProcessor, $dummySection, Request $request): void
    {
        try {
            $generalExplanationHtml = $request->input('generalExplanation', '');
            Log::info('General explanation HTML: ' . $generalExplanationHtml);
            $generalExplanationHtml = $this->cleanHtmlEntities($generalExplanationHtml ?? '');
            Log::info('General explanation HTML cleaned: ' . $generalExplanationHtml);
            
            $generalExplanationTable = $dummySection->addTable(['borderSize' => 0, 'borderColor' => 'ffffff', 'cellMargin' => 0]);
            $cell1 = $generalExplanationTable->addRow()->addCell();
            Html::addHtml($cell1, $generalExplanationHtml);
            $templateProcessor->setComplexBlock('generalExplanation', $generalExplanationTable);
        } catch (\ErrorException $exception) {
            SentryEnhancedLogger::logError(
                $exception,
                $request,
                'general_explanation_processing_error',
                [
                    'general_explanation_length' => strlen($generalExplanationHtml ?? ''),
                    'has_general_explanation' => !empty($generalExplanationHtml),
                    'process_name' => $request->input('additionalData.fileName', 'Unknown'),
                ]
            );

            $complexType = $dummySection->addText('No general explanation provided, please check the payload.');
            $templateProcessor->setComplexBlock('generalExplanation', $complexType);
        }
    }

    private function processStepExplanations(TemplateProcessor $templateProcessor, $dummySection, Request $request, string $processName): void
    {
        $stepExplanationsHtml = $request->input('stepExplanations', '');
        $stepExplanationsHtml = $this->cleanHtmlEntities($stepExplanationsHtml ?? '');

        // Create a borderless table from the dummy section to provide context
        $stepsTable = $dummySection->addTable(['borderSize' => 0, 'borderColor' => 'ffffff', 'width' => 100 * 50, 'unit' => 'pct']);

        if (!empty($stepExplanationsHtml)) {
            $dom = new DOMDocument;
            @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $stepExplanationsHtml, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            $xpath = new DOMXPath($dom);

            // Find each step, which is wrapped in a <div class="content-block">
            $stepNodes = $xpath->query('//div[contains(@class, "content-block")]');

            foreach ($stepNodes as $key => $node) {
                // Extract the title (h2) and body (the rest of the content)
                $titleNode = $xpath->query('.//h2', $node)->item(0);
                $titleText = $titleNode ? $titleNode->nodeValue : 'Step';

                // Remove the title from the node so it's not repeated in the body
                if ($titleNode) {
                    $titleNode->parentNode->removeChild($titleNode);
                }
                $bodyHtml = $dom->saveHTML($node);
                $bodyHtml = $this->cleanHtmlEntities($bodyHtml);
                $border = [
                    'borderSize' => 1,
                    'borderColor' => '000000',
                ];

                // Add a row for the step title
                $titleRow = $stepsTable->addRow();
                $titleCell = $titleRow->addCell(null, [
                    'shading' => ['fill' => 'A6A6A6', 'val' => \PhpOffice\PhpWord\Style\Shading::PATTERN_SOLID],
                ] + $border);

                // Define font and paragraph styles for the title
                $titleFont = ['bold' => true, 'size' => 16, 'color' => '000000'];
                $titleParagraph = [
                    'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
                    'spaceBefore' => 240,
                ];

                $titleCell->addText($titleText, $titleFont, $titleParagraph);

                if (!$this->isValidHTML($bodyHtml)) {
                    $bodyHtml = '<br /><p>Invalid HTML content detected.</p>';
                    SentryEnhancedLogger::logMessage(
                        'Invalid HTML detected in step explanation',
                        $request,
                        'warning',
                        [
                            'step_index' => $key,
                            'process_name' => $processName,
                            'html_length' => strlen($bodyHtml),
                            'html_preview' => substr($bodyHtml, 0, 200),
                        ]
                    );
                }

                // Add a row for the step body
                $bodyRow = $stepsTable->addRow(null, $border);
                $bodyCell = $bodyRow->addCell(null, $border);
                // This call now works because $bodyCell is part of $stepsTable, which was created from $dummySection
                Html::addHtml($bodyCell, $bodyHtml);

                // Add a row for spacing between blocks
                $stepsTable->addRow()->addCell()->addTextBreak(1);
            }
        }

        // Replace the single placeholder with our dynamically generated table of steps
        $templateProcessor->setComplexBlock('stepExplanations', $stepsTable);
    }

    private function handleDocConversion(string $docxFilePath, Request $request, string $baseName): \Illuminate\Http\Response
    {
        try {
            // Generate DOC filename
            $docFileName = str_replace('.docx', '.doc', basename($docxFilePath));
            $docFilePath = str_replace('.docx', '.doc', $docxFilePath);

            // Get conversion options from request
            $conversionOptions = [
                'method' => $request->input('conversion_method', 'auto'),
                'clean_before_conversion' => $request->boolean('clean_before_conversion', true)
            ];

            Log::info('Starting DOCX to DOC conversion', [
                'docx_path' => $docxFilePath,
                'doc_path' => $docFilePath,
                'options' => $conversionOptions
            ]);

            // Perform the conversion
            $conversionSuccess = $this->documentConverter->convertDocxToDoc(
                $docxFilePath,
                $docFilePath,
                $conversionOptions
            );

            if ($conversionSuccess && file_exists($docFilePath)) {
                Log::info('DOC conversion successful', ['doc_path' => $docFilePath]);

                // Clean up the temporary DOCX file
                if (file_exists($docxFilePath)) {
                    unlink($docxFilePath);
                }

                // Return the DOC file
                return $this->createFileResponse($docFilePath, $docFileName);
            } else {
                // If conversion fails, return the DOCX file as fallback
                Log::warning('DOC conversion failed, returning DOCX file as fallback');
                
                SentryEnhancedLogger::logMessage(
                    'DOC conversion failed, fallback to DOCX',
                    $request,
                    'warning',
                    [
                        'docx_path' => $docxFilePath,
                        'doc_path' => $docFilePath,
                        'conversion_options' => $conversionOptions
                    ]
                );

                return $this->createFileResponse($docxFilePath, basename($docxFilePath));
            }

        } catch (\Exception $e) {
            Log::error('DOC conversion error: ' . $e->getMessage());
            
            SentryEnhancedLogger::logError(
                $e,
                $request,
                'doc_conversion_error',
                [
                    'docx_path' => $docxFilePath,
                    'base_name' => $baseName
                ]
            );

            // Return DOCX as fallback
            return $this->createFileResponse($docxFilePath, basename($docxFilePath));
        }
    }

    private function createFileResponse(string $filePath, string $fileName): \Illuminate\Http\Response
    {
        $mimeType = pathinfo($fileName, PATHINFO_EXTENSION) === 'doc' 
            ? 'application/msword'
            : 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';

        return response()->download($filePath, $fileName, [
            'Content-Type' => $mimeType,
        ])->deleteFileAfterSend(true);
    }

    private function cleanHtmlEntities(string $html): string
    {
        // Your existing cleanHtmlEntities implementation
        return html_entity_decode($html, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private function isValidHTML(string $html): bool
    {
        // Your existing isValidHTML implementation
        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $result = $dom->loadHTML($html);
        libxml_clear_errors();
        return $result !== false;
    }

    /**
     * Endpoint to check available conversion tools
     */
    public function checkConversionTools()
    {
        $tools = $this->documentConverter->checkAvailableTools();
        $bestMethod = $this->documentConverter->getBestConversionMethod();

        return response()->json([
            'available_tools' => $tools,
            'recommended_method' => $bestMethod,
            'status' => 'success'
        ]);
    }

    /**
     * Endpoint to convert an existing DOCX file to DOC
     */
    public function convertExistingFile(Request $request)
    {
        $request->validate([
            'file_path' => 'required|string',
            'conversion_method' => 'sometimes|string|in:auto,soffice,rtf,pandoc'
        ]);

        try {
            $inputPath = $request->input('file_path');
            
            if (!file_exists($inputPath)) {
                return response()->json(['error' => 'File not found'], 404);
            }

            $outputPath = str_replace('.docx', '.doc', $inputPath);
            $options = [
                'method' => $request->input('conversion_method', 'auto'),
                'clean_before_conversion' => $request->boolean('clean_before_conversion', true)
            ];

            $success = $this->documentConverter->convertDocxToDoc($inputPath, $outputPath, $options);

            if ($success) {
                return response()->json([
                    'success' => true,
                    'output_path' => $outputPath,
                    'message' => 'Conversion completed successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Conversion failed'
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}