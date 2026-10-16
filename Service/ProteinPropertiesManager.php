<?php
/**
 * Proteins properties Functions
 * Inspired by BioPHP's project biophp.org
 * Created 24 february 2019
 * Last modified 14 september 2026
 */
namespace Amelaye\BioTools\Service;

use Amelaye\BioPHP\Api\Interfaces\AminoApiAdapter;
use Amelaye\BioPHP\Api\Interfaces\PKApiAdapter;
use Amelaye\BioPHP\Domain\Sequence\Interfaces\SequenceInterface;
use Amelaye\BioPHP\Domain\Sequence\ValueObject\AminoAcidSequence;
use Amelaye\BioPHP\Domain\Tools\Interfaces\ProteinPropertiesInterface;
use Amelaye\BioPHP\Domain\Tools\Service\ProteinPropertiesCalculator;

/**
 * Class ProteinPropertiesManager
 * @package BioTools\Service
 * @author Amélie DUVERNET aka Amelaye <amelieonline@gmail.com>
 */
class ProteinPropertiesManager
{
    /**
     * @var array
     */
    private $aAminos;

    /**
     * @var array
     */
    private $aResidueWeights;

    /**
     * @var PKApiAdapter
     */
    private $pkApi;

    /**
     * @var array
     */
    private $aPk;

    /**
     * @var SequenceInterface|null
     */
    private $sequenceManager;

    /**
     * @var ProteinPropertiesInterface
     */
    private $proteinProperties;

    /**
     * ProteinPropertiesManager constructor.
     * @param   AminoApiAdapter         $aminoApi
     * @param   PKApiAdapter            $pkApi
     * @param   SequenceInterface|null  $sequenceManager    Needed by proteinAminoacidsChemicalGroup() only
     * @param   ProteinPropertiesInterface|null $proteinProperties  Charge and isoelectric point, BioPHP's calculator by default
     */
    public function __construct(
        AminoApiAdapter $aminoApi,
        PKApiAdapter $pkApi,
        ?SequenceInterface $sequenceManager = null,
        ?ProteinPropertiesInterface $proteinProperties = null
    ){
        $this->sequenceManager   = $sequenceManager;
        $this->proteinProperties = $proteinProperties ?? new ProteinPropertiesCalculator();
        $this->aResidueWeights   = $aminoApi::GetAminoResidueWeights($aminoApi->getAminos());
        $this->aAminos           = $aminoApi::GetAminosOneToThreeLetters($aminoApi->getAminos());
        $this->pkApi             = $pkApi;
    }

    /**
     * Get pk values for charged aminoacids
     * @param   string  $sDataSource
     */
    public function setPk($sDataSource)
    {
        $this->aPk = $this->pkApi->getPkValueById($sDataSource);
    }

