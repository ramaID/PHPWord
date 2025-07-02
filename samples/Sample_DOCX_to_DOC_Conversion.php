<?php

include_once __DIR__ . '/../src/PhpWord/Autoloader.php';
\PhpOffice\PhpWord\Autoloader::register();

echo "PHPWord DOCX to DOC Conversion Example" . PHP_EOL;
echo "=====================================" . PHP_EOL . PHP_EOL;

// Creating the new document...
$phpWord = new \PhpOffice\PhpWord\PhpWord();

// Set document properties
$properties = $phpWord->getDocumentProperties();
$properties->setCreator('PHPWord Conversion Example')
           ->setCompany('PHPOffice')
           ->setTitle('Document Format Conversion Demo')
           ->setDescription('Example showing conversion between DOCX and DOC formats')
           ->setSubject('Format Conversion')
           ->setKeywords('PHPWord docx doc conversion');

echo "Creating document content..." . PHP_EOL;

// Adding an empty Section to the document...
$section = $phpWord->addSection();

// Add document title
$titleFont = ['name' => 'Arial', 'size' => 16, 'bold' => true, 'color' => '1F4E79'];
$section->addText('Document Format Conversion Example', $titleFont);
$section->addTextBreak(2);

// Add introduction text
$normalFont = ['name' => 'Calibri', 'size' => 11];
$section->addText('This document demonstrates the conversion between DOCX and DOC formats using PHPWord.', $normalFont);
$section->addTextBreak();

$section->addText('Original document created: ' . date('Y-m-d H:i:s'), $normalFont);
$section->addTextBreak(2);

// Add formatted content
$section->addTitle('Text Formatting Examples', 1);

$section->addText('This is normal text in Calibri font.', $normalFont);
$section->addTextBreak();

$boldFont = ['name' => 'Calibri', 'size' => 11, 'bold' => true];
$section->addText('This text is bold.', $boldFont);

$italicFont = ['name' => 'Calibri', 'size' => 11, 'italic' => true];
$section->addText('This text is italic.', $italicFont);

$underlineFont = ['name' => 'Calibri', 'size' => 11, 'underline' => 'single'];
$section->addText('This text is underlined.', $underlineFont);

$colorFont = ['name' => 'Calibri', 'size' => 11, 'color' => 'FF0000'];
$section->addText('This text is red.', $colorFont);

$section->addTextBreak();

// Add a text run with mixed formatting
$textRun = $section->addTextRun($normalFont);
$textRun->addText('This paragraph contains ');
$textRun->addText('bold', ['bold' => true]);
$textRun->addText(', ');
$textRun->addText('italic', ['italic' => true]);
$textRun->addText(', and ');
$textRun->addText('underlined', ['underline' => 'single']);
$textRun->addText(' text within the same paragraph.');
$section->addTextBreak(2);

// Add a table
$section->addTitle('Table Example', 1);

$tableStyle = [
    'borderColor' => '006699',
    'borderSize'  => 6,
    'cellMargin'  => 50
];
$cellRowSpan = ['vMerge' => 'restart', 'bgcolor' => 'F2F2F2'];
$cellRowContinue = ['vMerge' => 'continue'];
$cellColSpan = ['gridSpan' => 2, 'bgcolor' => 'F2F2F2'];

$table = $section->addTable($tableStyle);

// Header row
$table->addRow(900);
$table->addCell(2000, $cellColSpan)->addText('Product Information', ['bold' => true]);
$table->addCell(2000, ['bgcolor' => 'F2F2F2'])->addText('Price', ['bold' => true]);
$table->addCell(2000, ['bgcolor' => 'F2F2F2'])->addText('Stock', ['bold' => true]);

// Data rows
$table->addRow();
$table->addCell(1000)->addText('Product Name');
$table->addCell(1000)->addText('Widget A');
$table->addCell(2000)->addText('$25.99');
$table->addCell(2000)->addText('50');

$table->addRow();
$table->addCell(1000)->addText('');
$table->addCell(1000)->addText('Widget B');
$table->addCell(2000)->addText('$35.99');
$table->addCell(2000)->addText('25');

