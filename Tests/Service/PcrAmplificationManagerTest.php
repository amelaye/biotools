<?php

namespace Tests\MinitoolsBundle\Service;

use Amelaye\BioPHP\Api\NucleotidApi;
use Amelaye\BioTools\Service\PcrAmplificationManager;
use PHPUnit\Framework\TestCase;

class PcrAmplificationManagerTest extends TestCase
{
    protected $dnaComplement;

    protected $apiNucleoMock;

    public function setUp(): void
    {
        $this->dnaComplement = ["A" => "T", "T" => "A", "G" => "C", "C" => "G"];

        /**
         * Mock API
         */

        require 'samples/Nucleotids.php';

        $this->apiNucleoMock = $this->getMockBuilder(NucleotidApi::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getNucleotids'])
            ->getMock();
        $this->apiNucleoMock->method("getNucleotids")->willReturn($aNucleoObjects);
    }

    public function testAmplify()
    {
        $sStartPattern = ".AGCAGTTGG|G.GCAGTTGG|GA.CAGTTGG|GAG.AGTTGG|GAGC.GTTGG|GAGCA.TTGG|GAGCAG.TGG|GAGCAGT.GG|";
        $sStartPattern.= "GAGCAGTT.G|GAGCAGTTG.|.CCGCTGGGG|G.CGCTGGGG|GC.GCTGGGG|GCC.CTGGGG|GCCG.TGGGG|GCCGC.GGGG|";
        $sStartPattern.= "GCCGCT.GGG|GCCGCTG.GG|GCCGCTGG.G|GCCGCTGGG.";

        $sEndPattern = ".CCCAGCGGC|C.CCAGCGGC|CC.CAGCGGC|CCC.AGCGGC|CCCC.GCGGC|CCCCA.CGGC|CCCCAG.GGC|CCCCAGC.GC|";
        $sEndPattern.= "CCCCAGCG.C|CCCCAGCGG.|.CAACTGCTC|C.AACTGCTC|CC.ACTGCTC|CCA.CTGCTC|CCAA.TGCTC|CCAAC.GCTC|";
        $sEndPattern.= "CCAACT.CTC|CCAACTG.TC|CCAACTGC.C|CCAACTGCT.";

        $sSequence = "GGAGTGAGGGGAGCAGTTGGGAACAGATGGTCCCCGCCGAGGGACCGGTGGGCGACGGCGAGCTGTGGCAGACCTGGCTTCCTAACCACGTCGTGT";
        $sSequence.= "TCTTGCGGCTCCGGCCCCTGCGGCGACGCTCAGATCCAACCGAAGCTGAGAAACCAGCTTCTTCGTCGTTGCCTTCGTCGCCGCCGCCGCAGTTGC";
        $sSequence.= "TGACGAGAGAGGAGTTGGTTGGCCTCGGCGGAGAGCTTTTCCTGTGGGACGGAGAAGACAGCTCCTTCTTAGTCGTTCGCCTTCGGGGCCCCAGCGGCGGCGGCGAAG";

        $iMaxLength = "3000";
        $aExpected = [10 => 110, 200 => 90];

        $service = new PcrAmplificationManager($this->apiNucleoMock);
        $testFunction = $service->amplify($sStartPattern, $sEndPattern, $sSequence, $iMaxLength);

        $this->assertEquals($testFunction, $aExpected);
    }

    public function testIncludeN()
    {
        $sPattern = "GAGCAGTTGG";
        $sExpected = ".AGCAGTTGG|G.GCAGTTGG|GA.CAGTTGG|GAG.AGTTGG|GAGC.GTTGG|GAGCA.TTGG|GAGCAG.TGG|GAGCAGT.GG|GAGCAGTT.G|GAGCAGTTG.";

        $service = new PcrAmplificationManager($this->apiNucleoMock);
        $testFunction = $service->includeN($sPattern);

        $this->assertEquals($testFunction, $sExpected);
    }

    public function testCreateStartPattern()
    {
        $primer1 = "GAGCAGTTGG";
        $primer2 = "GCCGCTGGGG";
        $bAllowMismatch = true;

        $sExpected = ".AGCAGTTGG|G.GCAGTTGG|GA.CAGTTGG|GAG.AGTTGG|GAGC.GTTGG|GAGCA.TTGG|GAGCAG.TGG|GAGCAGT.GG|";
        $sExpected.= "GAGCAGTT.G|GAGCAGTTG.|.CCGCTGGGG|G.CGCTGGGG|GC.GCTGGGG|GCC.CTGGGG|GCCG.TGGGG|GCCGC.GGGG|";
        $sExpected.= "GCCGCT.GGG|GCCGCTG.GG|GCCGCTGG.G|GCCGCTGGG.";

        $service = new PcrAmplificationManager($this->apiNucleoMock);
        $testFunction = $service->createStartPattern($primer1, $primer2, $bAllowMismatch);

        $this->assertEquals($testFunction, $sExpected);
    }

    /**
     * The template holds primer 1, ten A, then the reverse complement of primer 2 (CCCCAGCGGC):
     * one product of 10 + 10 + 10 = 30 bases at 0
     */
    private const PCR_TEMPLATE = "GAGCAGTTGG" . "AAAAAAAAAA" . "CCCCAGCGGC";

    private function amplifyWithPrimers($sPrimer1, $sPrimer2, $bAllowMismatch, $iMaxLength)
    {
        $service = new PcrAmplificationManager($this->apiNucleoMock);
        $sStartPattern = $service->createStartPattern($sPrimer1, $sPrimer2, $bAllowMismatch);

        return $service->amplify($sStartPattern, $service->createEndPattern($sStartPattern), self::PCR_TEMPLATE, $iMaxLength);
    }

    /**
     * A primer with N finds its site in exact mode, and still finds it when a mismatch is allowed:
     * the N is any base in every alternative, never a literal "N"
     */
    public function testAPrimerWithNAmplifiesWithAndWithoutMismatch()
    {
        $service = new PcrAmplificationManager($this->apiNucleoMock);
        $this->assertStringNotContainsString("N", $service->createStartPattern("GANCANTTGG", "GCCGCTGGGG", true));

        $this->assertSame([0 => 30], $this->amplifyWithPrimers("GANCANTTGG", "GCCGCTGGGG", false, 3000));
        $this->assertSame([0 => 30], $this->amplifyWithPrimers("GANCANTTGG", "GCCGCTGGGG", true, 3000));
    }

    /**
     * M stands for A or C: GAGMAGTTGG matches the GAGCAGTTGG of the template, and its reverse
     * complement holds [GT]
     */
    public function testADegeneratePrimerMatchesTheBasesItStandsFor()
    {
        $service = new PcrAmplificationManager($this->apiNucleoMock);
        $this->assertSame("GAG[AC]AGTTGG|GCCGCTGGGG", $service->createStartPattern("GAGMAGTTGG", "GCCGCTGGGG", false));
        $this->assertSame("CCCCAGCGGC|CCAACT[GT]CTC", $service->createEndPattern("GAG[AC]AGTTGG|GCCGCTGGGG"));

        $this->assertSame([0 => 30], $this->amplifyWithPrimers("GAGMAGTTGG", "GCCGCTGGGG", false, 3000));
        // R (A or G) does not cover the C of the template
        $this->assertSame([], $this->amplifyWithPrimers("GAGRAGTTGG", "GCCGCTGGGG", false, 3000));
    }

    /**
     * A degenerate primer with the mismatch option: each alternative keeps the classes but one
     */
    public function testADegeneratePrimerWithMismatch()
    {
        $service = new PcrAmplificationManager($this->apiNucleoMock);

        $this->assertSame(".[AG]T|A.T|A[AG].|.CC|G.C|GC.", $service->createStartPattern("ART", "GCC", true));
        $this->assertSame([0 => 30], $this->amplifyWithPrimers("GAGMAGTTGG", "GCCGCTGGGG", true, 3000));
    }

    /**
     * The maximum length counts the forward primer: the 30-base product fits in 30, not in 25
     */
    public function testTheMaximumLengthIncludesTheForwardPrimer()
    {
        $this->assertSame([0 => 30], $this->amplifyWithPrimers("GAGCAGTTGG", "GCCGCTGGGG", false, 30));
        $this->assertSame([], $this->amplifyWithPrimers("GAGCAGTTGG", "GCCGCTGGGG", false, 25));
    }

    public function testCreateEndPattern()
    {
        $sStartPattern = ".AGCAGTTGG|G.GCAGTTGG|GA.CAGTTGG|GAG.AGTTGG|GAGC.GTTGG|GAGCA.TTGG|GAGCAG.TGG|GAGCAGT.GG|";
        $sStartPattern.= "GAGCAGTT.G|GAGCAGTTG.|.CCGCTGGGG|G.CGCTGGGG|GC.GCTGGGG|GCC.CTGGGG|GCCG.TGGGG|GCCGC.GGGG|";
        $sStartPattern.= "GCCGCT.GGG|GCCGCTG.GG|GCCGCTGG.G|GCCGCTGGG.";

        $sEndPattern = ".CCCAGCGGC|C.CCAGCGGC|CC.CAGCGGC|CCC.AGCGGC|CCCC.GCGGC|CCCCA.CGGC|CCCCAG.GGC|CCCCAGC.GC|";
        $sEndPattern.= "CCCCAGCG.C|CCCCAGCGG.|.CAACTGCTC|C.AACTGCTC|CC.ACTGCTC|CCA.CTGCTC|CCAA.TGCTC|CCAAC.GCTC|";
        $sEndPattern.= "CCAACT.CTC|CCAACTG.TC|CCAACTGC.C|CCAACTGCT.";

        $service = new PcrAmplificationManager($this->apiNucleoMock);
        $testFunction = $service->createEndPattern($sStartPattern);

        $this->assertEquals($testFunction, $sEndPattern);
    }
}