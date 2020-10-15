<?php

namespace Tests\MinitoolsBundle\Service;

use Amelaye\BioPHP\Api\Pam250MatrixDigitApi;
use PHPUnit\Framework\TestCase;
use App\Service\SequenceAlignmentManager;

class SequenceAlignmentManagerTest extends TestCase
{
    protected $matrix;

    public function setUp()
    {
        require_once('samples/Pam250Matrix.php');

        /**
         * Mock API
         */
        $this->matrixMock = $this->getMockBuilder(Pam250MatrixDigitApi::class)
            ->disableOriginalConstructor()
            ->setMethods(['getPam250Matrix'])
            ->getMock();
        $this->matrixMock->method("getPam250Matrix")->will($this->returnValue($aPam250Matrix));
    }

    public function testAlignDNA()
    {
        $seqa = "GGAGTGAGGGGAGCAGTTGGCTGAAGATGGTCCCCGCCGAGGGACCGGTGGGCGACGGCGAGCTGTGGCAGACCTGGCTTCCTAACCACGTCCGTGTTCTTGCGGCTCCGGGAGGGACTG";
        $seqb = "CGCATGCGGAGTGAGGGGAGCAGTTGGGAACAGATGGTCCCCGCCGAGGGACCGGTGGGCGACGGCCAGCTGTGGCAGACCTGGCTTCCTAACCACGGAACGTTCTTTCCGCTCCGGGAG";

        $aExpected = [
          "seqa" => "-------GGAGTGAGGGGAGCAGTTGGCTGAAGATGGTCCCCGCCGAGGGACCGGTGGGCGACGGCGAGCTGTGGCAGACCTGGCTTCCTAACCACGTC-CGTGTTCTTGCGGCTCCGGGAGGGACT",
          "seqb" => "CGCATGCGGAGTGAGGGGAGCAGTTGGGAACAGATGGTCCCCGCCGAGGGACCGGTGGGCGACGGCCAGCTGTGGCAGACCTGGCTTCCTAACCACGGAACGT--TCTTTCCGCTCCGGGAG-----"
        ];

        $service = new SequenceAlignmentManager($this->matrixMock);
        $testFunction = $service->alignDNA($seqa, $seqb);

        $this->assertEquals($aExpected, $testFunction);
    }

