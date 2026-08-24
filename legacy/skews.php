<?php


// THIS SCRIPTS WILL GENERATE A IMAGE.
// the image will be saved to dir defined bellow
$dir="path";
// avoid reporting errors
error_reporting(0);
// Set maximum amount of seconds the script will run to 1,000 seconds. In many cases,
//   the default will be 30 seconds, which may not be enought for this script
set_time_limit (1000);


// COMPRESION OF OUTPUT CONTENT
// This page will use the function "OutputContent" bellow to output info
//  - All contents will be send to user by using this method
//  - The information is compressed before sending it
//  - information is trasferrer as compressed info, so
//    amount of information transmitted is about 1/4 comparing
//    to normal html content (non compressed)
function OutputContent($content){
        //header("Content-Encoding: gzip");
        //$content = gzencode($content, 9);
        echo $content;
        die();
}

// ####################################################
// START
// TOP OF PAGE
$content="
<!--
Title: GC, AT, KETO and Oligo-Skews
Author: Joseba Bikandi
License: GNU GLP v2
-->

<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\">
<html>
<head>
  <title>GC, AT, KETO and Oligo-Skews</title>
</head>
<body bgcolor=FFFFFF>
<center>
";

// INFO WHEN REQUESTED
if($_SERVER["QUERY_STRING"]=="info"){
        $content.=print_info ();
        OutputContent($content); // and dies
}
// FORM AS DEFAULT
if(!$_POST){
        $content.=print_form();
        OutputContent($content); //and dies
}

// IF HERE, INFO WAS SUBMITTED

// $secondsSTART  are seconds since the Unix Epoch (January 1 1970 00:00:00 GMT)
// will be used to display seconds required to compute the skew
$SecondsSTART=date("U");

// GET DATA AND CHECK WHETHER IS CORRECT
    // size of window
        // $window = window size from select
        $window=$_POST["window"];
        // $window = custom window size from user
        $window2=$_POST["window2"];
        // custom window size id not submited, use the $window value
        if ($window2!=""){$window=$window2;}
        // check whether $window is in the correct range
        if ($window<100 or $window>100000 or $window==0){die($content."Window size is not within allowed range: 100 to 100000 bases");}
    // type of skews to create; if value is 1, it will be computed
        $GC=$_POST["GC"];       // for GC-skew
        $AT=$_POST["AT"];       // for AT-skew
        $KETO=$_POST["KETO"];   // for KETO-skew
        $GmC=$_POST["GmC"];     // for G+C skew
    // get name of sequence
        $name=$_POST["name"];
        // if name is not specified, name is "sequence"
        if($name==""){$name="sequence";}
    // get sequence
        $sequence=strtoupper($_POST["seq"]);
        // remove non-coding
        $sequence=preg_replace("/\W|\d/","",$sequence);
        // is sequence is over 10 MB long, display error
        if(strlen($sequence)>10000000){die($content."Error: maximum lengt of sequence allowed is 10,000,000 bp. ");}
    // if subsequence of input sequence must be used
        // get start and end
        $from=$_POST["from"];
        $to=$_POST["to"];
        // remove useless part of sequence
        if ($from or $to){
                if(str_is_int($to)==1){$sequence=substr($sequence,0,$to);}
                if(str_is_int($from)==1){$sequence=substr($sequence,$from);}
        }
    // if sequence does not exists, display error
        if ($sequence==""){die($content."Error: no sequence selected for computing");}
    // if sequence is to sort to work with, display error
        if(strlen($sequence)<($window+1400)){die($content."Error: sequence is very small for the selected window size.");}

// COMPUTE OLIGO-SKEWS REALTED INFO WHEN REQUESTED
// when oligo-skew is requested, computing time will be long; let know the user and compute data for oligo-skew
if ($_POST["oskew"]==1){
   if(str_is_int($_POST["oligo_len"])==1){
        if ($_POST["oligo_len"]==4 and $_POST["strands"]==2){
                // This is the default option of oligo-skews, so a optimized version was create to reduce computing at least to 1/2
                print "Computing...(will be aborted after 15 minutes; oligo-skews require intense computing). Please wait. ";flush();
                // in next line a function will compute an array with distances
                $oligo_skew_array=Oligo_skew_array_calculation4bothstrands($sequence,$window);
        }else{
                print "Computing...(will be aborted after 15 minutes; oligo-skews require intense computing). Please wait. ";flush();
                // in next line a function will compute an array with distances
                $oligo_skew_array=Oligo_skew_array_calculation($sequence,$window,$_POST["oligo_len"],$_POST["strands"]);
        }
   }
}

// CREATE IMAGE CONTAINING SKEWS
 $data_table=Create_image($sequence,$window,$GC,$AT,$KETO,$GmC,$oligo_skew_array,$_POST["oligo_len"], $from, $to, $name,$dir);

// CALCULATE SECONDS USED TO COMPUTE THE PAGE (THE SKEWS)
   $secondsEND=date("U");
   $seconds=$secondsEND-$SecondsSTART;

// THE CONTENT TO BE SEND TO USER (INCLUDES THE IMAGE)
$content.="
<h1>Skew from custom sequences</h1>
<p>
<img src=".$dir."/image.png?".date("U")." border=0 width=850 height=450 ismap=ismap align=top>
<br>Computed in ".$seconds."seconds.
</center>
</body>
</html>
";