    /**
     * Converts a subsequence into 3 letters code
     * @param   string      $sSubsequence
     * @return  string
     * @throws  \Exception
     */
    public function convertInto3lettersCode($sSubsequence)
    {
        try {
            $s3LetterCode = "";
            foreach(str_split($sSubsequence) as $sLetter) {
                $s3LetterCode .= $this->aAminos[$sLetter];
            }
            return $s3LetterCode;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Generates subsequence string
     * @param   int     $iStart
     * @param   int     $iEnd
     * @param   string  $sSequence
     * @return  string
     * @throws  \Exception
     */
    public function writeSubsequence($iStart, $iEnd, $sSequence)
    {
        try {
            $sSubsequence = "";
            // if subsequence is requested
            if ($iStart != "" || $iEnd != "") {
                $iStartPos = ($iStart != "") ? $iStart - 1 : 0;
                $iEndPos  = ($iEnd != "") ? $iEnd : strlen($sSequence);
                $sSubsequence = substr($sSequence, $iStartPos,$iEndPos - $iStartPos);
            }
            return $sSubsequence;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }


    /**
     * Isoelectric point: the pH where the net charge is zero, found by BioPHP's bisection for the
     * pK set chosen with setPk(), with two decimals.
     * @param       array       $aAminoacidContent
     * @return      float
     * @throws      \Exception
     */
    public function proteinIsoelectricPoint($aAminoacidContent)
    {
        try {
            return round(
                $this->proteinProperties->isoelectricPoint($this->sequenceFromContent($aAminoacidContent), $this->aPk),
                2
            );
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * The residues of an amino acid content (letter => count), as a sequence: the order does not
     * matter to the charge
     * @param   array   $aAminoacidContent
     * @return  AminoAcidSequence
     */
    private function sequenceFromContent(array $aAminoacidContent) : AminoAcidSequence
    {
        $sSequence = "";
        foreach ($aAminoacidContent as $sAminoAcid => $iCount) {
            $sSequence .= str_repeat((string) $sAminoAcid, (int) $iCount);
        }

        return new AminoAcidSequence($sSequence);
    }

    /**
     * Computes partial charge
     * @param       float     $fVal1
     * @param       int       $iVal2
     * @return      float
     * @throws      \Exception
     */
    public function partialCharge($fVal1, $iVal2)
    {
        try {
            $iCr = pow(10,$fVal1 - $iVal2); // compute concentration ratio
            $fPc = $iCr / ($iCr + 1); // compute partial charge
            return $fPc;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Computes protein charge at corresponding pH, for the pK set chosen with setPk()
     * @param       array       $aAminoacidContent
     * @param       int|float   $iPH
     * @return      float
     * @throws      \Exception
     */
    public function proteinCharge($aAminoacidContent, $iPH)
    {
        try {
            return $this->proteinProperties->netCharge(
                $this->sequenceFromContent($aAminoacidContent), (float) $iPH, $this->aPk
            );
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }


    /**
     * @param   array $aAminoacidContent
     * @return  array
     * @throws  \Exception
     */
    public function formatAminoacidContent($aAminoacidContent)
    {
        try {
            $aResults = [];
            foreach($aAminoacidContent as $sAa => $iCount) {
                $aResults[] = ["one_letter" => $sAa, "three_letters" => $this->aAminos[$sAa], "count" => $iCount];
            }
            return $aResults;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }


    /**
     * Counts number of amino acids in a sequence
     * @param       string      $sSequence
     * @return      array
     * @throws      \Exception
     */
    public function aminoacidContent($sSequence)
    {
        try {
            $aNbAminos = [];
            foreach($this->aAminos as $sName1Letter => $sName3Letters) {
                if(isset($sName3Letters) && $sName3Letters != "N/A") {
                    $aNbAminos[$sName1Letter] = 0;
                }
            }

            for($i = 0; $i < strlen($sSequence); $i++){
                $sSequence2 = substr($sSequence, $i,1);
                $aNbAminos[$sSequence2]++;
            }
            return $aNbAminos;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Prediction of the molar absorption coefficient of a protein at 280 nm, in M-1.cm-1
     * Pace et al. . Protein Sci. 1995;4:2411-23.
     * epsilon = W*5500 + Y*1490 + cystines*125, a cystine being a disulfide bridge, i.e. two Cys.
     * The cystine count assumes every Cys that can pair is paired.
     * @param   array       $aAminoacidContent
     * @return  float
     * @throws  \Exception
     */
    public function molarAbsorptionCoefficientOfProt($aAminoacidContent)
    {
        try {
            return $aAminoacidContent["W"] * 5500
                + $aAminoacidContent["Y"] * 1490
                + floor($aAminoacidContent["C"] / 2) * 125;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Mass absorption coefficient at 280 nm: A280 of a 1 g/L solution (epsilon / molecular weight)
     * @param   float       $fMolarAbsorptionCoefficient    In M-1.cm-1
     * @param   float       $fMolWeight                     In Da
     * @return  float
     * @throws  \InvalidArgumentException
     */
    public function massAbsorptionCoefficientOfProt($fMolarAbsorptionCoefficient, $fMolWeight)
    {
        if ($fMolWeight <= 0) {
            throw new \InvalidArgumentException("The molecular weight must be positive.");
        }

        return $fMolarAbsorptionCoefficient / $fMolWeight;
    }

    /**
     * Molecular weight calculation
     *
     * Deliberate deviation from the legacy minitool: legacy's molecular weight loop adds
     * "$aminoacid_content["Z"]*99.13" for Valine, but the content array it builds only ever
     * has a "V" key - "Z" is a typo, so legacy's sum for that undefined index is always 0
     * and Valine's weight is silently dropped from every legacy result. This port sums
     * every amino acid actually present, Valine included, so the weight it returns is the
     * scientifically correct one and will differ from legacy's by 99.13 per Valine residue.
     * @param   array   $aAminoacidContent
     * @return  float
     * @throws  \Exception
     */
    public function proteinMolecularWeight($aAminoacidContent)
    {
        try {
            $fMolWeight = 0;
            $iResidueCount = 0;
            foreach($aAminoacidContent as $sKey => $sAmino) {
                $fMolWeight += $sAmino * $this->aResidueWeights[$sKey];
                $iResidueCount += $sAmino;
            }
            // An empty chain has zero residues and zero mass: it must not gain a spurious
            // free water molecule.
            if ($iResidueCount > 0) {
                $fMolWeight += 18.02;  // water
            }
            return $fMolWeight;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Displays the colored aminoacids
     * @param       string      $sSequence
     * @param       array       $aColors
     * @return      array
     * @throws      \Exception
     */
    public function proteinAminoacidNature1($sSequence, $aColors)
    {
        try {
            $aResult = [];
            for($i = 0; $i < strlen($sSequence); $i++) {
                // non-polar aminoacids, yellow
                if (strpos(" GAPVILFM", substr($sSequence,$i,1)) > 0) {
                    $aResult[] = [substr($sSequence,$i,1), $aColors["nonpolar"]];
                    continue;
                }
                // polar aminoacids, magenta
                if (strpos(" SCTNQHYW", substr($sSequence,$i,1)) > 0) {
                    $aResult[] = [substr($sSequence,$i,1), $aColors["polar"]];
                    continue;
                }
                // charged aminoacids, red
                if (strpos(" DEKR", substr($sSequence,$i,1)) > 0) {
                    $aResult[] = [substr($sSequence,$i,1), $aColors["charged"]];
                    continue;
                }
            }
            return $aResult;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Displays the colored aminoacids (tyoe 2)
     * @param   string      $sSequence
     * @param   array       $aColors
     * @return  array
     * @throws  \Exception
     */
    public function proteinAminoacidNature2($sSequence, $aColors)
    {
        try {
            $aResult = [];
            for ($i = 0; $i < strlen($sSequence); $i++) {
                // Small nonpolar (yellow)
                if (strpos(" GAST",substr($sSequence,$i,1)) > 0) {
                    $aResult[] = [substr($sSequence,$i,1), $aColors["nonpolar"]];
                    continue;
                }
                // Small hydrophobic (green)
                if (strpos(" CVILPFYMW",substr($sSequence,$i,1)) > 0) {
                    $aResult[] = [substr($sSequence,$i,1), $aColors["hydrophobic"]];
                    continue;
                }
                // Polar
                if (strpos(" NQH",substr($sSequence,$i,1)) > 0) {
                    $aResult[] = [substr($sSequence,$i,1), $aColors["polar"]];
                    continue;
                }
                // Negatively charged
                if (strpos(" DE",substr($sSequence,$i,1)) > 0) {
                    $aResult[] = [substr($sSequence,$i,1), $aColors["negatively_charged"]];
                    continue;
                }
                // Positively charged
                if (strpos(" KR",substr($sSequence,$i,1)) > 0) {
                    $aResult[] = [substr($sSequence,$i,1), $aColors["positively_charged"]];
                    continue;
                }
            }
            return $aResult;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Replaces each amino acid by the letter of its chemical group, as BioPHP's chemicalGroup() does:
     * L aliphatic, H hydroxyl, M amide, R aromatic, S sulfur, I imino, A acidic, C basic
     * @param   string      $sAminoSeq
     * @return  string
     * @throws  \LogicException    When no SequenceInterface was injected
     * @throws  \Exception         On an invalid amino acid symbol
     */
    public function proteinAminoacidsChemicalGroup($sAminoSeq)
    {
        if ($this->sequenceManager === null) {
            throw new \LogicException('No SequenceInterface was injected into ProteinPropertiesManager.');
        }

        return $this->sequenceManager->chemicalGroup($sAminoSeq);
    }
}
