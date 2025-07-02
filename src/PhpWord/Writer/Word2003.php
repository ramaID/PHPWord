<?php

/**
 * This file is part of PHPWord - A pure PHP library for reading and writing
 * word processing documents.
 *
 * PHPWord is free software distributed under the terms of the GNU Lesser
 * General Public License version 3 as published by the Free Software Foundation.
 *
 * For the full copyright and license information, please read the LICENSE
 * file that was distributed with this source code. For the full list of
 * contributors, visit https://github.com/PHPOffice/PHPWord/contributors.
 *
 * @see         https://github.com/PHPOffice/PHPWord
 *
 * @license     http://www.gnu.org/licenses/lgpl.txt LGPL version 3
 */

namespace PhpOffice\PhpWord\Writer;

use PhpOffice\PhpWord\PhpWord;

/**
 * Word2003 writer.
 * 
 * Generates HTML-based content compatible with Microsoft Word 2003 .doc format.
 * This approach allows Word 2003 and later versions to open the files correctly.
 */
class Word2003 extends AbstractWriter implements WriterInterface
{
    /**
     * Create new Word2003 writer.
     */
    public function __construct(?PhpWord $phpWord = null)
    {
        // Assign PhpWord
        $this->setPhpWord($phpWord);

        // Create parts
        $this->parts = [
            'Header' => '',
            'Document' => '',
            'Footer' => ''
        ];

        foreach (array_keys($this->parts) as $partName) {
            $partClass = static::class . '\\Part\\' . $partName;
            if (class_exists($partClass)) {
                /** @var Word2003\Part\AbstractPart $part Type hint */
                $part = new $partClass();
                $part->setParentWriter($this);
                $this->writerParts[strtolower($partName)] = $part;
            }
        }
    }

    /**
     * Save document by name.
     */
    public function save(string $filename): void
    {
        $content = $this->generateContent();
        $this->writeFile($this->openFile($filename), $content);
    }

    /**
     * Generate the complete HTML content for Word2003 compatibility.
     *
     * @return string
     */
    private function generateContent(): string
    {
        $phpWord = $this->getPhpWord();
        
        $content = '';
        
        // HTML DOCTYPE and opening tags for Word 2003 compatibility
        $content .= '<!DOCTYPE html>' . PHP_EOL;
        $content .= '<html xmlns:v="urn:schemas-microsoft-com:vml"' . PHP_EOL;
        $content .= 'xmlns:o="urn:schemas-microsoft-com:office:office"' . PHP_EOL;
        $content .= 'xmlns:w="urn:schemas-microsoft-com:office:word"' . PHP_EOL;
        $content .= 'xmlns:m="http://schemas.microsoft.com/office/2004/12/omml"' . PHP_EOL;
        $content .= 'xmlns="http://www.w3.org/TR/REC-html40">' . PHP_EOL;
        
        // Head section
        $content .= '<head>' . PHP_EOL;
        $content .= '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">' . PHP_EOL;
        $content .= '<meta name="ProgId" content="Word.Document">' . PHP_EOL;
        $content .= '<meta name="Generator" content="Microsoft Word">' . PHP_EOL;
        $content .= '<meta name="Originator" content="Microsoft Word">' . PHP_EOL;
        
        // Document properties
        if ($phpWord->getDocumentProperties()->getTitle()) {
            $content .= '<title>' . htmlspecialchars($phpWord->getDocumentProperties()->getTitle()) . '</title>' . PHP_EOL;
        }
        
        // CSS styles for Word compatibility
        $content .= $this->generateStyles();
        
        $content .= '</head>' . PHP_EOL;
        
        // Body section
        $content .= '<body lang="EN-US" style="tab-interval:36.0pt">' . PHP_EOL;
        
        // Document content
        $content .= $this->generateDocumentContent();
        
        $content .= '</body>' . PHP_EOL;
        $content .= '</html>' . PHP_EOL;
        
        return $content;
    }

