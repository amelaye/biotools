<?php
/**
 * Form DnaToProteinType
 * Freely inspired by BioPHP's project biophp.org
 * Created 18 march 2019
 * Last modified 24 august 2026
 */
namespace Amelaye\BioTools\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints\File;

/**
 * Class FastaUploaderType
 * @package BioTools\Form
 * @author Amélie DUVERNET aka Amelaye <amelieonline@gmail.com>
 */
class FastaUploaderType extends AbstractType
{
    /**
     * Form builder
     * @param   FormBuilderInterface  $builder
     * @param   array                 $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'fasta',
            FileType::class,
            [
                'label' => 'FASTA File',
                'constraints' => array(
                    new File([
                        "maxSize" => "100000k",
                        "mimeTypes" =>  "text/plain",
                        "mimeTypesMessage" => "Please upload a valid TXT"
                    ])
                )
            ]
        );

        $builder->add(
            'submit',
            SubmitType::class,
            [
                'label' => "Submit",
                'attr' => [
                    'class' => "btn btn-primary"
                ]
            ]
        );
    }
}