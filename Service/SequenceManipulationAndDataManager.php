<?php
/**
 * Sequence Manipulation and DATA Functions
 * Inspired by BioPHP's project biophp.org
 * Created 1st march  2019
 * Last modified 16 october 2026
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
     * The sequence on 70 bases lines, each followed by its plain complement written base for base
     * under it: the second row reads 3' to 5' from left to right (it is not the reverse complement),
     * and each line ends with the 1-based position of its last base. The complement is the one of DNA:
     * it knows no U. The forward row keeps the case of the input, the complement is in upper case.
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
     * says nothing about it and is left out, so "ACGTNNNNNN" is 50 %, not 20 %. A gap, a digit
     * or any other symbol that is no base is left out the same way: "AC-GT" is 2 out of 4 = 50 %,
     * where the whole length (5) would give 40 %. The Tm uses the same definition. The skews do
     * not: they are (G - C) / (G + C), not a G+C fraction.
     * When no base tells G/C from A/T/U (empty sequence, only N...) the G+C is undefined, not 0 %:
     * null is returned. Legacy divided by strlen() unguarded: under PHP 5 that warned and yielded
     * INF, but since PHP 8 it is a fatal DivisionByZeroError, and the sequence field is emptied by
     * the form whenever the input holds no codable character at all (e.g. "ZZZZ").
     * @param   string      $sSeq
     * @return  float|null
     * @throws  \Exception
     */
    public function gcContent($sSeq)
    {
        try {
            if (preg_match('/[GCSATUW]/i', (string) $sSeq) !== 1) {
                return null;
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