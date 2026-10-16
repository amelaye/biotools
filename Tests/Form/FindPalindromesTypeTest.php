<?php
namespace Tests\Form;

use Amelaye\BioTools\Form\FindPalindromesType;

class FindPalindromesTypeTest extends AbstractFormTypeTestCase
{
    public function testTheSequenceIsUppercasedAndStripped()
    {
        $form = $this->factory->create(FindPalindromesType::class);

        $form->submit(['seq' => "gaa 12\nttc", 'min' => '4', 'max' => '10']);

        $this->assertSame('GAATTC', $form->getData()['seq']);
    }
}
