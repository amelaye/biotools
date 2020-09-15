<?php

namespace Tests\MinitoolsBundle\Service;

use Amelaye\BioPHP\Api\AminoApi;
use Amelaye\BioPHP\Api\DTO\ElementDTO;
use Amelaye\BioPHP\Api\ElementApi;
use Amelaye\BioPHP\Api\NucleotidApi;
use Amelaye\BioPHP\Api\TmBaseStackingApi;
use Amelaye\BioPHP\Domain\Sequence\Builder\SequenceBuilder;
use Amelaye\BioPHP\Domain\Sequence\Entity\Sequence;
use Amelaye\BioPHP\Domain\Sequence\Service\SequenceManager;
use App\Service\MeltingTemperatureManager;
use PHPUnit\Framework\TestCase;

class MeltingTemperatureManagerTest extends TestCase
{
    protected $enthalpyValues;

    protected $apiMock;

    protected $enthropyValues;

    protected $nucleoMock;

    public function setUp()
    {
        $this->enthalpyValues = [
            "AA" => -7.9,
            "AC" => -8.4,
            "AG" => -7.8,
            "AT" => -7.2,
            "CA" => -8.5,
            "CC" => -8,
            "CG" => -10.6,
            "CT" => -7.8,
            "GA" => -8.2,
            "GC" => -9.8,
            "GG" => -8,
            "GT" => -8.4,
            "TA" => -7.2,
            "TC" => -8.2,
            "TG" => -8.5,
            "TT" => -7.9,
        ];

        $this->enthropyValues = [
            "AA" => -22.2,
            "AC" => -22.4,
            "AG" => -21,
            "AT" => -20.4,
            "CA" => -22.7,
            "CC" => -19.9,
            "CG" => -27.2,
            "CT" => -21,
            "GA" => -22.2,
            "GC" => -24.4,
            "GG" => -19.9,
            "GT" => -22.4,
            "TA" => -21.3,
            "TC" => -22.2,
            "TG" => -22.7,
            "TT" => -22.2
        ];

        $water = new ElementDTO();
        $water->setId(6);
        $water->setName("water");
        $water->setWeight(18.015);


        /**
         * Mock API
         */
        require 'samples/Aminos.php';
        require 'samples/Nucleotids.php';
        require 'samples/Elements.php';

        $this->apiAminoMock = $this->getMockBuilder(AminoApi::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAminos'])
            ->getMock();
        $this->apiAminoMock->method("getAminos")->will($this->returnValue($aAminosObjects));

        $this->apiNucleoMock = $this->getMockBuilder(NucleotidApi::class)
            ->disableOriginalConstructor()
            ->setMethods(['getNucleotids'])
            ->getMock();
        $this->apiNucleoMock->method("getNucleotids")->will($this->returnValue($aNucleoObjects));

        $this->apiElemMock = $this->getMockBuilder(ElementApi::class)
            ->disableOriginalConstructor()
            ->setMethods(['getElements','getElement'])
            ->getMock();
        $this->apiElemMock->method("getElements")->will($this->returnValue($aElementsObjects));
        $this->apiElemMock->method("getElement")->will($this->returnValue($water));

        $this->sequenceManager = new SequenceManager($this->apiAminoMock, $this->apiNucleoMock, $this->apiElemMock);
        $this->sequenceBuilder = new SequenceBuilder($this->sequenceManager);

        require 'samples/TmBaseStacking.php';

        $this->apiTmMock = $this->getMockBuilder(TmBaseStackingApi::class)
            ->disableOriginalConstructor()
            ->setMethods(['getTmBaseStackings'])
            ->getMock();
        $this->apiTmMock->method("getTmBaseStackings")->will($this->returnValue($aTemperatureObjects));
    }

    public function testCalculateCG()
    {
        $primer = "AAAATTTGGGGCCCATGCCC";
        $fExpected = 55.0;

        $oSequence = new Sequence();
        $oSequence->setSequence($primer);
        $this->sequenceBuilder->setSequence($oSequence);

        $service = new MeltingTemperatureManager($this->sequenceBuilder, $this->apiTmMock);
        $testFunction = $service->calculateCG($primer);

        $this->assertEquals($testFunction, $fExpected);
    }

    public function testCalculateCGException()
    {
        $this->expectException(\Exception::class);
        $primer = [];

        $service = new MeltingTemperatureManager($this->sequenceBuilder, $this->apiTmMock);
        $service->calculateCG($primer);
    }

    public function testTmBaseStacking()
    {
        $primer = "AAAATTTGGGGCCCATGCCC";
        $concPrimer = "200";
        $concSalt = "50";
        $concMg = "2";

        $aExpected = [
            "tm" => 68.6,
            "enthalpy" => -152.6,
            "entropy" => -414.45,
        ];

        $service = new MeltingTemperatureManager($this->sequenceBuilder, $this->apiTmMock);
        $testFunction = $service->tmBaseStacking($primer, $concPrimer, $concSalt, $concMg);

        $this->assertEquals($testFunction, $aExpected);
    }

    public function testTmBaseStackingException()
    {
        $this->expectException(\Exception::class);
        $primer = [];
        $concPrimer = "200";
        $concSalt = "50";
        $concMg = "2";

        $service = new MeltingTemperatureManager($this->sequenceBuilder, $this->apiTmMock);
        $service->tmBaseStacking($primer, $concPrimer, $concSalt, $concMg);
    }

    public function testTmMinMoreFourteen()
    {
        $primer = "AAAATTTGGGGCCCATGCCC";
        $fExpected =  53.8;

        $service = new MeltingTemperatureManager($this->sequenceBuilder, $this->apiTmMock);
        $testFunction = $service->tmMin($primer);

        $this->assertEquals($testFunction, $fExpected);
    }

    public function testTmMinLessFourteen()
    {
        $primer = "AAAATTT";
        $fExpected =  14.0;

        $service = new MeltingTemperatureManager($this->sequenceBuilder, $this->apiTmMock);
        $testFunction = $service->tmMin($primer);

        $this->assertEquals($testFunction, $fExpected);
    }

    public function testTmMinException()
    {
        $this->expectException(\Exception::class);
        $primer = [];

        $service = new MeltingTemperatureManager($this->sequenceBuilder, $this->apiTmMock);
        $service->tmMin($primer);
    }

    public function testTmMaxMoreFourteen()
    {
        $primer = "AAAATTTGGGGCCCATGCCC";
        $fExpected =  53.8;

        $service = new MeltingTemperatureManager($this->sequenceBuilder, $this->apiTmMock);
        $testFunction = $service->tmMax($primer);

        $this->assertEquals($testFunction, $fExpected);
    }

    public function testTmMaxLessFourteen()
    {
        $primer = "GAGAGA";
        $fExpected =  18.0;

        $service = new MeltingTemperatureManager($this->sequenceBuilder, $this->apiTmMock);
        $testFunction = $service->tmMax($primer);

        $this->assertEquals($testFunction, $fExpected);
    }

    public function testTmMaxException()
    {
        $this->expectException(\Exception::class);
        $primer = [];
        $service = new MeltingTemperatureManager($this->sequenceBuilder, $this->apiTmMock);
        $service->tmMax($primer);
    }

    public function testMolwtUpperLimit()
    {
        $sSequence = "AAAATTTGGGGCCCATGCCC";
        $sMoltype = "DNA";
        $sLimit = "upperlimit";

        $fExpected = 6182.655;

        $service = new MeltingTemperatureManager($this->sequenceBuilder, $this->apiTmMock);
        $testFunction = $service->molwt($sSequence, $sMoltype, $sLimit);

        $this->assertEquals($testFunction, $fExpected);
    }

    public function testMolwtLowerLimit()
    {
        $sSequence = "AAAATTTGGGGCCCATGCCC";
        $sMoltype = "DNA";
        $sLimit = "lowerlimit";

        $fExpected = 6182.655;

        $service = new MeltingTemperatureManager($this->sequenceBuilder, $this->apiTmMock);
        $testFunction = $service->molwt($sSequence, $sMoltype, $sLimit);

        $this->assertEquals($testFunction, $fExpected);
    }
}