<?php
/**
 * PCR Amplification Functions
 * Inspired by BioPHP's project biophp.org
 * Created 26 february 2019
 * Last modified 16 october 2026
 */
namespace Amelaye\BioTools\Service;

use Amelaye\BioPHP\Api\Interfaces\NucleotidApiAdapter;

/**
 * Class PcrAmplificationManager
 * @package BioTools\Service
 * @author Amélie DUVERNET aka Amelaye <amelieonline@gmail.com>
 */
class PcrAmplificationManager
{
    /**
     * @var array
     */
    private $aDnaComplements;

    /**
     * The bases each IUPAC code of a primer stands for; N is any base
     */
    private const IUPAC_CLASSES = [
        "R" => "[AG]", "Y" => "[CT]", "S" => "[CG]", "W" => "[AT]", "K" => "[GT]", "M" => "[AC]",
        "B" => "[CGT]", "D" => "[AGT]", "H" => "[ACT]", "V" => "[ACG]", "N" => ".",
    ];

    /**
     * PcrAmplificationManager constructor.
     * @param NucleotidApiAdapter $nucleotidApi
     */
    public function __construct(NucleotidApiAdapter $nucleotidApi)
    {
        $this->aDnaComplements = $nucleotidApi::GetDNAComplement($nucleotidApi->getNucleotids());
    }

    /**
     * Amplify the sequence
     * A product is at most $iMaxLength long, forward primer included: the reverse primer is searched in
     * the $iMaxLength bases that start with the forward one (the window used to start after it, so a
     * product could exceed the maximum by the length of the primer).
     * @param   string      $sStartPattern
     * @param   string      $sEndPattern
     * @param   string      $sSequence
     * @param   int         $iMaxLength
     * @return  array
     * @throws  \Exception
     */
    public function amplify($sStartPattern, $sEndPattern, $sSequence, $iMaxLength)
    {
        try {
            $aResults = [];

            // SPLIT SEQUENCE BASED IN $start_pattern (start positions of amplicons)
            $aFragments = preg_split("/($sStartPattern)/", $sSequence,-1,PREG_SPLIT_DELIM_CAPTURE);
            $iMaxFragments = sizeof($aFragments);
            $iPosition = strlen($aFragments[0]);

            for($m = 1; $m < $iMaxFragments; $m += 2) {
                $sSubfragmentToMaximum = substr($aFragments[$m + 1], 0, max(0, (int) $iMaxLength - strlen($aFragments[$m])));
                $aFragments2 = preg_split("/($sEndPattern)/", $sSubfragmentToMaximum,-1,PREG_SPLIT_DELIM_CAPTURE);

                if (sizeof($aFragments2) > 1) {
                    $iLenFragment = strlen($aFragments[$m].$aFragments2[0].$aFragments2[1]);
                    $aResults[$iPosition] = $iLenFragment;
                }
                $iPosition += strlen($aFragments[$m]) + strlen($aFragments[$m+1]);
            }

            return($aResults);
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * If one missmatch is allowed, create new pattern
     * example: pattern="ACGT"; to allow one missmatch pattern=".CGT|A.GT|AC.T|ACG."
     * @param   string      $sPattern
     * @return  string
     * @throws  \Exception
     */
    public function includeN($sPattern)
    {
        try {
            $sNewPattern = "";
            if (strlen($sPattern) > 2) {
                $sNewPattern = ".".substr($sPattern,1);
                $iPos = 1;
                while($iPos < strlen($sPattern)) {
                    $sNewPattern .= "|".substr($sPattern,0,$iPos).".".substr($sPattern,$iPos + 1);
                    $iPos++;
                }
            }
            return $sNewPattern;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * SET PATTERNS FROM PRIMERS
     * Change N to point in primers, and every other IUPAC code to the bases it stands for (R is [AG]):
     * a degenerate primer matches the template. With one mismatch allowed, each position in turn
     * becomes any base, the degenerate ones included (an N used to stay a literal "N" there, so the
     * mismatch option found nothing a primer with N could not).
     * @param   string      $sPrimer1
     * @param   string      $sPrimer2
     * @param   bool        $bAllowMismatch
     * @return  string
     * @throws  \Exception
     */
    public function createStartPattern($sPrimer1, $sPrimer2, $bAllowMismatch)
    {
        $sPattern1 = $this->primerPattern($sPrimer1, (bool) $bAllowMismatch);
        $sPattern2 = $this->primerPattern($sPrimer2, (bool) $bAllowMismatch);

        $sStartPattern = "$sPattern1|$sPattern2"; // SET PATTERN
        return $sStartPattern;
    }

    /**
     * Regular expression of one primer: a token per base, with one mismatch the alternatives where one
     * token in turn is any base (the same as includeN() on a primer of A, C, G and T)
     * @param   string      $sPrimer
     * @param   bool        $bAllowMismatch
     * @return  string
     */
    private function primerPattern($sPrimer, bool $bAllowMismatch) : string
    {
        $aTokens = [];
        foreach (str_split(strtoupper($sPrimer)) as $sBase) {
            $aTokens[] = self::IUPAC_CLASSES[$sBase] ?? $sBase;
        }
        if (!$bAllowMismatch) {
            return implode("", $aTokens);
        }
        if (count($aTokens) <= 2) {
            return ""; // as includeN()
        }
        $aAlternatives = [];
        foreach ($aTokens as $iPos => $sToken) {
            $aAlternative = $aTokens;
            $aAlternative[$iPos] = ".";
            $aAlternatives[] = implode("", $aAlternative);
        }

        return implode("|", $aAlternatives);
    }

    /**
     * SET PATTERNS FROM PRIMERS
     * The reverse complement of the start pattern: its tokens (a base, ".", a class like [AG], "|")
     * in reverse order, each complemented; a class keeps its brackets ([AG] gives [CT]).
     * @param   string  $sStartPattern
     * @return  string
     */
    public function createEndPattern($sStartPattern)
    {
        preg_match_all("/\\[[^\\]]*\\]|./", strtoupper($sStartPattern), $aMatches);
        $aTokens = [];
        foreach (array_reverse($aMatches[0]) as $sToken) {
            if (strlen($sToken) > 1) {
                $aBases = array_map([$this, 'complementBase'], str_split(substr($sToken, 1, -1)));
                sort($aBases);
                $aTokens[] = "[".implode("", $aBases)."]";
            } else {
                $aTokens[] = $this->complementBase($sToken);
            }
        }
        $sEndPattern = implode("", $aTokens);

        return $sEndPattern;
    }

    /**
     * Complement of a base by the DNA table; any other character (".", "|") is itself
     * @param   string  $sBase
     * @return  string
     */
    private function complementBase($sBase) : string
    {
        return $this->aDnaComplements[$sBase] ?? $sBase;
    }
}