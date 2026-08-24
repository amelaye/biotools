<?php

// author    Joseba Bikandi
// license   GNU GPL v2
// source code available at  biophp.org


// #######################################################################
//                           GET DATA
// #######################################################################
// GETDATA
   // GET PRIMER SEQUENCE
        $primer="";
        $primer=strtoupper($_GET["primer"]);
        // REMOVE FROM PRIMER NON CODING PARTS
        $primer=preg_replace("/\W|[^ATGCYRWSKMDVHBN]|\d/","",$primer);
        // SHOW ERROR WHEN LENGTH OF PRIMER IS NOT IN THE CORRECT RANGE
        if ($primer!="" and (strlen($primer)<6 or strlen($primer)>50)){die("Error:<br>Length of primer must be 6-50 bp.");}

   // GET ADDITIONAL DATA
        $primer_concentration=200;
        if($_GET["cp"]){$primer_concentration=$_GET["cp"];}
        $salt_concentration=50;
        if($_GET["cs"]){$salt_concentration=$_GET["cs"];}
        $mg_concentration=0;
        if($_GET["cmg"]){$mg_concentration=$_GET["cmg"];}

// #######################################################################
//                          COMPUTE WHEN PRIMER IS SUBMITTED
// #######################################################################
$basic_primer_info="";
$tm_basic="";
$tm_Base_Stacking="";

if($primer!=""){
  // BASIC PRIMER INFORMATION
   //  in case a primer is submitted, they are shown length, C+G % and Molecular weigth
        // compute length
        $length=strlen($primer);
        // compute C+G % (it is rounded to one decimal)
        $cg=round(100*CountCG($primer)/$length,1);
        // compute Molecular weigth (uses and function)
        $Mol_wt=Mol_wt($primer);

        //basic primer info length, C+G % and Molecular weigth
        $basic_primer_info ="<table width=100%><tr><td bgcolor=DDDDFF><pre>";
        $basic_primer_info.="LENGTH                   ".$length."\n";          // length
        $basic_primer_info.="C+G%                     ".$cg."\n";              // C+G content
        $basic_primer_info.=$Mol_wt;                                           // Molecular weigth
        $basic_primer_info.="</pre></td></tr></table>\n";
  // COMPUTE TM WHEN REQUESTED
        // BASIC TM
        if($_GET["basic"]==1){
                $tm_basic="<tr><td>&nbsp;</td><td  bgcolor=DDDDFF><pre>";
                if (strlen($primer)!=CountATCG($primer)){
                        // when degenerated nucleotides are included within primer sequence
                        //   minimum and maximum tm values are computed
                        $tm_basic.="Minimun        <font color=880000><b>".Tm_min($primer)." &deg;C</b></font>\n";
                        $tm_basic.="Maximum        <font color=880000><b>".Tm_max($primer)." &deg;C</b></font>";
                }else{
                        //  when degenerated nucleotides are not present, only one tm value is computed
                        $tm_basic.="Tm:                 <font color=880000><b>".Tm_min($primer)." &deg;C</b></font>";
                }
                $tm_basic.="</pre></td>";
        }
        // BASE STACKING TM
        if($_GET["NearestNeighbor"]==1){
                $tm_Base_Stacking ="<tr><td>&nbsp;</td><td  bgcolor=DDDDFF><pre>";
                $tm_Base_Stacking.=tm_Base_Stacking($primer,$primer_concentration,$salt_concentration,$mg_concentration);    //
                $tm_Base_Stacking.="</pre></td>";
        }
}
// #######################################################################
//                  GENERATE RESPONSE (4output)
// #######################################################################

// PAGE TOP
$output ="<html><head><title>Melting Temperature (Tm) Calculation</title></head><body bgcolor=FFFFFF>";
$output.="<center><table border=0><tr><td>";
$output.="<center><h2>Melting Temperature (Tm) Calculation</h2></center>";

