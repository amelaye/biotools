<?php
namespace Tests\Form;

use Amelaye\BioPHP\Api\TripletSpecieApi;
use Amelaye\BioTools\Form\DnaToProteinType;
use PHPUnit\Framework\MockObject\MockObject;

class DnaToProteinTypeTest extends AbstractFormTypeTestCase
{
    /**
     * @var TripletSpecieApi&MockObject
     */
    protected $tripletSpeciesMock;

    protected function setUp(): void
    {
        require __DIR__ . '/../Service/samples/TripletsSpecies.php';

        $this->tripletSpeciesMock = $this->getMockBuilder(TripletSpecieApi::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getTriplets'])
            ->getMock();
        $this->tripletSpeciesMock->method("getTriplets")->willReturn($aTripletSpeciesObjects);

        parent::setUp();
    }

    protected function getTypes(): array
    {
        return [new DnaToProteinType($this->tripletSpeciesMock)];
    }

    public function testSequenceStripsNonWordAndDigitCharacters()
    {
        $form = $this->factory->create(DnaToProteinType::class);

        $form->submit([
            'sequence' => "GGA GTG-A 123\rGGG",
            'frames' => '3',
            'protsize' => '50',
            'genetic_code' => 'standard',
        ]);

        $this->assertEquals('GGAGTGAGGG', $form->getData()['sequence']);
    }

    public function testMycodeIsNormalizedWhenUseMycodeIsChecked()
    {
        $form = $this->factory->create(DnaToProteinType::class);

        // mycode is matched case-sensitively against uppercase amino-acid letters only,
        // lowercase letters, digits and spaces are all stripped just the same
        $mycode = "FFLLSSSSYY**CC*WLLLLPPPPhhqqRRRRIIIMTTTTNNKKSSRRVVVVAAAADDEEGGGG 123";

        $form->submit([
            'sequence' => 'GGAGTGAGGG',
            'frames' => '3',
            'protsize' => '50',
            'genetic_code' => 'standard',
            'usemycode' => '1',
            'mycode' => $mycode,
        ]);

        $this->assertEquals(
            'FFLLSSSSYY**CC*WLLLLPPPPRRRRIIIMTTTTNNKKSSRRVVVVAAAADDEEGGGG',
            $form->getData()['mycode']
        );
    }

    public function testMycodeOfWrongLengthIsInvalidWhenUseMycodeIsChecked()
    {
        $form = $this->factory->create(DnaToProteinType::class);

        $form->submit([
            'sequence' => 'GGAGTGAGGG',
            'frames' => '3',
            'protsize' => '50',
            'genetic_code' => 'standard',
            'usemycode' => '1',
            'mycode' => 'TOOSHORT',
        ]);

        $this->assertFalse($form->isValid());
    }

    /**
     * legacy only validates the custom code length "when usage of custom genetic code is
     * requested" - a wrong-length mycode must not block submission otherwise
     */
    public function testMycodeLengthIsIgnoredWhenUseMycodeIsUnchecked()
    {
        $form = $this->factory->create(DnaToProteinType::class);

        $form->submit([
            'sequence' => 'GGAGTGAGGG',
            'frames' => '3',
            'protsize' => '50',
            'genetic_code' => 'standard',
            'mycode' => 'TOOSHORT',
        ]);

        $this->assertTrue($form->isValid());
    }

    public function testProtsizeBelowTenIsInvalid()
    {
        $form = $this->factory->create(DnaToProteinType::class);

        $form->submit([
            'sequence' => 'GGAGTGAGGG',
            'frames' => '3',
            'protsize' => '5',
            'genetic_code' => 'standard',
        ]);

        $this->assertFalse($form->isValid());
    }
}
