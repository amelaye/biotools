<?php
namespace Tests\Form;

use Amelaye\BioPHP\Api\VendorApi;
use Amelaye\BioTools\Form\RestrictionEnzymeDigestType;
use PHPUnit\Framework\MockObject\MockObject;

class RestrictionEnzymeDigestTypeTest extends AbstractFormTypeTestCase
{
    /**
     * @var VendorApi&MockObject
     */
    protected $vendorApiMock;

    protected function setUp(): void
    {
        require __DIR__ . '/../Service/samples/Vendors.php';

        $this->vendorApiMock = $this->getMockBuilder(VendorApi::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getVendors'])
            ->getMock();
        $this->vendorApiMock->method("getVendors")->willReturn($vendorsObjects);

        parent::setUp();
    }

    protected function getTypes(): array
    {
        return [new RestrictionEnzymeDigestType($this->vendorApiMock)];
    }

    public function testSequenceOverOneMillionCharactersIsInvalid()
    {
        $form = $this->factory->create(RestrictionEnzymeDigestType::class);

        $form->submit([
            'sequence' => str_repeat('A', 1000001),
            'minimum' => '4',
            'retype' => '0',
        ]);

        $this->assertFalse($form->isValid());
    }

    public function testValidSubmissionIsValid()
    {
        $form = $this->factory->create(RestrictionEnzymeDigestType::class);

        $form->submit([
            'sequence' => 'ACGTACGTACGTTAGCTAGCTAGCTAGC',
            'minimum' => '4',
            'retype' => '0',
        ]);

        $this->assertTrue($form->isValid());
    }
}
