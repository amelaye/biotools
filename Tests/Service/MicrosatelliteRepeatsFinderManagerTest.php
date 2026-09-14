<?php

namespace Tests\MinitoolsBundle\Service;

use PHPUnit\Framework\TestCase;
use Amelaye\BioTools\Service\MicrosatelliteRepeatsFinderManager;

class MicrosatelliteRepeatsFinderManagerTest extends TestCase
{
    public function testFindMicrosatelliteRepeats()
    {
        $sSequence = "AACAATGCCATGATGATGATTATTACGACACAACAACACCGCGCTTGACGGCGGCGGATGGATGCCGCGATCAGACGTTCAACGCCCACGTAACGTAACGCAACGTAACCTAACGACACTGTTAACGGTACGAT";
        $iMinLength = 2;
        $iMaxLength = 6;
        $iMinRepeats = 3;
        $iMinLengthOMR = 6;
        $iMismatchesAllowed = 10;
        $aExpected = [
            0 => [
            "start_position" => 9,
            "length" => 3,
            "repeats" => 3,
            "sequence" => "ATGATGATG",
            ],
            1 => [
            "start_position" => 29,
            "length" => 3,
            "repeats" => 3,
            "sequence" => "ACAACAACA"
            ],
            2 => [
            "start_position" => 48,
            "length" => 3,
            "repeats" => 3,
            "sequence" => "CGGCGGCGG",
            ]
        ];

        $service = new MicrosatelliteRepeatsFinderManager();
        $testFunction = $service->findMicrosatelliteRepeats($sSequence, $iMinLength, $iMaxLength, $iMinRepeats, $iMinLengthOMR, $iMismatchesAllowed);

        $this->assertEquals($testFunction, $aExpected);
    }

    public function testFindMicrosatelliteRepeatsException()
    {
        $this->expectException(\Exception::class);
        $sSequence = [];
        $iMinLength = 0;
        $iMaxLength = 0;
        $iMinRepeats = 0;
        $iMinLengthOMR = 0;
        $iMismatchesAllowed = 0;

        $service = new MicrosatelliteRepeatsFinderManager();
        $service->findMicrosatelliteRepeats($sSequence, $iMinLength, $iMaxLength, $iMinRepeats, $iMinLengthOMR, $iMismatchesAllowed);
    }

    public function testIncludeN1()
    {
        $sPrimer = "AACAA";
        $iMinus = 0;
        $sExpected = ".ACAA|A.CAA|AA.AA|AAC.A|AACA.";

        $service = new MicrosatelliteRepeatsFinderManager();
        $testFunction = $service->includeN1($sPrimer, $iMinus);

        $this->assertEquals($testFunction, $sExpected);
    }

    public function testIncludeN1Exception()
    {
        $this->expectException(\Exception::class);
        $sPrimer = [];
        $iMinus = 0;

        $service = new MicrosatelliteRepeatsFinderManager();
        $service->includeN1($sPrimer, $iMinus);
    }

    public function testIncludeN1Plus()
    {
        $sPrimer = "AACAA";
        $iMinus = 0;
        $sExpected = "..CAA|.A.AA|.AC.A|.ACA.|A..AA|A.C.A|A.CA.|AA..A|AA.A.|AAC..";

        $service = new MicrosatelliteRepeatsFinderManager();
        $testFunction = $service->includeNPlus1($sPrimer, $iMinus);

        $this->assertEquals($testFunction, $sExpected);
    }

    public function testIncludeN1PlusException()
    {
        $this->expectException(\Exception::class);
        $sPrimer = [];
        $iMinus = 0;

        $service = new MicrosatelliteRepeatsFinderManager();
        $service->includeNPlus1($sPrimer, $iMinus);
    }

    /**
     * Every combination of three wildcard positions, C(5,3) = 10 patterns for a 5-mer.
     * Legacy's includeN_3() is a byte-for-byte copy of includeN_2() and only ever places
     * two wildcards, so this is a deliberate divergence from it.
     */
    public function testIncludeN3()
    {
        $sPrimer = "AACAA";
        $iMinus = 0;
        $sExpected = "...AA|..C.A|..CA.|.A..A|.A.A.|.AC..|A...A|A..A.|A.C..|AA...";

        $service = new MicrosatelliteRepeatsFinderManager();
        $testFunction = $service->includeN3($sPrimer, $iMinus);

        $this->assertEquals($sExpected, $testFunction);
    }

    /**
     * $iMinus keeps that many bases in 3' always matching, so a 5-mer with $iMinus = 1
     * only wildcards within its first four bases: C(4,3) = 4 patterns, all ending in "A"
     */
    public function testIncludeN3KeepsTheThreePrimeEndIntact()
    {
        $service = new MicrosatelliteRepeatsFinderManager();

        $this->assertEquals("...AA|..C.A|.A..A|A...A", $service->includeN3("AACAA", 1));
    }

    public function testIncludeN3Exception()
    {
        $this->expectException(\Exception::class);

        $service = new MicrosatelliteRepeatsFinderManager();
        $service->includeN3([], 0);
    }

    /**
     * The three-mismatch branch is reachable from the form: the mismatch count is
     * floor(length x percentage / 100), so the 30% option on a 10 base subsequence
     * asks for 3. It must now use a genuine three-wildcard pattern.
     */
    public function testThreeMismatchesUseTheThreeWildcardPattern()
    {
        $service = new MicrosatelliteRepeatsFinderManager();

        // ACGTACGTAC repeated, with 3 substitutions in the second copy
        $sSequence = "ACGTACGTAC" . "AGGTAGGTAG" . "ACGTACGTAC";
        $aResults = $service->findMicrosatelliteRepeats($sSequence, 10, 10, 3, 10, 30);

        $this->assertNotEmpty($aResults);
        $this->assertEquals(3, $aResults[0]["repeats"]);
    }
}