// THE FORM
//   INCLUDES Tm INFORMATION WHEN REQUESTED
   $output.="<form method=get action=\"".$_SERVER["PHP_SELF"]."\">";
   $output.="<b>Primer </b>(6-50 bases):<br>";
   $output.="<input type=text name=primer value=\"".$primer."\" size=40>";     //  primer sequence will be included in the form in case it was submitted and procesed
   $output.="<input type=submit value=\"Compute Tm\">";
   $output.=$basic_primer_info;                                                //  output basic primer info in case it is available
   $output.="<table width=100%>";
   $output.="<tr>";
   $output.="<td valign=top>";
   $output.="<input type=checkbox name=basic value=1";if ($_GET["basic"]==1){$output.=" checked";}$output.=">";
   $output.="</td>";
   $output.="<td valign=top>";
   $output.="<a href=?formula=basic>Basic Tm</a>";
   $output.="<br><font size=-1> Degenerated nucleotides are allowed</a></font>";
   $output.="</td>";
   $output.=$tm_basic;                                                        // BASIC TM is computed and shown when requested
   $output.="</tr>";
   $output.="<tr>";
   $output.="<td valign=top>";
   $output.="<input type=checkbox name=NearestNeighbor value=1";if ($_GET["NearestNeighbor"]==1){$output.=" checked";}$output.=">";
   $output.="</td>";
   $output.="<td valign=top>";
   $output.="<a href=?formula=BaseStaking>Base-Stacking Tm</a>";
   $output.="<br><font size=-1> Degenerated nucleotides are NOT allowed</a></font>\n";
   $output.="<table>\n";
   $output.="<tr><td>Primer concentration:</td><td><input type=text name=cp value=".$primer_concentration." size=4> nM</td></tr>\n";
   $output.="<tr><td>Salt concentration:</td><td><input type=text name=cs value=".$salt_concentration." size=4> mM</td></tr>\n";
   $output.="<tr><td>Mg<font size=-2><sup>2+</sup></font> concentration:</td><td><input type=text name=cmg value=".$mg_concentration." size=4> mM</td></tr>\n";
   $output.="</table>\n";
   $output.=$tm_Base_Stacking;                                            // BASE STACKING TM is computed and shown when requested
   $output.="</td>";
   $output.="</tr>";
   $output.="</table>";
   $output.="</form>";
   $output.="<hr>";
// END FORM

// LINK TO SCRIPT
   $output.="<font size=-1>\nSource code is freely downloable at <a href=http://www.biophp.org/minitools/melting_temperature/>biophp.org</a>\n</font>\n";
   $output.="</td></tr></table>\n";

// INFO ABOUT TM COMPUTIG METHODS WHEN REQUESTED
        // SHOW BASIC TM INFO
        if ($_GET["formula"]=="basic"){
           $output.=BasicTmInfo();
        }
        // SHOW BASE-STACKING TM
        if ($_GET["formula"]=="BaseStaking"){
           $output.=BaseStackingTmInfo();
        }
// ENDING
$output.="</center>\n</body></html>\n";


// OUTPUT PAGE CONTENT ($output)
        // OPTION 1 (sends info as normal text)
                // print $output;
        // OPTION 2 (sends info as gz compressed text to reduce bandwidth usage)
                header("Content-Encoding: gzip");
                echo gzencode($output,9);
                die();


// #######################################################################
//                           FUNTIONS
// #######################################################################
function Tm_min($primer){
   $primer2=primer_min($primer);
   $n_AT=substr_count($primer2,"A");
   $n_CG=substr_count($primer2,"G");
   $primer_len=strlen($primer2);
   return basic_tm($n_AT,$n_CG,$primer_len);
}
function Tm_max($primer){
   $primer2=primer_max($primer);
   $n_AT=substr_count($primer2,"A");
   $n_CG=substr_count($primer2,"G");
   $primer_len=strlen($primer2);
   return basic_tm($n_AT,$n_CG,$primer_len);
}
function basic_tm($n_AT,$n_CG,$primer_len){
        if ($primer_len < 14) {
                return round(2 * ($n_AT) + 4 * ($n_CG));
        }else{
                return round(64.9 + 41*(($n_CG-16.4)/$primer_len),1);
        }
}

