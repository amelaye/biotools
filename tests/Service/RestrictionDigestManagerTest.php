<?php
namespace Tests\MinitoolsBundle\Service;

use Amelaye\BioPHP\Api\TypeIIbEndonucleaseApi;
use Amelaye\BioPHP\Api\TypeIIEndonucleaseApi;
use Amelaye\BioPHP\Api\TypeIIsEndonucleaseApi;
use Amelaye\BioPHP\Api\VendorApi;
use Amelaye\BioPHP\Api\VendorLinkApi;
use App\Service\RestrictionDigestManager;
use PHPUnit\Framework\TestCase;

class RestrictionDigestManagerTest extends TestCase
{
    protected $aType2;

    protected $type2s;

    protected $type2b;

    public function setUp()
    {
        require 'samples/Vendors.php';
        require 'samples/VendorLinks.php';
        require 'samples/TypeIIEndonucleases.php';
        require 'samples/Type2sEndonucleases.php';
        require 'samples/Type2bEndonucleases.php';

        $vendorLinks = $vendorLinksObjects;
        $this->aType2 = $aTypeIIEndonucleases;
        $this->type2s = $aTypeIIsEndonucleases;
        $this->type2b = $aTypeIIbEndonucleases;
        $vendors = $vendorsObjects;

        /**
         * Mock API
         */
        $clientMock = $this->getMockBuilder('GuzzleHttp\Client')->getMock();
        $serializerMock = \JMS\Serializer\SerializerBuilder::create()
            ->build();

        $this->apiVendorLinksMock = $this->getMockBuilder(VendorLinkApi::class)
            ->disableOriginalConstructor()
            ->setMethods(['getVendorLinks'])
            ->getMock();
        $this->apiVendorLinksMock->method("getVendorLinks")->will($this->returnValue($vendorLinks));

        $this->apiTypeIIMock = $this->getMockBuilder(TypeIIEndonucleaseApi::class)
            ->disableOriginalConstructor()
            ->setMethods(['getTypeIIEndonucleases'])
            ->getMock();
        $this->apiTypeIIMock->method("getTypeIIEndonucleases")->will($this->returnValue($this->aType2));

        $this->apiTypeIIsMock = $this->getMockBuilder(TypeIIsEndonucleaseApi::class)
            ->disableOriginalConstructor()
            ->setMethods(['getTypeIIsEndonucleases'])
            ->getMock();
        $this->apiTypeIIsMock->method("getTypeIIsEndonucleases")->will($this->returnValue($this->type2s));

        $this->apiTypeIIbMock = $this->getMockBuilder(TypeIIbEndonucleaseApi::class)
            ->disableOriginalConstructor()
            ->setMethods(['getTypeIIbEndonucleases'])
            ->getMock();
        $this->apiTypeIIbMock->method("getTypeIIbEndonucleases")->will($this->returnValue($this->type2b));

        $this->apiVendorMock = $this->getMockBuilder(VendorApi::class)
            ->disableOriginalConstructor()
            ->setMethods(['getVendors'])
            ->getMock();
        $this->apiVendorMock->method("getVendors")->will($this->returnValue($vendors));
    }

    public function testGetVendors()
    {
        $message = "";
        $enzyme = "BfaI,FspBI,MaeI,XspI";
        $sExpected = [
          "BfaI" => [
            "company" => [
              "name" => "N",
              "url" => "http://rebase.neb.com/rebase/enz/BfaI.html",
            ],
            "links" => [
              0 => [
                "name" => "New England Biolabs",
                "url" => "http://www.neb.com",
              ]
            ]
          ],
          "FspBI" => [
            "company" => [
              "name" => "F",
              "url" => "http://rebase.neb.com/rebase/enz/FspBI.html",
            ],
            "links" => [
              0 => [
                "name" => "Fermentas AB",
                "url" => "http://www.fermentas.com",
              ]
            ]
          ],
          "MaeI" => [
            "company" => [
              "name" => "M",
              "url" => "http://rebase.neb.com/rebase/enz/MaeI.html",
            ],
            "links" => [
              0 => [
                "name" => "Roche Applied Science",
                "url" => "http://www.roche.com",
              ]
            ]
          ],
          "XspI" => [
            "company" => [
              "name" => "K",
              "url" => "http://rebase.neb.com/rebase/enz/XspI.html",
            ],
            "links" => [
              0 => [
                "name" => "Takara Shuzo Co. Ltd.",
                "url" => "http://www.takarashuzo.co.jp/english/index.htm",
              ]
            ]
          ]
        ];

        $service = new RestrictionDigestManager($this->apiVendorLinksMock, $this->apiTypeIIMock,
            $this->apiTypeIIbMock, $this->apiTypeIIsMock, $this->apiVendorMock);
        $testFunction = $service->getVendors($message, $enzyme);

        $this->assertEquals($sExpected, $testFunction);
    }

    public function testGetNucleolasesInfosOnlyType2()
    {
        $bIIs = false;
        $bIIb = false;
        $bDefined = true;

        $service = new RestrictionDigestManager($this->apiVendorLinksMock, $this->apiTypeIIMock,
            $this->apiTypeIIbMock, $this->apiTypeIIsMock, $this->apiVendorMock);
        $testFunction = $service->getNucleolasesInfos($bIIs, $bIIb, $bDefined);

        $this->assertEquals($this->aType2, $testFunction);
    }

    public function testGetNucleolasesInfosType2AndType2b()
    {
        $bIIs = false;
        $bIIb = true;
        $bDefined = false;

        $service = new RestrictionDigestManager($this->apiMock);
        $testFunction = $service->getNucleolasesInfos($bIIs, $bIIb, $bDefined);

        $aExpected = array_merge($this->aType2, $this->type2b);

        $this->assertEquals($aExpected, $testFunction);
    }

    public function testGetNucleolasesInfosType2AndType2s()
    {
        $bIIs = true;
        $bIIb = false;
        $bDefined = false;

        $service = new RestrictionDigestManager($this->apiMock);
        $testFunction = $service->getNucleolasesInfos($bIIs, $bIIb, $bDefined);

        $aExpected = array_merge($this->aType2, $this->type2s);

        $this->assertEquals($aExpected, $testFunction);
    }

    public function testGetNucleolasesInfosType2AndType2bAndType2s()
    {
        $bIIs = true;
        $bIIb = true;
        $bDefined = false;

        $service = new RestrictionDigestManager($this->apiMock);
        $testFunction = $service->getNucleolasesInfos($bIIs, $bIIb, $bDefined);

        $aExpected = array_merge($this->aType2, $this->type2b);
        $aExpected = array_merge($aExpected, $this->type2s);

        $this->assertEquals($aExpected, $testFunction);
    }

