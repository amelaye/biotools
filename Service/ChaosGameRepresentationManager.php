<?php
/**
 * Chaos Game Representation Functions
 * Inspired by BioPHP's project biophp.org
 * Created 3 march 2019
 * Last modified 14 september 2026
 * RIP Pasha, gone 27 february 2019 =^._.^= ∫
 */
namespace Amelaye\BioTools\Service;

use Amelaye\BioPHP\Api\Interfaces\NucleotidApiAdapter;
use Amelaye\BioTools\Service\Graphics\SvgCanvas;

/**
 * Class ChaosGameRepresentationManager
 * @package BioTools\Service
 * @author Amélie DUVERNET aka Amelaye <amelieonline@gmail.com>
 */
class ChaosGameRepresentationManager
{
    /**
     * @var array
     */
    private $aNucleotidsGraphs;

    /**
     * @var array
     */
    private $aDnaComplements;

    /**
     * ChaosGameRepresentationManager constructor.
     * @param   array                        $aNucleotidsGraphs
     * @param   NucleotidApiAdapter          $nucleotidApi
     */
    public function __construct(array $aNucleotidsGraphs, NucleotidApiAdapter $nucleotidApi)
    {
        $this->aNucleotidsGraphs = $aNucleotidsGraphs;
        $this->aDnaComplements   = $nucleotidApi::GetDNAComplement($nucleotidApi->getNucleotids());
    }

    /**
     * Builds the path of the file to write, inside the configured directory. The
     * configured file name only provides the stem: a random suffix is appended so that
     * two concurrent requests cannot overwrite each other's graphic.
     * @param   string      $sConfiguredName
     * @return  string
     */
    private function buildTargetPath(string $sConfiguredName) : string
    {
        $sDirectory = rtrim($this->aNucleotidsGraphs['path_graphs'] ?? '', '/');
        $sStem      = pathinfo($sConfiguredName, PATHINFO_FILENAME);

        return $sDirectory . '/' . $sStem . '_' . bin2hex(random_bytes(4)) . '.svg';
    }

