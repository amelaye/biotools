<?php
namespace Tests\MinitoolsBundle\Service;

use PHPUnit\Framework\TestCase;
use Amelaye\BioTools\Service\FormulasManager;

class FormulasManagerTest extends TestCase
{
    public function testMWOfDsDNA()
    {
        $sequence = "GGAGTGAGGGGAGCAGTTGGGCCAAGATGGCGGCCGCCGAGGGACCGGTGGGCGACGCGG";
        $iExpected = 39600;

        $service = new FormulasManager();
        $testFunction = $service->mwOfDsDNA($sequence);

        $this->assertEquals($iExpected, $testFunction);
    }

    public function testMWOfSsDNA()
    {
        $sequence = "GGAGTGAGGGGAGCAGTTGGGCCAAGATGGCGGCCGCCGAGGGACCGGTGGGCGACGCGG";
        $iExpected = 19800;

        $service = new FormulasManager();
        $testFunction = $service->mwOfSsDNA($sequence);

        $this->assertEquals($iExpected, $testFunction);
    }

    public function testPmolOfDsDNA()
    {
        $pmol_dsDNA_sequence = "GGAGTGAGGGGAGCAGTTGGGCCAAGATGGCGGCCGCCGAGGGACCGGTGGGCGACGCGG";
        $pmol_dsDNA_no_of_mueg = 23;
        $fExpected = 1161.6161616162;

        $service = new FormulasManager();
        $testFunction = $service->pmolOfDsDNA($pmol_dsDNA_sequence, $pmol_dsDNA_no_of_mueg);

        $this->assertEqualsWithDelta($fExpected, $testFunction, 0.0001);
    }

    public function testPmolOfSsDNA()
    {
        $sPmolDsDNASequence = "GGAGTGAGGGGAGCAGTTGGGCCAAGATGGCGGCCGCCGAGGGACCGGTGGGCGACGCGG";
        $iPmolDsDNANbMueg = 23;
        $fExpected = 1161.6161616162;

        $service = new FormulasManager();
        $testFunction = $service->pmolOfSsDNA($sPmolDsDNASequence, $iPmolDsDNANbMueg);

        $this->assertEqualsWithDelta($fExpected, $testFunction, 0.0001);
    }

    public function testMicroToPmolDsDNA()
    {
        $pmol_dsDNA_sequence = "GGAGTGAGGGGAGCAGTTGGGCCAAGATGGCGGCCGCCGAGGGACCGGTGGGCGACGCGG";
        $no_of_micro_dsDNA = 23;
        $fExpected = 580.75;

        $service = new FormulasManager();
        $testFunction = $service->microToPmolDsDNA($pmol_dsDNA_sequence, $no_of_micro_dsDNA);

        $this->assertEquals($fExpected, $testFunction);
    }

    public function testMicroToPmolSsDNA()
    {
        $pmol_ssDNA_sequence = "GGAGTGAGGGGAGCAGTTGGGCCAAGATGGCGGCCGCCGAGGGACCGGTGGGCGACGCGG";
        $no_of_micro_ssDNA = 23;
        $fExpected = 1161.5;

        $service = new FormulasManager();
        $testFunction = $service->microToPmolSsDNA($pmol_ssDNA_sequence, $no_of_micro_ssDNA);

        $this->assertEquals($fExpected, $testFunction);
    }

    public function testPmolToMicroDsDNA()
    {
        $micro_dsDNA_sequence = "GGAGTGAGGGGAGCAGTTGGGCCAAGATGGCGGCCGCCGAGGGACCGGTGGGCGACGCGG";
        $no_of_pmol_dsDNA = 23;
        $fExpected = 0.9108;

        $service = new FormulasManager();
        $testFunction = $service->pmolToMicroDsDNA($micro_dsDNA_sequence, $no_of_pmol_dsDNA);

        $this->assertEqualsWithDelta($fExpected, $testFunction, 0.0001);
    }

    public function testPmolToMicroSsDNA()
    {
        $micro_ssDNA_sequence = "GGAGTGAGGGGAGCAGTTGGGCCAAGATGGCGGCCGCCGAGGGACCGGTGGGCGACGCGG";
        $no_of_pmol_ssDNA = 23;
        $fExpected = 0.4554;

        $service = new FormulasManager();
        $testFunction = $service->pmolToMicroSsDNA($micro_ssDNA_sequence, $no_of_pmol_ssDNA);

        $this->assertEqualsWithDelta($fExpected, $testFunction, 0.0001);
    }

    public function testMwOfSsRNA()
    {
        $sequence = "GGAGTGAGGGGAGCAGTTGGGCCAAGATGGCGGCCGCCGAGGGACCGGTGGGCGACGCGG";
        $iExpected = 20400;

        $service = new FormulasManager();
        $testFunction = $service->mwOfSsRNA($sequence);

        $this->assertEquals($iExpected, $testFunction);
    }

