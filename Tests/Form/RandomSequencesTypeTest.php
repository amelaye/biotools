<?php
namespace Tests\Form;

use Amelaye\BioTools\Form\RandomSequencesType;

class RandomSequencesTypeTest extends AbstractFormTypeTestCase
{
    /**
     * porting bug fix: the PRE_SUBMIT listener used to check $data['sequence'], but the
     * textarea field is actually named 'seq', so the uppercase + non-coding-character
     * stripping normalization silently never ran.
     */
    public function testSequenceIsUppercasedAndNonCodingCharactersRemoved()
    {
        $form = $this->factory->create(RandomSequencesType::class);

        $form->submit([
            'procedure' => 'fromseq',
            'seq' => 'flim 123 vsp-t*z',
        ]);

        // "z" is not in the allowed amino-acid letters, it is stripped just like digits/dashes
        $this->assertEquals('FLIMVSPT*', $form->getData()['seq']);
    }
}
