<?php
namespace Tests\Form;

use Amelaye\BioTools\Form\FastaUploaderType;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FastaUploaderTypeTest extends AbstractFormTypeTestCase
{
    public function testValidFastaFileUploadIsValid()
    {
        $sSourceFile = tempnam(sys_get_temp_dir(), 'fasta_form_');
        file_put_contents($sSourceFile, ">seq1\nATGCATGC\n");

        $file = new UploadedFile($sSourceFile, 'sequence.fasta', 'application/octet-stream', null, true);

        $form = $this->factory->create(FastaUploaderType::class);
        $form->submit(['fasta' => $file]);

        $this->assertTrue($form->isValid());

        unlink($sSourceFile);
    }
}
