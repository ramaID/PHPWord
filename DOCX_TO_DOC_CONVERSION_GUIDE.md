# DOCX to DOC Conversion Feature Guide

This guide explains how to use the new DOCX to DOC conversion functionality to address LibreOffice compatibility issues and list numbering problems.

## Overview

The new conversion system provides multiple methods to convert DOCX files to DOC format:

1. **LibreOffice (soffice)** - Direct conversion with optimized parameters
2. **RTF Intermediate** - Converts DOCX → RTF → DOC for better compatibility  
3. **Pandoc + LibreOffice** - Preprocesses DOCX with Pandoc, then converts to DOC with LibreOffice
4. **Auto Fallback** - Tries multiple methods automatically

**Important**: LibreOffice is required for all DOC conversions since DOC is a legacy Microsoft format that only LibreOffice can reliably output.

## Setup Instructions

### 1. Add the Service Provider

Add the service provider to your `config/app.php`:

```php
'providers' => [
    // ... other providers
    App\Providers\DocumentConverterServiceProvider::class,
],
```

### 2. Install Dependencies

Ensure you have the required system dependencies:

```bash
# Install LibreOffice (for soffice command)
sudo apt-get update
sudo apt-get install libreoffice

# Optional: Install Pandoc for additional conversion method
sudo apt-get install pandoc
```

### 3. Update Your Existing Controller

Replace your existing document generation code with the enhanced version, or integrate the DOC conversion functionality into your existing controller.

## Usage Examples

### Basic Usage - Convert During Generation

```php
// In your request, add these parameters to generate DOC directly:
$request = [
    'intent' => 'doc',                          // Request DOC output
    'conversion_method' => 'auto',              // Use automatic method selection
    'clean_before_conversion' => true,          // Clean DOCX before conversion
    // ... your other document data
];

// Your existing generateDocument method will now return DOC format
$response = $controller->generateDocument($request);
```

### Convert Existing DOCX Files

```php
// Convert an existing DOCX file to DOC
use App\Services\DocumentConverter;

$converter = app(DocumentConverter::class);

$success = $converter->convertDocxToDoc(
    '/path/to/input.docx',
    '/path/to/output.doc',
    [
        'method' => 'soffice',
        'clean_before_conversion' => true
    ]
);

if ($success) {
    echo "Conversion successful!";
} else {
    echo "Conversion failed!";
}
```

### Check Available Tools

```php
use App\Services\DocumentConverter;

$converter = app(DocumentConverter::class);
$tools = $converter->checkAvailableTools();

// Returns:
// [
//     'soffice' => true,
//     'pandoc' => false,
//     'phpword_rtf' => true
// ]

$bestMethod = $converter->getBestConversionMethod(); // Returns: 'soffice'
```

## API Endpoints

### 1. Generate Document with DOC Conversion

```http
POST /api/generate-document
Content-Type: application/json

{
    "intent": "doc",
    "conversion_method": "auto",
    "clean_before_conversion": true,
    "additionalData": {
        "fileName": "my-process",
        "userName": "John Doe",
        "userEmail": "john@example.com"
    },
    "generalExplanation": "<p>Process overview...</p>",
    "stepExplanations": "<div class='content-block'>...</div>"
}
```

### 2. Check Conversion Tools

```http
GET /api/check-conversion-tools

Response:
{
    "available_tools": {
        "soffice": true,
        "pandoc": false,
        "phpword_rtf": true
    },
    "recommended_method": "soffice",
    "status": "success"
}
```

### 3. Convert Existing File

```http
POST /api/convert-existing-file
Content-Type: application/json

{
    "file_path": "/storage/app/public/document.docx",
    "conversion_method": "rtf",
    "clean_before_conversion": true
}
```

## Conversion Methods Explained

### 1. LibreOffice (soffice) Method

**Best for**: Most documents with complex formatting
**Pros**: Handles most formatting well, widely available
**Cons**: Can have issues with some DOCX features

```php
$converter->convertDocxToDoc($input, $output, ['method' => 'soffice']);
```

### 2. RTF Intermediate Method

**Best for**: Documents with list numbering issues
**Pros**: Better compatibility with older Word versions
**Cons**: May lose some complex formatting

```php
$converter->convertDocxToDoc($input, $output, ['method' => 'rtf']);
```

### 3. Pandoc Method

**Best for**: Documents with complex structures or formatting issues
**Pros**: Preprocesses DOCX to fix formatting issues before LibreOffice conversion
**Cons**: Requires additional installation, still needs LibreOffice for final DOC conversion
**Note**: Pandoc cannot directly output DOC format - it preprocesses DOCX then uses LibreOffice

```php
$converter->convertDocxToDoc($input, $output, ['method' => 'pandoc']);
```

### 4. Auto Fallback Method

**Best for**: Production environments
**Pros**: Tries multiple methods automatically
**Cons**: May take longer if first methods fail

```php
$converter->convertDocxToDoc($input, $output, ['method' => 'auto']);
```

## Configuration Options

### Request Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `intent` | string | 'docx' | Set to 'doc' to generate DOC format |
| `convert_to_doc` | boolean | false | Alternative way to request DOC conversion |
| `conversion_method` | string | 'auto' | Conversion method: 'auto', 'soffice', 'rtf', 'pandoc' |
| `clean_before_conversion` | boolean | true | Clean DOCX before conversion to fix compatibility issues |

