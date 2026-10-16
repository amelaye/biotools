<?php
/**
 * Skews Functions
 * Inspired by BioPHP's project biophp.org
 * Created 1st march 2019
 * Last modified 16 october 2026
 * RIP Pasha, gone 27 february 2019 =^._.^= ∫
 */
namespace Amelaye\BioTools\Service;

use Amelaye\BioPHP\Domain\Tools\Interfaces\OligosInterface;
use Amelaye\BioPHP\Domain\Tools\Interfaces\SkewCalculatorInterface;
use Amelaye\BioPHP\Domain\Tools\Service\MathematicsFunctions;
use Amelaye\BioPHP\Domain\Tools\Service\SkewCalculator;
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

    /**
     * Distances between the oligonucleotide frequencies of a window and of the whole sequence
     */
    public const DISTANCE_PEARSON = 'pearson';
    public const DISTANCE_ALMEIDA = 'almeida';

    private $oligosManger;

    /**
     * @var array   Geometry and output directory of the generated graphics
     */
    private $aNucleotidsGraphs;

    /**
     * @var SkewCalculatorInterface
     */
    private $skewCalculator;

    /**
     * @param   array                           $aNucleotidsGraphs
     * @param   OligosInterface                 $oligosManger
     * @param   SkewCalculatorInterface|null    $skewCalculator     Defaults to BioPHP's SkewCalculator
     */
    public function __construct(
        array $aNucleotidsGraphs,
        OligosInterface $oligosManger,
        ?SkewCalculatorInterface $skewCalculator = null
    ) {
        $this->aNucleotidsGraphs = $aNucleotidsGraphs;
        $this->oligosManger = $oligosManger;
        $this->skewCalculator = $skewCalculator ?? new SkewCalculator();
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
     * with distances: by default the Pearson distance (1 - r), as the legacy minitool did (which
     * correlated the 136 classes of the 256 tetranucleotides folded with their reverse complements;
     * here the 256 words are correlated, so the numbers are close but not identical), or on
     * request the distance of Almeida et al, 2001 (a Pearson correlation weighted by x * y).
     * The Almeida distance weights the words by their frequency in both series, which Pearson does
     * not: on a sequence whose two strands differ in composition it reshapes the curve instead of
     * only shifting it.
     * With two strands, the window and the whole sequence are both counted on the forward
     * strand plus its reverse complement, so that the distance measures the local change of
     * composition and not the asymmetry between the two strands.
     * @param       string      $sSequence      The sequence to analyze
     * @param       int         $iWindow        The size of the window
     * @param       int         $iOskew         Number of nucleos
     * @param       int         $iStrands       One or both strands. With one oligonucleotide length of 1 and both
     *                                          strands, A = T and C = G: the distance only says on which side of
     *                                          the AT / GC balance of the sequence the window is (0 or 2)
     * @param       string      $sDistance      self::DISTANCE_PEARSON (default) or self::DISTANCE_ALMEIDA
     * @return      array
     * @throws      \InvalidArgumentException  When the distance is neither of them
     * @throws      \Exception
     */
    public function oligoSkewArrayCalculation($sSequence, $iWindow, $iOskew, $iStrands, $sDistance = self::DISTANCE_PEARSON)
    {
        if (!in_array($sDistance, [self::DISTANCE_PEARSON, self::DISTANCE_ALMEIDA], true)) {
            throw new \InvalidArgumentException(sprintf('Unknown distance "%s".', $sDistance));
        }
        try {
            $aDistances = [];

            // search for oligos in the complet sequence; with two strands the reference is
            // symmetrised the same way as the windows
            $sReference = $iStrands == 2
                ? $sSequence . " " . $this->revCompDNA($sSequence)
                : $sSequence;
            $aOligosX = $this->oligosManger->findOligos($sReference, $iOskew);
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
                    $aDistances[$i] = $this->windowDistance($aOligosX, $aOligosY, $sDistance);
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
                    $aDistances[$i] = $this->windowDistance($aOligosX, $aOligosY, $sDistance);
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
        return MathematicsFunctions::AlmeidaDistance($aValsX, $aValsY);
    }

    /**
     * Distance of a window to the whole sequence, or null when it is undefined (a window with no
     * oligonucleotide, e.g. only N, has no variance): such a point is left out of the graph.
     * @param       array       $aValsX     Values for the whole sequence
     * @param       array       $aValsY     Values for the window
     * @param       string      $sDistance  self::DISTANCE_PEARSON or self::DISTANCE_ALMEIDA
     * @return      float|null
     */
    private function windowDistance($aValsX, $aValsY, $sDistance)
    {
        try {
            $fDistance = $sDistance === self::DISTANCE_ALMEIDA
                ? $this->distance($aValsX, $aValsY)
                : $this->pearsonDistance($aValsX, $aValsY);
        } catch (\InvalidArgumentException | \DivisionByZeroError $e) {
            return null;
        }

        return is_numeric($fDistance) && is_finite($fDistance) ? $fDistance : null;
    }

    /**
     * Computes the standard Pearson distance between two arrays of values, used to
     * compare oligonucleotide frequencies
     * @param       array       $aValsX     Values for X
     * @param       array       $aValsY     Values for Y
     * @return      float|void
     */
    public function pearsonDistance($aValsX, $aValsY)
    {
        if(sizeof($aValsX) != sizeof($aValsY)) {
            return;
        }
        return MathematicsFunctions::PearsonDistance($aValsX, $aValsY);
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
                $oSkew = $this->skewCalculator->calculate($sSubSequence);
                $aGC[$iPos] = $oSkew->getGcSkew();
                if($bAT) {
                    $aAT[$iPos] = $oSkew->getAtSkew();
                }
                if($bKETO) {
                    $aKETO[$iPos] = round($oSkew->getKetoSkew(),4);
                }
                if($bGmC) {
                    $aGmC[$iPos] = $oSkew->getGcContent();
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
            // flat curves (every skew null) have no scale of their own: use a default one
            if ($fNmax == 0) {
                $fNmax = 1;
            }
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
            // the oligo-skew axis runs from 0 to 0.8 (500 px per unit over the 400 px of the graph); a distance
            // that goes beyond it (Almeida, or Pearson on very different strands, up to 2) widens it by
            // steps of 0.8, so that the points stay in the graph
            $iAxisSteps = $this->oligoSkewAxisSteps($aOligoSkew);
            $fPxPerUnit = 500 / $iAxisSteps;
            $this->printScales($oIm, $aOligoSkew, $bAT, $bGC, $bGmC, $bKETO, $sRed, $sBlack, $sGb, $fNmax, $iAxisSteps);

            // print oligo-skew
            // oligo-skews must be the first one to be printed out
            $fXp = ($iWindow * 700) / (2 * $iLenSeq);
            if(sizeof($aOligoSkew) > 10) {
                foreach($aOligoSkew as $iPos => $fVal) {
                    if ($fVal === null) {
                        continue;
                    }
                    $fX = round(($iPos * 700 / $iLenSeq) + $fXp);
                    $oIm->line($fX, 20, $fX, 19 + ($fPxPerUnit * $fVal), $sQblack2);
                    $oIm->pixel($fX, 20 + ($fPxPerUnit * $fVal), $sGb);
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
     * How many times the default 0 to 0.8 axis of the oligo-skew has to be stretched to hold its values.
     * The axis therefore changes scale from one graph to another (0.8, 1.6, 2.4...): two graphs are not
     * comparable by eye, read the tick labels.
     * @param       array       $aOligoSkew     Distances, null where undefined
     * @return      int         1 when everything is under 0.8, else 2, 3...
     */
    private function oligoSkewAxisSteps($aOligoSkew) : int
    {
        $aValues = array_filter($aOligoSkew, function ($fVal) {
            return $fVal !== null;
        });
        if ([] === $aValues) {
            return 1;
        }

        return max(1, (int) ceil(round(max($aValues) / 0.8, 6)));
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
     * @param       int         $iAxisSteps     The oligo-skew axis goes to 0.8 times this
     * @throws      \Exception
     */
    private function printScales(&$oIm, $aOligoSkew, $bAT, $bGC, $bGmC, $bKETO, $iRed, $iBlack, $iGb, $iNmax, $iAxisSteps = 1)
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
                    $oIm->text(3, 710+$iNe, $iKkk, number_format($i * 0.1 * $iAxisSteps, 1, '.', ''), $iGb);
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