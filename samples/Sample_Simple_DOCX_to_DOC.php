<?php

include_once __DIR__ . '/../src/PhpWord/Autoloader.php';
\PhpOffice\PhpWord\Autoloader::register();

echo "Simple DOCX to DOC Conversion Example" . PHP_EOL;
echo "====================================" . PHP_EOL . PHP_EOL;

// Step 1: Create a new document
echo "Step 1: Creating a new document..." . PHP_EOL;
$phpWord = new \PhpOffice\PhpWord\PhpWord();

// Set basic document properties
$properties = $phpWord->getDocumentProperties();
$properties->setTitle('Sample Document for Format Conversion')
           ->setCreator('PHPWord Example')
           ->setDescription('This document will be saved in both DOCX and DOC formats');

// Add a section with some content
$section = $phpWord->addSection();

// Add title
$section->addTitle('Document Format Conversion', 1);
$section->addTextBreak();

// Add some basic content
$section->addText('This is a sample document created with PHPWord.');
$section->addText('It demonstrates saving the same content in both DOCX and DOC formats.');
$section->addTextBreak();

// Add some formatted text
$section->addText('Bold text example', ['bold' => true]);
$section->addText('Italic text example', ['italic' => true]);
$section->addText('Underlined text example', ['underline' => 'single']);
$section->addTextBreak();

// Add a simple table
$section->addTitle('Sample Table', 2);
$table = $section->addTable();
$table->addRow();
$table->addCell(3000)->addText('Column 1');
$table->addCell(3000)->addText('Column 2');
$table->addRow();
$table->addCell(3000)->addText('Data 1');
$table->addCell(3000)->addText('Data 2');

echo "✓ Document content created." . PHP_EOL . PHP_EOL;

// Step 2: Save as DOCX
echo "Step 2: Saving as DOCX format..." . PHP_EOL;
$word2007Writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
$docxFile = 'example.docx';
$word2007Writer->save($docxFile);

if (file_exists($docxFile)) {
    echo "✓ DOCX file created: {$docxFile}" . PHP_EOL;
} else {
    echo "✗ Failed to create DOCX file" . PHP_EOL;
    exit(1);
}

// Step 3: Save as DOC
echo "Step 3: Converting to DOC format..." . PHP_EOL;
$word2003Writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2003');
$docFile = 'example.doc';
$word2003Writer->save($docFile);

if (file_exists($docFile)) {
    echo "✓ DOC file created: {$docFile}" . PHP_EOL;
} else {
    echo "✗ Failed to create DOC file" . PHP_EOL;
    exit(1);
}

echo PHP_EOL . "Conversion completed successfully!" . PHP_EOL;
echo "Both files contain the same content in different formats:" . PHP_EOL;
echo "- example.docx (Office Open XML format)" . PHP_EOL;
echo "- example.doc (HTML-based Word 2003 compatible format)" . PHP_EOL . PHP_EOL;

// Step 4: Demonstrate reading DOCX and converting to DOC
echo "Step 4: Reading existing DOCX and converting to DOC..." . PHP_EOL;

try {
    // Load the DOCX file
    $loadedDocument = \PhpOffice\PhpWord\IOFactory::load($docxFile);
    
    // Save it as DOC with a new filename
    $convertedFile = 'converted_example.doc';
    $convertWriter = \PhpOffice\PhpWord\IOFactory::createWriter($loadedDocument, 'Word2003');
    $convertWriter->save($convertedFile);
    
    if (file_exists($convertedFile)) {
        echo "✓ Converted file created: {$convertedFile}" . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "✗ Error during conversion: " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL . "Example completed!" . PHP_EOL;
echo "Files created:" . PHP_EOL;
echo "1. example.docx - Original DOCX format" . PHP_EOL;
echo "2. example.doc - Converted DOC format" . PHP_EOL;
echo "3. converted_example.doc - DOCX loaded and converted to DOC" . PHP_EOL . PHP_EOL;

echo "You can now open these files in Microsoft Word to verify compatibility." . PHP_EOL;