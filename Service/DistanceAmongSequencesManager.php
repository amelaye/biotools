<?php
/**
 * DistanceAmongSequencesManager
 * Freely inspired by BioPHP's project biophp.org
 * Created 26 february 2019
 * Last modified 14 september 2026
 * RIP Pasha, gone 27 february 2019 =^._.^= ∫
 */
namespace Amelaye\BioTools\Service;

use Amelaye\BioPHP\Api\Interfaces\NucleotidApiAdapter;
use Amelaye\BioPHP\Domain\Phylogenetics\Interfaces\UpgmaTreeBuilderInterface;
use Amelaye\BioPHP\Domain\Phylogenetics\Service\UpgmaTreeBuilder;
use Amelaye\BioPHP\Domain\Phylogenetics\ValueObject\DistanceMatrix;
use Amelaye\BioPHP\Domain\Phylogenetics\ValueObject\PhylogeneticNode;
use Amelaye\BioPHP\Domain\Tools\Interfaces\OligosInterface;
use Amelaye\BioPHP\Domain\Tools\Service\MathematicsFunctions;
use Amelaye\BioPHP\Domain\Tools\Service\OligosManager;
use Amelaye\BioPHP\Domain\Sequence\Traits\SequenceTrait;
use Amelaye\BioTools\Service\Graphics\SvgCanvas;

/**
 * Class DistanceAmongSequencesManager
 * @package BioTools\Service
 * @author Amélie DUVERNET aka Amelaye <amelieonline@gmail.com>
 */
class DistanceAmongSequencesManager
{
    use SequenceTrait;

    /**
     * With 2 common words a correlation is +1 or -1 by construction, so 3 is the least that carries information
     */
    public const MINIMUM_COMMON_WORDS = 3;

    /**
     * Part of the shorter vector that the common words must make up for the Pearson distance to be defined
     */
    public const MINIMUM_COMMON_COVERAGE = 0.5;

    /**
     * @var int|string
     */
    private $sX = null;

    /**
     * @var int|string
     */
    private $sY = null;

    /**
     * @var array
     */
    private $aCases = null;

    /**
     * @var array
     */
    private $aDnaComplements;

    /**
     * @var OligosManager
     */
    private $oligosManager;

    /**
     * @var UpgmaTreeBuilderInterface
     */
    private $upgmaTreeBuilder;

    /**
     * DistanceAmongSequencesManager constructor.
     * @param   OligosInterface                 $oligosManager
     * @param   NucleotidApiAdapter             $nucleotidApi
     * @param   UpgmaTreeBuilderInterface|null  $upgmaTreeBuilder   Defaults to BioPHP's UpgmaTreeBuilder
     */
    public function __construct(
        OligosInterface $oligosManager,
        NucleotidApiAdapter $nucleotidApi,
        ?UpgmaTreeBuilderInterface $upgmaTreeBuilder = null
    ) {
        $this->aDnaComplements = $nucleotidApi::GetDNAComplement($nucleotidApi->getNucleotids());
        $this->oligosManager = $oligosManager;
        $this->upgmaTreeBuilder = $upgmaTreeBuilder ?? new UpgmaTreeBuilder();
    }

