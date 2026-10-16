<?php
/**
 * FastaUploadManager
 * Inspired by BioPHP's project biophp.org
 * Created 18 march 2019
 * Last modified 16 october 2026
 */
namespace Amelaye\BioTools\Service;

use Amelaye\BioPHP\Domain\Tools\Service\GeneticsFunctions;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

/**
 * Class FastaUploaderManager
 * @package BioTools\Service
 * @author Amélie DUVERNET aka Amelaye <amelieonline@gmail.com>
 */
class FastaUploaderManager
{
    /**
     * Checks the validity of the sequence
     * @param       string      $sSequence
     * @return      bool
     * @throws      \Exception
     */
    public function isValidSequence($sSequence)
    {
        if (!is_string($sSequence)) {
            throw new \Exception('The sequence must be a string.');
        }
        try {
            return GeneticsFunctions::CountACGT(strtoupper($sSequence)) === strlen($sSequence);
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Copy file into the server
     * @param  object   $oFile
     * @param  string   $sBrochuresDirectory
     * @return string
     */
    public function createFiles($oFile, $sBrochuresDirectory)
    {
        $sFileName = md5(uniqid()).'.txt';

        try {
            $oFile->move(
                $sBrochuresDirectory,
                $sFileName
            );
        } catch (FileException $e) {
            throw new FileException($e);
        }

        $sMyFile = $sBrochuresDirectory.'/'.$sFileName;
        $rFh = fopen($sMyFile, 'r');
        $sVar = fread($rFh, 1000000);
        fclose($rFh);

        return $sVar;
    }

    /**
     * Keeps the bases of a FASTA text: the header lines (">...") and the comment lines (";...", old
     * FASTA), then all the white space, line breaks included, are removed. The line endings may be
     * \n, \r\n or \r alone (old Mac files). Several records are concatenated: that is right for
     * the base composition, which is additive, and wrong for any statistic on words (a dinucleotide
     * across two records would be counted).
     * @param   string  $sText
     * @return  string
     */
    private function cleanFastaSequence(string $sText) : string
    {
        $sText = preg_replace('/\r\n|\r/', "\n", $sText);
        $sText = preg_replace('/^[>;].*$/m', '', $sText);

        return preg_replace('/\s+/', '', $sText);
    }

    /**
     * Validates the sequence in the file, once cleaned of its header and white space, then counts
     * the bases in its first $iLength characters. A N is an unknown base: it is accepted, and not
     * counted as A, C, G or T.
     * @param   string  $sVar
     * @param   int     $iA
     * @param   int     $iG
     * @param   int     $iT
     * @param   int     $iC
     * @param   int     $iLength
     * @throws  \Exception
     */
    public function checkNucleotidSequence($sVar, &$iA, &$iG, &$iT, &$iC, $iLength)
    {
        if($iLength != '') {
            $sClean = $this->cleanFastaSequence((string) $sVar);
            if($sClean === '') {
                throw new \Exception("No sequence was found in the file (only a header, or nothing)");
            }
            if(preg_match('/^[ACGTN]*$/i', $sClean) === 1) {
                $sUpper = strtoupper(substr($sClean, 0, (int) $iLength));
                $iA += substr_count($sUpper, 'A');
                $iT += substr_count($sUpper, 'T');
                $iC += substr_count($sUpper, 'C');
                $iG += substr_count($sUpper, 'G');
            } else {
                throw new \Exception("Please check....This is not a Nucleotide sequence");
            }
        }
    }
}
