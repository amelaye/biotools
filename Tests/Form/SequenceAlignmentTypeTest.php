<?php
namespace Tests\Form;

use Amelaye\BioTools\Form\SequenceAlignmentType;

class SequenceAlignmentTypeTest extends AbstractFormTypeTestCase
{
    public function testBothSequencesAreNormalized()
    {
        $form = $this->factory->create(SequenceAlignmentType::class);

        $form->submit([
            'id1' => 'Seq 1',
            'sequence' => 'acg 123 u-x',
            'id2' => 'Seq 2',
            'sequence2' => 'tgc 456 u-x',
        ]);

        $this->assertEquals('ACGTN', $form->getData()['sequence']);
        $this->assertEquals('TGCTN', $form->getData()['sequence2']);
    }

    public function testCombinedSequencesOverLimitAreInvalid()
    {
        $form = $this->factory->create(SequenceAlignmentType::class);

        $form->submit([
            'id1' => 'Seq 1',
            'sequence' => str_repeat('A', 200),
            'id2' => 'Seq 2',
            'sequence2' => str_repeat('T', 101),
        ]);

        $this->assertFalse($form->isValid());
    }

    public function testCombinedSequencesWithinLimitAreValid()
    {
        $form = $this->factory->create(SequenceAlignmentType::class);

        $form->submit([
            'id1' => 'Seq 1',
            'sequence' => str_repeat('A', 150),
            'id2' => 'Seq 2',
            'sequence2' => str_repeat('T', 150),
        ]);

        $this->assertTrue($form->isValid());
    }
}
