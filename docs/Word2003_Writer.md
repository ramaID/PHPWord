# Word2003 Writer

The Word2003 writer allows you to export PHPWord documents to the Microsoft Word 2003 .doc format. This writer generates HTML-based content with Microsoft Office-specific markup that is compatible with Word 2003 and later versions.

## Overview

The Word2003 writer creates documents that:
- Can be opened directly in Microsoft Word 2003 and later versions
- Use the .doc file extension
- Contain HTML markup with Microsoft Office namespaces for compatibility
- Support basic text formatting, tables, and document structure

## Usage

### Basic Usage

```php
<?php
require_once 'vendor/autoload.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

// Create a new document
$phpWord = new PhpWord();

// Add content to your document
$section = $phpWord->addSection();
$section->addText('Hello World!');

// Create Word2003 writer and save as .doc
$writer = IOFactory::createWriter($phpWord, 'Word2003');
$writer->save('document.doc');
?>
```

### Advanced Usage

```php
<?php
require_once 'vendor/autoload.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

// Create a new document
$phpWord = new PhpWord();

// Set document properties
$properties = $phpWord->getDocumentProperties();
$properties->setCreator('PHPWord')
           ->setTitle('Sample Word 2003 Document')
           ->setDescription('Document created with PHPWord Word2003 writer');

// Add a section
$section = $phpWord->addSection();

// Add formatted text
$section->addText('This is normal text.');
$section->addText('This is bold text.', ['bold' => true]);
$section->addText('This is italic text.', ['italic' => true]);
$section->addText('This is underlined text.', ['underline' => 'single']);

// Add a title
$section->addTitle('Main Heading', 1);

// Add a text run with mixed formatting
$textRun = $section->addTextRun();
$textRun->addText('This is a text run with ');
$textRun->addText('bold', ['bold' => true]);
$textRun->addText(' and ');
$textRun->addText('italic', ['italic' => true]);
$textRun->addText(' text.');

// Add a table
$table = $section->addTable();
$table->addRow();
$table->addCell(2000)->addText('Header 1');
$table->addCell(2000)->addText('Header 2');

$table->addRow();
$table->addCell(2000)->addText('Cell 1');
$table->addCell(2000)->addText('Cell 2');

// Save as Word 2003 document
$writer = IOFactory::createWriter($phpWord, 'Word2003');
$writer->save('advanced_document.doc');
?>
```

## Supported Features

The Word2003 writer supports the following PHPWord elements:

### Text Elements
- **Text**: Basic text with formatting (bold, italic, underline)
- **TextRun**: Mixed formatting within a single paragraph
- **TextBreak**: Line breaks
- **Title**: Headings (converted to HTML heading tags)

### Document Structure
- **Sections**: Document sections
- **Tables**: Basic table structure with rows and cells
- **Images**: Image references (paths are preserved in HTML)

### Formatting
- **Bold text**
- **Italic text**
- **Underlined text**
- **Font families** (via CSS)
- **Document properties** (title appears in HTML title tag)

## Technical Details

### File Format
The Word2003 writer generates HTML files with:
- Microsoft Office XML namespaces
- Word-specific meta tags
- CSS styles compatible with Word 2003
- Proper DOCTYPE declaration for Word compatibility

### Generated HTML Structure
```html
<!DOCTYPE html>
<html xmlns:v="urn:schemas-microsoft-com:vml"
xmlns:o="urn:schemas-microsoft-com:office:office"
xmlns:w="urn:schemas-microsoft-com:office:word"
xmlns:m="http://schemas.microsoft.com/office/2004/12/omml"
xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="ProgId" content="Word.Document">
    <meta name="Generator" content="Microsoft Word">
    <!-- CSS styles -->
</head>
<body>
    <div class="Section1">
        <!-- Document content -->
    </div>
</body>
</html>
```

## Limitations

- **Images**: Image files are referenced by path but not embedded
- **Complex formatting**: Advanced Word features are not supported
- **Binary compatibility**: Not true .doc binary format (uses HTML approach)
- **Styles**: Limited to basic formatting options

## Compatibility

The generated .doc files are compatible with:
- Microsoft Word 2003 and later
- Microsoft Office Online
- LibreOffice Writer
- OpenOffice Writer
- Most other word processors that support HTML-based .doc files

## Error Handling

The Word2003 writer follows the same error handling patterns as other PHPWord writers:

```php
try {
    $writer = IOFactory::createWriter($phpWord, 'Word2003');
    $writer->save('document.doc');
} catch (Exception $e) {
    echo 'Error saving document: ' . $e->getMessage();
}
```

## Performance Considerations

- The Word2003 writer is fast for simple to moderate complexity documents
- Large tables or documents with many elements may require more processing time
- Memory usage is generally lower than ZIP-based formats like Word2007

## Examples

See `samples/Sample_Word2003_Writer.php` for a complete working example demonstrating various features of the Word2003 writer.