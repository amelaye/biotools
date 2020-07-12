<?php
/**
 * Sequence Manipulation and DATA Functions
 * Inspired by BioPHP's project biophp.org
 * Created 1st march  2019
 * Last modified 8 may 2020
 * RIP Pasha, gone 27 february 2019 =^._.^= ∫
 */
namespace App\Service;

use Amelaye\BioPHP\Domain\Sequence\Traits\SequenceTrait;

/**
 * Sequence Manipulation and DATA Functions
 * Class SequenceManipulationAndDataManager
 * @package MinitoolsBundle\Service
 * @author Amélie DUVERNET aka Amelaye <amelieonline@gmail.com>
 */
class SequenceManipulationAndDataManager
{
    use SequenceTrait;

    /**
     * @param $seq
     * @return string
     * @throws \Exception
     */
    public function displayBothStrands($seq)
    {
        try {
            // get the complementary sequence
            $revcomp = $this->revCompDNA($seq);
            $result = "";
            $i = 0;
            while ($i < strlen($seq)) {
                if(strlen($seq) < ($i+70)) {
                    $j = strlen($seq);
                } else {
                    $j = $i;
                }
                $result .= substr($seq,$i,70)."\t$j\n";
                $result .= substr($revcomp,$i,70)."\t$j\n";
                $result .= "\n"; //line break
                $i+=70;
            }
            return $result;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Displays the content of G and C
     * @param   string      $seq
     * @return  string
     * @throws  \Exception
     */
    public function gcContent($seq)
    {
        try {
            $number_of_G = substr_count($seq,"G");
            $number_of_C = substr_count($seq,"C");
            $gc_percent = round(100*($number_of_G + $number_of_C)/strlen($seq),2);
            return $gc_percent;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Replaces T by U
     * @param $seq
     * @return string|string[]|null
     * @throws \Exception
     */
    public function toRNA($seq)
    {
        try {
            $seq = preg_replace("/T/","U",$seq);
            $seq = chunk_split($seq, 70);
            return $seq;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * @param $seq
     * @return string
     * @throws \Exception
     */
    public function acgtContent($seq)
    {
        try {
            $result = "Nucleotide composition";
            $result.="\nA: ".substr_count($seq,"A");
            $result.="\nC: ".substr_count($seq,"C");
            $result.="\nG: ".substr_count($seq,"G");
            $result.="\nT: ".substr_count($seq,"T");

            $nucleoNonDNA = ["Y", "R", "W", "S", "K", "M", "D", "V", "H", "B", "N"];

            foreach($nucleoNonDNA as $letter) {
                if (substr_count($seq,$letter) > 0) {
                    $result .= "\n$letter: ".substr_count($seq, $letter);
                }
            }

            $result.="\n\n";
            return $result;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }
}