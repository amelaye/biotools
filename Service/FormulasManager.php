<?php
/**
 * Formulas Functions
 * Inspired by BioPHP's project biophp.org
 * Created 3 march  2019
 * Last modified 24 august 2026
 * RIP Pasha, gone 27 february 2019 =^._.^= ∫
 */
namespace Amelaye\BioTools\Service;

/**
 * Class FormulasManager
 * @package BioTools\Service
 * @author Amélie DUVERNET aka Amelaye <amelieonline@gmail.com>
 */
class FormulasManager
{
    /**
     * Calculation of molecular weight in Dalton
     * MW of dsDNA = [number of basepairs] x [660 Da]
     * @param       string          $sSequence
     * @return      float|int
     * @throws      \Exception
     */
    public function mwOfDsDNA($sSequence)
    {
        try {
            $iNbBasePair = strlen($sSequence);
            return ($iNbBasePair * 660);
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Calculation of molecular weight in Dalton
     * MW of ssDNA = [number of bases] x [330 Da]
     * @param       string          $sSequence
     * @return      float|int
     * @throws      \Exception
     */
    public function mwOfSsDNA($sSequence)
    {
        try {
            $iNbasePair = strlen($sSequence);
            return ($iNbasePair * 330);
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Calculation of pmol of 5'(or3')ends of DNA
     * Pmol of ends of a dsDNA molecule = 2 x 106 x µg (of dsDNA)/Nbp x 660 Da
     * @param       string      $sPmolDsDNASequence
     * @param       int         $iPmolDsDNANbMueg
     * @return      float|int
     * @throws      \Exception
     */
    public function pmolOfDsDNA($sPmolDsDNASequence, $iPmolDsDNANbMueg)
    {
        try {
            $iNbBasePair = strlen($sPmolDsDNASequence);
            if (!$iNbBasePair || !$iPmolDsDNANbMueg) {
                return 0;
            } else {
                $iResult = (2 * pow(10,6)) * ($iPmolDsDNANbMueg) / (($iNbBasePair) * 660);
                return $iResult;
            }
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Calculation of pmol of 5'(or3')ends of DNA
     * pmol of ends of a ssDNA molecule = 1 x 106 x µg (of dsDNA)/Nbp x 330 Da
     * @param       string      $sPmolDsDNASequence
     * @param       int         $iPmolDsDNANbMueg
     * @return      float|int
     * @throws      \Exception
     */
    public function pmolOfSsDNA($sPmolDsDNASequence, $iPmolDsDNANbMueg)
    {
        try {
            $iNbBasePair = strlen($sPmolDsDNASequence);
            if (!$iNbBasePair || !$iPmolDsDNANbMueg) {
                return 0;
            } else {
                $iResult = 1 * pow(10,6) * $iPmolDsDNANbMueg / (($iNbBasePair) * 330);
                return $iResult;
            }
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Conversion of µg to pmol
     * pmol of dsDNA = µg (of dsDNA) x 1515/Nbp
     * @param       string      $sPmolDsDNASequence
     * @param       int         $iNbOfMicroDsDNA
     * @return      float|int
     * @throws      \Exception
     */
    public function microToPmolDsDNA($sPmolDsDNASequence, $iNbOfMicroDsDNA)
    {
        try {
            $iNbBasePair = strlen($sPmolDsDNASequence);
            if (!$iNbBasePair || !$iNbOfMicroDsDNA) {
                return 0;
            } else {
                return (($iNbOfMicroDsDNA * 1515) / $iNbBasePair);
            }
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Conversion of µg to pmol
     * pmol of ssDNA = µg (of ssDNA) x 3030/Nbp
     * @param       string      $sPmolSsDNASequence
     * @param       int         $iNbOfMicroSsDNA
     * @return      float|int
     * @throws      \Exception
     */
    public function microToPmolSsDNA($sPmolSsDNASequence, $iNbOfMicroSsDNA)
    {
        try {
            $iNbBase_pair = strlen($sPmolSsDNASequence);
            if (!$iNbBase_pair || !$iNbOfMicroSsDNA) {
                return 0;
            } else {
                return (($iNbOfMicroSsDNA * 3030) / $iNbBase_pair);
            }
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Conversion of Pmol to µg
     * µg of dsDNA = pmol (of dsDNA) x Nbp x 6.6 x 10^-4
     * @param       string      $sMicroDsDNASequence
     * @param       int         $iNbOfPmolDsDNA
     * @return      float|int
     * @throws      \Exception
     */
    public function pmolToMicroDsDNA($sMicroDsDNASequence, $iNbOfPmolDsDNA)
    {
        try {
            $iNbBase_pair = strlen($sMicroDsDNASequence);
            if (!$iNbBase_pair || !$iNbOfPmolDsDNA) {
                return 0;
            } else {
                return ($iNbOfPmolDsDNA * $iNbBase_pair * (6.6 * pow(10, (-4))));
            }
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Conversion of Pmol to µg
     * µg of ssDNA = pmol (of dsDNA) x Nbp x 3.3 x 10^-4
     * @param       string      $sMicrossDNASequence
     * @param       int         $iNbOfPmolSsDNA
     * @return      float|int
     * @throws      \Exception
     */
    public function pmolToMicroSsDNA($sMicrossDNASequence, $iNbOfPmolSsDNA)
    {
        try {
            $nbBasePair = strlen($sMicrossDNASequence);
            if (!$nbBasePair || !$iNbOfPmolSsDNA) {
                return 0;
            } else {
                return ($iNbOfPmolSsDNA * $nbBasePair * (3.3 * pow(10, (-4))));
            }
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Molecular weight of RNA
     * MW of ssDNA = [number of basepairs] x [340 Da]
     * @param       string          $sSequence
     * @return      float|int
     * @throws      \Exception
     */
    public function mwOfSsRNA($sSequence)
    {
        try {
            $iNbBasePair = strlen($sSequence);
            $iResult = $iNbBasePair * 340;
            return $iResult;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Calculation of pmol of 5'(or3')ends of RNA
     * pmol of ends of a ssRNA molecule =µg (of ssRNA)*2941/Nbp
     * @param       string      $sPmolSsRNASequence
     * @param       int         $iPmolSsRNANoOfMueg
     * @return      float|int
     * @throws      \Exception
     */
    public function pmolOfSsRNA($sPmolSsRNASequence, $iPmolSsRNANoOfMueg)
    {
        try {
            $iNbBasePair = strlen($sPmolSsRNASequence);
            if(!$iNbBasePair || !$iPmolSsRNANoOfMueg) {
                return 0;
            } else {
                return (($iPmolSsRNANoOfMueg * 2941) / ($iNbBasePair));
            }
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Conversion of Pmol to µg
     * µg of ssDNA = pmol (of ssRNA) x Nbp x 3.4 x 10^-4
     * @param       string      $sMicroSsRNASequence
     * @param       int         $iNoOfPmolSsRNA
     * @return      float|int
     * @throws      \Exception
     */
    public function pmolToMicroSsRNA($sMicroSsRNASequence, $iNoOfPmolSsRNA)
    {
        try {
            $iNoBasePair = strlen($sMicroSsRNASequence);
            if(!$iNoBasePair || !$iNoOfPmolSsRNA) {
                return 0;
            } else {
                return ($iNoOfPmolSsRNA * $iNoBasePair * (3.4 * pow(10,(-4))));
            }
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Conversions Between Centigrade and Fahrenheit
     * F = 32 + (C x 9/5)
     * Note: biophp.org's original applies "32 + (C x 0.555)". 0.555 (~5/9) is the
     * Fahrenheit-to-Centigrade factor, used there in the wrong direction: it returns
     * 87.5 degF for 100 degC instead of 212 degF. The correct factor is 9/5 = 1.8.
     * @param       float       $fCentigrade
     * @return      float
     * @throws      \Exception
     */
    public function centiToFahren($fCentigrade)
    {
        try {
            $fResult = 32 + ($fCentigrade * 1.8);
            return $fResult;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Conversions Between Fahrenheit and centigrade
     * @param       float       $fFahren
     * @return      float|int
     * @throws      \Exception
     */
    public function farhenToCenti($fFahren)
    {
        try {
            $fResult = 0.555 * ($fFahren - 32);
            return $fResult;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * From millibars to millimeters of mercury (mm Hg)
     * From millibars (mbar) to Millimeters of mercury (mm Hg) = mbar x 0.750000
     * @param   float       $fHg
     * @return  float|int
     * @throws  \Exception
     */
    public function mbarToMmHg($fHg)
    {
        try {
            $fResult = 0.750000 * $fHg;
            return $fResult;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * From millibars to inches of mercury (inch Hg)
     * From millibars (mbar) to Inches of mercury (inch Hg) = mbar x 0.029530
     * Note: biophp.org's original applies 0.039400, which is the millimetre-to-inch
     * factor (1 mm = 0.03937 in) rather than the millibar-to-inHg one, overstating
     * the result by ~33%. 1 mbar = 0.750062 mmHg x 0.03937 in/mm = 0.02953 inHg.
     * @param       float       $fInchHg
     * @return      float|int
     * @throws      \Exception
     */
    public function mbarToInchHg($fInchHg)
    {
        try {
            $fResult = 0.029530 * $fInchHg;
            return $fResult;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * From millibars (mbar) to pounds per square inch (psi)
     * From millibars (mbar) to Pounds per square inch (psi) = mbar x 0.014500
     * @param       float       $fPsi
     * @return      float|int
     * @throws      \Exception
     */
    public function mbarToPsi($fPsi)
    {
        try {
            $fResults = 0.014500 * $fPsi;
            return $fResults;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * From millibars (mbar) to Atmospheres (atm) = mbar x 0.000987
     * @param       float       $fAtm
     * @return      float|int
     * @throws      \Exception
     */
    public function mbarToAtm($fAtm)
    {
        try {
            $fResult = 0.000987 * $fAtm;
            return $fResult;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * From millibars (mbar) to kilopascals (kPa) = mbar x 0.100000
     * @param       float       $fKPa
     * @return      float|int
     * @throws      \Exception
     */
    public function mbarToKPa($fKPa)
    {
        try {
            $fResult = 0.100000 * $fKPa;
            return $fResult;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * From millibars (mbar) to Torrs (Torr) = mbar x 0.750000
     * @param       float       $fTorr
     * @return      float|int
     * @throws      \Exception
     */
    public function mbarToTorr($fTorr)
    {
        try {
            $fResult = 0.750000 * $fTorr;
            return $fResult;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Molar conversions for proteins
     * ug of protein = pmol (of protein) x MW (in kDa) / 1000
     * ie. 100 pmol of a 10 kDa protein weighs 1 ug
     * @param       float       $fPmolOfProtein
     * @param       float       $fMolecularWeight   Molecular weight in kDa
     * @return      float|int
     * @throws      \Exception
     */
    public function pmolToMicrogProtein($fPmolOfProtein, $fMolecularWeight)
    {
        try {
            if (!$fPmolOfProtein || !$fMolecularWeight) {
                return 0;
            }
            return ($fPmolOfProtein * $fMolecularWeight) / 1000;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Protein/DNA conversions
     * 1 kb of DNA encodes 333 amino acids = 3.7 x 10^4 Da, so the coding length
     * in base pairs = MW (in kDa) x 1000 / 37
     * ie. a 10 kDa protein is encoded by ~270 bp
     * @param       float       $fMolecularWeight   Molecular weight in kDa
     * @return      float|int
     * @throws      \Exception
     */
    public function kDaToBasePairs($fMolecularWeight)
    {
        try {
            if (!$fMolecularWeight) {
                return 0;
            }
            return ($fMolecularWeight * 1000) / 37;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }
}