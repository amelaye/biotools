<?php


namespace Tests\MinitoolsBundle\Service;

use Amelaye\BioPHP\Api\TripletSpecieApi;
use App\Service\ProteinToDnaManager;
use PHPUnit\Framework\TestCase;

class ProteinToDnaManagerTest extends TestCase
{
    protected $apiMock;

    public function setUp()
    {
        require 'samples/TripletsSpecies.php';

        /**
         * Mock API
         */
        $this->tripletSpeciesMock = $this->getMockBuilder(TripletSpecieApi::class)
            ->disableOriginalConstructor()
            ->setMethods(['getTriplets'])
            ->getMock();
        $this->tripletSpeciesMock->method("getTriplets")->will($this->returnValue($aTripletSpeciesObjects));
    }

    public function testTranslateProteinToDNAYeastMitochondrial()
    {
        $sSequence = "FLIMVSPTAYHQNKDECWRGX*";
        $sGeneticCode = "yeast_mitochondrial";
        $sExpected = "TTYTTRATYATRGTNWSNCCNMYNGCNTAYCAYCARAAYAARGAYGARTGYTGRMGNGGNNNNTAR";

        $service = new ProteinToDnaManager($this->tripletSpeciesMock);
        $testFunction = $service->translateProteinToDNA($sSequence, $sGeneticCode);

        $this->assertEquals($sExpected, $testFunction);
    }

    public function testTranslateProteinToDNABlepharismaMacronuclearl()
    {
        $sSequence = "FLIMVSPTAYHQNKDECWRGX*";
        $sGeneticCode = "blepharisma_macronuclear";
        $sExpected = "TTYYTNATHATGGTNWSNCCNACNGCNTAYCAYYARAAYAARGAYGARTGYTGGMGNGGNNNNTRA";

        $service = new ProteinToDnaManager($this->tripletSpeciesMock);
        $testFunction = $service->translateProteinToDNA($sSequence, $sGeneticCode);

        $this->assertEquals($sExpected, $testFunction);
    }
}