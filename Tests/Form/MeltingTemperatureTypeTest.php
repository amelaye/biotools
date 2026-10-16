<?php
namespace Tests\Form;

use Amelaye\BioTools\Form\MeltingTemperatureType;
use PHPUnit\Framework\Attributes\DataProvider;

class MeltingTemperatureTypeTest extends AbstractFormTypeTestCase
{
    private function submit(array $aOverride)
    {
        $form = $this->factory->create(MeltingTemperatureType::class);
        $form->submit(array_merge([
            'primer' => 'ACGTACGTAC',
            'nearestNeighbor' => '1',
            'cp' => '200',
            'cs' => '50',
            'cmg' => '0',
        ], $aOverride));

        return $form;
    }

    public function testDefaultValuesAreValid()
    {
        $this->assertTrue($this->submit([])->isValid());
    }

    public function testMagnesiumAloneIsEnough()
    {
        $this->assertTrue($this->submit(['cs' => '0', 'cmg' => '2'])->isValid());
    }

    #[DataProvider('provideInvalidConcentrations')]
    public function testInvalidConcentrationsAreRefused(array $aOverride)
    {
        $this->assertFalse($this->submit($aOverride)->isValid());
    }

    public static function provideInvalidConcentrations(): array
    {
        return [
            'empty primer concentration' => [['cp' => '']],
            'null primer concentration'  => [['cp' => '0']],
            'negative primer'            => [['cp' => '-5']],
            'not a number'               => [['cs' => 'abc']],
            'empty salt'                 => [['cs' => '']],
            'negative magnesium'         => [['cmg' => '-1']],
            'no salt and no magnesium'   => [['cs' => '0', 'cmg' => '0']],
        ];
    }

    /**
     * The basic Tm does not use the concentrations: no salt/magnesium is then no problem
     */
    public function testNoSaltAndNoMagnesiumIsFineForTheBasicTm()
    {
        $this->assertTrue($this->submit(['nearestNeighbor' => null, 'basic' => '1', 'cs' => '0', 'cmg' => '0'])->isValid());
    }

    public function testAtLeastOneTmMethodMustBeTicked()
    {
        $this->assertFalse($this->submit(['nearestNeighbor' => null])->isValid());
        $this->assertTrue($this->submit(['nearestNeighbor' => null, 'basic' => '1'])->isValid());
        $this->assertTrue($this->submit(['nearestNeighbor' => '1', 'basic' => null])->isValid());
    }

    /**
     * A lower case primer is read as the same primer in upper case
     */
    public function testLowerCasePrimerIsAccepted()
    {
        $form = $this->submit(['primer' => ' acgtacgtac ']);

        $this->assertTrue($form->isValid());
        $this->assertSame('ACGTACGTAC', $form->getData()['primer']);
    }
}
