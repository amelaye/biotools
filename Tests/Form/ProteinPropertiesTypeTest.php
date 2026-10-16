<?php
namespace Tests\Form;

use Amelaye\BioTools\Form\ProteinPropertiesType;

class ProteinPropertiesTypeTest extends AbstractFormTypeTestCase
{
    public function testTheSequenceIsUppercasedAndStrippedButKeepsTheStopAndEveryLetter()
    {
        $form = $this->factory->create(ProteinPropertiesType::class);

        $form->submit(['seq' => "mwk 12\nbzuo-x*", 'pH' => '7']);

        $this->assertSame('MWKBZUOX*', $form->getData()['seq']);
    }
}
