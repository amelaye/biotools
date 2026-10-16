<?php
/**
 * DNA To Protein Functions
 * Inspired by BioPHP's project biophp.org
 * Created 24 february 2019
 * Last modified 14 september 2026
 * RIP Pasha, gone 27 february 2019 =^._.^= ∫
 */
namespace Amelaye\BioTools\Service;


use Amelaye\BioPHP\Api\Interfaces\TripletApiAdapter;
use Amelaye\BioPHP\Api\Interfaces\AminoApiAdapter;
use Amelaye\BioPHP\Api\Interfaces\TripletSpecieApiAdapter;
use Amelaye\BioPHP\Domain\Sequence\Traits\SequenceTrait;
use Amelaye\BioPHP\Domain\Sequence\ValueObject\DnaSequence;
use Amelaye\BioPHP\Domain\Tools\Interfaces\AlternateGeneticCodeTranslatorInterface;
use Amelaye\BioPHP\Domain\Tools\Interfaces\OrfFinderInterface;
use Amelaye\BioPHP\Domain\Tools\ValueObject\OpenReadingFrame;


/**
 * Class DnaToProteinManager
 * @package BioTools\Service
 * @author Amélie DUVERNET aka Amelaye <amelieonline@gmail.com>
 */
class DnaToProteinManager
{
    use SequenceTrait;

    /**
     * NCBI genetic code table ids, by the label of the genetic codes of the form
     * (lower case, words separated by a space)
     */
    public const NCBI_GENETIC_CODES = [
        "standard"                                  => 1,
        "vertebrate mitochondrial"                  => 2,
        "yeast mitochondrial"                       => 3,
        "mold protozoan coelenterate mitochondrial" => 4,
        "invertebrate mitochondrial"                => 5,
        "ciliate dasycladacean hexamita nuclear"    => 6,
        "echinoderm mitochondrial"                  => 9,
        "euplotid nuclear"                          => 10,
        "bacterial plant plastid"                   => 11,
        "alternative yeast nuclear"                 => 12,
        "ascidian mitochondria"                     => 13,
        "flatworm mitochondrial"                    => 14,
        "blepharisma macronuclear"                  => 15,
        "chlorophycean mitochondrial"               => 16,
        "trematode mitochondrial"                   => 21,
        "scenedesmus obliquus mitochondrial"        => 22,
        "thraustochytrium mitochondrial code"       => 23,
    ];

    /**
     * @var array
     */
    private $aAminos;

    /**
     * @var array
     */
    private $aTriplets;

    /**
     * @var array
     */
    private $aTripletsCombinations;

    /**
     * @var string
     */
    private $sRvSequence;

    /**
     * @var OrfFinderInterface|null
     */
    private $orfFinder;

    /**
     * @var AlternateGeneticCodeTranslatorInterface|null
     */
    private $codonTranslator;

    /**
     * DnaToProteinManager constructor.
     * @param AminoApiAdapter            $aminoApi
     * @param TripletApiAdapter          $tripletApi
     * @param TripletSpecieApiAdapter    $tripletSpecieApi
     * @param OrfFinderInterface|null    $orfFinder          Needed by findOrfRegions() only
     * @param AlternateGeneticCodeTranslatorInterface|null $codonTranslator  Translates the codons of the genetic codes
     *                                                       of the form (NCBI tables); without it, the triplets
     *                                                       groups of the API are used, which leave an ambiguous
     *                                                       codon such as AGR as an X
     */
    public function __construct(
        AminoApiAdapter $aminoApi,
        TripletApiAdapter $tripletApi,
        TripletSpecieApiAdapter $tripletSpecieApi,
        ?OrfFinderInterface $orfFinder = null,
        ?AlternateGeneticCodeTranslatorInterface $codonTranslator = null
    ) {
        $this->orfFinder                  = $orfFinder;
        $this->codonTranslator            = $codonTranslator;
        $this->aAminos                    = $aminoApi::GetAminosOnlyLetters($aminoApi->getAminos());
        $this->aTriplets                  = $tripletSpecieApi::GetTripletsGroups($tripletSpecieApi->getTriplets());
        $this->aTripletsCombinations      = $tripletApi::GetTripletsArray($tripletApi->getTriplets());
    }

