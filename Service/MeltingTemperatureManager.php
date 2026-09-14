<?php
/**
 * MeltingTemperatureManager
 * Inspired by BioPHP's project biophp.org
 * Created 26 february 2019
 * Last modified 14 september 2026
 * RIP Pasha, gone 27 february 2019 =^._.^= ∫
 */
namespace Amelaye\BioTools\Service;

use Amelaye\BioPHP\Api\Interfaces\TmBaseStackingApiAdapter;
use Amelaye\BioPHP\Domain\Sequence\Entity\Sequence;
use Amelaye\BioPHP\Domain\Sequence\Interfaces\SequenceInterface;
use Amelaye\BioPHP\Domain\Tools\Service\GeneticsFunctions;

/**
 * Class MeltingTemperatureManager
 * @package BioTools\Service
 * @author Amélie DUVERNET aka Amelaye <amelieonline@gmail.com>
 */
class MeltingTemperatureManager
{
    /**
     * @var SequenceInterface
     */
    private $sequenceManager;

    /**
     * @var array
     */
    private $aEnthropyValues;

    /**
     * @var array
     */
    private $aEnthalpyValues;

    /**
     * MeltingTemperatureManager constructor.
     * @param   SequenceInterface           $sequenceManager
     * @param   TmBaseStackingApiAdapter    $tmBaseStackingApi
     */
    public function __construct(
        SequenceInterface $sequenceManager,
        TmBaseStackingApiAdapter $tmBaseStackingApi
    )
    {
        $this->sequenceManager      = $sequenceManager;
        $this->aEnthropyValues      = $tmBaseStackingApi::GetEnthropyValues($tmBaseStackingApi->getTmBaseStackings());
        $this->aEnthalpyValues      = $tmBaseStackingApi::GetEnthalpyValues($tmBaseStackingApi->getTmBaseStackings());
    }

    /**
     * Calculates CG
     * An empty primer has no C+G at all, so it scores 0. Legacy divided by strlen()
     * unguarded: under PHP 5 that warned and yielded INF, but since PHP 8 it is a
     * fatal DivisionByZeroError, and an empty primer does reach here - legacy only
     * enforces the 6-50 bp range on a non-empty primer ("if ($primer!="" and ...)"),
     * a rule the MeltingTemperature constraint reproduces.
     * @param       string     $sPrimer
     * @return      float|int
     * @throws      \Exception
     */
    public function calculateCG($sPrimer)
    {
        if (!is_string($sPrimer)) {
            throw new \Exception('The primer must be a string.');
        }
        if ($sPrimer === "") {
            return 0;
        }
        $fCg = round(100 * GeneticsFunctions::CountCG($sPrimer) / strlen($sPrimer),1);
        return $fCg;
    }

    /**
     * Gets both the upper and lower MWT
     * @param   float   $fUpperMwt
     * @param   float   $fLowerMwt
     * @param   string  $sPrimer
     * @throws  \Exception
     */
    public function calculateMWT(&$fUpperMwt, &$fLowerMwt, $sPrimer)
    {
        $fUpperMwt = $this->molwt($sPrimer,"DNA","upperlimit");
        $fLowerMwt = $this->molwt($sPrimer,"DNA","lowerlimit");
    }

    /**
     * @param   bool    $bBasic
     * @param   string  $sPrimer
     * @param   int     $iCountATGC
     * @param   float   $fTmMin
     * @param   float   $fTmMax
     * @throws  \Exception
     */
    public function basicCalculations($bBasic, $sPrimer, &$iCountATGC, &$fTmMin, &$fTmMax)
    {
        if($bBasic) {
            $iCountATGC = GeneticsFunctions::CountACGT($sPrimer);
            $fTmMin = $this->tmMin($sPrimer);
            $fTmMax = $this->tmMax($sPrimer);
        }
    }

    /**
     * @param $bNearestNeighbor
     * @param   array   $aTmBaseStacking
     * @param   string  $sPrimer
     * @param   int     $iConcPrimer
     * @param   int     $iConcSalt
     * @param   int     $iConcMg
     * @throws \Exception
     */
    public function neighborCalculations($bNearestNeighbor, &$aTmBaseStacking, $sPrimer, $iConcPrimer, $iConcSalt, $iConcMg)
    {
        if($bNearestNeighbor) {
            $aTmBaseStacking = $this->tmBaseStacking(
                $sPrimer, $iConcPrimer, $iConcSalt, $iConcMg
            );
        }
    }