// SEND CONTENT AND END
OutputContent($content);

// #######################################################################################
// ##############################       FUNCTIONS     ####################################
// #######################################################################################
// #########################################################################
function print_form(){
   // provides the form with all the options
   $theform ="<table cellpadding=5>
   <tr><td align=center>

   <h1>Skews from custom sequences</h1>

   </td></tr><td bgcolor=DDDDFF>

   <form method=post>
   Name of sequence: <input type=text name=name size=20>
   <br>Copy sequence in the textarea below (up to 5,000,000 bp):<br>
   <textarea cols=80 rows=3 name=seq></textarea><br>

   <p> Window size
   <select name=window><option value=\"\">Choose size
   <option value=5000>5000
   <option value=10000>10000
   <option value=50000>50000
   <option value=100000>100000
   </select> or <input type=text name=window2 size=7> bases(100-50000)

   <p><input value=\"Show new graph\" type=submit>

   <hr> Show <input type=checkbox name=GmC value=1 checked> G+C %
   <br> Show <input type=checkbox name=GC value=1> GC-skew
   <br> Show <input type=checkbox name=AT value=1> AT-skew
   <br> Show <input type=checkbox name=KETO value=1> KETO-skew
   <br>Show <input type=checkbox name=oskew value=1> oligo-skew
   for
   <select name=oligo_len>
   <option value=2>dinucleotides
   <option value=3>trinucleotides
   <option value=4 selected>tetranucleotides
   <option value=5>pentanucleotides
   <option value=6>hexanucleotides
   </select>
   in <select name=strands><option value=1>one strand<option value=2  selected>both strands</select>

   <hr>
   Show subsequence: from <input name=from type=text size=5> to <input name=to type=text size=5>

   </form>

   </td></tr><tr><td align=right>

   <a href=?info>Info</a>

   </td></tr><tr><td>
   <font size=-1>
   This script has been extracted from online tools at <a href=http://insilico.ehu.eus/>insilico.ehu.eus/</a>.
   <br>The type of skews describer are available for all sequenced bacterial genomes <a href=http://insilico.ehu.eus/describe/>here</a>
   <p>Source code is available at <a href=http://biophp.org/minitools/skews/ target=new>BioPHP.org</a>
   </font>

   </td></tr></table>
   </center>
   </body>
   </html>";
   return $theform;
}
function print_info(){
   // the response is  information... nothing more
   $info="
   <h1>Skews from custom sequences</h1>

   <table width=900 cellpadding=10 cellspacing=2><tr><td style=\"vertical-align: top;\">

   To compute skews, the input sequence is scanned with a sliding window, and the requested parameter is calculate
   for each window. The data is shown in a graph.

   <P><big><big><b>G+C %</b></big></big>
   <br>The porcentaje of G+C is computed

   <p><big><big><b>GC-skew</b></big></big>
   <br>For each window, (G-C)/(G+C) is computed, where G and C are the number of ocurrences for each nucleotide

   <p><big><big><b>AT-skew</b></big></big>
   <br>For each window, (A-T)/(A+T) is computed, where A and T are the number of ocurrences for each nucleotide.

   <p><big><big><b>KETO-skew</b></big></big>
   <br>For each window, (G+C-A-T)/(G+C+A+T) is computed, where A, C, G and T are the number of ocurrences for each nucleotide.

   <p><big><big><b style=\"font-weight: bold;\">Oligo-skew</b></big></big>
   <br>For each window, frecuencies for all nucleotides with selected length is computed.
   Those frecuencies are compared to frecuencies obtained from the complete sequence.
   When comparing both frecuencies, Pearson distance is computed, as described
   <a href=http://insilico.ehu.eus/gs/01_Frequencies_and_distances.pdf target=new>here</a>.&nbsp; </p>

   </td></tr></table>
   </center>
   </body>
   </html>
   ";
   return $info;
}
// #######################################################################################
function Oligo_skew_array_calculation($sequence,$window,$oskew,$strands){
        // will  compare oligonucleotide frequencies in all the sequence
        //    with frequencies in each window, and will return an array
        //    with Pearson distances.

        // search for oligos in the completE sequence
        $oligofreqsA=search_oligos($sequence,$oskew);
        $seq_len= strlen($sequence);
        $period=ceil($seq_len/1400);
        if($period<10){$period=10;}
        if ($strands==2){
           // if both strands are used for computing oligonucleotide frequencies
           $sequence2=Comp($sequence);
           $i=0;
           while ($i<$seq_len-$window+1){
                $subsequence=substr($sequence,$i,$window)." ".strrev(substr($sequence2,$i,$window));
                // compute oligonucleotide frequencies in window
                $oligofreqsB=search_oligos($subsequence,$oskew);
                // compute distance between complete sequence and window
                $data[$i]=distance($oligofreqsA,$oligofreqsB);
                $i+=$period;
            }
        }else{
           // if only one strand is used for computing oligonucleotide frequencies
           $i=0;
            while ($i<$seq_len-$window+1){
                $subsequence=substr($sequence,$i,$window);
                // compute oligonucleotide frequencies in window
                $oligofreqsB=search_oligos($subsequence,$oskew);
                // compute distance between complete sequence and window
                $data[$i]=Pearson_distance($oligofreqsA,$oligofreqsB);
                $i+=$period;
            }
        }
        // return the array with distances
        return $data;
}
function Oligo_skew_array_calculation4bothstrands($sequence,$window){
        // OPTIMIZED version of function Oligo_skew_array_calculation
        //      for TETRAnucleotides and BOTH strands
        //
        // will  compare tetranucleotide frequencies in all the sequence
        //    with frequencies in each window, and will return an array
        //    with Pearson distances.
        //
        // works with functions:
        //       search_oligos_4bothstrands
        //      Pearson_distance_136
        //
        // searches only one DNA strands, but provides tetranucleotide
        //    frequencies from both strands

        // search oligos frequencies in the complet sequence
        $tetraAll=search_oligos_4bothstrands($sequence);

        $seq_len= strlen($sequence);
        $period=ceil($seq_len/1400);
        if($period<10){$period=10;}

        //  both strands are used for computing oligonucleotide frequencies
        $i=0;
        while ($i<$seq_len-$window+1){
                $subsequence=substr($sequence,$i,$window)." ".strrev(substr($sequence2,$i,$window));
                // compute oligonucleotide frequencies in window
                $tetraB=search_oligos_4bothstrands($subsequence);

                // compute distance between complete sequence and window
                $data[$i]=Pearson_distance_136($tetraAll,$tetraB);

                $i+=$period;
        }

        // return the array with distances
        return $data;
}
// #######################################################################################
function search_oligos($seq,$len_oligos){
        // search for frequencies of oligonucleotides of len $len_oligos,
        // and returns results in an array
        $i=0;
        $len=strlen($seq)-$len_oligos+1;
        while ($i<$len){
            $subseq=substr($seq,$i,$len_oligos);
            $p[$subseq]++;               // this array will contain the oligonucleotides ($subseq) as keys
                                         //   but not all of them are necessarely oligonucleotides
                                         // p.e.: some keys may contain "N" in case it is present within sequence
            $oligos_internos[$seq]++;
            $i++;
        }

        $base=array("A","C","G","T");

        //for oligos 2 bases long
        if ($len_oligos==2){
                foreach($base as $key_a => $val_a){
                foreach($base_b as $key_b => $val_b){
                  if ($p[$val_a.$val_b]){
                        $oligos[] = $p[$val_a.$val_b];
                  }else{
                        $oligos[] = 0;
                  }
                }}
        }
        //for oligos 3 bases long
        if ($len_oligos==3){
                foreach($base as $key_a => $val_a){
                foreach($base as $key_b => $val_b){
                foreach($base as $key_c => $val_c){
                if ($p[$val_a.$val_b.$val_c]){
                        $oligos[] = $p[$val_a.$val_b.$val_c];
                }else{
                        $oligos[] = 0;
                }
                }}}
        }
        //for oligos 4 bases long
        if ($len_oligos==4){
                foreach($base as $key_a => $val_a){
                foreach($base as $key_b => $val_b){
                foreach($base as $key_c => $val_c){
                foreach($base as $key_d => $val_d){
                    if ($p[$val_a.$val_b.$val_c.$val_d]){
                        $oligos[] = $p[$val_a.$val_b.$val_c.$val_d];
                    }else{
                        $oligos[] = 0;
                    }
                }}}}
        }
        //for oligos 5 bases long
        if ($len_oligos==5){
                foreach($base as $key_a => $val_a){
                foreach($base as $key_b => $val_b){
                foreach($base as $key_c => $val_c){
                foreach($base as $key_d => $val_d){
                foreach($base as $key_e => $val_e){
                    if ($p[$val_a.$val_b.$val_c.$val_d.$val_e]){
                        $oligos[] = $p[$val_a.$val_b.$val_c.$val_d.$val_e];
                    }else{
                        $oligos[] = 0;
                    }
                }}}}}
        }
        //for oligos 6 bases long
        if ($len_oligos==6){
                foreach($base as $key_a => $val_a){
                foreach($base as $key_b => $val_b){
                foreach($base as $key_c => $val_c){
                foreach($base as $key_d => $val_d){
                foreach($base as $key_e => $val_e){
                foreach($base as $key_f => $val_f){
                    if ($p[$val_a.$val_b.$val_c.$val_d.$val_e.$val_f]){
                        $oligos[] = $p[$val_a.$val_b.$val_c.$val_d.$val_e.$val_f];
                    }else{
                        $oligos[] = 0;
                    }
                }}}}}}
        }
        return $oligos;

}
function search_oligos_4bothstrands($seq){
        // optimized method to compute TETRAnucleotides in BOTH strands
        // computes frequencies in 120+16 format and returns results in an array
        //    - values 1-120 are frequencies of especific oligos and its complementary oligo
        //      (p.e.: occurrences in both strands of AAAA; the ocurrences of TTTT in both strands is the same)
        //    - values 121 to 136 are of especific oligos (the complement oligo is the same oligo)
        //      (p.e.: occurrences in both strands of AATT; the complementary oligo is AATT in this case)
        //    - values 137 and 138 of the array includes sum(x) and sum(x^2) as precomputed values
        // distances will be used in function Pearson_distance_136
        $i=0;
        $len=strlen($seq)-4+1;
        while ($i<$len){
            $tetra=substr($seq,$i,4);
            $p[$tetra]++;              // this array will contain the tetranucleotides ($tetra) as keys
                                       //   but not all of them are necessarely tetranucleotides
                                       // p.e.: some keys may contain "N" in case it is present within sequence
            $i++;
        }
        $sum_x=0;
        $sum_x2=0;
        $oligos[]=$p["AAAA"]+$p["TTTT"];
        $oligos[]=$p["AAAC"]+$p["GTTT"];
        $oligos[]=$p["AAAG"]+$p["CTTT"];
        $oligos[]=$p["AAAT"]+$p["ATTT"];
        $oligos[]=$p["AACA"]+$p["TGTT"];
        $oligos[]=$p["AACC"]+$p["GGTT"];
        $oligos[]=$p["AACG"]+$p["CGTT"];
        $oligos[]=$p["AACT"]+$p["AGTT"];
        $oligos[]=$p["AAGA"]+$p["TCTT"];
        $oligos[]=$p["AAGC"]+$p["GCTT"];
        $oligos[]=$p["AAGG"]+$p["CCTT"];
        $oligos[]=$p["AAGT"]+$p["ACTT"];
        $oligos[]=$p["AATA"]+$p["TATT"];
        $oligos[]=$p["AATC"]+$p["GATT"];
        $oligos[]=$p["AATG"]+$p["CATT"];
        $oligos[]=$p["ACAA"]+$p["TTGT"];
        $oligos[]=$p["ACAC"]+$p["GTGT"];
        $oligos[]=$p["ACAG"]+$p["CTGT"];
        $oligos[]=$p["ACAT"]+$p["ATGT"];
        $oligos[]=$p["ACCA"]+$p["TGGT"];
        $oligos[]=$p["ACCC"]+$p["GGGT"];
        $oligos[]=$p["ACCG"]+$p["CGGT"];
        $oligos[]=$p["ACCT"]+$p["AGGT"];
        $oligos[]=$p["ACGA"]+$p["TCGT"];
        $oligos[]=$p["ACGC"]+$p["GCGT"];
        $oligos[]=$p["ACGG"]+$p["CCGT"];
        $oligos[]=$p["ACTA"]+$p["TAGT"];
        $oligos[]=$p["ACTC"]+$p["GAGT"];
        $oligos[]=$p["ACTG"]+$p["CAGT"];
        $oligos[]=$p["AGAA"]+$p["TTCT"];
        $oligos[]=$p["AGAC"]+$p["GTCT"];
        $oligos[]=$p["AGAG"]+$p["CTCT"];
        $oligos[]=$p["AGAT"]+$p["ATCT"];
        $oligos[]=$p["AGCA"]+$p["TGCT"];
        $oligos[]=$p["AGCC"]+$p["GGCT"];
        $oligos[]=$p["AGCG"]+$p["CGCT"];
        $oligos[]=$p["AGGA"]+$p["TCCT"];
        $oligos[]=$p["AGGC"]+$p["GCCT"];
        $oligos[]=$p["AGGG"]+$p["CCCT"];
        $oligos[]=$p["AGTA"]+$p["TACT"];
        $oligos[]=$p["AGTC"]+$p["GACT"];
        $oligos[]=$p["AGTG"]+$p["CACT"];
        $oligos[]=$p["ATAA"]+$p["TTAT"];
        $oligos[]=$p["ATAC"]+$p["GTAT"];
        $oligos[]=$p["ATAG"]+$p["CTAT"];
        $oligos[]=$p["ATCA"]+$p["TGAT"];
        $oligos[]=$p["ATCC"]+$p["GGAT"];
        $oligos[]=$p["ATCG"]+$p["CGAT"];
        $oligos[]=$p["ATGA"]+$p["TCAT"];
        $oligos[]=$p["ATGC"]+$p["GCAT"];
        $oligos[]=$p["ATGG"]+$p["CCAT"];
        $oligos[]=$p["ATTA"]+$p["TAAT"];
        $oligos[]=$p["ATTC"]+$p["GAAT"];
        $oligos[]=$p["ATTG"]+$p["CAAT"];
        $oligos[]=$p["CAAA"]+$p["TTTG"];
        $oligos[]=$p["CAAC"]+$p["GTTG"];
        $oligos[]=$p["CAAG"]+$p["CTTG"];
        $oligos[]=$p["CACA"]+$p["TGTG"];
        $oligos[]=$p["CACC"]+$p["GGTG"];
        $oligos[]=$p["CACG"]+$p["CGTG"];
        $oligos[]=$p["CAGA"]+$p["TCTG"];
        $oligos[]=$p["CAGC"]+$p["GCTG"];
        $oligos[]=$p["CAGG"]+$p["CCTG"];
        $oligos[]=$p["CATA"]+$p["TATG"];
        $oligos[]=$p["CATC"]+$p["GATG"];
        $oligos[]=$p["CCAA"]+$p["TTGG"];
        $oligos[]=$p["CCAC"]+$p["GTGG"];
        $oligos[]=$p["CCAG"]+$p["CTGG"];
        $oligos[]=$p["CCCA"]+$p["TGGG"];
        $oligos[]=$p["CCCC"]+$p["GGGG"];
        $oligos[]=$p["CCCG"]+$p["CGGG"];
        $oligos[]=$p["CCGA"]+$p["TCGG"];
        $oligos[]=$p["CCGC"]+$p["GCGG"];
        $oligos[]=$p["CCTA"]+$p["TAGG"];
        $oligos[]=$p["CCTC"]+$p["GAGG"];
        $oligos[]=$p["CGAA"]+$p["TTCG"];
        $oligos[]=$p["CGAC"]+$p["GTCG"];
        $oligos[]=$p["CGAG"]+$p["CTCG"];
        $oligos[]=$p["CGCA"]+$p["TGCG"];
        $oligos[]=$p["CGCC"]+$p["GGCG"];
        $oligos[]=$p["CGGA"]+$p["TCCG"];
        $oligos[]=$p["CGGC"]+$p["GCCG"];
        $oligos[]=$p["CGTA"]+$p["TACG"];
        $oligos[]=$p["CGTC"]+$p["GACG"];
        $oligos[]=$p["CTAA"]+$p["TTAG"];
        $oligos[]=$p["CTAC"]+$p["GTAG"];
        $oligos[]=$p["CTCA"]+$p["TGAG"];
        $oligos[]=$p["CTCC"]+$p["GGAG"];
        $oligos[]=$p["CTGA"]+$p["TCAG"];
        $oligos[]=$p["CTGC"]+$p["GCAG"];
        $oligos[]=$p["CTTA"]+$p["TAAG"];
        $oligos[]=$p["CTTC"]+$p["GAAG"];
        $oligos[]=$p["GAAA"]+$p["TTTC"];
        $oligos[]=$p["GAAC"]+$p["GTTC"];
        $oligos[]=$p["GACA"]+$p["TGTC"];
        $oligos[]=$p["GACC"]+$p["GGTC"];
        $oligos[]=$p["GAGA"]+$p["TCTC"];
        $oligos[]=$p["GAGC"]+$p["GCTC"];
        $oligos[]=$p["GATA"]+$p["TATC"];
        $oligos[]=$p["GCAA"]+$p["TTGC"];
        $oligos[]=$p["GCAC"]+$p["GTGC"];
        $oligos[]=$p["GCCA"]+$p["TGGC"];
        $oligos[]=$p["GCCC"]+$p["GGGC"];
        $oligos[]=$p["GCGA"]+$p["TCGC"];
        $oligos[]=$p["GCTA"]+$p["TAGC"];
        $oligos[]=$p["GGAA"]+$p["TTCC"];
        $oligos[]=$p["GGAC"]+$p["GTCC"];
        $oligos[]=$p["GGCA"]+$p["TGCC"];
        $oligos[]=$p["GGGA"]+$p["TCCC"];
        $oligos[]=$p["GGTA"]+$p["TACC"];
        $oligos[]=$p["GTAA"]+$p["TTAC"];
        $oligos[]=$p["GTCA"]+$p["TGAC"];
        $oligos[]=$p["GTGA"]+$p["TCAC"];
        $oligos[]=$p["GTTA"]+$p["TAAC"];
        $oligos[]=$p["TAAA"]+$p["TTTA"];
        $oligos[]=$p["TACA"]+$p["TGTA"];
        $oligos[]=$p["TAGA"]+$p["TCTA"];
        $oligos[]=$p["TCAA"]+$p["TTGA"];
        $oligos[]=$p["TCCA"]+$p["TGGA"];
        $oligos[]=$p["TGAA"]+$p["TTCA"];
        $sum_x=array_sum($oligos);
        foreach($oligos as $k => $v){$sum_x2+=$v*$v;}
        $oligos[]=$p["AATT"]*2;
        $oligos[]=$p["ACGT"]*2;
        $oligos[]=$p["AGCT"]*2;
        $oligos[]=$p["ATAT"]*2;
        $oligos[]=$p["CATG"]*2;
        $oligos[]=$p["CCGG"]*2;
        $oligos[]=$p["CGCG"]*2;
        $oligos[]=$p["CTAG"]*2;
        $oligos[]=$p["GATC"]*2;
        $oligos[]=$p["GCGC"]*2;
        $oligos[]=$p["GGCC"]*2;
        $oligos[]=$p["GTAC"]*2;
        $oligos[]=$p["TATA"]*2;
        $oligos[]=$p["TCGA"]*2;
        $oligos[]=$p["TGCA"]*2;
        $oligos[]=$p["TTAA"]*2;
        // going along all array ensures duplicating values for values 1 to 120
        //   and adding values 121 to 136 only ones
        $sum_x+=array_sum($oligos);
        foreach($oligos as $k => $v){$sum_x2+=$v*$v;}

        $oligos[]=$sum_x;   // value 137
        $oligos[]=$sum_x2;  // value 138

        return $oligos;
}
// #######################################################################################
function Pearson_distance($vals_x,$vals_y){
        //Pearson distance computing in only one single-pass algorithm
        // original functions searched a modified Pearson correlation,
        // but normal pearson distance is a better approach
        if (sizeof($vals_x)!= sizeof($vals_y)){return;}
        $sum_x=array_sum($vals_x);
        $sum_y=array_sum($vals_y);
        $sum_x2=0;
        $sum_y2=0;
        $sum_xy=0;
        foreach($vals_x as $key => $val_x){
                $val_y=$vals_y[$key];
                $sum_x2+=$val_x*$val_x;
                $sum_y2+=$val_y*$val_y;
                $sum_xy+=$val_x*$val_y;
        }
        $n=sizeof($vals_x);
        // calculate regression
        $r=($n*$sum_xy-$sum_x*$sum_y)/((sqrt($n*$sum_x2-$sum_x*$sum_x)*(sqrt($n*$sum_y2-$sum_y*$sum_y))));
        // return distance
        return (1-$r);

}
function Pearson_distance_136($a,$b){
   // optimized version of Pearson distance for tetranucleotides
   //   requires de input arrays to be in an especific
   //   format (computed by function search_oligos_4bothstrands)
   $sum_xy=0;
   $sum_xy =$a[0]*$b[0];
   $sum_xy+=$a[1]*$b[1];
   $sum_xy+=$a[2]*$b[2];
   $sum_xy+=$a[3]*$b[3];
   $sum_xy+=$a[4]*$b[4];
   $sum_xy+=$a[5]*$b[5];
   $sum_xy+=$a[6]*$b[6];
   $sum_xy+=$a[7]*$b[7];
   $sum_xy+=$a[8]*$b[8];
   $sum_xy+=$a[9]*$b[9];
   $sum_xy+=$a[10]*$b[10];
   $sum_xy+=$a[11]*$b[11];
   $sum_xy+=$a[12]*$b[12];
   $sum_xy+=$a[13]*$b[13];
   $sum_xy+=$a[14]*$b[14];
   $sum_xy+=$a[15]*$b[15];
   $sum_xy+=$a[16]*$b[16];
   $sum_xy+=$a[17]*$b[17];
   $sum_xy+=$a[18]*$b[18];
   $sum_xy+=$a[19]*$b[19];
   $sum_xy+=$a[20]*$b[20];
   $sum_xy+=$a[21]*$b[21];
   $sum_xy+=$a[22]*$b[22];
   $sum_xy+=$a[23]*$b[23];
   $sum_xy+=$a[24]*$b[24];
   $sum_xy+=$a[25]*$b[25];
   $sum_xy+=$a[26]*$b[26];
   $sum_xy+=$a[27]*$b[27];
   $sum_xy+=$a[28]*$b[28];
   $sum_xy+=$a[29]*$b[29];
   $sum_xy+=$a[30]*$b[30];
   $sum_xy+=$a[31]*$b[31];
   $sum_xy+=$a[32]*$b[32];
   $sum_xy+=$a[33]*$b[33];
   $sum_xy+=$a[34]*$b[34];
   $sum_xy+=$a[35]*$b[35];
   $sum_xy+=$a[36]*$b[36];
   $sum_xy+=$a[37]*$b[37];
   $sum_xy+=$a[38]*$b[38];
   $sum_xy+=$a[39]*$b[39];
   $sum_xy+=$a[40]*$b[40];
   $sum_xy+=$a[41]*$b[41];
   $sum_xy+=$a[42]*$b[42];
   $sum_xy+=$a[43]*$b[43];
   $sum_xy+=$a[44]*$b[44];
   $sum_xy+=$a[45]*$b[45];
   $sum_xy+=$a[46]*$b[46];
   $sum_xy+=$a[47]*$b[47];
   $sum_xy+=$a[48]*$b[48];
   $sum_xy+=$a[49]*$b[49];
   $sum_xy+=$a[50]*$b[50];
   $sum_xy+=$a[51]*$b[51];
   $sum_xy+=$a[52]*$b[52];
   $sum_xy+=$a[53]*$b[53];
   $sum_xy+=$a[54]*$b[54];
   $sum_xy+=$a[55]*$b[55];
   $sum_xy+=$a[56]*$b[56];
   $sum_xy+=$a[57]*$b[57];
   $sum_xy+=$a[58]*$b[58];
   $sum_xy+=$a[59]*$b[59];
   $sum_xy+=$a[60]*$b[60];
   $sum_xy+=$a[61]*$b[61];
   $sum_xy+=$a[62]*$b[62];
   $sum_xy+=$a[63]*$b[63];
   $sum_xy+=$a[64]*$b[64];
   $sum_xy+=$a[65]*$b[65];
   $sum_xy+=$a[66]*$b[66];
   $sum_xy+=$a[67]*$b[67];
   $sum_xy+=$a[68]*$b[68];
   $sum_xy+=$a[69]*$b[69];
   $sum_xy+=$a[70]*$b[70];
   $sum_xy+=$a[71]*$b[71];
   $sum_xy+=$a[72]*$b[72];
   $sum_xy+=$a[73]*$b[73];
   $sum_xy+=$a[74]*$b[74];
   $sum_xy+=$a[75]*$b[75];
   $sum_xy+=$a[76]*$b[76];
   $sum_xy+=$a[77]*$b[77];
   $sum_xy+=$a[78]*$b[78];
   $sum_xy+=$a[79]*$b[79];
   $sum_xy+=$a[80]*$b[80];
   $sum_xy+=$a[81]*$b[81];
   $sum_xy+=$a[82]*$b[82];
   $sum_xy+=$a[83]*$b[83];
   $sum_xy+=$a[84]*$b[84];
   $sum_xy+=$a[85]*$b[85];
   $sum_xy+=$a[86]*$b[86];
   $sum_xy+=$a[87]*$b[87];
   $sum_xy+=$a[88]*$b[88];
   $sum_xy+=$a[89]*$b[89];
   $sum_xy+=$a[90]*$b[90];
   $sum_xy+=$a[91]*$b[91];
   $sum_xy+=$a[92]*$b[92];
   $sum_xy+=$a[93]*$b[93];
   $sum_xy+=$a[94]*$b[94];
   $sum_xy+=$a[95]*$b[95];
   $sum_xy+=$a[96]*$b[96];
   $sum_xy+=$a[97]*$b[97];
   $sum_xy+=$a[98]*$b[98];
   $sum_xy+=$a[99]*$b[99];
   $sum_xy+=$a[100]*$b[100];
   $sum_xy+=$a[101]*$b[101];
   $sum_xy+=$a[102]*$b[102];
   $sum_xy+=$a[103]*$b[103];
   $sum_xy+=$a[104]*$b[104];
   $sum_xy+=$a[105]*$b[105];
   $sum_xy+=$a[106]*$b[106];
   $sum_xy+=$a[107]*$b[107];
   $sum_xy+=$a[108]*$b[108];
   $sum_xy+=$a[109]*$b[109];
   $sum_xy+=$a[110]*$b[110];
   $sum_xy+=$a[111]*$b[111];
   $sum_xy+=$a[112]*$b[112];
   $sum_xy+=$a[113]*$b[113];
   $sum_xy+=$a[114]*$b[114];
   $sum_xy+=$a[115]*$b[115];
   $sum_xy+=$a[116]*$b[116];
   $sum_xy+=$a[117]*$b[117];
   $sum_xy+=$a[118]*$b[118];
   $sum_xy+=$a[119]*$b[119];
   $sum_xy=$sum_xy*2;
   $sum_xy+=$a[120]*$b[120];
   $sum_xy+=$a[121]*$b[121];
   $sum_xy+=$a[122]*$b[122];
   $sum_xy+=$a[123]*$b[123];
   $sum_xy+=$a[124]*$b[124];
   $sum_xy+=$a[125]*$b[125];
   $sum_xy+=$a[126]*$b[126];
   $sum_xy+=$a[127]*$b[127];
   $sum_xy+=$a[128]*$b[128];
   $sum_xy+=$a[129]*$b[129];
   $sum_xy+=$a[130]*$b[130];
   $sum_xy+=$a[131]*$b[131];
   $sum_xy+=$a[132]*$b[132];
   $sum_xy+=$a[133]*$b[133];
   $sum_xy+=$a[134]*$b[134];
   $sum_xy+=$a[135]*$b[135];

   $sum_x =$a[136];
   $sum_x2=$a[137];
   $sum_y =$b[136];
   $sum_y2=$b[137];

   // calculate regression
   $r=(256*$sum_xy-$sum_x*$sum_y)/(sqrt(256*$sum_x2-$sum_x*$sum_x)*(sqrt(256*$sum_y2-$sum_y*$sum_y)));
   // return distance
   return 1-$r;
}

