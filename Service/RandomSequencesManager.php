<?php
/**
 * Formulas Functions
 * Inspired by BioPHP's project biophp.org
 * Created 3 march 2019
 * Last modified 14 september 2026
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
                        $aDNA[$sAmino] = round($$sAmino * $iLength / $iAcgt);
                    }

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
                        $aProteins[$sProtein] = round($$sProtein * $iLength / $iAcdefghiklmnpgrstvwy);
                    }

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
            $iAcgt = 0;
            if ($iLength != null) {
                // in case length is specified
                foreach($aAminoAcids as $iAmino) {
                    $iAcgt += $iAmino;
                }
                foreach($aAminoAcids as $sKey => $iData) {
                    $aDNA[$sKey] = round($iData * $iLength / $iAcgt);
                }
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
            $iAcdefghiklmnpgrstvwy = 0;

            // Get number of ocurrences per each aminoacid
            if ($iLength != null) {
                // in case length is specified
                foreach($aAminoAcids as $iAmino) {
                    $iAcdefghiklmnpgrstvwy += $iAmino;
                }

                foreach($aAminoAcids as $sKey => $iData) {
                    $aProteins[$sKey] = round($iData * $iLength / $iAcdefghiklmnpgrstvwy);
                }

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
}