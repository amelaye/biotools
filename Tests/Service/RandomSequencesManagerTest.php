<?php


namespace Tests\MinitoolsBundle\Service;


use Amelaye\BioPHP\Api\AminoApi;
use Amelaye\BioPHP\Api\NucleotidApi;
use Amelaye\BioTools\Service\RandomSequencesManager;
use PHPUnit\Framework\TestCase;

class RandomSequencesManagerTest extends TestCase
{

    /**
     * @var mixed
     */
    protected $aminosMock;

    /**
     * @var mixed
     */
    protected $nucleoMock;
    protected $aminos;

    protected $proteins;

    public function setUp(): void
    {
        /*$this->aminos = [
          "A" => "T",
          "T" => "A",
          "G" => "C",
          "C" => "G",
        ];*/

        require 'samples/Aminos.php';
        require 'samples/Nucleotids.php';

        /**
         * Mock API
         */
        $this->aminosMock = $this->getMockBuilder(AminoApi::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAminos'])
            ->getMock();
        $this->aminosMock->method("getAminos")->willReturn($aAminosObjects);

        /**
         * Mock API
         */
        $this->nucleoMock = $this->getMockBuilder(NucleotidApi::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getNucleotids'])
            ->getMock();
        $this->nucleoMock->method("getNucleotids")->willReturn($aNucleoObjects);
    }

    public function testRandomize2ndOption30Len()
    {
        $aElements = [
          "A" => 9.0,
          "C" => 6.0,
          "G" => 6.0,
          "T" => 9.0,
        ];

        $service = new RandomSequencesManager($this->nucleoMock, $this->aminosMock);
        $testFunction = $service->randomize($aElements);

        $this->assertEquals(30, strlen($testFunction));
    }

    public function testRandomize3rdOption100Len()
    {
        $aElements = [
          "A" => 1.0,
          "C" => 2.0,
          "D" => 5.0,
          "E" => 7.0,
          "F" => 4.0,
          "G" => 7.0,
          "H" => 7.0,
          "I" => 7.0,
          "K" => 6.0,
          "L" => 9.0,
          "M" => 2.0,
          "N" => 4.0,
          "P" => 6.0,
          "Q" => 4.0,
          "R" => 5.0,
          "S" => 7.0,
          "T" => 6.0,
          "V" => 6.0,
          "W" => 2.0,
          "Y" => 3.0
        ];

        $service = new RandomSequencesManager($this->nucleoMock, $this->aminosMock);
        $testFunction = $service->randomize($aElements);

        $this->assertEquals(100, strlen($testFunction));
    }

    public function testCreateFromSeqAminos()
    {
        $iLength = 100;

        $sSequence = 'GGCAGATTCCCCCTAGACCCGCCCGCACCATGGTCAGGCATGCCCCTCCTCATCGCTGGGCACAGCCCAGAGGGT\r\n';
        $sSequence.= 'ATAAACAGTGCTGGAGGCTGGCGGGGCAGGCCAGCTGAGTCCTGAGCAGCAGCCCAGCGCAGCCACCGAGACACC\r\n';
        $sSequence.= 'ATGAGAGCCCTCACACTCCTCGCCCTATTGGCCCTGGCCGCACTTTGCATCGCTGGCCAGGCAGGTGAGTGCCCC\r\n';
        $sSequence.= 'CACCTCCCCTCAGGCCGCATTGCAGTGGGGGCTGAGAGGAGGAAGCACCATGGCCCACCTCTTCTCACCCCTTTG\r\n';
        $sSequence.= 'GCTGGCAGTCCCTTTGCAGTCTAACCACCTTGTTGCAGGCTCAATCCATTTGCCCCAGCTCTGCCCTTGCAGAG';

        $service = new RandomSequencesManager($this->nucleoMock, $this->aminosMock);
        $testFunction = $service->createFromSeq($iLength, $sSequence);

        $this->assertEquals(100, strlen($testFunction));
    }

    public function testCreateFromSeqProteins()
    {
        $iLength = 100;

        $sSequence = 'TVECRAQITESFTKRSKTVHHHLGGNNRTIKDKFVSMTGLWYYLLDPDESFGNEQLVGPHEIRQSILHIQ';
        $sSequence .= 'PMHSKIPFRNCPVLLKYGIHDPESVLGDETVECRAQITESFTKRSKTVHHHLGGNNRTIKDKFVSMTGLWYYLLDPDESFGNEQLVGPHEIRQSILHIQPMHSKIPFRNCPVLLKYGIHDPESVLGDE';

        $service = new RandomSequencesManager($this->nucleoMock, $this->aminosMock);
        $testFunction = $service->createFromSeq($iLength, $sSequence);

        // the requested length: rounding each share separately gave 99
        $this->assertEquals(100, strlen($testFunction));
    }

    public function testCreateFromACGT()
    {
        $aAminoAcids = [
          "A" => "29.5",
          "C" => "20.5",
          "G" => "20.5",
          "T" => "29.5"
        ];
        $iLength = 30;

        $service = new RandomSequencesManager($this->nucleoMock, $this->aminosMock);
        $testFunction = $service->createFromACGT($aAminoAcids, $iLength);

        $this->assertEquals(30, strlen($testFunction));
    }

    /**
     * The sequence has the requested length: 29.5/20.5/20.5/29.5 % of 3 bases are 0.885, 0.615,
     * 0.615, 0.885; rounded down to 0 each, the 3 bases go to the largest remainders, A and T, then
     * C (the first of the two ties). Rounding each share gave 4 bases. Four equal shares of 10 give
     * 3, 3, 2, 2 (it was 12 bases).
     */
    public function testCreateFromACGTHasTheRequestedLength()
    {
        $service = new RandomSequencesManager($this->nucleoMock, $this->aminosMock);

        $sResult = $service->createFromACGT(["A" => "29.5", "C" => "20.5", "G" => "20.5", "T" => "29.5"], 3);
        $this->assertSame(3, strlen($sResult));
        $this->assertSame([1, 1, 0, 1], [substr_count($sResult, "A"), substr_count($sResult, "C"), substr_count($sResult, "G"), substr_count($sResult, "T")]);

        $sResult = $service->createFromACGT(["A" => 25, "C" => 25, "G" => 25, "T" => 25], 10);
        $this->assertSame([3, 3, 2, 2], [substr_count($sResult, "A"), substr_count($sResult, "C"), substr_count($sResult, "G"), substr_count($sResult, "T")]);
        $this->assertSame(10, strlen($service->createFromAA(["A" => 1, "C" => 1, "D" => 1], 10)));
        // no weight at all: nothing to draw from
        $this->assertSame("", $service->createFromACGT(["A" => 0, "C" => 0, "G" => 0, "T" => 0], 10));
    }

    /**
     * When no length is requested (form submits 0), legacy just rounds the raw
     * occurrence counts instead of scaling them - the resulting sequence length is the
     * sum of those rounded counts, not the requested length
     */
    public function testCreateFromACGTWithoutRequestedLength()
    {
        $aAminoAcids = [
          "A" => "29.5",
          "C" => "20.5",
          "G" => "20.5",
          "T" => "29.5"
        ];

        $service = new RandomSequencesManager($this->nucleoMock, $this->aminosMock);
        $testFunction = $service->createFromACGT($aAminoAcids, 0);

        // round(29.5)=30, round(20.5)=21, round(20.5)=21, round(29.5)=30
        $this->assertEquals(102, strlen($testFunction));
    }

    public function testcreateFromAA()
    {
        $aAminoAcids = [
          "A" => "1.174",
          "C" => "2.395",
          "D" => "4.872",
          "E" => "6.662",
          "F" => "3.624",
          "G" => "7.532",
          "H" => "7.532",
          "I" => "7.532",
          "K" => "5.635",
          "L" => "9.412",
          "M" => "2.196",
          "N" => "3.789",
          "P" => "6.294",
          "Q" => "4.509",
          "R" => "5.607",
          "S" => "7.527",
          "T" => "5.685",
          "V" => "6.026",
          "W" => "1.48",
          "Y" => "2.84"
        ];

        $iLength = 100;

        $service = new RandomSequencesManager($this->nucleoMock, $this->aminosMock);
        $testFunction = $service->createFromAA($aAminoAcids, $iLength);

        // the requested length: rounding each share separately gave 99
        $this->assertEquals(100, strlen($testFunction));
    }

    /**
     * When no length is requested (form submits 0), legacy just rounds the raw
     * occurrence counts instead of scaling them
     */
    public function testCreateFromAAWithoutRequestedLength()
    {
        $aAminoAcids = [
          "A" => "1.174",
          "C" => "2.395",
          "D" => "4.872",
          "E" => "6.662",
          "F" => "3.624",
          "G" => "7.532",
          "H" => "7.532",
          "I" => "7.532",
          "K" => "5.635",
          "L" => "9.412",
          "M" => "2.196",
          "N" => "3.789",
          "P" => "6.294",
          "Q" => "4.509",
          "R" => "5.607",
          "S" => "7.527",
          "T" => "5.685",
          "V" => "6.026",
          "W" => "1.48",
          "Y" => "2.84"
        ];

        $service = new RandomSequencesManager($this->nucleoMock, $this->aminosMock);
        $testFunction = $service->createFromAA($aAminoAcids, 0);

        $this->assertEquals(105, strlen($testFunction));
    }
}