    public function testReduceEnzymesArray()
    {
        $aEnzymes = $this->aType2;
        $iMinimum = 3;
        $iRetype = 1;
        $bDefinedSq = false;
        $sWre = "AarI";
        $aExpected = [
          "AatI" =>  [
            0 => "AatI,Eco147I,PceI,SseBI,StuI",
            1 => "AGG'CCT",
            2 => "(AGGCCT)",
            3 => 6,
            4 => 3,
            5 => 0,
            6 => 6,
          ],
          "Acc16I" =>  [
            0 => "Acc16I,AviII,FspI,NsbI",
            1 => "TGC'GCA",
            2 => "(TGCGCA)",
            3 => 6,
            4 => 3,
            5 => 0,
            6 => 6,
          ],
          "AccBSI" =>  [
            0 => "AccBSI,BsrBI,MbiI",
            1 => "CCG'CTC",
            2 => "(CCGCTC|GAGCGG)",
            3 => 6,
            4 => 3,
            5 => 0,
            6 => 6,
          ],
          "AccII" =>  [
            0 => "AccII,Bsh1236I,BspFNI,BstFNI,BstUI,MvnI",
            1 => "CG'CG",
            2 => "(CGCG)",
            3 => 4,
            4 => 2,
            5 => 0,
            6 => 4,
          ],
          "AcvI" =>  [
            0 => "AcvI,BbrPI,Eco72I,PmaCI,PmlI,PspCI",
            1 => "CAC'GTG",
            2 => "(CACGTG)",
            3 => 6,
            4 => 3,
            5 => 0,
            6 => 6,
          ],
          "AfaI" =>  [
            0 => "AfaI,RsaI",
            1 => "GT'AC",
            2 => "(GTAC)",
            3 => 4,
            4 => 2,
            5 => 0,
            6 => 4,
          ],
          "AfeI" =>  [
            0 => "AfeI,Aor51HI,Eco47III",
            1 => "AGC'GCT",
            2 => "(AGCGCT)",
            3 => 6,
            4 => 3,
            5 => 0,
            6 => 6,
          ],
          "AjiI" =>  [
            0 => "AjiI,BmgBI,BtrI",
            1 => "CAC'GTC",
            2 => "(CACGTC|GACGTG)",
            3 => 6,
            4 => 3,
            5 => 0,
            6 => 6,
          ],
          "AleI" =>  [
            0 => "AleI,OliI",
            1 => "CACNN'NNGTG",
            2 => "(CAC....GTG)",
            3 => 10,
            4 => 5,
            5 => 0,
            6 => 6,
          ],
          "AluI" =>  [
            0 => "AluI,AluBI",
            1 => "AG'CT",
            2 => "(AGCT)",
            3 => 4,
            4 => 2,
            5 => 0,
            6 => 4,
          ],
          "Asp700I" =>  [
            0 => "Asp700I,MroXI,PdmI,XmnI",
            1 => "GAANN'NNTTC",
            2 => "(GAA....TTC)",
            3 => 10,
            4 => 5,
            5 => 0,
            6 => 6,
          ],
          "AssI" =>  [
            0 => "AssI,BmcAI,ScaI,ZrmI",
            1 => "AGT'ACT",
            2 => "(AGTACT)",
            3 => 6,
            4 => 3,
            5 => 0,
            6 => 6,
          ],
          "BalI" =>  [
            0 => "BalI,MlsI,MluNI,MscI,Msp20I",
            1 => "TGG'CCA",
            2 => "(TGGCCA)",
            3 => 6,
            4 => 3,
            5 => 0,
            6 => 6,
          ],
          "BmiI" =>  [
            0 => "BmiI,BspLI,NlaIV,PspN4I",
            1 => "GGN'NCC",
            2 => "(GG..CC)",
            3 => 6,
            4 => 3,
            5 => 0,
            6 => 4,
          ],
          "BoxI" =>  [
            0 => "BoxI,PshAI,BstPAI",
            1 => "GACNN'NNGTC",
            2 => "(GAC....GTC)",
            3 => 10,
            4 => 5,
            5 => 0,
            6 => 6,
          ],
          "BsaAI" =>  [
            0 => "BsaAI,BstBAI,Ppu21I",
            1 => "YAC'GTR",
            2 => "(CACGTA|CACGTG|TACGTA|TACGTG)",
            3 => 6,
            4 => 3,
            5 => 0,
            6 => 6,
          ],
          "BsaBI" =>  [
            0 => "BsaBI,Bse8I,BseJI,MamI",
            1 => "GATNN'NNATC",
            2 => "(GAT....ATC)",
            3 => 10,
            4 => 5,
            5 => 0,
            6 => 6,
          ],
          "BshFI" =>  [
            0 => "BshFI,BsnI,BspANI,BsuRI,HaeIII,PhoI",
            1 => "GG'CC",
            2 => "(GGCC)",
            3 => 4,
            4 => 2,
            5 => 0,
            6 => 4,
          ],
          "Bsp68I" =>  [
            0 => "Bsp68I,BtuMI,NruI,RruI",
            1 => "TCG'CGA",
            2 => "(TCGCGA)",
            3 => 6,
            4 => 3,
            5 => 0,
            6 => 6,
          ],
          "BssNAI" =>  [
            0 => "BssNAI,Bst1107I,BstZ17I",
            1 => "GTA'TAC",
            2 => "(GTATAC)",
            3 => 6,
            4 => 3,
            5 => 0,
            6 => 6,
          ],
          "BstC8I" =>  [
            0 => "BstC8I,Cac8I",
            1 => "GCN'NGC",
            2 => "(GC..GC)",
            3 => 6,
            4 => 3,
            5 => 0,
            6 => 4,
          ],
          "BstSNI" =>  [
            0 => "BstSNI,Eco105I,SnaBI",
            1 => "TAC'GTA",
            2 => "(TACGTA)",
            3 => 6,
            4 => 3,
            5 => 0,
            6 => 6,
          ],
          "CviJI" =>  [
            0 => "CviJI,CviKI-1",
            1 => "RG'CY",
            2 => "(AGCC|AGCT|GGCC|GGCT)",
            3 => 4,
            4 => 2,
            5 => 0,
            6 => 4,
          ],
          "DpnI" =>  [
            0 => "DpnI,MalI",
            1 => "GA'TC",
            2 => "(GATC)",
            3 => 4,
            4 => 2,
            5 => 0,
            6 => 4,
          ],
          "DraI" =>  [
            0 => "DraI",
            1 => "TTT'AAA",
            2 => "(TTTAAA)",
            3 => 6,
            4 => 3,
            5 => 0,
            6 => 6,
          ],
          "Ecl136II" =>  [
            0 => "Ecl136II,Eco53kI,EcoICRI",
            1 => "GAG'CTC",
            2 => "(GAGCTC)",
            3 => 6,
            4 => 3,
            5 => 0,
            6 => 6,
          ],
          "Eco32I" =>  [
            0 => "Eco32I,EcoRV",
            1 => "GAT'ATC",
            2 => "(GATATC)",
            3 => 6,
            4 => 3,
            5 => 0,
            6 => 6,
          ],
          "EgeI" =>  [
            0 => "EgeI,EheI,SfoI",
            1 => "GGC'GCC",
            2 => "(GGCGCC)",
            3 => 6,
            4 => 3,
            5 => 0,
            6 => 6,
          ],
          "FaiI" =>  [
            0 => "FaiI",
            1 => "YA'TR",
            2 => "(CATA|CATG|TATA|TATG)",
            3 => 4,
            4 => 2,
            5 => 0,
            6 => 4,
          ],
          "FspAI" =>  [
            0 => "FspAI",
            1 => "RTGC'GCAY",
            2 => "(ATGCGCAC|ATGCGCAT|GTGCGCAC|GTGCGCAT)",
            3 => 8,
            4 => 4,
            5 => 0,
            6 => 8,
          ],
          "GlaI" =>  [
            0 => "GlaI",
            1 => "GC'GC",
            2 => "(GCGC)",
            3 => 4,
            4 => 2,
            5 => 0,
            6 => 4,
          ],
          "HincII" =>  [
            0 => "HincII,HindII",
            1 => "GTY'RAC",
            2 => "(GTCAAC|GTCGAC|GTTAAC|GTTGAC)",
            3 => 6,
            4 => 3,
            5 => 0,
            6 => 6,
          ],
          "HpaI" =>  [
            0 => "HpaI,KspAI",
            1 => "GTT'AAC",
            2 => "(GTTAAC)",
            3 => 6,
            4 => 3,
            5 => 0,
            6 => 6,
          ],
          "Hpy166II" =>  [
            0 => "Hpy166II,Hpy8I",
            1 => "GTN'NAC",
            2 => "(GT..AC)",
            3 => 6,
            4 => 3,
            5 => 0,
            6 => 4,
          ],
          "HpyCH4V" =>  [
            0 => "HpyCH4V",
            1 => "TG'CA",
            2 => "(TGCA)",
            3 => 4,
            4 => 2,
            5 => 0,
            6 => 4,
          ],
          "MslI" =>  [
            0 => "MslI,RseI,SmiMI",
            1 => "CAYNN'NNRTG",
            2 => "(CAC....ATG|CAC....GTG|CAT....ATG|CAT....GTG)",
            3 => 10,
            4 => 5,
            5 => 0,
            6 => 6,
          ],
          "MspA1I" =>  [
            0 => "MspA1I",
            1 => "CMG'CKG",
            2 => "(CAGCGG|CAGCTG|CCGCGG|CCGCTG)",
            3 => 6,
            4 => 3,
            5 => 0,
            6 => 6,
          ],
          "MssI" =>  [
            0 => "MssI,PmeI",
            1 => "GTTT'AAAC",
            2 => "(GTTTAAAC)",
            3 => 8,
            4 => 4,
            5 => 0,
            6 => 8,
          ],
          "NaeI" =>  [
            0 => "NaeI,PdiI",
            1 => "GCC'GGC",
            2 => "(GCCGGC)",
            3 => 6,
            4 => 3,
            5 => 0,
            6 => 6,
          ],
          "PsiI" =>  [
            0 => "AanI,PsiI",
            1 => "TTA'TAA",
            2 => "(TTATAA)",
            3 => 6,
            4 => 3,
            5 => 0,
            6 => 6,
          ],
          "PvuII" =>  [
            0 => "PvuII",
            1 => "CAG'CTG",
            2 => "(CAGCTG)",
            3 => 6,
            4 => 3,
            5 => 0,
            6 => 6,
          ],
          "SmaI" =>  [
            0 => "SmaI",
            1 => "CCC'GGG",
            2 => "(CCCGGG)",
            3 => 6,
            4 => 3,
            5 => 0,
            6 => 6,
          ],
          "SmiI" =>  [
            0 => "SmiI,SwaI",
            1 => "ATTT'AAAT",
            2 => "(ATTTAAAT)",
            3 => 8,
            4 => 4,
            5 => 0,
            6 => 8,
          ],
          "SrfI" =>  [
            0 => "SrfI",
            1 => "GCCC'GGGC",
            2 => "(GCCCGGGC)",
            3 => 8,
            4 => 4,
            5 => 0,
            6 => 8,
          ],
          "SspI" =>  [
            0 => "SspI",
            1 => "AAT'ATT",
            2 => "(AATATT)",
            3 => 6,
            4 => 3,
            5 => 0,
            6 => 6,
          ],
          "ZraI" =>  [
            0 => "ZraI",
            1 => "GAC'GTC",
            2 => "(GACGTC)",
            3 => 6,
            4 => 3,
            5 => 0,
            6 => 6,
          ]
        ];

        $service = new RestrictionDigestManager($this->apiMock);
        $testFunction = $service->reduceEnzymesArray($aEnzymes, $iMinimum, $iRetype, $bDefinedSq, $sWre);
        $this->assertEquals($aExpected, $testFunction);
    }