function tm_Base_Stacking($c,$conc_primer,$conc_salt,$conc_mg){

   if (CountATCG($c)!= strlen($c)){return "Non computed. The oligonucleotide contains degenerated nucleotides.";}

   $h=$s=0;
   // from table at http://www.ncbi.nlm.nih.gov/pmc/articles/PMC19045/table/T2/ (SantaLucia, 1998)
   // enthalpy values
   $array_h["AA"]= -7.9;
   $array_h["AC"]= -8.4;
   $array_h["AG"]= -7.8;
   $array_h["AT"]= -7.2;
   $array_h["CA"]= -8.5;
   $array_h["CC"]= -8.0;
   $array_h["CG"]=-10.6;
   $array_h["CT"]= -7.8;
   $array_h["GA"]= -8.2;
   $array_h["GC"]= -9.8;
   $array_h["GG"]= -8.0;
   $array_h["GT"]= -8.4;
   $array_h["TA"]= -7.2;
   $array_h["TC"]= -8.2;
   $array_h["TG"]= -8.5;
   $array_h["TT"]= -7.9;
   // entropy values
   $array_s["AA"]=-22.2;
   $array_s["AC"]=-22.4;
   $array_s["AG"]=-21.0;
   $array_s["AT"]=-20.4;
   $array_s["CA"]=-22.7;
   $array_s["CC"]=-19.9;
   $array_s["CG"]=-27.2;
   $array_s["CT"]=-21.0;
   $array_s["GA"]=-22.2;
   $array_s["GC"]=-24.4;
   $array_s["GG"]=-19.9;
   $array_s["GT"]=-22.4;
   $array_s["TA"]=-21.3;
   $array_s["TC"]=-22.2;
   $array_s["TG"]=-22.7;
   $array_s["TT"]=-22.2;

   // effect on entropy by salt correction; von Ahsen et al 1999
      // Increase of stability due to presence of Mg;
      $salt_effect= ($conc_salt/1000)+(($conc_mg/1000) * 140);
      // effect on entropy
      $s+=0.368 * (strlen($c)-1)* log($salt_effect);

   // terminal corrections. Santalucia 1998
      $firstnucleotide=substr($c,0,1);
      if ($firstnucleotide=="G" or $firstnucleotide=="C"){$h+=0.1; $s+=-2.8;}
      if ($firstnucleotide=="A" or $firstnucleotide=="T"){$h+=2.3; $s+=4.1;}

      $lastnucleotide=substr($c,strlen($c)-1,1);
      if ($lastnucleotide=="G" or $lastnucleotide=="C"){$h+=0.1; $s+=-2.8;}
      if ($lastnucleotide=="A" or $lastnucleotide=="T"){$h+=2.3; $s+=4.1;}

   // compute new H and s based on sequence. Santalucia 1998
   for($i=0; $i<strlen($c)-1; $i++){
      $subc=substr($c,$i,2);
      $h+=$array_h[$subc];
      $s+=$array_s[$subc];
   }
   $tm=((1000*$h)/($s+(1.987*log($conc_primer/2000000000))))-273.15;

   $result="Tm:                 <font color=880000><b>".round($tm,1)." &deg;C</b></font>";
   $result.="\n<font color=008800>  Enthalpy: ".round($h,2)."\n  Entropy:  ".round($s,2)."</font>";
   return $result;
}

