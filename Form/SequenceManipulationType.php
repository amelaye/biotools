<?php
/**
 * SequenceManipulation Form Inputs
 * Freely inspired by BioPHP's project biophp.org
 * Created 22 july 2019
 * Last modified 14 september 2026
 */
namespace Amelaye\BioTools\Form;

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
 * @package BioTools\Form
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
        $sData = "GGAGTGAGGGGAGCAGTTGGGCCAAGATGGCGGCCGCCGAGGGACCGGTGGGCGACGCGG\r";
        $sData.= "GAGTGAGGGGAGCAGTTGGGCCAAGATGGCGGCCGCCGAGGGACCGGTGGGCGACGGGGG\r";
        $sData.= "AGTGAGGGGAGCAGTTGGGCCAAGATGGCGGCCGCCGAGGGACCGGTGGGCGACGGCGGA\r";
        $sData.= "GTGAGGGGAGCAGTTGGGCCAAGATGGCGGCCGCCGAGGGACCGGTGGGCGACGGGGAGT\r";
        $sData.= "GAGGGGAGCAGTTGGGCCAAGATGGCGGCCGCCGAGGGACCGGTGGGCGACGCGGGAGTG\r";

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
                'data' => $sData,
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
                // legacy's "<select name=action size=7>" has no "multiple" attribute -
                // size=7 only sets how many rows are visible - and the business logic
                // treats $action as a single scalar, comparing it with == one value at a
                // time, so only one action can ever apply
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
            $aData = $event->getData();
            // remove non coding (works by default)
            if (isset($aData['seq'])) {
                // change the sequence to upper case
                $sSeq = strtoupper($aData['seq']);
                // legacy bug fix: replace X by N before stripping non-coding characters -
                // X isn't in the [ATGCYRWSKMDVHBN] allow-list, so doing it after (as legacy
                // did) always strips every X before this replacement can ever see one.
                $sSeq = preg_replace("/X/","N",$sSeq);
                // remove non-words (\W), con coding ([^ATGCYRWSKMDVHBN]) and digits (\d) from sequence
                $aData['seq'] = preg_replace("/\W|[^ATGCYRWSKMDVHBN]|\d/","",$sSeq);
                $event->setData($aData);
            }
        });
    }
}