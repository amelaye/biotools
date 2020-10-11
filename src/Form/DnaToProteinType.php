<?php
/**
 * Form DnaToProteinType
 * @author Amélie DUVERNET aka Amelaye
 * Freely inspired by BioPHP's project biophp.org
 * Created 24 february 2019
 * Last modified 9 may 2019
 */
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\Length;
//use AppBundle\Api\Bioapi;
use Amelaye\BioPHP\Api\Interfaces\TripletSpecieApiAdapter;

class DnaToProteinType extends AbstractType
{
    /**
     * All the species with triplets on API
     * @var array $geneticData
     */
    private $geneticData;

    /**
     * ProteinToDnaType constructor.
     * @param TripletSpecieApiAdapter $tripletSpecieApiAdapter
     */
    public function __construct(TripletSpecieApiAdapter $tripletSpecieApiAdapter)
    {
        $this->geneticData = $tripletSpecieApiAdapter::GetSpeciesNames($tripletSpecieApiAdapter->getTriplets());
    }

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
        $optionsFrames = array("1" => "1" ,"1-3" => "3", "1-6" => "6");

        $textSequence = "GGAGTGAGGG GAGCAGTTGG GCCAAGATGG CGGCCGCCGA GGGACCGGTG GGCGACGCGG 60\r";
        $textSequence .= "GAGTGAGGGG AGCAGTTGGG CCAAGATGGC GGCCGCCGAG GGACCGGTGG GCGACGGGGG 120\r";
        $textSequence .= "AGTGAGGGGA GCAGTTGGGC CAAGATGGCG GCCGCCGAGG GACCGGTGGG CGACGGCGGA 180\r";
        $textSequence .= "GTGAGGGGAG CAGTTGGGCC AAGATGGCGG CCGCCGAGGG ACCGGTGGGC GACGGGGAGT 240\r";
        $textSequence .= "GAGGGGAGCA GTTGGGCCAA GATGGCGGCC GCCGAGGGAC CGGTGGGCGA CGCGGGAGTG 300\r";

        /*
         * Form construction
         */
        $builder->add(
            'sequence',
            TextareaType::class,
            [
                'data' => $textSequence,
                'attr' => [
                    'cols'  => 75,
                    'rows'  => 10,
                    'class' => "form-control"
                ],
                'label' => "Sequence : ",
            ]
        );
        $builder->add(
            'submit',
            SubmitType::class,
            [
                'label' => "Translate to Protein",
                'attr' => [
                    'class' => "btn btn-primary"
                ]
            ]
        );
        $builder->add(
            'frames',
            ChoiceType::class,
            [
                'choices' => $optionsFrames,
                'label' => "Translate frames : ",
                'attr' => [
                    'class' => "custom-select d-block w-20"
                ]
            ]
        );
        $builder->add(
            'dgaps',
            CheckboxType::class,
            [
                'required' => false,
                'label' => "Output the amino acids with double gaps (--)"
            ]
        );
        $builder->add(
            'show_aligned',
            CheckboxType::class,
            [
                'required' => false,
                'label' => "Show Translations Aligned "
            ]
        );
        $builder->add(
            'search_orfs',
            CheckboxType::class,
            [
                'required' => false,
                'label' => "Search for ORFs "
            ]
        );
        $builder->add(
            'protsize',
            TextType::class,
            [
                'data' => 50,
                'label' => "Minimum size of protein sequence : ",
                'attr' => [
                    'class' => "form-control"
                ],
                'constraints' => [
                    new GreaterThanOrEqual(["value" => 10])
                ]
            ]
        );
        $builder->add(
            'only_coding',
            CheckboxType::class,
            [
                'label' => "... and do not show non-coding",
                'required' => false
            ]
        );
        $builder->add(
            'trimmed',
            CheckboxType::class,
            [
                'label' => "ORFs trimmed to MET-to-Stop",
                'required' => false,
            ]
        );
        $builder->add(
            'genetic_code',
            ChoiceType::class,
            [
                'choices' => $this->geneticData,
                'label' => "Genetic code : ",
                'attr' => [
                    'class' => "custom-select d-block w-20"
                ]
            ]
        );
        $builder->add(
            'usemycode',
            CheckboxType::class,
            [
                'label' => "Use custom genetic code",
                'required' => false
            ]
        );
        $builder->add(
            'mycode',
            TextType::class,
            [
                'data' => "FFLLSSSSYY**CC*WLLLLPPPPHHQQRRRRIIIMTTTTNNKKSSRRVVVVAAAADDEEGGGG",
                'attr' => [
                    'class' => "form-control"
                ],
                'constraints' => [
                    new Length([
                        'min' => 64,
                        'max' => 64,
                        'minMessage' => 'The custom code is not correct (is not 64 characters long)',
                        'maxMessage' => 'The custom code is not correct (is not 64 characters long)'
                    ]),
                ]
            ]
        );

        /**
         * Formatting Seq before validation
         * Remove non word and digits from sequence
         */
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function(FormEvent $event) {
            $data = $event->getData();

            if (isset($data['sequence'])) {
                $sSequence = preg_replace("(\W|\d)", "", $data['sequence']);
                $data['sequence'] = $sSequence;
            }

            if (isset($data['usemycode']) && $data["usemycode"] == 1) {
                $mycode = preg_replace("([^FLIMVSPTAY*HQNKDECWRG\*])", "", $data["mycode"]);
                $data["mycode"] = $mycode;
            }

            $event->setData($data);
        });
    }
}