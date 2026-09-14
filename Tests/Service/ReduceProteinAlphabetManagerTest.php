<?php


namespace Tests\MinitoolsBundle\Service;

use Amelaye\BioPHP\Api\ProteinReductionApi;
use Amelaye\BioTools\Service\ReduceProteinAlphabetManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ReduceProteinAlphabetManagerTest extends TestCase
{

    /**
     * @var mixed
     */
    protected $tripletSpeciesMock;
    protected $proteinColors;

    public function setUp(): void
    {
        $this->proteinColors = [
          20 => [
            "A" => "CCFFFF",
            "R" => "E60606",
            "N" => "FF9900",
            "D" => "FFCC99",
            "C" => "00FFFF",
            "E" => "FFCC00",
            "Q" => "FF6600",
            "G" => "00FF00",
            "H" => "FFFF99",
            "I" => "000088",
            "L" => "3366FF",
            "K" => "C64200",
            "M" => "99CCFF",
            "F" => "00CCFF",
            "P" => "FFFF00",
            "S" => "CCFF99",
            "T" => "00FF99",
            "W" => "CC99FF",
            "Y" => "CCFFCC",
            "V" => "0000FF",
          ],
          2 => [
            "P" => "0000FF",
            "H" => "FF0000",
          ],
          5 => [
            "A" => "FF0000",
            "R" => "00FF00",
            "C" => "0000FF",
            "T" => "FFFF00",
            "D" => "00FFFF",
          ],
          6 => [
            "A" => "FF0000",
            "R" => "00FF00",
            "P" => "0000FF",
            "N" => "8888FF",
            "T" => "FFFF00",
            "D" => "00FFFF",
          ],
          "3IMG" => [
            "P" => "E60606",
            "N" => "FFFF00",
            "H" => "3366FF",
          ],
          "5IMG" => [
            "G" => "E60606",
            "C" => "E60606",
            "E" => "E60606",
            "M" => "E60606",
            "F" => "E60606",
          ],
          "11IMG" => [
            "A" => "1B04AE",
            "F" => "00CCFF",
            "C" => "CCECFF",
            "G" => "00FF00",
            "S" => "89F88B",
            "W" => "CC99FF",
            "Y" => "CCFFCC",
            "P" => "FFFF00",
            "D" => "FFCC00",
            "N" => "F4A504",
            "H" => "EC1504",
          ],
          "Murphy15" => [
            "L" => "FF0000",
            "C" => "00FF00",
            "A" => "0000FF",
            "G" => "FFFF00",
            "S" => "00FFFF",
            "T" => "FF00FF",
            "P" => "880000",
            "F" => "008800",
            "W" => "000088",
            "E" => "888800",
            "D" => "008888",
            "N" => "880088",
            "Q" => "FF8888",
            "K" => "88FF88",
            "H" => "8888FF",
          ],
          "Murphy10" => [
            "L" => "FF0000",
            "C" => "00FF00",
            "A" => "0000FF",
            "G" => "FFFF00",
            "S" => "00FFFF",
            "P" => "880000",
            "F" => "008800",
            "E" => "888800",
            "K" => "88FF88",
            "H" => "8888FF",
          ],
          "Murphy8" => [
            "L" => "FF0000",
            "A" => "0000FF",
            "S" => "00FFFF",
            "P" => "880000",
            "F" => "0000FF",
            "E" => "008800",
            "K" => "88FF88",
            "H" => "0000FF",
          ],
          "Murphy4" => [
            "L" => "00FF00",
            "A" => "00FFFF",
            "F" => "FF0000",
            "E" => "0000FF",
          ],
          "Murphy2" => [
            "P" => "FF0000",
            "E" => "0000FF",
          ],
          "Wang5" => [
            "I" => "FF0000",
            "A" => "00FF00",
            "G" => "FFFF00",
            "E" => "0000FF",
            "K" => "00FFFF",
          ],
          "Wang5v" => [
            "I" => "FF0000",
            "L" => "FFFF00",
            "A" => "00FF00",
            "E" => "0000FF",
            "K" => "00FFFF",
          ],
          "Wang3" => [
            "I" => "FF0000",
            "A" => "00FF00",
            "E" => "0000FF",
          ],
          "Wang2" => [
            "I" => "FF0000",
            "A" => "0000FF",
          ],
          "Li10" => [
            "C" => "FF0000",
            "Y" => "FFFF00",
            "L" => "FF00FF",
            "V" => "FF8888",
            "G" => "00FFFF",
            "P" => "88FF88",
            "S" => "00FF00",
            "N" => "8888FF",
            "E" => "0000FF",
            "K" => "88FFFF",
          ],
          "Li5" => [
            "Y" => "FFFF00",
            "I" => "FF0000",
            "G" => "00FFFF",
            "S" => "00FF00",
            "E" => "0000FF",
          ],
          "Li4" => [
            "Y" => "FFFF00",
            "I" => "FF0000",
            "S" => "00FF00",
            "E" => "0000FF",
          ],
          "Li3" => [
            "I" => "FF0000",
            "S" => "00FF00",
            "E" => "0000FF",
          ]
        ];

        $a11Imgt = [
            "Description" => "11 IMGT amino acid chemical characteristics alphabet",
            "Elements" => [
                "AVIL" => "A: Aliphatic",
                "F" => "F: Phenylalanine",
                "CM" => "G: Sulfur",
                "G" => "G: Glycine",
                "ST" => "S: Hydroxyl",
                "W" => "W: Tryptophan",
                "Y" => "Y: Tyrosine",
                "P" => "P: Proline",
                "DE" => "A: Acidic",
                "NQ" => "N: Amide",
                "HKR" => "H: Basic",
            ]
        ];

        require 'samples/ProteinReductions.php';

        /**
         * Mock API
         */
        $this->tripletSpeciesMock = $this->getMockBuilder(ProteinReductionApi::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getReductions'])
            ->getMock();
        $this->tripletSpeciesMock->method("getReductions")->willReturn($aReductions);
    }

    public function testReduceAlphabet()
    {
        $sSequence = "ARNDCEQGHILKMFPSTWYVX*";
        $sType = "11IMG";
        $sExpected = "AHNDCDNGHAAHCFPSSWYAX*";
        $service = new ReduceProteinAlphabetManager($this->proteinColors, $this->tripletSpeciesMock);
        $testFunction = $service->reduceAlphabet($sSequence, $sType);

        $this->assertEquals($sExpected, $testFunction);
    }

    public function testReduceAlphabetCustom()
    {
        $sSequence = "ARNDCEQGHILKMFPSTWYVX*";
        $sCustomAlphabet = "TCDCTCDTRAACDRDTDRRA";
        $sExpected = "TCDCTCDTRAACDRDTDRRAX*";

        $service = new ReduceProteinAlphabetManager($this->proteinColors, $this->tripletSpeciesMock);
        $testFunction = $service->reduceAlphabetCustom($sSequence, $sCustomAlphabet);

        $this->assertEquals($sExpected, $testFunction);
    }

    /**
     * Every reduction the minitool offers, applied to the complete amino acid alphabet
     * plus the unknown X and the stop codon
     */
    #[DataProvider('providerAlphabets')]
    public function testReduceAlphabetOfEveryPredefinedAlphabet($sType, $sExpected)
    {
        $service = new ReduceProteinAlphabetManager($this->proteinColors, $this->tripletSpeciesMock);

        $this->assertEquals($sExpected, $service->reduceAlphabet("ARNDCEQGHILKMFPSTWYVX*", $sType));
    }

    public static function providerAlphabets()
    {
        return [
            "Murphy 2"  => ["Murphy2",  "PEEEPEEPEPPEPPPPPPPPX*"],
            "Murphy 4"  => ["Murphy4",  "AEEELEEAELLELFAAAFFLX*"],
            "Murphy 10" => ["Murphy10", "AKEECEEGHLLKLFPSSFFLX*"],
            "Murphy 15" => ["Murphy15", "AKNDCEQGHLLKLFPSTWFLX*"],
            "Wang 2"    => ["Wang2",    "AAAAIAAAAIIAIIAAAIIIX*"],
            "Wang 3"    => ["Wang3",    "AAEEIEEAAIIEIIAEAIIIX*"],
            "Wang 5"    => ["Wang5",    "AKKEIEKGAIIKIIGKAIIIX*"],
            "Wang 5v"   => ["Wang5v",   "AKEEIEEAKILKIIKAALLLX*"],
            "Li 3"      => ["Li3",      "SEEEIEESEIIEIISSSIIIX*"],
            "Li 4"      => ["Li4",      "SEEEYEESEIIEIYSSSYYIX*"],
            "Li 5"      => ["Li5",      "SEEEYEEGEIIEIYSSSYYIX*"],
            "Li 10"     => ["Li10",     "SKNECEEGNVLKLYPSSYYVX*"],
            "IMGT 3"    => ["3IMG",     "HPPPHPPNHHHPNHNNNHNHX*"],
            "IMGT 5"    => ["5IMG",     "GMCCCEEGEMMMMFCGCFFEX*"],
            "IMGT 11"   => ["11IMG",    "AHNDCDNGHAAHCFPSSWYAX*"],
        ];
    }

    /**
     * The unknown amino acid and the stop codon belong to no group and are left as they are
     */
    public function testReduceAlphabetLeavesTheUnknownAndTheStopUntouched()
    {
        $service = new ReduceProteinAlphabetManager($this->proteinColors, $this->tripletSpeciesMock);

        $this->assertEquals("X*X*", $service->reduceAlphabet("X*X*", "Murphy2"));
    }

    public function testReduceAlphabetOfAnEmptySequence()
    {
        $service = new ReduceProteinAlphabetManager($this->proteinColors, $this->tripletSpeciesMock);

        $this->assertEquals("", $service->reduceAlphabet("", "Murphy2"));
    }

    /**
     * A two letters alphabet leaves only two different symbols in the reduced sequence,
     * one per group, and does not change its length
     */
    public function testReduceAlphabetKeepsOnlyTheSymbolsOfTheAlphabet()
    {
        $service = new ReduceProteinAlphabetManager($this->proteinColors, $this->tripletSpeciesMock);

        $sReduced = $service->reduceAlphabet("ARNDCEQGHILKMFPSTWYV", "Murphy2");

        $aSymbols = array_unique(str_split($sReduced));
        sort($aSymbols);

        $this->assertEquals(["E", "P"], $aSymbols);
        $this->assertEquals(20, strlen($sReduced));
    }

    /**
     * The custom alphabet gives one letter per amino acid, in the ARNDCEQGHILKMFPSTWYV
     * order the minitool shows above the input field
     */
    public function testReduceAlphabetCustomMapsTheAminoAcidsInOrder()
    {
        $service = new ReduceProteinAlphabetManager($this->proteinColors, $this->tripletSpeciesMock);

        // every amino acid onto the same letter
        $this->assertEquals(
            "AAAAAAAAAAAAAAAAAAAAX*",
            $service->reduceAlphabetCustom("ARNDCEQGHILKMFPSTWYVX*", str_repeat("A", 20))
        );
    }

    /**
     * The custom alphabet is lower cased before being applied, so a letter that has just
     * been substituted is not substituted again by a later amino acid: mapping A onto R
     * and R onto A swaps them instead of collapsing both onto one letter
     */
    public function testReduceAlphabetCustomDoesNotCascade()
    {
        $service = new ReduceProteinAlphabetManager($this->proteinColors, $this->tripletSpeciesMock);

        $sCustom = "RA" . "NDCEQGHILKMFPSTWYV"; // A -> R, R -> A, the rest unchanged

        $this->assertEquals(
            "RANDCEQGHILKMFPSTWYV",
            $service->reduceAlphabetCustom("ARNDCEQGHILKMFPSTWYV", $sCustom)
        );
    }

    /**
     * A custom alphabet shorter than twenty letters only reduces the amino acids it
     * reaches, the following ones are kept as they are
     */
    public function testReduceAlphabetCustomShorterThanTheAlphabet()
    {
        $service = new ReduceProteinAlphabetManager($this->proteinColors, $this->tripletSpeciesMock);

        // only A, R and N are mapped, onto X, Y and Z
        $this->assertEquals(
            "XYZDCEQGHILKMFPSTWYV",
            $service->reduceAlphabetCustom("ARNDCEQGHILKMFPSTWYV", "XYZ")
        );
    }

    public function testReduceAlphabetCustomOfAnEmptySequence()
    {
        $service = new ReduceProteinAlphabetManager($this->proteinColors, $this->tripletSpeciesMock);

        $this->assertEquals("", $service->reduceAlphabetCustom("", str_repeat("A", 20)));
    }

    /**
     * The description and the groups of a reduction, as the minitool prints them under
     * the coloured sequence
     */
    public function testCreateReduceCode()
    {
        $aExpected = [
            "Description" => "Murphy et al, 2000; 2 letters alphabet",
            "Elements" => [
                "LVIMCAGSTPFYW" => "P: Hydrophobic",
                "EDNQKRH"       => "E: Hydrophilic",
            ],
        ];

        $service = new ReduceProteinAlphabetManager($this->proteinColors, $this->tripletSpeciesMock);

        $this->assertEquals($aExpected, $service->createReduceCode("Murphy2"));
    }

    public function testCreateReduceCodeOfTheElevenLettersImgtAlphabet()
    {
        $aExpected = [
            "Description" => "11 IMGT amino acid chemical characteristics alphabet",
            "Elements" => [
                "AVIL" => "A: Aliphatic",
                "F"    => "F: Phenylalanine",
                "CM"   => "G: Sulfur",
                "G"    => "G: Glycine",
                "ST"   => "S: Hydroxyl",
                "W"    => "W: Tryptophan",
                "Y"    => "Y: Tyrosine",
                "P"    => "P: Proline",
                "DE"   => "A: Acidic",
                "NQ"   => "N: Amide",
                "HKR"  => "H: Basic",
            ],
        ];

        $service = new ReduceProteinAlphabetManager($this->proteinColors, $this->tripletSpeciesMock);

        $this->assertEquals($aExpected, $service->createReduceCode("11IMG"));
    }

    /**
     * Every reduction carries its description and as many groups as its name announces
     */
    #[DataProvider('providerAlphabetDescriptions')]
    public function testCreateReduceCodeDescribesEveryAlphabet($sType, $sDescription, $iGroups)
    {
        $service = new ReduceProteinAlphabetManager($this->proteinColors, $this->tripletSpeciesMock);

        $aReduction = $service->createReduceCode($sType);

        $this->assertEquals($sDescription, $aReduction["Description"]);
        $this->assertCount($iGroups, $aReduction["Elements"]);
    }

    public static function providerAlphabetDescriptions()
    {
        return [
            "Murphy 2"  => ["Murphy2",  "Murphy et al, 2000; 2 letters alphabet", 2],
            "Murphy 4"  => ["Murphy4",  "Murphy et al, 2000; 4 letters alphabet", 4],
            "Murphy 10" => ["Murphy10", "Murphy et al, 2000; 10 letters alphabet", 10],
            "Murphy 15" => ["Murphy15", "Murphy et al, 2000; 15 letters alphabet", 15],
            "Wang 2"    => ["Wang2",    "Wang & Wang, 1999; 2 letters alphabet", 2],
            "Wang 3"    => ["Wang3",    "Wang & Wang, 1999; 3 letters alphabet", 3],
            "Wang 5"    => ["Wang5",    "Wang & Wang, 1999; 5 letters alphabet", 5],
            "Wang 5v"   => ["Wang5v",   "Wang & Wang, 1999; 5 letters variant alphabet", 5],
            "Li 3"      => ["Li3",      "Li et al, 2003; 3 letters alphabet", 3],
            "Li 4"      => ["Li4",      "Li et al, 2003; 4 letters alphabet", 4],
            "Li 5"      => ["Li5",      "Li et al, 2003; 5 letters alphabet", 5],
            "Li 10"     => ["Li10",     "Li et al, 2003; 10 letters alphabet", 10],
            "IMGT 3"    => ["3IMG",     "3 IMGT amino acid hydropathy alphabet", 3],
            "IMGT 5"    => ["5IMG",     "5 IMGT amino acid volume alphabet", 5],
            "IMGT 11"   => ["11IMG",    "11 IMGT amino acid chemical characteristics alphabet", 11],
        ];
    }

    /**
     * An alphabet the API does not know gives nothing rather than failing
     */
    public function testCreateReduceCodeOfAnUnknownAlphabet()
    {
        $service = new ReduceProteinAlphabetManager($this->proteinColors, $this->tripletSpeciesMock);

        $this->assertEquals([], $service->createReduceCode("NoSuchAlphabet"));
    }
}