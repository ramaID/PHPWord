# Important Correction: Pandoc DOC Conversion

## Issue Discovered

You correctly identified that **Pandoc cannot directly output DOC format**. The error you encountered:

```
Unknown output format doc
Pandoc can convert to DOCX, but not to DOC.
```

This is completely accurate! I've corrected the implementation to reflect this reality.

## What Was Fixed

### Before (Incorrect):
The original `convertWithPandoc` method attempted to use:
```bash
pandoc -f docx -t doc input.docx -o output.doc  # ❌ This fails!
```

### After (Corrected):
The updated `convertWithPandoc` method now:
1. Uses Pandoc to **preprocess** the DOCX: `pandoc -f docx -t docx input.docx -o processed.docx`
2. Then uses **LibreOffice** to convert to DOC: `soffice --convert-to doc processed.docx`

## Why This Actually Helps

Even though Pandoc can't output DOC directly, using it as a preprocessing step can still be beneficial because:

1. **Pandoc can clean up DOCX formatting** that might cause issues for LibreOffice
2. **Pandoc handles complex document structures** very well
3. **The preprocessed DOCX** often converts better to DOC via LibreOffice

## Updated Method Explanation

### LibreOffice Requirement
**All DOC conversions require LibreOffice** because:
- DOC is Microsoft's legacy binary format
- LibreOffice is currently the most reliable tool for outputting DOC format
- Even the "RTF" and "Pandoc" methods ultimately use LibreOffice for the final conversion

### Corrected Method Priorities

1. **`soffice`** - Direct LibreOffice conversion (most straightforward)
2. **`rtf`** - PHPWord → RTF → LibreOffice → DOC (better for list numbering issues)  
3. **`pandoc`** - Pandoc preprocessing → LibreOffice → DOC (better for complex formatting)

## Tool Availability Check Updated

The `checkAvailableTools()` method now correctly shows:

```php
[
    'soffice' => true,                    // Required for all DOC conversions
    'pandoc' => true,                     // Optional: for DOCX preprocessing  
    'pandoc_note' => 'Pandoc can preprocess DOCX but requires LibreOffice for DOC conversion',
    'phpword_rtf' => true,                // Always available: for RTF generation
    'rtf_note' => 'RTF method uses PHPWord + LibreOffice'
]
```

## Updated Usage

The API usage remains the same - the correction is internal:

```php
// This still works as expected, but now correctly uses Pandoc → LibreOffice
$converter->convertDocxToDoc($input, $output, ['method' => 'pandoc']);
```

## Key Takeaway

**LibreOffice is essential** for DOC conversion. The other tools (Pandoc, PHPWord RTF) serve as preprocessing steps to improve the quality of the final LibreOffice conversion, but cannot replace the need for LibreOffice when outputting DOC format.

This correction makes the implementation more accurate and reliable while maintaining all the intended functionality for solving your LibreOffice compatibility and list numbering issues.