    public function testPmolOfSsRNA()
    {
        $pmol_ssRNA_sequence = "GGAGTGAGGGGAGCAGTTGGGCCAAGATGGCGGCCGCCGAGGGACCGGTGGGCGACGCGG";
        $pmol_ssRNA_no_of_mueg = 23;
        $iExpected = 1127.3833333333;

        $service = new FormulasManager();
        $testFunction = $service->pmolOfSsRNA($pmol_ssRNA_sequence, $pmol_ssRNA_no_of_mueg);

        $this->assertEqualsWithDelta($iExpected, $testFunction, 0.0001);
    }

    public function testPmolToMicroSsRNA()
    {
        $micro_ssRNA_sequence = "GGAGTGAGGGGAGCAGTTGGGCCAAGATGGCGGCCGCCGAGGGACCGGTGGGCGACGCGG";
        $no_of_pmol_ssRNA = 23;
        $iExpected = 0.4692;

        $service = new FormulasManager();
        $testFunction = $service->pmolToMicroSsRNA($micro_ssRNA_sequence, $no_of_pmol_ssRNA);

        $this->assertEqualsWithDelta($iExpected, $testFunction, 0.0001);
    }

    public function testCentiToFahren()
    {
        // F = 32 + (C x 9/5). biophp.org's original returns 43.1 here, applying the
        // inverse factor 0.555 in the wrong direction. See FormulasManager::centiToFahren().
        $centigrade = 20;
        $fExpected = 68;

        $service = new FormulasManager();
        $testFunction = $service->centiToFahren($centigrade);

        $this->assertEquals($fExpected, $testFunction);
    }

    public function testFarhenToCenti()
    {
        $fahren = 100;
        $fExpected = 37.74;

        $service = new FormulasManager();
        $testFunction = $service->farhenToCenti($fahren);

        $this->assertEquals($fExpected, $testFunction);
    }

    public function testMbarToMmHg()
    {
        $Hg = 10;
        $fExpected = 7.5;

        $service = new FormulasManager();
        $testFunction = $service->mbarToMmHg($Hg);
        $this->assertEquals($fExpected, $testFunction);
    }

    public function testMbarToInchHg()
    {
        // 1 mbar = 0.02953 inch Hg. biophp.org's original returns 0.394 here, using the
        // millimetre-to-inch factor. See FormulasManager::mbarToInchHg().
        $fInchHg = 10;
        $fExpected = 0.2953;

        $service = new FormulasManager();
        $testFunction = $service->mbarToInchHg($fInchHg);
        $this->assertEquals($fExpected, $testFunction);
    }

    public function testMbarToPsi()
    {
        $psi = 10;
        $fExpected = 0.145;

        $service = new FormulasManager();
        $testFunction = $service->mbarToPsi($psi);
        $this->assertEqualsWithDelta($fExpected, $testFunction, 0.0001);
    }

    public function testMbarToAtm()
    {
        $atm = 10;
        $fExpected = 0.00987;

        $service = new FormulasManager();
        $testFunction = $service->mbarToAtm($atm);
        $this->assertEquals($fExpected, $testFunction);
    }

    public function testMbarToKPa()
    {
        $kPa = 10;
        $fExpected = 1;

        $service = new FormulasManager();
        $testFunction = $service->mbarToKPa($kPa);
        $this->assertEquals($fExpected, $testFunction);
    }

    public function testMbarToTorr()
    {
        $torr = 10;
        $fExpected = 7.5;

        $service = new FormulasManager();
        $testFunction = $service->mbarToTorr($torr);
        $this->assertEquals($fExpected, $testFunction);
    }

    public function testPmolToMicrogProtein()
    {
        // biophp.org reference table : 100 pmol of a 10 kDa protein weighs 1 ug
        $fPmol = 100;
        $fMolecularWeight = 10;
        $fExpected = 1;

        $service = new FormulasManager();
        $testFunction = $service->pmolToMicrogProtein($fPmol, $fMolecularWeight);
        $this->assertEquals($fExpected, $testFunction);
    }

    public function testPmolToMicrogProteinWithoutValues()
    {
        $service = new FormulasManager();
        $this->assertEquals(0, $service->pmolToMicrogProtein(0, 10));
        $this->assertEquals(0, $service->pmolToMicrogProtein(100, 0));
    }

    public function testKDaToBasePairs()
    {
        // biophp.org reference table : a 30 kDa protein is encoded by ~810 bp
        $fMolecularWeight = 30;
        $fExpected = 810.81;

        $service = new FormulasManager();
        $testFunction = $service->kDaToBasePairs($fMolecularWeight);
        $this->assertEqualsWithDelta($fExpected, $testFunction, 0.01);
    }

    public function testKDaToBasePairsWithoutValue()
    {
        $service = new FormulasManager();
        $this->assertEquals(0, $service->kDaToBasePairs(0));
    }
}