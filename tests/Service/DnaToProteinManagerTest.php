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
use App\Service\DnaToProteinManager;

class DnaToProteinManagerTest extends TestCase
{
    protected $apiMock;

    protected function setUp()
    {
        require 'samples/Aminos.php';

        /**
         * Mock API
         */
        $clientMock = $this->getMockBuilder('GuzzleHttp\Client')->getMock();
        $serializerMock = \JMS\Serializer\SerializerBuilder::create()
            ->build();

        require 'samples/Aminos.php';
        require 'samples/Triplets.php';

        $this->apiAminoMock = $this->getMockBuilder(AminoApi::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAminos'])
            ->getMock();
        $this->apiAminoMock->method("getAminos")->will($this->returnValue($aAminosObjects));

        $this->tripletsMock = $this->getMockBuilder(TripletApi::class)
            ->disableOriginalConstructor()
            ->setMethods(['getTriplets'])
            ->getMock();
        $this->tripletsMock->method("getTriplets")->will($this->returnValue($aTripletObjects));

        $this->tripletsMock = $this->getMockBuilder(TripletSpecieApi::class)
            ->disableOriginalConstructor()
            ->setMethods(['getTriplets'])
            ->getMock();
        $this->tripletsMock->method("getTriplets")->will($this->returnValue($aTripletSpeciesObjects));
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

        $service = new DnaToProteinManager($this->apiAminoMock, $this->tripletsMock);
        $testFunction = $service->customTreatment($iFrames, $sSequence, $sMycode);

        $this->assertEquals($aFrames, $testFunction);
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

        $service = new DnaToProteinManager($this->apiMock);
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
            4 => "PHSPRQPGSTAXXSLATRCALTPLVNPVLPPAAPWPPAAPSLPSSTRFYRRRLPXHPLPPHSPRQPGSTAXXSLATRCPSLPSSTRFYRRRLPXHPLRPH",
            5 => "LTPLVNPVLPPAAPWPPAAPSLPSSTRFYRRRLPXHPLPPHSPRQPGSTAXXSLATRCRLTPLVNPVLPPAAPWPPAAPHSPRQPGSTAXXSLATRCAL",
            6 => "SLPSSTRFYRRRLPXHPLRPHSPRQPGSTAXXSLATRCPLTPLVNPVLPPAAPWPPAAASLPSSTRFYRRRLPXHPLPLTPLVNPVLPPAAPWPPAAPS",
        ];

        $service = new DnaToProteinManager($this->apiMock);
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

        $service = new DnaToProteinManager($this->apiMock);
        $testFunction = $service->definedTreatment($iFrames, $sGeneticCode, $sSequence);

        $aFrames = [
            1 => "GVRGAVGPRWRPPRDRWATRE*GEQLGQDGGRRGTGGRRGSEGSSWAKMAAAEGPVGDGGVRGAVGPRWRPPRDRWATGSEGSSWAKMAAAEGPVGDAGV"
        ];

        $this->assertEquals($aFrames, $testFunction);
    }

    public function testDefinedTreatmentLess3Frames()
    {
        $iFrames = "3";

        $sSequence = "GGAGTGAGGGGAGCAGTTGGGCCAAGATGGCGGCCGCCGAGGGACCGGTGGGCGACGCGGGAGTGAGGGGAGCAGTTGGGCCAAGATGGCGGCC";
        $sSequence .= "GCCGAGGGACCGGTGGGCGACGGGGGAGTGAGGGGAGCAGTTGGGCCAAGATGGCGGCCGCCGAGGGACCGGTGGGCGACGGCGGAGTGAGGGGAGCAGTTGGGCCAA";
        $sSequence .= "GATGGCGGCCGCCGAGGGACCGGTGGGCGACGGGGAGTGAGGGGAGCAGTTGGGCCAAGATGGCGGCCGCCGAGGGACCGGTGGGCGACGCGGGAGTG";

        $sGeneticCode = "yeast_mitochondrial";

        $service = new DnaToProteinManager($this->apiMock);
        $testFunction = $service->definedTreatment($iFrames, $sGeneticCode, $sSequence);

        $aFrames = [
          1 => "GVRGAVGPRWRPPRDRWATREWGEQLGQDGGRRGTGGRRGSEGSSWAKMAAAEGPVGDGGVRGAVGPRWRPPRDRWATGSEGSSWAKMAAAEGPVGDAGV",
          2 => "EWGEQLGQDGGRRGTGGRRGSEGSSWAKMAAAEGPVGDGGVRGAVGPRWRPPRDRWATAEWGEQLGQDGGRRGTGGRRGVRGAVGPRWRPPRDRWATRE",
          3 => "SEGSSWAKMAAAEGPVGDAGVRGAVGPRWRPPRDRWATGEWGEQLGQDGGRRGTGGRRRSEGSSWAKMAAAEGPVGDGEWGEQLGQDGGRRGTGGRRGS"
        ];

        $this->assertEquals($aFrames, $testFunction);
    }

