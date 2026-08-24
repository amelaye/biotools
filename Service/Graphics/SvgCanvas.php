<?php
/**
 * Minimal SVG canvas, replacing the GD primitives used by the graphic managers
 * Created 24 august 2026
 * Last modified 24 august 2026
 */
namespace Amelaye\BioTools\Service\Graphics;

/**
 * Draws the minitools graphics as SVG instead of GD bitmaps.
 *
 * The drawing methods mirror the GD functions they replace one for one, so the
 * managers keep their original geometry: rect() for imagefilledrectangle(),
 * line() for imageline(), pixel() for imagesetpixel() and text() for imagestring().
 *
 * Class SvgCanvas
 * @package Amelaye\BioTools\Service\Graphics
 * @author Amélie DUVERNET aka Amelaye <amelieonline@gmail.com>
 */
class SvgCanvas
{
    /**
     * Metrics of the five built-in GD fonts, kept so that text laid out for GD keeps
     * its position: advance width and cell height in pixels, the equivalent SVG font
     * size, and the offset from the top of the cell down to the baseline — GD places
     * text by its top-left corner where SVG places it on the baseline.
     */
    private const GD_FONTS = [
        1 => ['width' => 5, 'size' => 8,  'baseline' => 7],
        2 => ['width' => 6, 'size' => 10, 'baseline' => 10],
        3 => ['width' => 7, 'size' => 11, 'baseline' => 10],
        4 => ['width' => 8, 'size' => 13, 'baseline' => 12],
        5 => ['width' => 9, 'size' => 14, 'baseline' => 12],
    ];

    /**
     * @var int
     */
    private $iWidth;

    /**
     * @var int
     */
    private $iHeight;

    /**
     * @var array   The SVG fragments, in painting order
     */
    private $aElements = [];

    /**
     * @param   int     $iWidth
     * @param   int     $iHeight
     */
    public function __construct(int $iWidth, int $iHeight)
    {
        $this->iWidth  = max(1, $iWidth);
        $this->iHeight = max(1, $iHeight);
    }

    /**
     * Builds a colour string, replacing imagecolorallocate()
     * @param   int     $iRed
     * @param   int     $iGreen
     * @param   int     $iBlue
     * @return  string
     */
    public static function rgb(int $iRed, int $iGreen, int $iBlue) : string
    {
        return sprintf('#%02x%02x%02x', max(0, min(255, $iRed)), max(0, min(255, $iGreen)), max(0, min(255, $iBlue)));
    }

    /**
     * Fills the whole canvas, as GD does when a background colour is allocated first
     * @param   string  $sColor
     * @return  self
     */
    public function background(string $sColor) : self
    {
        return $this->rect(0, 0, $this->iWidth, $this->iHeight, $sColor);
    }

    /**
     * Replaces imagefilledrectangle(). Coordinates may be given in any order.
     * @param   float   $fX1
     * @param   float   $fY1
     * @param   float   $fX2
     * @param   float   $fY2
     * @param   string  $sColor
     * @return  self
     */
    public function rect($fX1, $fY1, $fX2, $fY2, string $sColor) : self
    {
        $fLeft   = min($fX1, $fX2);
        $fTop    = min($fY1, $fY2);
        $fWidth  = abs($fX2 - $fX1);
        $fHeight = abs($fY2 - $fY1);

        $this->aElements[] = sprintf(
            '<rect x="%s" y="%s" width="%s" height="%s" fill="%s"/>',
            $this->num($fLeft),
            $this->num($fTop),
            $this->num($fWidth),
            $this->num($fHeight),
            $sColor
        );

        return $this;
    }

    /**
     * Replaces imageline()
     * @param   float   $fX1
     * @param   float   $fY1
     * @param   float   $fX2
     * @param   float   $fY2
     * @param   string  $sColor
     * @return  self
     */
    public function line($fX1, $fY1, $fX2, $fY2, string $sColor) : self
    {
        $this->aElements[] = sprintf(
            '<line x1="%s" y1="%s" x2="%s" y2="%s" stroke="%s" stroke-width="1"/>',
            $this->num($fX1),
            $this->num($fY1),
            $this->num($fX2),
            $this->num($fY2),
            $sColor
        );

        return $this;
    }

    /**
     * Replaces imagesetpixel()
     * @param   float   $fX
     * @param   float   $fY
     * @param   string  $sColor
     * @return  self
     */
    public function pixel($fX, $fY, string $sColor) : self
    {
        $this->aElements[] = sprintf(
            '<rect x="%s" y="%s" width="1" height="1" fill="%s"/>',
            $this->num($fX),
            $this->num($fY),
            $sColor
        );

        return $this;
    }

