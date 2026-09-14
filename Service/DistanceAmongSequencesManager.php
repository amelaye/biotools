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
use Amelaye\BioPHP\Domain\Tools\Interfaces\OligosInterface;
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
     * DistanceAmongSequencesManager constructor.
     * @param   OligosInterface        $oligosManager
     * @param   NucleotidApiAdapter    $nucleotidApi
     */
    public function __construct(OligosInterface $oligosManager, NucleotidApiAdapter $nucleotidApi)
    {
        $this->aDnaComplements = $nucleotidApi::GetDNAComplement($nucleotidApi->getNucleotids());
        $this->oligosManager = $oligosManager;
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
                $sValRevert = strrev($sVal);
                foreach ($this->aDnaComplements as $sNucleotide => $sComplement) {
                    $sValRevert = str_replace($sNucleotide, strtolower($sComplement), $sValRevert);
                }
                $sSeqAndRevseq = $sVal." ".strtoupper($sValRevert);

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
                    $aData[$iKey][$iKey2]= $this->pearsonDistance(
                        $aOligoArray[$iKey],
                        $aOligoArray[$iKey2]
                    );
                }
            }
            return $aData;
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
            $oIm    = new SvgCanvas((int) ($iWidth*1.2), $iRows*$iW+40);
            $sWhite = SvgCanvas::rgb(255, 255, 255);
            $sBlack = SvgCanvas::rgb(0, 0, 0);
            $sRed   = SvgCanvas::rgb(255, 0, 0);
            $oIm->background($sWhite);

            $iY = $iRows*$iW;    // vertical location
            $iF = $iWidth;       // multiplication factor

            // lines for scale
            $aJValues = [0.1, 0.2, 0.3, 0.5, 1.0, 1.5, 2.0];

            foreach($aJValues as $fJ) {
                $fX = log($fJ + 1) * $iF + 20;
                $fX2 = log($fJ + 1) * $iF - 8 + 20;
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
                    $fXmax = $fMax+(($fVal2-($fMax))/2);
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
        try {
            $fC = sqrt(pow(2, $iLen))
                / pow(4, $iLen);   // content
            $fSum = 0;
            foreach($aA as $sKey => $fVal) {
                $fSum += pow($fVal-$aB[$sKey],2);
            }
            $fResult = $fC * sqrt($fSum);
            return $fResult;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }


    /**
     * Generates the distance value from array X and array Y
     * Only in case pearson selected
     * Unit test created
     * @param   array   $aValsX     First array for comparaison
     * @param   array   $aValsY     Second array fo comparaison
     * @return  int
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
        try {
            $fValue = 0;
            // normal correlation
            if (sizeof($aValsX) != sizeof($aValsY)) {
                return $fValue;
            }
            $fSumX = 0;
            $fSumX2 = 0;
            $fSumY = 0;
            $fSumY2 = 0;
            $fSumXY = 0;
            $iN = sizeof($aValsX);
            foreach($aValsX as $sKey => $fVal){
                $fValX = $fVal;
                $fValY = $aValsY[$sKey];
                $fSumX += $fValX;
                $fSumX2 += $fValX * $fValX;
                $fSumY += $fValY;
                $fSumY2 += $fValY * $fValY;
                $fSumXY += $fValX * $fValY;
            }
            // calculate regression
            $fTempA = sqrt($fSumY2 - (1 / $iN) * $fSumY * $fSumY);
            $fTempB = sqrt($fSumX2 - (1 / $iN) * $fSumX * $fSumX);
            $fTempC = $fSumXY - (1 / $iN) * $fSumX * $fSumY;
            $fRegresion = $fTempC / ($fTempB * $fTempA);
            if ($fRegresion > 0.999999999) {
                $fRegresion = 1;
            }      // round data
            $fValue = 1 - $fRegresion;
            return $fValue;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Feeds the oligo array
     * No Unit Test : private access
     * @param   string      $sTheseq
     * @param   int         $iIteration
     * @return  array
     * @throws  \Exception
     */
    private function iterateOligo($sTheseq, $iIteration)
    {
        try {
            $aOligos = [];
            $i = 0;
            $iLen = strlen($sTheseq) - $iIteration + 1;
            while($i < $iLen) {
                $sSeq = substr($sTheseq, $i,$iIteration);
                if(isset($aOligos[$sSeq])) {
                    $aOligos[$sSeq]++;
                } else {
                    $aOligos[$sSeq] = 1;
                }
                $i++;
            }
            return $aOligos;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
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

            $aOligos2 = $this->iterateOligo($sTheseq, 2);
            $aOligos3 = $this->iterateOligo($sTheseq, 3);
            $aOligos4 = $this->iterateOligo($sTheseq, 4);

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
     * in each loop, array $aData is reduced (one case per loop)
     * @param $aData
     * @param $sMethod
     * @param $iLen
     * @param $sDendogramFile
     * @throws \Exception
     */
    public function upgmaClustering($aData, $sMethod, $iLen, $sDendogramFile)
    {
        try {
            while (sizeof($aData) > 1) {
                $fMin = $this->minArray($aData);
                $aComp[$this->sX][$this->sY] = $fMin;
                $aData = $this->newArray($aData);
            }

            $fMin = $this->minArray($aData);

            $sX = $this->sX;
            $sY = $this->sY;

            /*
             * end of clustering
             * array $aComp stores the important data
             */
            $aComp[$sX][$sY] = $fMin;

            /*
             * $sTextcluster is the results of the cluster as text.
             * p.e.:  ((3,4),7),(((5,6),1),2)
             */
            $sTextcluster = $sX.",".$sY;


            // CREATE THE IMAGE WITH THE DENDROGRAM
            return $this->createDendrogram($sTextcluster, $aComp, $sDendogramFile, $sMethod, $iLen);
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }
}