<?php
/**
 * Integration test for MeltingTemperatureManager: wires the real TmBaseStackingApi,
 * stubbing only its HTTP transport with amelaye/biophp's own real 16-dinucleotide
 * enthalpy/entropy fixture - unlike Tests/Service/MeltingTemperatureManagerTest.php,
 * which mocks TmBaseStackingApiAdapter directly.
 */
namespace Tests\Integration;

use Amelaye\BioPHP\Api\TmBaseStackingApi;
use Amelaye\BioPHP\Domain\Sequence\Entity\Sequence;
use Amelaye\BioPHP\Domain\Sequence\Interfaces\SequenceInterface;
use Amelaye\BioTools\Service\MeltingTemperatureManager;
use PHPUnit\Framework\TestCase;

class MeltingTemperatureManagerIntegrationTest extends TestCase
{
    use RealBioPhpApiTrait;

    public function testRealBaseStackingDataComputesTm()
    {
        $tmApi = $this->buildRealListApi(TmBaseStackingApi::class, $this->loadBioPhpSample('TmBaseStacking.php', 'aTemperatureObjects'));

        // molwt()/the sequence manager is irrelevant to tmBaseStacking(): a stub suffices
        $oSequenceManager = $this->createMock(SequenceInterface::class);

        $oManager = new MeltingTemperatureManager($oSequenceManager, $tmApi);

        $aResult = $oManager->tmBaseStacking("ACGTACGTAC", 200, 50, 1.5);
        $this->assertEquals(['tm' => 34.8, 'enthalpy' => -75.2, 'entropy' => -212.16], $aResult);
    }

    public function testRealBaseStackingDataRefusesDegenerateNucleotides()
    {
        $tmApi = $this->buildRealListApi(TmBaseStackingApi::class, $this->loadBioPhpSample('TmBaseStacking.php', 'aTemperatureObjects'));
        $oSequenceManager = $this->createMock(SequenceInterface::class);

        $oManager = new MeltingTemperatureManager($oSequenceManager, $tmApi);

        $aResult = $oManager->tmBaseStacking("ACGTNY", 200, 50, 1.5);
        $this->assertNull($aResult['tm']);
        $this->assertEquals('Non computed. The oligonucleotide contains degenerated nucleotides.', $aResult['message']);
    }
}
