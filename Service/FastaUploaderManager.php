<?php
/**
 * FastaUploadManager
 * Inspired by BioPHP's project biophp.org
 * Created 18 march 2019
 * Last modified 14 september 2026
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
     * Validates the sequence in the file
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
            if($this->isValidSequence($sVar)) {
                $sUpper = strtoupper(substr($sVar, 0, (int) $iLength));
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