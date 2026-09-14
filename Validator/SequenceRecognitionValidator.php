<?php
/**
 * Recognizes if the Sequence is valid
 * @author Amélie DUVERNET akka Amelaye
 * Freely inspired by BioPHP's project biophp.org
 * Created 24 june 2019
 * Last modified 24 august 2026
 */
namespace Amelaye\BioTools\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class SequenceRecognitionValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        $sSequence = strtoupper($value);
        $sSequence = preg_replace("/\\W|\\d/","", $sSequence);
        $sSequence = preg_replace("/X/","N", $sSequence);

        if($sSequence == "") {
            $this->context->buildViolation($constraint->messageEmpty)
                ->addViolation();
        }

        $iLenSeq = strlen($sSequence);
        $iNumberAtgc = $this->countACGT($sSequence);
        $iNumberYrwskmdvhb = $this->countYRWSKMDVHB($sSequence);
        $iNumber = $iNumberAtgc + $iNumberYrwskmdvhb + substr_count($sSequence,"N");

        if ($iNumber != $iLenSeq) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }


    /**
     * Will count number of A, C, G and T bases in the sequence
     * @param   string  $sSequence  is the sequence
     * @return  int
     * @throws \Exception
     */
    public function countACGT($sSequence)
    {
        try {
            $iCg = substr_count($sSequence,"A")
                + substr_count($sSequence,"T")
                + substr_count($sSequence,"G")
                + substr_count($sSequence,"C");
            return $iCg;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Will count number of degenerate nucleotides (Y, R, W, S, K, MD, V, H and B) in the sequence
     * @param   string $sC
     * @return  int
     * @throws \Exception
     */
    public function countYRWSKMDVHB($sC){
        try {
            $iCg = substr_count($sC,"Y")
                + substr_count($sC,"R")
                + substr_count($sC,"W")
                + substr_count($sC,"S")
                + substr_count($sC,"K")
                + substr_count($sC,"M")
                + substr_count($sC,"D")
                + substr_count($sC,"V")
                + substr_count($sC,"H")
                + substr_count($sC,"B");
            return $iCg;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * @param $sC
     * @return int
     * @throws \Exception
     */
    public function countCG($sC)
    {
        try {
            $iCg = substr_count($sC,"G")
                + substr_count($sC,"C");
            return $iCg;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }
}