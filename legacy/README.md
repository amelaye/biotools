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

Do not "fix" these files to match the modernised code: they are kept verbatim as the
upstream reference.
