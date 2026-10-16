<?php
/**
 * Entity MeltingTemperature for Form
 * Freely inspired by BioPHP's project biophp.org
 * Created 23 february 2019
 * Last modified 24 august 2026
 * RIP Pasha, gone 27 february 2019 =^._.^= ∫
 */
namespace Amelaye\BioTools\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Amelaye\BioTools\Validator\MeltingTemperature;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\PositiveOrZero;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Class MeltingTemperatureType
 * @package BioTools\Form
 * @author Amélie DUVERNET aka Amelaye <amelieonline@gmail.com>
 */
class MeltingTemperatureType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'primer',
            TextType::class,
            [
                'attr' => [
                    'cols'  => 75,
                    'rows'  => 10,
                    'class' => "form-control"
                ],
                'label' => "Primer (6 - 50 bases) : ",
                'constraints' => array(
                    new MeltingTemperature()
                )
            ]
        );

        $builder->add(
            'basic',
            CheckboxType::class,
            [
                'required' => false,
                'label' => "Basic Tm (Degenerated nucleotides are allowed) : ",
            ]
        );

        $builder->add(
            'nearestNeighbor',
            CheckboxType::class,
            [
                'required' => false,
                'label' => "Basic Tm (Degenerated nucleotides are NOT allowed) : ",
            ]
        );

        $builder->add(
            'cp',
            TextType::class,
            [
                'attr' => [
                    'cols'  => 75,
                    'rows'  => 10,
                    'class' => "form-control"
                ],
                'data' => 200,
                'label' => "Primer concentration (nM) : ",
                'constraints' => [new NotBlank(), new Type('numeric'), new Positive()]
            ]
        );

        $builder->add(
            'cs',
            TextType::class,
            [
                'attr' => [
                    'cols'  => 75,
                    'rows'  => 10,
                    'class' => "form-control"
                ],
                'data' => 50,
                'label' => "Salt concentration (mM) : ",
                'constraints' => [new NotBlank(), new Type('numeric'), new PositiveOrZero()]
            ]
        );

        $builder->add(
            'cmg',
            TextType::class,
            [
                'attr' => [
                    'cols'  => 75,
                    'rows'  => 10,
                    'class' => "form-control"
                ],
                'data' => 0,
                'label' => "Mg2+ concentration (mM) : ",
                'constraints' => [new NotBlank(), new Type('numeric'), new PositiveOrZero()]
            ]
        );


        $builder->add(
            'submit',
            SubmitType::class,
            [
                'label' => "Calculate Tm",
                'attr' => [
                    'class' => "btn btn-primary"
                ]
            ]
        );
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'constraints' => [
                new Callback(['callback' => [$this, 'validateSaltOrMagnesium']]),
            ]
        ]);
    }

    /**
     * The nearest-neighbor Tm needs some monovalent salt or some magnesium: with neither, the
     * salt correction has nothing to work with
     * @param   mixed                       $aObject
     * @param   ExecutionContextInterface   $context
     */
    public static function validateSaltOrMagnesium($aObject, ExecutionContextInterface $context)
    {
        if (empty($aObject['nearestNeighbor'])) {
            return;
        }
        if (!is_numeric($aObject['cs'] ?? null) || !is_numeric($aObject['cmg'] ?? null)) {
            return; // already reported by the field constraints
        }
        if ((float) $aObject['cs'] <= 0 && (float) $aObject['cmg'] <= 0) {
            $context
                ->buildViolation("The nearest-neighbor Tm needs a salt or a Mg2+ concentration above 0.")
                ->atPath('cs')
                ->addViolation();
        }
    }
}
