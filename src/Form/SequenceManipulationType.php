<?php
/**
 * SequenceManipulation Form Inputs
 * Freely inspired by BioPHP's project biophp.org
 * Created 22 july 2019
 * Last modified 8 may 2019
 */
namespace App\Form;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Class SequenceManipulationType
 * @package MinitoolsBundle\Form
 * @author Amelie DUVERNET aka Amelaye <amelieonline@gmail.com>
 */
class SequenceManipulationType extends AbstractType
{
    /**
     * Form builder
     * @param   FormBuilderInterface    $builder
     * @param   array                   $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $data = "GGAGTGAGGGGAGCAGTTGGGCCAAGATGGCGGCCGCCGAGGGACCGGTGGGCGACGCGG\r";
        $data.= "GAGTGAGGGGAGCAGTTGGGCCAAGATGGCGGCCGCCGAGGGACCGGTGGGCGACGGGGG\r";
        $data.= "AGTGAGGGGAGCAGTTGGGCCAAGATGGCGGCCGCCGAGGGACCGGTGGGCGACGGCGGA\r";
        $data.= "GTGAGGGGAGCAGTTGGGCCAAGATGGCGGCCGCCGAGGGACCGGTGGGCGACGGGGAGT\r";
        $data.= "GAGGGGAGCAGTTGGGCCAAGATGGCGGCCGCCGAGGGACCGGTGGGCGACGCGGGAGTG\r";

        $aActions = [
            "Remove non coding characters" => "remove_non_coding",
            "Reverse sequence" => "reverse",
            "Complement sequence" => "complement",
            "Reverse and Complement of sequence" => "reverse_and_complement",
            "Display Double-stranded Sequence" => "display_both_strands",
            "Convert to RNA" => "toRNA"
        ];

        $builder->add(
            'seq',
            TextareaType::class,
            [
                'attr' => [
                    'cols'  => 75,
                    'rows'  => 4,
                    'class' => "form-control"
                ],
                'data' => $data,
                'required' => true
            ]
        );
        $builder->add(
            'action',
            ChoiceType::class,
            [
                'choices' => $aActions,
                'attr' => [
                    'class' => "custom-select d-block w-20",
                ],
                'multiple' => true
            ]
        );
        $builder->add(
            'start',
            TextType::class,
            [
                'label' => "Select subsequence from position : ",
                'required' => false,
                'attr' => [
                    'class' => "form-control"
                ]
            ]
        );
        $builder->add(
            'end',
            TextType::class,
            [
                'label' => "to (both included)",
                'required' => false,
                'attr' => [
                    'class' => "form-control"
                ]
            ]
        );
        $builder->add(
            'GC',
            CheckboxType::class,
            [
                'required' => false,
                'label' => "G + C content"
            ]
        );
        $builder->add(
            'ACGT',
            CheckboxType::class,
            [
                'required' => false,
                'label' => "Nucleotide composition"
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

        /**
         * Formatting Seq before validation
         */
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function(FormEvent $event) {
            $data = $event->getData();
            // remove non coding (works by default)
            if (isset($data['seq'])) {
                // change the sequence to upper case
                $seq = strtoupper($data['seq']);
                // remove non-words (\W), con coding ([^ATGCYRWSKMDVHBN]) and digits (\d) from sequence
                $seq = preg_replace("/\W|[^ATGCYRWSKMDVHBN]|\d/","",$seq);
                // replace all X by N (to normalized sequences)
                $data['seq'] = preg_replace("/X/","N",$seq);
                $event->setData($data);
            }
        });
    }
}