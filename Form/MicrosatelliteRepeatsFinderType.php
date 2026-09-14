<?php
/**
 * Form ProteinPropertiesType
 * Freely inspired by BioPHP's project biophp.org
 * Created 31 march 2019
 * Last modified 24 august 2026
 */

namespace Amelaye\BioTools\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Class MicrosatelliteRepeatsFinderType
 * @package BioTools\Form
 * @author Amélie DUVERNET aka Amelaye <amelieonline@gmail.com>
 */
class MicrosatelliteRepeatsFinderType extends AbstractType
{
    /**
     * Form builder
     * @param   FormBuilderInterface  $builder
     * @param   array                 $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $sSequenceSample = "AACAATGCCATGATGATGATTATTACGACACAACAACACCGCGCTTGACGGCGGCGGATGGATGCCG";
        $sSequenceSample .= "CGATCAGACGTTCAACGCCCACGTAACGTAACGCAACGTAACCTAACGACACTGTTAACGGTACGAT";


        $aDataMin = [2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6];
        $aDataMax = [3 => 3, 4 => 4, 5 => 5, 6 => 6, 7 => 7, 8 => 8, 9 => 9, 10 => 10];
        $aMinRepeats = [2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6];
        $aLengthOfMR = [5 => 5, 6 => 6, 7 => 7, 8 => 8, 9 => 9, 10 => 10, 11 => 11, 12 => 12, 13 => 13, 14 => 14,
            15 => 15, 16 => 16, 17 => 17, 18 => 18, 19 => 19, 20 => 20];
        $aMismatch = [0 => 0, 10 => 10, 20 => 20, 30 => 30];

        $builder->add(
            'sequence',
            TextareaType::class,
            [
                'attr' => [
                    'cols'  => 75,
                    'rows'  => 10,
                    'class' => "form-control"
                ],
                'label' => "Sequence : ",
                'data' => $sSequenceSample
            ]
        );

        $builder->add(
            'min',
            ChoiceType::class,
            [
                'choices' => $aDataMin,
                'label' => "Minimum length of repeated sequence :",
                'attr' => [
                    'class' => "form-control"
                ],
            ]
        );

        $builder->add(
            'max',
            ChoiceType::class,
            [
                'choices' => $aDataMax,
                'label' => "Maximum length of repeated sequence :",
                'attr' => [
                    'class' => "form-control"
                ],
                'choice_attr' => function($iMax) {
                    $aAttr = [];
                    if ($iMax === 6) {
                        $aAttr['selected'] = 'selected';
                    }
                    return $aAttr;
                }
            ]
        );

        $builder->add(
            'min_repeats',
            ChoiceType::class,
            [
                'choices' => $aMinRepeats,
                'label' => "Minimum number of repeats :",
                'attr' => [
                    'class' => "form-control"
                ],
                'choice_attr' => function($iRepeats) {
                    $aAttr = [];
                    if ($iRepeats === 3) {
                        $aAttr['selected'] = 'selected';
                    }
                    return $aAttr;
                }
            ]
        );

        $builder->add(
            'length_of_MR',
            ChoiceType::class,
            [
                'choices' => $aLengthOfMR,
                'label' => "Minimum length of tandem repeat : ",
                'attr' => [
                    'class' => "form-control"
                ],
                'choice_attr' => function($iLength) {
                    $aAttr = [];
                    if ($iLength === 6) {
                        $aAttr['selected'] = 'selected';
                    }
                    return $aAttr;
                }
            ]
        );

        $builder->add(
            'mismatch',
            ChoiceType::class,
            [
                'choices' => $aMismatch,
                'label' => "Allowed percentage of mismatches :",
                'attr' => [
                    'class' => "form-control"
                ]
            ]
        );

        $builder->add(
            'submit',
            SubmitType::class,
            [
                'label' => "Find Microsatellite repeats",
                'attr' => [
                    'class' => "btn btn-primary"
                ]
            ]
        );

        /**
         * Formatting Seq before validation
         * Remove non word and digits from sequence
         */
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function(FormEvent $event) {
            $aData = $event->getData();

            if (isset($aData['sequence'])) {
                $sSequence = strtoupper($aData['sequence']);
                $sSequence = preg_replace("/\W|\d/", "", $sSequence);

                $aData['sequence'] = $sSequence;
                $event->setData($aData);
            }
        });
    }
}