    /**
     * Generate CSS styles for Word 2003 compatibility.
     *
     * @return string
     */
    private function generateStyles(): string
    {
        $content = '<style>' . PHP_EOL;
        $content .= '<!--' . PHP_EOL;
        $content .= '@page Section1 {' . PHP_EOL;
        $content .= '    size: 8.5in 11.0in;' . PHP_EOL;
        $content .= '    margin: 1.0in 1.0in 1.0in 1.0in;' . PHP_EOL;
        $content .= '    mso-header-margin: 0.5in;' . PHP_EOL;
        $content .= '    mso-footer-margin: 0.5in;' . PHP_EOL;
        $content .= '    mso-paper-source: 0;' . PHP_EOL;
        $content .= '}' . PHP_EOL;
        $content .= 'div.Section1 { page: Section1; }' . PHP_EOL;
        $content .= 'p.MsoNormal {' . PHP_EOL;
        $content .= '    margin: 0in 0in 0pt;' . PHP_EOL;
        $content .= '    font-size: 12.0pt;' . PHP_EOL;
        $content .= '    font-family: "Times New Roman", serif;' . PHP_EOL;
        $content .= '}' . PHP_EOL;
        $content .= 'p {' . PHP_EOL;
        $content .= '    margin: 0in 0in 10pt;' . PHP_EOL;
        $content .= '    font-size: 12.0pt;' . PHP_EOL;
        $content .= '    font-family: "Times New Roman", serif;' . PHP_EOL;
        $content .= '}' . PHP_EOL;
        $content .= '-->' . PHP_EOL;
        $content .= '</style>' . PHP_EOL;
        
        return $content;
    }

    /**
     * Generate the main document content.
     *
     * @return string
     */
    private function generateDocumentContent(): string
    {
        $phpWord = $this->getPhpWord();
        $content = '';
        
        $content .= '<div class="Section1">' . PHP_EOL;
        
        // Process each section
        foreach ($phpWord->getSections() as $section) {
            $content .= $this->processSectionElements($section->getElements());
        }
        
        $content .= '</div>' . PHP_EOL;
        
        return $content;
    }

    /**
     * Process section elements and convert them to HTML.
     *
     * @param array $elements
     * @return string
     */
    private function processSectionElements(array $elements): string
    {
        $content = '';
        
        foreach ($elements as $element) {
            $content .= $this->processElement($element);
        }
        
        return $content;
    }

    /**
     * Process individual elements and convert them to HTML.
     *
     * @param mixed $element
     * @return string
     */
    private function processElement($element): string
    {
        $className = get_class($element);
        $content = '';
        
        switch ($className) {
            case 'PhpOffice\PhpWord\Element\Text':
                $content .= $this->processText($element);
                break;
                
            case 'PhpOffice\PhpWord\Element\TextRun':
                $content .= $this->processTextRun($element);
                break;
                
            case 'PhpOffice\PhpWord\Element\TextBreak':
                $content .= '<br>' . PHP_EOL;
                break;
                
            case 'PhpOffice\PhpWord\Element\Table':
                $content .= $this->processTable($element);
                break;
                
            case 'PhpOffice\PhpWord\Element\Image':
                $content .= $this->processImage($element);
                break;
                
            case 'PhpOffice\PhpWord\Element\Title':
                $content .= $this->processTitle($element);
                break;
                
            default:
                // Handle other element types or fall back to text content
                if (method_exists($element, 'getText')) {
                    $content .= '<p>' . htmlspecialchars($element->getText()) . '</p>' . PHP_EOL;
                } elseif (method_exists($element, 'getElements')) {
                    $content .= $this->processSectionElements($element->getElements());
                }
                break;
        }
        
        return $content;
    }