function Mol_wt($primer){
   $upper_mwt=molwt($primer,"DNA","upperlimit");
   $lower_mwt=molwt($primer,"DNA","lowerlimit");
   if ($upper_mwt==$lower_mwt){
        return "Molecular weight:        $upper_mwt";
   }else{
        return "Upper Molecular weight:  $upper_mwt\nLower Molecular weight:  $lower_mwt";
   }
}
function CountCG($c){
   $cg=substr_count($c,"G")+substr_count($c,"C");
   return $cg;
}
function CountATCG($c){
   $cg=substr_count($c,"A")+substr_count($c,"T")+substr_count($c,"G")+substr_count($c,"C");
   return $cg;
}
function primer_min($primer){
   $primer=preg_replace("/A|T|Y|R|W|K|M|D|V|H|B|N/","A",$primer);
   $primer=preg_replace("/C|G|S/","G",$primer);
   return $primer;
}

function primer_max($primer){
   $primer=preg_replace("/A|T|W/","A",$primer);
   $primer=preg_replace("/C|G|Y|R|S|K|M|D|V|H|B|N/","G",$primer);
   return $primer;
}
function molwt($sequence,$moltype,$limit){
   // the following are single strand molecular weights / base
   $rna_A_wt = 329.245;
   $rna_C_wt = 305.215;
   $rna_G_wt = 345.245;
   $rna_U_wt = 306.195;

   $dna_A_wt = 313.245;
   $dna_C_wt = 289.215;
   $dna_G_wt = 329.245;
   $dna_T_wt = 304.225;

   $water = 18.015;

   $dna_wts = array('A' => array($dna_A_wt, $dna_A_wt),  // Adenine
      'C' => array($dna_C_wt, $dna_C_wt),  // Cytosine
      'G' => array($dna_G_wt, $dna_G_wt),  // Guanine
      'T' => array($dna_T_wt, $dna_T_wt),  // Thymine
      'M' => array($dna_C_wt, $dna_A_wt),  // A or C
      'R' => array($dna_A_wt, $dna_G_wt),  // A or G
      'W' => array($dna_T_wt, $dna_A_wt),  // A or T
      'S' => array($dna_C_wt, $dna_G_wt),  // C or G
      'Y' => array($dna_C_wt, $dna_T_wt),  // C or T
      'K' => array($dna_T_wt, $dna_G_wt),  // G or T
      'V' => array($dna_C_wt, $dna_G_wt),  // A or C or G
      'H' => array($dna_C_wt, $dna_A_wt),  // A or C or T
      'D' => array($dna_T_wt, $dna_G_wt),  // A or G or T
      'B' => array($dna_C_wt, $dna_G_wt),  // C or G or T
      'X' => array($dna_C_wt, $dna_G_wt),  // G, A, T or C
      'N' => array($dna_C_wt, $dna_G_wt)   // G, A, T or C
   );

   $rna_wts = array('A' => array($rna_A_wt, $rna_A_wt),  // Adenine
      'C' => array($rna_C_wt, $rna_C_wt),  // Cytosine
      'G' => array($rna_G_wt, $rna_G_wt),  // Guanine
      'U' => array($rna_U_wt, $rna_U_wt),  // Uracil
      'M' => array($rna_C_wt, $rna_A_wt),  // A or C
      'R' => array($rna_A_wt, $rna_G_wt),  // A or G
      'W' => array($rna_U_wt, $rna_A_wt),  // A or U
      'S' => array($rna_C_wt, $rna_G_wt),  // C or G
      'Y' => array($rna_C_wt, $rna_U_wt),  // C or U
      'K' => array($rna_U_wt, $rna_G_wt),  // G or U
      'V' => array($rna_C_wt, $rna_G_wt),  // A or C or G
      'H' => array($rna_C_wt, $rna_A_wt),  // A or C or U
      'D' => array($rna_U_wt, $rna_G_wt),  // A or G or U
      'B' => array($rna_C_wt, $rna_G_wt),  // C or G or U
      'X' => array($rna_C_wt, $rna_G_wt),  // G, A, U or C
      'N' => array($rna_C_wt, $rna_G_wt)   // G, A, U or C
   );

   $all_na_wts = array('DNA' => $dna_wts, 'RNA' => $rna_wts);
   //print_r($all_na_wts);
   $na_wts = $all_na_wts[$moltype];

   $mwt = 0;
   $NA_len = strlen($sequence);

   if($limit=="lowerlimit"){$wlimit=1;}
   if($limit=="upperlimit"){$wlimit=0;}

   for ($i = 0; $i < $NA_len; $i++) {
            $NA_base = substr($sequence, $i, 1);
            $mwt += $na_wts[$NA_base][$wlimit];
   }
   $mwt += $water;

   return $mwt;
 }

