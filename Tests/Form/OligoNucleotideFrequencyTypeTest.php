<?php
namespace Tests\Form;

use Amelaye\BioTools\Form\OligoNucleotideFrequencyType;

class OligoNucleotideFrequencyTypeTest extends AbstractFormTypeTestCase
{
    public function testSequenceIsUppercasedAndStripped()
    {
        $form = $this->factory->create(OligoNucleotideFrequencyType::class);

        $form->submit([
            'sequence' => str_repeat('act 123 g-t', 5),
            'len' => '2',
            'strands' => '1',
        ]);

        $this->assertEquals(str_repeat('ACTGT', 5), $form->getData()['sequence']);
    }

    public function testSequenceShorterThanFourPowLenIsInvalid()
    {
        $form = $this->factory->create(OligoNucleotideFrequencyType::class);

        $form->submit([
            // 4^3 = 64, this sequence is only 8 bp long
            'sequence' => 'ACGTACGT',
            'len' => '3',
            'strands' => '1',
        ]);

        $this->assertFalse($form->isValid());
    }

    public function testSequenceAtLeastFourPowLenIsValid()
    {
        $form = $this->factory->create(OligoNucleotideFrequencyType::class);

        $form->submit([
            // 4^2 = 16
            'sequence' => str_repeat('ACGT', 4),
            'len' => '2',
            'strands' => '1',
        ]);

        $this->assertTrue($form->isValid());
    }

    public function testSequenceOverOneMillionCharactersIsInvalid()
    {
        $form = $this->factory->create(OligoNucleotideFrequencyType::class);

        $form->submit([
            'sequence' => str_repeat('A', 1000001),
            'len' => '2',
            'strands' => '1',
        ]);

        $this->assertFalse($form->isValid());
    }
}
