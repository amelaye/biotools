<?php


namespace Tests\MinitoolsBundle\Service;

use Amelaye\BioPHP\Api\ProteinReductionApi;
use App\Service\ReduceProteinAlphabetManager;
use PHPUnit\Framework\TestCase;

class ReduceProteinAlphabetManagerTest extends TestCase
{
    protected $proteinColors;

    public function setUp()
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

        require_once('samples/ProteinReductions.php');

        /**
         * Mock API
         */
        $this->tripletSpeciesMock = $this->getMockBuilder(ProteinReductionApi::class)
            ->disableOriginalConstructor()
            ->setMethods(['getReductions'])
            ->getMock();
        $this->tripletSpeciesMock->method("getReductions")->will($this->returnValue($aReductions));
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
}