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

    /**
     * In a protein U is selenocysteine and X an unknown residue: neither may become T or N
     */
    public function testProteinsKeepTheirUAndX()
    {
        $form = $this->factory->create(SequenceAlignmentType::class);

        $form->submit([
            'id1' => 'Seq 1',
            'sequence' => 'mkxxlu',
            'id2' => 'Seq 2',
            'sequence2' => 'mknnlu',
        ]);

        $this->assertEquals('MKXXLU', $form->getData()['sequence']);
        $this->assertEquals('MKNNLU', $form->getData()['sequence2']);
    }

    /**
     * Aligned together, a protein against a nucleic-looking sequence leaves both untouched
     */
    public function testOneProteinKeepsBothSequencesUntouched()
    {
        $form = $this->factory->create(SequenceAlignmentType::class);

        $form->submit([
            'id1' => 'Seq 1',
            'sequence' => 'ACGUX',
            'id2' => 'Seq 2',
            'sequence2' => 'MKACGUX',
        ]);

        $this->assertEquals('ACGUX', $form->getData()['sequence']);
        $this->assertEquals('MKACGUX', $form->getData()['sequence2']);
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

    /**
     * A peptide made of the letters A, C, G, T, N only looks like DNA: choosing "Protein" keeps it as it is
     */
    public function testTheChosenTypeOverridesTheDetection()
    {
        $aBase = ['id1' => 'a', 'sequence' => 'GATTACAX', 'id2' => 'b', 'sequence2' => 'GATTAGAX'];

        $oAuto = $this->factory->create(SequenceAlignmentType::class);
        $oAuto->submit($aBase);
        $this->assertSame('GATTACAN', $oAuto->getData()['sequence']);

        $oProtein = $this->factory->create(SequenceAlignmentType::class);
        $oProtein->submit($aBase + ['moltype' => 'protein']);
        $this->assertSame('GATTACAX', $oProtein->getData()['sequence']);
        $this->assertSame('GATTAGAX', $oProtein->getData()['sequence2']);
    }

    /**
     * An RNA with IUPAC codes is not recognised by the detection (R, Y... are also amino acids),
     * but the nucleic type converts it
     */
    public function testNucleicTypeConvertsAnRnaWithIupacCodes()
    {
        $aBase = ['id1' => 'a', 'sequence' => 'AUGRYKUUX', 'id2' => 'b', 'sequence2' => 'AUGCCGUUX'];

        $oAuto = $this->factory->create(SequenceAlignmentType::class);
        $oAuto->submit($aBase);
        $this->assertSame('AUGRYKUUX', $oAuto->getData()['sequence']);

        $oNucleic = $this->factory->create(SequenceAlignmentType::class);
        $oNucleic->submit($aBase + ['moltype' => 'nucleic']);
        $this->assertSame('ATGRYKTTN', $oNucleic->getData()['sequence']);
        $this->assertSame('ATGCCGTTN', $oNucleic->getData()['sequence2']);
    }
}
