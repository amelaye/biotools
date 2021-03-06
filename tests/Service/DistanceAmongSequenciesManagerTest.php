<?php
/**
 * Created by PhpStorm.
 * User: amelaye
 * Date: 2019-07-23
 * Time: 11:34
 */

namespace Tests\MinitoolsBundle\Service;

use Amelaye\BioPHP\Api\NucleotidApi;
use App\Service\ChaosGameRepresentationManager;
use App\Service\DistanceAmongSequencesManager;
use Amelaye\BioPHP\Domain\Tools\Service\OligosManager;
use PHPUnit\Framework\TestCase;


class DistanceAmongSequenciesManagerTest extends TestCase
{
    protected $dnaComplement;

    protected $apiMock;

    protected function setUp()
    {
        $this->dnaComplement = ["A" => "T", "T" => "A", "G" => "C", "C" => "G"];

        /**
         * Mock API
         */
        $clientMock = $this->getMockBuilder('GuzzleHttp\Client')->getMock();
        $serializerMock = \JMS\Serializer\SerializerBuilder::create()
            ->build();

        require 'samples/Nucleotids.php';

        $this->apiNucleoMock = $this->getMockBuilder(NucleotidApi::class)
            ->disableOriginalConstructor()
            ->setMethods(['getNucleotids'])
            ->getMock();
        $this->apiNucleoMock->method("getNucleotids")->will($this->returnValue($aNucleoObjects));
    }

    /**
     * @test
     * @throws \Exception
     */
    public function testFormatSequences()
    {
        $argument = "GTGCCGAGCTGAGTTCCTTATAAGAATTAATCTTAATTTTGTATTTTTTCCTGTAAGACAATAGGCCATG";

        $aExpected = array(
            0 => "GTGCCGAGCTGAGTTCCTTATAAGAATTAATCTTAATTTTGTATTTTTTCCTGTAAGACAATAGGCCATG"
        );

        $oligoMock = $this->getMockBuilder('Amelaye\BioPHP\Domain\Tools\Service\OligosManager')
            ->setConstructorArgs([$this->apiNucleoMock])
            ->setMethods()
            ->getMock();

        $service = new DistanceAmongSequencesManager($oligoMock, $this->apiNucleoMock);
        $testFunction = $service->formatSequences($argument);

        $this->assertEquals($aExpected, $testFunction);
    }

    public function testComputeOligonucleotidsFrequenciesEuclideanDinucleotids()
    {
        $aSeq = [0 => "GTGCCGAGCTGAGTTCCTTATAAGAATTAATCTTAATTTTGTATTTTTTCCTGTAAGACAATAGGCCATG"];
        $iLen = 2;

        $aExpected = array(
            0 => array(
                "AA" => 2.0869565217391,
                "AC" => 0.57971014492754,
                "AG" => 1.0434782608696,
                "AT" => 1.6231884057971,
                "CA" => 0.81159420289855,
                "CC" => 0.57971014492754,
                "CG" => 0.23188405797101,
                "CT" => 1.0434782608696,
                "GA" => 0.81159420289855,
                "GC" => 0.69565217391304,
                "GG" => 0.57971014492754,
                "GT" => 0.57971014492754,
                "TA" => 1.6231884057971,
                "TC" => 0.81159420289855,
                "TG" => 0.81159420289855,
                "TT" => 2.0869565217391,
            )
        );

        $oligoMock = $this->getMockBuilder('Amelaye\BioPHP\Domain\Tools\Service\OligosManager')
            ->setConstructorArgs([$this->apiNucleoMock])
            ->setMethods()
            ->getMock();

        $service = new DistanceAmongSequencesManager($oligoMock, $this->apiNucleoMock);
        $testFunction = $service->computeOligonucleotidsFrequenciesEuclidean($aSeq, $iLen);

        $this->assertEquals($aExpected, $testFunction);
    }

    public function testComputeOligonucleotidsFrequenciesException()
    {
        $this->expectException(\Exception::class);
        $aSeq = false;
        $iLen = 2;

        $oligoMock = $this->getMockBuilder('Amelaye\BioPHP\Domain\Tools\Service\OligosManager')
            ->setConstructorArgs([$this->apiNucleoMock])
            ->setMethods()
            ->getMock();

        $service = new DistanceAmongSequencesManager($oligoMock, $this->apiNucleoMock);
        $service->computeOligonucleotidsFrequenciesEuclidean($aSeq, $iLen);
    }

    public function testComputeOligonucleotidsFrequenciesEuclideanTrinucleotids()
    {
        $aSeq = [0 => "GTGCCGAGCTGAGTTCCTTATAAGAATTAATCTTAATTTTGTATTTTTTCCTGTAAGACAATAGGCCATG"];
        $iLen = 3;

        $aExpected = array(
            0 => array(
                "AAA" => 2.8235294117647,
                "AAC" => 0.47058823529412,
                "AAG" => 1.8823529411765,
                "AAT" => 3.2941176470588,
                "ACA" => 1.4117647058824,
                "ACC" => 0.0,
                "ACG" => 0.0,
                "ACT" => 0.47058823529412,
                "AGA" => 1.4117647058824,
                "AGC" => 0.94117647058824,
                "AGG" => 1.4117647058824,
                "AGT" => 0.47058823529412,
                "ATA" => 1.8823529411765,
                "ATC" => 0.47058823529412,
                "ATG" => 0.94117647058824,
                "ATT" => 3.2941176470588,
                "CAA" => 0.94117647058824,
                "CAC" => 0.47058823529412,
                "CAG" => 0.94117647058824,
                "CAT" => 0.94117647058824,
                "CCA" => 0.47058823529412,
                "CCC" => 0.0,
                "CCG" => 0.47058823529412,
                "CCT" => 1.4117647058824,
                "CGA" => 0.47058823529412,
                "CGC" => 0.0,
                "CGG" => 0.47058823529412,
                "CGT" => 0.0,
                "CTA" => 0.47058823529412,
                "CTC" => 0.94117647058824,
                "CTG" => 0.94117647058824,
                "CTT" => 1.8823529411765,
                "GAA" => 1.4117647058824,
                "GAC" => 0.47058823529412,
                "GAG" => 0.94117647058824,
                "GAT" => 0.47058823529412,
                "GCA" => 0.47058823529412,
                "GCC" => 1.4117647058824,
                "GCG" => 0.0,
                "GCT" => 0.94117647058824,
                "GGA" => 0.94117647058824,
                "GGC" => 1.4117647058824,
                "GGG" => 0.0,
                "GGT" => 0.0,
                "GTA" => 0.94117647058824,
                "GTC" => 0.47058823529412,
                "GTG" => 0.47058823529412,
                "GTT" => 0.47058823529412,
                "TAA" => 3.2941176470588,
                "TAC" => 0.94117647058824,
                "TAG" => 0.47058823529412,
                "TAT" => 1.8823529411765,
                "TCA" => 0.47058823529412,
                "TCC" => 0.94117647058824,
                "TCG" => 0.47058823529412,
                "TCT" => 1.4117647058824,
                "TGA" => 0.47058823529412,
                "TGC" => 0.47058823529412,
                "TGG" => 0.47058823529412,
                "TGT" => 1.4117647058824,
                "TTA" => 3.2941176470588,
                "TTC" => 1.4117647058824,
                "TTG" => 0.94117647058824,
                "TTT" => 2.8235294117647
            )
        );


        $oligoMock = $this->getMockBuilder('Amelaye\BioPHP\Domain\Tools\Service\OligosManager')
            ->setConstructorArgs([$this->apiNucleoMock])
            ->setMethods()
            ->getMock();

        $service = new DistanceAmongSequencesManager($oligoMock, $this->apiNucleoMock);
        $testFunction = $service->computeOligonucleotidsFrequenciesEuclidean($aSeq, $iLen);

        $this->assertEquals($aExpected, $testFunction);
    }

