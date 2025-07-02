<?php

include_once __DIR__ . '/../src/PhpWord/Autoloader.php';
\PhpOffice\PhpWord\Autoloader::register();

/**
 * Batch DOCX to DOC Converter
 * 
 * This utility converts multiple DOCX files to DOC format using PHPWord
 */

class DocxToDocConverter 
{
    private $inputDirectory;
    private $outputDirectory;
    private $converted = 0;
    private $failed = 0;
    private $errors = [];

    public function __construct($inputDir = 'input', $outputDir = 'output')
    {
        $this->inputDirectory = $inputDir;
        $this->outputDirectory = $outputDir;
    }

    /**
     * Convert all DOCX files in the input directory to DOC format
     */
    public function convertAll()
    {
        echo "PHPWord Batch DOCX to DOC Converter" . PHP_EOL;
        echo "===================================" . PHP_EOL . PHP_EOL;

        // Check if input directory exists
        if (!is_dir($this->inputDirectory)) {
            echo "Creating input directory: {$this->inputDirectory}" . PHP_EOL;
            mkdir($this->inputDirectory, 0755, true);
            $this->createSampleFiles();
        }

        // Create output directory if it doesn't exist
        if (!is_dir($this->outputDirectory)) {
            echo "Creating output directory: {$this->outputDirectory}" . PHP_EOL;
            mkdir($this->outputDirectory, 0755, true);
        }

        // Find all DOCX files
        $docxFiles = $this->findDocxFiles();
        
        if (empty($docxFiles)) {
            echo "No DOCX files found in '{$this->inputDirectory}' directory." . PHP_EOL;
            echo "Please place DOCX files in the input directory and run again." . PHP_EOL;
            return;
        }

        echo "Found " . count($docxFiles) . " DOCX file(s) to convert:" . PHP_EOL;
        foreach ($docxFiles as $file) {
            echo "- " . basename($file) . PHP_EOL;
        }
        echo PHP_EOL;

        // Convert each file
        $startTime = microtime(true);
        
        foreach ($docxFiles as $docxFile) {
            $this->convertFile($docxFile);
        }

        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);

