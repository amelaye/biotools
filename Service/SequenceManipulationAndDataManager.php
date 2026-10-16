<?php
/**
 * Sequence Manipulation and DATA Functions
 * Inspired by BioPHP's project biophp.org
 * Created 1st march  2019
 * Last modified 14 september 2026
 * RIP Pasha, gone 27 february 2019 =^._.^= ∫
 */
namespace Amelaye\BioTools\Service;

use Amelaye\BioPHP\Domain\Sequence\Traits\SequenceTrait;
use Amelaye\BioPHP\Domain\Sequence\ValueObject\AbstractNucleicSequence;

/**
 * Sequence Manipulation and DATA Functions
 * Class SequenceManipulationAndDataManager
 * @package BioTools\Service
 * @author Amélie DUVERNET aka Amelaye <amelieonline@gmail.com>
 */
class SequenceManipulationAndDataManager
{
    use SequenceTrait;

    /**
     * @param   string  $sSeq
     * @return  string
     * @throws  \Exception
     */
    public function displayBothStrands($sSeq)
    {
        try {
            // get the complementary sequence, base for base under the forward strand
            $sRevcomp = $this->compDNA($sSeq);
            $sResult = "";
            $i = 0;
            while ($i < strlen($sSeq)) {
                // 1-based position of the last base of the line, on every line
                $iJ = min($i + 70, strlen($sSeq));
                $sResult .= substr($sSeq,$i,70)."\t$iJ\n";
                $sResult .= substr($sRevcomp,$i,70)."\t$iJ\n";
                $sResult .= "\n"; //line break
                $i+=70;
            }
            return $sResult;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Displays the content of G and C, as a percentage of the bases that tell G/C from A/T/U
     * (BioPHP's AbstractNucleicSequence::gcFraction): an N or any ambiguity code mixing both
     * says nothing about it and is left out, so "ACGTNNNNNN" is 50 %, not 20 %. The Tm and the
     * skews of biotools use the same definition.
     * An empty sequence has no G+C at all, so it scores 0. Legacy divided by
     * strlen() unguarded: under PHP 5 that warned and yielded INF, but since PHP 8
     * it is a fatal DivisionByZeroError, and the sequence field is emptied by the
     * form whenever the input holds no codable character at all (e.g. "ZZZZ").
     * @param   string      $sSeq
     * @return  float|int
     * @throws  \Exception
     */
    public function gcContent($sSeq)
    {
        try {
            $iLenSeq = strlen((string) $sSeq);
            if ($iLenSeq === 0) {
                return 0;
            }
            $fGcPercent = round(100 * AbstractNucleicSequence::gcFraction((string) $sSeq), 2);
            return $fGcPercent;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Replaces T by U
     * @param   string  $sSeq
     * @return  string|string[]|null
     * @throws  \Exception
     */
    public function toRNA($sSeq)
    {
        try {
            $sSeq = preg_replace("/T/","U",$sSeq);
            $sSeq = chunk_split($sSeq, 70);
            return $sSeq;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * @param   string  $sSeq
     * @return  string
     * @throws  \Exception
     */
    public function acgtContent($sSeq)
    {
        try {
            $sResult = "Nucleotide composition";
            $sResult.="\nA: ".substr_count($sSeq,"A");
            $sResult.="\nC: ".substr_count($sSeq,"C");
            $sResult.="\nG: ".substr_count($sSeq,"G");
            $sResult.="\nT: ".substr_count($sSeq,"T");

            $aNucleoNonDNA = ["Y", "R", "W", "S", "K", "M", "D", "V", "H", "B", "N"];

            foreach($aNucleoNonDNA as $sLetter) {
                if (substr_count($sSeq,$sLetter) > 0) {
                    $sResult .= "\n$sLetter: ".substr_count($sSeq, $sLetter);
                }
            }

            $sResult.="\n\n";
            return $sResult;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }
}