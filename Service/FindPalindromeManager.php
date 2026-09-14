<?php
/**
 * FindPalindromeManager
 * Inspired by BioPHP's project biophp.org
 * Created 26 february 2019
 * Last modified 14 september 2026
 */
namespace Amelaye\BioTools\Service;

use Amelaye\BioPHP\Domain\Sequence\Traits\SequenceTrait;

/**
 * Class FindPalindromeManager
 * @package BioTools\Service
 * @author Amélie DUVERNET akka Amelaye <amelieonline@gmail.com>
 */
class FindPalindromeManager
{
    use SequenceTrait;

    /**
     * Searches sequence for palindromic substrings
     * @param   string  $sSequence      is the sequence to be searched
     * @param   int     $iMin           the minimum length of palindromic sequence to be searched
     * @param   int     $iMax           the maximum length of palindromic sequence to be searched
     * @return  array                   keys are positions in genome, and values are length of palindromic sequences
     * @throws \Exception
     */
    public function findPalindromicSeqs($sSequence, $iMin, $iMax)
    {
        if (!is_string($sSequence)) {
            throw new \Exception('The sequence must be a string.');
        }
        try {
            $aResults = [];
            $iSeqLen = strlen($sSequence);
            for($i = 0; $i < $iSeqLen-$iMin+1; $i++) {
                $iJ = $iMin;
                while($iJ < $iMax+1 && ($i+$iJ) <= $iSeqLen) {
                    $sSubSeq = substr($sSequence, $i, $iJ);
                    if ($this->dnaIsPalindrome($sSubSeq) == 1) {
                        $aResults[$i] = $sSubSeq;
                    }
                    $iJ++;
                }

            }
            return $aResults;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }


    /**
     * Checks whether a DNA sequence is palindromic.
     * When degenerate nucleotides are included in the sequence to be searched,
     * sequences as "AANTT" will be considered palindromic.
     * @param   string      $sSequence      is the sequence to be searched
     * @return  bool
     * @throws \Exception
     */
    public function dnaIsPalindrome($sSequence)
    {
        if (!is_string($sSequence)) {
            throw new \Exception('The sequence must be a string.');
        }
        try {
            if ($sSequence == $this->revCompDNA($sSequence)) {
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }
}