    public function testDefinedTreatmentMore3Frames()
    {
        $iFrames = "6";

        $sSequence = "GGAGTGAGGGGAGCAGTTGGGCCAAGATGGCGGCCGCCGAGGGACCGGTGGGCGACGCGGGAGTGAGGGGAGCAGTTGGGCCAAGATGGCGGCC";
        $sSequence .= "GCCGAGGGACCGGTGGGCGACGGGGGAGTGAGGGGAGCAGTTGGGCCAAGATGGCGGCCGCCGAGGGACCGGTGGGCGACGGCGGAGTGAGGGGAGCAGTTGGGCCAA";
        $sSequence .= "GATGGCGGCCGCCGAGGGACCGGTGGGCGACGGGGAGTGAGGGGAGCAGTTGGGCCAAGATGGCGGCCGCCGAGGGACCGGTGGGCGACGCGGGAGTG";

        $sGeneticCode = "euplotid_nuclear";

        $service = new DnaToProteinManager($this->apiMock);
        $testFunction = $service->definedTreatment($iFrames, $sGeneticCode, $sSequence);

        $aFrames = [
          1 => "GVRGAVGPRWRPPRDRWATRECGEQLGQDGGRRGTGGRRGSEGSSWAKMAAAEGPVGDGGVRGAVGPRWRPPRDRWATGSEGSSWAKMAAAEGPVGDAGV",
          2 => "ECGEQLGQDGGRRGTGGRRGSEGSSWAKMAAAEGPVGDGGVRGAVGPRWRPPRDRWATAECGEQLGQDGGRRGTGGRRGVRGAVGPRWRPPRDRWATRE",
          3 => "SEGSSWAKMAAAEGPVGDAGVRGAVGPRWRPPRDRWATGECGEQLGQDGGRRGTGGRRRSEGSSWAKMAAAEGPVGDGECGEQLGQDGGRRGTGGRRGS",
          4 => "PHSPRQPGSTAGGSLATRCALTPLVNPVLPPAAPWPPAAPSLPSSTRFYRRRLPGHPLPPHSPRQPGSTAGGSLATRCPSLPSSTRFYRRRLPGHPLRPH",
          5 => "LTPLVNPVLPPAAPWPPAAPSLPSSTRFYRRRLPGHPLPPHSPRQPGSTAGGSLATRCRLTPLVNPVLPPAAPWPPAAPHSPRQPGSTAGGSLATRCAL",
          6 => "SLPSSTRFYRRRLPGHPLRPHSPRQPGSTAGGSLATRCPLTPLVNPVLPPAAPWPPAAASLPSSTRFYRRRLPGHPLPLTPLVNPVLPPAAPWPPAAPS",
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
          1 => "_____________________*____x__xx_________________MAAAEGPVXDXGVRGAVGPRWRPPRDRWATGSEGSSWAKMAAAEGPVXDAGV",
          2 => "_*____x__xx_________________MAAAEGPVXDGGVRGAVGPRWRPPRDRWATAE*____x__xx_____________________________",
          3 => "________________x_______________________*____x__xx_________________________x___*____x__xx__________",
        ];

        $service = new DnaToProteinManager($this->apiMock);
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

        $service = new DnaToProteinManager($this->apiMock);
        $service->findORF($aFrames, $iProtsize, $bOnlyCoding, $bTrimmed);
    }

    public function testTranslateDNAToProtein()
    {
        $sSequence = "CCTCACTCCCCTCGTCAACCCGGTTCTACCGCCGGCGGCTCCCTGGCCACCCGCTGCGCCCTCACTCCCCTCGTCAACCCGGTTCTACCGCCGGCGGCTCCCTGGCCA";
        $sSequence .= "CCCGCTGCCCCCTCACTCCCCTCGTCAACCCGGTTCTACCGCCGGCGGCTCCCTGGCCACCCGCTGCCGCCTCACTCCCCTCGTCAACCCGGTTCTACCGCCGGCGGC";
        $sSequence .= "TCCCTGGCCACCCGCTGCCCCTCACTCCCCTCGTCAACCCGGTTCTACCGCCGGCGGCTCCCTGGCCACCCGCTGCGCCCTCAC";

        $sGeneticCode = "euplotid_nuclear";

        $sPeptide = "PHSPRQPGSTAGGSLATRCALTPLVNPVLPPAAPWPPAAPSLPSSTRFYRRRLPGHPLPPHSPRQPGSTAGGSLATRCPSLPSSTRFYRRRLPGHPLRPH";

        $service = new DnaToProteinManager($this->apiMock);
        $testFunction = $service->translateDNAToProtein($sSequence, $sGeneticCode);

        $this->assertEquals($sPeptide, $testFunction);
    }

    public function testTranslateDNAToProteinException()
    {
        $this->expectException(\Exception::class);
        $sSequence = 4;

        $sGeneticCode = "pim_poum";

        $service = new DnaToProteinManager($this->apiMock);
        $service->translateDNAToProtein($sSequence, $sGeneticCode);
    }
}