<?php
/**
 * Formulas Functions
 * Inspired by BioPHP's project biophp.org
 * Created 3 march 2019
 * Last modified 16 october 2026
 * RIP Pasha, gone 27 february 2019 =^._.^= ∫
 */
namespace Amelaye\BioTools\Service;

use Amelaye\BioPHP\Api\Interfaces\AminoApiAdapter;
use Amelaye\BioPHP\Api\Interfaces\NucleotidApiAdapter;

/**
 * Class RandomSequencesManager
 * @package BioTools\Service
 * @author Amélie DUVERNET aka Amelaye <amelieonline@gmail.com>
 */
class RandomSequencesManager
{
    /**
     * @var array
     */
    private $aAminos;

    /**
     * @var array
     */
    private $aProteins;

    /**
     * RandomSequencesManager constructor.
     * @param   NucleotidApiAdapter    $nucleotidApi
     * @param   AminoApiAdapter        $aminoApi
     */
    public function __construct(NucleotidApiAdapter $nucleotidApi, AminoApiAdapter $aminoApi)
    {
        $this->aAminos   = $nucleotidApi::GetDNAComplement($nucleotidApi->getNucleotids());
        $this->aProteins = $aminoApi::GetAminosOneLetterBasic($aminoApi->getAminos());
    }

    /**
     * Generate a random protein or DNA sequence
     * $a, $c, $g and $t are the number of nucleotides A, C, G or T
     * @param   array   $aElements
     * @return  string
     * @throws  \Exception
     */
    public function randomize(array $aElements) : string
    {
        try {
            $sElements = "";
            foreach($aElements as $sKey => $iElement) {
                $sElements .= str_repeat($sKey, $iElement);
            }
            return str_shuffle($sElements);
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Creates a random sequence
     * @param       int         $iLength        Length of the sequence
     * @param       string      $sSequence      Sequence
     * @return      string
     * @throws      \Exception
     */
    public function createFromSeq(int $iLength, string $sSequence) : string
    {
        try {
            if($iLength != null) {
                // remove from sequence characters different to ACGT.
                $sSeqACGT = preg_replace("/[^ACGT]/","", $sSequence);
                // The sequence is DNA if A+C+G+T>70% (so, if $sSeqACGT is long enough)
                if(strlen($sSeqACGT) > strlen($sSequence) * 0.7) {
                    $iAcgt = 0;
                    $aDNA = [];

                    // The sequence is DNA
                    // get the frequencies for each nucleotide
                    foreach($this->aAminos as $sAmino) {
                        $$sAmino = substr_count($sSequence,$sAmino);
                        $iAcgt += $$sAmino;
                    }

                    // Get number of ocurrences per each nucleotide for a seq with length=$length1
                    foreach($this->aAminos as $sAmino) {
                        $aDNA[$sAmino] = $$sAmino;
                    }
                    $aDNA = $this->countsForLength($aDNA, $iLength);

                    // get randomized sequence
                    $sResult = $this->randomize($aDNA);
                } else {
                    // The sequence is protein
                    $aProteins = [];
                    $iAcdefghiklmnpgrstvwy = 0;
                    $aListProteins = ["A", "C", "D", "E", "F", "G", "H", "I", "K", "L", "M", "N", "P", "Q", "R", "S", "T", "V", "W", "Y"];

                    // get the frequencies for each aminoacid
                    foreach($aListProteins as $sProtein) {
                        $$sProtein = substr_count($sSequence, $sProtein);
                        $iAcdefghiklmnpgrstvwy += $$sProtein;
                    }

                    // Get number of ocurrences per each nucleotide for a seq with length=$length1
                    foreach($aListProteins as $sProtein) {
                        $aProteins[$sProtein] = $$sProtein;
                    }
                    $aProteins = $this->countsForLength($aProteins, $iLength);

                    $sResult = $this->randomize($aProteins);
                }
            } else {
                // just shuffle the sequence when length is not provided
                $sResult = str_shuffle($sSequence);
            }
            return $sResult;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Creates a random sequence from ATGC combination
     * @param       array       $aAminoAcids    Array containing combinations of nucleotids
     * @param       int         $iLength        Length of the sequence
     * @return      string
     * @throws      \Exception
     */
    public function createFromACGT(array $aAminoAcids, int $iLength) : string
    {
        try {
            $aDNA = [];
            if ($iLength != null) {
                // in case length is specified
                $aDNA = $this->countsForLength($aAminoAcids, $iLength);
            } else {
                // in case length is not specified
                foreach($aAminoAcids as $sKey => $iData) {
                    $aDNA[$sKey] = round($iData);
                }
            }

            $sResult = $this->randomize($aDNA); // get randomized sequence
            return $sResult;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Creates a random sequence from ATGC combination
     * @param       array       $aAminoAcids    Array containing combinations of nucleotids
     * @param       int         $iLength        Length of the sequence
     * @return      string
     * @throws      \Exception
     */
    public function createFromAA(array $aAminoAcids, int $iLength) : string
    {
        try {
            $aProteins = [];

            // Get number of ocurrences per each aminoacid
            if ($iLength != null) {
                // in case length is specified
                $aProteins = $this->countsForLength($aAminoAcids, $iLength);

            } else {
                // in case length is not specified
                foreach($aAminoAcids as $sKey => $iData) {
                    $aProteins[$sKey] = round($iData);
                }
            }
            // get randomized sequence
            $sResult = $this->randomize($aProteins);
            return $sResult;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Number of each symbol in a sequence of $iLength, proportional to $aWeights, adding up to
     * $iLength exactly: each share is rounded down, then the bases left go to the largest remainders
     * (the first symbol on a tie). Rounding each share on its own, as legacy did, gave 4 bases for a
     * length of 3 with 29.5/29.5/20.5/20.5 %, and 12 for a length of 10 with four equal shares.
     * @param       array       $aWeights       Symbol => weight (a count or a percentage)
     * @param       int         $iLength
     * @return      array       Symbol => count
     */
    private function countsForLength(array $aWeights, int $iLength) : array
    {
        $fTotal = array_sum($aWeights);
        $aCounts = [];
        $aRemainders = [];
        foreach ($aWeights as $sKey => $fWeight) {
            $fShare = $fTotal > 0 ? $fWeight * $iLength / $fTotal : 0;
            $aCounts[$sKey] = (int) floor($fShare);
            $aRemainders[$sKey] = $fShare - $aCounts[$sKey];
        }
        $iLeft = $fTotal > 0 ? $iLength - array_sum($aCounts) : 0;
        // stable sort: on a tie, the first symbol keeps its place
        $aOrder = array_keys($aRemainders);
        usort($aOrder, function ($sA, $sB) use ($aRemainders, $aOrder) {
            return [$aRemainders[$sB], array_search($sA, $aOrder)] <=> [$aRemainders[$sA], array_search($sB, $aOrder)];
        });
        foreach (array_slice($aOrder, 0, $iLeft) as $sKey) {
            $aCounts[$sKey]++;
        }

        return $aCounts;
    }
}
