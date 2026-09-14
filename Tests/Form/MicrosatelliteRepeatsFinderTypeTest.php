<?php
namespace Tests\Form;

use Amelaye\BioTools\Form\MicrosatelliteRepeatsFinderType;

class MicrosatelliteRepeatsFinderTypeTest extends AbstractFormTypeTestCase
{
    public function testSequenceIsUppercasedAndStripped()
    {
        $form = $this->factory->create(MicrosatelliteRepeatsFinderType::class);

        $form->submit([
            'sequence' => 'act 123 g-t',
            'min' => '2',
            'max' => '6',
            'min_repeats' => '3',
            'length_of_MR' => '6',
            'mismatch' => '0',
        ]);

        $this->assertEquals('ACTGT', $form->getData()['sequence']);
    }
}