    public function testReduceEnzymesArrayWreNull()
    {
        $aEnzymes = $this->aType2;
        $iMinimum = 8;
        $iRetype = 1;
        $bDefinedSq = false;
        $sWre = "";
        $aExpected = [
          "FspAI" => [
            0 => "FspAI",
            1 => "RTGC'GCAY",
            2 => "(ATGCGCAC|ATGCGCAT|GTGCGCAC|GTGCGCAT)",
            3 => 8,
            4 => 4,
            5 => 0,
            6 => 8,
          ],
          "MssI" => [
            0 => "MssI,PmeI",
            1 => "GTTT'AAAC",
            2 => "(GTTTAAAC)",
            3 => 8,
            4 => 4,
            5 => 0,
            6 => 8,
          ],
          "SmiI" => [
            0 => "SmiI,SwaI",
            1 => "ATTT'AAAT",
            2 => "(ATTTAAAT)",
            3 => 8,
            4 => 4,
            5 => 0,
            6 => 8,
          ],
          "SrfI" => [
            0 => "SrfI",
            1 => "GCCC'GGGC",
            2 => "(GCCCGGGC)",
            3 => 8,
            4 => 4,
            5 => 0,
            6 => 8,
          ]
        ];

        $service = new RestrictionDigestManager($this->apiMock);
        $testFunction = $service->reduceEnzymesArray($aEnzymes, $iMinimum, $iRetype, $bDefinedSq, $sWre);
        $this->assertEquals($aExpected, $testFunction);
    }
    
    public function testRestrictionDigest()
    {
        $aEnzymes = $this->aType2;
        $sSequence = "ACGTACGTACGTTAGCTAGCTAGCTAGC";

        $aExpected = [
          "AfaI" => [
            "cuts" => [
              4 => "",
              8 => "",
            ],
          ],
          "AluI" => [
            "cuts" => [
              15 => "",
              19 => "",
              23 => "",
            ],
          ],
          "AsuNHI" => [
            "cuts" => [
              15 => "",
              19 => "",
              23 => "",
            ],
          ],
          "BfaI" => [
            "cuts" => [
              16 => "",
              20 => "",
              24 => "",
            ],
          ],
          "BmtI" => [
            "cuts" => [
              19 => "",
              23 => "",
              27 => "",
            ],
          ],
          "BsaAI" => [
            "cuts" => [
              6 => ""
            ],
          ],
          "BsiWI" => [
            "cuts" => [
              2 => "",
              6 => "",
            ],
          ],
          "BstC8I" => [
            "cuts" => [
              17 => "",
              21 => "",
              25 => "",
            ],
          ],
          "BstSNI" => [
            "cuts" => [
              6 => ""
            ]
          ],
          "Csp6I" => [
            "cuts" => [
              3 => "",
              7 => "",
            ]
          ],
          "CviJI" => [
            "cuts" => [
              15 => "",
              19 => "",
              23 => "",
            ]
          ],
          "HpyCH4IV" => [
            "cuts" => [
              1 => "",
              5 => "",
              9 => "",
            ]
          ],
          "SetI" => [
            "cuts" => [
              4 => "",
              8 => "",
              12 => "",
              17 => "",
              21 => "",
              25 => "",
            ]
          ],
          "TaiI" => [
            "cuts" => [
              4 => "",
              8 => "",
              12 => "",
            ]
          ]
        ];

        $service = new RestrictionDigestManager($this->apiMock);
        $testFunction = $service->restrictionDigest($aEnzymes, $sSequence);
        $this->assertEquals($aExpected, $testFunction);
    }

    public function testExtractSequencesOneSeq()
    {
        $sSequence = "ACGTACGTACGTTAGCTAGCTAGCTAGC";

        $aExpected = [
          0 => [
            "seq" => "ACGTACGTACGTTAGCTAGCTAGCTAGC"
          ]
        ];

        $service = new RestrictionDigestManager($this->apiMock);
        $testFunction = $service->extractSequences($sSequence);
        $this->assertEquals($aExpected, $testFunction);
    }