    public function testGenerateResultsProtein()
    {
        $aMatrix = [
          51 => [
            27 => 1
          ],
          50 => [
            26 => 1
          ],
          49 => [
            25 => 1
          ],
          48 => [
            24 => 1
          ],
          47 => [
            23 => 1
          ],
          46 => [
            22 => 1
          ],
          45 => [
            21 => 1
          ],
          44 => [
            20 => 1
          ],
          43 => [
            20 => 1
          ],
          42 => [
            19 => 1
          ],
          41 => [
            18 => 1
          ],
          40 => [
            17 => 1
          ],
          39 => [
            16 => 1
          ],
          38 => [
            15 => 1
          ],
          37 => [
            14 => 1
          ],
          36 => [
            13 => 1
          ],
          35 => [
            12 => 1
          ],
          34 => [
            11 => 1
          ],
          33 => [
            10 => 1
          ],
          32 => [
            9 => 1
          ],
          31 => [
            8 => 1
          ],
          30 => [
            8 => 1
          ],
          29 => [
            8 => 1
          ],
          28 => [
            7 => 1,
            6 => 1
          ],
          27 => [
            5 => 1,
          ],
          26 => [
            4 => 1
          ],
          25 => [
            3 => 1
          ],
          24 => [
            2 => 1
          ],
          23 => [
            1 => 1
          ],
          22 => [
            0 => 1
          ],
          21 => [
            0 => 1
          ],
          20 => [
            0 => 1
          ],
          19 => [
            0 => 1
          ],
          18 => [
            0 => 1
          ],
          17 => [
            0 => 1
          ],
          16 => [
            0 => 1
          ],
          15 => [
            0 => 1
          ],
          14 => [
            0 => 1
          ],
          13 => [
            0 => 1
          ],
          12 => [
            0 => 1
          ],
          11 => [
            0 => 1
          ],
          10 => [
            0 => 1
          ],
          9 => [
            0 => 1
          ],
          8 => [
            0 => 1
          ],
          7 => [
            0 => 1
          ],
          6 => [
            0 => 1
          ],
          5 => [
            0 => 1
          ],
          4 => [
            0 => 1
          ],
          3 => [
            0 => 1
          ],
          2 => [
            0 => 1
          ],
          1 => [
            0 => 1
          ],
          0 => [
            0 => 1
          ],
          52 => [
            28 => 1
          ],
          53 => [
            29 => 1
          ],
          54 => [
            30 => 1
          ],
          55 => [
            31 => 1
          ],
          56 => [
            32 => 1
          ],
          57 => [
            33 => 1
          ],
          58 => [
            34 => 1
          ],
          59 => [
            35 => 1
          ],
          60 => [
            36 => 1,
            37 => 1,
            38 => 1,
            39 => 1,
            40 => 1,
            41 => 1,
            42 => 1,
            43 => 1,
            44 => 1,
            45 => 1,
            46 => 1,
            47 => 1,
            48 => 1,
            49 => 1,
            50 => 1,
            51 => 1,
            52 => 1,
            53 => 1,
            54 => 1,
            55 => 1,
            56 => 1,
            57 => 1,
            58 => 1,
            59 => 1,
          ],
        ];
        $sSequenceA = "DHAPTQERTYKYIGFENTCRFSMDQDCWNDSHQYVMTSDAAMKYGKVTGHYGFTVHKERC";
        $sSequenceB = "VYYESGSPRCQKHTIHPVRVYQEFGRRQKTIEYPKCGFARWTEEFHMIKNAWWQYRTHEF";

        $aSequenceA = [
          0 => "D",
          1 => "H",
          2 => "A",
          3 => "P",
          4 => "T",
          5 => "Q",
          6 => "E",
          7 => "R",
          8 => "T",
          9 => "Y",
          10 => "K",
          11 => "Y",
          12 => "I",
          13 => "G",
          14 => "F",
          15 => "E",
          16 => "N",
          17 => "T",
          18 => "C",
          19 => "R",
          20 => "F",
          21 => "S",
          22 => "M",
          23 => "D",
          24 => "Q",
          25 => "D",
          26 => "C",
          27 => "W",
          28 => "N",
          29 => "D",
          30 => "S",
          31 => "H",
          32 => "Q",
          33 => "Y",
          34 => "V",
          35 => "M",
          36 => "T",
          37 => "S",
          38 => "D",
          39 => "A",
          40 => "A",
          41 => "M",
          42 => "K",
          43 => "Y",
          44 => "G",
          45 => "K",
          46 => "V",
          47 => "T",
          48 => "G",
          49 => "H",
          50 => "Y",
          51 => "G",
          52 => "F",
          53 => "T",
          54 => "V",
          55 => "H",
          56 => "K",
          57 => "E",
          58 => "R",
          59 => "C",
        ];

        $aSequenceB = [
          0 => "V",
          1 => "Y",
          2 => "Y",
          3 => "E",
          4 => "S",
          5 => "G",
          6 => "S",
          7 => "P",
          8 => "R",
          9 => "C",
          10 => "Q",
          11 => "K",
          12 => "H",
          13 => "T",
          14 => "I",
          15 => "H",
          16 => "P",
          17 => "V",
          18 => "R",
          19 => "V",
          20 => "Y",
          21 => "Q",
          22 => "E",
          23 => "F",
          24 => "G",
          25 => "R",
          26 => "R",
          27 => "Q",
          28 => "K",
          29 => "T",
          30 => "I",
          31 => "E",
          32 => "Y",
          33 => "P",
          34 => "K",
          35 => "C",
          36 => "G",
          37 => "F",
          38 => "A",
          39 => "R",
          40 => "W",
          41 => "T",
          42 => "E",
          43 => "E",
          44 => "F",
          45 => "H",
          46 => "M",
          47 => "I",
          48 => "K",
          49 => "N",
          50 => "A",
          51 => "W",
          52 => "W",
          53 => "Q",
          54 => "Y",
          55 => "R",
          56 => "T",
          57 => "H",
          58 => "E",
          59 => "F",
        ];

        $iMaxA = 60;
        $iMaxB = 60;
        $bIsProt = 1;

        $aExpected = [
          "seqa" => "----------------------DHAPTQER--TYKYIGFENTCR-FSMDQDCWNDSHQYVMTSDAAMKYGKVTGHYGFTVHKERC",
          "seqb" => "VYYESGSPRCQKHTIHPVRVYQEFGRRQ-KTIEYPKCGFARWTEEFHMIKNAWWQYRTHEF------------------------"
        ];

        $service = new SequenceAlignmentManager($this->matrixMock);
        $testFunction = $service->generateresults($aMatrix, $sSequenceA, $sSequenceB, $aSequenceA, $aSequenceB, $iMaxA, $iMaxB, $bIsProt);

        $this->assertEquals($aExpected, $testFunction);
    }