    public function testComputeOligonucleotidsFrequenciesPearson()
    {
        $aSeq = [0 => "GTGCCGAGCTGAGTTCCTTATAAGAATTAATCTTAATTTTGTATTTTTTCCTGTAAGACAATAGGCCATG"];

        $aExpected = array(
            0 => array(
                0 => 2.1213203435596424,
                1 => -0.7276068751089989,
                2 => -1.6035674514745462,
                3 => -0.341881729378914,
                4 => -1.3693063937629153,
                7 => 2.23606797749979,
                8 => 2.3717082451262845,
                9 => -1.4342743312012725,
                10 => -0.4743416490252568,
                11 => -0.9486832980505138,
                12 => 0.0,
                13 => 1.0377490433255416,
                14 => -1.5275252316519468,
                15 => 0.5345224838248488,
                16 => 1.9321835661585918,
                17 => -0.9354143466934853,
                18 => 0.24152294576982405,
                19 => -1.4491376746189437,
                28 => -0.375,
                29 => 1.984313483298443,
                30 => -0.5669467095138409,
                31 => -0.9486832980505138,
                32 => -0.44095855184409855,
                33 => 1.247219128924647,
                34 => -1.4491376746189437,
                35 => 1.247219128924647,
                36 => -0.7745966692414834,
                37 => -1.7320508075688774,
                39 => 2.4494897427831783,
                40 => 1.4907119849998598,
                41 => -1.4907119849998598,
                44 => -0.9128709291752769,
                45 => -0.5590169943749475,
                46 => -0.5590169943749475,
                47 => 2.23606797749979,
                48 => 0.0,
                49 => 0.724568837309472,
                50 => 1.6408253082847342,
                51 => -1.4966629547095764,
                52 => -0.4409585518440984,
                53 => -0.6831300510639733,
                54 => -0.4409585518440984,
                55 => 1.247219128924647,
                56 => -0.6831300510639733,
                57 => -0.6831300510639733,
                58 => 1.7078251276599332,
                59 => -1.4491376746189437,
                60 => 0.2754961485142392,
                61 => -0.21622499104693424,
                62 => 0.3418817293789138,
                63 => -0.341881729378914,
                64 => 0.5303300858899107,
                65 => -0.36380343755449945,
                66 => -0.8017837257372732,
                67 => 0.3418817293789138,
                68 => -1.3693063937629153,
                71 => -0.5590169943749475,
                72 => -1.1338934190276817,
                73 => 1.0714285714285716,
                74 => 0.5669467095138409,
                75 => -0.5669467095138409,
                76 => -0.9660917830792959,
                77 => -0.4236592728681617,
                78 => 3.7416573867739418,
                79 => -1.5275252316519468,
                80 => -0.6831300510639733,
                81 => -0.4409585518440984,
                82 => -0.6831300510639733,
                83 => 1.7078251276599332,
                88 => 1.414213562373095,
                90 => -1.414213562373095,
                92 => 1.5000000000000002,
                93 => -1.1338934190276817,
                94 => 0.5669467095138409,
                95 => -0.4743416490252568,
                96 => -0.9354143466934853,
                97 => -0.4409585518440984,
                98 => 1.7078251276599332,
                99 => -0.4409585518440984,
                104 => -0.9128709291752769,
                105 => 0.9128709291752769,
                112 => -1.0377490433255416,
                113 => -0.4236592728681617,
                114 => -0.2878197989826109,
                115 => 1.6408253082847342,
                116 => 1.7078251276599332,
                117 => -1.0583005244258363,
                118 => 1.7078251276599332,
                119 => -1.4491376746189437,
                120 => 1.7078251276599332,
                121 => -0.6831300510639733,
                122 => -0.6831300510639733,
                123 => 0.24152294576982405,
                124 => 2.8428212488760574,
                125 => -1.01418510567422,
                126 => -0.8017837257372732,
                127 => -1.6035674514745462,
                128 => 0.0,
                129 => 2.3008949665421112,
                130 => -1.01418510567422,
                131 => -0.21622499104693424,
                132 => 0.9128709291752769,
                135 => -0.5590169943749475,
                136 => -1.1338934190276817,
                137 => 1.0714285714285716,
                138 => -1.1338934190276817,
                139 => 1.984313483298443,
                140 => -0.6563301233138936,
                141 => -0.2878197989826109,
                142 => -0.4236592728681617,
                143 => 1.0377490433255416,
                144 => -0.6831300510639733,
                145 => 2.6457513110645907,
                146 => -0.6831300510639733,
                147 => -0.6831300510639733,
                148 => 0.9128709291752769,
                150 => 0.9128709291752769,
                151 => -1.4907119849998598,
                156 => -0.5669467095138409,
                157 => 1.0714285714285716,
                158 => 1.0714285714285716,
                159 => -1.4342743312012725,
                160 => 1.9321835661585918,
                161 => -0.6831300510639733,
                162 => -1.0583005244258363,
                163 => -0.6831300510639733,
                164 => 1.0954451150103321,
                165 => 0.8164965809277261,
                167 => -1.7320508075688774,
                176 => 0.0,
                177 => -0.6236095644623235,
                178 => -0.4236592728681617,
                179 => 0.724568837309472,
                180 => -0.4409585518440984,
                181 => -0.6831300510639733,
                182 => -0.4409585518440984,
                183 => 1.247219128924647,
                184 => -0.4409585518440984,
                185 => 2.6457513110645907,
                186 => -0.4409585518440984,
                187 => -0.9354143466934853,
                188 => -0.8208512602438095,
                189 => 2.3008949665421112,
                190 => -0.36380343755449945,
                191 => -0.7276068751089989,
                192 => -2.393172105652397,
                193 => -0.8208512602438095,
                194 => 2.8428212488760574,
                195 => 0.2754961485142392,
                196 => 1.4907119849998598,
                199 => -0.9128709291752769,
                200 => -0.75,
                201 => -0.5669467095138409,
                202 => 1.5000000000000002,
                203 => -0.375,
                204 => 1.1224972160321824,
                205 => -0.6563301233138936,
                206 => -0.9660917830792959,
                207 => 0.0,
                208 => -0.6831300510639733,
                209 => -0.4409585518440984,
                210 => 1.7078251276599332,
                211 => -0.6831300510639733,
                212 => -0.9128709291752769,
                214 => -0.9128709291752769,
                215 => 1.4907119849998598,
                216 => -1.414213562373095,
                218 => 1.414213562373095,
                220 => -0.75,
                221 => -1.1338934190276817,
                222 => -1.1338934190276817,
                223 => 2.3717082451262845,
                224 => -0.9354143466934853,
                225 => -0.4409585518440984,
                226 => 1.7078251276599332,
                227 => -0.4409585518440984,
                228 => -0.4898979485566356,
                229 => 1.0954451150103321,
                231 => -0.7745966692414834,
                232 => -0.9128709291752769,
                233 => 0.9128709291752769,
                236 => 1.4907119849998598,
                237 => 0.9128709291752769,
                238 => -1.3693063937629153,
                239 => -1.3693063937629153,
                240 => 0.5345224838248488,
                241 => 0.0,
                242 => -1.0377490433255416,
                243 => 0.0,
                244 => -0.9354143466934853,
                245 => 1.9321835661585918,
                246 => -0.9354143466934853,
                247 => -0.44095855184409855,
                248 => -0.6831300510639733,
                249 => -0.6831300510639733,
                250 => -0.6831300510639733,
                251 => 1.9321835661585918,
                252 => -2.393172105652397,
                253 => 0.0,
                254 => 0.5303300858899107,
                255 => 2.1213203435596424
            )
        );

        $oligoMock = $this->getMockBuilder('Amelaye\BioPHP\Domain\Tools\Service\OligosManager')
            ->setConstructorArgs([$this->apiNucleoMock])
            ->setMethods()
            ->getMock();

        $service = new DistanceAmongSequencesManager($oligoMock, $this->apiNucleoMock);
        $testFunction = $service->computeOligonucleotidsFrequencies($aSeq);

        $this->assertEquals($aExpected, $testFunction);
    }

    public function testComputeOligonucleotidsFrequenciesPearsonException()
    {
        $this->expectException(\Exception::class);

        $aSeq = false;

        $oligoMock = $this->getMockBuilder('AppBundle\Service\Misc\OligosManager')
            ->setConstructorArgs([$this->apiMock])
            ->setMethods()
            ->getMock();

        $service = new DistanceAmongSequencesManager($oligoMock, $this->apiMock);
        $service->computeOligonucleotidsFrequencies($aSeq);
    }


