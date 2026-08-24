<?php
/**
 * Form FindPalindromesType
 * Freely inspired by BioPHP's project biophp.org
 * Created 18 march 2019
 * Last modified 24 august 2026
 */
namespace Amelaye\BioTools\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Amelaye\BioTools\Validator\SequenceRecognition;

/**
 * Class FindPalindromesType
 * @package BioTools\Form
 * @author Amélie DUVERNET aka Amelaye <amelieonline@gmail.com>
 */
class FindPalindromesType extends AbstractType
{
    /**
     * Form builder
     * @param   FormBuilderInterface  $builder
     * @param   array                 $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /*
         * Sample Datas
         */
        $dataSequence = "AACAATGCCATGATGATGATTATTACGACACAACAACACCGCGCTTGACGGCGGCGGATGGATGCCG";
        $dataSequence .= "CGATCAGACGTTCAACGCCCACGTAACGTAACGCAACGTAACCTAACGACACTGTTAACGGTACGAT";

        $minData = [
            4 => 4,
            5 => 5,
            6 => 6,
            7 => 7,
            8 => 8,
            9 => 9,
            10 => 10
        ];

        $maxData = [
            5 => 5,
            6 => 6,
            7 => 7,
            8 => 8,
            9 => 9,
            10 => 10,
            11 => 11,
            12 => 12,
            13 => 13,
            14 => 14,
            15 => 15,
            16 => 16,
            17 => 17,
            18 => 18,
            19 => 19,
            20 => 20
        ];

        /*
         * Form construction
         */
        $builder->add(
            'seq',
            TextareaType::class,
            [
                'data' => $dataSequence,
                'attr' => [
                    'cols'  => 75,
                    'rows'  => 10,
                    'class' => "form-control"
                ],
                'label' => "Sequence : ",
                'constraints' => array(
                    new SequenceRecognition(),
                )
            ]
        );


        $builder->add(
            'min',
            ChoiceType::class,
            [
                'choices' => $minData,
                'label' => "Minimum length of palindromic sequence : ",
                'attr' => [
                    'class' => "custom-select d-block w-20"
                ]
            ]
        );

        $builder->add(
            'max',
            ChoiceType::class,
            [
                'choices' => $maxData,
                'label' => "Maximum length of palindromic sequence : ",
                'attr' => [
                    'class' => "custom-select d-block w-20"
                ]
            ]
        );

        $builder->add(
            'submit',
            SubmitType::class,
            [
                'label' => "Find Palindromic Sequences",
                'attr' => [
                    'class' => "btn btn-primary"
                ]
            ]
        );
    }
}