function BasicTmInfo(){
   // info bellow will be shows when requested
   $info ="<table width=700><tr><td>\n";
   $info.="<hr size=3 color=blue>\n";
   $info.="<h2>Basic Melting Temperature (Tm) Calculations</h2>\n";
   $info.="Two standard approximation calculations are used.\n";
   $info.="<ul>\n";
   $info.="<li>For sequences less than 14 nucleotides\n";
   $info.="the formula is:\n";
   $info.="<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tm= (wA+xT) * 2 + (yG+zC) * 4\n";
   $info.="<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;where w,x,y,z are the number of the bases A,T,G,C in the sequence, respectively.\n";
   $info.="<li>For sequences longer than 13 nucleotides, the equation used is\n";
   $info.="<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tm= 64.9 +41*(yG+zC-16.4)/(wA+xT+yG+zC)\n";
   $info.="</ul>\n";
   $info.="<p>When degenerated nucleotides are included in the primer sequence (Y,R,W,S,K,M,D,V,H,B or N), those nucleotides will be internally substituted prior to minimum and maximum Tm calculation.\n";
   $info.="<p><pre>    Example:\n";
   $info.="    Primer sequence:                       CTCT<b>RY</b>CT<b>WS</b>CTCTCT\n";
   $info.="    Sequence for minimum Tm calculation:   CTCT<b>AT</b>CT<b>AG</b>CTCTCT\n";
   $info.="    Sequence for maximum Tm calculation:   CTCT<b>GC</b>CT<b>AG</b>CTCTCT</pre>\n";
   $info.="<p><b>ASSUMPTIONS:</b>\n";
   $info.="<p>Equations above assume that the annealing occurs under the standard conditions of 50 nM primer, 50 mM Na<sup><font size=-2>+</font></sup>, and pH 7.0.";
   $info.="<hr size=3 color=blue>\n";
   $info.="</td></tr></table>\n";
   return $info;
}

function BaseStackingTmInfo(){
   $info="<table width=700><tr><td>\n";
   $info.="<hr size=3 color=blue>\n";
   $info.="<h2>Base-Stacking Melting Temperature (Tm) Calculations</h2>\n";
   $info.="This aproximation uses Thermodynamical concepts to compute T<sub>m</sub>.\n";
   $info.="<p>The following references were used to develop the script:\n";
   $info.="<ul>\n";
   $info.="<li>SantaLucia J. A unified view of polymer, dumbbell, and oligonucleotide DNA nearest-neighbor thermodynamics. Proc Natl Acad Sci U S A. 1998 Feb 17;95(4):1460-5.";
   $info.="<a href=http://www.ncbi.nlm.nih.gov/sites/entrez?Db=pubmed&Cmd=ShowDetailView&TermToSearch=9465037>NCBI</a>\n";
   $info.="<li>von Ahsen N, Oellerich M, Armstrong VW, Sch&uuml;tz E. Application of a thermodynamic nearest-neighbor model to estimate nucleic acid stability ";
   $info.="and optimize probe design: prediction of melting points of multiple mutations of apolipoprotein B-3500 and factor V with a hybridization probe genotyping ";
   $info.="assay on the LightCycler. Clin Chem. 1999 Dec;45(12):2094-101.";
   $info.="<a href=http://www.ncbi.nlm.nih.gov/sites/entrez?Db=pubmed&Cmd=ShowDetailView&TermToSearch=10585340>NCBI</a>\n";
   $info.="</ul>\n";
   $info.="<hr size=3 color=blue>\n";
   $info.="</td></tr></table>\n";
   return $info;
}

?>
