<?php
/**
 * Twig extension exposing the bundle's text-formatting Service methods as
 * Twig filters/functions, so consuming applications can build alignment and
 * translation views without duplicating this presentation logic.
 * Created 14 september 2026
 */
namespace Amelaye\BioTools\Twig;

use Amelaye\BioTools\Service\DnaToProteinManager;
use Amelaye\BioTools\Service\SequenceAlignmentManager;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Class BioToolsExtension
 * @package Amelaye\BioTools\Twig
 * @author Amélie DUVERNET aka Amelaye <amelieonline@gmail.com>
 */
class BioToolsExtension extends AbstractExtension
{
    /**
     * @var SequenceAlignmentManager
     */
    private $oSequenceAlignmentManager;

    /**
     * @var DnaToProteinManager
     */
    private $oDnaToProteinManager;

    /**
     * BioToolsExtension constructor.
     * @param   SequenceAlignmentManager   $oSequenceAlignmentManager
     * @param   DnaToProteinManager        $oDnaToProteinManager
     */
    public function __construct(
        SequenceAlignmentManager $oSequenceAlignmentManager,
        DnaToProteinManager $oDnaToProteinManager
    ) {
        $this->oSequenceAlignmentManager = $oSequenceAlignmentManager;
        $this->oDnaToProteinManager = $oDnaToProteinManager;
    }

    /**
     * @return TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('compare_alignment', [$this, 'compareAlignment']),
        ];
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('show_translations_aligned', [$this, 'showTranslationsAligned']),
            new TwigFunction('show_translations_aligned_complementary', [$this, 'showTranslationsAlignedComplementary']),
        ];
    }

    /**
     * Twig filter: {{ sSeqa|compare_alignment(sSeqb) }}
     * @param   string  $sSeqa
     * @param   string  $sSeqb
     * @return  string
     */
    public function compareAlignment(string $sSeqa, string $sSeqb): string
    {
        return $this->oSequenceAlignmentManager->compareAlignment($sSeqa, $sSeqb);
    }

    /**
     * Twig function: {{ show_translations_aligned(sSequence, aFrame) }}
     * @param   string  $sSequence
     * @param   array   $aFrame
     * @return  string
     * @throws  \Exception
     */
    public function showTranslationsAligned(string $sSequence, array $aFrame): string
    {
        return $this->oDnaToProteinManager->showTranslationsAligned($sSequence, $aFrame);
    }

    /**
     * Twig function: {{ show_translations_aligned_complementary(aFrame) }}
     * @param   array   $aFrame
     * @return  string
     * @throws  \Exception
     */
    public function showTranslationsAlignedComplementary(array $aFrame): string
    {
        return $this->oDnaToProteinManager->showTranslationsAlignedComplementary($aFrame);
    }
}