    public function testExtractSequencesTwoSeqs()
    {
        $sSequence = "ACGTACGTACGTTAGCTAGCTAGCTAGC>GTACGTTAGCTGTACGTTAGCT";

        $aExpected = [
          0 => [
            "seq" => "ACGTACGTACGTTAGCTAGCTAGCTAGC",
            "name" => ""
          ],
          1 =>  [
            "seq" => "GTACGTTAGCTGTACGTTAGCT",
            "name" => ""
          ]
        ];

        $service = new RestrictionDigestManager($this->apiMock);
        $testFunction = $service->extractSequences($sSequence);
        $this->assertEquals($aExpected, $testFunction);
    }

    public function testEnzymesForMultiSeqWreNullShowdiffs()
    {
        $aSequence = [
          0 => [
            "seq" => "ACGTACGTACGTTAGCTAGCTAGCTAGC",
            "name" => "",
          ],
          1 => [
            "seq" => "GTACGTTAGCTGTACGTTAGCT",
            "name" => "",
          ]
        ];

        $aDigestion = [
          0 => [
            "AfaI" => [
              "cuts" => [
                4 => "",
                8 => ""
              ]
            ],
            "AluI" => [
              "cuts" => [
                15 => "",
                19 => "",
                23 => ""
              ]
            ],
            "BstSNI" => [
              "cuts" => [
                6 => ""
              ]
            ]
          ],
          1 => [
            "AfaI" => [
              "cuts" => [
                2 => "",
                13 => ""
              ]
            ],
            "AluI" => [
              "cuts" => [
                9 => "",
                20 => ""
              ]
            ]
          ]
        ];

        $aEnzymes = [
          "AatI" => [
            0 => "AatI,Eco147I,PceI,SseBI,StuI",
            1 => "AGG'CCT",
            2 => "(AGGCCT)",
            3 => 6,
            4 => 3,
            5 => 0,
            6 => 6,
          ],
          "Acc16I" => [
            0 => "Acc16I,AviII,FspI,NsbI",
            1 => "TGC'GCA",
            2 => "(TGCGCA)",
            3 => 6,
            4 => 3,
            5 => 0,
            6 => 6,
          ],
          "AccII" => [
            0 => "AccII,Bsh1236I,BspFNI,BstFNI,BstUI,MvnI",
            1 => "CG'CG",
            2 => "(CGCG)",
            3 => 4,
            4 => 2,
            5 => 0,
            6 => 4,
          ],
          "AcvI" => [
            0 => "AcvI,BbrPI,Eco72I,PmaCI,PmlI,PspCI",
            1 => "CAC'GTG",
            2 => "(CACGTG)",
            3 => 6,
            4 => 3,
            5 => 0,
            6 => 6,
          ],
          "AfaI" => [
            0 => "AfaI,RsaI",
            1 => "GT'AC",
            2 => "(GTAC)",
            3 => 4,
            4 => 2,
            5 => 0,
            6 => 4,
          ],
          "AfeI" => [
            0 => "AfeI,Aor51HI,Eco47III",
            1 => "AGC'GCT",
            2 => "(AGCGCT)",
            3 => 6,
            4 => 3,
            5 => 0,
            6 => 6,
          ],
          "AluI" => [
            0 => "AluI,AluBI",
            1 => "AG'CT",
            2 => "(AGCT)",
            3 => 4,
            4 => 2,
            5 => 0,
            6 => 4,
          ],
          "AssI" => [
            0 => "AssI,BmcAI,ScaI,ZrmI",
            1 => "AGT'ACT",
            2 => "(AGTACT)",
            3 => 6,
            4 => 3,
            5 => 0,
            6 => 6,
          ],
          "BalI" => [
            0 => "BalI,MlsI,MluNI,MscI,Msp20I",
            1 => "TGG'CCA",
            2 => "(TGGCCA)",
            3 => 6,
            4 => 3,
            5 => 0,
            6 => 6,
          ],
          "BshFI" => [
            0 => "BshFI,BsnI,BspANI,BsuRI,HaeIII,PhoI",
            1 => "GG'CC",
            2 => "(GGCC)",
            3 => 4,
            4 => 2,
            5 => 0,
            6 => 4,
          ],
          "Bsp68I" => [
            0 => "Bsp68I,BtuMI,NruI,RruI",
            1 => "TCG'CGA",
            2 => "(TCGCGA)",
            3 => 6,
            4 => 3,
            5 => 0,
            6 => 6,
          ],
          "BssNAI" => [
            0 => "BssNAI,Bst1107I,BstZ17I",
            1 => "GTA'TAC",
            2 => "(GTATAC)",
            3 => 6,
            4 => 3,
            5 => 0,
            6 => 6,
          ],
          "BstSNI" => [
            0 => "BstSNI,Eco105I,SnaBI",
            1 => "TAC'GTA",
            2 => "(TACGTA)",
            3 => 6,
            4 => 3,
            5 => 0,
            6 => 6,
          ],
          "DpnI" => [
            0 => "DpnI,MalI",
            1 => "GA'TC",
            2 => "(GATC)",
            3 => 4,
            4 => 2,
            5 => 0,
            6 => 4,
          ],
          "DraI" => [
            0 => "DraI",
            1 => "TTT'AAA",
            2 => "(TTTAAA)",
            3 => 6,
            4 => 3,
            5 => 0,
            6 => 6,
          ],
          "Ecl136II" => [
            0 => "Ecl136II,Eco53kI,EcoICRI",
            1 => "GAG'CTC",
            2 => "(GAGCTC)",
            3 => 6,
            4 => 3,
            5 => 0,
            6 => 6,
          ],
          "Eco32I" => [
            0 => "Eco32I,EcoRV",
            1 => "GAT'ATC",
            2 => "(GATATC)",
            3 => 6,
            4 => 3,
            5 => 0,
            6 => 6,
          ],
          "EgeI" => [
            0 => "EgeI,EheI,SfoI",
            1 => "GGC'GCC",
            2 => "(GGCGCC)",
            3 => 6,
            4 => 3,
            5 => 0,
            6 => 6,
          ],
          "GlaI" => [
            0 => "GlaI",
            1 => "GC'GC",
            2 => "(GCGC)",
            3 => 4,
            4 => 2,
            5 => 0,
            6 => 4,
          ],
          "HpaI" => [
            0 => "HpaI,KspAI",
            1 => "GTT'AAC",
            2 => "(GTTAAC)",
            3 => 6,
            4 => 3,
            5 => 0,
            6 => 6,
          ],
          "HpyCH4V" => [
            0 => "HpyCH4V",
            1 => "TG'CA",
            2 => "(TGCA)",
            3 => 4,
            4 => 2,
            5 => 0,
            6 => 4,
          ],
          "MssI" => [
            0 => "MssI,PmeI",
            1 => "GTTT'AAAC",
            2 => "(GTTTAAAC)",
            3 => 8,
            4 => 4,
            5 => 0,
            6 => 8,
          ],
          "NaeI" => [
            0 => "NaeI,PdiI",
            1 => "GCC'GGC",
            2 => "(GCCGGC)",
            3 => 6,
            4 => 3,
            5 => 0,
            6 => 6,
          ],
          "PsiI" => [
            0 => "AanI,PsiI",
            1 => "TTA'TAA",
            2 => "(TTATAA)",
            3 => 6,
            4 => 3,
            5 => 0,
            6 => 6,
          ],
          "PvuII" => [
            0 => "PvuII",
            1 => "CAG'CTG",
            2 => "(CAGCTG)",
            3 => 6,
            4 => 3,
            5 => 0,
            6 => 6,
          ],
          "SmaI" => [
            0 => "SmaI",
            1 => "CCC'GGG",
            2 => "(CCCGGG)",
            3 => 6,
            4 => 3,
            5 => 0,
            6 => 6,
          ],
          "SmiI" => [
            0 => "SmiI,SwaI",
            1 => "ATTT'AAAT",
            2 => "(ATTTAAAT)",
            3 => 8,
            4 => 4,
            5 => 0,
            6 => 8,
          ],
          "SrfI" => [
            0 => "SrfI",
            1 => "GCCC'GGGC",
            2 => "(GCCCGGGC)",
            3 => 8,
            4 => 4,
            5 => 0,
            6 => 8,
          ],
          "SspI" => [
            0 => "SspI",
            1 => "AAT'ATT",
            2 => "(AATATT)",
            3 => 6,
            4 => 3,
            5 => 0,
            6 => 6,
          ],
          "ZraI" => [
            0 => "ZraI",
            1 => "GAC'GTC",
            2 => "(GACGTC)",
            3 => 6,
            4 => 3,
            5 => 0,
            6 => 6,
          ]
        ];

        $bIsOnlyDiff = true;
        $sWre = "";

        $aExpected = [
          0 => "AluI"
        ];

        $service = new RestrictionDigestManager($this->apiMock);
        $testFunction = $service->enzymesForMultiSeq($aSequence, $aDigestion, $aEnzymes, $bIsOnlyDiff, $sWre);
        $this->assertEquals($aExpected, $testFunction);
    }

