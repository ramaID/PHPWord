<?php

namespace App\Services;

use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Writer\RTF;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Exception;

class DocumentConverter
{
    /**
     * Convert DOCX to DOC format with multiple fallback methods
     *
     * @param string $inputPath Path to the input DOCX file
     * @param string $outputPath Path for the output DOC file
     * @param array $options Conversion options
     * @return bool Success status
     */
    public function convertDocxToDoc(string $inputPath, string $outputPath, array $options = []): bool
    {
        $method = $options['method'] ?? 'auto';
        $cleanBeforeConversion = $options['clean_before_conversion'] ?? true;
        
        Log::info('Starting DOCX to DOC conversion', [
            'input' => $inputPath,
            'output' => $outputPath,
            'method' => $method,
            'clean_before_conversion' => $cleanBeforeConversion
        ]);

        try {
            // Clean the DOCX file before conversion to fix formatting issues
            if ($cleanBeforeConversion) {
                $cleanedPath = $this->cleanDocxForLibreOffice($inputPath);
                $inputPath = $cleanedPath ?: $inputPath;
            }

            switch ($method) {
                case 'soffice':
                    return $this->convertWithSoffice($inputPath, $outputPath);
                
                case 'rtf':
                    return $this->convertViaRtf($inputPath, $outputPath);
                
                case 'pandoc':
                    return $this->convertWithPandoc($inputPath, $outputPath);
                
                case 'auto':
                default:
                    return $this->convertWithFallback($inputPath, $outputPath);
            }
        } catch (Exception $e) {
            Log::error('Document conversion failed', [
                'error' => $e->getMessage(),
                'input' => $inputPath,
                'output' => $outputPath
            ]);
            return false;
        }
    }

