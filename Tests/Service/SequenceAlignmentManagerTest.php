<?php

namespace Tests\MinitoolsBundle\Service;

use Amelaye\BioPHP\Api\Pam250MatrixDigitApi;
use PHPUnit\Framework\TestCase;
use Amelaye\BioTools\Service\SequenceAlignmentManager;

class SequenceAlignmentManagerTest extends TestCase
{

    /**
     * @var mixed
     */
    protected $matrixMock;
    protected $matrix;

    public function setUp(): void
    {
        require 'samples/Pam250Matrix.php';

        /**
         * Mock API
         */
        $this->matrixMock = $this->getMockBuilder(Pam250MatrixDigitApi::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getPam250Matrix'])
            ->getMock();
        $this->matrixMock->method("getPam250Matrix")->willReturn($aPam250Matrix);
    }

    /**
     * Without any common base there is no local alignment: an empty result with a message,
     * not an exception
     */
    public function testAlignDNAWithoutLocalSimilarity()
    {
        $service = new SequenceAlignmentManager($this->matrixMock);
        $aResult = $service->alignDNA("AAAA", "TTTT");

        $this->assertSame("", $aResult["seqa"]);
        $this->assertSame("", $aResult["seqb"]);
        $this->assertStringContainsString("No local similarity", $aResult["message"]);
    }

    public function testAlignProteinsWithoutLocalSimilarity()
    {
        $service = new SequenceAlignmentManager($this->matrixMock);
        // W against C scores -8 in PAM250: nothing positive to align
        $aResult = $service->alignProteins("WWWW", "CCCC");

        $this->assertSame("", $aResult["seqa"]);
        $this->assertStringContainsString("No local similarity", $aResult["message"]);
    }

    /**
     * B, Z, J, O and * have no PAM250 score
     */
    public function testAlignProteinsWithASymbolPam250DoesNotKnow()
    {
        $service = new SequenceAlignmentManager($this->matrixMock);
        $aResult = $service->alignProteins("MKBL", "MKDL");

        $this->assertSame("", $aResult["seqa"]);
        $this->assertStringContainsString("not supported by the PAM250 matrix", $aResult["message"]);
    }

    /**
     * Identical sequences align without any gap or flank
     */
    public function testAlignDNAIdentical()
    {
        $service = new SequenceAlignmentManager($this->matrixMock);

        $this->assertEquals(["seqa" => "ACGTACGT", "seqb" => "ACGTACGT"], $service->alignDNA("acgtacgt", "ACGTACGT"));
    }

    /**
     * The best local region is aligned and the unaligned ends of the longer sequence are
     * written facing gaps
     */
    public function testAlignDNAShowsUnalignedEndsFacingGaps()
    {
        $service = new SequenceAlignmentManager($this->matrixMock);

        // best local region: the whole of the shorter sequence (8 matches x 2 = 16)
        $this->assertEquals(
            ["seqa" => "ACGTACGTACGT", "seqb" => "ACGTACGT----"],
            $service->alignDNA("ACGTACGTACGT", "ACGTACGT")
        );
        // the 7 leading bases of B have no counterpart in A, as in the legacy minitool output
        $this->assertEquals(
            ["seqa" => "-------GGAGTGAGGGGAGC", "seqb" => "CGCATGCGGAGTGAGGGGAGC"],
            $service->alignDNA("GGAGTGAGGGGAGC", "CGCATGCGGAGTGAGGGGAGC")
        );
    }

    /**
     * The whole job is delegated to BioPHP's aligner, with the legacy minitool scores
     */
    public function testAlignDNADelegatesToSmithWatermanAligner()
    {
        $oAligner = $this->createMock(\Amelaye\BioPHP\Domain\Alignment\Interfaces\SmithWatermanAlignerInterface::class);
        $oAligner->expects($this->once())
            ->method('align')
            ->with(
                $this->callback(fn($o) => $o->getValue() === "TTACGTAA"),
                $this->callback(fn($o) => $o->getValue() === "GGACGTCC"),
                $this->callback(fn($o) => $o->score("A", "A") === 2 && $o->score("A", "C") === -1),
                -4
            )
            ->willReturn(new \Amelaye\BioPHP\Domain\Alignment\Result\PairwiseAlignmentResult("ACGT", "ACGT", 8, 2, 5, 2, 5));

        $service = new SequenceAlignmentManager($this->matrixMock, $oAligner);

        // both sides have a 2-base flank before and after the aligned region
        $this->assertEquals(["seqa" => "TTACGTAA", "seqb" => "GGACGTCC"], $service->alignDNA("ttacgtaa", "ggacgtcc"));
    }

    public function testAlignDNAPadsFlanksOfDifferentLengths()
    {
        $oAligner = $this->createMock(\Amelaye\BioPHP\Domain\Alignment\Interfaces\SmithWatermanAlignerInterface::class);
        $oAligner->method('align')
            ->willReturn(new \Amelaye\BioPHP\Domain\Alignment\Result\PairwiseAlignmentResult("ACGT", "ACGT", 8, 1, 4, 3, 6));

        $service = new SequenceAlignmentManager($this->matrixMock, $oAligner);

        // left: "T" vs "GGG" (right-aligned), right: "AA" vs "" (left-aligned)
        $this->assertEquals(["seqa" => "--TACGTAA", "seqb" => "GGGACGT--"], $service->alignDNA("TACGTAA", "GGGACGT"));
    }

    public function testAlignDNARejectsNonDnaSequence()
    {
        $service = new SequenceAlignmentManager($this->matrixMock);

        $this->expectException(\Exception::class);
        $service->alignDNA("ACGT", "ACGZ");
    }

    /**
     * Proteins are scored with the PAM250 matrix and a -50 gap
     */
    public function testAlignProteins()
    {
        $service = new SequenceAlignmentManager($this->matrixMock);

        $this->assertEquals(["seqa" => "MKVL", "seqb" => "MKVL"], $service->alignProteins("MKVL", "mkvl"));
    }

    public function testAlignProteinsDelegatesWithPam250AndGapPenalty()
    {
        $oAligner = $this->createMock(\Amelaye\BioPHP\Domain\Alignment\Interfaces\SmithWatermanAlignerInterface::class);
        $oAligner->expects($this->once())
            ->method('align')
            ->with($this->anything(), $this->anything(), $this->anything(), -50)
            ->willReturn(new \Amelaye\BioPHP\Domain\Alignment\Result\PairwiseAlignmentResult("MK", "MK", 14, 0, 1, 0, 1));

        $service = new SequenceAlignmentManager($this->matrixMock, $oAligner);

        $this->assertEquals(["seqa" => "MK", "seqb" => "MK"], $service->alignProteins("MK", "MK"));
    }

    public function testCompareAlignment()
    {
        $seqa = "-------GGAGTGAGGGGAGCAGTTGGCTGA";
        $seqb = "CGCATGCGGAGTGAGGGGAGCAGTTGGGAACA";

        $aExpected = "       ||||||||||||||||||||    ";

        $service = new SequenceAlignmentManager($this->matrixMock);
        $testFunction = $service->compareAlignment($seqa, $seqb);

        $this->assertEquals($aExpected, $testFunction);
    }

    public function testCompareAlignmentIdentical()
    {
        $seqa = "ACGT";
        $seqb = "ACGT";

        $service = new SequenceAlignmentManager($this->matrixMock);
        $testFunction = $service->compareAlignment($seqa, $seqb);

        $this->assertEquals("||||", $testFunction);
    }
}