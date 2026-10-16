<?php
/**
 * MeltingTemperatureManager
 * Inspired by BioPHP's project biophp.org
 * Created 26 february 2019
 * Last modified 16 october 2026
 * RIP Pasha, gone 27 february 2019 =^._.^= ∫
 */
namespace Amelaye\BioTools\Service;

use Amelaye\BioPHP\Api\Interfaces\TmBaseStackingApiAdapter;
use Amelaye\BioPHP\Domain\Sequence\Entity\Sequence;
use Amelaye\BioPHP\Domain\Sequence\Interfaces\SequenceInterface;
use Amelaye\BioPHP\Domain\Tools\Interfaces\MeltingTemperatureInterface;
use Amelaye\BioPHP\Domain\Tools\Service\GeneticsFunctions;
use Amelaye\BioPHP\Domain\Tools\Service\MeltingTemperatureCalculator;

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
     * @var MeltingTemperatureInterface
     */
    private $meltingTemperature;

    /**
     * MeltingTemperatureManager constructor.
     * The Tm and GC% maths live in BioPHP's MeltingTemperatureCalculator; when none is
     * injected, one is built from the base stacking adapter.
     * @param   SequenceInterface               $sequenceManager
     * @param   TmBaseStackingApiAdapter        $tmBaseStackingApi
     * @param   MeltingTemperatureInterface|null $meltingTemperature
     */
    public function __construct(
        SequenceInterface $sequenceManager,
        TmBaseStackingApiAdapter $tmBaseStackingApi,
        ?MeltingTemperatureInterface $meltingTemperature = null
    )
    {
        $this->sequenceManager      = $sequenceManager;
        $this->meltingTemperature   = $meltingTemperature ?? new MeltingTemperatureCalculator($tmBaseStackingApi);
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
        return $this->meltingTemperature->calculateGcPercent($sPrimer);
    }

    /**
     * Gets both the upper and lower MWT, of a 5'-phosphate strand (see molwt())
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
            $iCountATGC = GeneticsFunctions::CountACGT(strtoupper($sPrimer));
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
            if (GeneticsFunctions::CountACGT(strtoupper($sPrimer)) != strlen($sPrimer)) {
                return [
                    'tm'        => null,
                    'enthalpy'  => null,
                    'entropy'   => null,
                    'message'   => 'Non computed. The oligonucleotide contains degenerated nucleotides.',
                ];
            }

            $oResult = $this->meltingTemperature->calculateNearestNeighborTm(
                $sPrimer, (float) $iConcPrimer, (float) $iConcSalt, (float) $iConcMg
            );

            $aBaseStacking = [
                'tm'        => $oResult->getTm(),
                'enthalpy'  => $oResult->getEnthalpy(),
                'entropy'   => $oResult->getEntropy()
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
            return $this->meltingTemperature->calculateMinimumTm($sPrimer);
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
            return $this->meltingTemperature->calculateMaximumTm($sPrimer);
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Gets the weight of the sequence. BioPHP's convention is a strand with a 5'-phosphate: it is
     * about 79.98 Da (HPO3) above a synthetic primer, which has a 5'-OH.
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
}