    public function testEnzymesForMultiSeqWreNotNullNoDiffs()
    {
        $aSequence = [
          0 => [
            "seq" => "ACGTACGTACGTTAGCTAGCTAGCTAGC",
            "name" => ""
          ],
          1 => [
            "seq" => "GTACGTTAGCTGTACGTTAGCT",
            "name" => ""
          ]
        ];

        $aDigestion = [
            [
            "AfaI" => [
              "cuts" => [
                4 => "",
                8 => ""
              ]
            ],
            "AluI" => [
              "cuts" => [
                15 => "",
                19 => "",
                23 => ""
              ]
            ],
            "AsuNHI" => [
              "cuts" => [
                15 => "",
                19 => "",
                23 => ""
              ]
            ],
            "BfaI" => [
              "cuts" => [
                16 => "",
                20 => "",
                24 => ""
              ]
            ],
            "BmtI" => [
              "cuts" => [
                19 => "",
                23 => "",
                27 => ""
              ]
            ],
            "BsiWI" => [
              "cuts" => [
                2 => "",
                6 => ""
              ]
            ],
            "BstSNI" => [
              "cuts" => [
                6 => ""
              ]
            ],
            "Csp6I" => [
              "cuts" => [
                3 => "",
                7 => ""
              ]
            ],
            "HpyCH4IV" => [
              "cuts" => [
                1 => "",
                5 => "",
                9 => ""
              ]
            ],
            "TaiI" => [
              "cuts" => [
                4 => "",
                8 => "",
                12 => ""
              ]
            ]
          ],
          1 => [
            "AfaI" => [
              "cuts" => [
                2 => "",
                13 => ""
              ]
            ],
            "AluI" => [
              "cuts" => [
                9 => "",
                20 => ""
              ]
            ],
            "Csp6I" => [
              "cuts" => [
                1 => "",
                12 => ""
              ]
            ],
            "HpyCH4IV" => [
              "cuts" => [
                3 => "",
                14 => ""
              ]
            ],
            "TaiI" => [
              "cuts" => [
                6 => "",
                17 => ""
              ]
            ]
          ]
        ];

        $aEnzymes = [
          "AatI" => [
            0 => "AatI,Eco147I,PceI,SseBI,StuI",
            1 => "AGG'CCT",
            2 => "(AGGCCT)",
            3 => 6,
            4 => 3,
            5 => 0,
            6 => 6,
          ],
          "AatII" => [
            0 => "AatII",
            1 => "G_ACGT'C",
            2 => "(GACGTC)",
            3 => 6,
            4 => 5,
            5 => -4,
            6 => 6,
          ],
          "AbsI" => [
            0 => "AbsI",
            1 => "CC'TCGA_GG",
            2 => "(CCTCGAGG)",
            3 => 8,
            4 => 6,
            5 => -4,
            6 => 8,
          ],
          "Acc16I" => [
            0 => "Acc16I,AviII,FspI,NsbI",
            1 => "TGC'GCA",
            2 => "(TGCGCA)",
            3 => 6,
            4 => 3,
            5 => 0,
            6 => 6,
          ],
          "Acc65I" => [
            0 => "Acc65I,Asp718I",
            1 => "G'GTAC_C",
            2 => "(GGTACC)",
            3 => 6,
            4 => 1,
            5 => 4,
            6 => 6,
          ],
          "AccII" => [
            0 => "AccII,Bsh1236I,BspFNI,BstFNI,BstUI,MvnI",
            1 => "CG'CG",
            2 => "(CGCG)",
            3 => 4,
            4 => 2,
            5 => 0,
            6 => 4,
          ],
          "AccIII" => [
            0 => "AccIII,Aor13HI,BlfI,BseAI,Bsp13I,BspEI,Kpn2I,MroI",
            1 => "T'CCGG_A",
            2 => "(TCCGGA)",
            3 => 6,
            4 => 1,
            5 => 4,
            6 => 6,
          ],
          "AclI" => [
            0 => "AclI,Psp1406I",
            1 => "AA'CG_TT",
            2 => "(AACGTT)",
            3 => 6,
            4 => 2,
            5 => 2,
            6 => 6,
          ],
          "AcvI" => [
            0 => "AcvI,BbrPI,Eco72I,PmaCI,PmlI,PspCI",
            1 => "CAC'GTG",
            2 => "(CACGTG)",
            3 => 6,
            4 => 3,
            5 => 0,
            6 => 6,
          ],
          "AfaI" => [
            0 => "AfaI,RsaI",
            1 => "GT'AC",
            2 => "(GTAC)",
            3 => 4,
            4 => 2,
            5 => 0,
            6 => 4,
          ],
          "AfeI" => [
            0 => "AfeI,Aor51HI,Eco47III",
            1 => "AGC'GCT",
            2 => "(AGCGCT)",
            3 => 6,
            4 => 3,
            5 => 0,
            6 => 6,
          ],
          "AflII" => [
            0 => "AflII,BfrI,BspTI,Bst98I,BstAFI,MspCI,Vha464I",
            1 => "C'TTAA_G",
            2 => "(CTTAAG)",
            3 => 6,
            4 => 1,
            5 => 4,
            6 => 6,
          ],
          "AgeI" => [
            0 => "AgeI,AsiGI,BshTI,CspAI,PinAI",
            1 => "A'CCGG_T",
            2 => "(ACCGGT)",
            3 => 6,
            4 => 1,
            5 => 4,
            6 => 6,
          ],
          "AhlI" => [
            0 => "AhlI,BcuI,SpeI",
            1 => "A'CTAG_T",
            2 => "(ACTAGT)",
            3 => 6,
            4 => 1,
            5 => 4,
            6 => 6,
          ],
          "AluI" => [
            0 => "AluI,AluBI",
            1 => "AG'CT",
            2 => "(AGCT)",
            3 => 4,
            4 => 2,
            5 => 0,
            6 => 4,
          ],
          "Alw44I" => [
            0 => "Alw44I,ApaLI,VneI",
            1 => "G'TGCA_C",
            2 => "(GTGCAC)",
            3 => 6,
            4 => 1,
            5 => 4,
            6 => 6,
          ],
          "ApaI" => [
            0 => "ApaI",
            1 => "G_GGCC'C",
            2 => "(GGGCCC)",
            3 => 6,
            4 => 5,
            5 => -4,
            6 => 6,
          ],
          "AscI" => [
            0 => "AscI,PalAI,SgsI",
            1 => "GG'CGCG_CC",
            2 => "(GGCGCGCC)",
            3 => 8,
            4 => 2,
            5 => 4,
            6 => 8,
          ],
          "AseI" => [
            0 => "AseI,PshBI,VspI",
            1 => "AT'TA_AT",
            2 => "(ATTAAT)",
            3 => 6,
            4 => 2,
            5 => 2,
            6 => 6,
          ],
          "AsiSI" => [
            0 => "AsiSI,RgaI,SfaAI,SgfI",
            1 => "GCG_AT'CGC",
            2 => "(GCGATCGC)",
            3 => 8,
            4 => 5,
            5 => -2,
            6 => 8,
          ],
          "AspA2I" => [
            0 => "AspA2I,AvrII,BlnI,XmaJI",
            1 => "C'CTAG_G",
            2 => "(CCTAGG)",
            3 => 6,
            4 => 1,
            5 => 4,
            6 => 6,
          ],
          "AspLEI" => [
            0 => "AspLEI,BstHHI,CfoI,HhaI",
            1 => "G_CG'C",
            2 => "(GCGC)",
            3 => 4,
            4 => 3,
            5 => -2,
            6 => 4,
          ],
          "AssI" => [
            0 => "AssI,BmcAI,ScaI,ZrmI",
            1 => "AGT'ACT",
            2 => "(AGTACT)",
            3 => 6,
            4 => 3,
            5 => 0,
            6 => 6,
          ],
          "AsuII" => [
            0 => "AsuII,Bpu14I,Bsp119I,BspT104I,BstBI,Csp45I,NspV,SfuI",
            1 => "TT'CG_AA",
            2 => "(TTCGAA)",
            3 => 6,
            4 => 2,
            5 => 2,
            6 => 6,
          ],
          "AsuNHI" => [
            0 => "AsuNHI,BspOI,NheI",
            1 => "G'CTAG_C",
            2 => "(GCTAGC)",
            3 => 6,
            4 => 1,
            5 => 4,
            6 => 6,
          ],
          "BalI" => [
            0 => "BalI,MlsI,MluNI,MscI,Msp20I",
            1 => "TGG'CCA",
            2 => "(TGGCCA)",
            3 => 6,
            4 => 3,
            5 => 0,
            6 => 6,
          ],
          "BamHI" => [
            0 => "BamHI",
            1 => "G'GATC_C",
            2 => "(GGATCC)",
            3 => 6,
            4 => 1,
            5 => 4,
            6 => 6,
          ],
          "BanIII" => [
            0 => "BanIII,Bsa29I,BseCI,BshVI,BspDI,BspXI,Bsu15I,BsuTUI,ClaI",
            1 => "AT'CG_AT",
            2 => "(ATCGAT)",
            3 => 6,
            4 => 2,
            5 => 2,
            6 => 6,
          ],
          "BauI" => [
            0 => "BauI",
            1 => "C'ACGA_G",
            2 => "(CACGAG)",
            3 => 6,
            4 => 1,
            5 => 4,
            6 => 6,
          ],
          "BbeI" => [
            0 => "BbeI,PluTI",
            1 => "G_GCGC'C",
            2 => "(GGCGCC)",
            3 => 6,
            4 => 5,
            5 => -4,
            6 => 6,
          ],
          "BbuI" => [
            0 => "BbuI,PaeI,SphI",
            1 => "G_CATG'C",
            2 => "(GCATGC)",
            3 => 6,
            4 => 5,
            5 => -4,
            6 => 6,
          ],
          "BclI" => [
            0 => "BclI,FbaI,Ksp22I",
            1 => "T'GATC_A",
            2 => "(TGATCA)",
            3 => 6,
            4 => 1,
            5 => 4,
            6 => 6,
          ],
          "BfaI" => [
            0 => "BfaI,FspBI,MaeI,XspI",
            1 => "C'TA_G",
            2 => "(CTAG)",
            3 => 4,
            4 => 1,
            5 => 2,
            6 => 4,
          ],
          "BfuCI" => [
            0 => "BfuCI,Bsp143I,BssMI,BstMBI,DpnII,Kzo9I,MboI,NdeII,Sau3AI",
            1 => "'GATC_",
            2 => "(GATC)",
            3 => 4,
            4 => 0,
            5 => 4,
            6 => 4,
          ],
          "BglII" => [
            0 => "BglII",
            1 => "A'GATC_T",
            2 => "(AGATCT)",
            3 => 6,
            4 => 1,
            5 => 4,
            6 => 6,
          ],
          "BmtI" => [
            0 => "BmtI",
            1 => "G_CTAG'C",
            2 => "(GCTAGC)",
            3 => 6,
            4 => 5,
            5 => -4,
            6 => 6,
          ],
          "BpvUI" => [
            0 => "BpvUI,MvrI,PvuI,Ple19I",
            1 => "CG_AT'CG",
            2 => "(CGATCG)",
            3 => 6,
            4 => 4,
            5 => -2,
            6 => 6,
          ],
          "BsePI" => [
            0 => "BsePI,BssHII,PauI,PteI",
            1 => "G'CGCG_C",
            2 => "(GCGCGC)",
            3 => 6,
            4 => 1,
            5 => 4,
            6 => 6,
          ],
          "BseX3I" => [
            0 => "BseX3I,BstZI,EagI,EclXI,Eco52I",
            1 => "C'GGCC_G",
            2 => "(CGGCCG)",
            3 => 6,
            4 => 1,
            5 => 4,
            6 => 6,
          ],
          "BshFI" => [
            0 => "BshFI,BsnI,BspANI,BsuRI,HaeIII,PhoI",
            1 => "GG'CC",
            2 => "(GGCC)",
            3 => 4,
            4 => 2,
            5 => 0,
            6 => 4,
          ],
          "BsiSI" => [
            0 => "BsiSI,HapII,HpaII,MspI",
            1 => "C'CG_G",
            2 => "(CCGG)",
            3 => 4,
            4 => 1,
            5 => 2,
            6 => 4,
          ],
          "BsiWI" => [
            0 => "BsiWI,Pfl23II,PspLI",
            1 => "C'GTAC_G",
            2 => "(CGTACG)",
            3 => 6,
            4 => 1,
            5 => 4,
            6 => 6,
          ],
          "Bsp120I" => [
            0 => "Bsp120I,PspOMI",
            1 => "G'GGCC_C",
            2 => "(GGGCCC)",
            3 => 6,
            4 => 1,
            5 => 4,
            6 => 6,
          ],
          "Bsp1407I" => [
            0 => "Bsp1407I,BsrGI,BstAUI,SspBI",
            1 => "T'GTAC_A",
            2 => "(TGTACA)",
            3 => 6,
            4 => 1,
            5 => 4,
            6 => 6,
          ],
          "Bsp19I" => [
            0 => "Bsp19I,NcoI",
            1 => "C'CATG_G",
            2 => "(CCATGG)",
            3 => 6,
            4 => 1,
            5 => 4,
            6 => 6,
          ],
          "Bsp68I" => [
            0 => "Bsp68I,BtuMI,NruI,RruI",
            1 => "TCG'CGA",
            2 => "(TCGCGA)",
            3 => 6,
            4 => 3,
            5 => 0,
            6 => 6,
          ],
          "BspHI" => [
            0 => "BspHI,CciI,PagI,RcaI",
            1 => "T'CATG_A",
            2 => "(TCATGA)",
            3 => 6,
            4 => 1,
            5 => 4,
            6 => 6,
          ],
          "BspLU11I" => [
            0 => "BspLU11I,PciI,PscI",
            1 => "A'CATG_T",
            2 => "(ACATGT)",
            3 => 6,
            4 => 1,
            5 => 4,
            6 => 6,
          ],
          "BspMAI" => [
            0 => "BspMAI,PstI",
            1 => "C_TGCA'G",
            2 => "(CTGCAG)",
            3 => 6,
            4 => 5,
            5 => -4,
            6 => 6,
          ],
          "BssNAI" => [
            0 => "BssNAI,Bst1107I,BstZ17I",
            1 => "GTA'TAC",
            2 => "(GTATAC)",
            3 => 6,
            4 => 3,
            5 => 0,
            6 => 6,
          ],
          "BstKTI" => [
            0 => "BstKTI",
            1 => "G_AT'C",
            2 => "(GATC)",
            3 => 4,
            4 => 3,
            5 => 2,
            6 => 4,
          ],
          "BstSNI" => [
            0 => "BstSNI,Eco105I,SnaBI",
            1 => "TAC'GTA",
            2 => "(TACGTA)",
            3 => 6,
            4 => 3,
            5 => 0,
            6 => 6,
          ],
          "CciNI" => [
            0 => "CciNI,NotI",
            1 => "GC'GGCC_GC",
            2 => "(GCGGCCGC)",
            3 => 8,
            4 => 2,
            5 => 4,
            6 => 8,
          ],
          "Cfr42I" => [
            0 => "Cfr42I,KspI,SacII,Sfr303I,SgrBI,SstII",
            1 => "CC_GC'GG",
            2 => "(CCGCGG)",
            3 => 6,
            4 => 4,
            5 => -2,
            6 => 6,
          ],
          "Cfr9I" => [
            0 => "Cfr9I,TspMI,XmaI,XmaCI",
            1 => "C'CCGG_G",
            2 => "(CCCGGG)",
            3 => 6,
            4 => 1,
            5 => 4,
            6 => 6,
          ],
          "Csp6I" => [
            0 => "Csp6I,CviQI,RsaNI",
            1 => "G'TA_C",
            2 => "(GTAC)",
            3 => 4,
            4 => 1,
            5 => 2,
            6 => 4,
          ],
          "CviAII" => [
            0 => "CviAII,FaeI,Hin1II,Hsp92II,NlaIII",
            1 => "_CATG'",
            2 => "(CATG)",
            3 => 4,
            4 => 4,
            5 => -4,
            6 => 4,
          ],
          "DinI" => [
            0 => "DinI,Mly113I,NarI,SspDI",
            1 => "GG'CG_CC",
            2 => "(GGCGCC)",
            3 => 6,
            4 => 2,
            5 => 2,
            6 => 6,
          ],
          "DpnI" => [
            0 => "DpnI,MalI",
            1 => "GA'TC",
            2 => "(GATC)",
            3 => 4,
            4 => 2,
            5 => 0,
            6 => 4,
          ],
          "DraI" => [
            0 => "DraI",
            1 => "TTT'AAA",
            2 => "(TTTAAA)",
            3 => 6,
            4 => 3,
            5 => 0,
            6 => 6,
          ],
          "Ecl136II" => [
            0 => "Ecl136II,Eco53kI,EcoICRI",
            1 => "GAG'CTC",
            2 => "(GAGCTC)",
            3 => 6,
            4 => 3,
            5 => 0,
            6 => 6,
          ],
          "Eco32I" => [
            0 => "Eco32I,EcoRV",
            1 => "GAT'ATC",
            2 => "(GATATC)",
            3 => 6,
            4 => 3,
            5 => 0,
            6 => 6,
          ],
          "EcoRI" => [
            0 => "EcoRI",
            1 => "G'AATT_C",
            2 => "(GAATTC)",
            3 => 6,
            4 => 1,
            5 => 4,
            6 => 6,
          ],
          "EcoT22I" => [
            0 => "EcoT22I,Mph1103I,NsiI,Zsp2I",
            1 => "A_TGCA'T",
            2 => "(ATGCAT)",
            3 => 6,
            4 => 5,
            5 => -4,
            6 => 6,
          ],
          "EgeI" => [
            0 => "EgeI,EheI,SfoI",
            1 => "GGC'GCC",
            2 => "(GGCGCC)",
            3 => 6,
            4 => 3,
            5 => 0,
            6 => 6,
          ],
          "FatI" => [
            0 => "FatI",
            1 => "'CATG_",
            2 => "(CATG)",
            3 => 4,
            4 => 0,
            5 => 4,
            6 => 4,
          ],
          "FauNDI" => [
            0 => "FauNDI,NdeI",
            1 => "CA'TA_TG",
            2 => "(CATATG)",
            3 => 6,
            4 => 2,
            5 => 2,
            6 => 6,
          ],
          "FseI" => [
            0 => "FseI,RigI",
            1 => "GG_CCGG'CC",
            2 => "(GGCCGGCC)",
            3 => 8,
            4 => 6,
            5 => -4,
            6 => 8,
          ],
          "GlaI" => [
            0 => "GlaI",
            1 => "GC'GC",
            2 => "(GCGC)",
            3 => 4,
            4 => 2,
            5 => 0,
            6 => 4,
          ],
          "Hin6I" => [
            0 => "Hin6I,HinP1I,HspAI",
            1 => "G'CG_C",
            2 => "(GCGC)",
            3 => 4,
            4 => 1,
            5 => 2,
            6 => 4,
          ],
          "HindIII" => [
            0 => "HindIII",
            1 => "A'AGCT_T",
            2 => "(AAGCTT)",
            3 => 6,
            4 => 1,
            5 => 4,
            6 => 6,
          ],
          "HpaI" => [
            0 => "HpaI,KspAI",
            1 => "GTT'AAC",
            2 => "(GTTAAC)",
            3 => 6,
            4 => 3,
            5 => 0,
            6 => 6,
          ],
          "HpyCH4IV" => [
            0 => "HpyCH4IV,HpySE526I,MaeII",
            1 => "A'CG_T",
            2 => "(ACGT)",
            3 => 4,
            4 => 1,
            5 => 2,
            6 => 4,
          ],
          "HpyCH4V" => [
            0 => "HpyCH4V",
            1 => "TG'CA",
            2 => "(TGCA)",
            3 => 4,
            4 => 2,
            5 => 0,
            6 => 4,
          ],
          "KasI" => [
            0 => "KasI",
            1 => "G'GCGC_C",
            2 => "(GGCGCC)",
            3 => 6,
            4 => 1,
            5 => 4,
            6 => 6,
          ],
          "KpnI" => [
            0 => "KpnI",
            1 => "G_GTAC'C",
            2 => "(GGTACC)",
            3 => 6,
            4 => 5,
            5 => -4,
            6 => 6,
          ],
          "KroI" => [
            0 => "KroI,MroNI,NgoMIV",
            1 => "G'CCGG_C",
            2 => "(GCCGGC)",
            3 => 6,
            4 => 1,
            5 => 4,
            6 => 6,
          ],
          "MauBI" => [
            0 => "MauBI",
            1 => "CG'CGCG_CG",
            2 => "(CGCGCGCG)",
            3 => 8,
            4 => 2,
            5 => 4,
            6 => 8,
          ],
          "MfeI" => [
            0 => "MfeI,MunI",
            1 => "C'AATT_G",
            2 => "(CAATTG)",
            3 => 6,
            4 => 1,
            5 => 4,
            6 => 6,
          ],
          "MluCI" => [
            0 => "MluCI,Sse9I,TasI,Tsp509I,TspEI",
            1 => "'AATT_",
            2 => "(AATT)",
            3 => 4,
            4 => 0,
            5 => 4,
            6 => 4,
          ],
          "MluI" => [
            0 => "MluI",
            1 => "A'CGCG_T",
            2 => "(ACGCGT)",
            3 => 6,
            4 => 1,
            5 => 4,
            6 => 6,
          ],
          "MreI" => [
            0 => "MreI",
            1 => "CG'CCGG_CG",
            2 => "(CGCCGGCG)",
            3 => 8,
            4 => 2,
            5 => 4,
            6 => 8,
          ],
          "MseI" => [
            0 => "MseI,SaqAI,Tru1I,Tru9I",
            1 => "T'TA_A",
            2 => "(TTAA)",
            3 => 4,
            4 => 1,
            5 => 2,
            6 => 4,
          ],
          "MssI" => [
            0 => "MssI,PmeI",
            1 => "GTTT'AAAC",
            2 => "(GTTTAAAC)",
            3 => 8,
            4 => 4,
            5 => 0,
            6 => 8,
          ],
          "NaeI" => [
            0 => "NaeI,PdiI",
            1 => "GCC'GGC",
            2 => "(GCCGGC)",
            3 => 6,
            4 => 3,
            5 => 0,
            6 => 6,
          ],
          "PacI" => [
            0 => "PacI",
            1 => "TTA_AT'TAA",
            2 => "(TTAATTAA)",
            3 => 8,
            4 => 5,
            5 => -2,
            6 => 8,
          ],
          "PaeR7I" => [
            0 => "PaeR7I,Sfr274I,SlaI,StrI,TliI,XhoI",
            1 => "C'TCGA_G",
            2 => "(CTCGAG)",
            3 => 6,
            4 => 1,
            5 => 4,
            6 => 6,
          ],
          "PsiI" => [
            0 => "AanI,PsiI",
            1 => "TTA'TAA",
            2 => "(TTATAA)",
            3 => 6,
            4 => 3,
            5 => 0,
            6 => 6,
          ],
          "Psp124BI" => [
            0 => "Psp124BI,SacI,SstI",
            1 => "G_AGCT'C",
            2 => "(GAGCTC)",
            3 => 6,
            4 => 5,
            5 => -44,
            6 => 6,
          ],
          "PvuII" => [
            0 => "PvuII",
            1 => "CAG'CTG",
            2 => "(CAGCTG)",
            3 => 6,
            4 => 3,
            5 => 0,
            6 => 6,
          ],
          "SalI" => [
            0 => "SalI",
            1 => "G'TCGA_C",
            2 => "(GTCGAC)",
            3 => 6,
            4 => 1,
            5 => 4,
            6 => 6,
          ],
          "SbfI" => [
            0 => "SbfI,SdaI,Sse8387I",
            1 => "CC_TGCA'GG",
            2 => "(CCTGCAGG)",
            3 => 8,
            4 => 6,
            5 => -4,
            6 => 8,
          ],
          "SgrDI" => [
            0 => "SgrDI",
            1 => "CG'TCGA_CG",
            2 => "(CGTCGACG)",
            3 => 8,
            4 => 2,
            5 => 4,
            6 => 8,
          ],
          "SmaI" => [
            0 => "SmaI",
            1 => "CCC'GGG",
            2 => "(CCCGGG)",
            3 => 6,
            4 => 3,
            5 => 0,
            6 => 6,
          ],
          "SmiI" => [
            0 => "SmiI,SwaI",
            1 => "ATTT'AAAT",
            2 => "(ATTTAAAT)",
            3 => 8,
            4 => 4,
            5 => 0,
            6 => 8,
          ],
          "SrfI" => [
            0 => "SrfI",
            1 => "GCCC'GGGC",
            2 => "(GCCCGGGC)",
            3 => 8,
            4 => 4,
            5 => 0,
            6 => 8,
          ],
          "SspI" => [
            0 => "SspI",
            1 => "AAT'ATT",
            2 => "(AATATT)",
            3 => 6,
            4 => 3,
            5 => 0,
            6 => 6,
          ],
          "TaiI" => [
            0 => "TaiI",
            1 => "_ACGT'",
            2 => "(ACGT)",
            3 => 4,
            4 => 4,
            5 => -4,
            6 => 4,
          ],
          "TaqI" => [
            0 => "TaqI",
            1 => "T'CG_A",
            2 => "(TCGA)",
            3 => 4,
            4 => 1,
            5 => 2,
            6 => 4,
          ],
          "XbaI" => [
            0 => "XbaI",
            1 => "T'CTAG_A",
            2 => "(TCTAGA)",
            3 => 6,
            4 => 1,
            5 => 4,
            6 => 6,
          ],
          "ZraI" => [
            0 => "ZraI",
            1 => "GAC'GTC",
            2 => "(GACGTC)",
            3 => 6,
            4 => 3,
            5 => 0,
            6 => 6,
          ]
        ];
        $bIsOnlyDiff = false;
        $sWre = "AarI";

        $aExpected = [
          0 => "AfaI",
          1 => "AfaI",
          2 => "AluI",
          3 => "AluI",
          4 => "AsuNHI",
          5 => "BfaI",
          6 => "BmtI",
          7 => "BsiWI",
          8 => "BstSNI",
          9 => "Csp6I",
          10 => "Csp6I",
          11 => "HpyCH4IV",
          12 => "HpyCH4IV",
          13 => "TaiI",
          14 => "TaiI",
        ];

        $service = new RestrictionDigestManager($this->apiMock);
        $testFunction = $service->enzymesForMultiSeq($aSequence, $aDigestion, $aEnzymes, $bIsOnlyDiff, $sWre);
        $this->assertEquals($aExpected, $testFunction);
    }