    /**
     * Compute nucleotide frequencies
     * Unit Test Created
     * @param   array   $aSeqData   Data of the sequence
     * @return  array
     * @throws  \Exception
     */
    public function numberNucleos($aSeqData)
    {
        try {
            $aNucleotides = [];

            foreach($this->aDnaComplements as $sNucleotide) {
                $aNucleotides[$sNucleotide] = substr_count($aSeqData["sequence"], $sNucleotide);
            }

            return $aNucleotides;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Analyses Data before sending the image
     * @param   string  $sSeqName
     * @param   string  $sSequence
     * @param   int     $iSize
     * @return  string  The path written
     * @throws  \Exception
     */
    public function CGRCompute($sSeqName, $sSequence, $iSize)
    {
        try {
            $iSeqLen = strlen($sSequence);

            if($iSize == "auto") {
                // legacy bug fix: the >100000 check used to run unconditionally after the
                // >1000000 one, so a sequence over 1,000,000 bp (which is also over 100,000)
                // always had its size overwritten back down to 512 and never reached 1024.
                // Ordered as a tier so the largest matching threshold wins.
                if($iSeqLen > 1000000) {
                    $iSize = 1024;
                } elseif($iSeqLen > 100000) {
                    $iSize = 512;
                } else {
                    $iSize = 256;
                }
            }

            return $this->createCGRImage($sSeqName, $sSequence, $iSize);
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }


    /**
     * Gets data sequences
     * Unit Test created (to review)
     * @param   string      $sSequence
     * @param   int         $iOligoLen
     * @param   int         $iStrand
     * @return  array
     * @throws  \Exception
     */
    public function FCGRCompute($sSequence, $iOligoLen, $iStrand)
    {
        try {
            // If double strand is requested to be computed...
            if ($iStrand == 2) {
                $sSeqRevert = strrev($sSequence);
                foreach ($this->aDnaComplements as $sNucleotide => $sComplement) {
                    $sSeqRevert = str_replace($sNucleotide, strtolower($sComplement), $sSeqRevert);
                }
                $sSequence .= " ".strtoupper($sSeqRevert);
            }

            $aDataSeq = array(
                "sequence" => $sSequence,
                "length"   => $iOligoLen,
            );
            return $aDataSeq;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }


    /**
     * CREATE CHAOS GAME REPRESENTATION OF FREQUENCIE
     * @param   string      $sSeqName
     * @param   string      $sSequence
     * @param   int         $iSize
     * @return  string      The path written
     * @throws  \Exception
     */
    public function createCGRImage($sSeqName, $sSequence, $iSize)
    {
        try {
            $oIm = new SvgCanvas($iSize, $iSize + 20);
            $sWhite = SvgCanvas::rgb(255, 255, 255);
            $sBlack = SvgCanvas::rgb(0, 0, 0);
            $oIm->background($sWhite);
            $fX = round($iSize / 2);
            $fY = $fX;
            $aPoints = [];
            for ($i = 0; $i < strlen($sSequence); $i++) {
                $sW = substr($sSequence, $i, 1);
                if ($sW == "A") {
                    $fX -= $fX / 2;
                    $fY += ($iSize - $fY) / 2;
                }
                if ($sW == "C") {
                    $fX -= $fX / 2;
                    $fY -= $fY / 2;
                }
                if ($sW == "G") {
                    $fX += ($iSize - $fX) / 2;
                    $fY -= $fY / 2;
                }
                if ($sW == "T") {
                    $fX += ($iSize - $fX) / 2;
                    $fY += ($iSize - $fY) / 2;
                }
                $aPoints[] = [floor($fX), floor($fY)];
            }
            $oIm->pixelCloud($aPoints, $sBlack);

            $iSeqlen = strlen($sSequence);
            $oIm->text(3, 5, $iSize + 5, "$sSeqName ($iSeqlen bp)", $sBlack);

            return $oIm->save($this->buildTargetPath($this->aNucleotidsGraphs["cgr_file"]));
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * CREATE CHAOS GAME REPRESENTATION OF FREQUENCIES
     * The FCGR drawing is written to a file; the image map coordinates and the path
     * written are returned as ["map" => [...], "file" => "..."]
     * Unit Test created
     * @param   array       $aOligos
     * @param   string      $sSeqName
     * @param   array       $aNucleotids
     * @param   int         $iSeqLen
     * @param   string      $sN
     * @param   int         $iOligoLen
     * @return  array
     * @throws  \Exception
     */
    public function createFCGRImage($aOligos, $sSeqName, $aNucleotids, $iSeqLen, $sN, $iOligoLen)
    {
        if (!is_array($aOligos) || [] === $aOligos) {
            throw new \Exception('The oligonucleotides array must not be empty.');
        }
        try {
            $iFontWeight = 3;

            $fMaxVal = max($aOligos);
            $fMinVal = min($aOligos);

            foreach($aOligos as $sKey => $fVal) {
                $aRatio[$sKey] = floor(255 - ((255 * ($fVal - $fMinVal)) / ($fMaxVal - $fMinVal)));
            }

            $oIm = new SvgCanvas(552, 370);


            for($iC = 0; $iC < 256; $iC++) {
                $aThecolor[$iC] = SvgCanvas::rgb($iC, $iC, $iC);
            }
            $sBackgroundColor = SvgCanvas::rgb(255, 255, 255);
            $oIm->rect(0,0,552,700,$sBackgroundColor);

            $sBlack = SvgCanvas::rgb(0, 0, 0);
            $sRed   = SvgCanvas::rgb(255, 0, 0);
            $sBlue  = SvgCanvas::rgb(0, 0, 255);

            $oIm->text(4, 10, 10, "Over or under-representation of oligonucleotides", $sBlue);
            $oIm->text(3, 20, 30, "Chaos Game Representation of frequencies (FCGR)", $sBlack);
            $oIm->line(10, 50, 350, 50, $sBlack);
            $sSeqName = substr($sSeqName,0,15);
            $oIm->text(3, 20, 55, "Sequence name: $sSeqName ($iSeqLen bp)", $sBlack);

            if($sN == 1) {
                $oIm->text(3, 20, 73, "Results for only one strand", $sBlack);
            }
            else if($sN == 2) {
                $oIm->text(3, 20, 73, "Results for both strands", $sBlack);
            }

            $aThecolor[255] = SvgCanvas::rgb(255, 255, 255);

            // maps area data
            $aForMap = $this->mapAreaData($aRatio, $aThecolor, $oIm);

            $aImageNucleotids = array(
                "A" => array("font" => $iFontWeight, "x" => 420,  "y" => 10, "occurences" => $aNucleotids["A"]),
                "C" => array("font" => $iFontWeight, "x" => 420,  "y" => 30, "occurences" => $aNucleotids["C"]),
                "G" => array("font" => $iFontWeight, "x" => 420,  "y" => 50, "occurences" => $aNucleotids["G"]),
                "T" => array("font" => $iFontWeight, "x" => 420,  "y" => 70, "occurences" => $aNucleotids["T"]),
            );

            foreach($aImageNucleotids as $sKey => $aL) {
                $oIm->text($aL["font"], $aL["x"], $aL["y"],  $sKey.': '.$aL["occurences"].'', $sBlack);
            }

            // lines
            $oIm->line(10,  90,  10,  346, $sBlack);
            $oIm->line(266, 90,  266, 346, $sBlack);
            $oIm->line(10,  90,  266, 90,  $sBlack);
            $oIm->line(10,  346, 266, 346, $sBlack);

            if($iOligoLen == 2) {
                $this->createGraphFor2Nucleo($oIm, $sBlack, $iFontWeight);
            }
            if ($iOligoLen == 3) {
                $this->createGraphForTrinucleo($oIm, $sBlack, $iFontWeight);
            }

            // show length of oligonucleotides
            $oIm->text($iFontWeight, 50, 350,  "Oligonucleotide length: $iOligoLen", $sBlack);


            $iCent = 286;
            $oIm->text(2, 6   + $iCent, 228, "Frequency", $sBlack);
            $oIm->rect(6   + $iCent,208,16  + $iCent,218,$aThecolor[255]);
            $oIm->rect(19  + $iCent,208,29  + $iCent,218,$aThecolor[240]);
            $oIm->rect(32  + $iCent,208,42  + $iCent,218,$aThecolor[225]);
            $oIm->rect(45  + $iCent,208,55  + $iCent,218,$aThecolor[210]);
            $oIm->rect(58  + $iCent,208,68  + $iCent,218,$aThecolor[195]);
            $oIm->rect(71  + $iCent,208,81  + $iCent,218,$aThecolor[180]);
            $oIm->rect(84  + $iCent,208,94  + $iCent,218,$aThecolor[165]);
            $oIm->rect(97  + $iCent,208,107 + $iCent,218,$aThecolor[150]);
            $oIm->rect(110 + $iCent,208,120 + $iCent,218,$aThecolor[135]);
            $oIm->rect(123 + $iCent,208,133 + $iCent,218,$aThecolor[135]);
            $oIm->rect(136 + $iCent,208,146 + $iCent,218,$aThecolor[120]);
            $oIm->rect(149 + $iCent,208,159 + $iCent,218,$aThecolor[105]);
            $oIm->rect(162 + $iCent,208,172 + $iCent,218,$aThecolor[90]);
            $oIm->rect(175 + $iCent,208,185 + $iCent,218,$aThecolor[75]);
            $oIm->rect(188 + $iCent,208,198 + $iCent,218,$aThecolor[60]);
            $oIm->rect(201 + $iCent,208,211 + $iCent,218,$aThecolor[45]);
            $oIm->rect(214 + $iCent,208,224 + $iCent,218,$aThecolor[30]);
            $oIm->rect(227 + $iCent,208,237 + $iCent,218,$aThecolor[15]);
            $oIm->rect(240 + $iCent,208,250 + $iCent,218,$aThecolor[0]);

            $sFile = $oIm->save($this->buildTargetPath($this->aNucleotidsGraphs["fcgr_file"]));

            return ['map' => $aForMap, 'file' => $sFile];
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Creates graph for two nucleotids
     * @param   SvgCanvas $oIm
     * @param   string $sBlack
     * @param   int $iFontWeight
     * @throws  \Exception
     */
    private function createGraphFor2Nucleo(&$oIm, $sBlack, $iFontWeight)
    {
        try {
            // lines
            $iStartX    = $this->aNucleotidsGraphs["startx_2"];
            $iStartY    = $this->aNucleotidsGraphs["starty_2"];
            $iInterval  = $this->aNucleotidsGraphs["intervals_2"];

            $oIm->line($iStartX, ($iStartY + $iInterval), ($iStartX + $iInterval * 4), ($iStartY + $iInterval), $sBlack);
            $oIm->line($iStartX, ($iStartY + $iInterval * 2), ($iStartX + $iInterval * 4), ($iStartY + $iInterval * 2), $sBlack);
            $oIm->line($iStartX, ($iStartY + $iInterval * 3), ($iStartX + $iInterval * 4), ($iStartY + $iInterval * 3), $sBlack);

            $oIm->line(($iStartX + $iInterval), $iStartY, ($iStartX + $iInterval), ($iStartY + $iInterval * 4), $sBlack);
            $oIm->line(($iStartX + $iInterval * 2), $iStartY, ($iStartX + $iInterval * 2), ($iStartY + $iInterval * 4), $sBlack);
            $oIm->line(($iStartX + $iInterval * 3), $iStartY, ($iStartX + $iInterval * 3), ($iStartY + $iInterval * 4), $sBlack);

            // dimers in their place
            $iHPos = $this->aNucleotidsGraphs["positions_2"]["h_pos"];
            $iVPos = $this->aNucleotidsGraphs["positions_2"]["v_pos"];

            $aImageNucleotids = array(
                "CC" => array(
                    "x" => $iStartX + $iHPos,
                    "y" => $iStartY + $iVPos
                ),
                "GC" => array(
                    "x" => $iStartX + $iInterval + $iHPos,
                    "y" => $iStartY + $iVPos
                ),
                "CG" => array(
                    "x" => $iStartX + ($iInterval * 2) + $iHPos,
                    "y" => $iStartY + $iVPos
                ),
                "GG" => array(
                    "x" => $iStartX + ($iInterval * 3) + $iHPos,
                    "y" => $iStartY + $iVPos
                ),

                "AC" => array(
                    "x" => $iStartX  + $iHPos,
                    "y" => $iStartY + $iInterval + $iVPos
                ),
                "TC" => array(
                    "x" => $iStartX + $iInterval + $iHPos,
                    "y" => $iStartY + $iInterval + $iVPos
                ),
                "AG" => array(
                    "x" => $iStartX + ($iInterval * 2) + $iHPos,
                    "y" => $iStartY + $iInterval + $iVPos
                ),
                "TG" => array(
                    "x" => $iStartX + ($iInterval * 3) + $iHPos,
                    "y" => $iStartY + $iInterval + $iVPos
                ),

                "CA" => array(
                    "x" => $iStartX + $iHPos,
                    "y" => $iStartY + ($iInterval * 2) + $iVPos
                ),
                "GA" => array(
                    "x" => $iStartX + $iInterval + $iHPos,
                    "y" => $iStartY + ($iInterval * 2) + $iVPos
                ),
                "CT" => array(
                    "x" => $iStartX + ($iInterval * 2) + $iHPos,
                    "y" => $iStartY + ($iInterval * 2) + $iVPos
                ),
                "GT" => array(
                    "x" => $iStartX + ($iInterval * 3) + $iHPos,
                    "y" => $iStartY + ($iInterval * 2) + $iVPos
                ),

                "AA" => array(
                    "x" => $iStartX  + $iHPos,
                    "y" => $iStartY + ($iInterval * 3) + $iVPos
                ),
                "TA" => array(
                    "x" => $iStartX + $iInterval + $iHPos,
                    "y" => $iStartY + ($iInterval * 3) + $iVPos
                ),
                "AT" => array(
                    "x" => $iStartX + ($iInterval * 2) + $iHPos,
                    "y" => $iStartY + ($iInterval * 3) + $iVPos
                ),
                "TT" => array(
                    "x" => $iStartX + ($iInterval * 3) + $iHPos,
                    "y" => $iStartY + ($iInterval * 3) + $iVPos
                ),
            );

            foreach($aImageNucleotids as $sKey => $aL) {
                $oIm->text($iFontWeight, $aL["x"], $aL["y"], $sKey, $sBlack);
            }
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Creates graph for three nucleotids
     * @param   SvgCanvas $oIm
     * @param   string $sBlack
     * @param   int $iFontWeight
     * @throws  \Exception
     */
    private function createGraphForTrinucleo(&$oIm, $sBlack, $iFontWeight)
    {
        try {
            // lines
            $oIm->line(10, 122, 266, 122, $sBlack);
            $oIm->line(10, 154, 266, 154, $sBlack);
            $oIm->line(10, 186, 266, 186, $sBlack);
            $oIm->line(10, 218, 266, 218, $sBlack);
            $oIm->line(10, 250, 266, 250, $sBlack);
            $oIm->line(10, 282, 266, 282, $sBlack);
            $oIm->line(10, 314, 266, 314, $sBlack);
            $oIm->line(42, 90, 42, 346, $sBlack);
            $oIm->line(74, 90, 74, 346, $sBlack);
            $oIm->line(106, 90, 106, 346, $sBlack);
            $oIm->line(138, 90, 138, 346, $sBlack);
            $oIm->line(170, 90, 170, 346, $sBlack);
            $oIm->line(202, 90, 202, 346, $sBlack);
            $oIm->line(234, 90, 234, 346, $sBlack);

            // trinucleotides in their place
            $iHPos = 8;
            $iVPos = 10;

            $aImageNucleotids = array(
                "CCC" => array("x" => 10   + $iHPos,  "y" => 90 + $iVPos), // x + 32
                "GCC" => array("x" => 42   + $iHPos,  "y" => 90 + $iVPos),
                "CGC" => array("x" => 74   + $iHPos,  "y" => 90 + $iVPos),
                "GGC" => array("x" => 106  + $iHPos,  "y" => 90 + $iVPos),
                "CCG" => array("x" => 138  + $iHPos,  "y" => 90 + $iVPos),
                "GCG" => array("x" => 170  + $iHPos,  "y" => 90 + $iVPos),
                "CGG" => array("x" => 202  + $iHPos,  "y" => 90 + $iVPos),
                "GGG" => array("x" => 234  + $iHPos,  "y" => 90 + $iVPos),

                "ACC" => array("x" => 10   + $iHPos,  "y" => 122 + $iVPos), // y + 32
                "TCC" => array("x" => 42   + $iHPos,  "y" => 122 + $iVPos),
                "AGC" => array("x" => 74   + $iHPos,  "y" => 122 + $iVPos),
                "TGC" => array("x" => 106  + $iHPos,  "y" => 122 + $iVPos),
                "ACG" => array("x" => 138  + $iHPos,  "y" => 122 + $iVPos),
                "TCG" => array("x" => 170  + $iHPos,  "y" => 122 + $iVPos),
                "AGG" => array("x" => 202  + $iHPos,  "y" => 122 + $iVPos),
                "TGG" => array("x" => 234  + $iHPos,  "y" => 122 + $iVPos),

                "CAC" => array("x" => 10   + $iHPos,  "y" => 154 + $iVPos),
                "GAC" => array("x" => 42   + $iHPos,  "y" => 154 + $iVPos),
                "ATC" => array("x" => 74   + $iHPos,  "y" => 154 + $iVPos),
                "CTC" => array("x" => 106  + $iHPos,  "y" => 154 + $iVPos),
                "CAG" => array("x" => 138  + $iHPos,  "y" => 154 + $iVPos),
                "GAG" => array("x" => 170  + $iHPos,  "y" => 154 + $iVPos),
                "CTG" => array("x" => 202  + $iHPos,  "y" => 154 + $iVPos),
                "GTG" => array("x" => 234  + $iHPos,  "y" => 154 + $iVPos),

                "AAC" => array("x" => 10   + $iHPos,  "y" => 186 + $iVPos),
                "TAC" => array("x" => 42   + $iHPos,  "y" => 186 + $iVPos),
                "GTC" => array("x" => 74   + $iHPos,  "y" => 186 + $iVPos),
                "TTC" => array("x" => 106  + $iHPos,  "y" => 186 + $iVPos),
                "AAG" => array("x" => 138  + $iHPos,  "y" => 186 + $iVPos),
                "TAG" => array("x" => 170  + $iHPos,  "y" => 186 + $iVPos),
                "ATG" => array("x" => 202  + $iHPos,  "y" => 186 + $iVPos),
                "TTG" => array("x" => 234  + $iHPos,  "y" => 186 + $iVPos),

                "CCA" => array("x" => 10   + $iHPos,  "y" => 218 + $iVPos),
                "GCA" => array("x" => 42   + $iHPos,  "y" => 218 + $iVPos),
                "CGA" => array("x" => 74   + $iHPos,  "y" => 218 + $iVPos),
                "GGA" => array("x" => 106  + $iHPos,  "y" => 218 + $iVPos),
                "CCT" => array("x" => 138  + $iHPos,  "y" => 218 + $iVPos),
                "GCT" => array("x" => 170  + $iHPos,  "y" => 218 + $iVPos),
                "CGT" => array("x" => 202  + $iHPos,  "y" => 218 + $iVPos),
                "GGT" => array("x" => 234  + $iHPos,  "y" => 218 + $iVPos),

                "ACA" => array("x" => 10   + $iHPos,  "y" => 250 + $iVPos),
                "TCA" => array("x" => 42   + $iHPos,  "y" => 250 + $iVPos),
                "AGA" => array("x" => 74   + $iHPos,  "y" => 250 + $iVPos),
                "TGA" => array("x" => 106  + $iHPos,  "y" => 250 + $iVPos),
                "ACT" => array("x" => 138  + $iHPos,  "y" => 250 + $iVPos),
                "TCT" => array("x" => 170  + $iHPos,  "y" => 250 + $iVPos),
                "AGT" => array("x" => 202  + $iHPos,  "y" => 250 + $iVPos),
                "TGT" => array("x" => 234  + $iHPos,  "y" => 250 + $iVPos),

                "CAA" => array("x" => 10   + $iHPos,  "y" => 282 + $iVPos),
                "GAA" => array("x" => 42   + $iHPos,  "y" => 282 + $iVPos),
                "CTA" => array("x" => 74   + $iHPos,  "y" => 282 + $iVPos),
                "GTA" => array("x" => 106  + $iHPos,  "y" => 282 + $iVPos),
                "CAT" => array("x" => 138  + $iHPos,  "y" => 282 + $iVPos),
                "GAT" => array("x" => 170  + $iHPos,  "y" => 282 + $iVPos),
                "CTT" => array("x" => 202  + $iHPos,  "y" => 282 + $iVPos),
                "GTT" => array("x" => 234  + $iHPos,  "y" => 282 + $iVPos),

                "AAA" => array("x" => 10   + $iHPos,  "y" => 314 + $iVPos),
                "TAA" => array("x" => 10   + $iHPos,  "y" => 314 + $iVPos),
                "ATA" => array("x" => 10   + $iHPos,  "y" => 314 + $iVPos),
                "TTA" => array("x" => 10   + $iHPos,  "y" => 314 + $iVPos),
                "AAT" => array("x" => 10   + $iHPos,  "y" => 314 + $iVPos),
                "TAT" => array("x" => 10   + $iHPos,  "y" => 314 + $iVPos),
                "ATT" => array("x" => 10   + $iHPos,  "y" => 314 + $iVPos),
                "TTT" => array("x" => 10   + $iHPos,  "y" => 314 + $iVPos),
            );

            foreach($aImageNucleotids as $sKey => $aL) {
                $oIm->text($iFontWeight, $aL["x"], $aL["y"], $sKey, $sBlack);
            }
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Creates the different positions of areas
     * @param   array   $aRatio
     * @param   array   $aThecolor
     * @param   SvgCanvas   $oIm
     * @return  array
     * @throws  \Exception
     */
    private function mapAreaData($aRatio, $aThecolor, $oIm)
    {
        try {
            $aAreas = [];
            $iFrameLength = null;

            foreach($aRatio as $sSeq => $fVal) {
                $iLen = strlen($sSeq);
                switch($iLen) {
                    case 7:
                        $iFrameLength = 1;
                        break;
                    case 6:
                        $iFrameLength = 3;
                        break;
                    case 5:
                        $iFrameLength = 7;
                        break;
                    case 4:
                        $iFrameLength = 15;
                        break;
                    case 3:
                        $iFrameLength = 31;
                        break;
                    case 2:
                        $iFrameLength = 63;
                        break;
                }

                $iHPos = $this->aNucleotidsGraphs["startx_2"];
                $iVPos = $this->aNucleotidsGraphs["starty_2"];

                // each position
                $fX = 0;
                $fY = 0;
                $iTt = 0;
                $iLen2 = $iLen;
                while($iLen2 > 0) {
                    $iLen2 --;
                    $fTtt = pow(2, $iTt);
                    $iTt ++;
                    $sSubseq1 = substr($sSeq, $iLen2, 1);
                    if($sSubseq1 == "A" || $sSubseq1 == "T") {
                        $fY += 128 / $fTtt;
                    }
                    if($sSubseq1 == "G" || $sSubseq1 == "T") {
                        $fX += 128 / $fTtt;
                    }
                }
                $fX += $iHPos;
                $fX2 = $fX + $iFrameLength;
                $fY += $iVPos;
                $fY2 = $fY + $iFrameLength;

                $oIm->rect($fX,$fY,$fX2,$fY2,$aThecolor[$fVal]);

                $aAreas[$sSeq] = array($fX,$fY,$fX2,$fY2);
            }
            return $aAreas;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }
}