    public function testGenerateResultsDNA()
    {
        $matrizz = [
          119 => [
            113 => 1,
            114 => 1,
            115 => 1,
            116 => 1,
            117 => 1,
            118 => 1,
            119 => 1,
          ],
          118 => [
            112 => 1
          ],
          117 => [
            111 => 1
          ],
          116 => [
            110 => 1
          ],
          115 => [
            109 => 1
          ],
          114 => [
            108 => 1
          ],
          113 => [
            107 => 1
          ],
          112 => [
            106 => 1
          ],
          111 => [
            105 => 1
          ],
          110 => [
            104 => 1
          ],
          109 => [
            103 => 1
          ],
          108 => [
            102 => 1
          ],
          107 => [
            101 => 1
          ],
          106 => [
            100 => 1
          ],
          105 => [
            99 => 1
          ],
          104 => [
            98 => 1
          ],
          103 => [
            97 => 1,
            96 => 1,
            95 => 1,
          ],
          102 => [
            94 => 1,
          ],
          101 => [
            93 => 1
          ],
          100 => [
            92 => 1
          ],
          99 => [
            92 => 1
          ],
          98 => [
            91 => 1
          ],
          97 => [
            90 => 1
          ],
          96 => [
            89 => 1
          ],
          95 => [
            88 => 1
          ],
          94 => [
            87 => 1
          ],
          93 => [
            86 => 1
          ],
          92 => [
            85 => 1
          ],
          91 => [
            84 => 1
          ],
          90 => [
            83 => 1
          ],
          89 => [
            82 => 1
          ],
          88 => [
            81 => 1
          ],
          87 => [
            80 => 1
          ],
          86 => [
            79 => 1
          ],
          85 => [
            78 => 1
          ],
          84 => [
            77 => 1
          ],
          83 => [
            76 => 1
          ],
          82 => [
            75 => 1
          ],
          81 => [
            74 => 1
          ],
          80 => [
            73 => 1
          ],
          79 => [
            72 => 1
          ],
          78 => [
            71 => 1
          ],
          77 => [
            70 => 1
          ],
          76 => [
            69 => 1
          ],
          75 => [
            68 => 1
          ],
          74 => [
            67 => 1
          ],
          73 => [
            66 => 1
          ],
          72 => [
            65 => 1
          ],
          71 => [
            64 => 1
          ],
          70 => [
            63 => 1
          ],
          69 => [
            62 => 1
          ],
          68 => [
            61 => 1
          ],
          67 => [
            60 => 1
          ],
          66 => [
            59 => 1
          ],
          65 => [
            58 => 1
          ],
          64 => [
            57 => 1
          ],
          63 => [
            56 => 1
          ],
          62 => [
            55 => 1
          ],
          61 => [
            54 => 1
          ],
          60 => [
            53 => 1
          ],
          59 => [
            52 => 1
          ],
          58 => [
            51 => 1
          ],
          57 => [
            50 => 1
          ],
          56 => [
            49 => 1
          ],
          55 => [
            48 => 1
          ],
          54 => [
            47 => 1
          ],
          53 => [
            46 => 1
          ],
          52 => [
            45 => 1
          ],
          51 => [
            44 => 1
          ],
          50 => [
            43 => 1
          ],
          49 => [
            42 => 1
          ],
          48 => [
            41 => 1
          ],
          47 => [
            40 => 1
          ],
          46 => [
            39 => 1
          ],
          45 => [
            38 => 1
          ],
          44 => [
            37 => 1
          ],
          43 => [
            36 => 1
          ],
          42 => [
            35 => 1
          ],
          41 => [
            34 => 1
          ],
          40 => [
            33 => 1
          ],
          39 => [
            32 => 1
          ],
          38 => [
            31 => 1
          ],
          37 => [
            30 => 1
          ],
          36 => [
            29 => 1
          ],
          35 => [
            28 => 1
          ],
          34 => [
            27 => 1
          ],
          33 => [
            26 => 1
          ],
          32 => [
            25 => 1
          ],
          31 => [
            24 => 1
          ],
          30 => [
            23 => 1
          ],
          29 => [
            22 => 1
          ],
          28 => [
            21 => 1
          ],
          27 => [
            20 => 1
          ],
          26 => [
            19 => 1
          ],
          25 => [
            18 => 1
          ],
          24 => [
            17 => 1
          ],
          23 => [
            16 => 1
          ],
          22 => [
            15 => 1
          ],
          21 => [
            14 => 1
          ],
          20 => [
            13 => 1
          ],
          19 => [
            12 => 1
          ],
          18 => [
            11 => 1
          ],
          17 => [
            10 => 1
          ],
          16 => [
            9 => 1
          ],
          15 => [
            8 => 1
          ],
          14 => [
            7 => 1
          ],
          13 => [
            6 => 1
          ],
          12 => [
            5 => 1
          ],
          11 => [
            4 => 1
          ],
          10 => [
            3 => 1
          ],
          9 => [
            2 => 1
          ],
          8 => [
            1 => 1
          ],
          7 => [
            0 => 1
          ],
          6 => [
            0 => 1
          ],
          5 => [
            0 => 1
          ],
          4 => [
            0 => 1
          ],
          3 => [
            0 => 1
          ],
          2 => [
            0 => 1
          ],
          1 => [
            0 => 1
          ],
          0 => [
            0 => 1
          ]
        ];

        $seqa = "GGAGTGAGGGGAGCAGTTGGCTGAAGATGGTCCCCGCCGAGGGACCGGTGGGCGACGGCGAGCTGTGGCAGACCTGGCTTCCTAACCACGTCCGTGTTCTTGCGGCTCCGGGAGGGACTG";
        $seqb = "CGCATGCGGAGTGAGGGGAGCAGTTGGGAACAGATGGTCCCCGCCGAGGGACCGGTGGGCGACGGCCAGCTGTGGCAGACCTGGCTTCCTAACCACGGAACGTTCTTTCCGCTCCGGGAG";

        $a = [
          0 => "G",
          1 => "G",
          2 => "A",
          3 => "G",
          4 => "T",
          5 => "G",
          6 => "A",
          7 => "G",
          8 => "G",
          9 => "G",
          10 => "G",
          11 => "A",
          12 => "G",
          13 => "C",
          14 => "A",
          15 => "G",
          16 => "T",
          17 => "T",
          18 => "G",
          19 => "G",
          20 => "C",
          21 => "T",
          22 => "G",
          23 => "A",
          24 => "A",
          25 => "G",
          26 => "A",
          27 => "T",
          28 => "G",
          29 => "G",
          30 => "T",
          31 => "C",
          32 => "C",
          33 => "C",
          34 => "C",
          35 => "G",
          36 => "C",
          37 => "C",
          38 => "G",
          39 => "A",
          40 => "G",
          41 => "G",
          42 => "G",
          43 => "A",
          44 => "C",
          45 => "C",
          46 => "G",
          47 => "G",
          48 => "T",
          49 => "G",
          50 => "G",
          51 => "G",
          52 => "C",
          53 => "G",
          54 => "A",
          55 => "C",
          56 => "G",
          57 => "G",
          58 => "C",
          59 => "G",
          60 => "A",
          61 => "G",
          62 => "C",
          63 => "T",
          64 => "G",
          65 => "T",
          66 => "G",
          67 => "G",
          68 => "C",
          69 => "A",
          70 => "G",
          71 => "A",
          72 => "C",
          73 => "C",
          74 => "T",
          75 => "G",
          76 => "G",
          77 => "C",
          78 => "T",
          79 => "T",
          80 => "C",
          81 => "C",
          82 => "T",
          83 => "A",
          84 => "A",
          85 => "C",
          86 => "C",
          87 => "A",
          88 => "C",
          89 => "G",
          90 => "T",
          91 => "C",
          92 => "C",
          93 => "G",
          94 => "T",
          95 => "G",
          96 => "T",
          97 => "T",
          98 => "C",
          99 => "T",
          100 => "T",
          101 => "G",
          102 => "C",
          103 => "G",
          104 => "G",
          105 => "C",
          106 => "T",
          107 => "C",
          108 => "C",
          109 => "G",
          110 => "G",
          111 => "G",
          112 => "A",
          113 => "G",
          114 => "G",
          115 => "G",
          116 => "A",
          117 => "C",
          118 => "T",
          119 => "G",
        ];

        $b = [
          0 => "C",
          1 => "G",
          2 => "C",
          3 => "A",
          4 => "T",
          5 => "G",
          6 => "C",
          7 => "G",
          8 => "G",
          9 => "A",
          10 => "G",
          11 => "T",
          12 => "G",
          13 => "A",
          14 => "G",
          15 => "G",
          16 => "G",
          17 => "G",
          18 => "A",
          19 => "G",
          20 => "C",
          21 => "A",
          22 => "G",
          23 => "T",
          24 => "T",
          25 => "G",
          26 => "G",
          27 => "G",
          28 => "A",
          29 => "A",
          30 => "C",
          31 => "A",
          32 => "G",
          33 => "A",
          34 => "T",
          35 => "G",
          36 => "G",
          37 => "T",
          38 => "C",
          39 => "C",
          40 => "C",
          41 => "C",
          42 => "G",
          43 => "C",
          44 => "C",
          45 => "G",
          46 => "A",
          47 => "G",
          48 => "G",
          49 => "G",
          50 => "A",
          51 => "C",
          52 => "C",
          53 => "G",
          54 => "G",
          55 => "T",
          56 => "G",
          57 => "G",
          58 => "G",
          59 => "C",
          60 => "G",
          61 => "A",
          62 => "C",
          63 => "G",
          64 => "G",
          65 => "C",
          66 => "C",
          67 => "A",
          68 => "G",
          69 => "C",
          70 => "T",
          71 => "G",
          72 => "T",
          73 => "G",
          74 => "G",
          75 => "C",
          76 => "A",
          77 => "G",
          78 => "A",
          79 => "C",
          80 => "C",
          81 => "T",
          82 => "G",
          83 => "G",
          84 => "C",
          85 => "T",
          86 => "T",
          87 => "C",
          88 => "C",
          89 => "T",
          90 => "A",
          91 => "A",
          92 => "C",
          93 => "C",
          94 => "A",
          95 => "C",
          96 => "G",
          97 => "G",
          98 => "A",
          99 => "A",
          100 => "C",
          101 => "G",
          102 => "T",
          103 => "T",
          104 => "C",
          105 => "T",
          106 => "T",
          107 => "T",
          108 => "C",
          109 => "C",
          110 => "G",
          111 => "C",
          112 => "T",
          113 => "C",
          114 => "C",
          115 => "G",
          116 => "G",
          117 => "G",
          118 => "A",
          119 => "G",
        ];

        $maxa = 120;
        $maxb = 120;

        $aExpected = [
          "seqa" => "-------GGAGTGAGGGGAGCAGTTGGCTGAAGATGGTCCCCGCCGAGGGACCGGTGGGCGACGGCGAGCTGTGGCAGACCTGGCTTCCTAACCACGTC-CGTGTTCTTGCGGCTCCGGGAGGGACT",
          "seqb" => "CGCATGCGGAGTGAGGGGAGCAGTTGGGAACAGATGGTCCCCGCCGAGGGACCGGTGGGCGACGGCCAGCTGTGGCAGACCTGGCTTCCTAACCACGGAACGT--TCTTTCCGCTCCGGGAG-----"
        ];

        $service = new SequenceAlignmentManager($this->matrixMock);
        $testFunction = $service->generateresults($matrizz, $seqa, $seqb, $a, $b, $maxa, $maxb, 0);

        $this->assertEquals($aExpected, $testFunction);
    }

    public function testAlignProteins()
    {
        $seqa = "DHAPTQERTYKYIGFENTCRFSMDQDCWNDSHQYVMTSDAAMKYGKVTGHYGFTVHKERC";
        $seqb = "VYYESGSPRCQKHTIHPVRVYQEFGRRQKTIEYPKCGFARWTEEFHMIKNAWWQYRTHEF";

        $aExpected = [
          "seqa" => "----------------------DHAPTQER--TYKYIGFENTCR-FSMDQDCWNDSHQYVMTSDAAMKYGKVTGHYGFTVHKERC",
          "seqb" => "VYYESGSPRCQKHTIHPVRVYQEFGRRQ-KTIEYPKCGFARWTEEFHMIKNAWWQYRTHEF------------------------"
        ];

        $service = new SequenceAlignmentManager($this->matrixMock);
        $testFunction = $service->alignProteins($seqa, $seqb);

        $this->assertEquals($aExpected, $testFunction);
    }
}