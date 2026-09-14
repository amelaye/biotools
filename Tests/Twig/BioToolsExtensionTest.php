<?php

namespace Tests\Twig;

use Amelaye\BioTools\Service\DnaToProteinManager;
use Amelaye\BioTools\Service\SequenceAlignmentManager;
use Amelaye\BioTools\Twig\BioToolsExtension;
use PHPUnit\Framework\TestCase;
use Twig\TwigFilter;
use Twig\TwigFunction;

class BioToolsExtensionTest extends TestCase
{
    /**
     * @var mixed
     */
    protected $alignmentMock;

    /**
     * @var mixed
     */
    protected $dnaToProteinMock;

    public function setUp(): void
    {
        $this->alignmentMock = $this->getMockBuilder(SequenceAlignmentManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['compareAlignment'])
            ->getMock();

        $this->dnaToProteinMock = $this->getMockBuilder(DnaToProteinManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['showTranslationsAligned', 'showTranslationsAlignedComplementary'])
            ->getMock();
    }

    public function testGetFilters()
    {
        $oExtension = new BioToolsExtension($this->alignmentMock, $this->dnaToProteinMock);
        $aFilters = $oExtension->getFilters();

        $this->assertCount(1, $aFilters);
        $this->assertContainsOnlyInstancesOf(TwigFilter::class, $aFilters);
        $this->assertEquals("compare_alignment", $aFilters[0]->getName());
    }

    public function testGetFunctions()
    {
        $oExtension = new BioToolsExtension($this->alignmentMock, $this->dnaToProteinMock);
        $aFunctions = $oExtension->getFunctions();

        $aNames = array_map(function(TwigFunction $oFunction) {
            return $oFunction->getName();
        }, $aFunctions);

        $this->assertCount(2, $aFunctions);
        $this->assertContainsOnlyInstancesOf(TwigFunction::class, $aFunctions);
        $this->assertEquals(
            ["show_translations_aligned", "show_translations_aligned_complementary"],
            $aNames
        );
    }

    public function testCompareAlignmentDelegatesToTheManager()
    {
        $this->alignmentMock->expects($this->once())
            ->method("compareAlignment")
            ->with("ACGT", "ACTT")
            ->willReturn("|| ||");

        $oExtension = new BioToolsExtension($this->alignmentMock, $this->dnaToProteinMock);

        $this->assertEquals("|| ||", $oExtension->compareAlignment("ACGT", "ACTT"));
    }

    public function testShowTranslationsAlignedDelegatesToTheManager()
    {
        $aFrame = [1 => "MAK"];

        $this->dnaToProteinMock->expects($this->once())
            ->method("showTranslationsAligned")
            ->with("ATGGCTAAA", $aFrame)
            ->willReturn("ATGGCTAAA\nM  A  K");

        $oExtension = new BioToolsExtension($this->alignmentMock, $this->dnaToProteinMock);

        $this->assertEquals(
            "ATGGCTAAA\nM  A  K",
            $oExtension->showTranslationsAligned("ATGGCTAAA", $aFrame)
        );
    }

    public function testShowTranslationsAlignedComplementaryDelegatesToTheManager()
    {
        $aFrame = [4 => "FSL", 5 => "SL", 6 => "LF"];

        $this->dnaToProteinMock->expects($this->once())
            ->method("showTranslationsAlignedComplementary")
            ->with($aFrame)
            ->willReturn("TTTAGCCAT\nF  S  L");

        $oExtension = new BioToolsExtension($this->alignmentMock, $this->dnaToProteinMock);

        $this->assertEquals(
            "TTTAGCCAT\nF  S  L",
            $oExtension->showTranslationsAlignedComplementary($aFrame)
        );
    }
}
