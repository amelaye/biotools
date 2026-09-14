<?php
/**
 * MicroarrayAnalysisAdaptive
 * Inspired by BioPHP's project biophp.org
 * Created 26 february 2019
 * Last modified 14 september 2026
 */
namespace Amelaye\BioTools\Service;

use Amelaye\BioPHP\Domain\Tools\Service\MathematicsFunctions;
use Exception;

/**
 * Class MicroarrayAnalysisAdaptiveManager
 * @package BioTools\Service
 * @author Amélie DUVERNET aka Amelaye <amelieonline@gmail.com>
 */
class MicroarrayAnalysisAdaptiveManager
{
    /**
     * Processes the Microarray data
     * @param       string      $sFile
     * @return      array
     * @throws      Exception
     */
    public function processMicroarrayDataAdaptiveQuantificationMethod($sFile)
    {
        if (!is_string($sFile)) {
            throw new \Exception('The microarray data must be a string.');
        }
        try {
            $aResults = [];

            $aData = $this->fileToArray($sFile);

            $iSumCh1 = 0;
            $iSumCh2 = 0;

            $aData2 = $this->createBackground($aData, $iSumCh1, $iSumCh2);
            $aData3 = $this->computeDataBackground($aData2, $iSumCh1, $iSumCh2);
            $aData4 = $this->computeRatios($aData3);
            ksort($aData4);

            if(!empty($aData4)) {
                foreach($aData4 as $sKey => $aVal) {
                    $aResults[$sKey]["n_data"] = count($aData4[$sKey][1]);
                    $aResults[$sKey]["median1"] = MathematicsFunctions::Median($aData4[$sKey][1]);
                    $aResults[$sKey]["medlog1"] = round(log10($aResults[$sKey]["median1"]),3);
                    $aResults[$sKey]["median2"] = MathematicsFunctions::Median($aData4[$sKey][2]);
                    $aResults[$sKey]["medlog2"] = round(log10($aResults[$sKey]["median2"]),3);
                }
            }

            return $aResults;
        } catch (Exception $e) {
            throw new Exception($e);
        }
    }

    /**
     * Parses the data and return array
     * @param       string      $sFile
     * @return      array
     * @throws      Exception
     */
    private function fileToArray($sFile)
    {
        try {
            // find data for first column and row, and remove all headings;
            $sFile = substr($sFile, strpos($sFile,"1\t1\t"));
            // remove from file returns (\r) and (\")
            $sFile = preg_replace("/\r|\"/","",$sFile);

            // split file into lines ($data_array)
            $aData = preg_split("/\n/",$sFile, -1, PREG_SPLIT_NO_EMPTY);

            return $aData;
        } catch (Exception $e) {
            throw new Exception($e);
        }
    }

    /**
     * Compute data-background (save result in $aData2) and
     * sum of all data-background ($iSumCh1 and $iSumCh2)
     * Example of line to be splitted:
     * 1        2        G16        1136        159        538        118
     * where        1 and 2 define position in the plate
     *              G16 is name of gene/experiment
     *              1136 is reading of chanel 1, and 159 is the background
     *              538 is reading of chanel 2, and 159 is the background
     * @param       array   $aData
     * @param       int     $iSumCh1
     * @param       int     $iSumCh2
     * @return      array
     * @throws      Exception
     */
    private function createBackground($aData, &$iSumCh1, &$iSumCh2)
    {
        try {
            $aData2 = [];
            if(!empty($aData)) {
                foreach($aData as $sKey => $aVal) {
                    $aLineElement = preg_split("/\t/",$aVal, -1, PREG_SPLIT_NO_EMPTY);
                    if (sizeof ($aLineElement) < 7) {
                        continue;
                    }
                    $sName = $aLineElement[2]; // This is the name of the gene studied

                    // For chanel 1
                    // calculate data obtained in chanel 1 minus background
                    $iCh1Bg = $aLineElement[3] - $aLineElement[4];
                    // save data to a element in $aData2 (separate different calculations from the same gene with commas)
                    $aData2[$sName][1][] = $iCh1Bg;
                    $iSumCh1 += $iCh1Bg; // $sum_ch1 will record the sum of all (chanel 1 - background) values

                    // For chanel 2
                    // calculate data obtained in chanel 2 minus background
                    $iCh2Bg = $aLineElement[5] - $aLineElement[6];
                    // save data to a element in $data_array2 (separate different calculations from the same gene with commas)
                    $aData2[$sName][2][] = $iCh2Bg;
                    $iSumCh2 += $iCh2Bg; // $sum_ch1 will record the sum of all (chanel 2 - background) values
                }
            }
            return $aData2;
        } catch (Exception $e) {
            throw new Exception($e);
        }
    }

    /**
     * Compute (data-background)*100/sum(data-background)),
     * where sum(data-background) is $iSumCh1 or $iSumCh2
     * and save data in $aData3
     * @param       array       $aData2
     * @param       int         $iSumCh1
     * @param       int         $iSumCh2
     * @return      array
     * @throws      Exception
     */
    private function computeDataBackground($aData2, $iSumCh1, $iSumCh2)
    {
        try {
            $aData3 = [];
            if(!empty($aData2)) {
                foreach($aData2 as $sKey => $aVal) {
                    // split data separated by comma (chanel 1)
                    foreach($aData2[$sKey][1] as $sKey2 => $fValue) {
                        $fRatio = $fValue * 100 / $iSumCh1; // compute ratios
                        $aData3[$sKey][1][] = $fRatio; // save result
                    }

                    // split data separated by comma (chanel 2)
                    foreach($aData2[$sKey][2] as $sKey2 => $fValue) {
                        $fRatio = $fValue * 100 / $iSumCh2; // compute ratios
                        $aData3[$sKey][2][] = $fRatio; // save result
                    }
                }
            }
            return $aData3;
        } catch (Exception $e) {
            throw new Exception($e);
        }
    }

    /**
     * Compute ratios for values in chanel 1 and chanel 2
     * chanel 1/chanel 2  and  chanel 2/chanel 1
     * save results to $aData4
     * @param       array       $aData3
     * @return      array
     * @throws      Exception
     */
    private function computeRatios($aData3)
    {
        try {
            $aData4 = [];
            foreach($aData3 as $sKey => $aVal) {
                foreach ($aData3[$sKey][1] as $sKey2 => $fValue) {
                    $fRatio = $aData3[$sKey][1][$sKey2] / $aData3[$sKey][2][$sKey2]; //compute ch1 / ch2
                    $aData4[$sKey][1][] = $fRatio; // and save
                    $fRatio = $aData3[$sKey][2][$sKey2] / $aData3[$sKey][1][$sKey2]; //compute ch2 / ch1
                    $aData4[$sKey][2][] = $fRatio; // and save
                }
            }
            return $aData4;
        } catch (Exception $e) {
            throw new Exception($e);
        }
    }
}