    /**
     * Gets different informations when degenerated nucleotids are not allowed
     * @param   string      $sPrimer         Primer string
     * @param   int         $iConcPrimer     Primer concentration
     * @param   int         $iConcSalt       Salt concentration
     * @param   int         $iConcMg         Mg2+ concentration
     * @return  array
     * @throws  \Exception
     */
    public function tmBaseStacking($sPrimer, $iConcPrimer, $iConcSalt, $iConcMg)
    {
        if (!is_string($sPrimer)) {
            throw new \Exception('The primer must be a string.');
        }
        try {
            // legacy: "if (CountATCG($c)!= strlen($c)){return "Non computed. The
            // oligonucleotide contains degenerated nucleotides.";}" - the nearest
            // neighbor / base stacking method has no defined thermodynamic values for
            // degenerate nucleotides, unlike the basic Tm methods which tolerate them
            if (GeneticsFunctions::CountACGT($sPrimer) != strlen($sPrimer)) {
                return [
                    'tm'        => null,
                    'enthalpy'  => null,
                    'entropy'   => null,
                    'message'   => 'Non computed. The oligonucleotide contains degenerated nucleotides.',
                ];
            }

            $fH = $fS = 0;

            $aEnthalpyValues = $this->aEnthalpyValues;
            $aEnthropyValues = $this->aEnthropyValues;

            // effect on entropy by salt correction; von Ahsen et al 1999
            // Increase of stability due to presence of Mg;
            $fSaltEffect = ($iConcSalt/1000) + (($iConcMg/1000) * 140);
            // effect on entropy
            $fS += 0.368 * (strlen($sPrimer)-1) * log($fSaltEffect);

            // terminal corrections. Santalucia 1998
            $sFirstNucleotid = substr($sPrimer,0,1);
            if($sFirstNucleotid == "G" || $sFirstNucleotid == "C") {
                $fH += 0.1;
                $fS += -2.8;
            }
            if($sFirstNucleotid == "A" ||  $sFirstNucleotid == "T") {
                $fH += 2.3;
                $fS += 4.1;
            }

            $sLastNucleotid = substr($sPrimer,strlen($sPrimer)-1,1);
            if ($sLastNucleotid == "G" || $sLastNucleotid == "C") {
                $fH += 0.1;
                $fS += -2.8;
            }
            if ($sLastNucleotid == "A" || $sLastNucleotid == "T"){
                $fH += 2.3;
                $fS += 4.1;
            }

            // compute new H and s based on sequence. Santalucia 1998
            for($i = 0; $i < strlen($sPrimer)-1; $i++) {
                $sSubc = substr($sPrimer, $i,2);
                $fH += $aEnthalpyValues[$sSubc];
                $fS += $aEnthropyValues[$sSubc];
            }
            $fTm = ((1000 * $fH) / ($fS + (1.987 * log($iConcPrimer / 2000000000)))) - 273.15;

            $aBaseStacking = [
                'tm'        => round($fTm, 1),
                'enthalpy'  => round($fH,2),
                'entropy'   => round($fS,2)
            ];

            return $aBaseStacking;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Gets temperature mini
     * @param       string      $sPrimer     Sequence to analyze
     * @return      float
     * @throws      \Exception
     */
    public function tmMin($sPrimer)
    {
        if (!is_string($sPrimer)) {
            throw new \Exception('The primer must be a string.');
        }
        try {
            $iPrimerLen = strlen($sPrimer);
            $sPrimer2 = $this->primerMin($sPrimer);
            $fTemperature = $this->calculateTemperature($sPrimer2, $iPrimerLen);

            return $fTemperature;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }


    /**
     * Gets temperature maxi
     * @param       string      $sPrimer     Sequence to analyze
     * @return      float
     * @throws      \Exception
     */
    public function tmMax($sPrimer)
    {
        if (!is_string($sPrimer)) {
            throw new \Exception('The primer must be a string.');
        }
        try {
            $iPrimerLen = strlen($sPrimer);
            $sPrimer2 = $this->primerMax($sPrimer);
            $fTemperature = $this->calculateTemperature($sPrimer2, $iPrimerLen);
            return $fTemperature;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Gets the weight of the sequence
     * @param   string      $sSequence       Sequence to analyse
     * @param   string      $sMoltype        DNA or RNA
     * @param   string      $sLimit          "Upperlimit" or "lowerlimit" (string)
     * @return  float
     * @throws  \Exception
     */
    public function molwt($sSequence, $sMoltype, $sLimit)
    {
        try {
            $oSequence = new Sequence();
            $oSequence->setSequence($sSequence);
            $oSequence->setMoltype($sMoltype);
            $oSequence->setSeqLength(strlen($sSequence));
            $this->sequenceManager->setSequence($oSequence);
            $fMwt = $this->sequenceManager->molwt($sLimit);
            return $fMwt;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Gets Temperature
     * @param   string      $sPrimer
     * @param   int         $iPrimerLen
     * @return  float
     * @throws  \Exception
     */
    private function calculateTemperature($sPrimer, $iPrimerLen)
    {
        try {
            $fTemperature = 0;

            $iNbAT = substr_count($sPrimer,"A");
            $iNbCG = substr_count($sPrimer,"G");

            if($iPrimerLen > 0) {
                if($iPrimerLen < 14) {
                    $fTemperature = round(2 * ($iNbAT) + 4 * ($iNbCG));
                } else {
                    $fTemperature = round(64.9 + 41 * (($iNbCG - 16.4) / $iPrimerLen),1);
                }
            }

            return $fTemperature;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Unreduces the primer
     * @param       string      $sPrimer     Sequence to analyze
     * @return      string
     * @throws      \Exception
     */
    private function primerMax($sPrimer)
    {
        try {
            $sPrimer = preg_replace("/A|T|W/","A",$sPrimer);
            $sPrimer = preg_replace("/C|G|Y|R|S|K|M|D|V|H|B|N/","G",$sPrimer);
            return $sPrimer;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Reduces the primer
     * @param       string      $sPrimer     Sequence to analyze
     * @return      string
     * @throws      \Exception
     */
    private function primerMin($sPrimer)
    {
        try {
            $sPrimer = preg_replace("/A|T|Y|R|W|K|M|D|V|H|B|N/","A",$sPrimer);
            $sPrimer = preg_replace("/C|G|S/","G",$sPrimer);
            return $sPrimer;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }
}