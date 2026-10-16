<?php
/**
 * FindPalindromeManager
 * Inspired by BioPHP's project biophp.org
 * Created 26 february 2019
 * Last modified 16 october 2026
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
     * The sequence is read in upper case without white space or digits, like legacy's
     * remove_useless_from_DNA(): the positions are those of that cleaned sequence. A lower case
     * "gaattc" is the EcoRI site, and a line break no longer joins two halves of a site.
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
            $sSequence = preg_replace("/\W|\d/", "", strtoupper($sSequence));
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
     * sequences as "AANTT" will be considered palindromic. The case does not matter.
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
            $sSequence = strtoupper($sSequence);
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