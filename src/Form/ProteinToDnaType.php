<?php
/**
 * Form ProteinToDnaType
 * Freely inspired by BioPHP's project biophp.org
 * Created 26 february 2019
 * Last modified 8 may 2020
 */
namespace App\Form;

use Amelaye\BioPHP\Api\Interfaces\TripletSpecieApiAdapter;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Class ProteinToDna
 * @package MinitoolsBundle\Form
 * @author Amélie DUVERNET aka Amelaye <amelieonline@gmail.com>
 */
class ProteinToDnaType extends AbstractType
{
    /**
     * All the species with triplets on API
     * @var array $geneticData
     */
    private $geneticData;

    /**
     * ProteinToDnaType constructor.
     * @param TripletSpecieApiAdapter $tripletSpecieApi
     */
    public function __construct(TripletSpecieApiAdapter $tripletSpecieApi)
    {
        $this->geneticData = $tripletSpecieApi::GetSpeciesNames($tripletSpecieApi->getTriplets());
    }

    /**
     * Form builder
     * @param   FormBuilderInterface  $builder
     * @param   array                 $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'sequence',
            TextareaType::class,
            [
                'attr' => [
                    'cols'  => 75,
                    'rows'  => 10,
                    'class' => "form-control"
                ],
                'label' => "Protein sequence : ",
                'data' => "FLIMVSPTAYHQNKDECWRGX*"
            ]
        );

        $builder->add(
            'genetic_code',
            ChoiceType::class,
            [
                'choices' => $this->geneticData,
                'attr' => [
                    'class' => "custom-select d-block w-20"
                ],
                'label' => "Genetic code : ",
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

            if (isset($data['sequence'])) {
                $sSequence = strtoupper($data['sequence']);
                $sSequence = preg_replace("([^FLIMVSPTAY*HQNKDECWRGX\*])", "", $sSequence);
                $data['sequence'] = $sSequence;
            }

            $event->setData($data);
        });
    }
}