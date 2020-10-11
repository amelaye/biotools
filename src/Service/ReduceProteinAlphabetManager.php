<?php
/**
 * Class ReduceProteinAlphabetManager
 * Inspired by BioPHP's project biophp.org
 * Created 27 february 2019 - RIP Pasha =^._.^= ∫
 * Last modified 11 october 2020
 */
namespace App\Service;

use Amelaye\BioPHP\Api\Interfaces\ProteinReductionApiAdapter;

/**
 * Reduce Protein Alphabet Functions
 * @package MinitoolsBundle\Service
 * @author Amélie DUVERNET aka Amelaye <amelieonline@gmail.com>
 */
class ReduceProteinAlphabetManager
{
    /**
     * @var array
     */
    private $proteinColors;

    /**
     * @var array
     */
    private $aReductions;

    /**
     * @var ProteinReductionApiAdapter
     */
    private $oProteinReduction;

    /**
     * ReduceProteinAlphabetManager constructor.
     * @param       array                      $proteinColors
     * @param       ProteinReductionApiAdapter $oProteinReduction
     */
    public function __construct(array $proteinColors, ProteinReductionApiAdapter $oProteinReduction)
    {
        $this->proteinColors     = $proteinColors;
        $this->oProteinReduction = $oProteinReduction;
        $this->aReductions       = $oProteinReduction::GetReductionsArray($oProteinReduction->getReductions());
    }

    /**
     * Reduce alphabet for $seq by using the predefined $type type of reduction
     * returns a reduced sequence
     * @param   string $sSequence
     * @param   string $sType
     * @return  string
     * @throws  \Exception
     */
    public function reduceAlphabet(string $sSequence, string $sType) : string
    {
        try {
            $aPattern       =  $this->aReductions[$sType]["pattern"];
            $aReplacement   =  $this->aReductions[$sType]["reduction"];
            $sSequence = preg_replace($aPattern, $aReplacement, $sSequence);
            $sSequence = strtoupper($sSequence);
            return $sSequence;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Reduce the alphabet for $seq by using the user defined personalized alphabet
     * returns the reduced sequence
     * @param       string          $sSequence
     * @param       string          $sCustomAlphabet
     * @return      string
     * @throws      \Exception
     */
    public function reduceAlphabetCustom(string $sSequence, string $sCustomAlphabet) : string
    {
        try {
            $sCustomAlphabet = strtolower($sCustomAlphabet);
            // array with reduced code
            $a = preg_split("//",$sCustomAlphabet,-1,PREG_SPLIT_NO_EMPTY);
            // array with aminoacids
            $b = preg_split("//","ARNDCEQGHILKMFPSTWYV",-1,PREG_SPLIT_NO_EMPTY);

            foreach($a as $key=> $val) {
                // replace aminoacids by reduced codes
                $sSequence = preg_replace("/".$b[$key]."/", $val, $sSequence);
            }
            $sSequence = strtoupper($sSequence);
            return $sSequence;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Gets the informations about reductions
     * @param       string      $sType
     * @return      array
     * @throws      \Exception
     */
    public function createReduceCode(string $sType) : array
    {
        try {
            $oProteinReduction = $this->oProteinReduction;
            $aReductions = $oProteinReduction::GetAlphabetInfos($oProteinReduction->getReductions(), $sType);
            return $aReductions;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }
}