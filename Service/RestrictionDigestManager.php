<?php
/**
 * Restrictions Digest  Functions
 * Inspired by BioPHP's project biophp.org
 * Created 26 february 2019
 * Modified 27 february 2019 - RIP Pasha =^._.^= ∫
 * Last modified 14 september 2026
 */
namespace Amelaye\BioTools\Service;

use Amelaye\BioPHP\Api\Interfaces\TypeIIbEndonucleaseApiAdapter;
use Amelaye\BioPHP\Api\Interfaces\TypeIIEndonucleaseApiAdapter;
use Amelaye\BioPHP\Api\Interfaces\TypeIIsEndonucleaseApiAdapter;
use Amelaye\BioPHP\Api\Interfaces\VendorApiAdapter;
use Amelaye\BioPHP\Api\Interfaces\VendorLinkApiAdapter;

/**
 * Class RestrictionDigestManager
 * @package BioTools\Service
 * @author Amelie DUVERNET aka Amelaye <amelieonline@gmail.com>
 */
class RestrictionDigestManager
{
    /**
     * From API : list of vendor links
     * @var array
     */
    private $aVendorLinks;

    /**
     * From API : list of TypeII enzymes
     * @var array
     */
    private $aType2;

    /**
     * From API : list of TypeIIs enzymes
     * @var array
     */
    private $aType2s;

    /**
     * From API : list of TypeIIb enzymes
     * @var array
     */
    private $aType2b;

    /**
     * From API : list of vendors enzymes
     * @var array
     */
    private $aVendors;

    /**
     * RestrictionDigestManager constructor.
     * @param  VendorLinkApiAdapter             $vendorLinksApi
     * @param  TypeIIEndonucleaseApiAdapter     $typeIIEndonucleaseApi
     * @param  TypeIIbEndonucleaseApiAdapter    $typeIIbEndonucleaseApi
     * @param  TypeIIsEndonucleaseApiAdapter    $typeIIsEndonucleaseApi
     * @param  VendorApiAdapter                 $vendorApiAdapter
     */
    public function __construct(
        VendorLinkApiAdapter $vendorLinksApi,
        TypeIIEndonucleaseApiAdapter $typeIIEndonucleaseApi,
        TypeIIbEndonucleaseApiAdapter $typeIIbEndonucleaseApi,
        TypeIIsEndonucleaseApiAdapter $typeIIsEndonucleaseApi,
        VendorApiAdapter $vendorApiAdapter
    ) {
        $this->aVendorLinks = $vendorLinksApi::GetVendorLinksArray($vendorLinksApi->getVendorLinks());
        $this->aType2       = $typeIIEndonucleaseApi::GetTypeIIEndonucleasesArray($typeIIEndonucleaseApi->getTypeIIEndonucleases());
        $this->aType2b      = $typeIIbEndonucleaseApi::GetTypeIIbEndonucleasesArray($typeIIbEndonucleaseApi->getTypeIIbEndonucleases());
        $this->aType2s      = $typeIIsEndonucleaseApi::GetTypeIIsEndonucleasesArray($typeIIsEndonucleaseApi->getTypeIIsEndonucleases());
        $this->aVendors     = $vendorApiAdapter::GetVendorsArray($vendorApiAdapter->getVendors());
    }