### DocumentConverter Options

```php
$options = [
    'method' => 'auto',                    // Conversion method
    'clean_before_conversion' => true,     // Pre-process DOCX for better compatibility
];
```

## Troubleshooting

### Common Issues and Solutions

#### 1. LibreOffice Not Found

**Error**: `soffice: command not found`

**Solution**:
```bash
# Ubuntu/Debian
sudo apt-get install libreoffice

# CentOS/RHEL
sudo yum install libreoffice

# macOS
brew install --cask libreoffice
```

#### 2. Permission Issues

**Error**: `Permission denied`

**Solution**:
```bash
# Ensure LibreOffice can write to temp directories
sudo chmod 755 /tmp
sudo chown www-data:www-data /storage/app/public/
```

#### 3. List Numbering Issues

**Problem**: List numbering breaks in LibreOffice

**Solution**: Use RTF method or enable cleaning:
```php
$converter->convertDocxToDoc($input, $output, [
    'method' => 'rtf',
    'clean_before_conversion' => true
]);
```

#### 4. Conversion Fails

**Problem**: All conversion methods fail

**Solution**: Check logs and use fallback:
```php
// Check what tools are available
$tools = $converter->checkAvailableTools();
Log::info('Available tools', $tools);

// Use createDocCompatibleVersion as last resort
$success = $converter->createDocCompatibleVersion($input, $output);
```

#### 5. Pandoc DOC Output Error

**Error**: `Unknown output format doc` when using Pandoc

**Explanation**: This is expected! Pandoc cannot directly output DOC format. Our implementation uses Pandoc to preprocess the DOCX file and then uses LibreOffice for the final DOC conversion.

**Solution**: This is handled automatically by the `pandoc` method - no action needed.

### Debug Mode

Enable detailed logging by setting log level to debug in your `.env`:

```env
LOG_LEVEL=debug
```

This will log detailed information about the conversion process.

## Best Practices

### 1. Choose the Right Method

- **For most documents**: Use `auto` method
- **For list numbering issues**: Use `rtf` method
- **For production**: Always enable `clean_before_conversion`

### 2. Error Handling

Always implement proper error handling:

```php
try {
    $success = $converter->convertDocxToDoc($input, $output, $options);
    
    if (!$success) {
        // Log error and provide fallback
        Log::warning('DOC conversion failed, providing DOCX instead');
        return response()->download($input);
    }
    
    return response()->download($output);
} catch (Exception $e) {
    Log::error('Conversion error: ' . $e->getMessage());
    return response()->json(['error' => 'Conversion failed'], 500);
}
```

### 3. Performance Optimization

- Use `clean_before_conversion => false` if you're sure your DOCX is compatible
- Cache conversion results when possible
- Use background jobs for large documents

### 4. Testing

Test your documents with different methods to find the best one:

```php
$methods = ['soffice', 'rtf', 'pandoc'];
foreach ($methods as $method) {
    $output = "/tmp/test_{$method}.doc";
    $success = $converter->convertDocxToDoc($input, $output, ['method' => $method]);
    if ($success) {
        echo "Method {$method}: SUCCESS\n";
    } else {
        echo "Method {$method}: FAILED\n";
    }
}
```

## Advanced Features

### Custom Formatting Fixes

You can extend the `fixSectionFormatting` method in `DocumentConverter` to handle specific formatting issues:

```php
private function fixSectionFormatting($section): void
{
    try {
        // Add custom fixes here
        // Example: Fix list numbering
        foreach ($section->getElements() as $element) {
            if ($element instanceof \PhpOffice\PhpWord\Element\ListItem) {
                // Apply specific list formatting fixes
                $this->fixListItemFormatting($element);
            }
        }
    } catch (Exception $e) {
        Log::warning('Failed to fix section formatting', ['error' => $e->getMessage()]);
    }
}
```

### Batch Conversion

For converting multiple files:

```php
$files = ['/path/to/file1.docx', '/path/to/file2.docx'];
$results = [];

foreach ($files as $file) {
    $outputFile = str_replace('.docx', '.doc', $file);
    $success = $converter->convertDocxToDoc($file, $outputFile);
    $results[$file] = $success;
}
```

## Integration with Your Existing Code

To integrate with your existing document generation code, modify your controller method like this:

```php
// After your existing template processing...
$templateProcessor->saveAs($filePath);

// Add DOC conversion if requested
$intent = $request->input('intent', 'docx');
if ($intent === 'doc') {
    $docPath = str_replace('.docx', '.doc', $filePath);
    $converter = app(DocumentConverter::class);
    
    $success = $converter->convertDocxToDoc($filePath, $docPath, [
        'method' => $request->input('conversion_method', 'auto'),
        'clean_before_conversion' => $request->boolean('clean_before_conversion', true)
    ]);
    
    if ($success) {
        unlink($filePath); // Remove DOCX file
        $filePath = $docPath; // Use DOC file instead
    }
}

return response()->download($filePath);
```

This will provide you with a robust solution for converting DOCX to DOC format while addressing the LibreOffice compatibility issues you mentioned.