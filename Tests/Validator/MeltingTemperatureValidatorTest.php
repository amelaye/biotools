<?php
/**
 * Tests of the oligonucleotide constraint used by the "Melting Temperature" minitool.
 * legacy (melting_temperature.php) accepts the IUPAC degenerate nucleotides in the
 * primer - "preg_replace("/\W|[^ATGCYRWSKMDVHBN]|\d/","",$primer)" - since the "Basic
 * Tm" method explicitly supports them (it is the nearest-neighbor/base-stacking method,
 * in MeltingTemperatureManager::tmBaseStacking(), that has no thermodynamic values for
 * degenerate pairs and instead returns a "non computed" message for those - a separate,
 * service-level concern from this form-level constraint). Length is only checked when
 * the primer is non-empty: "if ($primer!="" and (strlen($primer)<6 or strlen($primer)>50))".
 */
namespace Tests\MinitoolsBundle\Validator;

use Amelaye\BioTools\Validator\MeltingTemperature;
use Amelaye\BioTools\Validator\MeltingTemperatureValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class MeltingTemperatureValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): MeltingTemperatureValidator
    {
        return new MeltingTemperatureValidator();
    }

    /**
     * A primer of 6 to 50 bases, made only of valid NC-IUBMB codes (plain or
     * degenerate), is accepted
     */
    #[DataProvider('providerValidOligonucleotides')]
    public function testValidOligonucleotideRaisesNoViolation($sOligonucleotide)
    {
        $this->validator->validate($sOligonucleotide, new MeltingTemperature());

        $this->assertNoViolation();
    }

    public static function providerValidOligonucleotides()
    {
        return [
            "the four bases, six long" => ["ACGTAC"],
            "a real primer"            => ["GGCAGATTCCCCCTAGACCCGCCCGCACCATG"],
            "a degenerated nucleotide, six long" => ["ACGTNY"],
            "every degenerate code, long enough" => ["YRWSKMDVHBN"],
            "exactly 6 bases (lower bound)"  => ["ACGTAC"],
            "exactly 50 bases (upper bound)" => [str_repeat("ACGT", 12) . "AC"],
            // nothing to count and nothing to compare it to: the constraint stays silent
            "an empty sequence" => [""],
        ];
    }

    /**
     * Anything that is not a plain or degenerate NC-IUBMB code is rejected
     */
    #[DataProvider('providerInvalidOligonucleotides')]
    public function testInvalidOligonucleotideRaisesAViolation($sOligonucleotide)
    {
        $oConstraint = new MeltingTemperature();

        $this->validator->validate($sOligonucleotide, $oConstraint);

        $this->buildViolation($oConstraint->message)->assertRaised();
    }

    public static function providerInvalidOligonucleotides()
    {
        return [
            "a protein sequence"    => ["MKWVTFISLL"],
            "an unknown letter"     => ["ACGTACZZZZ"],
            "a digit"               => ["ACGTAC4"],
            "a space"               => ["ACGTAC ACGTAC"],
            "an end of line"        => ["ACGTAC\nACGTAC"],
            // substr_count() is case sensitive, so a lower case primer is refused
            "a lower case primer"   => ["acgtacgtac"],
            "a mixed case primer"   => ["ACGTacgtACGT"],
        ];
    }

    /**
     * legacy only enforces the 6-50 bp range when a primer was actually submitted
     */
    #[DataProvider('providerWrongLengthOligonucleotides')]
    public function testOligonucleotideOutsideLengthRangeRaisesTheLengthViolation($sOligonucleotide)
    {
        $oConstraint = new MeltingTemperature();

        $this->validator->validate($sOligonucleotide, $oConstraint);

        $this->buildViolation($oConstraint->lengthMessage)->assertRaised();
    }

    public static function providerWrongLengthOligonucleotides()
    {
        return [
            "one base short of the minimum" => ["ACGTA"],
            "a single base"                  => ["A"],
            "one base over the maximum"      => [str_repeat("ACGT", 12) . "ACG"], // 51
            "way too long"                   => [str_repeat("ACGT", 30)], // 120
        ];
    }

    public function testCountAcgt()
    {
        $oValidator = new MeltingTemperatureValidator();

        $this->assertEquals(4, $oValidator->countACGT("ACGT"));
        $this->assertEquals(8, $oValidator->countACGT("ACGTACGT"));
        $this->assertEquals(0, $oValidator->countACGT(""));
        $this->assertEquals(0, $oValidator->countACGT("YRWS"));
        // the degenerated nucleotides and the N are not counted
        $this->assertEquals(4, $oValidator->countACGT("ACGTNYRW"));
        // and neither is a lower case base
        $this->assertEquals(0, $oValidator->countACGT("acgt"));
    }

    public function testCountYrwskmdvhb()
    {
        $oValidator = new MeltingTemperatureValidator();

        $this->assertEquals(10, $oValidator->countYRWSKMDVHB("YRWSKMDVHB"));
        $this->assertEquals(0, $oValidator->countYRWSKMDVHB("ACGT"));
        $this->assertEquals(0, $oValidator->countYRWSKMDVHB(""));
        // the N is not one of them, it is counted apart by the validator
        $this->assertEquals(0, $oValidator->countYRWSKMDVHB("NNNN"));
        $this->assertEquals(2, $oValidator->countYRWSKMDVHB("ACGTYRACGT"));
    }

    /**
     * The constraint points at its own validator, and carries the messages the minitool
     * shows
     */
    public function testConstraint()
    {
        $oConstraint = new MeltingTemperature();

        $this->assertEquals(MeltingTemperatureValidator::class, $oConstraint->validatedBy());
        $this->assertEquals("The oligonucleotide is not valid.", $oConstraint->message);
        $this->assertEquals("Length of primer must be 6-50 bp.", $oConstraint->lengthMessage);
    }
}
