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

    private function buildManagerWithTheRealCodonTranslator(): DnaToProteinManager
    {
        $aminoData    = $this->loadBioPhpSample('Aminos.php', 'aAminosObjects');
        $nucleoData   = $this->loadBioPhpSample('Nucleotids.php', 'aNucleoObjects');
        $elementsData = $this->loadBioPhpSample('Elements.php', 'aElementsObjects');
        $tripletData  = $this->loadBioPhpSample('Triplets.php', 'aTripletObjects');
        $speciesData  = $this->loadBioPhpSample('TripletsSpecies.php', 'aTripletSpeciesObjects');

        $oClient     = $this->getMockBuilder(\GuzzleHttp\Client::class)->getMock();
        $oSerializer = \JMS\Serializer\SerializerBuilder::create()->build();

        $oAmino = $this->getMockBuilder(AminoApi::class)
            ->setConstructorArgs([$oClient, $oSerializer])->onlyMethods(['getAminos'])->getMock();
        $oAmino->method('getAminos')->willReturn($aminoData);
        $oNucleo = $this->getMockBuilder(\Amelaye\BioPHP\Api\NucleotidApi::class)
            ->setConstructorArgs([$oClient, $oSerializer])->onlyMethods(['getNucleotids'])->getMock();
        $oNucleo->method('getNucleotids')->willReturn($nucleoData);
        $oElements = $this->getMockBuilder(\Amelaye\BioPHP\Api\ElementApi::class)
            ->setConstructorArgs([$oClient, $oSerializer])->onlyMethods(['getElements', 'getElement'])->getMock();
        $oElements->method('getElements')->willReturn($elementsData);
        $oElements->method('getElement')->willReturn($elementsData[5]);

        $oSequenceManager = new \Amelaye\BioPHP\Domain\Sequence\Service\SequenceManager($oAmino, $oNucleo, $oElements);
        $oTranslator = new \Amelaye\BioPHP\Domain\Tools\Service\Codon\AlternateGeneticCodeTranslator(
            new \Amelaye\BioPHP\Domain\Sequence\Builder\SequenceBuilder($oSequenceManager)
        );

        return new DnaToProteinManager(
            $this->buildRealListApi(AminoApi::class, $aminoData),
            $this->buildRealListApi(TripletApi::class, $tripletData),
            $this->buildRealListApi(TripletSpecieApi::class, $speciesData),
            null,
            $oTranslator
        );
    }

    /**
     * AGR (AGA or AGG) is Arg and YTR (CTA or TTA... CTG, TTG) is Leu in the standard code: the triplet groups
     * of the API left them as X. Expected letters worked out by hand from the genetic code.
     */
    public function testAmbiguousCodonsAreTranslatedUnderTheStandardCode()
    {
        $oManager = $this->buildManagerWithTheRealCodonTranslator();

        $this->assertSame("MKRL", $oManager->translateDNAToProtein("ATGAAAAGRYTR", "standard"));
    }

    /**
     * Vertebrate mitochondrial code (NCBI 2): TGA = Trp, ATA = Met, AGA and AGG = stop, hence AGR = stop
     */
    public function testNcbiTablesAreAppliedFromTheLabelOfTheForm()
    {
        $oManager = $this->buildManagerWithTheRealCodonTranslator();

        $this->assertSame("MWM**", $oManager->translateDNAToProtein("ATGTGAATAAGAAGR", "vertebrate_mitochondrial"));
        // euplotid nuclear (NCBI 10): TGA = Cys
        $this->assertSame("C", $oManager->translateDNAToProtein("TGA", "euplotid_nuclear"));
        $this->assertSame("*", $oManager->translateDNAToProtein("TGA", "standard"));
    }

    public function testSymbolsOutsideIupacGiveXAndAnIncompleteLastCodonIsDropped()
    {
        $oManager = $this->buildManagerWithTheRealCodonTranslator();

        $this->assertSame("MXK", $oManager->translateDNAToProtein("atgZZZaaaAC", "standard"));
    }
}
