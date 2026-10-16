<?php
/**
 * Inspired by BioPHP's project biophp.org
 * Created 28 february 2019
 * Last modified 16 october 2026
 * RIP Pasha, gone 27 february 2019 =^._.^= ∫
 */
namespace Amelaye\BioTools\Service;

use Amelaye\BioPHP\Api\Interfaces\Pam250MatrixDigitApiAdapter;
use Amelaye\BioPHP\Domain\Alignment\Exception\InvalidAlignmentInputException;
use Amelaye\BioPHP\Domain\Alignment\Interfaces\SmithWatermanAlignerInterface;
use Amelaye\BioPHP\Domain\Alignment\Interfaces\SubstitutionScoringInterface;
use Amelaye\BioPHP\Domain\Alignment\Result\PairwiseAlignmentResult;
use Amelaye\BioPHP\Domain\Alignment\Service\Pam250Scoring;
use Amelaye\BioPHP\Domain\Alignment\Service\NucleotideAmbiguityScoring;
use Amelaye\BioPHP\Domain\Alignment\Service\SmithWatermanAligner;
use Amelaye\BioPHP\Domain\Sequence\ValueObject\AminoAcidSequence;
use Amelaye\BioPHP\Domain\Sequence\ValueObject\DnaSequence;

/**
 * Class SequenceAlignmentManager : Sequence Alignment Functions
 * @package BioTools\Service
 * @author Amélie DUVERNET aka Amelaye <amelieonline@gmail.com>
 */
class SequenceAlignmentManager
{
    /**
     * Scores of the legacy minitool: +2 for a match, -1 for a mismatch, -4 per gap in DNA,
     * PAM250 and -50 per gap in proteins. An ambiguity code (N, R, Y...) facing a base it may stand for
     * scores 0: it neither confirms nor refutes it (the legacy scored N against N as a full match, so
     * two stretches of 50 N scored as well as 50 real matches).
     */
    private const DNA_MATCH     = 2;
    private const DNA_MISMATCH  = -1;
    private const DNA_AMBIGUOUS = 0;
    private const DNA_GAP      = -4;
    private const PROTEIN_GAP  = -50;

    /**
     * @var SmithWatermanAlignerInterface
     */
    private $oAligner;

    /**
     * @var SubstitutionScoringInterface
     */
    private $oPam250Scoring;

    /**
     * SequenceAlignmentManager constructor.
     * The alignment itself is done by BioPHP's Smith-Waterman aligner.
     * @param  Pam250MatrixDigitApiAdapter      $oPam250MatrixDigitApi
     * @param  SmithWatermanAlignerInterface|null $oAligner     Defaults to BioPHP's SmithWatermanAligner
     */
    public function __construct(
        Pam250MatrixDigitApiAdapter $oPam250MatrixDigitApi,
        ?SmithWatermanAlignerInterface $oAligner = null
    ) {
        $this->oPam250Scoring = new Pam250Scoring($oPam250MatrixDigitApi);
        $this->oAligner       = $oAligner ?? new SmithWatermanAligner();
    }

    /**
     * Local alignment of two DNA sequences
     * @param       string      $sSequenceA     First sequence to analyse
     * @param       string      $sSequenceB     Second sequence to analyse
     * @return      array       seqa and seqb, the unaligned ends being shown in lower case against gaps, with the score
     *                          and the 1-based positions of the aligned region (see showWithFlanks)
     * @throws      \Exception
     */
    public function alignDNA($sSequenceA, $sSequenceB)
    {
        return $this->alignLocally(
            new DnaSequence(strtoupper($sSequenceA)),
            new DnaSequence(strtoupper($sSequenceB)),
            new NucleotideAmbiguityScoring(self::DNA_MATCH, self::DNA_MISMATCH, self::DNA_AMBIGUOUS),
            self::DNA_GAP
        );
    }

    /**
     * Local alignment of two proteins, scored with the PAM250 matrix
     * @param       string      $sSequenceA
     * @param       string      $sSequenceB
     * @return      array       seqa and seqb, the unaligned ends being shown in lower case against gaps, with the score
     *                          and the 1-based positions of the aligned region (see showWithFlanks)
     * @throws      \Exception
     */
    public function alignProteins($sSequenceA, $sSequenceB)
    {
        return $this->alignLocally(
            new AminoAcidSequence(strtoupper($sSequenceA)),
            new AminoAcidSequence(strtoupper($sSequenceB)),
            $this->oPam250Scoring,
            self::PROTEIN_GAP
        );
    }

    /**
     * A result without alignment, with the same keys as one with an alignment so that a template
     * can read them whatever happened
     * @param   string  $sMessage
     * @return  array
     */
    private function emptyResult(string $sMessage) : array
    {
        return [
            "seqa" => "", "seqb" => "", "score" => null,
            "startA" => null, "endA" => null, "startB" => null, "endB" => null,
            "message" => $sMessage,
        ];
    }

