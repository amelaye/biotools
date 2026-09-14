<?php
namespace Tests\Form;

use Amelaye\BioPHP\Api\TripletSpecieApi;
use Amelaye\BioTools\Form\ProteinToDnaType;
use PHPUnit\Framework\MockObject\MockObject;

class ProteinToDnaTypeTest extends AbstractFormTypeTestCase
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
        return [new ProteinToDnaType($this->tripletSpeciesMock)];
    }

    public function testSequenceIsUppercasedAndFilteredToAminoAcidLetters()
    {
        $form = $this->factory->create(ProteinToDnaType::class);

        $form->submit([
            'sequence' => 'flim 123 vsp-tay*z',
            'genetic_code' => 'standard',
        ]);

        // "z" is not one of the 20 amino acid letters + X + *, stripped just like digits/dashes
        $this->assertEquals('FLIMVSPTAY*', $form->getData()['sequence']);
    }
}
