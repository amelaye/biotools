<?php
/**
 * Created by PhpStorm.
 * User: amelaye
 * Date: 2019-07-23
 * Time: 11:34
 */

namespace Tests\MinitoolsBundle\Service;

use Amelaye\BioTools\Service\FastaUploaderManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FastaUploaderManagerTest extends TestCase
{
    protected $sBrochuresDirectory;

    protected function setUp(): void
    {
        $this->sBrochuresDirectory = sys_get_temp_dir() . '/biotools_fasta_uploader_test';
        if (!is_dir($this->sBrochuresDirectory)) {
            mkdir($this->sBrochuresDirectory);
        }
    }

    protected function tearDown(): void
    {
        foreach (glob($this->sBrochuresDirectory . '/*') as $sFile) {
            unlink($sFile);
        }
        rmdir($this->sBrochuresDirectory);
    }

    public function testIsValidSequenceTrue()
    {
        $service = new FastaUploaderManager();

        $this->assertTrue($service->isValidSequence("ATGCatgc"));
    }

    public function testIsValidSequenceFalse()
    {
        $service = new FastaUploaderManager();

        $this->assertFalse($service->isValidSequence("ATGCX"));
    }

    public function testIsValidSequenceException()
    {
        $this->expectException(\Exception::class);

        $service = new FastaUploaderManager();
        $service->isValidSequence(1234);
    }

    public function testCreateFiles()
    {
        $sSourceFile = tempnam(sys_get_temp_dir(), 'fasta_source_');
        file_put_contents($sSourceFile, ">seq1\nATGCATGC\n");

        $file = new UploadedFile($sSourceFile, 'sequence.fasta', 'text/plain', null, true);

        $service = new FastaUploaderManager();
        $testFunction = $service->createFiles($file, $this->sBrochuresDirectory);

        $this->assertEquals(">seq1\nATGCATGC\n", $testFunction);
    }

    public function testCheckNucleotidSequence()
    {
        $var = "ATGCatgc";
        $a = $g = $t = $c = 0;

        $service = new FastaUploaderManager();
        $service->checkNucleotidSequence($var, $a, $g, $t, $c, strlen($var));

        $this->assertEquals(2, $a);
        $this->assertEquals(2, $g);
        $this->assertEquals(2, $t);
        $this->assertEquals(2, $c);
    }

    public function testCheckNucleotidSequenceEmptyLength()
    {
        $var = "ATGCX";
        $a = $g = $t = $c = 0;

        $service = new FastaUploaderManager();
        $service->checkNucleotidSequence($var, $a, $g, $t, $c, '');

        $this->assertEquals(0, $a);
        $this->assertEquals(0, $g);
        $this->assertEquals(0, $t);
        $this->assertEquals(0, $c);
    }

    public function testCheckNucleotidSequenceInvalid()
    {
        $this->expectException(\Exception::class);

        $var = "ATGCX";
        $a = $g = $t = $c = 0;

        $service = new FastaUploaderManager();
        $service->checkNucleotidSequence($var, $a, $g, $t, $c, strlen($var));
    }
}
