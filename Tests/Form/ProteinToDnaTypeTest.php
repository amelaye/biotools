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
            'sequence' => 'flim 123 vsp-tay*',
            'genetic_code' => 'standard',
        ]);

        $this->assertEquals('FLIMVSPTAY*', $form->getData()['sequence']);
        $this->assertTrue($form->isValid());
    }

    /**
     * A residue with no codon (here Z) is refused, not silently removed
     */
    public function testAResidueWithoutCodonIsRefused()
    {
        $form = $this->factory->create(ProteinToDnaType::class);

        $form->submit([
            'sequence' => 'flim z',
            'genetic_code' => 'standard',
        ]);

        $this->assertEquals('FLIMZ', $form->getData()['sequence']);
        $this->assertFalse($form->isValid());
        $this->assertStringContainsString('have no codon', (string) $form->getErrors(true));
    }
}
