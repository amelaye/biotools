<?php
/**
 * Integration test for DnaToProteinManager and ProteinToDnaManager: wires the real
 * AminoApi/TripletApi/TripletSpecieApi classes, stubbing only their HTTP transport with
 * amelaye/biophp's own real "standard" genetic code fixture - unlike the Tests/Service/*
 * unit tests, which mock the Adapter interfaces directly.
 */
namespace Tests\Integration;

use Amelaye\BioPHP\Api\AminoApi;
use Amelaye\BioPHP\Api\TripletApi;
use Amelaye\BioPHP\Api\TripletSpecieApi;
use Amelaye\BioTools\Service\DnaToProteinManager;
use Amelaye\BioTools\Service\ProteinToDnaManager;
use PHPUnit\Framework\TestCase;

class GeneticCodeManagersIntegrationTest extends TestCase
{
    use RealBioPhpApiTrait;

    public function testRealStandardGeneticCodeTranslatesDnaToProtein()
    {
        $aminoData   = $this->loadBioPhpSample('Aminos.php', 'aAminosObjects');
        $tripletData = $this->loadBioPhpSample('Triplets.php', 'aTripletObjects');
        $speciesData = $this->loadBioPhpSample('TripletsSpecies.php', 'aTripletSpeciesObjects');

        $oManager = new DnaToProteinManager(
            $this->buildRealListApi(AminoApi::class, $aminoData),
            $this->buildRealListApi(TripletApi::class, $tripletData),
            $this->buildRealListApi(TripletSpecieApi::class, $speciesData)
        );

        // ATG=Met(M), AAA=Lys(K) under the real "standard" genetic code table
        $this->assertEquals("MK", $oManager->translateDNAToProtein("ATGAAA", "standard"));
    }

    public function testRealStandardGeneticCodeTranslatesProteinToDna()
    {
        $speciesData = $this->loadBioPhpSample('TripletsSpecies.php', 'aTripletSpeciesObjects');

        $oManager = new ProteinToDnaManager(
            $this->buildRealListApi(TripletSpecieApi::class, $speciesData)
        );

        // Methionine's only real codon is ATG
        $this->assertEquals("ATG", $oManager->translateProteinToDNA("M", "standard"));
    }
}
