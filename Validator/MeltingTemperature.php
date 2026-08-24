<?php
/**
 * Recognizes if the Sequence is valid
 * @author Amélie DUVERNET akka Amelaye
 * Freely inspired by BioPHP's project biophp.org
 * Created 29 june 2019
 * Last modified 24 august 2026
 */
namespace Amelaye\BioTools\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * Class SequenceRecognition
 * @package AppBundle\Validator
 * @Annotation
 */
class MeltingTemperature extends Constraint
{
    public $message = "The oligonucleotide is not valid.";

    public function validatedBy(): string
    {
        return \get_class($this).'Validator';
    }
}