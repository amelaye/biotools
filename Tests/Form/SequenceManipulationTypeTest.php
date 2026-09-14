<?php
namespace Tests\Form;

use Amelaye\BioTools\Form\SequenceManipulationType;

class SequenceManipulationTypeTest extends AbstractFormTypeTestCase
{
    public function testSequenceIsUppercasedAndNonCodingCharactersRemoved()
    {
        $form = $this->factory->create(SequenceManipulationType::class);

        $form->submit([
            'seq' => "act 123 g-t\rc x",
            'action' => 'remove_non_coding',
        ]);

        // "x" is a lowercase degenerate placeholder, normalized to uppercase "N"
        $this->assertEquals('ACTGTCN', $form->getData()['seq']);
    }

    /**
     * legacy bug fix: legacy replaced X by N *after* stripping non-[ATGCYRWSKMDVHBN]
     * characters, so X (not in that allow-list) was always removed first and the
     * X->N replacement was dead code; every X silently vanished instead of becoming N.
     */

    public function testDegenerateXIsNormalizedToN()
    {
        $form = $this->factory->create(SequenceManipulationType::class);

        $form->submit([
            'seq' => 'ATGX',
            'action' => 'remove_non_coding',
        ]);

        $this->assertEquals('ATGN', $form->getData()['seq']);
    }
}
