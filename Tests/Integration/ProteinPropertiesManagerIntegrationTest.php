<?php
/**
 * Integration test for ProteinPropertiesManager: wires the real AminoApi, stubbing only
 * its HTTP transport with amelaye/biophp's own real amino acid fixture - unlike
 * Tests/Service/ProteinPropertiesManagerTest.php, which mocks AminoApiAdapter directly.
 */
namespace Tests\Integration;

use Amelaye\BioPHP\Api\AminoApi;
use Amelaye\BioPHP\Api\Interfaces\PKApiAdapter;
use Amelaye\BioTools\Service\ProteinPropertiesManager;
use PHPUnit\Framework\TestCase;

class ProteinPropertiesManagerIntegrationTest extends TestCase
{
    use RealBioPhpApiTrait;

    public function testRealAminoDataConvertsIntoThreeLettersCode()
    {
        $aminoData = $this->loadBioPhpSample('Aminos.php', 'aAminosObjects');

        // PK values are irrelevant to convertInto3lettersCode(): a stub suffices
        $oPkApi = $this->createMock(PKApiAdapter::class);

        $oManager = new ProteinPropertiesManager(
            $this->buildRealListApi(AminoApi::class, $aminoData),
            $oPkApi
        );

        $this->assertEquals("Met", $oManager->convertInto3lettersCode("M"));
    }
}
