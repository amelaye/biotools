<?php
/**
 * Created by PhpStorm.
 * User: amelaye
 * Date: 2019-07-23
 * Time: 11:34
 */

namespace Tests\Service;

use Amelaye\BioPHP\Api\NucleotidApi;
use Amelaye\BioTools\Service\ChaosGameRepresentationManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;


class ChaosGameRepresentationManagerTest extends TestCase
{

    /**
     * @var mixed
     */
    protected $apiNucleoMock;
    protected $dnaComplement;

    protected $apiMock;

    protected $aNucleotidGraph;

    protected function setUp(): void
    {
        $this->aNucleotidGraph = [
            "positions_2" => ["h_pos" => 24, "v_pos" => 26],
            "intervals_2" => 64,
            "startx_2" => 10,
            "starty_2" => 90,
            "fcgr_file" => 'FCGR.svg',
            "cgr_file" => 'CGR.svg',
            "dendogram_file" => 'dendogram.svg',
            // the drawings are written to a temporary directory, never to the project tree
            "path_graphs" => sys_get_temp_dir()
        ];

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

    /**
     * @throws \Exception
     */
    public function testNumberNucleos()
    {
        $aSeqdata =  [
            "sequence" => "GTGCCGAGCTGAGTTCCTTATAAGAATTAATCTTAATTTTGTATTTTTTCCTGTAAGACAATAGGCCATGTTAATTAAACTGAAGAAG",
            "length" => "2"
        ];
        $aExpected = ["T" => 32, "A" => 28, "C" => 12, "G" => 16];

        $service = new ChaosGameRepresentationManager($this->aNucleotidGraph, $this->apiNucleoMock);
        $testFunction = $service->numberNucleos($aSeqdata);

        $this->assertEquals($aExpected, $testFunction);
    }

    public function testCreateFCGRImageWithOligoLen2()
    {
        $oligos = [
            "AA" => 98,
            "AC" => 24,
            "AG" => 36,
            "AT" => 66,
            "CA" => 39,
            "CC" => 21,
            "CG" => 4,
            "CT" => 36,
            "GA" => 38,
            "GC" => 18,
            "GG" => 21,
            "GT" => 24,
            "TA" => 50,
            "TC" => 38,
            "TG" => 39,
            "TT" => 98,
        ];

        $seq_name = "aaa";

        $aNucleotids = [
            "T" => 225,
            "A" => 225,
            "C" => 101,
            "G" => 101,
        ];

        $seq_len = 326;

        $n = 2;

        $oligo_len = "2";

        $aExpected = [
            "AA" => [0 => 10, 1 => 282, 2 => 73, 3 => 345],
            "AC" => [0 => 10, 1 => 154, 2 => 73, 3 => 217],
            "AG" => [0 => 138, 1 => 154, 2 => 201, 3 => 217],
            "AT" => [0 => 138, 1 => 282, 2 => 201, 3 => 345],
            "CA" => [0 => 10, 1 => 218, 2 => 73, 3 => 281],
            "CC" => [0 => 10, 1 => 90, 2 => 73, 3 => 153],
            "CG" => [0 => 138, 1 => 90, 2 => 201, 3 => 153],
            "CT" => [0 => 138, 1 => 218, 2 => 201, 3 => 281],
            "GA" => [0 => 74, 1 => 218, 2 => 137, 3 => 281],
            "GC" => [0 => 74, 1 => 90, 2 => 137, 3 => 153],
            "GG" => [0 => 202, 1 => 90, 2 => 265, 3 => 153],
            "GT" => [0 => 202, 1 => 218, 2 => 265, 3 => 281],
            "TA" => [0 => 74, 1 => 282, 2 => 137, 3 => 345],
            "TC" => [0 => 74, 1 => 154, 2 => 137, 3 => 217],
            "TG" => [0 => 202, 1 => 154, 2 => 265, 3 => 217],
            "TT" => [0 => 202, 1 => 282, 2 => 265, 3 => 345]
        ];

        $service = new ChaosGameRepresentationManager($this->aNucleotidGraph, $this->apiNucleoMock);
        $testFunction = $service->createFCGRImage($oligos, $seq_name, $aNucleotids, $seq_len, $n, $oligo_len)["map"];

        $this->assertEquals($aExpected, $testFunction);
    }

    public function testCreateFCGRImageException()
    {
        $this->expectException(\Exception::class);
        $oligos = [];
        $seq_name = "aaa";
        $aNucleotids = [];
        $seq_len = 326;
        $n = 2;
        $oligo_len = "2";

        $service = new ChaosGameRepresentationManager($this->aNucleotidGraph, $this->apiNucleoMock);
        $service->createFCGRImage($oligos, $seq_name, $aNucleotids, $seq_len, $n, $oligo_len);
    }

    public function testCreateFCGRImageWithOligoLen3()
    {
        $oligos = [
          "AAA" => 0,
          "AAC" => 0,
          "AAG" => 2,
          "AAT" => 4,
          "ACA" => 1,
          "ACC" => 0,
          "ACG" => 0,
          "ACT" => 0,
          "AGA" => 2,
          "AGC" => 1,
          "AGG" => 1,
          "AGT" => 1,
          "ATA" => 2,
          "ATC" => 1,
          "ATG" => 0,
          "ATT" => 3,
          "CAA" => 1,
          "CAC" => 0,
          "CAG" => 0,
          "CAT" => 0,
          "CCA" => 0,
          "CCC" => 0,
          "CCG" => 1,
          "CCT" => 2,
          "CGA" => 1,
          "CGC" => 0,
          "CGG" => 0,
          "CGT" => 0,
          "CTA" => 0,
          "CTC" => 0,
          "CTG" => 2,
          "CTT" => 2,
          "GAA" => 1,
          "GAC" => 1,
          "GAG" => 2,
          "GAT" => 0,
          "GCA" => 0,
          "GCC" => 1,
          "GCG" => 0,
          "GCT" => 1,
          "GGA" => 0,
          "GGC" => 0,
          "GGG" => 0,
          "GGT" => 0,
          "GTA" => 2,
          "GTC" => 0,
          "GTG" => 1,
          "GTT" => 1,
          "TAA" => 4,
          "TAC" => 0,
          "TAG" => 1,
          "TAT" => 2,
          "TCA" => 0,
          "TCC" => 2,
          "TCG" => 0,
          "TCT" => 1,
          "TGA" => 1,
          "TGC" => 1,
          "TGG" => 0,
          "TGT" => 2,
          "TTA" => 3,
          "TTC" => 2,
          "TTG" => 1,
          "TTT" => 6
        ];

        $seq_name = "aaa";

        $aNucleotids = [
            "T" => 26,
            "A" => 18,
            "C" => 9,
            "G" => 12
        ];

        $seq_len = 65;

        $n = 1;

        $oligo_len = "3";

        $aExpected = [
            "AAA" => [
                0 => 10,
                1 => 314,
                2 => 41,
                3 => 345,
            ],
            "AAC" => [
                0 => 10,
                1 => 186,
                2 => 41,
                3 => 217,
            ],
            "AAG" => [
                0 => 138,
                1 => 186,
                2 => 169,
                3 => 217,
            ],
            "AAT" => [
                0 => 138,
                1 => 314,
                2 => 169,
                3 => 345,
            ],
            "ACA" => [
                0 => 10,
                1 => 250,
                2 => 41,
                3 => 281,
            ],
            "ACC" => [
                0 => 10,
                1 => 122,
                2 => 41,
                3 => 153,
            ],
            "ACG" => [
                0 => 138,
                1 => 122,
                2 => 169,
                3 => 153,
            ],
            "ACT" => [
                0 => 138,
                1 => 250,
                2 => 169,
                3 => 281,
            ],
            "AGA" => [
                0 => 74,
                1 => 250,
                2 => 105,
                3 => 281,
            ],
            "AGC" => [
                0 => 74,
                1 => 122,
                2 => 105,
                3 => 153,
            ],
            "AGG" => [
                0 => 202,
                1 => 122,
                2 => 233,
                3 => 153,
            ],
            "AGT" => [
                0 => 202,
                1 => 250,
                2 => 233,
                3 => 281,
            ],
            "ATA" => [
                0 => 74,
                1 => 314,
                2 => 105,
                3 => 345,
            ],
            "ATC" => [
                0 => 74,
                1 => 186,
                2 => 105,
                3 => 217,
            ],
            "ATG" => [
                0 => 202,
                1 => 186,
                2 => 233,
                3 => 217,
            ],
            "ATT" => [
                0 => 202,
                1 => 314,
                2 => 233,
                3 => 345,
            ],
            "CAA" => [
                0 => 10,
                1 => 282,
                2 => 41,
                3 => 313,
            ],
            "CAC" => [
                0 => 10,
                1 => 154,
                2 => 41,
                3 => 185,
            ],
            "CAG" => [
                0 => 138,
                1 => 154,
                2 => 169,
                3 => 185,
            ],
            "CAT" => [
                0 => 138,
                1 => 282,
                2 => 169,
                3 => 313,
            ],
            "CCA" => [
                0 => 10,
                1 => 218,
                2 => 41,
                3 => 249,
            ],
            "CCC" => [
                0 => 10,
                1 => 90,
                2 => 41,
                3 => 121,
            ],
            "CCG" => [
                0 => 138,
                1 => 90,
                2 => 169,
                3 => 121,
            ],
            "CCT" => [
                0 => 138,
                1 => 218,
                2 => 169,
                3 => 249,
            ],
            "CGA" => [
                0 => 74,
                1 => 218,
                2 => 105,
                3 => 249,
            ],
            "CGC" => [
                0 => 74,
                1 => 90,
                2 => 105,
                3 => 121,
            ],
            "CGG" => [
                0 => 202,
                1 => 90,
                2 => 233,
                3 => 121,
            ],
            "CGT" => [
                0 => 202,
                1 => 218,
                2 => 233,
                3 => 249,
            ],
            "CTA" => [
                0 => 74,
                1 => 282,
                2 => 105,
                3 => 313,
            ],
            "CTC" => [
                0 => 74,
                1 => 154,
                2 => 105,
                3 => 185,
            ],
            "CTG" => [
                0 => 202,
                1 => 154,
                2 => 233,
                3 => 185,
            ],
            "CTT" => [
                0 => 202,
                1 => 282,
                2 => 233,
                3 => 313,
            ],
            "GAA" => [
                0 => 42,
                1 => 282,
                2 => 73,
                3 => 313,
            ],
            "GAC" => [
                0 => 42,
                1 => 154,
                2 => 73,
                3 => 185,
            ],
            "GAG" => [
                0 => 170,
                1 => 154,
                2 => 201,
                3 => 185,
            ],
            "GAT" => [
                0 => 170,
                1 => 282,
                2 => 201,
                3 => 313,
            ],
            "GCA" => [
                0 => 42,
                1 => 218,
                2 => 73,
                3 => 249,
            ],
            "GCC" => [
                0 => 42,
                1 => 90,
                2 => 73,
                3 => 121,
            ],
            "GCG" => [
                0 => 170,
                1 => 90,
                2 => 201,
                3 => 121,
            ],
            "GCT" => [
                0 => 170,
                1 => 218,
                2 => 201,
                3 => 249,
            ],
            "GGA" => [
                0 => 106,
                1 => 218,
                2 => 137,
                3 => 249,
            ],
            "GGC" => [
                0 => 106,
                1 => 90,
                2 => 137,
                3 => 121,
            ],
            "GGG" => [
                0 => 234,
                1 => 90,
                2 => 265,
                3 => 121,
            ],
            "GGT" => [
                0 => 234,
                1 => 218,
                2 => 265,
                3 => 249,
            ],
            "GTA" => [
                0 => 106,
                1 => 282,
                2 => 137,
                3 => 313,
            ],
            "GTC" => [
                0 => 106,
                1 => 154,
                2 => 137,
                3 => 185,
            ],
            "GTG" => [
                0 => 234,
                1 => 154,
                2 => 265,
                3 => 185,
            ],
            "GTT" => [
                0 => 234,
                1 => 282,
                2 => 265,
                3 => 313,
            ],
            "TAA" => [
                0 => 42,
                1 => 314,
                2 => 73,
                3 => 345,
            ],
            "TAC" => [
                0 => 42,
                1 => 186,
                2 => 73,
                3 => 217,
            ],
            "TAG" => [
                0 => 170,
                1 => 186,
                2 => 201,
                3 => 217,
            ],
            "TAT" => [
                0 => 170,
                1 => 314,
                2 => 201,
                3 => 345,
            ],
            "TCA" => [
                0 => 42,
                1 => 250,
                2 => 73,
                3 => 281,
            ],
            "TCC" => [
                0 => 42,
                1 => 122,
                2 => 73,
                3 => 153,
            ],
            "TCG" => [
                0 => 170,
                1 => 122,
                2 => 201,
                3 => 153,
            ],
            "TCT" => [
                0 => 170,
                1 => 250,
                2 => 201,
                3 => 281,
            ],
            "TGA" => [
                0 => 106,
                1 => 250,
                2 => 137,
                3 => 281,
            ],
            "TGC" => [
                0 => 106,
                1 => 122,
                2 => 137,
                3 => 153,
            ],
            "TGG" => [
                0 => 234,
                1 => 122,
                2 => 265,
                3 => 153,
            ],
            "TGT" => [
                0 => 234,
                1 => 250,
                2 => 265,
                3 => 281,
            ],
            "TTA" => [
                0 => 106,
                1 => 314,
                2 => 137,
                3 => 345,
            ],
            "TTC" => [
                0 => 106,
                1 => 186,
                2 => 137,
                3 => 217,
            ],
            "TTG" => [
                0 => 234,
                1 => 186,
                2 => 265,
                3 => 217,
            ],
            "TTT" => [
                0 => 234,
                1 => 314,
                2 => 265,
                3 => 345,
            ]
        ];

        $service = new ChaosGameRepresentationManager($this->aNucleotidGraph, $this->apiNucleoMock);
        $testFunction = $service->createFCGRImage($oligos, $seq_name, $aNucleotids, $seq_len, $n, $oligo_len)["map"];

        $this->assertEquals($aExpected, $testFunction);
    }

    /**
     * The 3-mer grid is 8 x 8 cells of 32 px. The first base is the least significant bit
     * (G/T: +1 column, A/T: +1 row), the second weighs 2 and the third 4. Expected cells are
     * worked out here by hand from that rule, not read back from the calculator.
     */
    public function testTrinucleotideLabelsSitInTheirOwnCell()
    {
        $service = new ChaosGameRepresentationManager($this->aNucleotidGraph, $this->apiNucleoMock);
        $aResult = $service->createFCGRImage(
            ["AAA" => 1, "TTT" => 2], "labels", ["T" => 1, "A" => 1, "C" => 1, "G" => 1], 3, 1, "3"
        );
        $sSvg = file_get_contents($aResult["file"]);
        unlink($aResult["file"]);

        $aPositions = [];
        preg_match_all('/<text x="([\d.]+)" y="([\d.]+)"[^>]*>([ACGT]{3})<\/text>/', $sSvg, $aMatches, PREG_SET_ORDER);
        foreach ($aMatches as $aMatch) {
            $aPositions[$aMatch[3]] = [(float) $aMatch[1], (float) $aMatch[2]];
        }
        $this->assertCount(64, $aPositions);

        // CCC sits in cell (0, 0): it gives the pixel origin of the labels
        list($fX0, $fY0) = $aPositions["CCC"];

        foreach ($aPositions as $sWord => $aPosition) {
            $iColumn = 0;
            $iRow = 0;
            foreach (str_split($sWord) as $i => $sBase) {
                $iColumn += in_array($sBase, ["G", "T"]) ? (1 << $i) : 0;
                $iRow    += in_array($sBase, ["A", "T"]) ? (1 << $i) : 0;
            }
            $this->assertEqualsWithDelta($fX0 + $iColumn * 32, $aPosition[0], 0.01, "$sWord column $iColumn");
            $this->assertEqualsWithDelta($fY0 + $iRow * 32, $aPosition[1], 0.01, "$sWord row $iRow");
        }

        // the cells that were mislabelled or piled up before
        $this->assertEqualsWithDelta($fX0 + 64, $aPositions["CTC"][0], 0.01);
        $this->assertEqualsWithDelta($fX0 + 224, $aPositions["TTT"][0], 0.01);
    }

    /**
     * Chaos game on a 256 px picture, from the centre (128, 128), half way to the corner of each base
     * (C top left, G top right, A bottom left, T bottom right). Worked out by hand:
     * A -> (64, 192), then T -> (160, 224), then G -> (208, 112), then C -> (104, 56).
     * Symbols other than A, C, G, T (N, a space) and the case of the letters do not matter.
     */
    public function testCGRImagePlaysTheChaosGameFromTheCentre()
    {
        $service = new ChaosGameRepresentationManager($this->aNucleotidGraph, $this->apiNucleoMock);
        $path = $service->createCGRImage("Seq", "aT N gC", 256);
        $content = file_get_contents($path);
        unlink($path);

        $this->assertStringContainsString('d="M64 192.5h1M160 224.5h1M208 112.5h1M104 56.5h1"', $content);
        $this->assertStringContainsString('Seq (5 bp)', $content);
    }

    /**
     * Every oligonucleotide is the same count: max - min is 0, there was no contrast to draw
     * and the shading divided by it
     */
    public function testCreateFCGRImageWithEqualCountsDoesNotDivideByZero()
    {
        $service = new ChaosGameRepresentationManager($this->aNucleotidGraph, $this->apiNucleoMock);
        $aOligos = [];
        foreach (["AA", "AC", "CA", "TT"] as $sOligo) {
            $aOligos[$sOligo] = 3;
        }

        $aResult = $service->createFCGRImage($aOligos, "flat", ["A" => 1, "C" => 1, "G" => 1, "T" => 1], 10, 1, "2");

        $this->assertCount(4, $aResult["map"]);
        $this->assertFileExists($aResult["file"]);
        unlink($aResult["file"]);
    }

    public function testOligoCountsDelegatesToTheCalculator()
    {
        $service = new ChaosGameRepresentationManager($this->aNucleotidGraph, $this->apiNucleoMock);

        $aOne = $service->oligoCounts("acgNtt", 2, 1);
        $this->assertCount(16, $aOne);
        $this->assertSame(1, $aOne["AC"]);
        $this->assertSame(1, $aOne["CG"]);
        $this->assertSame(0, $aOne["GT"]);   // an N separates G and T: no word across it
        $this->assertSame(1, $aOne["TT"]);
        $this->assertSame(3, array_sum($aOne));

        // both strands: the reverse complement of ACGTT is AACGT, adding AA, AC, CG, GT
        $aTwo = $service->oligoCounts("ACGTT", 2, 2);
        $this->assertSame(2, $aTwo["AC"]);
        $this->assertSame(1, $aTwo["AA"]);
        $this->assertSame(2, $aTwo["GT"]);
    }

    /**
     * Two strands, by hand: "ACGNTT" is the stretches ACG and TT. ACG: AC, CG; its reverse
     * complement CGT: CG, GT. TT: TT; its reverse complement AA: AA.
     * Totals AC 1, CG 2, GT 1, TT 1, AA 1 - and no word is made across the N.
     */
    public function testOligoCountsOnTwoStrandsKeepsEachStretchApart()
    {
        $service = new ChaosGameRepresentationManager($this->aNucleotidGraph, $this->apiNucleoMock);

        $aTwo = $service->oligoCounts("ACGNTT", 2, 2);

        $this->assertCount(16, $aTwo);
        $this->assertSame(["AA" => 1, "AC" => 1, "CG" => 2, "GT" => 1, "TT" => 1], array_filter($aTwo));
        // a gap or a space breaks the sequence like an N does
        $this->assertSame($aTwo, $service->oligoCounts("ACG-TT", 2, 2));
        $this->assertSame($aTwo, $service->oligoCounts("acg tt", 2, 2));
    }

    public function testOligoCountsWithoutAnyBaseCountsZero()
    {
        $service = new ChaosGameRepresentationManager($this->aNucleotidGraph, $this->apiNucleoMock);

        $aCounts = $service->oligoCounts("NNN", 3, 1);

        $this->assertCount(64, $aCounts);
        $this->assertSame(0, array_sum($aCounts));
    }

    public function testOligoCountsRefusesALengthBioPhpDoesNotHandle()
    {
        $service = new ChaosGameRepresentationManager($this->aNucleotidGraph, $this->apiNucleoMock);

        $this->expectException(\InvalidArgumentException::class);
        $service->oligoCounts("ACGT", 9, 1);
    }

    public function testFCGRComputeSingleStrand()
    {
        $service = new ChaosGameRepresentationManager($this->aNucleotidGraph, $this->apiNucleoMock);
        $testFunction = $service->FCGRCompute("ACGTACGT", 2, 1);

        $this->assertEquals(["sequence" => "ACGTACGT", "length" => 2], $testFunction);
    }

    public function testFCGRComputeDoubleStrand()
    {
        $service = new ChaosGameRepresentationManager($this->aNucleotidGraph, $this->apiNucleoMock);
        $testFunction = $service->FCGRCompute("ACGTACGT", 2, 2);

        $this->assertEquals(["sequence" => "ACGTACGT ACGTACGT", "length" => 2], $testFunction);
    }

    public function testCreateCGRImage()
    {
        $service = new ChaosGameRepresentationManager($this->aNucleotidGraph, $this->apiNucleoMock);
        $path = $service->createCGRImage("MySeq", "ACGTACGTACGTACGT", 64);

        $this->assertFileExists($path);

        $content = file_get_contents($path);
        $this->assertStringContainsString('<svg xmlns="http://www.w3.org/2000/svg" width="64" height="84" viewBox="0 0 64 84">', $content);
        $this->assertStringContainsString('MySeq (16 bp)', $content);

        unlink($path);
    }

    #[DataProvider('provideCGRComputeAutoSizes')]
    public function testCGRComputeAutoSizing($iSeqLen, $iExpectedWidth)
    {
        // a sequence over 1,000,000 bp needs more than PHP's default 128M memory_limit
        // to draw every point, just like the legacy script would in production; raising
        // it here is one-way for the rest of the test run, since PHP cannot shrink the
        // limit back below memory already in use. Peak usage measured at ~248M on PHP
        // 8.2 (a lower version needs more headroom than 8.3+, whose array/hashtable
        // overhead per element is smaller), so 512M is used to keep margin across the
        // whole supported PHP range rather than tuning to the thinnest observed case.
        if ((int) ini_get('memory_limit') < 512) {
            ini_set('memory_limit', '512M');
        }

        $service = new ChaosGameRepresentationManager($this->aNucleotidGraph, $this->apiNucleoMock);
        $path = $service->CGRCompute("Seq", str_repeat("A", $iSeqLen), "auto");
        $content = file_get_contents($path);

        $this->assertStringContainsString('width="' . $iExpectedWidth . '"', $content);

        unlink($path);
    }

    public static function provideCGRComputeAutoSizes(): array
    {
        return [
            'up to 100000 bp uses 256' => [100, 256],
            'over 100000 bp uses 512' => [150000, 512],
            // legacy bug fix: this tier used to be unreachable, see ChaosGameRepresentationManager::CGRCompute
            'over 1000000 bp uses 1024' => [1000001, 1024],
        ];
    }
}