    /**
     * Get the name of each sequence (save names to array $aSeqName)
     * Unit test created
     * @param   string  $sSeqs
     * @return  array[]|false|string[]
     * @throws  \Exception
     */
    public function formatSequences($sSeqs)
    {
        try {
            $aSeqs = preg_split("/>/", $sSeqs,-1,PREG_SPLIT_NO_EMPTY);
            foreach ($aSeqs as $iKey => $sVal) {
                $aSeqName[$iKey] = substr($sVal,0,strpos($sVal,"\n"));
                $sTempVal = substr($sVal,strpos($sVal,"\n"));
                $sTempVal = preg_replace("/\W|\d/","",$sTempVal);
                $aSeqs[$iKey] = strtoupper($sTempVal);
            }
            return $aSeqs;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * COMPUTE OLIGONUCLEOTIDE FREQUENCIES
     * Unit test created
     * @param $seqs
     * @param $len
     * @return mixed
     * @throws \Exception
     */
    public function computeOligonucleotidsFrequenciesEuclidean($aSeqs, $iLen)
    {
        if (!is_array($aSeqs)) {
            throw new \Exception('The sequences must be an array.');
        }
        try {
            $aOligoArray = [];
            foreach ($aSeqs as $iKey => $sVal) {
                // to compute oligonucleotide frequencies, both strands are used
                $sSeqAndRevseq = $sVal." ".$this->revCompDNA($sVal);

                $aOligos = $this->oligosManager->findOligos(
                    $sSeqAndRevseq,
                    $iLen
                );

                $aOligoArray[$iKey] = $this->standardFrecuencies($aOligos, $iLen);
            }
            return $aOligoArray;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * COMPUTE OLIGONUCLEOTIDE FREQUENCIES
     * @param $seqs
     * Unit test created
     * @return array
     * @throws \Exception
     */
    public function computeOligonucleotidsFrequencies($aSeqs)
    {
        if (!is_array($aSeqs)) {
            throw new \Exception('The sequences must be an array.');
        }
        try {
            $aOligoArray = [];
            foreach ($aSeqs as $iKey => $sTheseq) {
                $aOligoArray[$iKey] = $this->computeZscoresForTetranucleotides($sTheseq);
            }
            return $aOligoArray;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * COMPUTE DISTANCES AMONG SEQUENCES
     * by computing Euclidean distance
     * standarized oligonucleotide frequencies in $aOligoArray are used, and distances are stored in $aData array
     * Unit Test created
     * @param   array           $aSeqs
     * @param   array           $aOligoArray
     * @param   int             $iLen
     * @return  array
     * @throws  \Exception
     */
    public function computeDistancesAmongFrequenciesEuclidean($aSeqs, $aOligoArray, $iLen)
    {
        try {
            $aData = [];
            foreach ($aSeqs as $iKey => $sVal) {
                foreach($aSeqs as $iKey2 => $sVal2) {
                    if ($iKey >= $iKey2) {
                        continue;
                    }
                    $aData[$iKey][$iKey2] = $this->euclidDistance(
                        $aOligoArray[$iKey],
                        $aOligoArray[$iKey2],
                        $iLen
                    );
                }
            }
            return $aData;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * COMPUTE DISTANCES AMONG SEQUENCES
     * by computing Pearson distance
     * standarized oligonucleotide frequencies in $aOligoArray are used, and distances are stored in $aData array
     * Unit Test Created
     * @param   array           $aSeqs
     * @param   array           $aOligoArray
     * @return  mixed
     * @throws  \Exception
     */
    public function computeDistancesAmongFrequencies($aSeqs, $aOligoArray)
    {
        try {
            $aData = [];
            foreach($aSeqs as $iKey => $sVal){
                foreach($aSeqs as $iKey2 => $sVal2){
                    if ($iKey >= $iKey2) {
                        continue;
                    }
                    try {
                        $aData[$iKey][$iKey2] = $this->pearsonDistance(
                            $aOligoArray[$iKey],
                            $aOligoArray[$iKey2]
                        );
                    } catch (\DomainException $e) {
                        throw new \DomainException(
                            sprintf('Sequences %s and %s cannot be compared : %s', $iKey, $iKey2, $e->getMessage()),
                            0,
                            $e
                        );
                    }
                }
            }
            return $aData;
        } catch (\DomainException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }


    /**
     * @param   array   $aArray
     * @return  array|array[]|false|string[]
     * @throws  \Exception
     */
    public function getArrayCases($aArray)
    {
        try {
            $sDone = "";
            foreach($aArray as $sKey => $aVal){
                $sDone .= "#$sKey";
                foreach($aArray[$sKey] as $sKey2 => $fVal2){
                    $sDone .= "#$sKey2";
                }
            }
            $aCases = preg_split("/#/",$sDone,-1,PREG_SPLIT_NO_EMPTY);
            $aCases = array_unique($aCases);
            sort($aCases);
            return $aCases;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }


    /**
     * @param   array   $aArray
     * @return  mixed
     * @throws  \Exception
     */
    public function newArray($aArray)
    {
        try {
            $aCases = $this->getArrayCases($aArray);
            $aTempA = [];
            for($iJ = 0; $iJ < sizeof($aCases); $iJ++) {
                $sKey = $aCases[$iJ];

                // next 3 lines are required in windows for correct comparison
                settype($sKey, "string");
                settype($this->sX, "string");
                settype($this->sY, "string");

                if($sKey == $this->sX || $sKey == $this->sY) {
                    continue;
                }
                if(($aArray[$sKey][$this->sX] ?? "") != "") {
                    if(($aArray[$sKey][$this->sY] ?? "") != "") {
                        $aTempA[$sKey]["($this->sX,$this->sY)"] = ($aArray[$sKey][$this->sX]+$aArray[$sKey][$this->sY])/2;
                    }
                    if(($aArray[$this->sX][$sKey] ?? "") != "") {
                        $aTempA[$sKey]["($this->sX,$this->sY)"] = ($aArray[$sKey][$this->sX]+$aArray[$this->sX][$sKey])/2;
                    }
                    if(($aArray[$this->sY][$sKey] ?? "") != "") {
                        $aTempA[$sKey]["($this->sX,$this->sY)"] = ($aArray[$sKey][$this->sX]+$aArray[$this->sY][$sKey])/2;
                    }
                } else {
                    if(($aArray[$sKey][$this->sY] ?? "") != "") {
                        if (($aArray[$this->sX][$sKey] ?? "") != "") {
                            $aTempA[$sKey]["($this->sX,$this->sY)"] = ($aArray[$sKey][$this->sY]+$aArray[$this->sX][$sKey])/2;
                        }
                        if(($aArray[$this->sY][$sKey] ?? "") != "") {
                            $aTempA[$sKey]["($this->sX,$this->sY)"] = ($aArray[$sKey][$this->sY]+$aArray[$this->sY][$sKey])/2;
                        }
                    } else {
                        if(($aArray[$this->sY][$sKey] ?? "") != "") {
                            $aTempA[$sKey]["($this->sX,$this->sY)"] = ($aArray[$this->sY][$sKey]+$aArray[$this->sY][$sKey])/2;
                        }
                    }
                }

                for($i = $iJ+1; $i < sizeof($aCases); $i++) {
                    $sKey2 = $aCases[$i];
                    settype($sKey2, "string");
                    if ($sKey == $sKey2 || $sKey2 == $this->sX || $sKey2 == $this->sY) {
                        continue;
                    }
                    if (($aArray[$sKey][$sKey2] ?? "") != "") {
                        $aTempA[$sKey][$sKey2] = $aArray[$sKey][$sKey2];
                    }
                    if (($aArray[$sKey2][$sKey] ?? "") != "") {
                        $aTempA[$sKey][$sKey2] = $aArray[$sKey2][$sKey];
                    }
                }
            }
            return $aTempA;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }


    /**
     * @param   array   $aArray
     * @return  int|mixed
     * @throws  \Exception
     */
    public function minArray($aArray)
    {
        try {
            $sStrCases  = "";
            $fMin       = 1000000;
            $sDone      = "";
            foreach ($aArray as $sKey => $aVal) {
                $sStrCases .= "#$sKey";
                foreach($aArray[$sKey] as $sKey2 => $fVal2) {
                    if ($aVal == "") {
                        continue;
                    }
                    $sStrCases .= "#$sKey2";
                    if ($fVal2 < $fMin) {
                        $fMin = $fVal2;
                        $this->sX = $sKey;
                        $this->sY = $sKey2;
                    }
                }
            }
            $this->aCases = preg_split("/#/",$sDone,-1,PREG_SPLIT_NO_EMPTY);
            $this->aCases = array_unique($this->aCases);
            sort($this->aCases);
            return $fMin;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Creates the dendrogram, as an SVG drawing
     * @param   string      $sStr
     * @param   array       $aComp
     * @param   string      $sDendogramFile  Path of the file to write
     * @param   string      $sMethod
     * @param   int         $iLen
     * @return  string      The path written
     * @throws  \Exception
     */
    public function createDendrogram($sStr, $aComp, $sDendogramFile, $sMethod, $iLen)
    {
        try {
            $iW      = 20;          //height for each line (case)
            $aWherex = [];

            $sStr   = preg_replace("/\(|\)/","",$sStr).",";
            $aStr   = preg_split("/,/",$sStr,-1,PREG_SPLIT_NO_EMPTY);
            $iRows  = sizeof($aStr);

            $iWidth = 600;     // width of scale from 0 to 2
            $oIm    = new SvgCanvas((int) ($iWidth*1.2), $iRows*$iW+52);
            $sWhite = SvgCanvas::rgb(255, 255, 255);
            $sBlack = SvgCanvas::rgb(0, 0, 0);
            $sRed   = SvgCanvas::rgb(255, 0, 0);
            $oIm->background($sWhite);

            $iY = $iRows*$iW;    // vertical location
            $iF = $iWidth;       // multiplication factor

            // lines for scale
            $aJValues = [0.1, 0.2, 0.3, 0.5, 1.0, 1.5, 2.0];

            // the scale is graduated in distances D, and a node is drawn at the height D / 2 (UPGMA is
            // ultrametric): a merge at distance D falls on the tick labelled D. The axis is logarithmic.
            foreach($aJValues as $fJ) {
                $fX = log($fJ / 2 + 1) * $iF + 20;
                $fX2 = log($fJ / 2 + 1) * $iF - 8 + 20;
                $oIm->line($fX, $iY, $fX, $iY + 10, $sBlack);
                $oIm->text(1, $fX2, $iY + 12,  $fJ, $sBlack);
            }

            // write into the image the numbers corresponding to cases
            foreach($aStr as $iN => $sVal) {
                if(strlen($sVal) == 1) {
                    $sVal = " $sVal";
                }
                $oIm->text(3, 5, $iN * $iW + 5,  $sVal, $sBlack);
            }

            // WRITE LINES
            foreach ($aComp as $sKey => $aVal) {
                $fPos1 = $fPos2 = 0;
                foreach ($aComp[$sKey] as $sKey2 => $fVal2) {

                    // get position of case in the list
                    $sKeyA = preg_replace("/\(|\)/","",$sKey);
                    $fPos1 = substr_count (" ,".substr($sStr, 0,strpos(" ,".$sStr,",$sKeyA,")),",")-0.4;
                    $sKeyB = preg_replace("/\(|\)/","",$sKey2);
                    $fPos2 = substr_count (" ,".substr($sStr, 0,strpos(" ,".$sStr,",$sKeyB,")),",")-0.4;
                    if(substr_count($sKeyA,",")>0) {
                        $fPos1b = $fPos1 + substr_count($sKeyA,",")/2;
                    } else {
                        $fPos1b = $fPos1;
                    }
                    if(substr_count($sKeyB,",") > 0) {
                        $fPos2b = $fPos2+substr_count($sKeyB,",")/2;
                    } else {
                        $fPos2b = $fPos2;
                    }

                    // Position related data
                    $fXKey1 = isset($aWherex[$sKey]) ? $fXKey1 = $aWherex[$sKey] : $fXKey1 = 0;
                    if($fXKey1 == "") {
                        $fXKey1 = 0;
                    }

                    $fXKey2 = isset($aWherex[$sKey2]) ? $fXKey2 = $aWherex[$sKey2] : $fXKey2 = 0;
                    if($fXKey2 == "") {
                        $fXKey2 = 0;
                    }
                    $fMax = max($fXKey1,$fXKey2);
                    $fMin = min($fXKey1,$fXKey2);
                    // UPGMA: a node sits at half the distance of the merge, whatever the height of its children
                    $fXmax = $fVal2 / 2;
                    $fVal4 = log($fXmax+1)*$iF;
                    $fVal4max = log($fMax+1)*$iF;
                    $fVal4min = log($fMin+1)*$iF;

                    // write lines
                    if (isset($aWherex[$sKey]) && $aWherex[$sKey] == $fMax) {
                        $oIm->line($fVal4max+20, $fPos1b*$iW, $fVal4+20, $fPos1b*$iW, $sBlack);
                        $oIm->line($fVal4+20, $fPos1b*$iW, $fVal4+20, $fPos2b*$iW, $sBlack);
                        $oIm->line($fVal4min+20, $fPos2b*$iW, $fVal4+20, $fPos2b*$iW, $sBlack);
                    }else{
                        $oIm->line($fVal4min+20, $fPos1b*$iW, $fVal4+20, $fPos1b*$iW, $sBlack);
                        $oIm->line($fVal4+20, $fPos1b*$iW, $fVal4+20, $fPos2b*$iW, $sBlack);
                        $oIm->line($fVal4max+20, $fPos2b*$iW, $fVal4+20, $fPos2b*$iW, $sBlack);
                    }
                    $aWherex["(".$sKey.",".$sKey2.")"] = $fXmax;
                }

            }
            $oIm->line($fVal4+20, ($fPos1b+$fPos2b)*$iW/2, $fVal4+40, ($fPos1b+$fPos2b)*$iW/2, $sBlack);
            $oIm->line(20, $iY, $iWidth*1.2, $iY, $sBlack);

            if ($sMethod == "euclidean") {
                $oIm->text(2, 5, $iRows*$iW+25,  "Euclidean distance for ".$iLen." bases long oligonucleotides.", $sRed);
            } else {
                $oIm->text(2, 5, $iRows*$iW+25,  "Pearson distance for z-scores of tetranucleotides.", $sRed);
            }

            $oIm->text(1, 5, $iRows*$iW+42,  "Scale: distance D between the groups, drawn at the height D / 2 (UPGMA), logarithmic axis.", $sBlack);
            $oIm->text(2, $iWidth*1, $iRows*$iW+25,  "by insilico.ehu.es", $sBlack);
            return $oIm->save($sDendogramFile);
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Generates the distance value from array X and array Y
     * Only in case euclidian selected
     * Wang et al, Gene 2005; 346:173-185
     * Unit test created
     * @param   array   $aA     First array for comparaison
     * @param   array   $aB     Second array fo comparaison
     * @param   int     $iLen   Length of the combinations
     * @return  float
     * @throws \Exception
     */
    public function euclidDistance($aA, $aB, $iLen)
    {
        if (!is_array($aA)) {
            throw new \Exception('The X values must be an array.');
        }
        if (!is_array($aB)) {
            throw new \Exception('The Y values must be an array.');
        }
        return MathematicsFunctions::EuclideanDistance($aA, $aB, (int) $iLen);
    }

    /**
     * Whether all the values are the same, up to the rounding of a floating point number
     * @param   array   $aValues
     * @return  bool
     */
    private function isConstant(array $aValues) : bool
    {
        $fMax = max($aValues);
        $fMin = min($aValues);

        return ($fMax - $fMin) <= 1e-9 * max(1.0, abs($fMax), abs($fMin));
    }

    /**
     * Generates the distance value from array X and array Y
     * Only in case pearson selected
     * Unit test created
     * The z-scores of a sequence leave out the words whose variance is null, so two vectors rarely
     * hold the same words : they are compared on the words they have in common. When that leaves
     * no correlation to compute, the distance is undefined, and it is never reported as 0
     * (which would mean "identical").
     * @param   array   $aValsX     First array for comparaison
     * @param   array   $aValsY     Second array fo comparaison
     * @return  float
     * @throws  \DomainException    When the distance is undefined
     * @throws  \Exception
     */
    public function pearsonDistance($aValsX, $aValsY)
    {
        if (!is_array($aValsX)) {
            throw new \Exception('The X values must be an array.');
        }
        if (!is_array($aValsY)) {
            throw new \Exception('The Y values must be an array.');
        }

        $aCommonKeys = array_keys(array_intersect_key($aValsX, $aValsY));
        if (count($aCommonKeys) < self::MINIMUM_COMMON_WORDS) {
            throw new \DomainException(sprintf(
                'Pearson distance is undefined : %d common z-score(s), %d at least are needed.',
                count($aCommonKeys),
                self::MINIMUM_COMMON_WORDS
            ));
        }
        // The words of a sequence are those whose variance is not null: two sequences rarely hold the
        // same ones. When the words they share are only a small part of the shorter vector, the two are
        // not described by the same words, and their correlation says little about their resemblance.
        $iShorter = min(count($aValsX), count($aValsY));
        if (count($aCommonKeys) < self::MINIMUM_COMMON_COVERAGE * $iShorter) {
            throw new \DomainException(sprintf(
                'Pearson distance is undefined : only %d common z-score(s) out of %d, at least %d%% of the shorter vector is needed.',
                count($aCommonKeys),
                $iShorter,
                self::MINIMUM_COMMON_COVERAGE * 100
            ));
        }

        $aCommonX = $aCommonY = [];
        foreach ($aCommonKeys as $mKey) {
            $aCommonX[$mKey] = $aValsX[$mKey];
            $aCommonY[$mKey] = $aValsY[$mKey];
        }

        // BioPHP's PearsonDistance raises DivisionByZeroError for exactly constant series only: on
        // decimals that are all equal, its one-pass variance is rounding noise, not 0, and it returns a
        // meaningless number close to 1. The constant series are therefore refused here.
        if ($this->isConstant($aCommonX) || $this->isConstant($aCommonY)) {
            throw new \DomainException('Pearson distance is undefined : the common z-scores of a sequence are all equal.');
        }

        try {
            $fDistance = MathematicsFunctions::PearsonDistance($aCommonX, $aCommonY);
        } catch (\DivisionByZeroError $e) {
            throw new \DomainException('Pearson distance is undefined : the common z-scores of a sequence are all equal.');
        }
        if (!is_finite($fDistance)) {
            throw new \DomainException('Pearson distance is undefined : the correlation is not a finite number.');
        }

        return $fDistance;
    }

    /**
     * As described by Teeling et al. BMC Bioinformatics 2004, 5:163.
     * Unit test created
     * @param   string  $sTheseq
     * @return  mixed
     * @throws  \Exception
     */
    public function computeZscoresForTetranucleotides($sTheseq)
    {
        try {
            $sTheseq .= " ".$this->revCompDNA($sTheseq);

            $aOligos2 = $this->oligosManager->findOligos($sTheseq, 2);
            $aOligos3 = $this->oligosManager->findOligos($sTheseq, 3);
            $aOligos4 = $this->oligosManager->findOligos($sTheseq, 4);

            $aZscore = $this->oligosManager->findZScore($aOligos2, $aOligos3, $aOligos4);

            return $aZscore;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Generates array of frequencies
     * Unit test created
     * @param       array   $aArray
     * @param       int     $iLen
     * @return      mixed
     * @throws      \Exception
     */
    public function standardFrecuencies($aArray, $iLen)
    {
        if (!is_array($aArray) || [] === $aArray) {
            throw new \Exception('The frequencies array must not be empty.');
        }
        try {
            $fSum = 0;
            foreach($aArray as $sK => $fV) {
                $fSum += $fV;
            }
            $fC = pow(4, $iLen) / $fSum;
            foreach($aArray as $sK => $fV) {
                $aArray[$sK] = $fC * $fV;
            }
            return $aArray;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Perform UPGMA Clustering
     * The clustering is done by BioPHP's UpgmaTreeBuilder, which weights each merge by the
     * size of the clusters (true UPGMA). The former loop over newArray()/minArray() averaged
     * the two merged distances 50/50 whatever the cluster sizes, i.e. WPGMA; both helpers
     * are kept for compatibility but are no longer used here.
     * @param   array   $aData              Triangular matrix of distances, [$x][$y] => distance
     * @param   string  $sMethod
     * @param   int     $iLen
     * @param   string  $sDendogramFile
     * @return  string  The path written
     * @throws  \Exception
     */
    public function upgmaClustering($aData, $sMethod, $iLen, $sDendogramFile)
    {
        try {
            $oRoot = $this->upgmaTreeBuilder->build($this->buildDistanceMatrix($aData));

            /*
             * $aComp stores the merges, $aComp[left cluster][right cluster] = distance, children
             * before their parents. $sTextcluster is the result of the cluster as text,
             * p.e.:  ((3,4),7),(((5,6),1),2)
             */
            $aComp = [];
            $this->collectMerges($oRoot, $aComp);
            $aChildren = $oRoot->getChildren();
            $sTextcluster = $this->clusterName($aChildren[0]).",".$this->clusterName($aChildren[1]);

            // CREATE THE IMAGE WITH THE DENDROGRAM
            return $this->createDendrogram($sTextcluster, $aComp, $sDendogramFile, $sMethod, $iLen);
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Turns the triangular array of distances into a symmetric DistanceMatrix
     * @param   array   $aData
     * @return  DistanceMatrix
     * @throws  \Exception     When a pair of sequences has no distance
     */
    private function buildDistanceMatrix(array $aData) : DistanceMatrix
    {
        $aLabels = array_map('strval', $this->getArrayCases($aData));
        $aMatrix = [];
        foreach ($aLabels as $iRow => $sRow) {
            foreach ($aLabels as $iColumn => $sColumn) {
                if ($iRow === $iColumn) {
                    $aMatrix[$iRow][$iColumn] = 0.0;
                    continue;
                }
                $mDistance = $aData[$sRow][$sColumn] ?? $aData[$sColumn][$sRow] ?? null;
                if (!is_numeric($mDistance)) {
                    throw new \Exception("No distance between sequences $sRow and $sColumn.");
                }
                // a Pearson distance can fall a few ulps under zero
                $aMatrix[$iRow][$iColumn] = max(0.0, (float) $mDistance);
            }
        }

        return new DistanceMatrix($aLabels, $aMatrix);
    }

    /**
     * Name of a cluster as the dendrogram expects it: a leaf label, or "(left,right)"
     * @param   PhylogeneticNode    $oNode
     * @return  string
     */
    private function clusterName(PhylogeneticNode $oNode) : string
    {
        if ($oNode->isLeaf()) {
            return (string) $oNode->getName();
        }
        $aChildren = $oNode->getChildren();

        return "(".$this->clusterName($aChildren[0]).",".$this->clusterName($aChildren[1]).")";
    }

    /**
     * Height of a node, i.e. half of the distance at which its children merged
     * @param   PhylogeneticNode    $oNode
     * @return  float
     */
    private function nodeHeight(PhylogeneticNode $oNode) : float
    {
        if ($oNode->isLeaf()) {
            return 0.0;
        }
        $oChild = $oNode->getChildren()[0];

        return $this->nodeHeight($oChild) + (float) $oChild->getBranchLength();
    }

    /**
     * Records each merge of the tree, children before parents
     * @param   PhylogeneticNode    $oNode
     * @param   array               $aComp
     */
    private function collectMerges(PhylogeneticNode $oNode, array &$aComp) : void
    {
        if ($oNode->isLeaf()) {
            return;
        }
        $aChildren = $oNode->getChildren();
        $this->collectMerges($aChildren[0], $aComp);
        $this->collectMerges($aChildren[1], $aComp);
        $aComp[$this->clusterName($aChildren[0])][$this->clusterName($aChildren[1])] = 2 * $this->nodeHeight($oNode);
    }
}
