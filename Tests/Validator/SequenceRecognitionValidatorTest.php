<?php
/**
 * Tests of the sequence constraint shared by the minitools taking a nucleotide sequence.
 * The constraint accepts the NC-IUBMB codes: the four bases, the degenerated nucleotides
 * Y R W S K M D V H B and the unknown N. Before checking, it upper cases the sequence,
 * drops whatever is not a letter - so a sequence pasted with its line breaks, its spaces
 * or its position numbers goes through - and reads X as an N.
 */
namespace Tests\MinitoolsBundle\Validator;

use Amelaye\BioTools\Validator\SequenceRecognition;
use Amelaye\BioTools\Validator\SequenceRecognitionValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class SequenceRecognitionValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): SequenceRecognitionValidator
    {
        return new SequenceRecognitionValidator();
    }

    #[DataProvider('providerValidSequences')]
    public function testValidSequenceRaisesNoViolation($sSequence)
    {
        $this->validator->validate($sSequence, new SequenceRecognition());

        $this->assertNoViolation();
    }

    public static function providerValidSequences()
    {
        return [
            "the four bases"             => ["ACGT"],
            "a real sequence"            => ["GGCAGATTCCCCCTAGACCCGCCCGCACCATGGTCAGGCATGCCCCTCCTCATCGCTGGG"],
            "the degenerated nucleotides" => ["YRWSKMDVHB"],
            "an unknown nucleotide"      => ["ACGTNNNNACGT"],
            // X is read as an N
            "an X"                       => ["ACGTXXXX"],
            // the sequence is upper cased first
            "a lower case sequence"      => ["acgtyrwskm"],
            "a mixed case sequence"      => ["AcGtNnXx"],
            // everything that is not a letter is dropped before checking
            "a sequence with spaces"     => ["ACGT ACGT ACGT"],
            "a sequence with line breaks" => ["ACGT\nACGT\r\nACGT"],
            "a sequence with tabs"       => ["ACGT\tACGT"],
            "a numbered sequence"        => ["1 ACGTACGT 8"],
            "a sequence with dashes"     => ["ACGT-ACGT--ACGT"],
        ];
    }

    /**
     * A letter outside of the NC-IUBMB codes is refused
     */
    #[DataProvider('providerInvalidSequences')]
    public function testInvalidSequenceRaisesAViolation($sSequence)
    {
        $oConstraint = new SequenceRecognition();

        $this->validator->validate($sSequence, $oConstraint);

        $this->buildViolation($oConstraint->message)->assertRaised();
    }

    public static function providerInvalidSequences()
    {
        return [
            "an unknown letter"      => ["ACGTZ"],
            "a protein sequence"     => ["MKWVTFISLL"],
            "a U of an RNA sequence" => ["ACGU"],
            // the underscore is a word character, so it survives the cleaning
            "an underscore"          => ["ACGT_ACGT"],
        ];
    }

    /**
     * A sequence holding nothing to read gets the dedicated "no sequence" message
     */
    #[DataProvider('providerEmptySequences')]
    public function testEmptySequenceRaisesTheEmptyViolation($sSequence)
    {
        $oConstraint = new SequenceRecognition();

        $this->validator->validate($sSequence, $oConstraint);

        $this->buildViolation($oConstraint->messageEmpty)->assertRaised();
    }

    public static function providerEmptySequences()
    {
        return [
            "an empty string"        => [""],
            "only spaces"            => ["   "],
            "only line breaks"       => ["\n\n"],
            "only digits"            => ["12345"],
            "only punctuation"       => ["---...>>>"],
            // a FASTA header alone holds no sequence, but its letters are read as one
            "only separators"        => [" \t \r\n "],
        ];
    }

    public function testCountAcgt()
    {
        $oValidator = new SequenceRecognitionValidator();

        $this->assertEquals(4, $oValidator->countACGT("ACGT"));
        $this->assertEquals(0, $oValidator->countACGT(""));
        $this->assertEquals(0, $oValidator->countACGT("YRWSKMDVHB"));
        $this->assertEquals(4, $oValidator->countACGT("ACGTNYRW"));
        $this->assertEquals(10, $oValidator->countACGT("AAAAATTTTT"));
    }

    public function testCountYrwskmdvhb()
    {
        $oValidator = new SequenceRecognitionValidator();

        $this->assertEquals(10, $oValidator->countYRWSKMDVHB("YRWSKMDVHB"));
        $this->assertEquals(0, $oValidator->countYRWSKMDVHB("ACGT"));
        $this->assertEquals(0, $oValidator->countYRWSKMDVHB(""));
        // the N is not one of them, it is counted apart by the validator
        $this->assertEquals(0, $oValidator->countYRWSKMDVHB("NNNN"));
        $this->assertEquals(2, $oValidator->countYRWSKMDVHB("ACGTYRACGT"));
    }

    /**
     * The G+C count the minitools share, on which the GC content and the melting
     * temperature are built
     */
    public function testCountCg()
    {
        $oValidator = new SequenceRecognitionValidator();

        $this->assertEquals(2, $oValidator->countCG("ACGT"));
        $this->assertEquals(0, $oValidator->countCG("ATAT"));
        $this->assertEquals(10, $oValidator->countCG("GCGCGCGCGC"));
        $this->assertEquals(0, $oValidator->countCG(""));
    }

    /**
     * The constraint points at its own validator, and carries the two messages the
     * minitools show
     */
    public function testConstraint()
    {
        $oConstraint = new SequenceRecognition();

        $this->assertEquals(SequenceRecognitionValidator::class, $oConstraint->validatedBy());
        $this->assertEquals(
            "Sequence is not valid. At least one letter in the sequence is unknown (not a NC-UIBMB valid code)",
            $oConstraint->message
        );
        $this->assertEquals("No sequence available", $oConstraint->messageEmpty);
    }
}
