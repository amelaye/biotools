<?php
namespace Tests\Form;

use Amelaye\BioTools\Form\FormulasType;

class FormulasTypeTest extends AbstractFormTypeTestCase
{
    public function testSequenceIsUppercasedAndFilteredToNucleicAcidLetters()
    {
        $form = $this->factory->create(FormulasType::class);

        $form->submit([
            'formula' => 'mw_dsdna',
            'sequence' => 'acgt 123 u-z',
        ]);

        // "z" is not a valid nucleic acid letter (plain or degenerate), stripped like digits/dashes
        $this->assertEquals('ACGTU', $form->getData()['sequence']);
    }
}
