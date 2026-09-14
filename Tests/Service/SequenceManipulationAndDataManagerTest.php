<?php
/**
 * Tests of the "DNA Sequence Manipulation" and "GC Content Finder" minitools
 * Cases taken from biophp.org's sequence_manipulation_and_data.php and gc_content_finder.php
 */
namespace Tests\MinitoolsBundle\Service;

use Amelaye\BioTools\Service\SequenceManipulationAndDataManager;
use PHPUnit\Framework\TestCase;

class SequenceManipulationAndDataManagerTest extends TestCase
{
    /**
     * @var SequenceManipulationAndDataManager
     */
    private $service;

    public function setUp(): void
    {
        $this->service = new SequenceManipulationAndDataManager();
    }

    /**
     * A sequence shorter than one 70 character line is written as a single block: the
     * forward strand, the second strand, then a blank line. Both rows are labelled with
     * the length of the sequence, because the legacy minitool labels the last line of the
     * output with the total length instead of the offset.
     */
    public function testDisplayBothStrandsShorterThanOneLine()
    {
        $sExpected = "ATGGCCATTA\t10\n" . "TACCGGTAAT\t10\n" . "\n";

        $this->assertEquals($sExpected, $this->service->displayBothStrands("ATGGCCATTA"));
    }

    /**
     * Over several lines, every line but the last is labelled with the offset of the base
     * it starts on (0, then 70), and the last one carries the total length.
     */
    public function testDisplayBothStrandsWrapsEverySeventyBases()
    {
        $sSequence = str_repeat("ATGGCCATTA", 15); // 150 bases

        $sResult = $this->service->displayBothStrands($sSequence);
        $aLines  = explode("\n", $sResult);

        // 3 lines per block (forward, second strand, blank) for 3 blocks, plus the final ""
        $this->assertCount(10, $aLines);
        $this->assertSame("0", substr($aLines[0], strpos($aLines[0], "\t") + 1));
        $this->assertSame("0", substr($aLines[1], strpos($aLines[1], "\t") + 1));
        $this->assertSame("", $aLines[2]);
        $this->assertSame("70", substr($aLines[3], strpos($aLines[3], "\t") + 1));
        $this->assertSame("150", substr($aLines[6], strpos($aLines[6], "\t") + 1));

        // the two full lines hold exactly 70 bases, the last one the 10 remaining ones
        $this->assertSame(70, strpos($aLines[0], "\t"));
        $this->assertSame(70, strpos($aLines[3], "\t"));
        $this->assertSame(10, strpos($aLines[6], "\t"));
    }

    /**
     * Matches the reference minitool: biophp.org draws the second row with Complement(),
     * not the reverse complement, so that it reads under the forward strand base by base
     * (A over T, G over C, ...) instead of being reversed.
     */
    public function testDisplayBothStrandsSecondRowIsThePlainComplement()
    {
        $sResult = $this->service->displayBothStrands("ATGGCCATTA");
        $aLines  = explode("\n", $sResult);

        $sSecondRow = substr($aLines[1], 0, strpos($aLines[1], "\t"));

        $this->assertSame("TACCGGTAAT", $sSecondRow);
    }

    /**
     * Degenerated nucleotides are complemented too (Y <-> R, K <-> M, ...), base for base,
     * without reversing the sequence
     */
    public function testDisplayBothStrandsHandlesDegeneratedNucleotides()
    {
        $sResult = $this->service->displayBothStrands("ATGCYRWSKM");
        $aLines  = explode("\n", $sResult);

        $this->assertSame("TACGRYWSMK", substr($aLines[1], 0, strpos($aLines[1], "\t")));
    }

    public function testGcContent()
    {
        $this->assertEquals(40, $this->service->gcContent("ATGGCCATTA"));
    }

    public function testGcContentOfAFullyGcSequence()
    {
        $this->assertEquals(100, $this->service->gcContent("GCGCGCGCGC"));
    }

    public function testGcContentOfASequenceWithoutAnyGorC()
    {
        $this->assertEquals(0, $this->service->gcContent("ATATATATAT"));
    }

    /**
     * The percentage is rounded to two decimals: 1 G out of 3 bases is 33.33
     */
    public function testGcContentIsRoundedToTwoDecimals()
    {
        $this->assertEquals(33.33, $this->service->gcContent("ATG"));
    }

    /**
     * An empty sequence divides by its own length: PHP 8 raises a DivisionByZeroError,
     * which is an Error and not caught by the service's catch(\Exception)
     */
    public function testGcContentOfAnEmptySequence()
    {
        $this->expectException(\DivisionByZeroError::class);

        $this->service->gcContent("");
    }

    /**
     * chunk_split() ends every 70 character chunk - and the string itself - with a CRLF
     */
    public function testToRna()
    {
        $this->assertEquals("AUGGCCAUUA\r\n", $this->service->toRNA("ATGGCCATTA"));
    }

    public function testToRnaLeavesASequenceWithoutThymineUnchanged()
    {
        $this->assertEquals("AGCCGCAAGC\r\n", $this->service->toRNA("AGCCGCAAGC"));
    }

    public function testToRnaSplitsInSeventyCharacterChunks()
    {
        $sSequence = str_repeat("T", 150);

        $this->assertEquals(
            str_repeat("U", 70) . "\r\n" . str_repeat("U", 70) . "\r\n" . str_repeat("U", 10) . "\r\n",
            $this->service->toRNA($sSequence)
        );
    }

    /**
     * chunk_split() emits its terminator even on an empty string
     */
    public function testToRnaOfAnEmptySequence()
    {
        $this->assertEquals("\r\n", $this->service->toRNA(""));
    }

    public function testAcgtContent()
    {
        $sExpected = "Nucleotide composition\nA: 3\nC: 2\nG: 2\nT: 3\n\n";

        $this->assertEquals($sExpected, $this->service->acgtContent("ATGGCCATTA"));
    }

    /**
     * The four bases are always listed, even the ones absent from the sequence
     */
    public function testAcgtContentAlwaysListsTheFourBases()
    {
        $sExpected = "Nucleotide composition\nA: 10\nC: 0\nG: 0\nT: 0\n\n";

        $this->assertEquals($sExpected, $this->service->acgtContent("AAAAAAAAAA"));
    }

    /**
     * Degenerated nucleotides are only listed when the sequence holds at least one, and
     * they follow the Y R W S K M D V H B N order of the minitool
     */
    public function testAcgtContentListsThePresentDegeneratedNucleotides()
    {
        $sExpected = "Nucleotide composition\nA: 1\nC: 1\nG: 1\nT: 1\nY: 1\nR: 1\nN: 2\n\n";

        $this->assertEquals($sExpected, $this->service->acgtContent("ATGCNNRY"));
    }

    public function testAcgtContentOfAnEmptySequence()
    {
        $sExpected = "Nucleotide composition\nA: 0\nC: 0\nG: 0\nT: 0\n\n";

        $this->assertEquals($sExpected, $this->service->acgtContent(""));
    }
}
