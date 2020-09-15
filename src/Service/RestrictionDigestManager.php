<?php
/**
 * Restrictions Digest  Functions
 * Inspired by BioPHP's project biophp.org
 * Created 26 february 2019
 * Modified 27 february 2019 - RIP Pasha =^._.^= ∫
 * Last modified 15 september 2020
 */
namespace App\Service;

use Amelaye\BioPHP\Api\Interfaces\TypeIIbEndonucleaseApiAdapter;
use Amelaye\BioPHP\Api\Interfaces\TypeIIEndonucleaseApiAdapter;
use Amelaye\BioPHP\Api\Interfaces\TypeIIsEndonucleaseApiAdapter;
use Amelaye\BioPHP\Api\Interfaces\VendorApiAdapter;
use Amelaye\BioPHP\Api\Interfaces\VendorLinkApiAdapter;

/**
 * Class RestrictionDigestManager
 * @package MinitoolsBundle\Service
 * @author Amelie DUVERNET aka Amelaye <amelieonline@gmail.com>
 */
class RestrictionDigestManager
{
    /**
     * From API : list of vendor links
     * @var array
     */
    private $vendorLinks;

    /**
     * From API : list of TypeII enzymes
     * @var array
     */
    private $type2;

    /**
     * From API : list of TypeIIs enzymes
     * @var array
     */
    private $type2s;

    /**
     * From API : list of TypeIIb enzymes
     * @var array
     */
    private $type2b;

    /**
     * From API : list of vendors enzymes
     * @var array
     */
    private $vendors;

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
        $this->vendorLinks  = $vendorLinksApi::GetVendorLinksArray($vendorLinksApi->getVendorLinks());
        $this->type2        = $typeIIEndonucleaseApi::GetTypeIIEndonucleasesArray($typeIIEndonucleaseApi->getTypeIIEndonucleases());
        $this->type2b       = $typeIIbEndonucleaseApi::GetTypeIIbEndonucleasesArray($typeIIbEndonucleaseApi->getTypeIIbEndonucleases());
        $this->type2s       = $typeIIsEndonucleaseApi::GetTypeIIsEndonucleasesArray($typeIIsEndonucleaseApi->getTypeIIsEndonucleases());
        $this->vendors      = $vendorApiAdapter::GetVendorsArray($vendorApiAdapter->getVendors());
    }

    /**
     * Get array of companies selling each endonuclease
     * @param   string  $message
     * @param   string  $enzyme
     * @return  array
     * @throws  \Exception
     */
    public function getVendors(&$message, $enzyme)
    {
        try {
            $enzyme_array = [];
            // Get array of companies selling each endonuclease
            $vendors = $this->vendors;

            $endonuclease = preg_split("/,/", $enzyme);
            if (strpos($enzyme,",") > 0) {
                $message = "All endonucleases bellow are isoschizomers";
            }

            // print vendor for each endonuclease (uses a function)
            foreach ($endonuclease as $enzyme) {
                $enzyme_array[$enzyme] = $this->showVendors($vendors[$enzyme], $enzyme);
            }
            return $enzyme_array;
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
     * @return  array
     * @throws \Exception
     */
    public function getNucleolasesInfos($bIIs, $bIIb, $bDefined)
    {
        try {
            $enzymes_array = $this->type2;

            // if TypeIIs endonucleases are requested, get them
            if (($bIIs && !$bDefined)) {
                $enzymes_array = array_merge($enzymes_array, $this->type2s);
                asort($enzymes_array);
            }
            // if TypeIIb endonucleases are requested, get them
            if (($bIIb && !$bDefined)) {
                $enzymes_array = array_merge($enzymes_array, $this->type2b);
                asort($enzymes_array);
            }
            return $enzymes_array;
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
                foreach($aEnzymes as $key => $val) {
                    if (strpos(" ,".$aEnzymes[$key][0].",",$sWre) > 0) {
                        $aNewEnzymes[$sWre] = $aEnzymes[$key];
                        return $aNewEnzymes;
                    }
                }
            }
            // remove endonucleases which do not match requeriments
            foreach($aEnzymes as $enzyme => $val) {
                if ($iRetype == 1 && $aEnzymes[$enzyme][5] != 0) {
                    continue; // if retype==1 -> only Blund ends (continue for rest)
                }
                if ($iRetype == 2 && $aEnzymes[$enzyme][5] == 0) {
                    continue; // if retype==2 -> only Overhang end (continue for rest)
                }
                if ($iMinimun > $aEnzymes[$enzyme][6]) {
                    continue; // Only endonucleases with which recognized in template a minimum of bases (continue for rest)
                }
                if ($bDefinedSq == 1) {
                    if (strpos($aEnzymes[$enzyme][2],".") > 0 || strpos($aEnzymes[$enzyme][2],"|") > 0) {
                        continue; // if defined sequence selected, no N (".") or "|" in pattern
                    }
                }
                $aNewEnzymes[$enzyme] = $aEnzymes[$enzyme];
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
                foreach($aExtractSequences as $key => $val) {
                    $sSeq = substr($val,strpos($val,"\n"));
                    $sSeq = preg_replace ("/\W|\d/", "", strtoupper($sSeq));
                    if (strlen($sSeq)>0){
                        $aSequence[$iCounter]["seq"] = $sSeq;
                        $aSequence[$iCounter]["name"] = substr($val,0,strpos($val,"\n"));
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
            $digestionMulti = [];
            $aTempData = [];

            // Two or more sequence available
            foreach($aEnzymes as $enzyme => $val) {
                if ($bIsOnlyDiff == false || $sWre != ""){
                    // Show all restriction results, when endonuclease cuts at least one sequence
                    foreach($aSequence as $number => $val2){
                        if (isset($aDigestion[$number][$enzyme]) && sizeof($aDigestion[$number][$enzyme]["cuts"]) > 0) {
                            $digestionMulti[] = $enzyme;
                        }
                    }
                } else {
                    $aTemp = [];
                    if(isset($aDigestion[0][$enzyme])) {
                        // Show restriction results when they are different
                        $aTempData = sizeof($aDigestion[0][$enzyme]["cuts"]);
                        if ($aTemp > 0){
                            $aTemp = $aDigestion[0][$enzyme]["cuts"];
                        }
                    }

                    foreach($aSequence as $number => $val2) {
                        if ($number == 0) {
                            continue;
                        }
                        if(isset($aDigestion[$number][$enzyme])) {
                            $aTempData2 = sizeof($aDigestion[$number][$enzyme]["cuts"]);
                            if ($aTempData != $aTempData2) {
                                $digestionMulti[] = $enzyme;
                                break;
                            }
                            if ($aTempData2>0){
                                $aTemp = array_diff($aTemp, $aDigestion[$number][$enzyme]["cuts"]);
                                if (sizeof($aTemp) > 0) {
                                    $digestionMulti[] = $enzyme;
                                    break;
                                }
                            }
                        }
                    }
                }
            }
            return $digestionMulti;
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

            $aEnzymeVendors["company"] = ["name" => $sCompany, "url" => "http://rebase.neb.com/rebase/enz/$sEnzyme.html"];
            foreach($this->vendorLinks as $key => $data) {
                if(strpos($sCompany, $key) !== false) {
                    $aEnzymeVendors["links"][] = $data;
                }
            }
            return $aEnzymeVendors;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }
}