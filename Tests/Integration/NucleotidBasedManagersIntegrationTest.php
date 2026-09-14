<?php
/**
 * Integration test for the three services whose only BioPHP dependency is NucleotidApi -
 * ChaosGameRepresentationManager, PcrAmplificationManager and DistanceAmongSequencesManager
 * (the latter also wires a real, non-mocked OligosManager domain service, itself built on
 * a real NucleotidApi). The HTTP transport is stubbed with amelaye/biophp's own real DNA/RNA
 * nucleotide fixture - unlike the corresponding Tests/Service/* unit tests, which mock
 * NucleotidApiAdapter directly.
 */
namespace Tests\Integration;

use Amelaye\BioPHP\Api\NucleotidApi;
use Amelaye\BioPHP\Domain\Tools\Service\OligosManager;
use Amelaye\BioTools\Service\ChaosGameRepresentationManager;
use Amelaye\BioTools\Service\DistanceAmongSequencesManager;
use Amelaye\BioTools\Service\PcrAmplificationManager;
use PHPUnit\Framework\TestCase;

class NucleotidBasedManagersIntegrationTest extends TestCase
{
    use RealBioPhpApiTrait;

    public function testRealDnaComplementsCountNucleotidesInChaosGameRepresentation()
    {
        $nucleoData = $this->loadBioPhpSample('Nucleotids.php', 'aNucleoObjects');

        $oManager = new ChaosGameRepresentationManager(
            ['path_graphs' => sys_get_temp_dir()],
            $this->buildRealListApi(NucleotidApi::class, $nucleoData)
        );

        $aCounts = $oManager->numberNucleos(['sequence' => 'ATGCATGC']);
        $this->assertEquals(['T' => 2, 'A' => 2, 'C' => 2, 'G' => 2], $aCounts);
    }

    public function testRealDnaComplementsBuildTheReverseComplementEndPattern()
    {
        $nucleoData = $this->loadBioPhpSample('Nucleotids.php', 'aNucleoObjects');

        $oManager = new PcrAmplificationManager(
            $this->buildRealListApi(NucleotidApi::class, $nucleoData)
        );

        // "ACGT" is its own reverse complement
        $this->assertEquals("ACGT", $oManager->createEndPattern("ACGT"));
    }

    public function testRealDnaComplementsAndOligosManagerComputeDinucleotideFrequencies()
    {
        $nucleoData = $this->loadBioPhpSample('Nucleotids.php', 'aNucleoObjects');

        $oOligosManager = new OligosManager(
            $this->buildRealListApi(NucleotidApi::class, $nucleoData)
        );

        $oManager = new DistanceAmongSequencesManager(
            $oOligosManager,
            $this->buildRealListApi(NucleotidApi::class, $nucleoData)
        );

        $aResult = $oManager->computeOligonucleotidsFrequenciesEuclidean(["s1" => "ATAT"], 2);

        $this->assertEqualsWithDelta(10.666666666667, $aResult["s1"]["AT"], 1e-9);
        $this->assertEqualsWithDelta(5.3333333333333, $aResult["s1"]["TA"], 1e-9);
        $this->assertEquals(0, $aResult["s1"]["GC"]);
    }
}
