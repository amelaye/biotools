<?php

namespace Tests\MinitoolsBundle\Service;

use Amelaye\BioPHP\Api\AminoApi;
use Amelaye\BioPHP\Api\NucleotidApi;
use App\Service\PcrAmplificationManager;
use PHPUnit\Framework\TestCase;

class PcrAmplificationManagerTest extends TestCase
{
    protected $dnaComplement;

    protected $apiMock;

    public function setUp()
    {
        $this->dnaComplement = ["A" => "T", "T" => "A", "G" => "C", "C" => "G"];

        /**
         * Mock API
         */
        $clientMock = $this->getMockBuilder('GuzzleHttp\Client')->getMock();
        $serializerMock = \JMS\Serializer\SerializerBuilder::create()
            ->build();

        require 'samples/Nucleotids.php';

        $this->apiNucleoMock = $this->getMockBuilder(NucleotidApi::class)
            ->disableOriginalConstructor()
            ->setMethods(['getNucleotids'])
            ->getMock();
        $this->apiNucleoMock->method("getNucleotids")->will($this->returnValue($aNucleoObjects));
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