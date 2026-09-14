<?php
namespace Tests\Form;

use Amelaye\BioTools\Form\ReduceAlphabetType;

class ReduceAlphabetTypeTest extends AbstractFormTypeTestCase
{
    /**
     * porting bug fix: the PRE_SUBMIT listener used to check $data['sequence'], but the
     * textarea field is actually named 'seq', so the uppercase + non-coding-character
     * stripping normalization silently never ran.
     */
    public function testSequenceIsUppercasedAndNonCodingCharactersRemoved()
    {
        $form = $this->factory->create(ReduceAlphabetType::class);

        $form->submit([
            'seq' => 'arn 123 d-c*',
            'mode' => 'pre',
            'type' => '2',
        ]);

        $this->assertEquals('ARNDC*', $form->getData()['seq']);
    }

    public function testCustomAlphabetOfWrongLengthIsInvalidWhenModeIsCustom()
    {
        $form = $this->factory->create(ReduceAlphabetType::class);

        $form->submit([
            'seq' => 'ARNDCEQGHILKMFPSTWYVX*',
            'mode' => 'custom',
            'type' => '2',
            'custom_alphabet' => 'TOOSHORT',
        ]);

        $this->assertFalse($form->isValid());
    }

    public function testCustomAlphabetOfCorrectLengthIsValidWhenModeIsCustom()
    {
        $form = $this->factory->create(ReduceAlphabetType::class);

        $form->submit([
            'seq' => 'ARNDCEQGHILKMFPSTWYVX*',
            'mode' => 'custom',
            'type' => '2',
            'custom_alphabet' => 'TCDCTCDTRAACDRDTDRRA',
        ]);

        $this->assertTrue($form->isValid());
    }

    /**
     * legacy only validates the custom alphabet length "for personalized reduced
     * alphabets" (see ReduceAlphabetType::validateCustomAlphabet) - a wrong-length
     * custom_alphabet must not block submission when a pre-defined type is used instead
     */
    public function testCustomAlphabetLengthIsIgnoredWhenModeIsPre()
    {
        $form = $this->factory->create(ReduceAlphabetType::class);

        $form->submit([
            'seq' => 'ARNDCEQGHILKMFPSTWYVX*',
            'mode' => 'pre',
            'type' => '2',
            'custom_alphabet' => 'TOOSHORT',
        ]);

        $this->assertTrue($form->isValid());
    }
}
