<?php
namespace Tests\Form;

use Amelaye\BioTools\Form\SkewsType;

class SkewsTypeTest extends AbstractFormTypeTestCase
{
    public function testSequenceIsUppercasedAndStrippedOnSubmit()
    {
        $form = $this->factory->create(SkewsType::class);

        $form->submit([
            'name' => 'My Seq',
            'seq' => 'act g 123 gtc-a',
            'window' => 5000,
            'oligo_len' => 2,
            'strands' => 1,
        ]);

        $this->assertEquals('ACTGGTCA', $form->getData()['seq']);
    }

    public function testEmptyNameDefaultsToSequence()
    {
        $form = $this->factory->create(SkewsType::class);

        $form->submit([
            'name' => '',
            'seq' => str_repeat('ACGT', 2000),
            'window' => 5000,
            'oligo_len' => 2,
            'strands' => 1,
        ]);

        $this->assertEquals('sequence', $form->getData()['name']);
    }

    public function testWindow2OverridesWindowWhenProvided()
    {
        $form = $this->factory->create(SkewsType::class);

        $form->submit([
            'name' => 'seq',
            'seq' => str_repeat('ACGT', 500),
            'window' => 5000,
            'window2' => '500',
            'oligo_len' => 2,
            'strands' => 1,
        ]);

        $this->assertEquals('500', $form->getData()['window']);
    }

    public function testWindow2OutOfRangeIsInvalid()
    {
        $form = $this->factory->create(SkewsType::class);

        $form->submit([
            'name' => 'seq',
            'seq' => str_repeat('ACGT', 2000),
            'window' => 5000,
            'window2' => '50',
            'oligo_len' => 2,
            'strands' => 1,
        ]);

        $this->assertFalse($form->isValid());
    }

    public function testSequenceTooSmallForWindowIsInvalid()
    {
        $form = $this->factory->create(SkewsType::class);

        $form->submit([
            'name' => 'seq',
            'seq' => 'ACGTACGTACGT',
            'window' => 5000,
            'oligo_len' => 2,
            'strands' => 1,
        ]);

        $this->assertFalse($form->isValid());
    }

    public function testValidSubmissionIsValid()
    {
        $form = $this->factory->create(SkewsType::class);

        $form->submit([
            'name' => 'seq',
            'seq' => str_repeat('ACGT', 2000),
            'window' => 5000,
            'oligo_len' => 2,
            'strands' => 1,
        ]);

        $this->assertTrue($form->isValid());
    }
}
