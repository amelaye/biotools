<?php
/**
 * FastaUploadManager
 * Inspired by BioPHP's project biophp.org
 * Created 18 march 2019
 * Last modified 14 september 2026
 */
namespace Amelaye\BioTools\Service;

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
            $iLength = strlen($sSequence);
            for ($i = 0; $i < $iLength; ++$i) {
                if(!($sSequence[$i]=='a' || $sSequence[$i]=='A'||
                    $sSequence[$i]=='t'|| $sSequence[$i]=='T' ||
                    $sSequence[$i]=='g'|| $sSequence[$i]=='G'||
                    $sSequence[$i]=='c'|| $sSequence[$i]=='C')) {
                    return false;
                }
            }
            return true;
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
                for ($i = 0; $i < $iLength ; ++$i) {
                    switch($sVar[$i]) {
                        case 'a':
                        case 'A':
                            $iA++;
                            break;
                        case 't':
                        case 'T':
                            $iT++;
                            break;
                        case 'c':
                        case 'C':
                            $iC++;
                            break;
                        case 'g':
                        case 'G':
                            $iG++;
                    }
                }
            } else {
                throw new \Exception("Please check....This is not a Nucleotide sequence");
            }
        }
    }
}