<?php
/**
 * Skews Functions
 * Inspired by BioPHP's project biophp.org
 * Created 1st march 2019
 * Last modified 14 september 2026
 * RIP Pasha, gone 27 february 2019 =^._.^= ∫
 */
namespace Amelaye\BioTools\Service;

use Amelaye\BioPHP\Domain\Tools\Interfaces\OligosInterface;
use Amelaye\BioPHP\Domain\Sequence\Traits\SequenceTrait;
use Amelaye\BioTools\Service\Graphics\SvgCanvas;

/**
 * Class SkewsManager
 * @package BioTools\Service
 * @author Amélie DUVERNET aka Amelaye <amelieonline@gmail.com>
 */
class SkewsManager
{
    use SequenceTrait;

    private $oligosManger;

    /**
     * @var array   Geometry and output directory of the generated graphics
     */
    private $aNucleotidsGraphs;

    /**
     * @param   array               $aNucleotidsGraphs
     * @param   OligosInterface     $oligosManger
     */
    public function __construct(array $aNucleotidsGraphs, OligosInterface $oligosManger)
    {
        $this->aNucleotidsGraphs = $aNucleotidsGraphs;
        $this->oligosManger = $oligosManger;
    }

    /**
     * Builds the path of the file to write, inside the configured directory. The name
     * carries a random suffix so that two concurrent requests cannot overwrite each
     * other's graphic.
     * @param   string      $sName
     * @return  string
     */
    private function buildTargetPath(string $sName) : string
    {
        $sDirectory = rtrim($this->aNucleotidsGraphs['path_graphs'] ?? '', '/');
        $sSafeName  = preg_replace('/[^A-Za-z0-9_-]/', '_', $sName);

        return $sDirectory . '/' . $sSafeName . '_' . bin2hex(random_bytes(4)) . '.svg';
    }