$table->addRow();
$table->addCell(1000)->addText('Category');
$table->addCell(1000)->addText('Electronics');
$table->addCell(2000)->addText('Total Items:');
$table->addCell(2000)->addText('75');

$section->addTextBreak(2);

// Add list example
$section->addTitle('List Example', 1);

$listItems = [
    'First list item with normal text',
    'Second list item with formatting',
    'Third list item',
    'Fourth list item'
];

foreach ($listItems as $index => $item) {
    $listFont = ($index == 1) ? ['bold' => true] : $normalFont;
    $section->addListItem($item, 0, $listFont);
}

$section->addTextBreak(2);

// Add footer information
$section->addTitle('Conversion Information', 1);
$section->addText('This document will be saved in both DOCX and DOC formats to demonstrate format conversion capabilities.', $normalFont);

echo "Saving document as DOCX format..." . PHP_EOL;

// Save as DOCX (Word 2007 format)
$writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
$docxFile = 'sample_document.docx';
$writer->save($docxFile);

if (file_exists($docxFile)) {
    echo "✓ DOCX file saved successfully: {$docxFile} (" . formatBytes(filesize($docxFile)) . ")" . PHP_EOL;
} else {
    echo "✗ Failed to save DOCX file" . PHP_EOL;
}

echo "Converting to DOC format using Word2003 writer..." . PHP_EOL;

// Save the same document as DOC (Word 2003 format)
$word2003Writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2003');
$docFile = 'sample_document.doc';
$word2003Writer->save($docFile);

if (file_exists($docFile)) {
    echo "✓ DOC file saved successfully: {$docFile} (" . formatBytes(filesize($docFile)) . ")" . PHP_EOL;
} else {
    echo "✗ Failed to save DOC file" . PHP_EOL;
}

echo PHP_EOL . "Conversion completed!" . PHP_EOL;
echo "Files created:" . PHP_EOL;
echo "- {$docxFile} (Word 2007+ format)" . PHP_EOL;
echo "- {$docFile} (Word 2003 format)" . PHP_EOL . PHP_EOL;

// Additional example: Reading DOCX and converting to DOC
echo "Additional Example: Reading existing DOCX and converting to DOC" . PHP_EOL;
echo "================================================================" . PHP_EOL;

try {
    // Read the DOCX file we just created
    echo "Reading DOCX file..." . PHP_EOL;
    $phpWordFromFile = \PhpOffice\PhpWord\IOFactory::load($docxFile);
    
    // Save it as DOC with a different name
    $convertedDocFile = 'converted_from_docx.doc';
    $converterWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWordFromFile, 'Word2003');
    $converterWriter->save($convertedDocFile);
    
    if (file_exists($convertedDocFile)) {
        echo "✓ Converted DOC file saved: {$convertedDocFile} (" . formatBytes(filesize($convertedDocFile)) . ")" . PHP_EOL;
    } else {
        echo "✗ Failed to convert DOCX to DOC" . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "Error during conversion: " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL . "Format Comparison:" . PHP_EOL;
echo "==================" . PHP_EOL;

if (file_exists($docxFile) && file_exists($docFile)) {
    $docxSize = filesize($docxFile);
    $docSize = filesize($docFile);
    
    echo "DOCX file size: " . formatBytes($docxSize) . PHP_EOL;
    echo "DOC file size:  " . formatBytes($docSize) . PHP_EOL;
    
    $ratio = round(($docSize / $docxSize) * 100, 1);
    echo "DOC is {$ratio}% the size of DOCX" . PHP_EOL;
}

echo PHP_EOL . "Note: The DOC format uses HTML-based content which is compatible with" . PHP_EOL;
echo "Word 2003 and later versions. Both files contain the same content but" . PHP_EOL;
echo "use different internal representations." . PHP_EOL;

/**
 * Helper function to format file sizes
 */
function formatBytes($size, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    
    return round($size, $precision) . ' ' . $units[$i];
}