    /**
     * Convert using LibreOffice with optimized parameters for better formatting
     */
    private function convertWithSoffice(string $inputPath, string $outputPath): bool
    {
        try {
            $outputDir = dirname($outputPath);
            $outputName = pathinfo($outputPath, PATHINFO_FILENAME);
            
            // Use LibreOffice with specific parameters to preserve formatting
            $command = sprintf(
                'soffice --headless --invisible --nodefault --nolockcheck --nologo --norestore ' .
                '--convert-to "doc:MS Word 97" --outdir %s %s',
                escapeshellarg($outputDir),
                escapeshellarg($inputPath)
            );

            Log::info('Executing LibreOffice conversion', ['command' => $command]);
            
            $result = Process::run($command);
            
            if ($result->successful()) {
                // LibreOffice creates a file with .doc extension, rename if needed
                $generatedFile = $outputDir . '/' . pathinfo($inputPath, PATHINFO_FILENAME) . '.doc';
                if (file_exists($generatedFile) && $generatedFile !== $outputPath) {
                    rename($generatedFile, $outputPath);
                }
                
                Log::info('LibreOffice conversion successful');
                return file_exists($outputPath);
            } else {
                Log::warning('LibreOffice conversion failed', [
                    'stdout' => $result->output(),
                    'stderr' => $result->errorOutput()
                ]);
                return false;
            }
        } catch (Exception $e) {
            Log::error('LibreOffice conversion error', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Convert via RTF format for better compatibility
     */
    private function convertViaRtf(string $inputPath, string $outputPath): bool
    {
        try {
            Log::info('Converting via RTF format');
            
            // First convert DOCX to RTF using PHPWord
            $rtfPath = str_replace('.doc', '.rtf', $outputPath);
            
            $phpWord = IOFactory::load($inputPath, 'Word2007');
            $rtfWriter = IOFactory::createWriter($phpWord, 'RTF');
            $rtfWriter->save($rtfPath);
            
            if (!file_exists($rtfPath)) {
                Log::error('Failed to create RTF file');
                return false;
            }

            // Then convert RTF to DOC using LibreOffice
            $command = sprintf(
                'soffice --headless --invisible --convert-to "doc:MS Word 97" --outdir %s %s',
                escapeshellarg(dirname($outputPath)),
                escapeshellarg($rtfPath)
            );

            $result = Process::run($command);
            
            // Clean up temporary RTF file
            if (file_exists($rtfPath)) {
                unlink($rtfPath);
            }

            if ($result->successful()) {
                $generatedFile = dirname($outputPath) . '/' . pathinfo($rtfPath, PATHINFO_FILENAME) . '.doc';
                if (file_exists($generatedFile) && $generatedFile !== $outputPath) {
                    rename($generatedFile, $outputPath);
                }
                
                Log::info('RTF conversion successful');
                return file_exists($outputPath);
            }

            return false;
        } catch (Exception $e) {
            Log::error('RTF conversion error', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Convert using Pandoc (if available)
     */
    private function convertWithPandoc(string $inputPath, string $outputPath): bool
    {
        try {
            $command = sprintf(
                'pandoc -f docx -t doc %s -o %s',
                escapeshellarg($inputPath),
                escapeshellarg($outputPath)
            );

            $result = Process::run($command);
            
            if ($result->successful()) {
                Log::info('Pandoc conversion successful');
                return file_exists($outputPath);
            }

            return false;
        } catch (Exception $e) {
            Log::error('Pandoc conversion error', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Try multiple conversion methods with fallback
     */
    private function convertWithFallback(string $inputPath, string $outputPath): bool
    {
        $methods = ['soffice', 'rtf', 'pandoc'];
        
        foreach ($methods as $method) {
            Log::info("Trying conversion method: {$method}");
            
            if ($this->convertDocxToDoc($inputPath, $outputPath, ['method' => $method, 'clean_before_conversion' => false])) {
                Log::info("Conversion successful with method: {$method}");
                return true;
            }
        }
        
        Log::error('All conversion methods failed');
        return false;
    }

    /**
     * Clean DOCX file to fix LibreOffice compatibility issues
     */
    private function cleanDocxForLibreOffice(string $inputPath): ?string
    {
        try {
            $tempPath = $inputPath . '.cleaned.docx';
            
            // Load and process the document to fix formatting issues
            $phpWord = IOFactory::load($inputPath, 'Word2007');
            
            // Process sections to fix list numbering and formatting issues
            foreach ($phpWord->getSections() as $section) {
                $this->fixSectionFormatting($section);
            }
            
            // Save the cleaned version
            $writer = IOFactory::createWriter($phpWord, 'Word2007');
            $writer->save($tempPath);
            
            Log::info('Document cleaned for LibreOffice compatibility');
            return $tempPath;
            
        } catch (Exception $e) {
            Log::warning('Failed to clean document', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Fix section formatting issues that cause LibreOffice problems
     */
    private function fixSectionFormatting($section): void
    {
        try {
            // This method can be extended to handle specific formatting issues
            // For now, it serves as a placeholder for future enhancements
            
            // You can add specific fixes here based on the formatting issues you encounter
            // For example:
            // - Fix list numbering styles
            // - Normalize paragraph spacing
            // - Clean up complex table structures
            
        } catch (Exception $e) {
            Log::warning('Failed to fix section formatting', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Create a DOC-compatible version directly using PHPWord (limited compatibility)
     */
    public function createDocCompatibleVersion(string $inputPath, string $outputPath): bool
    {
        try {
            // Load the DOCX
            $phpWord = IOFactory::load($inputPath, 'Word2007');
            
            // Create a new document with simplified formatting
            $newPhpWord = new PhpWord();
            $newSection = $newPhpWord->addSection();
            
            // Copy content with simplified formatting
            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    $this->copyElementWithSimplifiedFormatting($element, $newSection);
                }
            }
            
            // Save as DOCX first, then convert
            $tempDocx = $outputPath . '.temp.docx';
            $writer = IOFactory::createWriter($newPhpWord, 'Word2007');
            $writer->save($tempDocx);
            
            // Convert to DOC
            $success = $this->convertWithSoffice($tempDocx, $outputPath);
            
            // Clean up
            if (file_exists($tempDocx)) {
                unlink($tempDocx);
            }
            
            return $success;
            
        } catch (Exception $e) {
            Log::error('Failed to create DOC compatible version', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Copy element with simplified formatting to avoid LibreOffice issues
     */
    private function copyElementWithSimplifiedFormatting($element, $targetSection): void
    {
        try {
            // Simplified element copying logic
            // This is a basic implementation - extend based on your needs
            
            if (method_exists($element, 'getText')) {
                $text = $element->getText();
                if (!empty($text)) {
                    $targetSection->addText($text);
                }
            }
            
        } catch (Exception $e) {
            Log::warning('Failed to copy element', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Check if conversion tools are available
     */
    public function checkAvailableTools(): array
    {
        $tools = [];
        
        // Check LibreOffice
        $sofficeResult = Process::run('soffice --version');
        $tools['soffice'] = $sofficeResult->successful();
        
        // Check Pandoc
        $pandocResult = Process::run('pandoc --version');
        $tools['pandoc'] = $pandocResult->successful();
        
        // PHPWord RTF is always available
        $tools['phpword_rtf'] = true;
        
        Log::info('Available conversion tools', $tools);
        
        return $tools;
    }

    /**
     * Get the best conversion method based on available tools
     */
    public function getBestConversionMethod(): string
    {
        $tools = $this->checkAvailableTools();
        
        if ($tools['soffice']) {
            return 'soffice';
        } elseif ($tools['pandoc']) {
            return 'pandoc';
        } else {
            return 'rtf';
        }
    }
}