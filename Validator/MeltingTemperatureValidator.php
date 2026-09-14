<?php
/**
 * Recognizes if the Sequence is valid
 * @author Amélie DUVERNET aka Amelaye
 * Freely inspired by BioPHP's project biophp.org
 * Created 29 june 2019
 * Last modified 14 september 2026
 */
namespace Amelaye\BioTools\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class MeltingTemperatureValidator extends ConstraintValidator
{
    /**
     * legacy (melting_temperature.php) accepts the IUPAC degenerate nucleotides too -
     * "preg_replace("/\W|[^ATGCYRWSKMDVHBN]|\d/","",$primer)" - not only plain A/C/G/T,
     * and only enforces the 6-50 bp length range when the primer is non-empty:
     * "if ($primer!="" and (strlen($primer)<6 or strlen($primer)>50)){die(...)}"
     */
    public function validate($value, Constraint $constraint)
    {
        $sValue = (string) $value;

        $iValid = $this->countACGT($sValue) + $this->countYRWSKMDVHB($sValue) + substr_count($sValue, "N");
        if ($iValid != strlen($sValue)) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
            return;
        }

        if ($sValue != "" && (strlen($sValue) < 6 || strlen($sValue) > 50)) {
            $this->context->buildViolation($constraint->lengthMessage)
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
            $cg = substr_count($sSequence,"A")
                + substr_count($sSequence,"T")
                + substr_count($sSequence,"G")
                + substr_count($sSequence,"C");
            return $cg;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Will count number of degenerate nucleotides (Y, R, W, S, K, M, D, V, H and B) in
     * the sequence
     * @param   string  $sSequence
     * @return  int
     * @throws \Exception
     */
    public function countYRWSKMDVHB($sSequence)
    {
        try {
            $cg = substr_count($sSequence,"Y")
                + substr_count($sSequence,"R")
                + substr_count($sSequence,"W")
                + substr_count($sSequence,"S")
                + substr_count($sSequence,"K")
                + substr_count($sSequence,"M")
                + substr_count($sSequence,"D")
                + substr_count($sSequence,"V")
                + substr_count($sSequence,"H")
                + substr_count($sSequence,"B");
            return $cg;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }
}