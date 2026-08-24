<?php


namespace Tests\MinitoolsBundle\Service;

use Amelaye\BioPHP\Api\TripletSpecieApi;
use Amelaye\BioTools\Service\ProteinToDnaManager;
use PHPUnit\Framework\TestCase;

class ProteinToDnaManagerTest extends TestCase
{

    /**
     * @var mixed
     */
    protected $tripletSpeciesMock;
    protected $apiMock;

    public function setUp(): void
    {
        require 'samples/TripletsSpecies.php';

        /**
         * Mock API
         */
        $this->tripletSpeciesMock = $this->getMockBuilder(TripletSpecieApi::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getTriplets'])
            ->getMock();
        $this->tripletSpeciesMock->method("getTriplets")->willReturn($aTripletSpeciesObjects);
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