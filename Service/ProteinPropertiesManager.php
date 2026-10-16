<?php
/**
 * Proteins properties Functions
 * Inspired by BioPHP's project biophp.org
 * Created 24 february 2019
 * Last modified 16 october 2026
 */
namespace Amelaye\BioTools\Service;

use Amelaye\BioPHP\Api\Interfaces\AminoApiAdapter;
use Amelaye\BioPHP\Api\Interfaces\PKApiAdapter;
use Amelaye\BioPHP\Domain\Sequence\Entity\Protein;
use Amelaye\BioPHP\Domain\Sequence\Interfaces\ProteinInterface;
use Amelaye\BioPHP\Domain\Sequence\Interfaces\SequenceInterface;
use Amelaye\BioPHP\Domain\Sequence\Service\ProteinManager;
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
     * @var ProteinInterface
     */
    private $proteinManager;

    /**
     * ProteinPropertiesManager constructor.
     * @param   AminoApiAdapter         $aminoApi
     * @param   PKApiAdapter            $pkApi
     * @param   SequenceInterface|null  $sequenceManager    Needed by proteinAminoacidsChemicalGroup() only
     * @param   ProteinPropertiesInterface|null $proteinProperties  Charge and isoelectric point, BioPHP's calculator by default
     * @param   ProteinInterface|null           $proteinManager     Molecular weight, BioPHP's ProteinManager by default
     */
    public function __construct(
        AminoApiAdapter $aminoApi,
        PKApiAdapter $pkApi,
        ?SequenceInterface $sequenceManager = null,
        ?ProteinPropertiesInterface $proteinProperties = null,
        ?ProteinInterface $proteinManager = null
    ){
        $this->sequenceManager   = $sequenceManager;
        $this->proteinProperties = $proteinProperties ?? new ProteinPropertiesCalculator();
        $this->proteinManager    = $proteinManager ?? new ProteinManager($aminoApi);
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
     * @throws      \InvalidArgumentException  When the protein is empty or the pK set lacks a group
     */
    public function proteinIsoelectricPoint($aAminoacidContent)
    {
        return round(
            $this->proteinProperties->isoelectricPoint($this->sequenceFromContent($aAminoacidContent), $this->aPk),
            2
        );
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
     * @throws      \InvalidArgumentException  When the protein is empty or the pK set lacks a group
     */
    public function proteinCharge($aAminoacidContent, $iPH)
    {
        return $this->proteinProperties->netCharge(
            $this->sequenceFromContent($aAminoacidContent), (float) $iPH, $this->aPk
        );
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
     * Since this method no longer divides by the molecular weight, it takes the amino acid content
     * only; a second argument (the weight of the former signature) is ignored and reported as
     * deprecated, because the value it used to receive was the mass coefficient: use
     * massAbsorptionCoefficientOfProt() for that one.
     * @param   array       $aAminoacidContent
     * @return  float
     * @throws  \Exception
     */
    public function molarAbsorptionCoefficientOfProt($aAminoacidContent)
    {
        if (func_num_args() > 1) {
            trigger_error(
                'molarAbsorptionCoefficientOfProt() no longer takes the molecular weight and returns the molar '
                . 'coefficient (M-1.cm-1), not the mass coefficient: use massAbsorptionCoefficientOfProt().',
                E_USER_DEPRECATED
            );
        }
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
     * Molecular weight calculation, by BioPHP's ProteinManager: the average masses of the free amino
     * acids less one water (18.0153) per peptide bond - Biopython's molecular_weight. Every residue of
     * the amino acid table counts, selenocysteine (U) and pyrrolysine (O) included, and Valine too:
     * legacy's loop added "$aminoacid_content["Z"]*99.13" for it, a typo for the "V" key, so legacy
     * dropped Valine from every result. The stop "*" is no residue. B, Z and X stand for several amino
     * acids, so they span a range: the upper limit is returned.
     * An empty chain weighs 0.
     * @param   array   $aAminoacidContent      Letter => count
     * @return  float
     * @throws  \Exception
     */
    public function proteinMolecularWeight($aAminoacidContent)
    {
        try {
            $sSequence = "";
            foreach ($aAminoacidContent as $sAminoAcid => $iCount) {
                if ($sAminoAcid !== "*") {
                    $sSequence .= str_repeat((string) $sAminoAcid, (int) $iCount);
                }
            }

            $oProtein = new Protein();
            $oProtein->setSequence($sSequence);
            $this->proteinManager->setProtein($oProtein);
            $aWeights = $this->proteinManager->molwt();
            if ($aWeights === false) {
                $aUnknown = [];
                foreach ($aAminoacidContent as $sAminoAcid => $iCount) {
                    if ($iCount > 0 && $sAminoAcid !== "*" && !isset($this->aAminos[$sAminoAcid])) {
                        $aUnknown[] = $sAminoAcid;
                    }
                }
                throw new \InvalidArgumentException(sprintf(
                    'The protein holds a symbol with no molecular weight%s.',
                    $aUnknown === [] ? '' : ' (' . implode(', ', $aUnknown) . ')'
                ));
            }

            return $aWeights[1];
        } catch (\InvalidArgumentException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Displays the colored aminoacids (type 1): the classes of the original minitool.
     * Nonpolar (G A P V I L F M), polar (S C T N Q H Y W), charged (D E K R); the usual textbook
     * tables differ on a few residues (Cys and Trp are often listed as nonpolar, His as basic).
     * A residue outside these classes (B, Z, J, X, U, O, the stop, any other symbol) is not left out:
     * it keeps its place with the colour $aColors["other"] ("black" when it is missing). The
     * residues are classified whatever their case.
     * @param   string      $sSequence
     * @param   array       $aColors
     * @return  array       [residue, colour] for every residue
     * @throws  \Exception
     */
    public function proteinAminoacidNature1($sSequence, $aColors)
    {
        try {
            $aResult = [];
            for($i = 0; $i < strlen($sSequence); $i++) {
                $sResidue = substr($sSequence, $i, 1);
                $sUpper = strtoupper($sResidue);
                if (strpos(" GAPVILFM", $sUpper) > 0) {
                    $aResult[] = [$sResidue, $aColors["nonpolar"]];
                } elseif (strpos(" SCTNQHYW", $sUpper) > 0) {
                    $aResult[] = [$sResidue, $aColors["polar"]];
                } elseif (strpos(" DEKR", $sUpper) > 0) {
                    $aResult[] = [$sResidue, $aColors["charged"]];
                } else {
                    $aResult[] = [$sResidue, $aColors["other"] ?? "black"];
                }
            }
            return $aResult;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Displays the colored aminoacids (type 2): groups in the style of Taylor's Venn diagram.
     * G A S T (small: the key "nonpolar" of the colours), C V I L P F Y M W (hydrophobic or aromatic:
     * the key "hydrophobic"; Y and W are only weakly hydrophobic), N Q H (polar), D E (negatively
     * charged acids; Asp is the acid and Asn the neutral amide), K R (positively charged). S and T
     * are polar hydroxyl residues that this grouping lists with the small ones.
     * A residue outside these groups keeps its place with the colour $aColors["other"] ("black" when
     * it is missing). The residues are classified whatever their case.
     * @param   string      $sSequence
     * @param   array       $aColors
     * @return  array       [residue, colour] for every residue
     * @throws  \Exception
     */
    public function proteinAminoacidNature2($sSequence, $aColors)
    {
        try {
            $aResult = [];
            for ($i = 0; $i < strlen($sSequence); $i++) {
                $sResidue = substr($sSequence, $i, 1);
                $sUpper = strtoupper($sResidue);
                if (strpos(" GAST", $sUpper) > 0) {
                    $aResult[] = [$sResidue, $aColors["nonpolar"]];
                } elseif (strpos(" CVILPFYMW", $sUpper) > 0) {
                    $aResult[] = [$sResidue, $aColors["hydrophobic"]];
                } elseif (strpos(" NQH", $sUpper) > 0) {
                    $aResult[] = [$sResidue, $aColors["polar"]];
                } elseif (strpos(" DE", $sUpper) > 0) {
                    $aResult[] = [$sResidue, $aColors["negatively_charged"]];
                } elseif (strpos(" KR", $sUpper) > 0) {
                    $aResult[] = [$sResidue, $aColors["positively_charged"]];
                } else {
                    $aResult[] = [$sResidue, $aColors["other"] ?? "black"];
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