    /**
     * Replaces imagestring(). $iFont is a GD built-in font number (1 to 5); the text
     * is drawn in a monospaced face because the callers position their labels
     * assuming fixed width glyphs.
     * @param   int     $iFont
     * @param   float   $fX
     * @param   float   $fY         Top of the text, as GD expects it
     * @param   string  $sText
     * @param   string  $sColor
     * @return  self
     */
    public function text(int $iFont, $fX, $fY, string $sText, string $sColor) : self
    {
        $aFont = self::GD_FONTS[$iFont] ?? self::GD_FONTS[3];

        $this->aElements[] = sprintf(
            '<text x="%s" y="%s" font-family="%s" font-size="%s" fill="%s"'
            . ' xml:space="preserve">%s</text>',
            $this->num($fX),
            $this->num($fY + $aFont['baseline']),
            'ui-monospace, SFMono-Regular, Menlo, Consolas, monospace',
            $aFont['size'],
            $sColor,
            htmlspecialchars($sText, ENT_XML1 | ENT_QUOTES, 'UTF-8')
        );

        return $this;
    }

    /**
     * Draws a cloud of single pixels as one path instead of one element per point.
     *
     * Scatter plots such as the Chaos Game Representation set one pixel per base, and
     * the same pixel is hit over and over: on a short sequence four points out of five
     * are already duplicates, and the ratio only grows with the length. Removing the
     * duplicates and merging the rest into a single path keeps the drawing small
     * enough to stay usable on long sequences.
     *
     * @param   array   $aPoints    List of [x, y] pairs
     * @param   string  $sColor
     * @return  self
     */
    public function pixelCloud(array $aPoints, string $sColor) : self
    {
        $aUnique = [];
        foreach ($aPoints as $aPoint) {
            $iX = (int) $aPoint[0];
            $iY = (int) $aPoint[1];
            $aUnique[$iX . ' ' . $iY] = [$iX, $iY];
        }

        if ([] === $aUnique) {
            return $this;
        }

        $sPath = '';
        foreach ($aUnique as $aPoint) {
            // a one pixel horizontal segment, centred on the pixel row
            $sPath .= sprintf('M%d %sh1', $aPoint[0], $aPoint[1] + 0.5);
        }

        $this->aElements[] = sprintf(
            '<path d="%s" stroke="%s" stroke-width="1" fill="none" shape-rendering="crispEdges"/>',
            $sPath,
            $sColor
        );

        return $this;
    }

    /**
     * Advance width in pixels of a string drawn with the given GD font, for callers
     * that need to centre or right-align a label
     * @param   int     $iFont
     * @param   string  $sText
     * @return  int
     */
    public static function textWidth(int $iFont, string $sText) : int
    {
        $aFont = self::GD_FONTS[$iFont] ?? self::GD_FONTS[3];

        return $aFont['width'] * mb_strlen($sText);
    }

    /**
     * The SVG markup. A viewBox is set so the drawing scales to any size while the
     * width and height attributes keep the original pixel dimensions as the default.
     * @return  string
     */
    public function render() : string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . sprintf(
                '<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d" viewBox="0 0 %d %d">',
                $this->iWidth,
                $this->iHeight,
                $this->iWidth,
                $this->iHeight
            ) . "\n"
            . implode("\n", $this->aElements) . "\n"
            . '</svg>' . "\n";
    }

    /**
     * Writes the drawing, replacing imagepng(). Unlike imagepng(), a failure is
     * reported instead of being silently ignored.
     * @param   string      $sPath
     * @return  string      The path written
     * @throws  \Exception
     */
    public function save(string $sPath) : string
    {
        $sDirectory = \dirname($sPath);
        if (!is_dir($sDirectory)) {
            throw new \Exception(sprintf('Graphics directory "%s" does not exist.', $sDirectory));
        }
        if (!is_writable($sDirectory)) {
            throw new \Exception(sprintf('Graphics directory "%s" is not writable.', $sDirectory));
        }
        if (false === file_put_contents($sPath, $this->render())) {
            throw new \Exception(sprintf('Could not write the graphic to "%s".', $sPath));
        }

        return $sPath;
    }

    /**
     * Trims the useless decimals coordinates pick up from the geometry computations
     * @param   float|int   $mValue
     * @return  string
     */
    private function num($mValue) : string
    {
        return rtrim(rtrim(number_format((float) $mValue, 2, '.', ''), '0'), '.');
    }
}