    /**
     * Get array of companies selling each endonuclease
     * @param   string  $sMessage
     * @param   string  $sEnzyme
     * @return  array
     * @throws  \Exception
     */
    public function getVendors(&$sMessage, $sEnzyme)
    {
        try {
            $aEnzymeArray = [];
            // Get array of companies selling each endonuclease
            $aVendors = $this->aVendors;

            $aEndonuclease = preg_split("/,/", $sEnzyme);
            if (strpos($sEnzyme,",") > 0) {
                $sMessage = "All endonucleases bellow are isoschizomers";
            }

            // print vendor for each endonuclease (uses a function)
            foreach ($aEndonuclease as $sCurrentEnzyme) {
                $aEnzymeArray[$sCurrentEnzyme] = $this->showVendors($aVendors[$sCurrentEnzyme], $sCurrentEnzyme);
            }
            return $aEnzymeArray;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * We will get info for endonucleases. The info is included within 3 different
     * functions in the bottom (for Type II, IIb and IIs enzymes).
     * Type II endonucleases are always used
     * @param   bool    $bIIs       Asks for IIs array
     * @param   bool    $bIIb       Asks for IIb array
     * @param   bool    $bDefined   Only restriction enzymes with known bases
     * @param   string  $sWre       A specific enzyme requested from the dropdown, if any
     * @return  array
     * @throws \Exception
     */
    public function getNucleolasesInfos($bIIs, $bIIb, $bDefined, $sWre = "")
    {
        try {
            $aEnzymesArray = $this->aType2;

            // if TypeIIs endonucleases are requested, get them - or, whatever the
            // checkboxes say, when a specific enzyme was requested from the dropdown,
            // since reduceEnzymesArray() will narrow the pool down to just that one
            if (($bIIs && !$bDefined) || $sWre != "") {
                $aEnzymesArray = array_merge($aEnzymesArray, $this->aType2s);
                asort($aEnzymesArray);
            }
            // if TypeIIb endonucleases are requested, get them - same rule as above
            if (($bIIb && !$bDefined) || $sWre != "") {
                $aEnzymesArray = array_merge($aEnzymesArray, $this->aType2b);
                asort($aEnzymesArray);
            }
            return $aEnzymesArray;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Remove from the list of endonucleases the ones not matching the criteria in the form:
     * $minimum, $retype and $defined_sq
     * @param       array       $aEnzymes
     * @param       int         $iMinimun
     * @param       int         $iRetype
     * @param       bool        $bDefinedSq
     * @param       string      $sWre
     * @return      mixed
     * @throws      \Exception
     */
    public function reduceEnzymesArray($aEnzymes, $iMinimun, $iRetype, $bDefinedSq, $sWre)
    {
        try {
            $aNewEnzymes = [];
            // if $wre not null => all endonucleases but the selected one must be removed
            if($sWre != null) {
                foreach($aEnzymes as $sKey => $aVal) {
                    if (strpos(" ,".$aEnzymes[$sKey][0].",",$sWre) > 0) {
                        $aNewEnzymes[$sWre] = $aEnzymes[$sKey];
                        return $aNewEnzymes;
                    }
                }
            }
            // remove endonucleases which do not match requeriments
            foreach($aEnzymes as $sEnzyme => $aVal) {
                if ($iRetype == 1 && $aEnzymes[$sEnzyme][5] != 0) {
                    continue; // if retype==1 -> only Blund ends (continue for rest)
                }
                if ($iRetype == 2 && $aEnzymes[$sEnzyme][5] == 0) {
                    continue; // if retype==2 -> only Overhang end (continue for rest)
                }
                if ($iMinimun > $aEnzymes[$sEnzyme][6]) {
                    continue; // Only endonucleases with which recognized in template a minimum of bases (continue for rest)
                }
                if ($bDefinedSq == 1) {
                    if (strpos($aEnzymes[$sEnzyme][2],".") > 0 || strpos($aEnzymes[$sEnzyme][2],"|") > 0) {
                        continue; // if defined sequence selected, no N (".") or "|" in pattern
                    }
                }
                $aNewEnzymes[$sEnzyme] = $aEnzymes[$sEnzyme];
            }
            return $aNewEnzymes;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }


    /**
     * Calculate digestion results - will return an array like this
     * $digestion[$enzyme]["cuts"] - with number of cuts within the sequence
     * @param       array       $aEnzymes   List of available enzymes
     * @param       string      $sSequence  Sequence to analyze
     * @return      array
     * @throws      \Exception
     */
    public function restrictionDigest($aEnzymes, $sSequence)
    {
        try {
            $aDigestion = [];
            foreach ($aEnzymes as $sEnzyme => $aVal) {
                // this is to put together results for IIb endonucleases, which are computed as "enzyme_name" and "enzyme_name@"
                $aNewEnzyme = str_replace("@","", $sEnzyme);

                // split sequence based on pattern from restriction enzyme
                $aFragments = preg_split("/".$aEnzymes[$sEnzyme][2]."/", $sSequence,-1,PREG_SPLIT_DELIM_CAPTURE);
                reset($aFragments);
                $iMaxFragments = sizeof($aFragments);

                // when sequence is cleaved ($iMaxFragments > 1) start further calculations
                if($iMaxFragments > 1) {
                    $iRecognitionPosition = strlen($aFragments[0]);
                    // for each frament generated, calculate cleavage position,
                    // add it to a list, and add 1 to counter
                    for($i = 2; $i < $iMaxFragments; $i += 2) {
                        $iCleavagePosition = $iRecognitionPosition + $aEnzymes[$sEnzyme][4];
                        $aDigestion[$aNewEnzyme]["cuts"][$iCleavagePosition] = "";

                        // As overlapping may occur for many endonucleases,
                        // a subsequence starting in position 2 of fragment is calculate
                        if(isset($aFragments[$i+1])) {
                            $sSubSequence = substr($aFragments[$i-1],1)
                                .$aFragments[$i]
                                .substr($aFragments[$i+1],0,40);
                        } else {
                            $sSubSequence = substr($aFragments[$i-1],1) . $aFragments[$i];
                        }

                        $sSubSequence = substr($sSubSequence,0,2 * $aEnzymes[$sEnzyme][3] - 2);
                        // Previous process is repeated
                        // split subsequence based on pattern from restriction enzyme
                        $aFragmentsSubsequence = preg_split($aEnzymes[$sEnzyme][2],$sSubSequence);
                        // when subsequence is cleaved start further calculations
                        if(sizeof($aFragmentsSubsequence) > 1) {
                            // for each fragment of subsequence, calculate overlapping cleavage position,
                            //    add it to a list, and add 1 to counter
                            $iOverlappedCleavage = $iRecognitionPosition + 1 + strlen($aFragmentsSubsequence[0]) + $aEnzymes[$sEnzyme][4];
                            $aDigestion[$aNewEnzyme]["cuts"][$iOverlappedCleavage]="";
                        }
                        // this is a counter for position
                        $iRecognitionPosition += strlen($aFragments[$i-1]) + strlen($aFragments[$i]);
                    }
                }
            }
            return $aDigestion;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Extract sequences, which will be stored in an array
     * @param   string      $sSequence
     * @return  array
     * @throws  \Exception
     */
    public function extractSequences($sSequence)
    {
        try {
            $aSequence = [];
            if (substr_count($sSequence,">") == 0) {
                $aSequence[0]["seq"] = preg_replace("/\W|\d/", "", strtoupper($sSequence));
            } else {
                $aExtractSequences = preg_split("/>/", $sSequence,-1,PREG_SPLIT_NO_EMPTY);
                $iCounter = 0;
                foreach($aExtractSequences as $sVal) {
                    $sSeq = substr($sVal,strpos($sVal,"\n"));
                    $sSeq = preg_replace ("/\W|\d/", "", strtoupper($sSeq));
                    if (strlen($sSeq)>0){
                        $aSequence[$iCounter]["seq"] = $sSeq;
                        $aSequence[$iCounter]["name"] = substr($sVal,0,strpos($sVal,"\n"));
                        $iCounter++;
                    }
                }
            }
            return $aSequence;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Gets the names of the enzymes involved in digestion when more than one sequence
     * @param       array       $aSequence
     * @param       array       $aDigestion
     * @param       array       $aEnzymes
     * @param       bool        $bIsOnlyDiff
     * @param       string      $sWre
     * @return      array
     * @throws      \Exception
     */
    public function enzymesForMultiSeq($aSequence, $aDigestion, $aEnzymes, $bIsOnlyDiff, $sWre)
    {
        try {
            $aDigestionMulti = [];
            $iTempData = 0;

            // Two or more sequence available
            foreach($aEnzymes as $sEnzyme => $aVal) {
                $bChecker = false;
                if ($bIsOnlyDiff == false || $sWre != ""){
                    // Show all restriction results, when endonuclease cuts at least one sequence
                    foreach($aSequence as $iNumber => $aVal2){
                        if (isset($aDigestion[$iNumber][$sEnzyme]) && sizeof($aDigestion[$iNumber][$sEnzyme]["cuts"]) > 0) {
                            $bChecker = true;
                        }
                    }
                } else {
                    $aTemp = [];
                    if(isset($aDigestion[0][$sEnzyme])) {
                        // Show restriction results when they are different
                        $iTempData = sizeof($aDigestion[0][$sEnzyme]["cuts"]);
                        if ($iTempData > 0){
                            $aTemp = $aDigestion[0][$sEnzyme]["cuts"];
                        }
                    }

                    foreach($aSequence as $iNumber => $aVal2) {
                        if ($iNumber == 0) {
                            continue;
                        }
                        if(isset($aDigestion[$iNumber][$sEnzyme])) {
                            $iTempData2 = sizeof($aDigestion[$iNumber][$sEnzyme]["cuts"]);
                            if ($iTempData != $iTempData2) {
                                $bChecker = true;
                                break;
                            }
                            if ($iTempData2>0){
                                $aTemp = array_diff($aTemp, $aDigestion[$iNumber][$sEnzyme]["cuts"]);
                                if (sizeof($aTemp) > 0) {
                                    $bChecker = true;
                                    break;
                                }
                            }
                        }
                    }
                }
                if ($bChecker) {
                    // one entry per qualifying enzyme, however many sequences it cuts
                    $aDigestionMulti[] = $sEnzyme;
                }
            }
            return $aDigestionMulti;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Gets the commercial links to buy the enzymes
     * @param       string          $sCompany
     * @param       string          $sEnzyme
     * @return      array
     * @throws      \Exception
     */
    public function showVendors($sCompany, $sEnzyme)
    {
        try {
            $aEnzymeVendors = [];

            $aEnzymeVendors["company"] = ["name" => $sCompany, "url" => "https://rebase.neb.com/rebase/enz/$sEnzyme.html"];
            foreach($this->aVendorLinks as $sKey => $sData) {
                if(strpos($sCompany, $sKey) !== false) {
                    $aEnzymeVendors["links"][] = $sData;
                }
            }
            return $aEnzymeVendors;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }
}