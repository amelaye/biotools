<?php
namespace Tests\Form;

use Amelaye\BioTools\Form\ChaosGameRepresentationType;

class ChaosGameRepresentationTypeTest extends AbstractFormTypeTestCase
{
    public function testSequenceIsUppercasedAndNonWordDigitCharactersRemoved()
    {
        $form = $this->factory->create(ChaosGameRepresentationType::class);

        $form->submit([
            'seq_name' => 'My Seq',
            'size' => 'auto',
            's' => '2',
            'len' => '2',
            'seq' => str_repeat('act g-t 123', 5),
        ]);

        $this->assertEquals(str_repeat('ACTGT', 5), $form->getData()['seq']);
    }

    public function testSequenceShorterThanFiftyBpIsInvalid()
    {
        $form = $this->factory->create(ChaosGameRepresentationType::class);

        $form->submit([
            'seq_name' => 'My Seq',
            'size' => 'auto',
            's' => '2',
            'len' => '2',
            'seq' => 'ACGT',
        ]);

        $this->assertFalse($form->isValid());
    }

    public function testSequenceOverFiveMillionCharactersIsInvalid()
    {
        $form = $this->factory->create(ChaosGameRepresentationType::class);

        $form->submit([
            'seq_name' => 'My Seq',
            'size' => 'auto',
            's' => '2',
            'len' => '2',
            'seq' => str_repeat('A', 5000001),
        ]);

        $this->assertFalse($form->isValid());
    }

    public function testValidSubmissionIsValid()
    {
        $form = $this->factory->create(ChaosGameRepresentationType::class);

        $form->submit([
            'seq_name' => 'My Seq',
            'size' => 'auto',
            's' => '2',
            'len' => '2',
            'seq' => str_repeat('ACGT', 20),
        ]);

        $this->assertTrue($form->isValid());
    }
}
