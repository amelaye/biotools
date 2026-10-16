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
use Amelaye\BioPHP\Domain\Tools\Interfaces\MeltingTemperatureInterface;
use Amelaye\BioPHP\Domain\Tools\Result\NearestNeighborTmResult;
use Amelaye\BioTools\Service\MeltingTemperatureManager;
use PHPUnit\Framework\TestCase;

class MeltingTemperatureManagerTest extends TestCase
{

    /**
     * @var mixed
     */
    protected $apiAminoMock;

    /**
     * @var mixed
     */
    protected $apiElemMock;

    /**
     * @var mixed
     */
    protected $apiNucleoMock;

    /**
     * @var mixed
     */
    protected $apiTmMock;

    /**
     * @var mixed
     */
    protected $sequenceBuilder;

    /**
     * @var mixed
     */
    protected $sequenceManager;
    protected $enthalpyValues;

    protected $apiMock;

    protected $enthropyValues;

    protected $nucleoMock;

    public function setUp(): void
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
            ->onlyMethods(['getAminos'])
            ->getMock();
        $this->apiAminoMock->method("getAminos")->willReturn($aAminosObjects);

        $this->apiNucleoMock = $this->getMockBuilder(NucleotidApi::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getNucleotids'])
            ->getMock();
        $this->apiNucleoMock->method("getNucleotids")->willReturn($aNucleoObjects);

        $this->apiElemMock = $this->getMockBuilder(ElementApi::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getElements','getElement'])
            ->getMock();
        $this->apiElemMock->method("getElements")->willReturn($aElementsObjects);
        $this->apiElemMock->method("getElement")->willReturn($water);

        $this->sequenceManager = new SequenceManager($this->apiAminoMock, $this->apiNucleoMock, $this->apiElemMock);
        $this->sequenceBuilder = new SequenceBuilder($this->sequenceManager);

        require 'samples/TmBaseStacking.php';

        $this->apiTmMock = $this->getMockBuilder(TmBaseStackingApi::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getTmBaseStackings'])
            ->getMock();
        $this->apiTmMock->method("getTmBaseStackings")->willReturn($aTemperatureObjects);
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

    /**
     * An empty primer used to divide by its own length, which PHP 8 turns into a fatal
     * DivisionByZeroError. It is reachable: legacy only enforces the 6-50 bp range on a
     * non-empty primer ("if ($primer!="" and (strlen($primer)<6 ...))"), and the
     * MeltingTemperature constraint reproduces that rule, so "" passes validation.
     * No base means no C+G, so 0.
     */
    public function testCalculateCGOfAnEmptyPrimer()
    {
        $service = new MeltingTemperatureManager($this->sequenceBuilder, $this->apiTmMock);

        $this->assertEquals(0, $service->calculateCG(""));
    }

    /**
     * BioPHP 2.0 corrects the legacy salt term : Mg2+ counts as 120 x sqrt([Mg2+]) mM of sodium
     * (von Ahsen 2001) and the strand concentration is divided by 4 (SantaLucia 1998). For Mg = 2 mM
     * the entropy moves from -414.45 (legacy) to -417.3, hand-checked : 0.368 x 19 x ln(0.2197)
     * replaces 0.368 x 19 x ln(0.33), a difference of -2.84 e.u.
     */
    public function testTmBaseStacking()
    {
        $primer = "AAAATTTGGGGCCCATGCCC";
        $concPrimer = "200";
        $concSalt = "50";
        $concMg = "2";

        $aExpected = [
            "tm" => 65.4,
            "enthalpy" => -152.6,
            "entropy" => -417.3,
        ];

        $service = new MeltingTemperatureManager($this->sequenceBuilder, $this->apiTmMock);
        $testFunction = $service->tmBaseStacking($primer, $concPrimer, $concSalt, $concMg);

        $this->assertEquals($testFunction, $aExpected);
    }

    /**
     * legacy: tm_Base_Stacking() returns the sentinel message "Non computed. The
     * oligonucleotide contains degenerated nucleotides." instead of a Tm value when the
     * primer holds a degenerate nucleotide, since the nearest neighbor thermodynamic
     * table only has values for plain A/C/G/T dinucleotides.
     */
    public function testTmBaseStackingWithDegeneratedNucleotides()
    {
        $primer = "AAAATTTGGGGCCCATGCCN";
        $concPrimer = "200";
        $concSalt = "50";
        $concMg = "2";

        $aExpected = [
            "tm" => null,
            "enthalpy" => null,
            "entropy" => null,
            "message" => "Non computed. The oligonucleotide contains degenerated nucleotides.",
        ];

        $service = new MeltingTemperatureManager($this->sequenceBuilder, $this->apiTmMock);
        $testFunction = $service->tmBaseStacking($primer, $concPrimer, $concSalt, $concMg);

        $this->assertEquals($aExpected, $testFunction);
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

        $this->assertEqualsWithDelta($testFunction, $fExpected, 0.0001);
    }

    public function testMolwtLowerLimit()
    {
        $sSequence = "AAAATTTGGGGCCCATGCCC";
        $sMoltype = "DNA";
        $sLimit = "lowerlimit";

        $fExpected = 6182.655;

        $service = new MeltingTemperatureManager($this->sequenceBuilder, $this->apiTmMock);
        $testFunction = $service->molwt($sSequence, $sMoltype, $sLimit);

        $this->assertEqualsWithDelta($testFunction, $fExpected, 0.0001);
    }

    public function testCalculateMWT()
    {
        $primer = "AAAATTTGGGGCCCATGCCC";

        $service = new MeltingTemperatureManager($this->sequenceBuilder, $this->apiTmMock);

        $upperMwt = $lowerMwt = null;
        $service->calculateMWT($upperMwt, $lowerMwt, $primer);

        $this->assertEqualsWithDelta(6182.655, $upperMwt, 0.0001);
        $this->assertEqualsWithDelta(6182.655, $lowerMwt, 0.0001);
    }

    public function testBasicCalculationsEnabled()
    {
        $primer = "AAAATTTGGGGCCCATGCCC";

        $service = new MeltingTemperatureManager($this->sequenceBuilder, $this->apiTmMock);

        $countATGC = $tmMin = $tmMax = null;
        $service->basicCalculations(true, $primer, $countATGC, $tmMin, $tmMax);

        $this->assertEquals(20, $countATGC);
        $this->assertEquals(53.8, $tmMin);
        $this->assertEquals(53.8, $tmMax);
    }

    public function testBasicCalculationsDisabled()
    {
        $primer = "AAAATTTGGGGCCCATGCCC";

        $service = new MeltingTemperatureManager($this->sequenceBuilder, $this->apiTmMock);

        $countATGC = $tmMin = $tmMax = null;
        $service->basicCalculations(false, $primer, $countATGC, $tmMin, $tmMax);

        $this->assertNull($countATGC);
        $this->assertNull($tmMin);
        $this->assertNull($tmMax);
    }

    public function testNeighborCalculationsEnabled()
    {
        $primer = "AAAATTTGGGGCCCATGCCC";

        $aExpected = [
            "tm" => 65.4,
            "enthalpy" => -152.6,
            "entropy" => -417.3,
        ];

        $service = new MeltingTemperatureManager($this->sequenceBuilder, $this->apiTmMock);

        $aTmBaseStacking = null;
        $service->neighborCalculations(true, $aTmBaseStacking, $primer, "200", "50", "2");

        $this->assertEquals($aExpected, $aTmBaseStacking);
    }

    public function testNeighborCalculationsDisabled()
    {
        $primer = "AAAATTTGGGGCCCATGCCC";

        $service = new MeltingTemperatureManager($this->sequenceBuilder, $this->apiTmMock);

        $aTmBaseStacking = null;
        $service->neighborCalculations(false, $aTmBaseStacking, $primer, "200", "50", "2");

        $this->assertNull($aTmBaseStacking);
    }

    /**
     * Tm and GC% maths are delegated to the injected BioPHP calculator
     */
    public function testDelegatesToInjectedMeltingTemperatureCalculator()
    {
        $oCalculator = $this->createMock(MeltingTemperatureInterface::class);
        $oCalculator->method('calculateGcPercent')->with('ACGT')->willReturn(50.0);
        $oCalculator->method('calculateMinimumTm')->with('ACGT')->willReturn(10.0);
        $oCalculator->method('calculateMaximumTm')->with('ACGT')->willReturn(12.0);
        $oCalculator->method('calculateNearestNeighborTm')
            ->with('ACGT', 200.0, 50.0, 1.5)
            ->willReturn(new NearestNeighborTmResult(30.1, -10.5, -20.25));

        $oManager = new MeltingTemperatureManager(
            $this->createMock(\Amelaye\BioPHP\Domain\Sequence\Interfaces\SequenceInterface::class),
            $this->createMock(\Amelaye\BioPHP\Api\Interfaces\TmBaseStackingApiAdapter::class),
            $oCalculator
        );

        $this->assertSame(50.0, $oManager->calculateCG('ACGT'));
        $this->assertSame(10.0, $oManager->tmMin('ACGT'));
        $this->assertSame(12.0, $oManager->tmMax('ACGT'));
        $this->assertSame(
            ['tm' => 30.1, 'enthalpy' => -10.5, 'entropy' => -20.25],
            $oManager->tmBaseStacking('ACGT', 200, 50, 1.5)
        );
    }
}
