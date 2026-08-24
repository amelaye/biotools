<?php
/**
 * Form FormulasType
 * Freely inspired by BioPHP's project biophp.org
 * Created 24 august 2026
 * Last modified 24 august 2026
 */
namespace Amelaye\BioTools\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Class FormulasType
 * @package BioTools\Form
 * @author Amélie DUVERNET aka Amelaye <amelieonline@gmail.com>
 */
class FormulasType extends AbstractType
{
    /**
     * Form builder
     * @param   FormBuilderInterface    $builder
     * @param   array                   $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'formula',
            ChoiceType::class,
            [
                'choices' => [
                    "Working with DNA" => [
                        "Molecular weight of dsDNA (Da)"            => "mw_dsdna",
                        "Molecular weight of ssDNA (Da)"            => "mw_ssdna",
                        "pmol of 5'(or 3') ends of dsDNA"           => "pmol_ends_dsdna",
                        "pmol of 5'(or 3') ends of ssDNA"           => "pmol_ends_ssdna",
                        "Conversion of µg to pmol (dsDNA)"          => "microg_to_pmol_dsdna",
                        "Conversion of µg to pmol (ssDNA)"          => "microg_to_pmol_ssdna",
                        "Conversion of pmol to µg (dsDNA)"          => "pmol_to_microg_dsdna",
                        "Conversion of pmol to µg (ssDNA)"          => "pmol_to_microg_ssdna",
                    ],
                    "Working with RNA" => [
                        "Molecular weight of ssRNA (Da)"            => "mw_ssrna",
                        "pmol of 5'(or 3') ends of ssRNA"           => "pmol_ends_ssrna",
                        "Conversion of pmol to µg (ssRNA)"          => "pmol_to_microg_ssrna",
                    ],
                    "Temperatures and pressure" => [
                        "Centigrade to Fahrenheit"                  => "centi_to_fahren",
                        "Fahrenheit to Centigrade"                  => "fahren_to_centi",
                        "Millibars to millimeters of mercury"       => "mbar_to_mmhg",
                        "Millibars to inches of mercury"            => "mbar_to_inchhg",
                        "Millibars to pounds per square inch"       => "mbar_to_psi",
                        "Millibars to atmospheres"                  => "mbar_to_atm",
                        "Millibars to kilopascals"                  => "mbar_to_kpa",
                        "Millibars to torrs"                        => "mbar_to_torr",
                    ],
                    "Quantification of proteins" => [
                        "Molar conversions for proteins (pmol to µg)" => "pmol_to_microg_protein",
                        "Protein to DNA coding length (kDa to bp)"    => "kda_to_bp",
                    ],
                ],
                'label' => "Formula to apply :",
                'multiple' => false,
                'expanded' => false,
                'required' => true,
                'attr' => [
                    'class' => "form-control"
                ]
            ]
        );

        $builder->add(
            'sequence',
            TextareaType::class,
            [
                'attr' => [
                    'cols'  => 75,
                    'rows'  => 8,
                    'class' => "form-control"
                ],
                'label' => "Sequence (nucleic acid formulas only) :",
                'required' => false
            ]
        );

        $builder->add(
            'quantity',
            TextType::class,
            [
                'attr' => [
                    'class' => "form-control"
                ],
                'label' => "Quantity (µg or pmol, depending on the formula) :",
                'required' => false
            ]
        );

        $builder->add(
            'value',
            TextType::class,
            [
                'attr' => [
                    'class' => "form-control"
                ],
                'label' => "Value to convert (degrees or millibars) :",
                'required' => false
            ]
        );

        $builder->add(
            'molecularWeight',
            TextType::class,
            [
                'attr' => [
                    'class' => "form-control"
                ],
                'label' => "Protein molecular weight (kDa) :",
                'required' => false
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
         * Formatting sequence before validation : nucleic acid formulas only count
         * bases, so non coding characters are removed to keep strlen() meaningful.
         */
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();

            if (isset($data['sequence'])) {
                $sSequence = strtoupper($data['sequence']);
                $sSequence = preg_replace("([^ACGTUBDHKMNRSVWY])", "", $sSequence);
                $data['sequence'] = $sSequence;
            }

            $event->setData($data);
        });
    }
}