    public function testComputeDistancesAmongFrequenciesEuclidean()
    {
        $seqs = array(
            0 => "GGCAGATTCCCCCTAGACCCGCCCGCACCATGGTCAGGCATGCCCCTCCTCATCGCTGGGCACAGCCCAGAGGGTATAAACAGT
            GCTGGAGGCTGGCGGGGCAGGCCAGCTGAGTCCTGAGCAGCAGCCCAGCGCAGCCACCGAGACACCATGAGAGCCCTCACACTCCTCGCCC
            TATTGGCCCTGGCCGCACTTTGCATCGCTGGCCAGGCAGGTGAGTGCCCCCACCTCCCCTCAGGCCGCATTGCAGTGGGGGCTGAGAGGAG
            GAAGCACCATGGCCCACCTCTTCTCACCCCTTTGGCTGGCAGTCCCTTTGCAGTCTAACCACCTTGTTGCAGGCTCAATCCATTTGCCCCAGCTCTGCCCTTGCAG
            AGGGAGAGGAGGGAAGAGCAAGCTGCCCGAGACGCAGGGGAAGGAGGATGAGGGCCCTGGGGATGAGCTGGGGTGAACCAGGCTCCCTTTCCTTTGCAGGTGCGAAG
            CCCAGCGGTGCAGAGTCCAGCAAAGGTGCAGGTATGAGGATGGACCTGATGGGTTCCTGGACCCTCCCCTCTCACCCTGGTCCCTCAGTCTCATTCCCCCACTCCTG
            CCACCTCCTGTCTGGCCATCAGGAAGGCCAGCCTGCTCCCCACCTGATCCTCCCAAACCCAGAGCCACCTGATGCCTGCCCCTCTGCTCCACAGCCTTTGTGTCCAA
            GCAGGAGGGCAGCGAGGTAGTGAAGAGACCCAGGCGCTACCTGTATCAATGGCTGGGGTGAGAGAAAAGGCAGAGCTGGGCCAAGGCCCTGCCTCTCCGGGATGGTC
            TGTGGGGGAGCTGCAGCAGGGAGTGGCCTCTCTGGGTTGTGGTGGGGGTACAGGCAGCCTGCCCTGGTGGGCACCCTGGAGCCCCATGTGTAGGGAGAGGAGGGATG
            GGCATTTTGCACGGGGGCTGATGCCACCACGTCGGGTGTCTCAGAGCCCCAGTCCCCTACCCGGATCCCCTGGAGCCCAGGAGGGAGGTGTGTGAGCTCAATCCGGA
            CTGTGACGAGTTGGCTGACCACATCGGCTTTCAGGAGGCCTATCGGCGCTTCTACGGCCCGGTCTAGGGTGTCGCTCTGCTGGCCTGGCCGGCAACCCCAGTTCTGC
            TCCTCTCCAGGCACCCTTCTTTCCTCTTCCCCTTGCCCTTGCCCTGACCTCCCAGCCCTATGGATGTGGGGTCCCCATCATCCCAGCTGCTCCCAAATAAACTCCAGA
            AG",
            1 => "CCACTGCACTCACCGCACCCGGCCAATTTTTGTGTTTTTAGTAGAGACTAAATACCATATAGTGAACACCTAAGACGGGGGGCCTTGGATCCAGGGCGATT
            CAGAGGGCCCCGGTCGGAGCTGTCGGAGATTGAGCGCGCGCGGTCCCGGGATCTCCGACGAGGCCCTGGACCCCCGGGCGGCGAAGCTGCGGCGCGGCGCCCCCTGGA
            GGCCGCGGGACCCCTGGCCGGTCCGCGCAGGCGCAGCGGGGTCGCAGGGCGCGGCGGGTTCCAGCGCGGGGATGGCGCTGTCCGCGGAGGACCGGGCGCTGGTGCGC
            GCCCTGTGGAAGAAGCTGGGCAGCAACGTCGGCGTCTACACGACAGAGGCCCTGGAAAGGTGCGGCAGGCTGGGCGCCCCCGCCCCCAGGGGCCCTCCCTCCCCAAG
            CCCCCCGGACGCGCCTCACCCACGTTCCTCTCGCAGGACCTTCCTGGCTTTCCCCGCCACGAAGACCTACTTCTCCCACCTGGACCTGAGCCCCGGCTCCTCACAAG
            TCAGAGCCCACGGCCAGAAGGTGGCGGACGCGCTGAGCCTCGCCGTGGAGCGCCTGGACGACCTACCCCACGCGCTGTCCGCGCTGAGCCACCTGCACGCGTGCCAG
            CTGCGAGTGGACCCGGCCAGCTTCCAGGTGAGCGGCTGCCGTGCTGGGCCCCTGTCCCCGGGAGGGCCCCGGCGGGGTGGGTGCGGGGGGCGTGCGGGGCGGGTGCA
            GGCGAGTGAGCCTTGAGCGCTCGCCGCAGCTCCTGGGCCACTGCCTGCTGGTAACCCTCGCCCGGCACTACCCCGGAGACTTCAGCCCCGCGCTGCAGGCGTCGCTG
            GACAAGTTCCTGAGCCACGTTATCTCGGCGCTGGTTTCCGAGTACCGCTGAACTGTGGGTGGGTGGCCGCGGGATCCCCAGGCGACCTTCCCCGTGTTTGAGTAAAG
            CCTCTCCCAGGAGCAGCCTTCTTGCCGTGCTCTCTCGAGGTCAGGACGCGAGAGGAAGGCGC");

        $oligo_array = array(
            0 => array(
                "AA" => 0.43577235772358,
                "AC" => 0.61788617886179,
                "AG" => 1.3853658536585,
                "AT" => 0.48130081300813,
                "CA" => 1.2943089430894,
                "CC" => 2.009756097561,
                "CG" => 0.39024390243902,
                "CT" => 1.3853658536585,
                "GA" => 0.96910569105691,
                "GC" => 1.4829268292683,
                "GG" => 2.009756097561,
                "GT" => 0.61788617886179,
                "TA" => 0.22113821138211,
                "TC" => 0.96910569105691,
                "TG" => 1.2943089430894,
                "TT" => 0.43577235772358
            ),
            1 =>  array(
                "AA" => 0.39254170755643,
                "AC" => 0.72227674190383,
                "AG" => 1.0755642787046,
                "AT" => 0.17271835132483,
                "CA" => 0.86359175662414,
                "CC" => 2.0333660451423,
                "CG" => 1.6643768400393,
                "CT" => 1.0755642787046,
                "GA" => 0.87144259077527,
                "GC" => 2.0098135426889,
                "GG" => 2.0333660451423,
                "GT" => 0.72227674190383,
                "TA" => 0.23552502453386,
                "TC" => 0.87144259077527,
                "TG" => 0.86359175662414,
                "TT" => 0.39254170755643
            )
        );

        $len = 2;

        $aExpected = [
            [1 => 0.20175660877203]
        ];


        $oligoMock = $this->getMockBuilder('AppBundle\Service\Misc\OligosManager')
            ->setConstructorArgs([$this->apiMock])
            ->setMethods()
            ->getMock();

        $service = new DistanceAmongSequencesManager($oligoMock, $this->apiMock);
        $testFunction = $service->computeDistancesAmongFrequenciesEuclidean($seqs, $oligo_array, $len);

        $this->assertEquals($aExpected, $testFunction);
    }

    public function testComputeDistancesAmongFrequencies()
    {
        $seqs = array(
            0 => "GGCAGATTCCCCCTAGACCCGCCCGCACCATGGTCAGGCATGCCCCTCCTCATCGCTGGGCACAGCCCAGAGGGTATAAACAGT
            GCTGGAGGCTGGCGGGGCAGGCCAGCTGAGTCCTGAGCAGCAGCCCAGCGCAGCCACCGAGACACCATGAGAGCCCTCACACTCCTCGCCC
            TATTGGCCCTGGCCGCACTTTGCATCGCTGGCCAGGCAGGTGAGTGCCCCCACCTCCCCTCAGGCCGCATTGCAGTGGGGGCTGAGAGGAG
            GAAGCACCATGGCCCACCTCTTCTCACCCCTTTGGCTGGCAGTCCCTTTGCAGTCTAACCACCTTGTTGCAGGCTCAATCCATTTGCCCCAGCTCTGCCCTTGCAG
            AGGGAGAGGAGGGAAGAGCAAGCTGCCCGAGACGCAGGGGAAGGAGGATGAGGGCCCTGGGGATGAGCTGGGGTGAACCAGGCTCCCTTTCCTTTGCAGGTGCGAAG
            CCCAGCGGTGCAGAGTCCAGCAAAGGTGCAGGTATGAGGATGGACCTGATGGGTTCCTGGACCCTCCCCTCTCACCCTGGTCCCTCAGTCTCATTCCCCCACTCCTG
            CCACCTCCTGTCTGGCCATCAGGAAGGCCAGCCTGCTCCCCACCTGATCCTCCCAAACCCAGAGCCACCTGATGCCTGCCCCTCTGCTCCACAGCCTTTGTGTCCAA
            GCAGGAGGGCAGCGAGGTAGTGAAGAGACCCAGGCGCTACCTGTATCAATGGCTGGGGTGAGAGAAAAGGCAGAGCTGGGCCAAGGCCCTGCCTCTCCGGGATGGTC
            TGTGGGGGAGCTGCAGCAGGGAGTGGCCTCTCTGGGTTGTGGTGGGGGTACAGGCAGCCTGCCCTGGTGGGCACCCTGGAGCCCCATGTGTAGGGAGAGGAGGGATG
            GGCATTTTGCACGGGGGCTGATGCCACCACGTCGGGTGTCTCAGAGCCCCAGTCCCCTACCCGGATCCCCTGGAGCCCAGGAGGGAGGTGTGTGAGCTCAATCCGGA
            CTGTGACGAGTTGGCTGACCACATCGGCTTTCAGGAGGCCTATCGGCGCTTCTACGGCCCGGTCTAGGGTGTCGCTCTGCTGGCCTGGCCGGCAACCCCAGTTCTGC
            TCCTCTCCAGGCACCCTTCTTTCCTCTTCCCCTTGCCCTTGCCCTGACCTCCCAGCCCTATGGATGTGGGGTCCCCATCATCCCAGCTGCTCCCAAATAAACTCCAGA
            AG",
            1 => "CCACTGCACTCACCGCACCCGGCCAATTTTTGTGTTTTTAGTAGAGACTAAATACCATATAGTGAACACCTAAGACGGGGGGCCTTGGATCCAGGGCGATT
            CAGAGGGCCCCGGTCGGAGCTGTCGGAGATTGAGCGCGCGCGGTCCCGGGATCTCCGACGAGGCCCTGGACCCCCGGGCGGCGAAGCTGCGGCGCGGCGCCCCCTGGA
            GGCCGCGGGACCCCTGGCCGGTCCGCGCAGGCGCAGCGGGGTCGCAGGGCGCGGCGGGTTCCAGCGCGGGGATGGCGCTGTCCGCGGAGGACCGGGCGCTGGTGCGC
            GCCCTGTGGAAGAAGCTGGGCAGCAACGTCGGCGTCTACACGACAGAGGCCCTGGAAAGGTGCGGCAGGCTGGGCGCCCCCGCCCCCAGGGGCCCTCCCTCCCCAAG
            CCCCCCGGACGCGCCTCACCCACGTTCCTCTCGCAGGACCTTCCTGGCTTTCCCCGCCACGAAGACCTACTTCTCCCACCTGGACCTGAGCCCCGGCTCCTCACAAG
            TCAGAGCCCACGGCCAGAAGGTGGCGGACGCGCTGAGCCTCGCCGTGGAGCGCCTGGACGACCTACCCCACGCGCTGTCCGCGCTGAGCCACCTGCACGCGTGCCAG
            CTGCGAGTGGACCCGGCCAGCTTCCAGGTGAGCGGCTGCCGTGCTGGGCCCCTGTCCCCGGGAGGGCCCCGGCGGGGTGGGTGCGGGGGGCGTGCGGGGCGGGTGCA
            GGCGAGTGAGCCTTGAGCGCTCGCCGCAGCTCCTGGGCCACTGCCTGCTGGTAACCCTCGCCCGGCACTACCCCGGAGACTTCAGCCCCGCGCTGCAGGCGTCGCTG
            GACAAGTTCCTGAGCCACGTTATCTCGGCGCTGGTTTCCGAGTACCGCTGAACTGTGGGTGGGTGGCCGCGGGATCCCCAGGCGACCTTCCCCGTGTTTGAGTAAAG
            CCTCTCCCAGGAGCAGCCTTCTTGCCGTGCTCTCTCGAGGTCAGGACGCGAGAGGAAGGCGC");

        $oligo_array = [
            0 => [
                0 => -1.7632846107208,
                1 => 0.033314554300288,
                2 => 1.3845220528617,
                3 => 0.24242922642405,
                4 => -0.41606040759729,
                5 => 0.20936812079746,
                6 => -0.91579680504028,
                7 => 0.7493133229504,
                8 => -0.13407091215146,
                9 => -0.79562818183268,
                10 => 1.0246508332734,
                11 => -0.99601149945114,
                12 => 1.0062776440773,
                13 => -0.17668828484085,
                14 => 0.46376315601927,
                15 => -1.3441133229754,
                16 => 0.084267255135069,
                17 => 1.42326005922,
                18 => -1.0336892756379,
                19 => -0.33088655259798,
                20 => -0.13226535007946,
                21 => 0.40156067470518,
                22 => -0.3840409955144,
                23 => -0.021845190286231,
                24 => -0.21516574145597,
                25 => -0.83258417331118,
                26 => -0.26552831844568,
                27 => 2.0082135869224,
                28 => -0.10145392751532,
                29 => 0.67517974771639,
                30 => 0.069956069558296,
                31 => -0.99601149945114,
                32 => 0.34211167097887,
                33 => 1.4710355681057,
                34 => 0.66374090825985,
                35 => -2.7372225956663,
                36 => -1.2795749808284,
                37 => 0.0013715782315959,
                38 => 1.6869548020697,
                39 => 0.25289787397256,
                40 => 0.4991180300904,
                41 => -1.1409535806344,
                42 => 0.65817535036403,
                43 => -0.021845190286231,
                44 => -1.6163791127736,
                45 => 1.21768566754,
                46 => -0.57324988879058,
                47 => 0.7493133229504,
                48 => 1.8446713098875,
                49 => 0.57414931498473,
                50 => -0.04893915463695,
                51 => -1.7941390445678,
                52 => 0.80903998343695,
                53 => 0.8356455187465,
                54 => 1.6929039438726,
                55 => -2.7372225956663,
                56 => 0.78579945305261,
                57 => -1.4204991331273,
                58 => 0.97131967549271,
                59 => -0.33088655259798,
                60 => -0.7422848793457,
                61 => -0.7380401394778,
                62 => 0.78755815802777,
                63 => 0.24242922642405,
                64 => 1.7052021670062,
                65 => -0.18181970467918,
                66 => -1.9649307262435,
                67 => 0.78755815802777,
                68 => 0.43012904575144,
                69 => 0.5100318606548,
                70 => -0.87025735448977,
                71 => -0.57324988879058,
                72 => -0.87189454516757,
                73 => 1.9261775604709,
                74 => -0.9668811781623,
                75 => 0.069956069558296,
                76 => -1.1028666896162,
                77 => 2.3166035020509,
                78 => -1.8177532558334,
                79 => 0.46376315601927,
                80 => -1.3748349105452,
                81 => 0.063107239527234,
                82 => 0.1594111308828,
                83 => 0.97131967549271,
                84 => 1.0156318296405,
                85 => -1.6236752494598,
                86 => 0.29911902861222,
                87 => 0.65817535036403,
                88 => 0.26552831844568,
                89 => -0.73254252566748,
                90 => 0.64625574049524,
                91 => -0.26552831844568,
                92 => 0.69205448376205,
                93 => -0.066987081208988,
                94 => -0.9668811781623,
                95 => 1.0246508332734,
                96 => -0.53935382987559,
                97 => -0.074529984957724,
                98 => -0.82181809580879,
                99 => 1.6929039438726,
                100 => 0.30961260997028,
                101 => -1.0323657978863,
                102 => -1.3726971700492,
                103 => 1.6869548020697,
                104 => -0.37082933326166,
                105 => 0.32670980473368,
                106 => 0.29911902861222,
                107 => -0.3840409955144,
                108 => 0.50634876565755,
                109 => 1.2846051686382,
                110 => -0.87025735448977,
                111 => -0.91579680504028,
                112 => -0.18297285519205,
                113 => 0.91119747051933,
                114 => -0.70484033893734,
                115 => -0.04893915463695,
                116 => 0.7717728921141,
                117 => -0.79835140668527,
                118 => -0.82181809580879,
                119 => 0.66374090825985,
                120 => -0.3357561428119,
                121 => 0.79468233061805,
                122 => 0.1594111308828,
                123 => -1.0336892756379,
                124 => -1.5016017089454,
                125 => 1.4299979725661,
                126 => -1.9649307262435,
                127 => 1.3845220528617,
                128 => -0.82704094170427,
                129 => -0.20437921155687,
                130 => 1.4299979725661,
                131 => -0.7380401394778,
                132 => -0.55699275614835,
                133 => -1.1099645531833,
                134 => 1.2846051686382,
                135 => 1.21768566754,
                136 => 0.42288520426015,
                137 => -0.59673548396775,
                138 => -0.066987081208988,
                139 => 0.67517974771639,
                140 => -0.47551142058957,
                141 => -2.0072092289766,
                142 => 2.3166035020509,
                143 => -0.17668828484085,
                144 => 2.0216230385806,
                145 => -1.2996291668941,
                146 => 0.79468233061805,
                147 => -1.4204991331273,
                148 => 0.15944164194571,
                149 => 0.58062042851697,
                150 => 0.32670980473368,
                151 => -1.1409535806344,
                152 => 1.5264043177372,
                153 => -0.0099434745730614,
                154 => -0.73254252566748,
                155 => -0.83258417331118,
                156 => -1.6635142528644,
                157 => -0.59673548396775,
                158 => 1.9261775604709,
                159 => -0.79562818183268,
                160 => 0.75091644751218,
                161 => -0.43400638574899,
                162 => -0.79835140668527,
                163 => 0.8356455187465,
                164 => 0.53622185106594,
                165 => 0.088200451650222,
                166 => -1.0323657978863,
                167 => 0.0013715782315959,
                168 => 0.92968996192108,
                169 => 0.58062042851697,
                170 => -1.6236752494598,
                171 => 0.40156067470518,
                172 => 0.56333323089976,
                173 => -1.1099645531833,
                174 => 0.5100318606548,
                175 => 0.20936812079746,
                176 => -1.1708833169601,
                177 => -0.77746025264604,
                178 => 0.91119747051933,
                179 => 0.57414931498473,
                180 => -0.99416395617456,
                181 => -0.43400638574899,
                182 => -0.074529984957724,
                183 => 1.4710355681057,
                184 => 0.35174921716378,
                185 => -1.2996291668941,
                186 => 0.063107239527234,
                187 => 1.42326005922,
                188 => 0.80923628551917,
                189 => -0.20437921155687,
                190 => -0.18181970467918,
                191 => 0.033314554300288,
                192 => 1.5912648933146,
                193 => 0.80923628551917,
                194 => -1.5016017089454,
                195 => -0.7422848793457,
                196 => 0.54224171609676,
                197 => 0.56333323089976,
                198 => 0.50634876565755,
                199 => -1.6163791127736,
                200 => 1.1423210090353,
                201 => -1.6635142528644,
                202 => 0.69205448376205,
                203 => -0.10145392751532,
                204 => 1.3685517470295,
                205 => -0.47551142058957,
                206 => -1.1028666896162,
                207 => 1.0062776440773,
                208 => -0.80596703453214,
                209 => 0.35174921716378,
                210 => -0.3357561428119,
                211 => 0.78579945305261,
                212 => -1.2686313167299,
                213 => 0.92968996192108,
                214 => -0.37082933326166,
                215 => 0.4991180300904,
                216 => -1.9364916731037,
                217 => 1.5264043177372,
                218 => 0.26552831844568,
                219 => -0.21516574145597,
                220 => 1.1423210090353,
                221 => 0.42288520426015,
                222 => -0.87189454516757,
                223 => -0.13407091215146,
                224 => -0.8954695522391,
                225 => -0.99416395617456,
                226 => 0.7717728921141,
                227 => 0.80903998343695,
                228 => 0.47743171983307,
                229 => 0.53622185106594,
                230 => 0.30961260997028,
                231 => -1.2795749808284,
                232 => -1.2686313167299,
                233 => 0.15944164194571,
                234 => 1.0156318296405,
                235 => -0.13226535007946,
                236 => 0.54224171609676,
                237 => -0.55699275614835,
                238 => 0.43012904575144,
                239 => -0.41606040759729,
                240 => -0.56428566724309,
                241 => -1.1708833169601,
                242 => -0.18297285519205,
                243 => 1.8446713098875,
                244 => -0.8954695522391,
                245 => 0.75091644751218,
                246 => -0.53935382987559,
                247 => 0.34211167097887,
                248 => -0.80596703453214,
                249 => 2.0216230385806,
                250 => -1.3748349105452,
                251 => 0.084267255135069,
                252 => 1.5912648933146,
                253 => -0.82704094170427,
                254 => 1.7052021670062,
                255 => -1.7632846107208
            ],
            1 => [
                0 => 0.86837674882558,
                1 => 0.28777061162876,
                2 => -1.5209574906327,
                3 => 0.8830215713767,
                4 => 1.1863247010221,
                5 => -0.93313429372923,
                6 => 0.0078514099854144,
                7 => 0.073718800687562,
                8 => 0.40547103183844,
                9 => -0.75851766635238,
                10 => -0.2441296256487,
                11 => 1.0365814145423,
                12 => -0.16554408564083,
                13 => -0.27865221840769,
                14 => -0.80439966653984,
                15 => 1.0484458757252,
                16 => 1.5257923607235,
                17 => -0.57629001593147,
                18 => -0.21879144169782,
                19 => -0.54503935750968,
                20 => -1.1947527653196,
                21 => 0.63454143236044,
                22 => -1.4481923526871,
                23 => 1.9492359193364,
                24 => 0.78622030941611,
                25 => -0.99283622472766,
                26 => -0.87068440647999,
                27 => 2.0150721276426,
                28 => 3.1663778050794,
                29 => -0.33780811441856,
                30 => -2.031751587501,
                31 => 1.0365814145423,
                32 => 0.016723259925163,
                33 => -0.56393352595835,
                34 => -0.039662693449899,
                35 => 0.93793990150764,
                36 => -0.42711707198293,
                37 => -0.23944691776628,
                38 => -0.54187065621742,
                39 => 1.4056805456581,
                40 => 0.070400269618026,
                41 => 0.20979466133942,
                42 => -1.6152034533536,
                43 => 1.9492359193364,
                44 => 1.9979450289975,
                45 => -1.4814551730729,
                46 => 0.12100042160829,
                47 => 0.073718800687562,
                48 => 0.0,
                49 => -0.69282032302755,
                50 => -0.53452248382485,
                51 => 1.5336231610145,
                52 => -1.4584635536756,
                53 => 1.1213026623252,
                54 => -0.93589098865335,
                55 => 0.93793990150764,
                56 => -0.63130873955433,
                57 => -0.91417039023049,
                58 => 1.6834899721449,
                59 => -0.54503935750968,
                60 => -0.87038827977849,
                61 => -1.050625134279,
                62 => 1.0241831129984,
                63 => 0.8830215713767,
                64 => -0.51159219845112,
                65 => -1.1702699642015,
                66 => 0.79779992942706,
                67 => 1.0241831129984,
                68 => -1.1364029658095,
                69 => -0.29463342753357,
                70 => 1.1392224725322,
                71 => 0.12100042160829,
                72 => -2.3473016382394,
                73 => 1.8584022953985,
                74 => 1.2646635027504,
                75 => -2.031751587501,
                76 => 0.96527959984781,
                77 => 0.13540064007727,
                78 => -0.46904157598234,
                79 => -0.80439966653984,
                80 => -1.0309228547118,
                81 => 0.27917694734258,
                82 => -0.09855298945774,
                83 => 1.6834899721449,
                84 => -0.51425461351889,
                85 => 0.43366280650365,
                86 => 1.461760821705,
                87 => -1.6152034533536,
                88 => -0.7389265306749,
                89 => -0.39755778010537,
                90 => 1.53320687941,
                91 => -0.87068440647999,
                92 => -0.34111467364366,
                93 => -0.94473443891185,
                94 => 1.2646635027504,
                95 => -0.2441296256487,
                96 => -1.4955965286347,
                97 => 1.0593017440573,
                98 => 0.75470608900961,
                99 => -0.93589098865335,
                100 => 0.0,
                101 => 0.27852424952912,
                102 => 0.0,
                103 => -0.54187065621742,
                104 => -0.51744725124612,
                105 => 0.093673651244506,
                106 => 1.461760821705,
                107 => -1.4481923526871,
                108 => -2.0460657729979,
                109 => 0.14975934260178,
                110 => 1.1392224725322,
                111 => 0.0078514099854144,
                112 => 1.1952286093344,
                113 => 1.690308509457,
                114 => -2.3473823893079,
                115 => -0.53452248382485,
                116 => 2.1914861092709,
                117 => -2.2262839775373,
                118 => 0.75470608900961,
                119 => -0.039662693449899,
                120 => -0.25342197289133,
                121 => 0.47400188485514,
                122 => -0.09855298945774,
                123 => -0.21879144169782,
                124 => -1.4378266701013,
                125 => 1.593180881594,
                126 => 0.79779992942706,
                127 => -1.5209574906327,
                128 => -1.3686553717335,
                129 => 0.26284596272814,
                130 => 1.593180881594,
                131 => -1.050625134279,
                132 => 0.68891449005169,
                133 => 0.50465482725968,
                134 => 0.14975934260178,
                135 => -1.4814551730729,
                136 => 1.8105335012717,
                137 => -0.17621969769082,
                138 => -0.94473443891185,
                139 => -0.33780811441856,
                140 => -1.3003770192359,
                141 => 1.2507775359529,
                142 => 0.13540064007727,
                143 => -0.27865221840769,
                144 => -0.83971912275963,
                145 => 0.28724010091379,
                146 => 0.47400188485514,
                147 => -0.91417039023049,
                148 => -0.42525946946166,
                149 => 0.076849527825552,
                150 => 0.093673651244506,
                151 => 0.20979466133942,
                152 => 0.49298081169994,
                153 => 0.70081517612327,
                154 => -0.39755778010537,
                155 => -0.99283622472766,
                156 => -2.2428706494134,
                157 => -0.17621969769082,
                158 => 1.8584022953985,
                159 => -0.75851766635238,
                160 => 0.93868374498095,
                161 => 0.91452020049132,
                162 => -2.2262839775373,
                163 => 1.1213026623252,
                164 => -1.2231532594503,
                165 => 0.87272727272727,
                166 => 0.27852424952912,
                167 => -0.23944691776628,
                168 => -1.0785406942313,
                169 => 0.076849527825552,
                170 => 0.43366280650365,
                171 => 0.63454143236044,
                172 => 0.66826280629891,
                173 => 0.50465482725968,
                174 => -0.29463342753357,
                175 => -0.93313429372923,
                176 => 0.0,
                177 => -1.0954451150103,
                178 => 1.690308509457,
                179 => -0.69282032302755,
                180 => -1.8130028624739,
                181 => 0.91452020049132,
                182 => 1.0593017440573,
                183 => -0.56393352595835,
                184 => -0.20570932962085,
                185 => 0.28724010091379,
                186 => 0.27917694734258,
                187 => -0.57629001593147,
                188 => 0.71438661635067,
                189 => 0.26284596272814,
                190 => -1.1702699642015,
                191 => 0.28777061162876,
                192 => 1.5896269611447,
                193 => 0.71438661635067,
                194 => -1.4378266701013,
                195 => -0.87038827977849,
                196 => -0.48653815696489,
                197 => 0.66826280629891,
                198 => -2.0460657729979,
                199 => 1.9979450289975,
                200 => 0.59390137091395,
                201 => -2.2428706494134,
                202 => -0.34111467364366,
                203 => 3.1663778050794,
                204 => 1.0484458757252,
                205 => -1.3003770192359,
                206 => 0.96527959984781,
                207 => -0.16554408564083,
                208 => 1.0309228547118,
                209 => -0.20570932962085,
                210 => -0.25342197289133,
                211 => -0.63130873955433,
                212 => 1.8724177700113,
                213 => -1.0785406942313,
                214 => -0.51744725124612,
                215 => 0.070400269618026,
                216 => -0.4905038577884,
                217 => 0.49298081169994,
                218 => -0.7389265306749,
                219 => 0.78622030941611,
                220 => 0.59390137091395,
                221 => 1.8105335012717,
                222 => -2.3473016382394,
                223 => 0.40547103183844,
                224 => 0.3909280165389,
                225 => -1.8130028624739,
                226 => 2.1914861092709,
                227 => -1.4584635536756,
                228 => 2.2857142857143,
                229 => -1.2231532594503,
                230 => 0.0,
                231 => -0.42711707198293,
                232 => 1.8724177700113,
                233 => -0.42525946946166,
                234 => -0.51425461351889,
                235 => -1.1947527653196,
                236 => -0.48653815696489,
                237 => 0.68891449005169,
                238 => -1.1364029658095,
                239 => 1.1863247010221,
                240 => -1.3693063937629,
                241 => 0.0,
                242 => 1.1952286093344,
                243 => 0.0,
                244 => 0.3909280165389,
                245 => 0.93868374498095,
                246 => -1.4955965286347,
                247 => 0.016723259925163,
                248 => 1.0309228547118,
                249 => -0.83971912275963,
                250 => -1.0309228547118,
                251 => 1.5257923607235,
                252 => 1.5896269611447,
                253 => -1.3686553717335,
                254 => -0.51159219845112,
                255 => 0.86837674882558
            ]
        ];

        $aExpected = [
            [1 => 0.96402018103392]
        ];

        $oligoMock = $this->getMockBuilder('AppBundle\Service\Misc\OligosManager')
            ->setConstructorArgs([$this->apiMock])
            ->setMethods()
            ->getMock();

        $service = new DistanceAmongSequencesManager($oligoMock, $this->apiMock);
        $testFunction = $service->computeDistancesAmongFrequencies($seqs, $oligo_array);

        $this->assertEquals($aExpected, $testFunction);
    }

    public function testComputeZscoresForTetranucleotides()
    {
        $theseq = "GGCAGATTCCCCCTAGACCCGCCCGCACCATGGTCAGGCATGCCCCTCCTCATCGCTGGGCACAGCCCAGAGGGTATAAACAGTGCTGGAGGCTGGCGG
        GGCAGGCCAGCTGAGTCCTGAGCAGCAGCCCAGCGCAGCCACCGAGACACCATGAGAGCCCTCACACTCCTCGCCCTATTGGCCCTGGCCGCACTTTGCATCGCTGGCCA
        GGCAGGTGAGTGCCCCCACCTCCCCTCAGGCCGCATTGCAGTGGGGGCTGAGAGGAGGAAGCACCATGGCCCACCTCTTCTCACCCCTTTGGCTGGCAGTCCCTTTGCAG
        TCTAACCACCTTGTTGCAGGCTCAATCCATTTGCCCCAGCTCTGCCCTTGCAGAGGGAGAGGAGGGAAGAGCAAGCTGCCCGAGACGCAGGGGAAGGAGGATGAGGGCCC
        TGGGGATGAGCTGGGGTGAACCAGGCTCCCTTTCCTTTGCAGGTGCGAAGCCCAGCGGTGCAGAGTCCAGCAAAGGTGCAGGTATGAGGATGGACCTGATGGGTTCCTGG
        ACCCTCCCCTCTCACCCTGGTCCCTCAGTCTCATTCCCCCACTCCTGCCACCTCCTGTCTGGCCATCAGGAAGGCCAGCCTGCTCCCCACCTGATCCTCCCAAACCCAGA
        GCCACCTGATGCCTGCCCCTCTGCTCCACAGCCTTTGTGTCCAAGCAGGAGGGCAGCGAGGTAGTGAAGAGACCCAGGCGCTACCTGTATCAATGGCTGGGGTGAGAGAA
        AAGGCAGAGCTGGGCCAAGGCCCTGCCTCTCCGGGATGGTCTGTGGGGGAGCTGCAGCAGGGAGTGGCCTCTCTGGGTTGTGGTGGGGGTACAGGCAGCCTGCCCTGGTG
        GGCACCCTGGAGCCCCATGTGTAGGGAGAGGAGGGATGGGCATTTTGCACGGGGGCTGATGCCACCACGTCGGGTGTCTCAGAGCCCCAGTCCCCTACCCGGATCCCCTG
        GAGCCCAGGAGGGAGGTGTGTGAGCTCAATCCGGACTGTGACGAGTTGGCTGACCACATCGGCTTTCAGGAGGCCTATCGGCGCTTCTACGGCCCGGTCTAGGGTGTCGC
        TCTGCTGGCCTGGCCGGCAACCCCAGTTCTGCTCCTCTCCAGGCACCCTTCTTTCCTCTTCCCCTTGCCCTTGCCCTGACCTCCCAGCCCTATGGATGTGGGGTCCCCAT
        CATCCCAGCTGCTCCCAAATAAACTCCAGAAG";

        $aExpected = [
          0 => -1.7632846107208,
          1 => 0.033314554300288,
          2 => 1.3845220528617,
          3 => 0.24242922642405,
          4 => -0.41606040759729,
          5 => 0.20936812079746,
          6 => -0.91579680504028,
          7 => 0.7493133229504,
          8 => -0.13407091215146,
          9 => -0.79562818183268,
          10 => 1.0246508332734,
          11 => -0.99601149945114,
          12 => 1.0062776440773,
          13 => -0.17668828484085,
          14 => 0.46376315601927,
          15 => -1.3441133229754,
          16 => 0.084267255135069,
          17 => 1.42326005922,
          18 => -1.0336892756379,
          19 => -0.33088655259798,
          20 => -0.13226535007946,
          21 => 0.40156067470518,
          22 => -0.3840409955144,
          23 => -0.021845190286231,
          24 => -0.21516574145597,
          25 => -0.83258417331118,
          26 => -0.26552831844568,
          27 => 2.0082135869224,
          28 => -0.10145392751532,
          29 => 0.67517974771639,
          30 => 0.069956069558296,
          31 => -0.99601149945114,
          32 => 0.34211167097887,
          33 => 1.4710355681057,
          34 => 0.66374090825985,
          35 => -2.7372225956663,
          36 => -1.2795749808284,
          37 => 0.0013715782315959,
          38 => 1.6869548020697,
          39 => 0.25289787397256,
          40 => 0.4991180300904,
          41 => -1.1409535806344,
          42 => 0.65817535036403,
          43 => -0.021845190286231,
          44 => -1.6163791127736,
          45 => 1.21768566754,
          46 => -0.57324988879058,
          47 => 0.7493133229504,
          48 => 1.8446713098875,
          49 => 0.57414931498473,
          50 => -0.04893915463695,
          51 => -1.7941390445678,
          52 => 0.80903998343695,
          53 => 0.8356455187465,
          54 => 1.6929039438726,
          55 => -2.7372225956663,
          56 => 0.78579945305261,
          57 => -1.4204991331273,
          58 => 0.97131967549271,
          59 => -0.33088655259798,
          60 => -0.7422848793457,
          61 => -0.7380401394778,
          62 => 0.78755815802777,
          63 => 0.24242922642405,
          64 => 1.7052021670062,
          65 => -0.18181970467918,
          66 => -1.9649307262435,
          67 => 0.78755815802777,
          68 => 0.43012904575144,
          69 => 0.5100318606548,
          70 => -0.87025735448977,
          71 => -0.57324988879058,
          72 => -0.87189454516757,
          73 => 1.9261775604709,
          74 => -0.9668811781623,
          75 => 0.069956069558296,
          76 => -1.1028666896162,
          77 => 2.3166035020509,
          78 => -1.8177532558334,
          79 => 0.46376315601927,
          80 => -1.3748349105452,
          81 => 0.063107239527234,
          82 => 0.1594111308828,
          83 => 0.97131967549271,
          84 => 1.0156318296405,
          85 => -1.6236752494598,
          86 => 0.29911902861222,
          87 => 0.65817535036403,
          88 => 0.26552831844568,
          89 => -0.73254252566748,
          90 => 0.64625574049524,
          91 => -0.26552831844568,
          92 => 0.69205448376205,
          93 => -0.066987081208988,
          94 => -0.9668811781623,
          95 => 1.0246508332734,
          96 => -0.53935382987559,
          97 => -0.074529984957724,
          98 => -0.82181809580879,
          99 => 1.6929039438726,
          100 => 0.30961260997028,
          101 => -1.0323657978863,
          102 => -1.3726971700492,
          103 => 1.6869548020697,
          104 => -0.37082933326166,
          105 => 0.32670980473368,
          106 => 0.29911902861222,
          107 => -0.3840409955144,
          108 => 0.50634876565755,
          109 => 1.2846051686382,
          110 => -0.87025735448977,
          111 => -0.91579680504028,
          112 => -0.18297285519205,
          113 => 0.91119747051933,
          114 => -0.70484033893734,
          115 => -0.04893915463695,
          116 => 0.7717728921141,
          117 => -0.79835140668527,
          118 => -0.82181809580879,
          119 => 0.66374090825985,
          120 => -0.3357561428119,
          121 => 0.79468233061805,
          122 => 0.1594111308828,
          123 => -1.0336892756379,
          124 => -1.5016017089454,
          125 => 1.4299979725661,
          126 => -1.9649307262435,
          127 => 1.3845220528617,
          128 => -0.82704094170427,
          129 => -0.20437921155687,
          130 => 1.4299979725661,
          131 => -0.7380401394778,
          132 => -0.55699275614835,
          133 => -1.1099645531833,
          134 => 1.2846051686382,
          135 => 1.21768566754,
          136 => 0.42288520426015,
          137 => -0.59673548396775,
          138 => -0.066987081208988,
          139 => 0.67517974771639,
          140 => -0.47551142058957,
          141 => -2.0072092289766,
          142 => 2.3166035020509,
          143 => -0.17668828484085,
          144 => 2.0216230385806,
          145 => -1.2996291668941,
          146 => 0.79468233061805,
          147 => -1.4204991331273,
          148 => 0.15944164194571,
          149 => 0.58062042851697,
          150 => 0.32670980473368,
          151 => -1.1409535806344,
          152 => 1.5264043177372,
          153 => -0.0099434745730614,
          154 => -0.73254252566748,
          155 => -0.83258417331118,
          156 => -1.6635142528644,
          157 => -0.59673548396775,
          158 => 1.9261775604709,
          159 => -0.79562818183268,
          160 => 0.75091644751218,
          161 => -0.43400638574899,
          162 => -0.79835140668527,
          163 => 0.8356455187465,
          164 => 0.53622185106594,
          165 => 0.088200451650222,
          166 => -1.0323657978863,
          167 => 0.0013715782315959,
          168 => 0.92968996192108,
          169 => 0.58062042851697,
          170 => -1.6236752494598,
          171 => 0.40156067470518,
          172 => 0.56333323089976,
          173 => -1.1099645531833,
          174 => 0.5100318606548,
          175 => 0.20936812079746,
          176 => -1.1708833169601,
          177 => -0.77746025264604,
          178 => 0.91119747051933,
          179 => 0.57414931498473,
          180 => -0.99416395617456,
          181 => -0.43400638574899,
          182 => -0.074529984957724,
          183 => 1.4710355681057,
          184 => 0.35174921716378,
          185 => -1.2996291668941,
          186 => 0.063107239527234,
          187 => 1.42326005922,
          188 => 0.80923628551917,
          189 => -0.20437921155687,
          190 => -0.18181970467918,
          191 => 0.033314554300288,
          192 => 1.5912648933146,
          193 => 0.80923628551917,
          194 => -1.5016017089454,
          195 => -0.7422848793457,
          196 => 0.54224171609676,
          197 => 0.56333323089976,
          198 => 0.50634876565755,
          199 => -1.6163791127736,
          200 => 1.1423210090353,
          201 => -1.6635142528644,
          202 => 0.69205448376205,
          203 => -0.10145392751532,
          204 => 1.3685517470295,
          205 => -0.47551142058957,
          206 => -1.1028666896162,
          207 => 1.0062776440773,
          208 => -0.80596703453214,
          209 => 0.35174921716378,
          210 => -0.3357561428119,
          211 => 0.78579945305261,
          212 => -1.2686313167299,
          213 => 0.92968996192108,
          214 => -0.37082933326166,
          215 => 0.4991180300904,
          216 => -1.9364916731037,
          217 => 1.5264043177372,
          218 => 0.26552831844568,
          219 => -0.21516574145597,
          220 => 1.1423210090353,
          221 => 0.42288520426015,
          222 => -0.87189454516757,
          223 => -0.13407091215146,
          224 => -0.8954695522391,
          225 => -0.99416395617456,
          226 => 0.7717728921141,
          227 => 0.80903998343695,
          228 => 0.47743171983307,
          229 => 0.53622185106594,
          230 => 0.30961260997028,
          231 => -1.2795749808284,
          232 => -1.2686313167299,
          233 => 0.15944164194571,
          234 => 1.0156318296405,
          235 => -0.13226535007946,
          236 => 0.54224171609676,
          237 => -0.55699275614835,
          238 => 0.43012904575144,
          239 => -0.41606040759729,
          240 => -0.56428566724309,
          241 => -1.1708833169601,
          242 => -0.18297285519205,
          243 => 1.8446713098875,
          244 => -0.8954695522391,
          245 => 0.75091644751218,
          246 => -0.53935382987559,
          247 => 0.34211167097887,
          248 => -0.80596703453214,
          249 => 2.0216230385806,
          250 => -1.3748349105452,
          251 => 0.084267255135069,
          252 => 1.5912648933146,
          253 => -0.82704094170427,
          254 => 1.7052021670062,
          255 => -1.7632846107208
        ];

        $oligoMock = $this->getMockBuilder('AppBundle\Service\Misc\OligosManager')
            ->setConstructorArgs([$this->apiMock])
            ->setMethods(['findZScore'])
            ->getMock();
        $oligoMock->method('findZScore')
            ->willReturn($aExpected);

        $service = new DistanceAmongSequencesManager($oligoMock, $this->apiMock);
        $testFunction = $service->computeZscoresForTetranucleotides($theseq);

        $this->assertEquals($aExpected, $testFunction);
    }

    public function testPearsonDistance()
    {
        $valsx = [
          0 => -1.7632846107208,
          1 => 0.033314554300288,
          2 => 1.3845220528617,
          3 => 0.24242922642405,
          4 => -0.41606040759729,
          5 => 0.20936812079746,
          6 => -0.91579680504028,
          7 => 0.7493133229504,
          8 => -0.13407091215146,
          9 => -0.79562818183268,
          10 => 1.0246508332734,
          11 => -0.99601149945114,
          12 => 1.0062776440773,
          13 => -0.17668828484085,
          14 => 0.46376315601927,
          15 => -1.3441133229754,
          16 => 0.084267255135069,
          17 => 1.42326005922,
          18 => -1.0336892756379,
          19 => -0.33088655259798,
          20 => -0.13226535007946,
          21 => 0.40156067470518,
          22 => -0.3840409955144,
          23 => -0.021845190286231,
          24 => -0.21516574145597,
          25 => -0.83258417331118,
          26 => -0.26552831844568,
          27 => 2.0082135869224,
          28 => -0.10145392751532,
          29 => 0.67517974771639,
          30 => 0.069956069558296,
          31 => -0.99601149945114,
          32 => 0.34211167097887,
          33 => 1.4710355681057,
          34 => 0.66374090825985,
          35 => -2.7372225956663,
          36 => -1.2795749808284,
          37 => 0.0013715782315959,
          38 => 1.6869548020697,
          39 => 0.25289787397256,
          40 => 0.4991180300904,
          41 => -1.1409535806344,
          42 => 0.65817535036403,
          43 => -0.021845190286231,
          44 => -1.6163791127736,
          45 => 1.21768566754,
          46 => -0.57324988879058,
          47 => 0.7493133229504,
          48 => 1.8446713098875,
          49 => 0.57414931498473,
          50 => -0.04893915463695,
          51 => -1.7941390445678,
          52 => 0.80903998343695,
          53 => 0.8356455187465,
          54 => 1.6929039438726,
          55 => -2.7372225956663,
          56 => 0.78579945305261,
          57 => -1.4204991331273,
          58 => 0.97131967549271,
          59 => -0.33088655259798,
          60 => -0.7422848793457,
          61 => -0.7380401394778,
          62 => 0.78755815802777,
          63 => 0.24242922642405,
          64 => 1.7052021670062,
          65 => -0.18181970467918,
          66 => -1.9649307262435,
          67 => 0.78755815802777,
          68 => 0.43012904575144,
          69 => 0.5100318606548,
          70 => -0.87025735448977,
          71 => -0.57324988879058,
          72 => -0.87189454516757,
          73 => 1.9261775604709,
          74 => -0.9668811781623,
          75 => 0.069956069558296,
          76 => -1.1028666896162,
          77 => 2.3166035020509,
          78 => -1.8177532558334,
          79 => 0.46376315601927,
          80 => -1.3748349105452,
          81 => 0.063107239527234,
          82 => 0.1594111308828,
          83 => 0.97131967549271,
          84 => 1.0156318296405,
          85 => -1.6236752494598,
          86 => 0.29911902861222,
          87 => 0.65817535036403,
          88 => 0.26552831844568,
          89 => -0.73254252566748,
          90 => 0.64625574049524,
          91 => -0.26552831844568,
          92 => 0.69205448376205,
          93 => -0.066987081208988,
          94 => -0.9668811781623,
          95 => 1.0246508332734,
          96 => -0.53935382987559,
          97 => -0.074529984957724,
          98 => -0.82181809580879,
          99 => 1.6929039438726,
          100 => 0.30961260997028,
          101 => -1.0323657978863,
          102 => -1.3726971700492,
          103 => 1.6869548020697,
          104 => -0.37082933326166,
          105 => 0.32670980473368,
          106 => 0.29911902861222,
          107 => -0.3840409955144,
          108 => 0.50634876565755,
          109 => 1.2846051686382,
          110 => -0.87025735448977,
          111 => -0.91579680504028,
          112 => -0.18297285519205,
          113 => 0.91119747051933,
          114 => -0.70484033893734,
          115 => -0.04893915463695,
          116 => 0.7717728921141,
          117 => -0.79835140668527,
          118 => -0.82181809580879,
          119 => 0.66374090825985,
          120 => -0.3357561428119,
          121 => 0.79468233061805,
          122 => 0.1594111308828,
          123 => -1.0336892756379,
          124 => -1.5016017089454,
          125 => 1.4299979725661,
          126 => -1.9649307262435,
          127 => 1.3845220528617,
          128 => -0.82704094170427,
          129 => -0.20437921155687,
          130 => 1.4299979725661,
          131 => -0.7380401394778,
          132 => -0.55699275614835,
          133 => -1.1099645531833,
          134 => 1.2846051686382,
          135 => 1.21768566754,
          136 => 0.42288520426015,
          137 => -0.59673548396775,
          138 => -0.066987081208988,
          139 => 0.67517974771639,
          140 => -0.47551142058957,
          141 => -2.0072092289766,
          142 => 2.3166035020509,
          143 => -0.17668828484085,
          144 => 2.0216230385806,
          145 => -1.2996291668941,
          146 => 0.79468233061805,
          147 => -1.4204991331273,
          148 => 0.15944164194571,
          149 => 0.58062042851697,
          150 => 0.32670980473368,
          151 => -1.1409535806344,
          152 => 1.5264043177372,
          153 => -0.0099434745730614,
          154 => -0.73254252566748,
          155 => -0.83258417331118,
          156 => -1.6635142528644,
          157 => -0.59673548396775,
          158 => 1.9261775604709,
          159 => -0.79562818183268,
          160 => 0.75091644751218,
          161 => -0.43400638574899,
          162 => -0.79835140668527,
          163 => 0.8356455187465,
          164 => 0.53622185106594,
          165 => 0.088200451650222,
          166 => -1.0323657978863,
          167 => 0.0013715782315959,
          168 => 0.92968996192108,
          169 => 0.58062042851697,
          170 => -1.6236752494598,
          171 => 0.40156067470518,
          172 => 0.56333323089976,
          173 => -1.1099645531833,
          174 => 0.5100318606548,
          175 => 0.20936812079746,
          176 => -1.1708833169601,
          177 => -0.77746025264604,
          178 => 0.91119747051933,
          179 => 0.57414931498473,
          180 => -0.99416395617456,
          181 => -0.43400638574899,
          182 => -0.074529984957724,
          183 => 1.4710355681057,
          184 => 0.35174921716378,
          185 => -1.2996291668941,
          186 => 0.063107239527234,
          187 => 1.42326005922,
          188 => 0.80923628551917,
          189 => -0.20437921155687,
          190 => -0.18181970467918,
          191 => 0.033314554300288,
          192 => 1.5912648933146,
          193 => 0.80923628551917,
          194 => -1.5016017089454,
          195 => -0.7422848793457,
          196 => 0.54224171609676,
          197 => 0.56333323089976,
          198 => 0.50634876565755,
          199 => -1.6163791127736,
          200 => 1.1423210090353,
          201 => -1.6635142528644,
          202 => 0.69205448376205,
          203 => -0.10145392751532,
          204 => 1.3685517470295,
          205 => -0.47551142058957,
          206 => -1.1028666896162,
          207 => 1.0062776440773,
          208 => -0.80596703453214,
          209 => 0.35174921716378,
          210 => -0.3357561428119,
          211 => 0.78579945305261,
          212 => -1.2686313167299,
          213 => 0.92968996192108,
          214 => -0.37082933326166,
          215 => 0.4991180300904,
          216 => -1.9364916731037,
          217 => 1.5264043177372,
          218 => 0.26552831844568,
          219 => -0.21516574145597,
          220 => 1.1423210090353,
          221 => 0.42288520426015,
          222 => -0.87189454516757,
          223 => -0.13407091215146,
          224 => -0.8954695522391,
          225 => -0.99416395617456,
          226 => 0.7717728921141,
          227 => 0.80903998343695,
          228 => 0.47743171983307,
          229 => 0.53622185106594,
          230 => 0.30961260997028,
          231 => -1.2795749808284,
          232 => -1.2686313167299,
          233 => 0.15944164194571,
          234 => 1.0156318296405,
          235 => -0.13226535007946,
          236 => 0.54224171609676,
          237 => -0.55699275614835,
          238 => 0.43012904575144,
          239 => -0.41606040759729,
          240 => -0.56428566724309,
          241 => -1.1708833169601,
          242 => -0.18297285519205,
          243 => 1.8446713098875,
          244 => -0.8954695522391,
          245 => 0.75091644751218,
          246 => -0.53935382987559,
          247 => 0.34211167097887,
          248 => -0.80596703453214,
          249 => 2.0216230385806,
          250 => -1.3748349105452,
          251 => 0.084267255135069,
          252 => 1.5912648933146,
          253 => -0.82704094170427,
          254 => 1.7052021670062,
          255 => -1.7632846107208
        ];

        $valsy = [
          0 => 0.86837674882558,
          1 => 0.28777061162876,
          2 => -1.5209574906327,
          3 => 0.8830215713767,
          4 => 1.1863247010221,
          5 => -0.93313429372923,
          6 => 0.0078514099854144,
          7 => 0.073718800687562,
          8 => 0.40547103183844,
          9 => -0.75851766635238,
          10 => -0.2441296256487,
          11 => 1.0365814145423,
          12 => -0.16554408564083,
          13 => -0.27865221840769,
          14 => -0.80439966653984,
          15 => 1.0484458757252,
          16 => 1.5257923607235,
          17 => -0.57629001593147,
          18 => -0.21879144169782,
          19 => -0.54503935750968,
          20 => -1.1947527653196,
          21 => 0.63454143236044,
          22 => -1.4481923526871,
          23 => 1.9492359193364,
          24 => 0.78622030941611,
          25 => -0.99283622472766,
          26 => -0.87068440647999,
          27 => 2.0150721276426,
          28 => 3.1663778050794,
          29 => -0.33780811441856,
          30 => -2.031751587501,
          31 => 1.0365814145423,
          32 => 0.016723259925163,
          33 => -0.56393352595835,
          34 => -0.039662693449899,
          35 => 0.93793990150764,
          36 => -0.42711707198293,
          37 => -0.23944691776628,
          38 => -0.54187065621742,
          39 => 1.4056805456581,
          40 => 0.070400269618026,
          41 => 0.20979466133942,
          42 => -1.6152034533536,
          43 => 1.9492359193364,
          44 => 1.9979450289975,
          45 => -1.4814551730729,
          46 => 0.12100042160829,
          47 => 0.073718800687562,
          48 => 0.0,
          49 => -0.69282032302755,
          50 => -0.53452248382485,
          51 => 1.5336231610145,
          52 => -1.4584635536756,
          53 => 1.1213026623252,
          54 => -0.93589098865335,
          55 => 0.93793990150764,
          56 => -0.63130873955433,
          57 => -0.91417039023049,
          58 => 1.6834899721449,
          59 => -0.54503935750968,
          60 => -0.87038827977849,
          61 => -1.050625134279,
          62 => 1.0241831129984,
          63 => 0.8830215713767,
          64 => -0.51159219845112,
          65 => -1.1702699642015,
          66 => 0.79779992942706,
          67 => 1.0241831129984,
          68 => -1.1364029658095,
          69 => -0.29463342753357,
          70 => 1.1392224725322,
          71 => 0.12100042160829,
          72 => -2.3473016382394,
          73 => 1.8584022953985,
          74 => 1.2646635027504,
          75 => -2.031751587501,
          76 => 0.96527959984781,
          77 => 0.13540064007727,
          78 => -0.46904157598234,
          79 => -0.80439966653984,
          80 => -1.0309228547118,
          81 => 0.27917694734258,
          82 => -0.09855298945774,
          83 => 1.6834899721449,
          84 => -0.51425461351889,
          85 => 0.43366280650365,
          86 => 1.461760821705,
          87 => -1.6152034533536,
          88 => -0.7389265306749,
          89 => -0.39755778010537,
          90 => 1.53320687941,
          91 => -0.87068440647999,
          92 => -0.34111467364366,
          93 => -0.94473443891185,
          94 => 1.2646635027504,
          95 => -0.2441296256487,
          96 => -1.4955965286347,
          97 => 1.0593017440573,
          98 => 0.75470608900961,
          99 => -0.93589098865335,
          100 => 0.0,
          101 => 0.27852424952912,
          102 => 0.0,
          103 => -0.54187065621742,
          104 => -0.51744725124612,
          105 => 0.093673651244506,
          106 => 1.461760821705,
          107 => -1.4481923526871,
          108 => -2.0460657729979,
          109 => 0.14975934260178,
          110 => 1.1392224725322,
          111 => 0.0078514099854144,
          112 => 1.1952286093344,
          113 => 1.690308509457,
          114 => -2.3473823893079,
          115 => -0.53452248382485,
          116 => 2.1914861092709,
          117 => -2.2262839775373,
          118 => 0.75470608900961,
          119 => -0.039662693449899,
          120 => -0.25342197289133,
          121 => 0.47400188485514,
          122 => -0.09855298945774,
          123 => -0.21879144169782,
          124 => -1.4378266701013,
          125 => 1.593180881594,
          126 => 0.79779992942706,
          127 => -1.5209574906327,
          128 => -1.3686553717335,
          129 => 0.26284596272814,
          130 => 1.593180881594,
          131 => -1.050625134279,
          132 => 0.68891449005169,
          133 => 0.50465482725968,
          134 => 0.14975934260178,
          135 => -1.4814551730729,
          136 => 1.8105335012717,
          137 => -0.17621969769082,
          138 => -0.94473443891185,
          139 => -0.33780811441856,
          140 => -1.3003770192359,
          141 => 1.2507775359529,
          142 => 0.13540064007727,
          143 => -0.27865221840769,
          144 => -0.83971912275963,
          145 => 0.28724010091379,
          146 => 0.47400188485514,
          147 => -0.91417039023049,
          148 => -0.42525946946166,
          149 => 0.076849527825552,
          150 => 0.093673651244506,
          151 => 0.20979466133942,
          152 => 0.49298081169994,
          153 => 0.70081517612327,
          154 => -0.39755778010537,
          155 => -0.99283622472766,
          156 => -2.2428706494134,
          157 => -0.17621969769082,
          158 => 1.8584022953985,
          159 => -0.75851766635238,
          160 => 0.93868374498095,
          161 => 0.91452020049132,
          162 => -2.2262839775373,
          163 => 1.1213026623252,
          164 => -1.2231532594503,
          165 => 0.87272727272727,
          166 => 0.27852424952912,
          167 => -0.23944691776628,
          168 => -1.0785406942313,
          169 => 0.076849527825552,
          170 => 0.43366280650365,
          171 => 0.63454143236044,
          172 => 0.66826280629891,
          173 => 0.50465482725968,
          174 => -0.29463342753357,
          175 => -0.93313429372923,
          176 => 0.0,
          177 => -1.0954451150103,
          178 => 1.690308509457,
          179 => -0.69282032302755,
          180 => -1.8130028624739,
          181 => 0.91452020049132,
          182 => 1.0593017440573,
          183 => -0.56393352595835,
          184 => -0.20570932962085,
          185 => 0.28724010091379,
          186 => 0.27917694734258,
          187 => -0.57629001593147,
          188 => 0.71438661635067,
          189 => 0.26284596272814,
          190 => -1.1702699642015,
          191 => 0.28777061162876,
          192 => 1.5896269611447,
          193 => 0.71438661635067,
          194 => -1.4378266701013,
          195 => -0.87038827977849,
          196 => -0.48653815696489,
          197 => 0.66826280629891,
          198 => -2.0460657729979,
          199 => 1.9979450289975,
          200 => 0.59390137091395,
          201 => -2.2428706494134,
          202 => -0.34111467364366,
          203 => 3.1663778050794,
          204 => 1.0484458757252,
          205 => -1.3003770192359,
          206 => 0.96527959984781,
          207 => -0.16554408564083,
          208 => 1.0309228547118,
          209 => -0.20570932962085,
          210 => -0.25342197289133,
          211 => -0.63130873955433,
          212 => 1.8724177700113,
          213 => -1.0785406942313,
          214 => -0.51744725124612,
          215 => 0.070400269618026,
          216 => -0.4905038577884,
          217 => 0.49298081169994,
          218 => -0.7389265306749,
          219 => 0.78622030941611,
          220 => 0.59390137091395,
          221 => 1.8105335012717,
          222 => -2.3473016382394,
          223 => 0.40547103183844,
          224 => 0.3909280165389,
          225 => -1.8130028624739,
          226 => 2.1914861092709,
          227 => -1.4584635536756,
          228 => 2.2857142857143,
          229 => -1.2231532594503,
          230 => 0.0,
          231 => -0.42711707198293,
          232 => 1.8724177700113,
          233 => -0.42525946946166,
          234 => -0.51425461351889,
          235 => -1.1947527653196,
          236 => -0.48653815696489,
          237 => 0.68891449005169,
          238 => -1.1364029658095,
          239 => 1.1863247010221,
          240 => -1.3693063937629,
          241 => 0.0,
          242 => 1.1952286093344,
          243 => 0.0,
          244 => 0.3909280165389,
          245 => 0.93868374498095,
          246 => -1.4955965286347,
          247 => 0.016723259925163,
          248 => 1.0309228547118,
          249 => -0.83971912275963,
          250 => -1.0309228547118,
          251 => 1.5257923607235,
          252 => 1.5896269611447,
          253 => -1.3686553717335,
          254 => -0.51159219845112,
          255 => 0.86837674882558
        ];

        $expected = 0.96402018103392;

        $oligoMock = $this->getMockBuilder('AppBundle\Service\Misc\OligosManager')
            ->setConstructorArgs([$this->apiMock])
            ->setMethods()
            ->getMock();

        $service = new DistanceAmongSequencesManager($oligoMock, $this->apiMock);
        $testFunction = $service->pearsonDistance($valsx, $valsy);

        $this->assertEquals($expected, $testFunction);
    }

    public function testPearsonDistanceException()
    {
        $this->expectException(\Exception::class);
        $valsx = 0;

        $valsy = 5;

        $oligoMock = $this->getMockBuilder('AppBundle\Service\Misc\OligosManager')
            ->setConstructorArgs([$this->apiMock])
            ->setMethods()
            ->getMock();

        $service = new DistanceAmongSequencesManager($oligoMock, $this->apiMock);
        $service->pearsonDistance($valsx, $valsy);
    }

    public function testEuclidDistance()
    {
        $a = [
            "AA" => 0.43577235772358,
            "AC" => 0.61788617886179,
            "AG" => 1.3853658536585,
            "AT" => 0.48130081300813,
            "CA" => 1.2943089430894,
            "CC" => 2.009756097561,
            "CG" => 0.39024390243902,
            "CT" => 1.3853658536585,
            "GA" => 0.96910569105691,
            "GC" => 1.4829268292683,
            "GG" => 2.009756097561,
            "GT" => 0.61788617886179,
            "TA" => 0.22113821138211,
            "TC" => 0.96910569105691,
            "TG" => 1.2943089430894,
            "TT" => 0.43577235772358
        ];

        $b = [
            "AA" => 0.39254170755643,
            "AC" => 0.72227674190383,
            "AG" => 1.0755642787046,
            "AT" => 0.17271835132483,
            "CA" => 0.86359175662414,
            "CC" => 2.0333660451423,
            "CG" => 1.6643768400393,
            "CT" => 1.0755642787046,
            "GA" => 0.87144259077527,
            "GC" => 2.0098135426889,
            "GG" => 2.0333660451423,
            "GT" => 0.72227674190383,
            "TA" => 0.23552502453386,
            "TC" => 0.87144259077527,
            "TG" => 0.86359175662414,
            "TT" => 0.39254170755643
        ];

        $len = 2;

        $fExpected = 0.20175660877203;

        $oligoMock = $this->getMockBuilder('AppBundle\Service\Misc\OligosManager')
            ->setConstructorArgs([$this->apiMock])
            ->setMethods()
            ->getMock();

        $service = new DistanceAmongSequencesManager($oligoMock, $this->apiMock);
        $testFunction = $service->euclidDistance($a,$b,$len);

        $this->assertEquals($fExpected, $testFunction);
    }

    public function testEuclidDistanceException()
    {
        $this->expectException(\Exception::class);
        $a = 4;
        $b = 6;
        $len = 0;

        $oligoMock = $this->getMockBuilder('AppBundle\Service\Misc\OligosManager')
            ->setConstructorArgs([$this->apiMock])
            ->setMethods()
            ->getMock();

        $service = new DistanceAmongSequencesManager($oligoMock, $this->apiMock);
        $service->euclidDistance($a,$b,$len);
    }

    public function testStandardFrecuencies()
    {
        $array = [
            "AA" => 67,
            "AC" => 95,
            "AG" => 213,
            "AT" => 74,
            "CA" => 199,
            "CC" => 309,
            "CG" => 60,
            "CT" => 213,
            "GA" => 149,
            "GC" => 228,
            "GG" => 309,
            "GT" => 95,
            "TA" => 34,
            "TC" => 149,
            "TG" => 199,
            "TT" => 67
        ];
        $len = 2;

        $aExpected = [
            "AA" => 0.43577235772358,
            "AC" => 0.61788617886179,
            "AG" => 1.3853658536585,
            "AT" => 0.48130081300813,
            "CA" => 1.2943089430894,
            "CC" => 2.009756097561,
            "CG" => 0.39024390243902,
            "CT" => 1.3853658536585,
            "GA" => 0.96910569105691,
            "GC" => 1.4829268292683,
            "GG" => 2.009756097561,
            "GT" => 0.61788617886179,
            "TA" => 0.22113821138211,
            "TC" => 0.96910569105691,
            "TG" => 1.2943089430894,
            "TT" => 0.43577235772358
        ];

        $oligoMock = $this->getMockBuilder('AppBundle\Service\Misc\OligosManager')
            ->setConstructorArgs([$this->apiMock])
            ->setMethods()
            ->getMock();

        $service = new DistanceAmongSequencesManager($oligoMock, $this->apiMock);
        $testFunction = $service->standardFrecuencies($array, $len);

        $this->assertEquals($aExpected, $testFunction);
    }

    public function testStandardFrecuenciesException()
    {
        $this->expectException(\Exception::class);
        $array = 4;
        $len = 2;

        $oligoMock = $this->getMockBuilder('AppBundle\Service\Misc\OligosManager')
            ->setConstructorArgs([$this->apiMock])
            ->setMethods()
            ->getMock();

        $service = new DistanceAmongSequencesManager($oligoMock, $this->apiMock);
        $service->standardFrecuencies($array, $len);
    }
}
