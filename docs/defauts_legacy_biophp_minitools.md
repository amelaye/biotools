# Defects found in the original BioPHP minitools

**Date**: 24 august 2026, extended 14 september 2026 (defects 7 to 10, found during a
systematic manager-by-manager re-audit against `legacy/`)
**Reported by**: Amélie DUVERNET (amelaye) — maintainer of the `amelaye/biotools` port
**Upstream**: <http://www.biophp.org/resources.php?mode=minitools>, author Joseba Bikandi, GNU GPL v2
**Sources examined**: the 20 minitools, retrieved through each tool's
`index.php?action=download` endpoint and kept verbatim in [`legacy/`](../legacy/)

This note lists defects found in the **original** minitools while porting them to a
modern PHP 8.2 / Symfony 7 component. They are reported here so the upstream authors can
decide what to do with them. None of them is a porting mistake: each was verified
against the original source, quoted below.

The port itself has diverged on these points; the divergences are documented in
[`legacy/README.md`](../legacy/README.md) and in the code.

---

## 1. Centigrade to Fahrenheit uses the inverse factor — wrong results

**File**: `useful_formulas/index.php` (`?&id9=conversion`)

The page states, and the code applies:

```
F° = 32 + (C° x 0.555)
C° = 0.555 x (F°-32)
```

The second line is correct: `0.555 ≈ 5/9` is the Fahrenheit → Centigrade factor. The
first line reuses that same factor in the **opposite** direction, where the correct one
is `9/5 = 1.8`.

| Input | Original result | Correct result |
|---|---|---|
| 100 °C | 87.5 °F | **212 °F** |
| 20 °C | 43.1 °F | **68 °F** |

**Suggested fix**: `F = 32 + (C x 1.8)`.

## 2. Millibars to inches of mercury uses the millimetre-to-inch factor — 33% error

**File**: `useful_formulas/index.php` (`?&id10=...`)

```
From millibars (mbar) to Inches of mercury (inch Hg) = mbar x 0.039400
```

`0.0394` is the **millimetre to inch** factor (1 mm = 0.03937 in), not the millibar to
inHg one. The conversion has to compose the two steps the page already lists elsewhere:

```
1 mbar = 0.750062 mmHg ; 1 mm = 0.03937 in
=> 1 mbar = 0.750062 x 0.03937 = 0.02953 inHg
```

The published factor overstates every result by about 33%.

**Suggested fix**: `mbar x 0.029530`.

## 3. `standar_frecuencies()` divides by zero on an empty frequency set

**File**: `distance_among_sequences/index.php`, line 505

```php
function standar_frecuencies($array, $m){
        $sum=0;
        foreach($array as $k => $v){
                $sum+=$v;
        }
        $c=pow(4,$m)/$sum;
        ...
```

`$sum` is zero whenever the oligonucleotide array is empty or contains only zeros — for
instance on an empty or fully ambiguous sequence. Under PHP 5 and 7 this produced a
warning and `INF`, silently poisoning every downstream frequency. **Since PHP 8 it is a
fatal `DivisionByZeroError`.**

**Suggested fix**: return early, or raise a meaningful error, when `$sum` is zero.

## 4. No input validation anywhere in the corpus

Searching the 20 sources for `is_string(`, `is_array(` or `throw new` returns **no match
at all**. Every function goes straight to `strlen()`, `substr()`, `strpos()`,
`preg_replace()`, `max()` and so on with whatever it is given.

This was survivable under PHP 5 and 7, where passing the wrong type to a string function
emitted a warning and carried on. **Under PHP 8 these are fatal `TypeError`,
`ValueError` or `DivisionByZeroError`.** The functions concerned in the port were:

| Tool | Function | PHP 8 failure |
|---|---|---|
| `find_palindromes` | `find_palindromic_seqs`, `DNA_is_palindrome` | `strlen()`, `strrev()` on non-string |
| `melting_temperature` | CG count, Tm min/max, base stacking | `substr_count()`, `strlen()` on non-string |
| `microsatellite_repeats_finder` | repeat search, `N1` / `N+1` helpers | `strlen()`, `substr()` on non-string |
| `gc_content_finder` | sequence validity check | `strlen()` on non-string |
| `microarray_analysis_adaptive_quantification` | data parsing | `strpos()` on non-string |
| `dna_to_protein` | translation, ORF search | `preg_replace()` pattern/replacement mismatch |
| `distance_among_sequences` | Pearson and Euclidean distances | `sizeof()` on non-countable |
| `chaos_game_representation` | FCGR rendering | `max()` on empty array |

**Suggested fix**: validate the arguments at the entry of each public function.

## 5. `imagedestroy()` is deprecated as of PHP 8.5

