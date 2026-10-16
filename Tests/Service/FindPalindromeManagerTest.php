<?php
namespace Tests\MinitoolsBundle\Service;

use Amelaye\BioTools\Service\FindPalindromeManager;
use PHPUnit\Framework\TestCase;

class FindPalindromeManagerTest extends TestCase
{
    public function testFindPalindromicSeqs()
    {
        $sSequence = "AACAATGCCATGATGATGATTATTACGACACAACAACACCGCGCTTGACGGCGGCGGATGGATGCCGCGATCAGACGTTCAACGCCCACGTAACGTAACGCAACGTAACCTAACGACACTGTTAACGGTACGAT";
        $iMin = 4;
        $iMax = 5;
        $aExpected = [
            8 => "CATG",
            39 => "CGCG",
            40 => "GCGC",
            65 => "CGCG",
            68 => "GATC",
            74 => "ACGT",
            87 => "ACGT",
            92 => "ACGT",
            102 => "ACGT",
            121 => "TTAA",
            127 => "GTAC"
        ];

        $service = new FindPalindromeManager();
        $testFunction = $service->findPalindromicSeqs($sSequence, $iMin, $iMax);

        $this->assertEquals($aExpected, $testFunction);
    }

    /**
     * Lower case and white space are no longer a reason to miss a site: "gaattc" holds EcoRI's
     * GAATTC at 0 and AATT at 1, and the line break inside "GAA\nTTC" no longer counts as a base
     */
    public function testFindPalindromicSeqsReadsACleanedUpperCaseSequence()
    {
        $service = new FindPalindromeManager();

        $this->assertSame([0 => "GAATTC", 1 => "AATT"], $service->findPalindromicSeqs("gaattc", 4, 10));
        $this->assertSame([0 => "GAATTC"], $service->findPalindromicSeqs("GAA\nTTC", 6, 7));
        $this->assertTrue($service->dnaIsPalindrome("gaattc"));
    }

    public function testFindPalindromicSeqsException()
    {
        $this->expectException(\Exception::class);

        $sSequence = [];
        $iMin = 0;
        $iMax = 0;

        $service = new FindPalindromeManager();
        $service->findPalindromicSeqs($sSequence, $iMin, $iMax);
    }

    public function testDnaIsPalindromeTrue()
    {
        $sSequence = "AAATTT";
        $service = new FindPalindromeManager();
        $testFunction = $service->dnaIsPalindrome($sSequence);
        $this->assertTrue($testFunction);
    }

    public function testDnaIsPalindromeFalse()
    {
        $sSequence = "AAGTTT";
        $service = new FindPalindromeManager();
        $testFunction = $service->dnaIsPalindrome($sSequence);
        $this->assertFalse($testFunction);
    }

    public function testDnaIsPalindromeException()
    {
        $this->expectException(\Exception::class);
        $sSequence = [];
        $service = new FindPalindromeManager();
        $service->dnaIsPalindrome($sSequence);
    }
}