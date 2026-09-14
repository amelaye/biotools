<?php
/**
 * Form to calculate Oligonucleotide Frequency
 * Freely inspired by BioPHP's project biophp.org
 * Created 31 march 2019
 * Last modified 24 august 2026
 */
namespace Amelaye\BioTools\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Class PcrAmplificationType
 * @package BioTools\Form
 * @author Amélie DUVERNET aka Amelaye <amelieonline@gmail.com>
 */
class PcrAmplificationType extends AbstractType
{
    /**
     * Form builder
     * @param   FormBuilderInterface $builder
     * @param   array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $sSequenceSample = "GGAGTGAGGG GAGCAGTTGG GAACAGATGG TCCCCGCCGA GGGACCGGTG GGCGACGGCG 60\n";
        $sSequenceSample.= "AGCTGTGGCA GACCTGGCTT CCTAACCACG TCGTGTTCTT GCGGCTCCGG CCCCTGCGGC 120\n";
        $sSequenceSample.= "GACGCTCAGA TCCAACCGAA GCTGAGAAAC CAGCTTCTTC GTCGTTGCCT TCGTCGCCGC 180\n";
        $sSequenceSample.= "CGCCGCAGTT GCTGACGAGA GAGGAGTTGG TTGGCCTCGG CGGAGAGCTT TTCCTGTGGG 240\n";
        $sSequenceSample.= "ACGGAGAAGA CAGCTCCTTC TTAGTCGTTC GCCTTCGGGG CCCCAGCGGC GGCGGCGAAG 300\n";

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
            'primer1',
            TextType::class,
            [
                'attr' => [
                    'cols'  => 75,
                    'rows'  => 10,
                    'class' => "form-control"
                ],
                'data' => 'GAGCAGTTGG',
                'label' => "Primer 1 (5' -> 3') : ",
            ]
        );
        $builder->add(
            'primer2',
            TextType::class,
            [
                'attr' => [
                    'cols'  => 75,
                    'rows'  => 10,
                    'class' => "form-control"
                ],
                'data' => 'GCCGCTGGGG',
                'label' => "Primer 1 (5' -> 3') : ",
            ]
        );
        $builder->add(
            'allowmismatch',
            CheckboxType::class,
            [
                'required' => false,
                'label' => "Allow one mismatch"
            ]
        );
        $builder->add(
            'length',
            TextType::class,
            [
                'attr' => [
                    'cols'  => 75,
                    'rows'  => 10,
                    'class' => "form-control"
                ],
                'data' => 3000,
                'label' => "Maximum length of bands (nucleotides) : ",
            ]
        );
        $builder->add(
            'submit',
            SubmitType::class,
            [
                'label' => "Amplify",
                'attr' => [
                    'class' => "btn btn-primary"
                ]
            ]
        );

        /**
         * Formatting Seq before validation
         * All non-word characters (\\W) and digits(\\d) are remove from primers and from sequence file
         */
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function(FormEvent $event) {
            $aData = $event->getData();

            if (isset($aData['sequence'])) {
                $sSequence = strtoupper($aData['sequence']);
                $sSequence = preg_replace("/\W|\d/", "", $sSequence);
                $aData['sequence'] = $sSequence;
            }

            if (isset($aData['primer1'])) {
                $sSequence = strtoupper($aData['primer1']);
                $sSequence = preg_replace("/\W|\d/", "", $sSequence);
                $aData['primer1'] = $sSequence;
            }

            if (isset($aData['primer2'])) {
                $sSequence = strtoupper($aData['primer2']);
                $sSequence = preg_replace("/\W|\d/", "", $sSequence);
                $aData['primer2'] = $sSequence;
            }

            $event->setData($aData);
        });
    }
}