// #######################################################################################
function str_is_int($str) {
        $var=intval($str);
        return ("$str"=="$var");
}
// #######################################################################################
// CREATE IMAGEN
function Create_Image($sequence,$window,$GC,$AT,$KETO,$GmC,$oligo_skew_array,$olen,$from,$to,$name,$dir){
        // creates the image based in data provided
        // the code is not properly explained yet
        $pos=0;
        $len_seq=strlen($sequence);
        $period=ceil($len_seq/6000);

        // computes data for GC, AT, KETO and G+C skews (if requested)
        while (1==2){
                $sub_seq=substr($sequence,$pos,$window);
                $A=substr_count($sub_seq,"A");
                $C=substr_count($sub_seq,"C");
                $G=substr_count($sub_seq,"G");
                $T=substr_count($sub_seq,"T");
                $GyC=$G+$C;
                $AyT=$A+$T;
                $ACGT=$AyT+$CyG;
                $dGC[$pos]=($G-$C)/$GyC;
                if ($AT==1){$dAT[$pos]=($A-$T)/$AyT;}
                if ($KETO==1){$dKETO[$pos]=round(($GyC-$AyT)/$ACGT,4);}
                if ($GmC==1){$dGmC[$pos]=$GyC/$ACGT;}
                $pos+=$period;
        }
        // computes data for GC, AT, KETO and G+C skews (if requested)
        while ($pos<$len_seq-$window){
                $sub_seq=substr($sequence,$pos,$window);
                $A=substr_count($sub_seq,"A");
                $C=substr_count($sub_seq,"C");
                $G=substr_count($sub_seq,"G");
                $T=substr_count($sub_seq,"T");
                $dGC[$pos]=($G-$C)/($G+$C);
                if ($AT==1){$dAT[$pos]=($A-$T)/($A+$T);}
                if ($KETO==1){$dKETO[$pos]=round(($G+$C-$A-$T)/($A+$C+$G+$T),4);}
                if ($GmC==1){$dGmC[$pos]=($G+$C)/($A+$C+$G+$T);}
                $pos+=$period;
        }

        // scale related variables
        $max=max(max($dAT),max($dGC),max($dKETO));
        $min=min(min($dAT),min($dGC),min($dKETO));
        $nmax=max($max,-$min);
        $rectify=round(200/$nmax);

        // starts the image
        $im = @imagecreate(850, 450) or die($content."Cannot Initialize new GD image stream. It is GD library available?");
        $background_color =imagecolorallocate($im, 255, 255, 255);
        $black=ImageColorAllocate($im, 0, 0, 0);
        $qblack2=ImageColorAllocate($im, 228, 228, 228);
        $qblack=ImageColorAllocate($im, 192, 192, 192);
        $red=ImageColorAllocate($im, 255, 0, 0);
        $blue=ImageColorAllocate($im, 0, 0, 255);
        $green=ImageColorAllocate($im, 0, 255, 0);
        $rb=ImageColorAllocate($im, 255, 0, 255);
        $gb=ImageColorAllocate($im, 0, 150,150);
        imagestring($im, 2, 610, 432,  "by biophp.org", $black);
        imagestring($im, 3, 600, 5,  "Window: $window", $black);

        // writes length of sequence
        if ($from or $to){
                if(!$from){$from=0;}
                if(!$to){$to=$len_seq;}
                imagestring($im, 3, 5, 432,  "Length of $name: $len_seq (from position $from to $to)", $black);
        }else{
                imagestring($im, 3, 5, 432,  "Length of $name: $len_seq", $black);
        }

        // write the kind of skews in proper color
        $goright=0;
        if ($GC==1){
                imagestring($im, 3, 5+$goright, 5,  "GC-skew", $blue);
                $goright=70;}
        if ($AT==1){
                imagestring($im, 3, 5+$goright, 5,  "AT-skew", $red);
                $goright+=70;}
        if ($KETO==1){
                imagestring($im, 3, 5+$goright, 5,  "KETO-skew", $green);
                $goright+=80;}
        if ($GmC==1){
                imagestring($im, 3, 5+$goright, 5,  "G+C", $black);
                $goright+=60;}
        if (sizeof($oligo_skew_array)>10){
                imagestring($im, 3, 5+$goright, 5,  "oligo-skew ($olen)", $gb);}

       // print scale for AT, GC or KETO skews
        $ne=0;
        if($AT==1 or $GC==1 or $KETO==1){
                imagestring($im, 3, 710, 210,  "0", $red);
                $scale=round($nmax*0.25,3);
                $v=$scale*3;
                        imagestring($im, 3, 710, 60,  $v, $red);
                        imagestring($im, 3, 710, 360,  -$v, $red);
                $v=$scale*2;
                        imagestring($im, 3, 710, 110,  $v, $red);
                        imagestring($im, 3, 710, 310,  -$v, $red);
                $v=$scale;
                        imagestring($im, 3, 710, 160,  $v, $red);
                        imagestring($im, 3, 710, 260,  -$v, $red);
                $ne=60;
        }
        // print scale for G+C skew
        if($GmC==1){
                $kkk=360;
                for($i=20;$i<81;$i+=10){imagestring($im, 3, 710+$ne, $kkk,  "$i%", $black);$kkk-=50;}
                if($ne==60){
                        for($i=20;$i<421;$i+=50){imageline($im,698+$ne,$i,703+$ne,$i,$black);}
                        imageline($im,764,20,764,420,$black);
                }
                $ne+=60;
        }
        // print scale for oligo-skew
        if(sizeof($oligo_skew_array)>10){
                $kkk=15;
                for($i=0;$i<9;$i++){imagestring($im, 3, 710+$ne, $kkk,  "0.$i", $gb);$kkk+=50;}
                if($ne>0){
                        for($i=20;$i<421;$i+=50){imageline($im,698+$ne,$i,703+$ne,$i,$black);}
                        imageline($im,704+$ne,20,704+$ne,420,$black);
                }
        }
        // compute and print out to image the oligo-skew
        //   oligo-skews must be the first one to be printed out
        $xp=($window*700)/(2*$len_seq);
        if (sizeof($oligo_skew_array)>10){
                foreach($oligo_skew_array as $pos => $val){
                        $x=round(($pos*700/$len_seq)+$xp);
                        imageline($im,$x,20,$x,19+(500*$val),$qblack2);
                        imagesetpixel($im,$x,20+(500*$val),$gb);
                }
        }
        // print AT, GC and/or KETO-skews
        //    each one with one color
        foreach($dGC as $pos => $val){
                $x=round(($pos*700/$len_seq)+$xp);
                if($AT==1){imagesetpixel($im,$x,220-$dAT[$pos]*$rectify,$red);}
                if($GC==1){imagesetpixel($im,$x,220-$val*$rectify,$blue);}
                if($KETO==1){imagesetpixel($im,$x,220-$dKETO[$pos]*$rectify,$green);}
                if($GmC==1){imagesetpixel($im,$x,470-(500*$dGmC[$pos]),$black);}
        }

        // write some aditional lines
        for($i=20;$i<421;$i+=50){imageline($im,0,$i,700,$i,$black);}
        imageline($im,70,20,70,420,$qblack);
        imageline($im,140,20,140,420,$qblack);
        imageline($im,210,20,210,420,$qblack);
        imageline($im,280,20,280,420,$qblack);
        imageline($im,350,20,350,420,$qblack);
        imageline($im,420,20,420,420,$qblack);
        imageline($im,490,20,490,420,$qblack);
        imageline($im,560,20,560,420,$qblack);
        imageline($im,630,20,630,420,$qblack);
        imageline($im,700,20,700,420,$black);

        // output the image to a file
        imagepng($im,"$dir/image.png");
        imagedestroy($im);
        return;
}
// #######################################################################################
function Comp($code){
        // returns reverse-complement of sequence $code
        $code=str_replace("A", "t", $code);
        $code=str_replace("T", "a", $code);
        $code=str_replace("G", "c", $code);
        $code=str_replace("C", "g", $code);
        return strtoupper ($code);
}
?>
