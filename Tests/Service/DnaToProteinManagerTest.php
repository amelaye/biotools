<?php
/**
 * Created by PhpStorm.
 * User: amelaye
 * Date: 2019-08-04
 * Time: 14:51
 */

namespace Tests\MinitoolsBundle\Service;

use Amelaye\BioPHP\Api\AminoApi;
use Amelaye\BioPHP\Api\TripletApi;
use Amelaye\BioPHP\Api\TripletSpecieApi;
use PHPUnit\Framework\TestCase;
use Amelaye\BioTools\Service\DnaToProteinManager;

class DnaToProteinManagerTest extends TestCase
{

    /**
     * @var mixed
     */
    protected $apiAminoMock;

    /**
     * @var mixed
     */
    protected $tripletSpeciesMock;

    /**
     * @var mixed
     */
    protected $tripletsMock;
    protected $apiMock;

    protected function setUp(): void
    {
        /**
         * Mock API
         */
        require 'samples/Aminos.php';
        require 'samples/Triplets.php';
        require 'samples/TripletsSpecies.php';

        $this->apiAminoMock = $this->getMockBuilder(AminoApi::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAminos'])
            ->getMock();
        $this->apiAminoMock->method("getAminos")->willReturn($aAminosObjects);

        $this->tripletsMock = $this->getMockBuilder(TripletApi::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getTriplets'])
            ->getMock();
        $this->tripletsMock->method("getTriplets")->willReturn($aTripletObjects);

        $this->tripletSpeciesMock = $this->getMockBuilder(TripletSpecieApi::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getTriplets'])
            ->getMock();
        $this->tripletSpeciesMock->method("getTriplets")->willReturn($aTripletSpeciesObjects);
    }

    public function testCustomTreatmentOneFrame()
    {
        $iFrames = "1";

        $sSequence = "GGAGTGAGGGGAGCAGTTGGGCCAAGATGGCGGCCGCCGAGGGACCGGTGGGCGACGCGGGAGTGAGGGGAGCAGTTGGGCCAAGATGGCGGCC";
        $sSequence .= "GCCGAGGGACCGGTGGGCGACGGGGGAGTGAGGGGAGCAGTTGGGCCAAGATGGCGGCCGCCGAGGGACCGGTGGGCGACGGCGGAGTGAGGGGAGCAGTTGGGCCAA";
        $sSequence .= "GATGGCGGCCGCCGAGGGACCGGTGGGCGACGGGGAGTGAGGGGAGCAGTTGGGCCAAGATGGCGGCCGCCGAGGGACCGGTGGGCGACGCGGGAGTG";

        $sMycode = "FFLLSSSSYY**CC*WLLLLPPPPHHQQRRRRIIIMTTTTNNKKSSRRVVVVAAAADDEEGGGG";

        $aFrames = [
            1 => "GVRGAVGPRWRPPRDRWATRE*GEQLXQDXXRRGTGGRRGSEGSSWAKMAAAEGPVXDXGVRGAVGPRWRPPRDRWATGSEGSSWAKMAAAEGPVXDAGV"
        ];

        $service = new DnaToProteinManager($this->apiAminoMock, $this->tripletsMock, $this->tripletSpeciesMock);
        $testFunction = $service->customTreatment($iFrames, $sSequence, $sMycode);

        $this->assertEquals($aFrames, $testFunction);
    }

    public function testCustomTreatmentOneFrameLowercaseInput()
    {
        // A soft-masked/lowercase DNA sequence must translate the same as its uppercase form.
        $iFrames = "1";
        $sSequence = "GGAGTGAGGGGAGCAGTTGGGCCAAGATGGCGGCCGCCGAGGGACCGGTGGGCGACGCGGGAGTGAGGGGAGCAGTTGGGCCAAGATGGCGGCC";
        $sMycode = "FFLLSSSSYY**CC*WLLLLPPPPHHQQRRRRIIIMTTTTNNKKSSRRVVVVAAAADDEEGGGG";

        $service = new DnaToProteinManager($this->apiAminoMock, $this->tripletsMock, $this->tripletSpeciesMock);

        $aUppercaseResult = $service->customTreatment($iFrames, strtoupper($sSequence), $sMycode);
        $aLowercaseResult = $service->customTreatment($iFrames, strtolower($sSequence), $sMycode);

        $this->assertEquals($aUppercaseResult, $aLowercaseResult);
    }

    public function testCustomTreatmentLess3Frames()
    {
        $iFrames = "3";

        $sSequence = "GGAGTGAGGGGAGCAGTTGGGCCAAGATGGCGGCCGCCGAGGGACCGGTGGGCGACGCGGGAGTGAGGGGAGCAGTTGGGCCAAGATGGCGGCC";
        $sSequence .= "GCCGAGGGACCGGTGGGCGACGGGGGAGTGAGGGGAGCAGTTGGGCCAAGATGGCGGCCGCCGAGGGACCGGTGGGCGACGGCGGAGTGAGGGGAGCAGTTGGGCCAA";
        $sSequence .= "GATGGCGGCCGCCGAGGGACCGGTGGGCGACGGGGAGTGAGGGGAGCAGTTGGGCCAAGATGGCGGCCGCCGAGGGACCGGTGGGCGACGCGGGAGTG";

        $sMycode = "FFLLSSSSYY**CC*WLLLLPPPPHHQQRRRRIIIMTTTTNNKKSSRRVVVVAAAADDEEGGGG";

        $aFrames = [
          1 => "GVRGAVGPRWRPPRDRWATRE*GEQLXQDXXRRGTGGRRGSEGSSWAKMAAAEGPVXDXGVRGAVGPRWRPPRDRWATGSEGSSWAKMAAAEGPVXDAGV",
          2 => "E*GEQLXQDXXRRGTGGRRGSEGSSWAKMAAAEGPVXDGGVRGAVGPRWRPPRDRWATAE*GEQLXQDXXRRGTGGRRGVRGAVGPRWRPPRDRWATRE",
          3 => "SEGSSWAKMAAAEGPVXDAGVRGAVGPRWRPPRDRWATGE*GEQLXQDXXRRGTGGRRRSEGSSWAKMAAAEGPVXDGE*GEQLXQDXXRRGTGGRRGS",
        ];

        $service = new DnaToProteinManager($this->apiAminoMock, $this->tripletsMock, $this->tripletSpeciesMock);
        $testFunction = $service->customTreatment($iFrames, $sSequence, $sMycode);

        $this->assertEquals($aFrames, $testFunction);
    }

    public function testCustomTreatmentMore3Frames()
    {
        $iFrames = "6";

        $sSequence = "GGAGTGAGGGGAGCAGTTGGGCCAAGATGGCGGCCGCCGAGGGACCGGTGGGCGACGCGGGAGTGAGGGGAGCAGTTGGGCCAAGATGGCGGCC";
        $sSequence .= "GCCGAGGGACCGGTGGGCGACGGGGGAGTGAGGGGAGCAGTTGGGCCAAGATGGCGGCCGCCGAGGGACCGGTGGGCGACGGCGGAGTGAGGGGAGCAGTTGGGCCAA";
        $sSequence .= "GATGGCGGCCGCCGAGGGACCGGTGGGCGACGGGGAGTGAGGGGAGCAGTTGGGCCAAGATGGCGGCCGCCGAGGGACCGGTGGGCGACGCGGGAGTG";

        $sMycode = "FFLLSSSSYY**CC*WLLLLPPPPHHQQRRRRIIIMTTTTNNKKSSRRVVVVAAAADDEEGGGG";

        $aFrames = [
            1 => "GVRGAVGPRWRPPRDRWATRE*GEQLXQDXXRRGTGGRRGSEGSSWAKMAAAEGPVXDXGVRGAVGPRWRPPRDRWATGSEGSSWAKMAAAEGPVXDAGV",
            2 => "E*GEQLXQDXXRRGTGGRRGSEGSSWAKMAAAEGPVXDGGVRGAVGPRWRPPRDRWATAE*GEQLXQDXXRRGTGGRRGVRGAVGPRWRPPRDRWATRE",
            3 => "SEGSSWAKMAAAEGPVXDAGVRGAVGPRWRPPRDRWATGE*GEQLXQDXXRRGTGGRRRSEGSSWAKMAAAEGPVXDGE*GEQLXQDXXRRGTGGRRGS",
            4 => "HSRVAHRSLXXRHLXPTAPLTPRRPPVPRRPPSWPNCSPHSAVAHRSLXXRHLXPTAPLTPPSPTGPSAAAILAQLLPSLPRRPPVPRRPPSWPNCSPHS",
            5 => "TPASPTGPSAAAILAQLLPSLPVAHRSLXXRHLXPTAPLTPPSPTGPSAAAILAQLLPSLPRRPPVPRRPPSWPNCSPHSRVAHRSLXXRHLXPTAPLT",
            6 => "LPRRPPVPRRPPSWPNCSPHSPSPTGPSAAAILAQLLPSLRRRPPVPRRPPSWPNCSPHSPVAHRSLXXRHLXPTAPLTPASPTGPSAAAILAQLLPSL",
        ];

        $service = new DnaToProteinManager($this->apiAminoMock, $this->tripletsMock, $this->tripletSpeciesMock);
        $testFunction = $service->customTreatment($iFrames, $sSequence, $sMycode);

        $this->assertEquals($aFrames, $testFunction);
    }

    public function testDefinedTreatmentOneFrame()
    {
        $iFrames = "1";

        $sSequence = "GGAGTGAGGGGAGCAGTTGGGCCAAGATGGCGGCCGCCGAGGGACCGGTGGGCGACGCGGGAGTGAGGGGAGCAGTTGGGCCAAGATGGCGGCC";
        $sSequence .= "GCCGAGGGACCGGTGGGCGACGGGGGAGTGAGGGGAGCAGTTGGGCCAAGATGGCGGCCGCCGAGGGACCGGTGGGCGACGGCGGAGTGAGGGGAGCAGTTGGGCCAA";
        $sSequence .= "GATGGCGGCCGCCGAGGGACCGGTGGGCGACGGGGAGTGAGGGGAGCAGTTGGGCCAAGATGGCGGCCGCCGAGGGACCGGTGGGCGACGCGGGAGTG";

        $sGeneticCode = "standard";

        $service = new DnaToProteinManager($this->apiAminoMock, $this->tripletsMock, $this->tripletSpeciesMock);
        $testFunction = $service->definedTreatment($iFrames, $sGeneticCode, $sSequence);

        $aFrames = [
            1 => "GVRGAVGPRWRPPRDRWATRE*GEQLGQDGGRRGTGGRRGSEGSSWAKMAAAEGPVGDGGVRGAVGPRWRPPRDRWATGSEGSSWAKMAAAEGPVGDAGV"
        ];

        $this->assertEquals($aFrames, $testFunction);
    }

    public function testDefinedTreatmentOneFrameLowercaseInput()
    {
        // A soft-masked/lowercase DNA sequence must translate the same as its uppercase form.
        $iFrames = "1";
        $sSequence = "GGAGTGAGGGGAGCAGTTGGGCCAAGATGGCGGCCGCCGAGGGACCGGTGGGCGACGCGGGAGTGAGGGGAGCAGTTGGGCCAAGATGGCGGCC";
        $sGeneticCode = "standard";

        $service = new DnaToProteinManager($this->apiAminoMock, $this->tripletsMock, $this->tripletSpeciesMock);

        $aUppercaseResult = $service->definedTreatment($iFrames, $sGeneticCode, strtoupper($sSequence));
        $aLowercaseResult = $service->definedTreatment($iFrames, $sGeneticCode, strtolower($sSequence));

        $this->assertEquals($aUppercaseResult, $aLowercaseResult);
    }

    public function testDefinedTreatmentLess3Frames()
    {
        $iFrames = "3";

        $sSequence = "GGAGTGAGGGGAGCAGTTGGGCCAAGATGGCGGCCGCCGAGGGACCGGTGGGCGACGCGGGAGTGAGGGGAGCAGTTGGGCCAAGATGGCGGCC";
        $sSequence .= "GCCGAGGGACCGGTGGGCGACGGGGGAGTGAGGGGAGCAGTTGGGCCAAGATGGCGGCCGCCGAGGGACCGGTGGGCGACGGCGGAGTGAGGGGAGCAGTTGGGCCAA";
        $sSequence .= "GATGGCGGCCGCCGAGGGACCGGTGGGCGACGGGGAGTGAGGGGAGCAGTTGGGCCAAGATGGCGGCCGCCGAGGGACCGGTGGGCGACGCGGGAGTG";

        $sGeneticCode = "yeast_mitochondrial";

        $service = new DnaToProteinManager($this->apiAminoMock, $this->tripletsMock, $this->tripletSpeciesMock);
        $testFunction = $service->definedTreatment($iFrames, $sGeneticCode, $sSequence);

        $aFrames = [
          1 => "GVRGAVGPRWRPPRDRWATREWGEQLGQDGGRRGTGGRRGSEGSSWAKMAAAEGPVGDGGVRGAVGPRWRPPRDRWATGSEGSSWAKMAAAEGPVGDAGV",
          2 => "EWGEQLGQDGGRRGTGGRRGSEGSSWAKMAAAEGPVGDGGVRGAVGPRWRPPRDRWATAEWGEQLGQDGGRRGTGGRRGVRGAVGPRWRPPRDRWATRE",
          3 => "SEGSSWAKMAAAEGPVGDAGVRGAVGPRWRPPRDRWATGEWGEQLGQDGGRRGTGGRRRSEGSSWAKMAAAEGPVGDGEWGEQLGQDGGRRGTGGRRGS"
        ];

        $this->assertEquals($aFrames, $testFunction);
    }

    /**
     * The opposite strand is read 5' to 3' : frames 4 to 6 translate the REVERSE complement.
     * ATGAAATAG -> reverse complement CTATTTCAT : CTA TTT CAT = L F H (frame 4),
     * TAT TTC = Y F (frame 5), ATT TCA = I S (frame 6). The complement alone (TACTTTATC)
     * would give Y F I, which no ribosome reads.
     */
    public function testDefinedTreatmentReadsTheOppositeStrandBackwards()
    {
        $service = new DnaToProteinManager($this->apiAminoMock, $this->tripletsMock, $this->tripletSpeciesMock);
        $aFrames = $service->definedTreatment(6, "standard", "ATGAAATAG");

        $this->assertSame("MK*", $aFrames[1]);
        $this->assertSame("LFH", $aFrames[4]);
        $this->assertSame("YF", $aFrames[5]);
        $this->assertSame("IS", $aFrames[6]);
    }

    public function testDefinedTreatmentMore3Frames()
    {
        $iFrames = "6";

        $sSequence = "GGAGTGAGGGGAGCAGTTGGGCCAAGATGGCGGCCGCCGAGGGACCGGTGGGCGACGCGGGAGTGAGGGGAGCAGTTGGGCCAAGATGGCGGCC";
        $sSequence .= "GCCGAGGGACCGGTGGGCGACGGGGGAGTGAGGGGAGCAGTTGGGCCAAGATGGCGGCCGCCGAGGGACCGGTGGGCGACGGCGGAGTGAGGGGAGCAGTTGGGCCAA";
        $sSequence .= "GATGGCGGCCGCCGAGGGACCGGTGGGCGACGGGGAGTGAGGGGAGCAGTTGGGCCAAGATGGCGGCCGCCGAGGGACCGGTGGGCGACGCGGGAGTG";

        $sGeneticCode = "euplotid_nuclear";

        $service = new DnaToProteinManager($this->apiAminoMock, $this->tripletsMock, $this->tripletSpeciesMock);
        $testFunction = $service->definedTreatment($iFrames, $sGeneticCode, $sSequence);

        $aFrames = [
          1 => "GVRGAVGPRWRPPRDRWATRECGEQLGQDGGRRGTGGRRGSEGSSWAKMAAAEGPVGDGGVRGAVGPRWRPPRDRWATGSEGSSWAKMAAAEGPVGDAGV",
          2 => "ECGEQLGQDGGRRGTGGRRGSEGSSWAKMAAAEGPVGDGGVRGAVGPRWRPPRDRWATAECGEQLGQDGGRRGTGGRRGVRGAVGPRWRPPRDRWATRE",
          3 => "SEGSSWAKMAAAEGPVGDAGVRGAVGPRWRPPRDRWATGECGEQLGQDGGRRGTGGRRRSEGSSWAKMAAAEGPVGDGECGEQLGQDGGRRGTGGRRGS",
          4 => "HSRVAHRSLGGRHLGPTAPLTPRRPPVPRRPPSWPNCSPHSAVAHRSLGGRHLGPTAPLTPPSPTGPSAAAILAQLLPSLPRRPPVPRRPPSWPNCSPHS",
          5 => "TPASPTGPSAAAILAQLLPSLPVAHRSLGGRHLGPTAPLTPPSPTGPSAAAILAQLLPSLPRRPPVPRRPPSWPNCSPHSRVAHRSLGGRHLGPTAPLT",
          6 => "LPRRPPVPRRPPSWPNCSPHSPSPTGPSAAAILAQLLPSLRRRPPVPRRPPSWPNCSPHSPVAHRSLGGRHLGPTAPLTPASPTGPSAAAILAQLLPSL",
        ];

        $this->assertEquals($aFrames, $testFunction);
    }

    public function testFindORF()
    {
        $sSequence = "GGAGTGAGGGGAGCAGTTGGGCCAAGATGGCGGCCGCCGAGGGACCGGTGGGCGACGCGGGAGTGAGGGGAGCAGTTGGGCCAAGATGGCGGCC";
        $sSequence .= "GCCGAGGGACCGGTGGGCGACGGGGGAGTGAGGGGAGCAGTTGGGCCAAGATGGCGGCCGCCGAGGGACCGGTGGGCGACGGCGGAGTGAGGGGAGCAGTTGGGCCAA";
        $sSequence .= "GATGGCGGCCGCCGAGGGACCGGTGGGCGACGGGGAGTGAGGGGAGCAGTTGGGCCAAGATGGCGGCCGCCGAGGGACCGGTGGGCGACGCGGGAGTG";

        $aFrames = [
          1 => "GVRGAVGPRWRPPRDRWATRE*GEQLXQDXXRRGTGGRRGSEGSSWAKMAAAEGPVXDXGVRGAVGPRWRPPRDRWATGSEGSSWAKMAAAEGPVXDAGV",
          2 => "E*GEQLXQDXXRRGTGGRRGSEGSSWAKMAAAEGPVXDGGVRGAVGPRWRPPRDRWATAE*GEQLXQDXXRRGTGGRRGVRGAVGPRWRPPRDRWATRE",
          3 => "SEGSSWAKMAAAEGPVXDAGVRGAVGPRWRPPRDRWATGE*GEQLXQDXXRRGTGGRRRSEGSSWAKMAAAEGPVXDGE*GEQLXQDXXRRGTGGRRGS"
        ];

        $iProtsize = "50";
        $bOnlyCoding = true;
        $bTrimmed = true;

        $aExpected = [
          1 => "_____________________*__________________________MAAAEGPVXDXGVRGAVGPRWRPPRDRWATGSEGSSWAKMAAAEGPVXDAGV",
          2 => "_*__________________________MAAAEGPVXDGGVRGAVGPRWRPPRDRWATAE*______________________________________",
          3 => "________________________________________*______________________________________*___________________",
        ];

        $service = new DnaToProteinManager($this->apiAminoMock, $this->tripletsMock, $this->tripletSpeciesMock);
        $testFunction = $service->findORF($aFrames, $iProtsize, $bOnlyCoding, $bTrimmed);

        $this->assertEquals($aExpected, $testFunction);
    }

    public function testFindORFException()
    {
        $this->expectException(\Exception::class);
        $aFrames = 4;

        $iProtsize = 100000;
        $bOnlyCoding = 6;
        $bTrimmed = 8;

        $service = new DnaToProteinManager($this->apiAminoMock, $this->tripletsMock, $this->tripletSpeciesMock);
        $service->findORF($aFrames, $iProtsize, $bOnlyCoding, $bTrimmed);
    }

    public function testTranslateDNAToProtein()
    {
        $sSequence = "CCTCACTCCCCTCGTCAACCCGGTTCTACCGCCGGCGGCTCCCTGGCCACCCGCTGCGCCCTCACTCCCCTCGTCAACCCGGTTCTACCGCCGGCGGCTCCCTGGCCA";
        $sSequence .= "CCCGCTGCCCCCTCACTCCCCTCGTCAACCCGGTTCTACCGCCGGCGGCTCCCTGGCCACCCGCTGCCGCCTCACTCCCCTCGTCAACCCGGTTCTACCGCCGGCGGC";
        $sSequence .= "TCCCTGGCCACCCGCTGCCCCTCACTCCCCTCGTCAACCCGGTTCTACCGCCGGCGGCTCCCTGGCCACCCGCTGCGCCCTCAC";

        $sGeneticCode = "euplotid_nuclear";

        $sPeptide = "PHSPRQPGSTAGGSLATRCALTPLVNPVLPPAAPWPPAAPSLPSSTRFYRRRLPGHPLPPHSPRQPGSTAGGSLATRCPSLPSSTRFYRRRLPGHPLRPH";

        $service = new DnaToProteinManager($this->apiAminoMock, $this->tripletsMock, $this->tripletSpeciesMock);
        $testFunction = $service->translateDNAToProtein($sSequence, $sGeneticCode);

        $this->assertEquals($sPeptide, $testFunction);
    }

    public function testTranslateDNAToProteinLowercaseInput()
    {
        // Soft-masked / lowercase DNA (e.g. genomic sequence, pasted FASTA) must translate
        // identically to the same sequence in uppercase, not pass through untranslated.
        $sSequence = "cctcactccc";
        $sGeneticCode = "euplotid_nuclear";

        $service = new DnaToProteinManager($this->apiAminoMock, $this->tripletsMock, $this->tripletSpeciesMock);

        $sUppercaseResult = $service->translateDNAToProtein(strtoupper($sSequence), $sGeneticCode);
        $sLowercaseResult = $service->translateDNAToProtein($sSequence, $sGeneticCode);

        $this->assertEquals($sUppercaseResult, $sLowercaseResult);
        $this->assertDoesNotMatchRegularExpression('/[acgt]/', $sLowercaseResult);
    }

    public function testTranslateDNAToProteinException()
    {
        $this->expectException(\Exception::class);
        $sSequence = 4;

        $sGeneticCode = "pim_poum";

        $service = new DnaToProteinManager($this->apiAminoMock, $this->tripletsMock, $this->tripletSpeciesMock);
        $service->translateDNAToProtein($sSequence, $sGeneticCode);
    }

    public function testShowAminosArrays()
    {
        $service = new DnaToProteinManager($this->apiAminoMock, $this->tripletsMock, $this->tripletSpeciesMock);

        $aCodes = $aLeft = $aRight = null;
        $service->showAminosArrays($aCodes, $aLeft, $aRight);

        $this->assertCount(26, $aCodes);
        $this->assertCount(13, $aLeft);
        $this->assertCount(13, $aRight);
        $this->assertEquals(['Alanine', 'Aspartate or asparagine', 'Cysteine'], array_slice(array_keys($aLeft), 0, 3));
        $this->assertEquals(['Pyrrolysine', 'Proline', 'Glutamine'], array_slice(array_keys($aRight), 0, 3));
        $this->assertEquals(['1' => 'A', '3' => 'Ala'], $aCodes['Alanine']);
        // left+right must recombine into the full array, in order
        $this->assertEquals($aCodes, $aLeft + $aRight);
    }

    public function testShowTranslationsAligned()
    {
        $sSequence = "GGAGTGAGGGGAGCAGTTGGGCCAAGATGGCGGCCGCCGAGGGACCGGTGGGCGACGC";
        $aFrames = [
            1 => "GVRGAVGPRWRPPRDRWAT",
            2 => "E*GEQLGQDGGRRGTGGRR",
            3 => "SEGSSWAKMAAAEGPVGD",
        ];

        $sExpected = "GGAGTGAGGGGAGCAGTTGGGCCAAGATGGCGGCCGCCGAGGGACCGGTGGGCGACGC  100\n"
            . "G  V  R  G  A  V  G  P  R  W  R  P  P  R  D  R  W  A  T  \n"
            . " E  *  G  E  Q  L  G  Q  D  G  G  R  R  G  T  G  G  R  R  \n"
            . "  S  E  G  S  S  W  A  K  M  A  A  A  E  G  P  V  G  D  \n\n";

        $service = new DnaToProteinManager($this->apiAminoMock, $this->tripletsMock, $this->tripletSpeciesMock);
        $testFunction = $service->showTranslationsAligned($sSequence, $aFrames);

        $this->assertEquals($sExpected, $testFunction);
    }

    public function testShowTranslationsAlignedComplementary()
    {
        $sSequence = "GGAGTGAGGGGAGCAGTTGGGCCAAGATGGCGGCCGCCGAGGGACCGGTGGGCGACGC";

        // the opposite strand is shown read 5' to 3' : the reverse complement, translated frame by frame
        $sExpected = "GCGTCGCCCACCGGTCCCTCGGCGGCCGCCATCTTGGCCCAACTGCTCCCCTCACTCC  100\n"
            . "A  S  P  T  G  P  S  A  A  A  I  L  A  Q  L  L  P  S  L  \n"
            . " R  R  P  P  V  P  R  R  P  P  S  W  P  N  C  S  P  H  S  \n"
            . "  V  A  H  R  S  L  G  G  R  H  L  G  P  T  A  P  L  T  \n\n";

        $service = new DnaToProteinManager($this->apiAminoMock, $this->tripletsMock, $this->tripletSpeciesMock);
        // sRvSequence is only populated as a side effect of definedTreatment()/customTreatment()
        // once 6 frames (both strands) are requested - mirrors the real controller flow
        $aFrames = $service->definedTreatment(6, "standard", $sSequence);

        $testFunction = $service->showTranslationsAlignedComplementary($aFrames);

        $this->assertEquals($sExpected, $testFunction);
    }

    public function testShowTranslationsAlignedComplementaryWithoutSixFrames()
    {
        $service = new DnaToProteinManager($this->apiAminoMock, $this->tripletsMock, $this->tripletSpeciesMock);
        // frame 6 absent (single strand only requested) - nothing to show
        $aFrames = [1 => "GVRGAVGPRWRPPRDRWAT"];

        $testFunction = $service->showTranslationsAlignedComplementary($aFrames);

        $this->assertEquals("", $testFunction);
    }

    public function testFindOrfRegionsDelegatesToOrfFinder()
    {
        $oOrf = new \Amelaye\BioPHP\Domain\Tools\ValueObject\OpenReadingFrame(1, 1, 9, 'MKA', true);
        $oFinder = $this->createMock(\Amelaye\BioPHP\Domain\Tools\Interfaces\OrfFinderInterface::class);
        $oFinder->expects($this->once())
            ->method('findOrfs')
            ->with($this->callback(fn($oDna) => $oDna->getValue() === 'ATGAAAGCCTAA'), 3)
            ->willReturn([$oOrf]);

        $service = new DnaToProteinManager($this->apiAminoMock, $this->tripletsMock, $this->tripletSpeciesMock, $oFinder);

        $this->assertSame([$oOrf], $service->findOrfRegions('atgaaagcctaa', 3));
    }

    public function testFindOrfRegionsRequiresAnOrfFinder()
    {
        $service = new DnaToProteinManager($this->apiAminoMock, $this->tripletsMock, $this->tripletSpeciesMock);

        $this->expectException(\LogicException::class);
        $service->findOrfRegions('ATGAAAGCCTAA');
    }
}
