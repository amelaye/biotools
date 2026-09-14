<?php
/**
 * Integration test for RandomSequencesManager: wires the real NucleotidApi and AminoApi,
 * stubbing only their HTTP transport with amelaye/biophp's own real fixtures - unlike
 * Tests/Service/RandomSequencesManagerTest.php, which mocks both Adapter interfaces directly.
 */
namespace Tests\Integration;

use Amelaye\BioPHP\Api\AminoApi;
use Amelaye\BioPHP\Api\NucleotidApi;
use Amelaye\BioTools\Service\RandomSequencesManager;
use PHPUnit\Framework\TestCase;

class RandomSequencesManagerIntegrationTest extends TestCase
{
    use RealBioPhpApiTrait;

    public function testRealNucleotideDataRandomizesRespectingComposition()
    {
        $nucleoData = $this->loadBioPhpSample('Nucleotids.php', 'aNucleoObjects');
        $aminoData  = $this->loadBioPhpSample('Aminos.php', 'aAminosObjects');

        $oManager = new RandomSequencesManager(
            $this->buildRealListApi(NucleotidApi::class, $nucleoData),
            $this->buildRealListApi(AminoApi::class, $aminoData)
        );

        $sResult = $oManager->randomize(["A" => 3, "T" => 3, "G" => 2, "C" => 2]);

        $this->assertSame(10, strlen($sResult));
        $aChars = str_split($sResult);
        sort($aChars);
        $this->assertSame("AAACCGGTTT", implode('', $aChars));
    }
}
