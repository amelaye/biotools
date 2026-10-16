<?php
/**
 * Inspired by BioPHP's project biophp.org
 * Created 28 february 2019
 * Last modified 14 september 2026
 * RIP Pasha, gone 27 february 2019 =^._.^= ∫
 */
namespace Amelaye\BioTools\Service;

use Amelaye\BioPHP\Api\Interfaces\Pam250MatrixDigitApiAdapter;
use Amelaye\BioPHP\Domain\Alignment\Interfaces\SmithWatermanAlignerInterface;
use Amelaye\BioPHP\Domain\Alignment\Interfaces\SubstitutionScoringInterface;
use Amelaye\BioPHP\Domain\Alignment\Result\PairwiseAlignmentResult;
use Amelaye\BioPHP\Domain\Alignment\Service\Pam250Scoring;
use Amelaye\BioPHP\Domain\Alignment\Service\SimpleMatchMismatchScoring;
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
     * PAM250 and -50 per gap in proteins
     */
    private const DNA_MATCH    = 2;
    private const DNA_MISMATCH = -1;
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
     * @return      array       ["seqa" => ..., "seqb" => ...], the unaligned ends being shown against gaps
     * @throws      \Exception
     */
    public function alignDNA($sSequenceA, $sSequenceB)
    {
        $oResult = $this->oAligner->align(
            new DnaSequence(strtoupper($sSequenceA)),
            new DnaSequence(strtoupper($sSequenceB)),
            new SimpleMatchMismatchScoring(self::DNA_MATCH, self::DNA_MISMATCH),
            self::DNA_GAP
        );

        return $this->showWithFlanks($oResult, strtoupper($sSequenceA), strtoupper($sSequenceB));
    }

    /**
     * Local alignment of two proteins, scored with the PAM250 matrix
     * @param       string      $sSequenceA
     * @param       string      $sSequenceB
     * @return      array       ["seqa" => ..., "seqb" => ...], the unaligned ends being shown against gaps
     * @throws      \Exception
     */
    public function alignProteins($sSequenceA, $sSequenceB)
    {
        $oResult = $this->oAligner->align(
            new AminoAcidSequence(strtoupper($sSequenceA)),
            new AminoAcidSequence(strtoupper($sSequenceB)),
            $this->oPam250Scoring,
            self::PROTEIN_GAP
        );

        return $this->showWithFlanks($oResult, strtoupper($sSequenceA), strtoupper($sSequenceB));
    }

    /**
     * A local alignment only covers the best matching region: the sequence parts before and
     * after it are written around it, facing gaps, so that the whole sequences stay readable
     * @param   PairwiseAlignmentResult     $oResult
     * @param   string                      $sSequenceA
     * @param   string                      $sSequenceB
     * @return  array
     */
    private function showWithFlanks(PairwiseAlignmentResult $oResult, string $sSequenceA, string $sSequenceB) : array
    {
        $sLeftA  = substr($sSequenceA, 0, $oResult->getFirstStart());
        $sLeftB  = substr($sSequenceB, 0, $oResult->getSecondStart());
        $sRightA = substr($sSequenceA, $oResult->getFirstEnd() + 1);
        $sRightB = substr($sSequenceB, $oResult->getSecondEnd() + 1);

        $iLeft  = max(strlen($sLeftA), strlen($sLeftB));
        $iRight = max(strlen($sRightA), strlen($sRightB));

        return [
            "seqa" => str_pad($sLeftA, $iLeft, "-", STR_PAD_LEFT) . $oResult->getAlignedFirst()
                . str_pad($sRightA, $iRight, "-", STR_PAD_RIGHT),
            "seqb" => str_pad($sLeftB, $iLeft, "-", STR_PAD_LEFT) . $oResult->getAlignedSecond()
                . str_pad($sRightB, $iRight, "-", STR_PAD_RIGHT),
        ];
    }

    /**
     * Also exposed as the `compare_alignment` Twig filter, see
     * Amelaye\BioTools\Twig\BioToolsExtension.
     * @param   string  $sSeqa
     * @param   string  $sSeqb
     * @return  string
     */
    public function compareAlignment($sSeqa,$sSeqb)
    {
        $sCompare = "";
        for($i = 0; $i < strlen($sSeqa); $i++) {
            if(substr($sSeqa,$i,1) == substr($sSeqb,$i,1)) {
                $sCompare .= "|";
            } else {
                $sCompare.=" ";
            }
        }
        return $sCompare;
    }
}