    /**
     * Runs the local alignment. Two situations are not errors of the user's request but results
     * to report: no similarity at all (BioPHP then refuses to build an empty alignment), and a
     * symbol the scoring matrix has no entry for (B, Z, J, O, * with PAM250). Both give empty
     * sequences and a message to display.
     * @param   AminoAcidSequence|DnaSequence   $oFirst
     * @param   AminoAcidSequence|DnaSequence   $oSecond
     * @param   SubstitutionScoringInterface    $oScoring
     * @param   int                             $iGapPenalty
     * @return  array
     */
    private function alignLocally($oFirst, $oSecond, SubstitutionScoringInterface $oScoring, int $iGapPenalty) : array
    {
        try {
            $oResult = $this->oAligner->align($oFirst, $oSecond, $oScoring, $iGapPenalty);
        } catch (InvalidAlignmentInputException $e) {
            if ($e->getMessage() === InvalidAlignmentInputException::emptyAlignedSequence()->getMessage()) {
                return $this->emptyResult("No local similarity was found between the two sequences.");
            }
            if (strpos($e->getMessage(), 'No substitution score is defined') === 0) {
                return $this->emptyResult("A symbol of the sequences is not supported by the PAM250 matrix. " . $e->getMessage());
            }
            throw $e;
        }

        return $this->showWithFlanks($oResult, $oFirst->getValue(), $oSecond->getValue());
    }

    /**
     * A local alignment only covers the best matching region: the sequence parts before and
     * after it are written around it, facing gaps, so that the whole sequences stay readable.
     * They are written in lower case, to tell them from the aligned region (upper case): they
     * are not aligned, and the "-" facing them are padding, not indels.
     * @param   PairwiseAlignmentResult     $oResult
     * @param   string                      $sSequenceA
     * @param   string                      $sSequenceB
     * @return  array       seqa, seqb, the score, and the start and end of the aligned region
     *                      in each sequence (1-based, end included); message is null
     */
    private function showWithFlanks(PairwiseAlignmentResult $oResult, string $sSequenceA, string $sSequenceB) : array
    {
        $sLeftA  = strtolower(substr($sSequenceA, 0, $oResult->getFirstStart()));
        $sLeftB  = strtolower(substr($sSequenceB, 0, $oResult->getSecondStart()));
        $sRightA = strtolower(substr($sSequenceA, $oResult->getFirstEnd() + 1));
        $sRightB = strtolower(substr($sSequenceB, $oResult->getSecondEnd() + 1));

        $iLeft  = max(strlen($sLeftA), strlen($sLeftB));
        $iRight = max(strlen($sRightA), strlen($sRightB));

        return [
            "seqa" => str_pad($sLeftA, $iLeft, "-", STR_PAD_LEFT) . $oResult->getAlignedFirst()
                . str_pad($sRightA, $iRight, "-", STR_PAD_RIGHT),
            "seqb" => str_pad($sLeftB, $iLeft, "-", STR_PAD_LEFT) . $oResult->getAlignedSecond()
                . str_pad($sRightB, $iRight, "-", STR_PAD_RIGHT),
            "score"  => $oResult->getScore(),
            "startA" => $oResult->getFirstStart() + 1,
            "endA"   => $oResult->getFirstEnd() + 1,
            "startB" => $oResult->getSecondStart() + 1,
            "endB"   => $oResult->getSecondEnd() + 1,
            "message" => null,
        ];
    }

    /**
     * Also exposed as the `compare_alignment` Twig filter, see
     * Amelaye\BioTools\Twig\BioToolsExtension.
     * Meant for the seqa / seqb of alignDNA() and alignProteins(): there the aligned region is in upper
     * case and the unaligned flanks in lower case, so a lower case residue gets no bar. Any other lower
     * case text would get none either.
     * An ambiguity code facing itself is no identity (N against N scores 0 in alignDNA()), so it gets no
     * bar either: X always; with $sMolType "DNA" every symbol but A, C, G, T and U; with "PROTEIN" B, Z,
     * J and X. Without $sMolType only X is treated so, since N or R are amino acids in a protein.
     * @param   string      $sSeqa
     * @param   string      $sSeqb
     * @param   string|null $sMolType   "DNA" or "PROTEIN", whatever the case
     * @return  string
     */
    public function compareAlignment($sSeqa, $sSeqb, $sMolType = null)
    {
        switch (strtoupper((string) $sMolType)) {
            case "DNA":
                $sAmbiguous = "/[^ACGTU]/";
                break;
            case "PROTEIN":
                $sAmbiguous = "/[BZJX]/";
                break;
            default:
                $sAmbiguous = "/X/";
        }
        $sCompare = "";
        for($i = 0; $i < strlen($sSeqa); $i++) {
            $sBaseA = substr($sSeqa, $i, 1);
            // the lower case flanks of a local alignment are not aligned: no bar under them
            if($sBaseA == substr($sSeqb,$i,1) && !ctype_lower($sBaseA) && !preg_match($sAmbiguous, $sBaseA)) {
                $sCompare .= "|";
            } else {
                $sCompare.=" ";
            }
        }
        return $sCompare;
    }
}