    /**
     * Process Text element.
     *
     * @param \PhpOffice\PhpWord\Element\Text $element
     * @return string
     */
    private function processText($element): string
    {
        $text = htmlspecialchars($element->getText());
        $style = $element->getFontStyle();
        
        $openTags = '';
        $closeTags = '';
        
        if (is_array($style)) {
            if (isset($style['bold']) && $style['bold']) {
                $openTags .= '<b>';
                $closeTags = '</b>' . $closeTags;
            }
            if (isset($style['italic']) && $style['italic']) {
                $openTags .= '<i>';
                $closeTags = '</i>' . $closeTags;
            }
            if (isset($style['underline']) && $style['underline'] !== 'none') {
                $openTags .= '<u>';
                $closeTags = '</u>' . $closeTags;
            }
        } elseif (is_object($style)) {
            if (method_exists($style, 'isBold') && $style->isBold()) {
                $openTags .= '<b>';
                $closeTags = '</b>' . $closeTags;
            }
            if (method_exists($style, 'isItalic') && $style->isItalic()) {
                $openTags .= '<i>';
                $closeTags = '</i>' . $closeTags;
            }
            if (method_exists($style, 'getUnderline') && $style->getUnderline() !== 'none') {
                $openTags .= '<u>';
                $closeTags = '</u>' . $closeTags;
            }
        }
        
        return '<p class="MsoNormal">' . $openTags . $text . $closeTags . '</p>' . PHP_EOL;
    }

    /**
     * Process TextRun element.
     *
     * @param \PhpOffice\PhpWord\Element\TextRun $element
     * @return string
     */
    private function processTextRun($element): string
    {
        $content = '<p class="MsoNormal">';
        
        foreach ($element->getElements() as $textElement) {
            if (get_class($textElement) === 'PhpOffice\PhpWord\Element\Text') {
                $text = htmlspecialchars($textElement->getText());
                $style = $textElement->getFontStyle();
                
                $openTags = '';
                $closeTags = '';
                
                if (is_array($style)) {
                    if (isset($style['bold']) && $style['bold']) {
                        $openTags .= '<b>';
                        $closeTags = '</b>' . $closeTags;
                    }
                    if (isset($style['italic']) && $style['italic']) {
                        $openTags .= '<i>';
                        $closeTags = '</i>' . $closeTags;
                    }
                    if (isset($style['underline']) && $style['underline'] !== 'none') {
                        $openTags .= '<u>';
                        $closeTags = '</u>' . $closeTags;
                    }
                }
                
                $content .= $openTags . $text . $closeTags;
            }
        }
        
        $content .= '</p>' . PHP_EOL;
        
        return $content;
    }

    /**
     * Process Table element.
     *
     * @param \PhpOffice\PhpWord\Element\Table $element
     * @return string
     */
    private function processTable($element): string
    {
        $content = '<table border="1" cellpadding="3" cellspacing="0">' . PHP_EOL;
        
        foreach ($element->getRows() as $row) {
            $content .= '  <tr>' . PHP_EOL;
            foreach ($row->getCells() as $cell) {
                $content .= '    <td>';
                $content .= $this->processSectionElements($cell->getElements());
                $content .= '</td>' . PHP_EOL;
            }
            $content .= '  </tr>' . PHP_EOL;
        }
        
        $content .= '</table>' . PHP_EOL;
        
        return $content;
    }

    /**
     * Process Image element.
     *
     * @param \PhpOffice\PhpWord\Element\Image $element
     * @return string
     */
    private function processImage($element): string
    {
        $source = $element->getSource();
        
        // For now, just reference the image path
        // In a full implementation, you'd want to embed the image or copy it
        return '<p><img src="' . htmlspecialchars($source) . '" alt="Image"></p>' . PHP_EOL;
    }

    /**
     * Process Title element.
     *
     * @param \PhpOffice\PhpWord\Element\Title $element
     * @return string
     */
    private function processTitle($element): string
    {
        $text = htmlspecialchars($element->getText());
        $depth = $element->getDepth();
        
        // Convert title depth to appropriate heading level
        $headingLevel = min($depth + 1, 6);
        
        return "<h{$headingLevel}>{$text}</h{$headingLevel}>" . PHP_EOL;
    }
}