    /**
     * Splits the array og aminos
     * @param $aAminoAcidCodes
     * @param $aAminoAcidCodesLeft
     * @param $aAminoAcidCodesRight
     */
    public function showAminosArrays(&$aAminoAcidCodes, &$aAminoAcidCodesLeft, &$aAminoAcidCodesRight)
    {
        $aAminoAcidCodes        = $this->aAminos;
        $aAminoAcidCodesLeft    = array_slice($aAminoAcidCodes, 0, 13);
        $aAminoAcidCodesRight   = array_slice($aAminoAcidCodes, 13);
    }


    /**
     * Treatment when chosen custom
     * Unit Tests OK
     * @param   int         $iFrames
     * @param   string      $sSequence
     * @param   string      $sMycode
     * @return  array
     * @throws \Exception
     */
    public function customTreatment($iFrames, $sSequence, $sMycode)
    {
        try {
            $aFrames = [];
            // Translate in  5-3 direction
            $aFrames[1] = $this->translateDNAToProteinCustomcode(substr($sSequence, 0, floor(strlen($sSequence)/3)*3), $sMycode);

            if ($iFrames > 1) {
                $aFrames[2] = $this->translateDNAToProteinCustomcode(substr($sSequence, 1,floor((strlen($sSequence)-1)/3)*3),$sMycode);
                $aFrames[3] = $this->translateDNAToProteinCustomcode(substr($sSequence, 2,floor((strlen($sSequence)-2)/3)*3),$sMycode);
            }
            // Translate the opposite strand (reverse complement)
            if ($iFrames > 3) {
                // Get the reverse complement : the opposite strand is read 5' to 3', i.e. backwards
                $this->sRvSequence = $this->revCompDNA($sSequence);
                $aFrames[4] = $this->translateDNAToProteinCustomcode(substr($this->sRvSequence, 0, floor(strlen($this->sRvSequence)/3)*3),$sMycode);
                $aFrames[5] = $this->translateDNAToProteinCustomcode(substr($this->sRvSequence, 1,floor((strlen($this->sRvSequence)-1)/3)*3),$sMycode);
                $aFrames[6] = $this->translateDNAToProteinCustomcode(substr($this->sRvSequence, 2,floor((strlen($this->sRvSequence)-2)/3)*3),$sMycode);
            }
            return $aFrames;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Treatment when a specie has been chosen
     * Unit Tests OK
     * @param   int             $iFrames
     * @param   string          $sGeneticCode
     * @param   string          $sSequence
     * @return  array
     * @throws  \Exception
     */
    public function definedTreatment($iFrames, $sGeneticCode, $sSequence)
    {
        try {
            // Translate in 5-3 direction
            $aFrames[1] = $this->translateDNAToProtein(substr($sSequence, 0, floor(strlen($sSequence)/3)*3), $sGeneticCode);
            if ($iFrames > 1){
                $aFrames[2] = $this->translateDNAToProtein(substr($sSequence, 1,floor((strlen($sSequence)-1)/3)*3), $sGeneticCode);
                $aFrames[3] = $this->translateDNAToProtein(substr($sSequence, 2,floor((strlen($sSequence)-2)/3)*3), $sGeneticCode);
            }
            // Translate the opposite strand (reverse complement)
            if ($iFrames > 3){
                // Get the reverse complement : the opposite strand is read 5' to 3', i.e. backwards
                $this->sRvSequence = $this->revCompDNA($sSequence);
                //calculate frames 4-6
                $aFrames[4] = $this->translateDNAToProtein(substr($this->sRvSequence, 0,floor(strlen($this->sRvSequence)/3)*3), $sGeneticCode);
                $aFrames[5] = $this->translateDNAToProtein(substr($this->sRvSequence, 1,floor((strlen($this->sRvSequence)-1)/3)*3), $sGeneticCode);
                $aFrames[6] = $this->translateDNAToProtein(substr($this->sRvSequence, 2,floor((strlen($this->sRvSequence)-2)/3)*3), $sGeneticCode);
            }
            return $aFrames;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Find ORFs in sequence
     * Unit tests OK
     * @param   array       $aFrames
     * @param   int         $iProtsize
     * @param   bool        $bOnlyCoding
     * @param   bool        $bTrimmed
     * @return  array
     * @throws  \Exception
     */
    public function findORF($aFrames, $iProtsize, $bOnlyCoding, $bTrimmed)
    {
        if (!is_array($aFrames)) {
            throw new \Exception('The frames must be an array.');
        }
        try {
            foreach ($aFrames as $iN => $sPeptideSequence) {
                $sPeptideSequence = strtolower($sPeptideSequence);
                $aOligo = preg_split('/\*/',$sPeptideSequence);
                foreach ($aOligo as $iM => $sVal) {
                    if ($bTrimmed) {
                        // an ORF starts at its first Met: a segment without Met is no ORF, and
                        // the size is measured from that Met to the stop
                        $iMet = strpos($sVal, "m");
                        if ($iMet !== false && strlen($sVal) - $iMet >= $iProtsize) {
                            $aOligo[$iM] = substr($sVal, 0, $iMet).strtoupper(substr($sVal, $iMet));
                        }
                    } elseif (strlen($sVal) >= $iProtsize) {
                        $aOligo[$iM] = strtoupper($sVal);
                    }
                }
                $sNewPeptideSequence = "";
                foreach ($aOligo as $iM => $sVal) {
                    if($iM != 0){
                        $sNewPeptideSequence .= "*".$sVal;
                    } else {
                        $sNewPeptideSequence .= $sVal;
                    }
                }
                // To avoid showing no coding, remove them from output sequence
                if($bOnlyCoding) {
                    $sNewPeptideSequence = preg_replace("/[flimvsptayhqnkdecwrgx]/","_",$sNewPeptideSequence);
                }
                $aFrames[$iN] = $sNewPeptideSequence;
            }
            return $aFrames;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Finds the ORFs of a DNA sequence with their coordinates, on both strands, with BioPHP's OrfFinder.
     * Unlike findORF(), which only formats already translated frames for display, this returns
     * the regions themselves (frame, 1-based start/end on the original sequence, peptide).
     * @param   string      $sDna                   DNA sequence
     * @param   int         $iMinimumProteinLength  Minimum length of the peptide, in amino acids
     * @param   int|string  $mGeneticCode           An NCBI table id, or a genetic code label of the form
     *                                              (standard by default): TGA is not a stop under every code
     * @return  OpenReadingFrame[]
     * @throws  \LogicException                     When no OrfFinder was injected
     * @throws  \InvalidArgumentException           When the genetic code label is unknown
     */
    public function findOrfRegions(string $sDna, int $iMinimumProteinLength = 1, $mGeneticCode = 1): array
    {
        if ($this->orfFinder === null) {
            throw new \LogicException('No OrfFinderInterface was injected into DnaToProteinManager.');
        }

        return $this->orfFinder->findOrfs(
            new DnaSequence(strtoupper($sDna)),
            $iMinimumProteinLength,
            $this->ncbiGeneticCode($mGeneticCode)
        );
    }

    /**
     * @param   int|string  $mGeneticCode   An NCBI table id, or a genetic code label of the form
     * @return  int
     * @throws  \InvalidArgumentException
     */
    private function ncbiGeneticCode($mGeneticCode) : int
    {
        if (is_int($mGeneticCode) || (is_string($mGeneticCode) && ctype_digit($mGeneticCode))) {
            return (int) $mGeneticCode;
        }

        $sLabel = strtolower(trim(str_replace("_", " ", (string) $mGeneticCode)));
        if (!isset(self::NCBI_GENETIC_CODES[$sLabel])) {
            throw new \InvalidArgumentException(sprintf('Unknown genetic code "%s".', $mGeneticCode));
        }

        return self::NCBI_GENETIC_CODES[$sLabel];
    }

    /**
     * Translates a DNA Sequence to proteins
     * Unit Tests OK
     * @param   string          $sSequence
     * @param   string          $sGeneticCode
     * @return  string
     * @throws  \Exception
     */
    public function translateDNAToProtein($sSequence, $sGeneticCode)
    {
        if (!is_string($sSequence)) {
            throw new \Exception('The sequence must be a string.');
        }
        try {
            $sLabel = strtolower(str_replace("_", " ", (string) $sGeneticCode));
            if ($this->codonTranslator !== null && isset(self::NCBI_GENETIC_CODES[$sLabel])) {
                return $this->translateWithNcbiTable($sSequence, self::NCBI_GENETIC_CODES[$sLabel]);
            }

            $aAminoAcids = ["F","L","I","M","V","S","P","T","A","Y","*","H","Q","N","K","D","E","C","W","R","G","X"];
            // place a space after each triplete in the sequence
            $sTemp = chunk_split(strtoupper($sSequence),3,' ');
            // replace triplets by corresponding amnoacid
            $sPeptide = preg_replace($this->aTriplets[$sGeneticCode], $aAminoAcids, $sTemp);
            // return peptide sequence
            return $sPeptide;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Translates codon by codon under an NCBI table. An ambiguous codon stands for an amino acid when
     * all the codons it covers read the same (AGR is Arg, YTR is Leu in the standard code), X otherwise;
     * a symbol that is no IUPAC nucleotide gives X. A last incomplete codon is not translated.
     * @param   string  $sSequence
     * @param   int     $iTableId
     * @return  string
     */
    private function translateWithNcbiTable(string $sSequence, int $iTableId) : string
    {
        $sPeptide = "";
        foreach (str_split(strtoupper($sSequence), 3) as $sCodon) {
            if (strlen($sCodon) < 3) {
                break;
            }
            $sPeptide .= preg_match('/^[ACGTURYSWKMBDHVN]{3}$/', $sCodon) === 1
                ? $this->codonTranslator->translateCodon($sCodon, $iTableId)
                : "X";
        }

        return $sPeptide;
    }

    /**
     * Translate DNA to protein (custom)
     * @param   string          $sSequence
     * @param   string          $sGeneticCode
     * @return  string
     * @throws  \Exception
     */
    public function translateDNAToProteinCustomcode($sSequence, $sGeneticCode)
    {
        try {
            $sTemp = chunk_split(strtoupper($sSequence),3,' '); // The sequence is chopped and @ is inserted after each triplete

            // each triplete replace by corresponding amnoacid
            foreach ($this->aTripletsCombinations as $iKey => $aTriplete) {
                $sTemp = str_replace($aTriplete,substr($sGeneticCode, $iKey, 1)."  ",$sTemp);
            }

            // no matching triplets -> X
            $sTemp = preg_replace("(\S\S\S )", "X  ", $sTemp);
            $sTemp = substr($sTemp, 0, -2);

            $sProtein = preg_replace("/ /","",$sTemp);
            return $sProtein;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Shows triplets and Proteins aligned (5'->3')
     * @param   string      $sSequence
     * @param   array       $aFrame
     * @return  string
     * @throws  \Exception
     */
    public function showTranslationsAligned($sSequence, $aFrame)
    {
        try {
            $aResults = [];
            $aChunkedFrames = [];

            foreach ($aFrame as $iN => $sPeptideSequence) {
                $aChunkedFrames[$iN] = chunk_split($sPeptideSequence, 1, '  ');
            }

            $i = 0;

            while ($i < strlen($sSequence)) {
                $aResults[] = substr($sSequence, $i, 100) . "  ";
                if ($i < strlen($sSequence) - $i) {
                    $aResults[] = $i + 100;
                }
                $aResults[] = "\n";
                $aResults[] = substr($aChunkedFrames[1], $i, 100) . "\n";
                if (isset($aChunkedFrames[2])) {
                    $aResults[] = substr(" " . $aChunkedFrames[2], $i, 100) . "\n";
                }
                if (isset( $aChunkedFrames[3])) {
                    $aResults[] = substr("  " . $aChunkedFrames[3], $i, 100) . "\n\n";
                }

                $i += 100;
            }

            $sResults = implode('', $aResults);
            return $sResults;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Shows triplets and Proteins aligned (complementary DNA chain)
     * @param   array    $aFrame
     * @return  string
     * @throws  \Exception
     */
    public function showTranslationsAlignedComplementary($aFrame)
    {
        try {
            $sResults = "";
            $aChunkedFrames = [];

            foreach ($aFrame as $iN => $sPeptideSequence) {
                $aChunkedFrames[$iN] = chunk_split($sPeptideSequence, 1, '  ');
            }

            if (isset($aFrame[6])) {
                $i = 0;
                while ($i < strlen($this->sRvSequence)) {
                    $sResults .= substr($this->sRvSequence, $i, 100) . "  ";
                    if ($i < strlen($this->sRvSequence) - $i) {
                        $sResults .= $i + 100;
                    }
                    $sResults .= "\n";
                    $sResults .= substr($aChunkedFrames[4], $i, 100) . "\n";
                    $sResults .= substr(" " . $aChunkedFrames[5], $i, 100) . "\n";
                    $sResults .= substr("  " . $aChunkedFrames[6], $i, 100) . "\n\n";
                    $i += 100;
                }
            }

            return $sResults;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }
}