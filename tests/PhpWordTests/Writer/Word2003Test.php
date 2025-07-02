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

namespace PhpOffice\PhpWordTests\Writer;

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\Writer\Word2003;

/**
 * Test class for PhpOffice\PhpWord\Writer\Word2003.
 *
 * @runTestsInSeparateProcesses
 */
class Word2003Test extends \PHPUnit\Framework\TestCase
{
    /**
     * Construct.
     */
    public function testConstruct(): void
    {
        $object = new Word2003(new PhpWord());

        self::assertInstanceOf('PhpOffice\\PhpWord\\PhpWord', $object->getPhpWord());
    }

    /**
     * Construct with null.
     */
    public function testConstructWithNull(): void
    {
        $this->expectException(\PhpOffice\PhpWord\Exception\Exception::class);
        $this->expectExceptionMessage('No PhpWord assigned.');
        $object = new Word2003();
        $object->getPhpWord();
    }

    /**
     * Save.
     */
    public function testSave(): void
    {
        $imageSrc = __DIR__ . '/../_files/images/PhpWord.png';
        $file = __DIR__ . '/../_files/temp.doc';

        $phpWord = new PhpWord();
        $phpWord->getDocumentProperties()->setTitle('Test Document');
        $phpWord->addFontStyle(
            'Font',
            ['name' => 'Verdana', 'size' => 11, 'color' => 'FF0000']
        );
        $phpWord->addParagraphStyle('Paragraph', ['alignment' => Jc::CENTER]);
        $section = $phpWord->addSection();
        $section->addText(htmlspecialchars('Test 1', ENT_COMPAT, 'UTF-8'), 'Font', 'Paragraph');
        $section->addTextBreak();
        $section->addText(htmlspecialchars('Test 2', ENT_COMPAT, 'UTF-8'), ['name' => 'Tahoma', 'bold' => true, 'italic' => true]);
        $section->addTitle(htmlspecialchars('Test Title', ENT_COMPAT, 'UTF-8'), 1);

        // Add a simple table
        $table = $section->addTable();
        $table->addRow();
        $table->addCell(2000)->addText('Cell 1');
        $table->addCell(2000)->addText('Cell 2');
        $table->addRow();
        $table->addCell(2000)->addText('Cell 3');
        $table->addCell(2000)->addText('Cell 4');

        // Add a text run
        $textrun = $section->addTextRun();
        $textrun->addText(htmlspecialchars('Test 3', ENT_COMPAT, 'UTF-8'));
        $textrun->addText(htmlspecialchars(' Bold Text', ENT_COMPAT, 'UTF-8'), ['bold' => true]);

        // Add image if it exists
        if (file_exists($imageSrc)) {
            $section->addImage($imageSrc);
        }

        $writer = new Word2003($phpWord);
        $writer->save($file);

        self::assertFileExists($file);

        // Verify the file contains HTML-like content for Word 2003
        $content = file_get_contents($file);
        self::assertStringContains('<!DOCTYPE html>', $content);
        self::assertStringContains('xmlns:w="urn:schemas-microsoft-com:office:word"', $content);
        self::assertStringContains('<meta name="ProgId" content="Word.Document">', $content);
        self::assertStringContains('Test 1', $content);
        self::assertStringContains('Test 2', $content);
        self::assertStringContains('Test Title', $content);
        self::assertStringContains('<table', $content);
        self::assertStringContains('Cell 1', $content);

        @unlink($file);
    }

    /**
     * Save to PHP output.
     */
    public function testSavePhpOutput(): void
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();
        $section->addText(htmlspecialchars('Test', ENT_COMPAT, 'UTF-8'));
        $writer = new Word2003($phpWord);
        
        ob_start();
        $writer->save('php://output');
        $contents = ob_get_contents();
        self::assertTrue(ob_end_clean());
        
        self::assertNotEmpty($contents);
        self::assertStringContains('<!DOCTYPE html>', $contents);
        self::assertStringContains('Test', $contents);
    }

    /**
     * Test document with formatting.
     */
    public function testDocumentWithFormatting(): void
    {
        $file = __DIR__ . '/../_files/temp_formatted.doc';

        $phpWord = new PhpWord();
        $section = $phpWord->addSection();
        
        // Test various formatting options
        $section->addText('Bold text', ['bold' => true]);
        $section->addText('Italic text', ['italic' => true]);
        $section->addText('Underlined text', ['underline' => 'single']);
        
        $writer = new Word2003($phpWord);
        $writer->save($file);

        self::assertFileExists($file);

        $content = file_get_contents($file);
        self::assertStringContains('<b>Bold text</b>', $content);
        self::assertStringContains('<i>Italic text</i>', $content);
        self::assertStringContains('<u>Underlined text</u>', $content);

        @unlink($file);
    }

    /**
     * Test empty document.
     */
    public function testEmptyDocument(): void
    {
        $file = __DIR__ . '/../_files/temp_empty.doc';

        $phpWord = new PhpWord();
        $phpWord->addSection(); // Add empty section
        
        $writer = new Word2003($phpWord);
        $writer->save($file);

        self::assertFileExists($file);

        $content = file_get_contents($file);
        self::assertStringContains('<!DOCTYPE html>', $content);
        self::assertStringContains('<div class="Section1">', $content);

        @unlink($file);
    }
}