<?php


namespace Tests\MinitoolsBundle\Service;

use Amelaye\BioPHP\Api\AminoApi;
use Amelaye\BioPHP\Api\PKApi;
use App\Service\ProteinPropertiesManager;
use PHPUnit\Framework\TestCase;

class ProteinPropertiesManagerTest extends TestCase
{
    protected $aminos;

    protected $apiMock;

    public function setUp()
    {
        $aPK = [
            "@CONTEXT" => "/contexts/PK",
            "@ID" => "/p_ks/EMBOSS",
            "@TYPE" => "PK",
            "ID" => "EMBOSS",
            "NTERMINUS" => 8.6,
            "K" => 10.8,
            "R" => 12.5,
            "H" => 6.5,
            "CTERMINUS" => 3.6,
            "D" => 3.9,
            "E" => 4.1,
            "C" => 8.5,
            "Y" => 10.1
        ];

        /**
         * Mock API
         */
        require 'samples/Aminos.php';

        $this->apiAminoMock = $this->getMockBuilder(AminoApi::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAminos'])
            ->getMock();
        $this->apiAminoMock->method("getAminos")->will($this->returnValue($aAminosObjects));

        $this->pkMock = $this->getMockBuilder(PKApi::class)
            ->disableOriginalConstructor()
            ->setMethods(["getPkValueById"])
            ->getMock();
        $this->pkMock->method("getPkValueById")->will($this->returnValue($aPK));
    }

    public function testConvertInto3lettersCode()
    {
        $subsequence = "RNDCEQGHILKMFPSTW";
        $sExpected = "ArgAsnAspCysGluGinGlyHisIleLeuLysMetPheProSerThrTrp";

        $service = new ProteinPropertiesManager($this->apiAminoMock, $this->pkMock);
        $testFunction = $service->convertInto3lettersCode($subsequence);

        $this->assertEquals($sExpected, $testFunction);

    }

    public function testWriteSubsequence()
    {
        $iStart = 6;
        $iEnd = 17;
        $sSequence = "ARNDCEQGHILKMFPSTWYVX*";

        $sExpected = "EQGHILKMFPST";

        $service = new ProteinPropertiesManager($this->apiAminoMock, $this->pkMock);
        $testFunction = $service->writeSubsequence($iStart, $iEnd, $sSequence);

        $this->assertEquals($sExpected, $testFunction);
    }

    public function testProteinIsoelectricPoint()
    {
        $aAminoacidContent = [
          "*" => 0,
          "A" => 0,
          "C" => 0,
          "D" => 0,
          "E" => 1,
          "F" => 1,
          "G" => 1,
          "H" => 1,
          "I" => 1,
          "K" => 1,
          "L" => 1,
          "M" => 1,
          "N" => 0,
          "O" => 0,
          "P" => 1,
          "Q" => 1,
          "R" => 0,
          "S" => 1,
          "T" => 1,
          "U" => 0,
          "V" => 0,
          "W" => 0,
          "X" => 0,
          "Y" => 0
        ];

        $fExpected = 7.55;

        $service = new ProteinPropertiesManager($this->apiAminoMock, $this->pkMock);
        $service->setPk("EMBOSS");
        $testFunction = $service->proteinIsoelectricPoint($aAminoacidContent);

        $this->assertEquals($fExpected, $testFunction);
    }

    public function testPartialCharge()
    {
        $fVal1 = 8.6;
        $iVal2 = 7;
        $fExpected = 0.97549663244966;

        $service = new ProteinPropertiesManager($this->apiAminoMock, $this->pkMock);
        $testFunction = $service->partialCharge($fVal1, $iVal2);

        $this->assertEquals($fExpected, $testFunction);
    }

    public function testProteinCharge()
    {
        $aAminoacidContent = [
          "*" => 0,
          "A" => 0,
          "C" => 0,
          "D" => 0,
          "E" => 1,
          "F" => 1,
          "G" => 1,
          "H" => 1,
          "I" => 1,
          "K" => 1,
          "L" => 1,
          "M" => 1,
          "N" => 0,
          "O" => 0,
          "P" => 1,
          "Q" => 1,
          "R" => 0,
          "S" => 1,
          "T" => 1,
          "U" => 0,
          "V" => 0,
          "W" => 0,
          "X" => 0,
          "Y" => 0,
        ];
        $iPH = 7;

        $fExpected = 0.217246532853;

        $service = new ProteinPropertiesManager($this->apiAminoMock, $this->pkMock);
        $service->setPk("EMBOSS");
        $testFunction = $service->proteinCharge($aAminoacidContent, $iPH);

        $this->assertEquals($fExpected, $testFunction);
    }

    public function testFormatAminoacidContent()
    {
        $aAminoacidContent = [
            "*" => 0,
            "A" => 0,
            "C" => 0,
            "D" => 0,
            "E" => 1,
            "F" => 1,
            "G" => 1,
            "H" => 1,
            "I" => 1,
            "K" => 1,
            "L" => 1,
            "M" => 1,
            "N" => 0,
            "O" => 0,
            "P" => 1,
            "Q" => 1,
            "R" => 0,
            "S" => 1,
            "T" => 1,
            "U" => 0,
            "V" => 0,
            "W" => 0,
            "X" => 0,
            "Y" => 0,
        ];

        $aExpected = [
            0 =>  [
                "one_letter" => "*",
                "three_letters" => "STP",
                "count" => 0,
            ],
            1 => [
                "one_letter" => "A",
                "three_letters" => "Ala",
                "count" => 0,
            ],
            2 => [
                "one_letter" => "C",
                "three_letters" => "Cys",
                "count" => 0,
            ],
            3 => [
                "one_letter" => "D",
                "three_letters" => "Asp",
                "count" => 0,
            ],
            4 => [
                "one_letter" => "E",
                "three_letters" => "Glu",
                "count" => 1,
            ],
            5 => [
                "one_letter" => "F",
                "three_letters" => "Phe",
                "count" => 1,
            ],
            6 => [
                "one_letter" => "G",
                "three_letters" => "Gly",
                "count" => 1,
            ],
            7 => [
                "one_letter" => "H",
                "three_letters" => "His",
                "count" => 1,
            ],
            8 => [
                "one_letter" => "I",
                "three_letters" => "Ile",
                "count" => 1,
            ],
            9 => [
                "one_letter" => "K",
                "three_letters" => "Lys",
                "count" => 1,
            ],
            10 => [
                "one_letter" => "L",
                "three_letters" => "Leu",
                "count" => 1,
            ],
            11 => [
                "one_letter" => "M",
                "three_letters" => "Met",
                "count" => 1,
            ],
            12 => [
                "one_letter" => "N",
                "three_letters" => "Asn",
                "count" => 0,
            ],
            13 => [
                "one_letter" => "O",
                "three_letters" => "Pyr",
                "count" => 0,
            ],
            14 => [
                "one_letter" => "P",
                "three_letters" => "Pro",
                "count" => 1,
            ],
            15 => [
                "one_letter" => "Q",
                "three_letters" => "Gin",
                "count" => 1,
            ],
            16 => [
                "one_letter" => "R",
                "three_letters" => "Arg",
                "count" => 0,
            ],
            17 => [
                "one_letter" => "S",
                "three_letters" => "Ser",
                "count" => 1,
            ],
            18 => [
                "one_letter" => "T",
                "three_letters" => "Thr",
                "count" => 1,
            ],
            19 => [
                "one_letter" => "U",
                "three_letters" => "Sec",
                "count" => 0,
            ],
            20 => [
                "one_letter" => "V",
                "three_letters" => "Val",
                "count" => 0,
            ],
            21 => [
                "one_letter" => "W",
                "three_letters" => "Trp",
                "count" => 0,
            ],
            22 => [
                "one_letter" => "X",
                "three_letters" => "XXX",
                "count" => 0,
            ],
            23 => [
                "one_letter" => "Y",
                "three_letters" => "Tyr",
                "count" => 0,
            ],
        ];

        $service = new ProteinPropertiesManager($this->apiAminoMock, $this->pkMock);
        $testFunction = $service->formatAminoacidContent($aAminoacidContent);

        $this->assertEquals($aExpected, $testFunction);
    }

    public function testAminoacidContent()
    {
        $seq = "EQGHILKMFPST";

        $aExpected = [
          "*" => 0,
          "A" => 0,
          "C" => 0,
          "D" => 0,
          "E" => 1,
          "F" => 1,
          "G" => 1,
          "H" => 1,
          "I" => 1,
          "K" => 1,
          "L" => 1,
          "M" => 1,
          "N" => 0,
          "O" => 0,
          "P" => 1,
          "Q" => 1,
          "R" => 0,
          "S" => 1,
          "T" => 1,
          "U" => 0,
          "V" => 0,
          "W" => 0,
          "X" => 0,
          "Y" => 0
        ];

        $service = new ProteinPropertiesManager($this->apiAminoMock, $this->pkMock);
        $testFunction = $service->aminoacidContent($seq);

        $this->assertEquals($aExpected, $testFunction);
    }

    public function testMolarAbsorptionCoefficientOfProt()
    {
        $aminoacid_content = [
          "*" => 0,
          "A" => 1,
          "C" => 1,
          "D" => 1,
          "E" => 1,
          "F" => 1,
          "G" => 1,
          "H" => 1,
          "I" => 1,
          "K" => 1,
          "L" => 1,
          "M" => 1,
          "N" => 1,
          "O" => 0,
          "P" => 1,
          "Q" => 1,
          "R" => 1,
          "S" => 1,
          "T" => 1,
          "U" => 0,
          "V" => 0,
          "W" => 0,
          "X" => 0,
          "Y" => 0,
        ];

        $molweight = 1947.07;
        $fExpected = 2.8889562265353;

        $service = new ProteinPropertiesManager($this->apiAminoMock, $this->pkMock);
        $testFunction = $service->molarAbsorptionCoefficientOfProt($aminoacid_content, $molweight);

        $this->assertEquals($fExpected, $testFunction);
    }

    public function testProteinMolecularWeight()
    {
        $aminoacid_content = [
            "*" => 0,
            "A" => 1,
            "C" => 1,
            "D" => 1,
            "E" => 1,
            "F" => 1,
            "G" => 1,
            "H" => 1,
            "I" => 1,
            "K" => 1,
            "L" => 1,
            "M" => 1,
            "N" => 1,
            "O" => 0,
            "P" => 1,
            "Q" => 1,
            "R" => 1,
            "S" => 1,
            "T" => 1,
            "U" => 0,
            "V" => 0,
            "W" => 0,
            "X" => 0,
            "Y" => 0,
        ];

        $fExpected = 1947.07;

        $service = new ProteinPropertiesManager($this->apiAminoMock, $this->pkMock);
        $testFunction = $service->proteinMolecularWeight($aminoacid_content);

        $this->assertEquals($fExpected, $testFunction);
    }

    public function testProteinAminoacidNature1()
    {
        $sSequence = "ARNDCEQGHILKMFPST";
        $aColors = [
            "polar" => "magenta",
            "nonpolar" => "yellow",
            "charged" => "red",
            "hydrophobic" => "green",
            "positively_charged" => "blue",
            "negatively_charged" => "red"
        ];

        $aExpected = [
            0 => [
                0 => "A",
                1 => "yellow",
            ],
            1 => [
                0 => "R",
                1 => "red",
            ],
            2 => [
                0 => "N",
                1 => "magenta",
            ],
            3 => [
                0 => "D",
                1 => "red",
            ],
            4 => [
                0 => "C",
                1 => "magenta",
            ],
            5 => [
                0 => "E",
                1 => "red",
            ],
            6 => [
                0 => "Q",
                1 => "magenta",
            ],
            7 => [
                0 => "G",
                1 => "yellow",
            ],
            8 => [
                0 => "H",
                1 => "magenta",
            ],
            9 => [
                0 => "I",
                1 => "yellow",
            ],
            10 => [
                0 => "L",
                1 => "yellow",
            ],
            11 => [
                0 => "K",
                1 => "red",
            ],
            12 => [
                0 => "M",
                1 => "yellow",
            ],
            13 => [
                0 => "F",
                1 => "yellow",
            ],
            14 => [
                0 => "P",
                1 => "yellow",
            ],
            15 => [
                0 => "S",
                1 => "magenta",
            ],
            16 => [
                0 => "T",
                1 => "magenta"
            ]
        ];

        $service = new ProteinPropertiesManager($this->apiAminoMock, $this->pkMock);
        $testFunction = $service->proteinAminoacidNature1($sSequence, $aColors);

        $this->assertEquals($aExpected, $testFunction);
    }

    public function testProteinAminoacidNature2()
    {
        $sSequence = "ARNDCEQGHILKMFP";
        $aColors = [
            "polar" => "magenta",
            "nonpolar" => "yellow",
            "charged" => "red",
            "hydrophobic" => "green",
            "positively_charged" => "blue",
            "negatively_charged" => "red"
        ];

        $aExpected = [
            0 => [
                0 => "A",
                1 => "yellow",
            ],
            1 => [
                0 => "R",
                1 => "blue",
            ],
            2 => [
                0 => "N",
                1 => "red",
            ],
            3 => [
                0 => "D",
                1 => "magenta",
            ],
            4 => [
                0 => "C",
                1 => "green",
            ],
            5 => [
                0 => "E",
                1 => "red",
            ],
            6 => [
                0 => "Q",
                1 => "magenta",
            ],
            7 => [
                0 => "G",
                1 => "yellow",
            ],
            8 => [
                0 => "H",
                1 => "magenta",
            ],
            9 => [
                0 => "I",
                1 => "green",
            ],
            10 => [
                0 => "L",
                1 => "green",
            ],
            11 => [
                0 => "K",
                1 => "blue",
            ],
            12 => [
                0 => "M",
                1 => "green",
            ],
            13 => [
                0 => "F",
                1 => "green",
            ],
            14 => [
                0 => "P",
                1 => "green",
            ]
        ];

        $service = new ProteinPropertiesManager($this->apiAminoMock, $this->pkMock);
        $testFunction = $service->proteinAminoacidNature2($sSequence, $aColors);

        $this->assertEquals($aExpected, $testFunction);
    }
}