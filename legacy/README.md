# Legacy minitools sources

Original source code of the BioPHP minitools, kept for reference and licence
continuity — the same practice as the `Legacy/` folder of `amelaye/biophp`.

- **Origin**: <http://www.biophp.org/resources.php?mode=minitools>, one file per
  tool, retrieved through each tool's `index.php?action=download` endpoint
  (served as `code.php.gz`) and decompressed.
- **Author**: Joseba Bikandi
- **Licence**: GNU GPL v2 — the same licence this package is distributed under
  (`GPL-2.0-only`).
- **Retrieved on**: 24 august 2026

Each file is the complete original single-file minitool: presentation, form and
computation mixed together. The modernised equivalents live in `Form/` (the
Symfony form types) and `Service/` (the extracted business logic).

## Correspondence with the modernised code

| Legacy file | Form type | Manager |
|---|---|---|
| `chaos_game_representation.php` | `ChaosGameRepresentationType` | `ChaosGameRepresentationManager` |
| `distance_among_sequences.php` | `DistanceAmongSequencesType` | `DistanceAmongSequencesManager` |
| `dna_to_protein.php` | `DnaToProteinType` | `DnaToProteinManager` |
| `find_palindromes.php` | `FindPalindromesType` | `FindPalindromeManager` |
| `gc_content_finder.php` | `FastaUploaderType` | `FastaUploaderManager` |
| `melting_temperature.php` | `MeltingTemperatureType` | `MeltingTemperatureManager` |
| `microarray_analysis_adaptive_quantification.php` | `MicroArrayDataAnalysisType` | `MicroarrayAnalysisAdaptiveManager` |
| `microsatellite_repeats_finder.php` | `MicrosatelliteRepeatsFinderType` | `MicrosatelliteRepeatsFinderManager` |
| `oligonucleotide_frequency.php` | `OligoNucleotideFrequencyType` | *(uses biophp `OligosManager`)* |
| `pcr_amplification.php` | `PcrAmplificationType` | `PcrAmplificationManager` |
| `protein_properties.php` | `ProteinPropertiesType` | `ProteinPropertiesManager` |
| `protein_to_dna.php` | `ProteinToDnaType` | `ProteinToDnaManager` |
| `random_seqs.php` | `RandomSequencesType` | `RandomSequencesManager` |
| `reader_gff_fasta.php` | **not migrated yet** | **not migrated yet** |
| `reduce_protein_alphabet.php` | `ReduceAlphabetType` | `ReduceProteinAlphabetManager` |
| `restriction_digest.php` | `RestrictionEnzymeDigestType` | `RestrictionDigestManager` |
| `seq_alignment.php` | `SequenceAlignmentType` | `SequenceAlignmentManager` |
| `sequence_manipulation_and_data.php` | `SequenceManipulationType` | `SequenceManipulationAndDataManager` |
| `skews.php` | `SkewsType` | `SkewsManager` |
| `useful_formulas.php` | `FormulasType` | `FormulasManager` |

## Deliberate divergences from the legacy code

Two formulas in `useful_formulas.php` are mathematically wrong in the original and
have been corrected in `Service/FormulasManager.php` (each divergence is commented
at the call site):

- Centigrade to Fahrenheit: the original applies `32 + (C x 0.555)`; `0.555` (~5/9)
  is the Fahrenheit-to-Centigrade factor used in the wrong direction. It returns
  87.5 degF for 100 degC instead of 212 degF. Corrected to `32 + (C x 1.8)`.
- Millibars to inches of mercury: the original applies `mbar x 0.0394`, which is the
  millimetre-to-inch factor, overstating the result by ~33%. Corrected to
  `mbar x 0.02953`.

`skews.php` computes its oligo-skews through two code paths, and **both** are broken
in the original, so `Service/SkewsManager.php` implements only the general one:

- `oligo_len == 4` and `strands == 2` — the tool's default — routes to the "optimized"
  `Oligo_skew_array_calculation4bothstrands()`. That function never assigns
  `$sequence2` (unlike its general sibling, which does `$sequence2 = Comp($sequence)`),
  so the `strrev(substr($sequence2, ...))` half of every window is empty: despite its
  name it silently analyses a single strand. It also scores with
  `Pearson_distance_136()`.
- any other `strands == 2` combination calls `distance()`, a function defined nowhere
  in the file — a fatal error under every PHP version.

`SkewsManager::oligoSkewArrayCalculation()` always takes the general path, with a real
reverse complement and a genuine `distance()` (Almeida et al, 2001) implementation.
Both defects are therefore fixed, but as a consequence **the tool's default
configuration returns different numbers from the original** — the original's were
computed on one strand.

Two entries the original does not compute at all are implemented in
`FormulasManager`: "Molar conversions for proteins" (`pmolToMicrogProtein`) and
"Protein/DNA conversions" (`kDaToBasePairs`). `useful_formulas.php` only prints them as
static three-row HTML tables. The formulas reproduce those tables, and in passing
correct the last cell of the second one, which reads `2.7 bp` where the stated rule
gives 2702.7 bp.

`revpermin()` (`useful_formulas.php`) is deliberately **not** migrated: nothing in the
original ever calls it, and it is a no-op anyway — it computes
`$rcf = 1.12 * $R * ($rpm/1000)^2` then immediately divides `$rcf` by `1.12 * $R`
again, so `1000 * sqrt(...)` just returns the `$rpm` it was given. It also carries a
leftover `print $temp;`.

`reader_gff_fasta.php` has no modern equivalent yet.

Do not "fix" these files to match the modernised code: they are kept verbatim as the
upstream reference.
