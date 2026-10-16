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

    /**
     * With or without an alignment the result has the same keys, so that a template reading score or
     * message does not fail on one of the two cases
     */
    public function testAlignmentResultsAlwaysHaveTheSameKeys()
    {
        $service = new SequenceAlignmentManager($this->matrixMock);
        $aKeys = array_keys($service->alignDNA("ACGT", "ACGT"));

        $this->assertSame($aKeys, array_keys($service->alignDNA("AAAA", "TTTT")));
        $this->assertSame($aKeys, array_keys($service->alignProteins("MKBL", "MKDL")));
        $this->assertNull($service->alignDNA("AAAA", "TTTT")["score"]);
        $this->assertNull($service->alignDNA("ACGT", "ACGT")["message"]);
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

        $this->assertEquals(
            ["seqa" => "ACGTACGT", "seqb" => "ACGTACGT", "score" => 16, "startA" => 1, "endA" => 8, "startB" => 1, "endB" => 8, "message" => null],
            $service->alignDNA("acgtacgt", "ACGTACGT")
        );
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
            ["seqa" => "ACGTACGTacgt", "seqb" => "ACGTACGT----", "score" => 16, "startA" => 1, "endA" => 8, "startB" => 1, "endB" => 8, "message" => null],
            $service->alignDNA("ACGTACGTACGT", "ACGTACGT")
        );
        // the 7 leading bases of B have no counterpart in A, as in the legacy minitool output
        $this->assertEquals(
            ["seqa" => "-------GGAGTGAGGGGAGC", "seqb" => "cgcatgcGGAGTGAGGGGAGC", "score" => 28, "startA" => 1, "endA" => 14, "startB" => 8, "endB" => 21, "message" => null],
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
        $aResult = $service->alignDNA("ttacgtaa", "ggacgtcc");
        $this->assertSame("ttACGTaa", $aResult["seqa"]);
        $this->assertSame("ggACGTcc", $aResult["seqb"]);
        $this->assertSame(8, $aResult["score"]);
        // the aligned region is bases 3 to 6 of each sequence
        $this->assertSame([3, 6, 3, 6], [$aResult["startA"], $aResult["endA"], $aResult["startB"], $aResult["endB"]]);
    }

    /**
     * An ambiguity code facing a base it may stand for scores 0, not a match: it neither confirms nor
     * refutes. N/N and R/A are 0, R/C (A or G against C) and A/C are -1, A/A is +2
     */
    public function testAlignDNAScoresAmbiguityCodesNeutrally()
    {
        $oAligner = $this->createMock(\Amelaye\BioPHP\Domain\Alignment\Interfaces\SmithWatermanAlignerInterface::class);
        $oAligner->expects($this->once())
            ->method('align')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->callback(fn($o) => $o->score("A", "A") === 2 && $o->score("A", "C") === -1
                    && $o->score("N", "N") === 0 && $o->score("N", "A") === 0
                    && $o->score("R", "A") === 0 && $o->score("R", "C") === -1),
                -4
            )
            ->willReturn(new \Amelaye\BioPHP\Domain\Alignment\Result\PairwiseAlignmentResult("ACGT", "ACGT", 8, 0, 3, 0, 3));

        (new SequenceAlignmentManager($this->matrixMock, $oAligner))->alignDNA("ACGT", "ACGT");
    }

    /**
     * Two stretches of N are not an alignment: with N/N at +2 they scored as well as real matches
     */
    public function testAlignDNAOfTwoStretchesOfNHasNoSimilarity()
    {
        $service = new SequenceAlignmentManager($this->matrixMock);
        $aResult = $service->alignDNA("NNNNNNNN", "NNNNNNNN");

        $this->assertNull($aResult["score"]);
        $this->assertStringContainsString("No local similarity", $aResult["message"]);
    }

    /**
     * ACNGT against ACAGT: A/A +2, C/C +2, N/A 0, G/G +2, T/T +2 = 8 (with N/A as a mismatch, 7)
     */
    public function testAlignDNAKeepsAnAmbiguousBaseInsideAMatchingRegion()
    {
        $service = new SequenceAlignmentManager($this->matrixMock);
        $aResult = $service->alignDNA("ACNGT", "ACAGT");

        $this->assertSame("ACNGT", $aResult["seqa"]);
        $this->assertSame("ACAGT", $aResult["seqb"]);
        $this->assertSame(8, $aResult["score"]);
    }

    public function testAlignDNAPadsFlanksOfDifferentLengths()
    {
        $oAligner = $this->createMock(\Amelaye\BioPHP\Domain\Alignment\Interfaces\SmithWatermanAlignerInterface::class);
        $oAligner->method('align')
            ->willReturn(new \Amelaye\BioPHP\Domain\Alignment\Result\PairwiseAlignmentResult("ACGT", "ACGT", 8, 1, 4, 3, 6));

        $service = new SequenceAlignmentManager($this->matrixMock, $oAligner);

        // left: "T" vs "GGG" (right-aligned), right: "AA" vs "" (left-aligned)
        $aResult = $service->alignDNA("TACGTAA", "GGGACGT");
        $this->assertSame("--tACGTaa", $aResult["seqa"]);
        $this->assertSame("gggACGT--", $aResult["seqb"]);
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

        $aResult = $service->alignProteins("MKVL", "mkvl");
        $this->assertSame("MKVL", $aResult["seqa"]);
        $this->assertSame("MKVL", $aResult["seqb"]);
        $this->assertSame(1, $aResult["startA"]);
        $this->assertSame(4, $aResult["endA"]);
    }

    public function testAlignProteinsDelegatesWithPam250AndGapPenalty()
    {
        $oAligner = $this->createMock(\Amelaye\BioPHP\Domain\Alignment\Interfaces\SmithWatermanAlignerInterface::class);
        $oAligner->expects($this->once())
            ->method('align')
            ->with($this->anything(), $this->anything(), $this->anything(), -50)
            ->willReturn(new \Amelaye\BioPHP\Domain\Alignment\Result\PairwiseAlignmentResult("MK", "MK", 14, 0, 1, 0, 1));

        $service = new SequenceAlignmentManager($this->matrixMock, $oAligner);

        $aResult = $service->alignProteins("MK", "MK");
        $this->assertSame("MK", $aResult["seqa"]);
        $this->assertSame(14, $aResult["score"]);
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

    /**
     * "TTACGTAA" / "GGACGTCC" only aligns ACGT: the lower case flanks are neither aligned nor
     * compared, even where they hold the same base (the two "a" on the right)
     */
    public function testCompareAlignmentIgnoresTheFlanks()
    {
        $service = new SequenceAlignmentManager($this->matrixMock);

        $this->assertSame("  ||||  ", $service->compareAlignment("ttACGTaa", "ttACGTaa"));
        $this->assertSame("  ||||  ", $service->compareAlignment("ttACGTaa", "ggACGTaa"));
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