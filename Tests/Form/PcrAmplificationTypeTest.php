<?php
namespace Tests\Form;

use Amelaye\BioTools\Form\PcrAmplificationType;

class PcrAmplificationTypeTest extends AbstractFormTypeTestCase
{
    public function testSequenceAndBothPrimersAreUppercasedAndStripped()
    {
        $form = $this->factory->create(PcrAmplificationType::class);

        $form->submit([
            'sequence' => 'act 123 g-t',
            'primer1' => 'gag 456 cag-ttgg',
            'primer2' => 'gcc 789 gct-gggg',
            'length' => '3000',
        ]);

        $this->assertEquals('ACTGT', $form->getData()['sequence']);
        $this->assertEquals('GAGCAGTTGG', $form->getData()['primer1']);
        $this->assertEquals('GCCGCTGGGG', $form->getData()['primer2']);
    }
}
