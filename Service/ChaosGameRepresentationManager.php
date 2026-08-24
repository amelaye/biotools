<?php
/**
 * Chaos Game Representation Functions
 * Inspired by BioPHP's project biophp.org
 * Created 3 march 2019
 * Last modified 24 august 2026
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
    private $nucleotidsGraphs;

    /**
     * @var array
     */
    private $dnaComplements;

    /**
     * ChaosGameRepresentationManager constructor.
     * @param   array                        $nucleotidsGraphs
     * @param   NucleotidApiAdapter          $nucleotidApi
     */
    public function __construct(array $nucleotidsGraphs, NucleotidApiAdapter $nucleotidApi)
    {
        $this->nucleotidsGraphs = $nucleotidsGraphs;
        $this->dnaComplements   = $nucleotidApi::GetDNAComplement($nucleotidApi->getNucleotids());
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
        $sDirectory = rtrim($this->nucleotidsGraphs['path_graphs'] ?? '', '/');
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

            foreach($this->dnaComplements as $sNucleotide) {
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
                $iSize = 256;
                if($iSeqLen > 1000000) {
                    $iSize = 1024;
                }
                if($iSeqLen > 100000) {
                    $iSize = 512;
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
                $seqRevert = strrev($sSequence);
                foreach ($this->dnaComplements as $nucleotide => $complement) {
                    $seqRevert = str_replace($nucleotide, strtolower($complement), $seqRevert);
                }
                $sSequence .= " ".strtoupper($seqRevert);
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
            $im = new SvgCanvas($iSize, $iSize + 20);
            $white = SvgCanvas::rgb(255, 255, 255);
            $black = SvgCanvas::rgb(0, 0, 0);
            $im->background($white);
            $x = round($iSize / 2);
            $y = $x;
            $aPoints = [];
            for ($i = 0; $i < strlen($sSequence); $i++) {
                $w = substr($sSequence, $i, 1);
                if ($w == "A") {
                    $x -= $x / 2;
                    $y += ($iSize - $y) / 2;
                }
                if ($w == "C") {
                    $x -= $x / 2;
                    $y -= $y / 2;
                }
                if ($w == "G") {
                    $x += ($iSize - $x) / 2;
                    $y -= $y / 2;
                }
                if ($w == "T") {
                    $x += ($iSize - $x) / 2;
                    $y += ($iSize - $y) / 2;
                }
                $aPoints[] = [floor($x), floor($y)];
            }
            $im->pixelCloud($aPoints, $black);

            $iSeqlen = strlen($sSequence);
            $im->text(3, 5, $iSize + 5, "$sSeqName ($iSeqlen bp)", $black);

            return $im->save($this->buildTargetPath($this->nucleotidsGraphs["cgr_file"]));
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * CREATE CHAOS GAME REPRESENTATION OF FREQUENCIES
     * The FCGR drawing is written to a file; the image map coordinates and the path
     * written are returned as ["map" => [...], "file" => "..."]
     * Unit Test created
     * @param   array       $oligos
     * @param   string      $seq_name
     * @param   array       $aNucleotids
     * @param   int         $seq_len
     * @param   string      $n
     * @param   int         $oligo_len
     * @return  array
     * @throws  \Exception
     */
    public function createFCGRImage($oligos, $seq_name, $aNucleotids, $seq_len, $n, $oligo_len)
    {
        if (!is_array($oligos) || [] === $oligos) {
            throw new \Exception('The oligonucleotides array must not be empty.');
        }
        try {
            $iFontWeight = 3;

            $max_val = max($oligos);
            $min_val = min($oligos);

            foreach($oligos as $key => $val) {
                $ratio[$key] = floor(255 - ((255 * ($val - $min_val)) / ($max_val - $min_val)));
            }

            $im = new SvgCanvas(552, 370);


            for($c = 0; $c < 256; $c++) {
                $thecolor[$c] = SvgCanvas::rgb($c, $c, $c);
            }
            $background_color = SvgCanvas::rgb(255, 255, 255);
            $im->rect(0,0,552,700,$background_color);

            $black  = SvgCanvas::rgb(0, 0, 0);
            $red    = SvgCanvas::rgb(255, 0, 0);
            $blue   = SvgCanvas::rgb(0, 0, 255);

            $im->text(4, 10, 10, "Over or under-representation of oligonucleotides", $blue);
            $im->text(3, 20, 30, "Chaos Game Representation of frequencies (FCGR)", $black);
            $im->line(10, 50, 350, 50, $black);
            $seq_name = substr($seq_name,0,15);
            $im->text(3, 20, 55, "Sequence name: $seq_name ($seq_len bp)", $black);

            if($n == 1) {
                $im->text(3, 20, 73, "Results for only one strand", $black);
            }
            else if($n == 2) {
                $im->text(3, 20, 73, "Results for both strands", $black);
            }

            $thecolor[255] = SvgCanvas::rgb(255, 255, 255);

            // maps area data
            $for_map = $this->mapAreaData($ratio, $thecolor, $im);

            $imageNucleotids = array(
                "A" => array("font" => $iFontWeight, "x" => 420,  "y" => 10, "occurences" => $aNucleotids["A"]),
                "C" => array("font" => $iFontWeight, "x" => 420,  "y" => 30, "occurences" => $aNucleotids["C"]),
                "G" => array("font" => $iFontWeight, "x" => 420,  "y" => 50, "occurences" => $aNucleotids["G"]),
                "T" => array("font" => $iFontWeight, "x" => 420,  "y" => 70, "occurences" => $aNucleotids["T"]),
            );

            foreach($imageNucleotids as $key => $l) {
                $im->text($l["font"], $l["x"], $l["y"],  $key.': '.$l["occurences"].'', $black);
            }

            // lines
            $im->line(10,  90,  10,  346, $black);
            $im->line(266, 90,  266, 346, $black);
            $im->line(10,  90,  266, 90,  $black);
            $im->line(10,  346, 266, 346, $black);

            if($oligo_len == 2) {
                $this->createGraphFor2Nucleo($im, $black, $iFontWeight);
            }
            if ($oligo_len == 3) {
                $this->createGraphForTrinucleo($im, $black, $iFontWeight);
            }

            // show length of oligonucleotides
            $im->text($iFontWeight, 50, 350,  "Oligonucleotide length: $oligo_len", $black);


            $cent = 286;
            $im->text(2, 6   + $cent, 228, "Frequency", $black);
            $im->rect(6   + $cent,208,16  + $cent,218,$thecolor[255]);
            $im->rect(19  + $cent,208,29  + $cent,218,$thecolor[240]);
            $im->rect(32  + $cent,208,42  + $cent,218,$thecolor[225]);
            $im->rect(45  + $cent,208,55  + $cent,218,$thecolor[210]);
            $im->rect(58  + $cent,208,68  + $cent,218,$thecolor[195]);
            $im->rect(71  + $cent,208,81  + $cent,218,$thecolor[180]);
            $im->rect(84  + $cent,208,94  + $cent,218,$thecolor[165]);
            $im->rect(97  + $cent,208,107 + $cent,218,$thecolor[150]);
            $im->rect(110 + $cent,208,120 + $cent,218,$thecolor[135]);
            $im->rect(123 + $cent,208,133 + $cent,218,$thecolor[135]);
            $im->rect(136 + $cent,208,146 + $cent,218,$thecolor[120]);
            $im->rect(149 + $cent,208,159 + $cent,218,$thecolor[105]);
            $im->rect(162 + $cent,208,172 + $cent,218,$thecolor[90]);
            $im->rect(175 + $cent,208,185 + $cent,218,$thecolor[75]);
            $im->rect(188 + $cent,208,198 + $cent,218,$thecolor[60]);
            $im->rect(201 + $cent,208,211 + $cent,218,$thecolor[45]);
            $im->rect(214 + $cent,208,224 + $cent,218,$thecolor[30]);
            $im->rect(227 + $cent,208,237 + $cent,218,$thecolor[15]);
            $im->rect(240 + $cent,208,250 + $cent,218,$thecolor[0]);

            $sFile = $im->save($this->buildTargetPath($this->nucleotidsGraphs["fcgr_file"]));

            return ['map' => $for_map, 'file' => $sFile];
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Creates graph for two nucleotids
     * @param   SvgCanvas $im
     * @param   string $black
     * @param   int $iFontWeight
     * @throws  \Exception
     */
    private function createGraphFor2Nucleo(&$im, $black, $iFontWeight)
    {
        try {
            // lines
            $startx     = $this->nucleotidsGraphs["startx_2"];
            $starty     = $this->nucleotidsGraphs["starty_2"];
            $interval   = $this->nucleotidsGraphs["intervals_2"];

            $im->line($startx, ($starty + $interval), ($startx + $interval * 4), ($starty + $interval), $black);
            $im->line($startx, ($starty + $interval * 2), ($startx + $interval * 4), ($starty + $interval * 2), $black);
            $im->line($startx, ($starty + $interval * 3), ($startx + $interval * 4), ($starty + $interval * 3), $black);

            $im->line(($startx + $interval), $starty, ($startx + $interval), ($starty + $interval * 4), $black);
            $im->line(($startx + $interval * 2), $starty, ($startx + $interval * 2), ($starty + $interval * 4), $black);
            $im->line(($startx + $interval * 3), $starty, ($startx + $interval * 3), ($starty + $interval * 4), $black);

            // dimers in their place
            $h_pos = $this->nucleotidsGraphs["positions_2"]["h_pos"];
            $v_pos = $this->nucleotidsGraphs["positions_2"]["v_pos"];

            $imageNucleotids = array(
                "CC" => array(
                    "x" => $startx + $h_pos,
                    "y" => $starty + $v_pos
                ),
                "GC" => array(
                    "x" => $startx + $interval + $h_pos,
                    "y" => $starty + $v_pos
                ),
                "CG" => array(
                    "x" => $startx + ($interval * 2) + $h_pos,
                    "y" => $starty + $v_pos
                ),
                "GG" => array(
                    "x" => $startx + ($interval * 3) + $h_pos,
                    "y" => $starty + $v_pos
                ),

                "AC" => array(
                    "x" => $startx  + $h_pos,
                    "y" => $starty + $interval + $v_pos
                ),
                "TC" => array(
                    "x" => $startx + $interval + $h_pos,
                    "y" => $starty + $interval + $v_pos
                ),
                "AG" => array(
                    "x" => $startx + ($interval * 2) + $h_pos,
                    "y" => $starty + $interval + $v_pos
                ),
                "TG" => array(
                    "x" => $startx + ($interval * 3) + $h_pos,
                    "y" => $starty + $interval + $v_pos
                ),

                "CA" => array(
                    "x" => $startx + $h_pos,
                    "y" => $starty + ($interval * 2) + $v_pos
                ),
                "GA" => array(
                    "x" => $startx + $interval + $h_pos,
                    "y" => $starty + ($interval * 2) + $v_pos
                ),
                "CT" => array(
                    "x" => $startx + ($interval * 2) + $h_pos,
                    "y" => $starty + ($interval * 2) + $v_pos
                ),
                "GT" => array(
                    "x" => $startx + ($interval * 3) + $h_pos,
                    "y" => $starty + ($interval * 2) + $v_pos
                ),

                "AA" => array(
                    "x" => $startx  + $h_pos,
                    "y" => $starty + ($interval * 3) + $v_pos
                ),
                "TA" => array(
                    "x" => $startx + $interval + $h_pos,
                    "y" => $starty + ($interval * 3) + $v_pos
                ),
                "AT" => array(
                    "x" => $startx + ($interval * 2) + $h_pos,
                    "y" => $starty + ($interval * 3) + $v_pos
                ),
                "TT" => array(
                    "x" => $startx + ($interval * 3) + $h_pos,
                    "y" => $starty + ($interval * 3) + $v_pos
                ),
            );

            foreach($imageNucleotids as $key => $l) {
                $im->text($iFontWeight, $l["x"], $l["y"], $key, $black);
            }
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Creates graph for three nucleotids
     * @param   SvgCanvas $im
     * @param   string $black
     * @param   int $iFontWeight
     * @throws  \Exception
     */
    private function createGraphForTrinucleo(&$im, $black, $iFontWeight)
    {
        try {
            // lines
            $im->line(10, 122, 266, 122, $black);
            $im->line(10, 154, 266, 154, $black);
            $im->line(10, 186, 266, 186, $black);
            $im->line(10, 218, 266, 218, $black);
            $im->line(10, 250, 266, 250, $black);
            $im->line(10, 282, 266, 282, $black);
            $im->line(10, 314, 266, 314, $black);
            $im->line(42, 90, 42, 346, $black);
            $im->line(74, 90, 74, 346, $black);
            $im->line(106, 90, 106, 346, $black);
            $im->line(138, 90, 138, 346, $black);
            $im->line(170, 90, 170, 346, $black);
            $im->line(202, 90, 202, 346, $black);
            $im->line(234, 90, 234, 346, $black);

            // trinucleotides in their place
            $h_pos = 8;
            $v_pos = 10;

            $imageNucleotids = array(
                "CCC" => array("x" => 10   + $h_pos,  "y" => 90 + $v_pos), // x + 32
                "GCC" => array("x" => 42   + $h_pos,  "y" => 90 + $v_pos),
                "CGC" => array("x" => 74   + $h_pos,  "y" => 90 + $v_pos),
                "GGC" => array("x" => 106  + $h_pos,  "y" => 90 + $v_pos),
                "CCG" => array("x" => 138  + $h_pos,  "y" => 90 + $v_pos),
                "GCG" => array("x" => 170  + $h_pos,  "y" => 90 + $v_pos),
                "CGG" => array("x" => 202  + $h_pos,  "y" => 90 + $v_pos),
                "GGG" => array("x" => 234  + $h_pos,  "y" => 90 + $v_pos),

                "ACC" => array("x" => 10   + $h_pos,  "y" => 122 + $v_pos), // y + 32
                "TCC" => array("x" => 42   + $h_pos,  "y" => 122 + $v_pos),
                "AGC" => array("x" => 74   + $h_pos,  "y" => 122 + $v_pos),
                "TGC" => array("x" => 106  + $h_pos,  "y" => 122 + $v_pos),
                "ACG" => array("x" => 138  + $h_pos,  "y" => 122 + $v_pos),
                "TCG" => array("x" => 170  + $h_pos,  "y" => 122 + $v_pos),
                "AGG" => array("x" => 202  + $h_pos,  "y" => 122 + $v_pos),
                "TGG" => array("x" => 234  + $h_pos,  "y" => 122 + $v_pos),

                "CAC" => array("x" => 10   + $h_pos,  "y" => 154 + $v_pos),
                "GAC" => array("x" => 42   + $h_pos,  "y" => 154 + $v_pos),
                "ATC" => array("x" => 74   + $h_pos,  "y" => 154 + $v_pos),
                "CTC" => array("x" => 106  + $h_pos,  "y" => 154 + $v_pos),
                "CAG" => array("x" => 138  + $h_pos,  "y" => 154 + $v_pos),
                "GAG" => array("x" => 170  + $h_pos,  "y" => 154 + $v_pos),
                "CTG" => array("x" => 202  + $h_pos,  "y" => 154 + $v_pos),
                "GTG" => array("x" => 234  + $h_pos,  "y" => 154 + $v_pos),

                "AAC" => array("x" => 10   + $h_pos,  "y" => 186 + $v_pos),
                "TAC" => array("x" => 42   + $h_pos,  "y" => 186 + $v_pos),
                "GTC" => array("x" => 74   + $h_pos,  "y" => 186 + $v_pos),
                "TTC" => array("x" => 106  + $h_pos,  "y" => 186 + $v_pos),
                "AAG" => array("x" => 138  + $h_pos,  "y" => 186 + $v_pos),
                "TAG" => array("x" => 170  + $h_pos,  "y" => 186 + $v_pos),
                "ATG" => array("x" => 202  + $h_pos,  "y" => 186 + $v_pos),
                "TTG" => array("x" => 234  + $h_pos,  "y" => 186 + $v_pos),

                "CCA" => array("x" => 10   + $h_pos,  "y" => 218 + $v_pos),
                "GCA" => array("x" => 42   + $h_pos,  "y" => 218 + $v_pos),
                "CGA" => array("x" => 74   + $h_pos,  "y" => 218 + $v_pos),
                "GGA" => array("x" => 106  + $h_pos,  "y" => 218 + $v_pos),
                "CCT" => array("x" => 138  + $h_pos,  "y" => 218 + $v_pos),
                "GCT" => array("x" => 170  + $h_pos,  "y" => 218 + $v_pos),
                "CGT" => array("x" => 202  + $h_pos,  "y" => 218 + $v_pos),
                "GGT" => array("x" => 234  + $h_pos,  "y" => 218 + $v_pos),

                "ACA" => array("x" => 10   + $h_pos,  "y" => 250 + $v_pos),
                "TCA" => array("x" => 42   + $h_pos,  "y" => 250 + $v_pos),
                "AGA" => array("x" => 74   + $h_pos,  "y" => 250 + $v_pos),
                "TGA" => array("x" => 106  + $h_pos,  "y" => 250 + $v_pos),
                "ACT" => array("x" => 138  + $h_pos,  "y" => 250 + $v_pos),
                "TCT" => array("x" => 170  + $h_pos,  "y" => 250 + $v_pos),
                "AGT" => array("x" => 202  + $h_pos,  "y" => 250 + $v_pos),
                "TGT" => array("x" => 234  + $h_pos,  "y" => 250 + $v_pos),

                "CAA" => array("x" => 10   + $h_pos,  "y" => 282 + $v_pos),
                "GAA" => array("x" => 42   + $h_pos,  "y" => 282 + $v_pos),
                "CTA" => array("x" => 74   + $h_pos,  "y" => 282 + $v_pos),
                "GTA" => array("x" => 106  + $h_pos,  "y" => 282 + $v_pos),
                "CAT" => array("x" => 138  + $h_pos,  "y" => 282 + $v_pos),
                "GAT" => array("x" => 170  + $h_pos,  "y" => 282 + $v_pos),
                "CTT" => array("x" => 202  + $h_pos,  "y" => 282 + $v_pos),
                "GTT" => array("x" => 234  + $h_pos,  "y" => 282 + $v_pos),

                "AAA" => array("x" => 10   + $h_pos,  "y" => 314 + $v_pos),
                "TAA" => array("x" => 10   + $h_pos,  "y" => 314 + $v_pos),
                "ATA" => array("x" => 10   + $h_pos,  "y" => 314 + $v_pos),
                "TTA" => array("x" => 10   + $h_pos,  "y" => 314 + $v_pos),
                "AAT" => array("x" => 10   + $h_pos,  "y" => 314 + $v_pos),
                "TAT" => array("x" => 10   + $h_pos,  "y" => 314 + $v_pos),
                "ATT" => array("x" => 10   + $h_pos,  "y" => 314 + $v_pos),
                "TTT" => array("x" => 10   + $h_pos,  "y" => 314 + $v_pos),
            );

            foreach($imageNucleotids as $key => $l) {
                $im->text($iFontWeight, $l["x"], $l["y"], $key, $black);
            }
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Creates the different positions of areas
     * @param   array   $aRatio
     * @param   array   $aThecolor
     * @param   $im
     * @return  array
     * @throws  \Exception
     */
    private function mapAreaData($aRatio, $aThecolor, $im)
    {
        try {
            $aAreas = [];
            $frameLength = null;

            foreach($aRatio as $seq => $val) {
                $len = strlen($seq);
                switch($len) {
                    case 7:
                        $frameLength = 1;
                        break;
                    case 6:
                        $frameLength = 3;
                        break;
                    case 5:
                        $frameLength = 7;
                        break;
                    case 4:
                        $frameLength = 15;
                        break;
                    case 3:
                        $frameLength = 31;
                        break;
                    case 2:
                        $frameLength = 63;
                        break;
                }

                $h_pos = $this->nucleotidsGraphs["startx_2"];
                $v_pos = $this->nucleotidsGraphs["starty_2"];

                // each position
                $x = 0;
                $y = 0;
                $tt = 0;
                $len2 = $len;
                while($len2 > 0) {
                    $len2 --;
                    $ttt = pow(2, $tt);
                    $tt ++;
                    $subseq1 = substr($seq, $len2, 1);
                    if($subseq1 == "A" || $subseq1 == "T") {
                        $y += 128 / $ttt;
                    }
                    if($subseq1 == "G" || $subseq1 == "T") {
                        $x += 128 / $ttt;
                    }
                }
                $x += $h_pos;
                $x2 = $x + $frameLength;
                $y += $v_pos;
                $y2 = $y + $frameLength;

                $im->rect($x,$y,$x2,$y2,$aThecolor[$val]);

                $aAreas[$seq] = array($x,$y,$x2,$y2);
            }
            return $aAreas;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }
}