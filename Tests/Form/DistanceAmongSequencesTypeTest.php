<?php
namespace Tests\Form;

use Amelaye\BioTools\Form\DistanceAmongSequencesType;

class DistanceAmongSequencesTypeTest extends AbstractFormTypeTestCase
{
    public function testEverythingBeforeFirstFastaHeaderIsStrippedAndCarriageReturnsRemoved()
    {
        $form = $this->factory->create(DistanceAmongSequencesType::class);

        $form->submit([
            'seq' => "some preamble\r\n>seq1\r\nACGT\r\n>seq2\r\nTTTT",
            'method' => 'euclidean',
            'len' => '4',
        ]);

        $this->assertEquals(">seq1\nACGT\n>seq2\nTTTT", $form->getData()['seq']);
    }

    public function testSequenceWithoutFastaHeaderIsLeftUnchanged()
    {
        $form = $this->factory->create(DistanceAmongSequencesType::class);

        $form->submit([
            'seq' => "ACGT\r\nTTTT",
            'method' => 'euclidean',
            'len' => '4',
        ]);

        $this->assertEquals("ACGT\nTTTT", $form->getData()['seq']);
    }

    public function testSequenceOverTwoMillionCharactersIsInvalid()
    {
        $form = $this->factory->create(DistanceAmongSequencesType::class);

        $form->submit([
            'seq' => ">seq1\n" . str_repeat('A', 2000001),
            'method' => 'euclidean',
            'len' => '4',
        ]);

        $this->assertFalse($form->isValid());
    }

    public function testValidSubmissionIsValid()
    {
        $form = $this->factory->create(DistanceAmongSequencesType::class);

        $form->submit([
            'seq' => ">seq1\nACGT\n>seq2\nTTTT",
            'method' => 'euclidean',
            'len' => '4',
        ]);

        $this->assertTrue($form->isValid());
    }
}