        // Show results
        $this->showResults($duration);
    }

    /**
     * Find all DOCX files in the input directory
     */
    private function findDocxFiles()
    {
        $files = [];
        $pattern = $this->inputDirectory . '/*.docx';
        $matches = glob($pattern);
        
        if ($matches) {
            $files = array_merge($files, $matches);
        }

        // Also check for uppercase extension
        $pattern = $this->inputDirectory . '/*.DOCX';
        $matches = glob($pattern);
        
        if ($matches) {
            $files = array_merge($files, $matches);
        }

        return $files;
    }

    /**
     * Convert a single DOCX file to DOC format
     */
    private function convertFile($docxFile)
    {
        $filename = basename($docxFile);
        $baseFilename = pathinfo($filename, PATHINFO_FILENAME);
        $outputFile = $this->outputDirectory . '/' . $baseFilename . '.doc';

        echo "Converting: {$filename} → {$baseFilename}.doc ... ";

        try {
            // Load the DOCX file
            $phpWord = \PhpOffice\PhpWord\IOFactory::load($docxFile);
            
            // Create Word2003 writer and save as DOC
            $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2003');
            $writer->save($outputFile);
            
            if (file_exists($outputFile)) {
                $this->converted++;
                $inputSize = filesize($docxFile);
                $outputSize = filesize($outputFile);
                echo "✓ Success (" . $this->formatBytes($inputSize) . " → " . $this->formatBytes($outputSize) . ")" . PHP_EOL;
            } else {
                $this->failed++;
                $this->errors[] = "{$filename}: Output file was not created";
                echo "✗ Failed - Output file not created" . PHP_EOL;
            }
            
        } catch (Exception $e) {
            $this->failed++;
            $this->errors[] = "{$filename}: " . $e->getMessage();
            echo "✗ Failed - " . $e->getMessage() . PHP_EOL;
        }
    }

    /**
     * Show conversion results
     */
    private function showResults($duration)
    {
        echo PHP_EOL . "Conversion Results:" . PHP_EOL;
        echo "==================" . PHP_EOL;
        echo "Successfully converted: {$this->converted} file(s)" . PHP_EOL;
        echo "Failed conversions: {$this->failed} file(s)" . PHP_EOL;
        echo "Total processing time: {$duration} seconds" . PHP_EOL;

        if (!empty($this->errors)) {
            echo PHP_EOL . "Errors encountered:" . PHP_EOL;
            foreach ($this->errors as $error) {
                echo "- {$error}" . PHP_EOL;
            }
        }

        if ($this->converted > 0) {
            echo PHP_EOL . "Converted files are available in: {$this->outputDirectory}/" . PHP_EOL;
        }
    }

    /**
     * Create sample DOCX files for demonstration
     */
    private function createSampleFiles()
    {
        echo "Creating sample DOCX files for demonstration..." . PHP_EOL;

        // Create sample file 1
        $phpWord1 = new \PhpOffice\PhpWord\PhpWord();
        $properties1 = $phpWord1->getDocumentProperties();
        $properties1->setTitle('Sample Document 1');
        
        $section1 = $phpWord1->addSection();
        $section1->addTitle('Sample Document 1', 1);
        $section1->addText('This is the first sample document for batch conversion.');
        $section1->addText('It contains basic formatting and text content.');
        $section1->addText('Bold text example', ['bold' => true]);
        
        $writer1 = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord1, 'Word2007');
        $writer1->save($this->inputDirectory . '/sample1.docx');

        // Create sample file 2
        $phpWord2 = new \PhpOffice\PhpWord\PhpWord();
        $properties2 = $phpWord2->getDocumentProperties();
        $properties2->setTitle('Sample Document 2');
        
        $section2 = $phpWord2->addSection();
        $section2->addTitle('Sample Document 2', 1);
        $section2->addText('This is the second sample document.');
        
        $table = $section2->addTable();
        $table->addRow();
        $table->addCell(3000)->addText('Header 1');
        $table->addCell(3000)->addText('Header 2');
        $table->addRow();
        $table->addCell(3000)->addText('Data 1');
        $table->addCell(3000)->addText('Data 2');
        
        $writer2 = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord2, 'Word2007');
        $writer2->save($this->inputDirectory . '/sample2.docx');

        // Create sample file 3
        $phpWord3 = new \PhpOffice\PhpWord\PhpWord();
        $properties3 = $phpWord3->getDocumentProperties();
        $properties3->setTitle('Sample Document 3');
        
        $section3 = $phpWord3->addSection();
        $section3->addTitle('Sample Document 3', 1);
        $section3->addText('This is the third sample document with mixed formatting.');
        
        $textRun = $section3->addTextRun();
        $textRun->addText('This paragraph has ');
        $textRun->addText('bold', ['bold' => true]);
        $textRun->addText(', ');
        $textRun->addText('italic', ['italic' => true]);
        $textRun->addText(', and ');
        $textRun->addText('underlined', ['underline' => 'single']);
        $textRun->addText(' text formatting.');
        
        $writer3 = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord3, 'Word2007');
        $writer3->save($this->inputDirectory . '/sample3.docx');

        echo "Created 3 sample DOCX files in {$this->inputDirectory}/" . PHP_EOL . PHP_EOL;
    }

    /**
     * Format file sizes in human-readable format
     */
    private function formatBytes($size, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }
        
        return round($size, $precision) . ' ' . $units[$i];
    }
}

// Usage example
$converter = new DocxToDocConverter();
$converter->convertAll();

echo PHP_EOL . "Usage Instructions:" . PHP_EOL;
echo "==================" . PHP_EOL;
echo "1. Place your DOCX files in the 'input/' directory" . PHP_EOL;
echo "2. Run this script: php " . basename(__FILE__) . PHP_EOL;
echo "3. Converted DOC files will be saved in the 'output/' directory" . PHP_EOL;
echo "4. You can customize input/output directories by modifying the script" . PHP_EOL . PHP_EOL;

echo "Example usage with custom directories:" . PHP_EOL;
echo "\$converter = new DocxToDocConverter('my_docx_files', 'converted_docs');" . PHP_EOL;
echo "\$converter->convertAll();" . PHP_EOL;