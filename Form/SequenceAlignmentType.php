<?php
/**
 * Class ReduceAlphabet
 * Freely inspired by BioPHP's project biophp.org
 * Created 20 april 2019
 * Last modified 24 august 2026
 */
namespace Amelaye\BioTools\Form;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;

/**
 * Class SequenceAlignmentType
 * @package BioTools\Form
 * @author Amelie DUVERNET aka Amelaye <amelieonline@gmail.com>
 */
class SequenceAlignmentType extends AbstractType
{
    /**
     * Form builder
     * @param   FormBuilderInterface    $builder
     * @param   array                   $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $sDataSeq1 = "GGAGTGAGGG GAGCAGTTGG CTGAAGATGG TCCCCGCCGA GGGACCGGTG GGCGACGGCG 60\n";
        $sDataSeq1.= "AGCTGTGGCA GACCTGGCTT CCTAACCACG TCCGTGTTCT TGCGGCTCCG GGAGGGACTG 120";

        $builder->add(
            'id1',
            TextType::class,
            [
                'attr' => [
                    'class' => "form-control"
                ],
                'data' => "Sequence 1"
            ]
        );

        $builder->add(
            'sequence',
            TextareaType::class,
            [
                'attr' => [
                    'cols'  => 75,
                    'rows'  => 4,
                    'class' => "form-control"
                ],
                'data' => $sDataSeq1,
                'required' => true
            ]
        );

        $sDataSeq2 = "CGCATGCGGA GTGAGGGGAG CAGTTGGGAA CAGATGGTCC CCGCCGAGGG ACCGGTGGGC 60\n";
        $sDataSeq2.= "GACGGCCAGC TGTGGCAGAC CTGGCTTCCT AACCACGGAA CGTTCTTTCC GCTCCGGGAG 120";

        $builder->add(
            'id2',
            TextType::class,
            [
                'attr' => [
                    'class' => "form-control"
                ],
                'data' => "Sequence 2"
            ]
        );

        $builder->add(
            'sequence2',
            TextareaType::class,
            [
                'attr' => [
                    'cols'  => 75,
                    'rows'  => 4,
                    'class' => "form-control"
                ],
                'data' => $sDataSeq2,
                'required' => true
            ]
        );

        $builder->add(
            'submit',
            SubmitType::class,
            [
                'label' => "Align sequences",
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

            if (isset($aData['sequence'])) {
                $sSequence = strtoupper($aData['sequence']);
                $sSequence = preg_replace("/\W|\d/", "", $sSequence); // remove useless characters
                $sSequence = preg_replace("/U/", "T", $sSequence);    // from RNA to DNA
                $sSequence = preg_replace("/X/", "N", $sSequence);    // substitute X -> N
                $aData['sequence'] = $sSequence;
            }
            if (isset($aData['sequence2'])) {
                $sSequence2 = strtoupper($aData['sequence2']);
                $sSequence2 = preg_replace("/\W|\d/", "", $sSequence2); // remove useless characters
                $sSequence2 = preg_replace("/U/", "T", $sSequence2);    // from RNA to DNA
                $sSequence2 = preg_replace("/X/", "N", $sSequence2);    // substitute X -> N
                $aData['sequence2'] = $sSequence2;
            }
            $event->setData($aData);
        });
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'constraints' => [
                new Callback([
                    'callback' => [$this, 'validateisReady'],
                ]),
            ]
        ]);
    }

    /**
     * Limit sequence length to limit memory usage
     * This script creates a big array that requires a huge amount of memory
     * Do not use sequences longer than 700 bases each (1400 for both sequences)
     * In this demo, the limit has been set up to 300 bases.
     * @param $aObject
     * @param ExecutionContextInterface $context
     * @throws \Exception
     */
    public static function validateisReady($aObject, ExecutionContextInterface $context)
    {
        $iLimit = 300;

        if ((strlen($aObject["sequence"]) + strlen($aObject["sequence2"])) > $iLimit) {
            $context
                ->buildViolation("The maximum length of code accepted for both sequences is $iLimit nucleotides")
                ->addViolation();
        }
    }
}