All the drawing tools end with `imagedestroy($im)`. The function has had no effect since
PHP 8.0 and **emits a deprecation notice as of PHP 8.5**. It can simply be removed.

## 6. Reference table typo in the Protein/DNA conversions

**File**: `useful_formulas/index.php` (`?&id12=Protein_DNA`)

The table reads:

| Protein | DNA |
|---|---|
| 10 kDa | 270 bp |
| 30 kDa | 810 bp |
| 100 kDa | **2.7 bp** |

The last cell should be **2.7 kb** (2700 bp). The first two rows are consistent with the
stated rule (1 kb of DNA encodes 333 amino acids = 3.7 × 10⁴ Da, so bp = kDa × 1000/37);
only the unit of the third is wrong.

## 7. `Oligo_skew_array_calculation4bothstrands()` never builds the second strand

**File**: `skews/index.php`, line 279

This function is the optimised path taken by the tool's **default** settings
(`oligo_len == 4` and `strands == 2`, chosen at line 107). Its own header comment says
it "provides tetranucleotide frequencies from both strands". The window is built at
line 304 with:

```php
$subsequence=substr($sequence,$i,$window)." ".strrev(substr($sequence2,$i,$window));
```

`$sequence2` is never assigned anywhere in the function. The general sibling
`Oligo_skew_array_calculation()` does assign it (`$sequence2=Comp($sequence);`, line
254), but that line has no counterpart here. `substr()` on the undefined variable
yields an empty string, so the appended half is always empty and the function scores
**one** strand while reporting both.

**Suggested fix**: add `$sequence2 = Comp($sequence);` before the loop.

## 8. `distance()` is called but never defined — fatal error

**File**: `skews/index.php`, line 261

```php
$data[$i]=distance($oligofreqsA,$oligofreqsB);
```

`Oligo_skew_array_calculation()` calls `distance()` on its `strands == 2` branch, but
no `distance()` exists in the file, and the file includes nothing. Every request
combining both strands with an oligonucleotide length other than 4 therefore dies with
"Call to undefined function distance()". Only the default length 4 escapes it, by being
routed to the separate (and itself defective, see 7) optimised function.

**Suggested fix**: supply the missing function — the header comment of
`Oligo_skew_array_calculation()` points at the modified Pearson correlation of Almeida
et al, 2001, which is what the port implements.

## 9. `includeN_3()` is a copy of `includeN_2()` and allows only two mismatches

**File**: `microsatellite_repeats_finder/index.php`, lines 178 and 204

The dispatcher selects one of three helpers by mismatch count:

```php
if ($mismatches==1){$sub_seq_pattern=includeN_1($sub_seq,0);}
elseif ($mismatches==2){$sub_seq_pattern=includeN_2($sub_seq,0);}
elseif ($mismatches==3){$sub_seq_pattern=includeN_3($sub_seq,0);}
```

`includeN_3()` and `includeN_2()` have byte-for-byte identical bodies: two nested loops
placing exactly two `.` wildcards. The documentation block above `includeN_3()` even
says "Similar to function IncludeN_1 and IncludeN_2, but allows **two** missmaches",
so the name is the only thing promising three. A request allowing three mismatches
silently searches with a two-mismatch pattern.

**Suggested fix**: either implement the three-wildcard pattern, or drop `includeN_3()`
and let the 2 and 3 cases share one helper (what the port does).

## 10. `revpermin()` is dead code and a mathematical no-op

**File**: `useful_formulas/index.php`, line 929

```php
function revpermin($rpm,$RCF,$R){
$rcf=1.12*$R*(pow(( $rpm/1000),2) );
$temp=($rcf / (1.12*$R));
print $temp;
$res=1000*( sqrt($temp) );
return ($res);
}
```

Nothing in the file calls it. Were it called, it would return its own `$rpm` argument:
`$temp` divides `$rcf` by the very `1.12*$R` that was just multiplied in, leaving
`($rpm/1000)^2`, and `1000*sqrt()` undoes the rest. The `$RCF` parameter is unused, and
the leftover `print $temp;` would write a raw number into the middle of the page.

**Suggested fix**: remove it, or implement the intended RPM/RCF conversion
(`RCF = 1.12 x R x (RPM/1000)^2`, so `RPM = 1000 x sqrt(RCF / (1.12 x R))`).

---

## Note on the two graphics defects found in the port only

For completeness, two further defects were found in the **port**, not in the original,
and have been fixed there: a hard-coded relative output directory that made image writing
fail silently, and fixed output file names that let concurrent requests overwrite each
other's graphic. They are mentioned here only to make clear they are **not** upstream
issues.

## Contact

These findings come from the `amelaye/biotools` component, which packages the minitools
as Symfony form types and services while keeping the original sources in `legacy/` for
licence continuity. Happy to provide patches for any of the above.
