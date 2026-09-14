<?php
/**
 * Integration test for ReduceProteinAlphabetManager: wires the real ProteinReductionApi,
 * stubbing only its HTTP transport with amelaye/biophp's own real reduction-alphabet
 * fixture - unlike Tests/Service/ReduceProteinAlphabetManagerTest.php, which mocks
 * ProteinReductionApiAdapter directly.
 */
namespace Tests\Integration;

use Amelaye\BioPHP\Api\ProteinReductionApi;
use Amelaye\BioTools\Service\ReduceProteinAlphabetManager;
use PHPUnit\Framework\TestCase;

class ReduceProteinAlphabetManagerIntegrationTest extends TestCase
{
    use RealBioPhpApiTrait;

    public function testRealReductionDataReducesToLi3Alphabet()
    {
        $reductionData = $this->loadBioPhpSample('ProteinReductions.php', 'aReductions');

        $oManager = new ReduceProteinAlphabetManager(
            [],
            $this->buildRealListApi(ProteinReductionApi::class, $reductionData)
        );

        $this->assertEquals(
            "SEEEIEESEIIEIISSSIII",
            $oManager->reduceAlphabet("ARNDCEQGHILKMFPSTWYV", "Li3")
        );
    }
}
