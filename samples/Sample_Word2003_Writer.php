<?php

include_once __DIR__ . '/../src/PhpWord/Autoloader.php';
\PhpOffice\PhpWord\Autoloader::register();

// Creating the new document...
$phpWord = new \PhpOffice\PhpWord\PhpWord();

// Set document properties
$properties = $phpWord->getDocumentProperties();
$properties->setCreator('PHPWord')
           ->setCompany('PHPOffice')
           ->setTitle('Sample Word 2003 Document')
           ->setDescription('Sample document generated using PHPWord Word2003 writer')
           ->setSubject('PHPWord')
           ->setKeywords('Office PhpWord php');

// Adding an empty Section to the document...
$section = $phpWord->addSection();

// Add text elements
$section->addText('This is a sample document created with the PHPWord Word2003 writer.');
$section->addTextBreak();

$section->addText('This document will be saved in the .doc format compatible with Microsoft Word 2003.');
$section->addTextBreak();

// Add some formatted text
$fontStyleBold = ['bold' => true];
$section->addText('This text is bold', $fontStyleBold);
$section->addTextBreak();

$fontStyleItalic = ['italic' => true];
$section->addText('This text is italic', $fontStyleItalic);
$section->addTextBreak();

$fontStyleUnderline = ['underline' => 'single'];
$section->addText('This text is underlined', $fontStyleUnderline);
$section->addTextBreak();

// Add a title
$section->addTitle('Sample Heading', 1);
$section->addText('This is content under the heading.');
$section->addTextBreak();

// Add a text run with mixed formatting
$textRun = $section->addTextRun();
$textRun->addText('This is a text run with ');
$textRun->addText('bold', ['bold' => true]);
$textRun->addText(' and ');
$textRun->addText('italic', ['italic' => true]);
$textRun->addText(' text formatting.');
$section->addTextBreak();

// Add a simple table
$table = $section->addTable();
$table->addRow();
$table->addCell(2000)->addText('Header 1');
$table->addCell(2000)->addText('Header 2');
$table->addCell(2000)->addText('Header 3');

$table->addRow();
$table->addCell(2000)->addText('Row 1, Cell 1');
$table->addCell(2000)->addText('Row 1, Cell 2');
$table->addCell(2000)->addText('Row 1, Cell 3');

$table->addRow();
$table->addCell(2000)->addText('Row 2, Cell 1');
$table->addCell(2000)->addText('Row 2, Cell 2');
$table->addCell(2000)->addText('Row 2, Cell 3');

// Saving the document using Word2003 writer...
$writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2003');
$writer->save('sample_word2003.doc');

echo 'Sample document has been created using Word2003 writer and saved as sample_word2003.doc' . PHP_EOL;
echo 'This file can be opened with Microsoft Word 2003 or later versions.' . PHP_EOL;