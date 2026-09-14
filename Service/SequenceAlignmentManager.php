<?php
/**
 * Inspired by BioPHP's project biophp.org
 * Created 28 february 2019
 * Last modified 14 september 2026
 * RIP Pasha, gone 27 february 2019 =^._.^= ∫
 */
namespace Amelaye\BioTools\Service;

use Amelaye\BioPHP\Api\Interfaces\Pam250MatrixDigitApiAdapter;

/**
 * Class SequenceAlignmentManager : Sequence Alignment Functions
 * @package BioTools\Service
 * @author Amélie DUVERNET aka Amelaye <amelieonline@gmail.com>
 */
class SequenceAlignmentManager
{
    /**
     * @var array
     */
    private $aPam250Matrix;

    /**
     * SequenceAlignmentManager constructor.
     * @param  Pam250MatrixDigitApiAdapter  $oPam250MatrixDigitApi
     */
    public function __construct(Pam250MatrixDigitApiAdapter $oPam250MatrixDigitApi)
    {
        $this->aPam250Matrix = $oPam250MatrixDigitApi::GetPam250MatrixArray($oPam250MatrixDigitApi->getPam250Matrix());
    }

    /**
     * First step before generate ADN matrix
     * @param       array       $aTempMatrix    Temporary matrix array
     * @param       int         $iMj
     * @param       int         $iMi
     * @param       int         $iMaxA
     * @param       int         $iMaxB
     * @param       array       $aSequenceA
     * @param       array       $aSequenceB
     * @param       int         $iMatch
     * @return      int
     * @throws      \Exception
     */
    public function step1(&$aTempMatrix, &$iMj, &$iMi, $iMaxA, $iMaxB, $aSequenceA, $aSequenceB, $iMatch)
    {
        try {
            $iMx = 0;
            for($i = 0; $i < $iMaxA; $i ++) {
                for ($j = 0; $j < $iMaxB; $j++) {
                    if($aSequenceB[$j] == $aSequenceA[$i]) {
                        $iX = (!isset($aTempMatrix[$j-1][$i - 1])) ? (0 + $iMatch) : ($aTempMatrix[$j - 1][$i - 1] + $iMatch);
                    } else {
                        $iValue2 = isset($aTempMatrix[$j-1][$i-1]) ? $aTempMatrix[$j-1][$i-1] - 1 : -1;
                        $iValue3 = isset($aTempMatrix[$j][$i-1]) ? $aTempMatrix[$j][$i-1] - 4 : -4;
                        $iValue4 = isset($aTempMatrix[$j-1][$i]) ? $aTempMatrix[$j-1][$i] - 4 : -4;
                        $iX = max(0, $iValue2, $iValue3, $iValue4);
                    }
                    $aTempMatrix[$j][$i] = $iX;
                    if ($iMx < $iX) {
                        $iMx = $iX;
                        $iMj = $j;
                        $iMi = $i;
                    }
                }
            }
            return $iMx;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * First step before generate protein matrix
     * @param       array       $aMatrix
     * @param       int         $iMj
     * @param       int         $iMi
     * @param       int         $iMaxA
     * @param       int         $iMaxB
     * @param       array       $aSequenceA
     * @param       array       $aSequenceB
     * @return      int
     * @throws      \Exception
     */
    public function step1Protein(&$aMatrix, &$iMj, &$iMi, $iMaxA, $iMaxB, $aSequenceA, $aSequenceB)
    {
        try {
            $aPam250 = $this->aPam250Matrix;
            $iGap = -50;

            for($i = 0; $i < $iMaxA; $i++) {
                $aMatrix[0][$i] = $aPam250["$aSequenceA[$i]$aSequenceB[0]"];
            }
            for($i = 0; $i < $iMaxB; $i++){
                $aMatrix[$i][0] = $aPam250["$aSequenceB[$i]$aSequenceA[0]"];
            }
            for($i = 1; $i < $iMaxA; $i++) {
                for($j = 1; $j < $iMaxB; $j++) {
                    if($aSequenceB[$j] == $aSequenceA[$i]) {
                        $iX = $aMatrix[$j-1][$i-1] + $aPam250["$aSequenceB[$j]$aSequenceA[$i]"];//$x=$matriz[$j-1][$i-1]+$match;
                    } else {
                        $iX = $aMatrix[$j-1][$i-1] + $aPam250["$aSequenceB[$j]$aSequenceA[$i]"];//$x=$matriz[$j-1][$i-1]+$mismatch;
                        $iY = $aMatrix[$j][$i-1] + $iGap;
                        if($iY > $iX) {
                            $iX = $iY;
                        }
                        $iY = $aMatrix[$j-1][$i] + $iGap;
                        if($iY > $iX) {
                            $iX = $iY;
                        }
                        if($iX < 0) {
                            $iX = 0;
                        }
                    }
                    $aMatrix[$j][$i] = $iX;
                    $iX = 0;
                } // end for $j
            }
            $iMx = 0;
            for($i = 0; $i < $iMaxA; $i++) {
                for ($j = 0; $j < $iMaxB; $j++) {
                    if($iMx < $aMatrix[$j][$i]) {
                        $iMx = $aMatrix[$j][$i];
                        $iMj = $j;
                        $iMi = $i;
                    }
                }
            }
            return $iMx;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * First step of the matrix array filling
     * @param       array       $aTempMatrix    Temporary matrix array
     * @param       int         $j
     * @param       int         $i
     * @return      array
     * @throws      \Exception
     */
    public function fillMatrix($aTempMatrix, $j, $i)
    {
        try {
            $aMyMatrix[$j][$i] = 1;                         // matrixx(n, m) = 1

            while ($i > 0 || $j > 0) {
                $iAa = $aTempMatrix[$j-1][$i-1]  ?? 0;       // a = matrix(n - 1, m - 1)
                $iAb = $aTempMatrix[$j][$i-1]    ?? 0;       // b = matrix(n, m - 1)
                $iAc = $aTempMatrix[$j-1][$i]    ?? 0;       // c = matrix(n - 1, m)
                if($iAa != '//' || $iAa == 0) {               // If a <> "" Then
                    if($iAa >= $iAb && $iAa >= $iAc) {          // If a >= b And a >= c Then
                        $j = $j - 1;                        // n = n - 1: m = m - 1
                        $i = $i - 1;
                    }
                    if($iAb > $iAa) {                         // If b > a Then m = m - 1
                        $i = $i - 1;
                    }
                    if($iAc > $iAa) {                         // If c > a Then n = n - 1
                        $j = $j - 1;
                    }
                } else {                                    // If a = "" Then
                    if($iAb != '//' || $iAb == 0) {           // If b <> "" Then m = m - 1
                        $i = $i - 1;
                    }
                    if($iAc != '//' || $iAc == 0) {           // If c <> "" Then n = n - 1
                        $j = $j - 1;
                    }
                }
                if($j < 0) {                                // If n = 0 Then n = 1
                    $j = 0;
                }
                if($i < 0) {                                // If m = 0 Then m = 1
                    $i = 0;
                }
                $aMyMatrix[$j][$i] = 1;                     // matrixx(n, m) = 1
            }
            return $aMyMatrix;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }


    /**
     * Second step to fill the matrix
     * @param       array       $aMyMatrix      Matrix array to fill
     * @param       int         $i              Size of the first sequence
     * @param       int         $j              Size of the second sequence
     * @param       array       $aTempMatrix    Temporary matrix array
     * @param       string      $sSequenceA     First sequence
     * @param       string      $sSequenceB     Second sequence
     * @param       int         $iLength
     * @return      array
     * @throws      \Exception
     */
    public function fillMatrix2($aMyMatrix, $i, $j, $aTempMatrix, $sSequenceA, $sSequenceB, $iLength)
    {
        try {
            while($i < strlen($sSequenceA) - 1 || $j < strlen($sSequenceB) - 1) {
                $iAa = $aTempMatrix[$j+1][$i+1]   ?? 0;                  //a = matrix(n + 1, m + 1)
                $iAb = $aTempMatrix[$j][$i+1]     ?? 0;                  //b = matrix(n, m + 1)
                $iAc = $aTempMatrix[$j+1][$i]     ?? 0;                  //c = matrix(n + 1, m)
                if($iAa != '//' || $iAa == 0) {                           //If a <> "" Then
                    if($iAa >= $iAb && $iAa >= $iAc) {                      //If a >= b And a >= c Then
                        $j = $j + 1;                                    // n = n - 1: m = m - 1
                        $i = $i + 1;
                    }
                    if($iAb > $iAa) {                                     //If b > a Then m = m - 1
                        $i = $i + 1;
                    }
                    if($iAc > $iAa) {                                     //If c > a Then n = n - 1
                        $j = $j + 1;
                    }
                } else {                                                //If a = "" Then
                    if($iAb != '//' or $iAb == 0) {                       //    If b <> "" Then m = m - 1
                        $i = $i + 1;
                    }
                    if($iAc != '//' or $iAc == 0) {                       //    If c <> "" Then n = n - 1
                        $j = $j + 1;
                    }
                }
                if($j > $iLength) {                                     //If n > lenn Then n = lenn
                    $j = $iLength;
                }
                if($i > $iLength) {                                     //If m > lenn Then m = lenn
                    $i = $iLength;
                }
                $aMyMatrix[$j][$i] = 1;                                 //matrixx(n, m) = 1
            }
            return $aMyMatrix;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * We get the last letter of the amino, his opposite, repeat the last position
     * @param       array       $aMatrix
     * @param       string      $sSequenceA
     * @param       string      $sSequenceB
     * @param       array       $aSequenceA
     * @param       array       $aSequenceB
     * @param       int         $iMaxA
     * @param       int         $iMaxB
     * @return      mixed
     * @throws      \Exception
     */
    public function generateResults($aMatrix, $sSequenceA, $sSequenceB, $aSequenceA, $aSequenceB, $iMaxA, $iMaxB, $bIsProt)
    {
        try {
            $i = $j = 0;
            $iT = 1;

            $sSeqa = $sSeqb = "";

            while ($i < strlen($sSequenceA) - 2 && $j < strlen($sSequenceB) - 2 && $iT = 1) {
                $iT = 0;
                if(isset($aMatrix[$j+1][$i+1]) && $aMatrix[$j+1][$i+1] == 1) {
                    $iT = 1;
                    $sSeqa .= $aSequenceA[$i];
                    $sSeqb .= $aSequenceB[$j];
                    $i = $i+1;
                    $j = $j+1;
                }
                if(isset($aMatrix[$j][$i+1]) && $aMatrix[$j][$i+1] == 1) {
                    $iT = 1;
                    $sSeqa .= $aSequenceA[$i];
                    $sSeqb .= "-";
                    $i = $i+1;
                }
                if(isset($aMatrix[$j+1][$i]) && $aMatrix[$j+1][$i] == 1) {
                    $iT = 1;
                    $sSeqa .= "-";
                    $sSeqb .= $aSequenceB[$j];
                    $j = $j+1;
                }
            }

            if($aMatrix[$j+1][$i+1] == 1) {
                $iT = 1;
                $sSeqa .= $aSequenceA[$i];
                $sSeqb .= $aSequenceB[$j];
                $i = $i+1;
                $j = $j+1;
            }
            if($iT == 0 && $aMatrix[$j][$i+1] == 1) {
                $sSeqa .= $aSequenceA[$i];
                $sSeqb .= "-";
                $i = $i+1;
            }
            if($iT == 0 && $aMatrix[$j+1][$i] == 1) {
                $sSeqa .= "-";
                $sSeqb .= $aSequenceB[$j];
                $j = $j+1;
            }
            if($i+1 == $iMaxA) {
                for($iCounter = $j; $iCounter < $iMaxB; $iCounter++) {
                    $sSeqb .= $aSequenceB[$iCounter];
                }
                $sSeqa .= $aSequenceA[$i];
                for($iCounter = $j; $iCounter < $iMaxB-1; $iCounter++) {
                    $sSeqa .= "-";
                }
            }
            if($j+1 == $iMaxB) {
                for($iCounter = $i; $iCounter < $iMaxA; $iCounter++) {
                    $sSeqa .= $aSequenceA[$iCounter];
                }
                $sSeqb .= $aSequenceB[$j];
                for($iCounter = $i; $iCounter < $iMaxA-1; $iCounter++) {
                    $sSeqb .= "-";
                }
            }

            if(!$bIsProt) { // Case DNA
                $aResults["seqa"] = substr($sSeqa,0,strlen($sSeqa)-1);
                $aResults["seqb"] = substr($sSeqb,0,strlen($sSeqb)-1);
            } else { // Case protein
                $aResults["seqa"] = $sSeqa;
                $aResults["seqb"] = $sSeqb;
            }

            return $aResults;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Matrix Creation
     * That reduces the code to make it more simple and fast, but lag of 20%
     * With bigger matrixes, PHP can have a timeout error
     * @param       string      $sSequenceA     First sequence to analyse
     * @param       string      $sSequenceB     Second sequence to analyse
     * @return      array
     * @throws      \Exception
     */
    public function alignDNA($sSequenceA, $sSequenceB)
    {
        try {
            $iMatch = 2;
            $aMatrix = array();
            $j = $i = 0;

            $aSequenceA = preg_split('//', $sSequenceA, -1, PREG_SPLIT_NO_EMPTY);
            $aSequenceB = preg_split('//', $sSequenceB, -1, PREG_SPLIT_NO_EMPTY);
            $iMaxA = sizeof($aSequenceA);
            $iMaxB = sizeof($aSequenceB);
            $iLength = max($iMaxA, $iMaxB);

            $iX = $this->step1($aMatrix, $j, $i, $iMaxA, $iMaxB, $aSequenceA, $aSequenceB, $iMatch); // Matrix created
            $aMyMatrix = $this->fillMatrix($aMatrix, $j, $i);
            $aMyMatrix = $this->fillMatrix2($aMyMatrix, $i, $j, $aMatrix, $sSequenceA, $sSequenceB, $iLength);
            $aResults = $this->generateResults($aMyMatrix, $sSequenceA, $sSequenceB, $aSequenceA, $aSequenceB, $iMaxA, $iMaxB, 0);

            return $aResults;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }


    /**
     * Matrix creation for Protein
     * @param       string      $sSequenceA
     * @param       string      $sSequenceB
     * @return      array
     * @throws      \Exception
     */
    public function alignProteins($sSequenceA, $sSequenceB)
    {
        try {
            $aMatrix = array();
            $j = $i = 0;

            $aSequenceA = preg_split('//', $sSequenceA, -1, PREG_SPLIT_NO_EMPTY);
            $aSequenceB = preg_split('//', $sSequenceB, -1, PREG_SPLIT_NO_EMPTY);
            $iMaxA = sizeof($aSequenceA);
            $iMaxB = sizeof($aSequenceB);

            $iLength = $iMaxA;
            if($iMaxB > $iLength) {
                $iLength = $iMaxB;
            }

            $mx = $this->step1Protein($aMatrix, $j, $i, $iMaxA, $iMaxB, $aSequenceA, $aSequenceB);
            $aMyMatrix = $this->fillMatrix($aMatrix, $j, $i);
            $aMyMatrix = $this->fillMatrix2($aMyMatrix, $i, $j, $aMatrix, $sSequenceA, $sSequenceB, $iLength);
            $aResults = $this->generateResults($aMyMatrix, $sSequenceA, $sSequenceB, $aSequenceA, $aSequenceB, $iMaxA, $iMaxB, 1);
            return $aResults;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Also exposed as the `compare_alignment` Twig filter, see
     * Amelaye\BioTools\Twig\BioToolsExtension.
     * @param   string  $sSeqa
     * @param   string  $sSeqb
     * @return  string
     */
    public function compareAlignment($sSeqa,$sSeqb)
    {
        $sCompare = "";
        for($i = 0; $i < strlen($sSeqa); $i++) {
            if(substr($sSeqa,$i,1) == substr($sSeqb,$i,1)) {
                $sCompare .= "|";
            } else {
                $sCompare.=" ";
            }
        }
        return $sCompare;
    }
}