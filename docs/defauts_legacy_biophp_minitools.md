# Defects found in the original BioPHP minitools

**Date**: 24 august 2026
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