    public function testShowVendors()
    {
        $sCompany = "CFIJMNQRSVXY";
        $sEnzyme = "RsaI";

        $aExpected = [
          "company" => [
            "name" => "CFIJMNQRSVXY",
            "url" => "http://rebase.neb.com/rebase/enz/RsaI.html"
          ],
          "links" =>  [
            0 => [
              "name" => "Minotech Biotechnology",
              "url" => "http://www.minotech.gr"
            ],
            1 => [
              "name" => "Fermentas AB",
              "url" => "http://www.fermentas.com"
            ],
            2 => [
              "name" => "SibEnzyme Ltd.",
              "url" => "http://www.sibenzyme.com"
            ],
            3 => [
              "name" => "Nippon Gene Co., Ltd.",
              "url" => "http://www.nippongene.jp"
            ],
            4 => [
              "name" => "Roche Applied Science",
              "url" => "http://www.roche.com"
            ],
            5 => [
              "name" => "New England Biolabs",
              "url" => "http://www.neb.com"
            ],
            6 => [
              "name" => "CHIMERx",
              "url" => "http://www.CHIMERx.com"
            ],
            7 => [
              "name" => "Promega Corporation",
              "url" => "http://www.promega.com"
            ],
            8 => [
              "name" => "Sigma Chemical Corporation",
              "url" => "http://www.sigmaaldrich.com"
            ],
            9 => [
              "name" => "MRC-Holland",
              "url" => "http://www.mrc-holland.com"
            ],
            10 => [
              "name" => "EURx Ltd.",
              "url" => "http://www.eurx.com.pl/index.php?op=catalog&cat=8"
            ]
          ]
        ];

        $service = new RestrictionDigestManager($this->apiMock);
        $testFunction = $service->showVendors($sCompany, $sEnzyme);
        $this->assertEquals($aExpected, $testFunction);
    }
}