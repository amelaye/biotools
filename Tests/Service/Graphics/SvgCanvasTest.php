<?php
/**
 * Tests of the SVG canvas replacing the GD primitives used by the graphic minitools
 * (Chaos Game Representation, Skews, Reduced Alphabets, Dendrograms)
 */
namespace Tests\MinitoolsBundle\Service\Graphics;

use Amelaye\BioTools\Service\Graphics\SvgCanvas;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SvgCanvasTest extends TestCase
{
    /**
     * @var array   Files written by testSave(), removed afterwards
     */
    private $aWrittenFiles = [];

    public function tearDown(): void
    {
        foreach ($this->aWrittenFiles as $sFile) {
            if (is_file($sFile)) {
                unlink($sFile);
            }
        }
        $this->aWrittenFiles = [];
    }

    /**
     * Replaces imagecolorallocate(): three channels into one hex string
     */
    public function testRgb()
    {
        $this->assertSame("#ff0000", SvgCanvas::rgb(255, 0, 0));
        $this->assertSame("#000000", SvgCanvas::rgb(0, 0, 0));
        $this->assertSame("#0a141e", SvgCanvas::rgb(10, 20, 30));
    }

    /**
     * Channels outside of the 0-255 range are clamped instead of producing a broken string
     */
    public function testRgbClampsOutOfRangeChannels()
    {
        $this->assertSame("#ff0080", SvgCanvas::rgb(300, -5, 128));
        $this->assertSame("#ffffff", SvgCanvas::rgb(1000, 1000, 1000));
        $this->assertSame("#000000", SvgCanvas::rgb(-1, -1, -1));
    }

    /**
     * An empty canvas still renders a valid SVG document carrying the requested size
     */
    public function testRenderOfAnEmptyCanvas()
    {
        $sSvg = (new SvgCanvas(200, 100))->render();

        $this->assertStringStartsWith('<?xml version="1.0" encoding="UTF-8"?>', $sSvg);
        $this->assertStringContainsString(
            '<svg xmlns="http://www.w3.org/2000/svg" width="200" height="100" viewBox="0 0 200 100">',
            $sSvg
        );
        $this->assertStringEndsWith("</svg>\n", $sSvg);
    }

    /**
     * A canvas cannot be narrower or shorter than one pixel
     */
    public function testCanvasSizeIsAtLeastOnePixel()
    {
        $sSvg = (new SvgCanvas(0, -5))->render();

        $this->assertStringContainsString('width="1" height="1" viewBox="0 0 1 1"', $sSvg);
    }

    /**
     * The rendered markup is well formed XML
     */
    public function testRenderProducesWellFormedXml()
    {
        $oCanvas = new SvgCanvas(50, 50);
        $oCanvas->background("#ffffff")
            ->rect(1, 1, 10, 10, "#ff0000")
            ->line(0, 0, 49, 49, "#00ff00")
            ->pixel(5, 5, "#0000ff")
            ->text(2, 3, 4, "A<B>", "#000000")
            ->pixelCloud([[1, 1], [2, 2]], "#123456");

        $oXml = simplexml_load_string($oCanvas->render());

        $this->assertNotFalse($oXml, "the canvas should render parseable XML");
    }

    public function testBackgroundFillsTheWholeCanvas()
    {
        $sSvg = (new SvgCanvas(200, 100))->background("#ffffff")->render();

        $this->assertStringContainsString('<rect x="0" y="0" width="200" height="100" fill="#ffffff"/>', $sSvg);
    }

    /**
     * Replaces imagefilledrectangle()
     */
    public function testRect()
    {
        $sSvg = (new SvgCanvas(50, 50))->rect(0, 0, 10, 5, "#000000")->render();

        $this->assertStringContainsString('<rect x="0" y="0" width="10" height="5" fill="#000000"/>', $sSvg);
    }

    /**
     * GD accepts the two corners in any order, so the canvas normalises them
     */
    public function testRectAcceptsItsCornersInAnyOrder()
    {
        $sReference = (new SvgCanvas(50, 50))->rect(0, 0, 10, 5, "#000000")->render();

        $this->assertSame($sReference, (new SvgCanvas(50, 50))->rect(10, 5, 0, 0, "#000000")->render());
        $this->assertSame($sReference, (new SvgCanvas(50, 50))->rect(10, 0, 0, 5, "#000000")->render());
        $this->assertSame($sReference, (new SvgCanvas(50, 50))->rect(0, 5, 10, 0, "#000000")->render());
    }

    /**
     * Replaces imageline()
     */
    public function testLine()
    {
        $sSvg = (new SvgCanvas(50, 50))->line(0, 0, 40, 30, "#ff0000")->render();

        $this->assertStringContainsString(
            '<line x1="0" y1="0" x2="40" y2="30" stroke="#ff0000" stroke-width="1"/>',
            $sSvg
        );
    }

    /**
     * Replaces imagesetpixel(): a one by one rectangle
     */
    public function testPixel()
    {
        $sSvg = (new SvgCanvas(50, 50))->pixel(3, 4, "#00ff00")->render();

        $this->assertStringContainsString('<rect x="3" y="4" width="1" height="1" fill="#00ff00"/>', $sSvg);
    }

    /**
     * The coordinates the geometry computations produce are trimmed to two decimals, and
     * a whole number keeps no decimal part at all
     */
    public function testCoordinatesAreTrimmedToTwoDecimals()
    {
        $sSvg = (new SvgCanvas(50, 50))->line(0.0, 1.5, 19.567, 9.50, "#000000")->render();

        $this->assertStringContainsString('x1="0" y1="1.5" x2="19.57" y2="9.5"', $sSvg);
    }

    /**
     * Replaces imagestring(). GD positions the text by the top of its cell, SVG by its
     * baseline, so the y given by the caller is pushed down by the font baseline: 10 for
     * the built-in font 3.
     */
    public function testText()
    {
        $sSvg = (new SvgCanvas(50, 50))->text(3, 10, 20, "ATG", "#123456")->render();

        $this->assertStringContainsString('<text x="10" y="30"', $sSvg);
        $this->assertStringContainsString('font-size="11"', $sSvg);
        $this->assertStringContainsString('fill="#123456"', $sSvg);
        $this->assertStringContainsString('>ATG</text>', $sSvg);
    }

    /**
     * The five GD built-in fonts each keep their own size and baseline
     */
    #[DataProvider('providerGdFonts')]
    public function testTextUsesTheMetricsOfEachGdFont($iFont, $iSize, $iBaseline)
    {
        $sSvg = (new SvgCanvas(50, 50))->text($iFont, 0, 100, "A", "#000000")->render();

        $this->assertStringContainsString('font-size="' . $iSize . '"', $sSvg);
        $this->assertStringContainsString('y="' . (100 + $iBaseline) . '"', $sSvg);
    }

    public static function providerGdFonts()
    {
        return [
            "font 1" => [1, 8, 7],
            "font 2" => [2, 10, 10],
            "font 3" => [3, 11, 10],
            "font 4" => [4, 13, 12],
            "font 5" => [5, 14, 12],
        ];
    }

    /**
     * A font number GD does not know falls back on font 3 instead of failing
     */
    public function testTextFallsBackOnFontThreeForAnUnknownFont()
    {
        $sReference = (new SvgCanvas(50, 50))->text(3, 0, 0, "A", "#000000")->render();

        $this->assertSame($sReference, (new SvgCanvas(50, 50))->text(9, 0, 0, "A", "#000000")->render());
        $this->assertSame($sReference, (new SvgCanvas(50, 50))->text(0, 0, 0, "A", "#000000")->render());
    }

    /**
     * Labels holding XML markup characters are escaped, so an enzyme or a sequence name
     * cannot break the document
     */
    public function testTextEscapesXmlSpecialCharacters()
    {
        $sSvg = (new SvgCanvas(50, 50))->text(3, 0, 0, 'A<B&C>"q"', "#000000")->render();

        $this->assertStringContainsString('>A&lt;B&amp;C&gt;&quot;q&quot;</text>', $sSvg);
        $this->assertNotFalse(simplexml_load_string($sSvg));
    }

    /**
     * Leading and trailing spaces of an aligned label are preserved
     */
    public function testTextPreservesWhitespace()
    {
        $sSvg = (new SvgCanvas(50, 50))->text(3, 0, 0, "  A  ", "#000000")->render();

        $this->assertStringContainsString('xml:space="preserve">  A  </text>', $sSvg);
    }

    /**
     * Advance width of a label, for the callers that centre or right align it
     */
    public function testTextWidth()
    {
        $this->assertSame(15, SvgCanvas::textWidth(1, "ABC"));
        $this->assertSame(18, SvgCanvas::textWidth(2, "ABC"));
        $this->assertSame(21, SvgCanvas::textWidth(3, "ABC"));
        $this->assertSame(24, SvgCanvas::textWidth(4, "ABC"));
        $this->assertSame(27, SvgCanvas::textWidth(5, "ABC"));
    }

    public function testTextWidthOfAnEmptyLabel()
    {
        $this->assertSame(0, SvgCanvas::textWidth(3, ""));
    }

    public function testTextWidthFallsBackOnFontThreeForAnUnknownFont()
    {
        $this->assertSame(SvgCanvas::textWidth(3, "AB"), SvgCanvas::textWidth(99, "AB"));
    }

    /**
     * A scatter plot is drawn as one path: the duplicated points a Chaos Game
     * Representation produces are dropped and the rest merged into a single element
     */
    public function testPixelCloud()
    {
        $sSvg = (new SvgCanvas(50, 50))->pixelCloud([[1, 2], [3, 4]], "#0000ff")->render();

        $this->assertStringContainsString(
            '<path d="M1 2.5h1M3 4.5h1" stroke="#0000ff" stroke-width="1" fill="none" shape-rendering="crispEdges"/>',
            $sSvg
        );
    }

    public function testPixelCloudRemovesDuplicatedPoints()
    {
        $sSvg = (new SvgCanvas(50, 50))
            ->pixelCloud([[1, 2], [1, 2], [1, 2], [3, 4], [1, 2]], "#0000ff")
            ->render();

        $this->assertStringContainsString('d="M1 2.5h1M3 4.5h1"', $sSvg);
    }

    /**
     * Coordinates are truncated to whole pixels, so two points inside the same pixel
     * count as one
     */
    public function testPixelCloudTruncatesCoordinatesToWholePixels()
    {
        $sSvg = (new SvgCanvas(50, 50))->pixelCloud([[1.2, 2.9], [1.8, 2.1]], "#0000ff")->render();

        $this->assertStringContainsString('d="M1 2.5h1"', $sSvg);
    }

    /**
     * An empty cloud draws nothing at all rather than an empty path
     */
    public function testPixelCloudOfAnEmptyListDrawsNothing()
    {
        $sSvg = (new SvgCanvas(50, 50))->pixelCloud([], "#0000ff")->render();

        $this->assertStringNotContainsString("<path", $sSvg);
    }

    /**
     * The drawing methods are chainable, as the managers use them
     */
    public function testDrawingMethodsAreChainable()
    {
        $oCanvas = new SvgCanvas(50, 50);

        $this->assertSame($oCanvas, $oCanvas->background("#ffffff"));
        $this->assertSame($oCanvas, $oCanvas->rect(0, 0, 1, 1, "#000000"));
        $this->assertSame($oCanvas, $oCanvas->line(0, 0, 1, 1, "#000000"));
        $this->assertSame($oCanvas, $oCanvas->pixel(0, 0, "#000000"));
        $this->assertSame($oCanvas, $oCanvas->text(3, 0, 0, "A", "#000000"));
        $this->assertSame($oCanvas, $oCanvas->pixelCloud([[0, 0]], "#000000"));
        $this->assertSame($oCanvas, $oCanvas->pixelCloud([], "#000000"));
    }

    /**
     * The elements are rendered in the order they were drawn, so a background painted
     * first stays behind the plot
     */
    public function testElementsAreRenderedInPaintingOrder()
    {
        $sSvg = (new SvgCanvas(50, 50))
            ->background("#ffffff")
            ->pixel(1, 1, "#ff0000")
            ->render();

        $this->assertLessThan(
            strpos($sSvg, '#ff0000'),
            strpos($sSvg, '#ffffff'),
            "the background should be painted before the plot"
        );
    }

    /**
     * Replaces imagepng(): writes the drawing and gives the path back
     */
    public function testSave()
    {
        $sPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid("biotools_canvas_", true) . ".svg";
        $this->aWrittenFiles[] = $sPath;

        $oCanvas = (new SvgCanvas(20, 10))->background("#ffffff");

        $this->assertSame($sPath, $oCanvas->save($sPath));
        $this->assertFileExists($sPath);
        $this->assertSame($oCanvas->render(), file_get_contents($sPath));
    }

    /**
     * Unlike imagepng(), a failure is reported instead of being silently ignored
     */
    public function testSaveThrowsWhenTheDirectoryDoesNotExist()
    {
        $sPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid("biotools_missing_", true)
            . DIRECTORY_SEPARATOR . "graph.svg";

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("does not exist");

        (new SvgCanvas(20, 10))->save($sPath);
    }
}