    /**
     * Will  compare oligonucleotide frequencies in all the sequence
     * with frequencies in each window, and will return an array
     * with distances  (computed as Almeida et al, 2001).
     * @param       string      $sSequence      The sequence to analyze
     * @param       int         $iWindow        The size of the window
     * @param       int         $iOskew         Number of nucleos
     * @param       int         $iStrands       One or both strands
     * @return      array
     * @throws      \Exception
     */
    public function oligoSkewArrayCalculation($sSequence, $iWindow, $iOskew, $iStrands)
    {
        try {
            $aDistances = [];

            // search for oligos in the complet sequence
            $aOligosX = $this->oligosManger->findOligos($sSequence, $iOskew);
            $iSeqLength = strlen($sSequence);
            $iPeriod = ceil($iSeqLength / 1400);
            if($iPeriod < 10) {
                $iPeriod = 10;
            }
            if ($iStrands == 2) {
                // if both strands are used for computing oligonucleotide frequencies
                $sSequence2 = $this->compDNA($sSequence);
                $i = 0;
                while ($i < $iSeqLength - $iWindow + 1) {
                    $sSequenceCut = substr($sSequence, $i, $iWindow)." ".strrev(substr($sSequence2, $i, $iWindow));
                    // compute oligonucleotide frequencies in window
                    $aOligosY = $this->oligosManger->findOligos($sSequenceCut, $iOskew);
                    // compute distance between complete sequence and window
                    $aDistances[$i] = $this->distance($aOligosX, $aOligosY);
                    $i += $iPeriod;
                }
            } else {
                // if only one strand is used for computing oligonucleotide frequencies
                $i = 0;
                while($i < $iSeqLength - $iWindow + 1) {
                    $sSequenceCut = substr($sSequence, $i ,$iWindow);
                    // compute oligonucleotide frequencies in window
                    $aOligosY = $this->oligosManger->findOligos($sSequenceCut, $iOskew);
                    // compute distance between complete sequence and window
                    $aDistances[$i] = $this->pearsonDistance($aOligosX, $aOligosY);
                    $i += $iPeriod;
                }
            }
            // return the array with distances
            return $aDistances;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Computes distance between two arrays of values based in Almeida et al, 2001
     * http://www.ncbi.nlm.nih.gov/entrez/query.fcgi?cmd=Retrieve&db=pubmed&dopt=Abstract&list_uids=11331237
     * (which is a based in a modified Pearson correlation)
     * @param       array       $aValsX     Values for X
     * @param       array       $aValsY     Values for Y
     * @return      float|void
     */
    public function distance($aValsX, $aValsY)
    {
        if(sizeof($aValsX) != sizeof($aValsY)) {
            return;
        }
        $fNw = $fX2y = $fXy2 = $fPreSx = $fPreSy = $fPreRw = 0;

        foreach($aValsX as $sKey => $fValX) {
            $fValY = $aValsY[$sKey];
            $fNw += $fValX * $fValY;
            $fX2y += $fValX * $fValX * $fValY;
            $fXy2 += $fValX * $fValY * $fValY;
        }
        $fXw = $fX2y / $fNw;
        $fYw = $fXy2 / $fNw;
        foreach($aValsX as $sKey => $fValX) {
            $fValY = $aValsY[$sKey];
            $fPreSx += pow($fValX - $fXw,2) * $fValX * $fValY;
            $fPreSy += pow($fValY - $fYw,2) * $fValX * $fValY;
        }
        $fSx = $fPreSx / $fNw;
        $fSy = $fPreSy / $fNw;
        foreach($aValsX as $sKey => $fValX){
            $fValY = $aValsY[$sKey];
            $fPreRw += ($fValX - $fXw) * ($fValY - $fYw) * $fValX * $fValY / (sqrt($fSx) * sqrt($fSy));
        }
        $fRw = $fPreRw / $fNw;
        $fDistance = round(1 - $fRw,8);
        return $fDistance;
    }

    /**
     * Computes the standard Pearson distance between two arrays of values, used to
     * compare oligonucleotide frequencies when only one strand is analysed
     * @param       array       $aValsX     Values for X
     * @param       array       $aValsY     Values for Y
     * @return      float|void
     */
    public function pearsonDistance($aValsX, $aValsY)
    {
        if(sizeof($aValsX) != sizeof($aValsY)) {
            return;
        }
        $fSumX = array_sum($aValsX);
        $fSumY = array_sum($aValsY);
        $fSumX2 = 0;
        $fSumY2 = 0;
        $fSumXY = 0;
        foreach($aValsX as $sKey => $fValX) {
            $fValY = $aValsY[$sKey];
            $fSumX2 += $fValX * $fValX;
            $fSumY2 += $fValY * $fValY;
            $fSumXY += $fValX * $fValY;
        }
        $iN = sizeof($aValsX);
        // calculate regression
        $fR = ($iN * $fSumXY - $fSumX * $fSumY)
            / (sqrt($iN * $fSumX2 - $fSumX * $fSumX) * sqrt($iN * $fSumY2 - $fSumY * $fSumY));
        // return distance
        return (1 - $fR);
    }


    /**
     * @param   string  $sStr
     * @return  bool
     */
    public function strIsInt($sStr)
    {
        $iVar = intval($sStr);
        return("$sStr" == "$iVar");
    }

    /**
     * Creates the graph image
     * @param       string      $sSequence      Sequence to analyse
     * @param       int         $iPos           Beginning position of the sequence
     * @param       int         $iWindow        Window size
     * @param       int         $bAT            Show AT-Skew
     * @param       int         $bKETO          Show KETO-Skew
     * @param       int         $bGmC           Show G+C%
     * @param       int         $iSeqLength     Length of the sequence
     * @param       int         $iPeriod        Period
     * @param       array       $aAT            Data for AT
     * @param       array       $aGC            Data for GC
     * @param       array       $aGmC           Data for GmC
     * @param       array       $aKETO          Data for KETO
     * @return      int
     * @throws      \Exception
     */
    public function computeImage($sSequence, &$iPos, $iWindow, $bAT, $bKETO, $bGmC, $iSeqLength, $iPeriod, &$aAT, &$aGC, &$aGmC, &$aKETO)
    {
        try {
            // computes data for GC, AT, KETO and G+C skews (if requested)
            while($iPos < $iSeqLength - $iWindow) {
                $sSubSequence = substr($sSequence, $iPos, $iWindow);
                $iA = substr_count($sSubSequence,"A");
                $iC = substr_count($sSubSequence,"C");
                $iG = substr_count($sSubSequence,"G");
                $iT = substr_count($sSubSequence,"T");
                $aGC[$iPos] = ($iG-$iC) / ($iG+$iC);
                if($bAT) {
                    $aAT[$iPos] = ($iA-$iT) / ($iA+$iT);
                }
                if($bKETO) {
                    $aKETO[$iPos] = round(($iG+$iC-$iA-$iT) / ($iA+$iC+$iG+$iT),4);
                }
                if($bGmC) {
                    $aGmC[$iPos] = ($iG+$iC)/($iA+$iC+$iG+$iT);
                }
                $iPos += $iPeriod;
            }

            // scale related variables
            $fMax = max(max($aAT), max($aGC), max($aKETO));
            $fMin = min(min($aAT), min($aGC), min($aKETO));
            $fNmax = max($fMax, -$fMin);
            return $fNmax;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Creates the image based in data provided
     * @param       string      $sSequence      Sequence to analyse
     * @param       int         $iWindow        Window size
     * @param       int         $bGC            Show GC
     * @param       int         $bAT            Show AT-Skew
     * @param       int         $bKETO          Show KETO-Skew
     * @param       int         $bGmC           Show G+C%
     * @param       array       $aOligoSkew     Datas
     * @param       int         $iOlen          Length of the oligos
     * @param       int         $iFrom          Beginning of the sequence to analyse
     * @param       int         $iTo            End of the sequence to analyse
     * @param       string      $sName          Name of the sequence
     * @return      string
     * @throws      \Exception
     */
    public function createImage($sSequence, $iWindow, $bGC, $bAT, $bKETO, $bGmC, $aOligoSkew, $iOlen, $iFrom, $iTo, $sName)
    {
        try {
            $iPos = 0;
            $iLenSeq = strlen($sSequence);
            $iPeriod = ceil($iLenSeq / 6000);
            $aAT = $aGC = $aGmC = $aKETO = [null];

            $fNmax = $this->computeImage($sSequence, $iPos, $iWindow, $bAT, $bKETO, $bGmC, $iLenSeq, $iPeriod, $aAT, $aGC, $aGmC, $aKETO);
            $fRectify = round(200 / $fNmax);

            // starts the image
            $oIm                = new SvgCanvas(850, 450);
            $sBackgroundColor   = SvgCanvas::rgb(255, 255, 255);
            $sBlack             = SvgCanvas::rgb(0, 0, 0);
            $sQblack2           = SvgCanvas::rgb(228, 228, 228);
            $sQblack            = SvgCanvas::rgb(192, 192, 192);
            $sRed               = SvgCanvas::rgb(255, 0, 0);
            $sBlue              = SvgCanvas::rgb(0, 0, 255);
            $sGreen             = SvgCanvas::rgb(0, 255, 0);
            $sRb                = SvgCanvas::rgb(255, 0, 255);
            $sGb                = SvgCanvas::rgb(0, 150,150);

            $oIm->background($sBackgroundColor);

            $oIm->text(2, 610, 432,  "by biophp.org", $sBlack);
            $oIm->text(3, 600, 5,  "Window: $iWindow", $sBlack);

            // writes length of sequence
            if ($iFrom != "" || $iTo != "") {
                if($iFrom == "") {
                    $iFrom = 0;
                }
                if($iTo == "") {
                    $iTo = $iLenSeq;
                }
                $oIm->text(3, 5, 432, "Length of $sName: $iLenSeq (from position $iFrom to $iTo)", $sBlack);
            } else {
                $oIm->text(3, 5, 432, "Length of $sName: $iLenSeq", $sBlack);
            }

            $this->writeSkews($oIm, $bGC, $bAT, $bKETO, $bGmC, $aOligoSkew, $sBlue, $sRed, $sGreen, $sBlack, $iOlen, $sGb);
            $this->printScales($oIm, $aOligoSkew, $bAT, $bGC, $bGmC, $bKETO, $sRed, $sBlack, $sGb, $fNmax);

            // print oligo-skew
            // oligo-skews must be the first one to be printed out
            $fXp = ($iWindow * 700) / (2 * $iLenSeq);
            if(sizeof($aOligoSkew) > 10) {
                foreach($aOligoSkew as $iPos => $fVal) {
                    $fX = round(($iPos * 700 / $iLenSeq) + $fXp);
                    $oIm->line($fX, 20, $fX, 19 + (500 * $fVal), $sQblack2);
                    $oIm->pixel($fX, 20 + (500 * $fVal), $sGb);
                }
            }
            // print AT, GC and/or KETO-skews
            // each one with its color; the points of a same series are gathered so
            // that each curve costs one path instead of one element per sample
            $aAtPoints = $aGcPoints = $aKetoPoints = $aGmcPoints = [];
            foreach ($aGC as $iPos => $fVal) {
                $fX = round(( $iPos * 700 / $iLenSeq) + $fXp);
                if($bAT) {
                    $aAtPoints[] = [$fX, 220 - $aAT[$iPos] * $fRectify];
                }
                if($bGC) {
                    $aGcPoints[] = [$fX, 220 - $fVal * $fRectify];
                }
                if($bKETO) {
                    $aKetoPoints[] = [$fX, 220 - $aKETO[$iPos] * $fRectify];
                }
                if($bGmC) {
                    $aGmcPoints[] = [$fX, 470 - (500 * $aGmC[$iPos])];
                }
            }
            $oIm->pixelCloud($aAtPoints, $sRed);
            $oIm->pixelCloud($aGcPoints, $sBlue);
            $oIm->pixelCloud($aKetoPoints, $sGreen);
            $oIm->pixelCloud($aGmcPoints, $sBlack);

            // write some aditional lines
            for($i = 20; $i < 421; $i += 50) {
                $oIm->line(0,$i,700,$i,$sBlack);
            }

            $aIntervals = [70, 140, 210, 280, 350, 420, 490, 560, 630];
            foreach($aIntervals as $iInterval) {
                $oIm->line($iInterval, 20, $iInterval, 420, $sQblack);
            }
            $oIm->line(700, 20, 700, 420, $sBlack);

            // output the image to a file
            return $oIm->save($this->buildTargetPath($sName));
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Write the kind of skews in proper color
     * @param       SvgCanvas    $oIm            The drawing being built
     * @param       int         $bGC            Show GC
     * @param       int         $bAT            Show AT-Skew
     * @param       int         $bKETO          Show KETO-Skew
     * @param       int         $bGmC           Show G+C%
     * @param       array       $aOligoSkew     Datas
     * @param       int         $iBlue          Shade of blue
     * @param       int         $iRed           Shade of red
     * @param       int         $iGreen         Shade of green
     * @param       int         $iBlack         Shade of black
     * @param       int         $iOlen          Length of the oligos
     * @param       int         $iGb            Shade of grey-blue
     * @return      int
     * @throws      \Exception
     */
    private function writeSkews(&$oIm, $bGC, $bAT, $bKETO, $bGmC, $aOligoSkew, $iBlue, $iRed, $iGreen, $iBlack, $iOlen, $iGb)
    {
        try {
            $iGoright = 0;
            if ($bGC) {
                $oIm->text(3, 5 + $iGoright, 5, "GC-skew", $iBlue);
                $iGoright = 70;
            }
            if ($bAT) {
                $oIm->text(3, 5 + $iGoright, 5, "AT-skew", $iRed);
                $iGoright += 70;
            }
            if ($bKETO) {
                $oIm->text(3, 5 + $iGoright, 5, "KETO-skew", $iGreen);
                $iGoright += 80;
            }
            if ($bGmC) {
                $oIm->text(3, 5 + $iGoright, 5, "G+C", $iBlack);
                $iGoright += 60;
            }
            if (sizeof($aOligoSkew) > 10) {
                $oIm->text(3, 5 + $iGoright, 5, "oligo-skew ($iOlen)", $iGb);
            }
            return $iGoright;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Print scale for AT, GC or KETO skews + GC Skews + oligo-skew
     * @param       SvgCanvas    $oIm            The drawing being built
     * @param       array       $aOligoSkew     Datas
     * @param       int         $bGC            Show GC
     * @param       int         $bAT            Show AT-Skew
     * @param       int         $bKETO          Show KETO-Skew
     * @param       int         $bGmC           Show G+C%
     * @param       int         $iRed           Shade of red
     * @param       int         $iGb            Shade of grey-blue
     * @param       int         $iBlack         Shade of black
     * @param       int         $iNmax
     * @throws      \Exception
     */
    private function printScales(&$oIm, $aOligoSkew, $bAT, $bGC, $bGmC, $bKETO, $iRed, $iBlack, $iGb, $iNmax)
    {
        try {
            $iNe = 0;
            if ($bAT || $bGC || $bKETO) {
                $oIm->text(3, 710, 210, "0", $iRed);
                $fScale = round($iNmax * 0.25,3);
                $fV = $fScale * 3;
                $oIm->text(3, 710, 60, $fV, $iRed);
                $oIm->text(3, 710, 360, -$fV, $iRed);
                $fV = $fScale * 2;
                $oIm->text(3, 710, 110, $fV, $iRed);
                $oIm->text(3, 710, 310, -$fV, $iRed);
                $fV = $fScale;
                $oIm->text(3, 710, 160, $fV, $iRed);
                $oIm->text(3, 710, 260, -$fV, $iRed);
                $iNe = 60;
            }
            // print scale for G+C skew
            if($bGmC == 1) {
                $iKkk = 360;
                for($i = 20; $i < 81; $i += 10) {
                    $oIm->text(3, 710+$iNe, $iKkk, "$i%", $iBlack);
                    $iKkk -= 50;
                }
                if($iNe == 60) {
                    for($i = 20; $i < 421; $i += 50) {
                        $oIm->line(698 + $iNe, $i, 703+$iNe, $i, $iBlack);
                    }
                    $oIm->line(764,20,764,420,$iBlack);
                }
                $iNe += 60;
            }
            // print scale for oligo-skew
            if(sizeof($aOligoSkew) > 10) {
                $iKkk = 15;
                for($i = 0; $i < 9; $i ++) {
                    $oIm->text(3, 710+$iNe, $iKkk, "0.$i", $iGb);
                    $iKkk += 50;
                }
                if($iNe > 0) {
                    for($i = 20; $i < 421; $i += 50) {
                        $oIm->line(698 + $iNe, $i, 703+$iNe, $i, $iBlack);
                    }
                    $oIm->line(704+$iNe, 20, 704 + $iNe, 420, $iBlack);
                }
            }
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }
}