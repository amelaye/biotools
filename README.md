# BioTools

Symfony forms and business logic for the [BioPHP minitools](http://www.biophp.org/resources.php?mode=minitools).

This package ships the **reusable half** of the minitools: a Symfony form type and a
service (manager) for each tool. It deliberately contains **no controller and no
template** — the consuming application wires the forms into its own controllers and
renders them with its own views.

All biological reference data (nucleotides, amino acids, codon tables, restriction
endonucleases, vendors…) comes from [`amelaye/biophp`](https://github.com/amelaye/biophp),
so nothing is duplicated here.

## Requirements

- PHP 8.2+
- Symfony 7.4
- `amelaye/biophp`, which itself needs **JMSSerializerBundle** and **DoctrineBundle**
  registered in the host application, and network access to `api.amelayes-biophp.net`
  (the API adapters fetch reference data over HTTP)

## Installation

```bash
composer require amelaye/biotools
```

Register both bundles in `config/bundles.php`:

```php
return [
    // ...
    JMS\SerializerBundle\JMSSerializerBundle::class => ['all' => true],
    Amelaye\BioPHP\AmelayeBioPHPBundle::class => ['all' => true],
    Amelaye\BioTools\AmelayeBioToolsBundle::class => ['all' => true],
];
```

## Usage

Every form type and every manager is available for autowiring. A controller only has
to build the form and delegate to the manager:

```php
use Amelaye\BioTools\Form\FindPalindromesType;
use Amelaye\BioTools\Service\FindPalindromeManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MinitoolsController extends AbstractController
{
    #[Route('/minitools/find-palindromes', name: 'find_palindromes')]
    public function findPalindromes(Request $request, FindPalindromeManager $manager): Response
    {
        $palindromes = [];
        $form = $this->createForm(FindPalindromesType::class);

        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
            $data = $form->getData();
            $palindromes = $manager->findPalindromicSeqs($data['seq'], $data['min'], $data['max']);
        }

        return $this->render('find_palindromes.html.twig', [
            'form' => $form->createView(),
            'palindromes' => $palindromes,
        ]);
    }
}
```

## Available tools

| Tool | Form type | Manager |
|---|---|---|
| Chaos Game Representation of DNA | `ChaosGameRepresentationType` | `ChaosGameRepresentationManager` |
| Oligonucleotide frequency based distance | `DistanceAmongSequencesType` | `DistanceAmongSequencesManager` |
| DNA to protein translation | `DnaToProteinType` | `DnaToProteinManager` |
| Palindromic sequences finder | `FindPalindromesType` | `FindPalindromeManager` |
| GC content finder | `FastaUploaderType` | `FastaUploaderManager` |
| Melting temperature (Tm) | `MeltingTemperatureType` | `MeltingTemperatureManager` |
| Microarray data analysis | `MicroArrayDataAnalysisType` | `MicroarrayAnalysisAdaptiveManager` |
| Microsatellite repeats finder | `MicrosatelliteRepeatsFinderType` | `MicrosatelliteRepeatsFinderManager` |
| Nucleotide / oligonucleotide frequency | `OligoNucleotideFrequencyType` | *(biophp `OligosManager`)* |
| PCR amplification | `PcrAmplificationType` | `PcrAmplificationManager` |
| Protein sequence properties | `ProteinPropertiesType` | `ProteinPropertiesManager` |
| Protein to DNA reverse translation | `ProteinToDnaType` | `ProteinToDnaManager` |
| Random sequences | `RandomSequencesType` | `RandomSequencesManager` |
| Reduced alphabets for proteins | `ReduceAlphabetType` | `ReduceProteinAlphabetManager` |
| Restriction enzyme digest of DNA | `RestrictionEnzymeDigestType` | `RestrictionDigestManager` |
| Alignment of two sequences | `SequenceAlignmentType` | `SequenceAlignmentManager` |
| DNA sequence manipulation / properties | `SequenceManipulationType` | `SequenceManipulationAndDataManager` |
| GC-, AT-, KETO- and oligo-skews | `SkewsType` | `SkewsManager` |
| Formula functions | `FormulasType` | `FormulasManager` |

The upstream *Reader (fasta/gff)* minitool is not covered: it is a parsing library
rather than a form-driven tool, so it belongs with the other parsers in
`amelaye/biophp`. Its original source is kept in [`legacy/`](legacy/).

## Generated graphics

Four tools produce a drawing: Chaos Game Representation (CGR and FCGR), skews and the
distance dendrogram. They are rendered as **SVG** — vector, so crisp at any zoom, with
real fonts and styleable with CSS — and written into the directory configured under
`nucleotids_graphs.path_graphs`. Each method **returns the path it wrote**; the file
name carries a random suffix so two concurrent requests cannot overwrite each other's
graphic:

```php
$sFile = $chaosGameRepresentationManager->createCGRImage($sName, $sSequence, 300);
// /var/www/created_files/CGR_9f3a1c0e.svg

$aResult = $chaosGameRepresentationManager->createFCGRImage(...);
// ['map' => [...image map coordinates...], 'file' => '.../FCGR_1b7d4a22.svg']
```

The directory must exist and be writable, otherwise an exception is thrown — the
previous GD implementation failed silently and returned a name for a file it had not
written.

## Configuration

The bundle works without configuration. Two data sets can be overridden:

```yaml
# config/packages/amelaye_biotools.yaml
amelaye_biotools:
    nucleotids_graphs:
        path_graphs: 'created_files/'   # where generated PNG images are written
        # ... geometry and file names for CGR/FCGR and skew images
    protein_colors:
        # colour palettes for reduced protein alphabets (20, Murphy15, Li10, …)
```

Defaults live in [`Resources/config/defaults.php`](Resources/config/defaults.php).

## Divergences from the original minitools

Two formulas are mathematically wrong in the upstream biophp.org code and have been
corrected here (each divergence is commented in `Service/FormulasManager.php`):

- **Centigrade to Fahrenheit** — the original applies `32 + (C x 0.555)`; `0.555`
  (~5/9) is the Fahrenheit-to-Centigrade factor, used in the wrong direction. It
  returns 87.5 °F for 100 °C instead of 212 °F. Corrected to `32 + (C x 1.8)`.
- **Millibars to inches of mercury** — the original applies `mbar x 0.0394`, the
  millimetre-to-inch factor, overstating the result by about 33%. Corrected to
  `mbar x 0.02953`.

## Legacy code

The original single-file minitools by Joseba Bikandi are kept verbatim in
[`legacy/`](legacy/) for reference and GPL continuity. See
[`legacy/README.md`](legacy/README.md) for provenance and the mapping to the
modernised classes.

## Tests

```bash
composer install
vendor/bin/phpunit
```

## Licence

GNU GPL v2 only — the licence of the original BioPHP minitools.
