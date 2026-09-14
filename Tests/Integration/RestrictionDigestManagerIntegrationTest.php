<?php
/**
 * Integration test for RestrictionDigestManager: wires the real (non-mocked)
 * VendorLinkApi/TypeIIEndonucleaseApi/TypeIIbEndonucleaseApi/TypeIIsEndonucleaseApi/VendorApi
 * classes, stubbing only their HTTP transport with amelaye/biophp's own production
 * fixtures - unlike Tests/Service/RestrictionDigestManagerTest.php, which mocks the
 * Adapter interfaces directly and never runs the real Api classes' JSON/DTO parsing code.
 */
namespace Tests\Integration;

use Amelaye\BioPHP\Api\TypeIIbEndonucleaseApi;
use Amelaye\BioPHP\Api\TypeIIEndonucleaseApi;
use Amelaye\BioPHP\Api\TypeIIsEndonucleaseApi;
use Amelaye\BioPHP\Api\VendorApi;
use Amelaye\BioPHP\Api\VendorLinkApi;
use Amelaye\BioTools\Service\RestrictionDigestManager;
use PHPUnit\Framework\TestCase;

class RestrictionDigestManagerIntegrationTest extends TestCase
{
    use RealBioPhpApiTrait;

    public function testRealVendorAndEndonucleaseDataDigestsEcoRISite()
    {
        $vendorApi     = $this->buildRealListApi(VendorApi::class, $this->loadBioPhpSample('Vendors.php', 'vendorsObjects'));
        $vendorLinkApi = $this->buildRealListApi(VendorLinkApi::class, $this->loadBioPhpSample('VendorLinks.php', 'vendorLinksObjects'));
        $type2Api      = $this->buildRealListApi(TypeIIEndonucleaseApi::class, $this->loadBioPhpSample('TypeIIEndonucleases.php', 'aTypeIIEndonucleases'));
        $type2bApi     = $this->buildRealListApi(TypeIIbEndonucleaseApi::class, $this->loadBioPhpSample('Type2bEndonucleases.php', 'aTypeIIbEndonucleases'));
        $type2sApi     = $this->buildRealListApi(TypeIIsEndonucleaseApi::class, $this->loadBioPhpSample('Type2sEndonucleases.php', 'aTypeIIsEndonucleases'));

        $oManager = new RestrictionDigestManager($vendorLinkApi, $type2Api, $type2bApi, $type2sApi, $vendorApi);

        $aEnzymes = $oManager->getNucleolasesInfos(false, false, false, "EcoRI");
        // real production dataset: hundreds of Type II endonucleases
        $this->assertGreaterThan(300, count($aEnzymes));
        $this->assertArrayHasKey("EcoRI", $aEnzymes);
        // EcoRI's real recognition data: G'AATT_C, computing pattern (GAATTC), 6bp, cut at +1/+4
        $this->assertEquals(["EcoRI", "G'AATT_C", "(GAATTC)", 6, 1, 4, 6], $aEnzymes["EcoRI"]);

        // EcoRI cuts between the G and the AATTC (G^AATTC): in "AAAAAGAATTCAAAAA" the
        // site starts at index 5, so the real cut position is 5 + 1 = 6
        $aDigestion = $oManager->restrictionDigest(["EcoRI" => $aEnzymes["EcoRI"]], "AAAAAGAATTCAAAAA");
        $this->assertEquals(["EcoRI" => ["cuts" => [6 => ""]]], $aDigestion);
    }
}
