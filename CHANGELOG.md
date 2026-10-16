# Changelog

Toutes les modifications notables de biotools sont consignées ici.
Le format suit [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/).

Les corrections scientifiques de la version non publiée sont décrites point par point dans
`docs/plan_corrections_bio_2026-10-16.md` (les numéros entre parenthèses y renvoient).

## [Non publié] — depuis v1.1.2

### Modifié

- **Dépendance :** biotools requiert `amelaye/biophp` `^2.0.1` (au lieu de `^1.5`).
- **Calculs délégués à BioPHP 2.0.1** : Tm et GC% (`MeltingTemperatureManager`), skews
  (`SkewsManager`), alignement local Smith-Waterman avec PAM250 (`SequenceAlignmentManager`),
  UPGMA, comptages d'oligonucléotides et distances (`DistanceAmongSequencesManager`), recherche
  d'ORF (`DnaToProteinManager`). Les services BioPHP sont injectés par des arguments de
  constructeur facultatifs, câblés dans `services.xml` : les appelants existants continuent de
  fonctionner.
- **Résultats différents du legacy, conformes aux corrections de BioPHP :**
  - le Mg²⁺ compte pour `120·√[Mg²⁺]` mM de sodium dans le Tm nearest-neighbor ;
  - le keto skew vaut `(G+T−A−C)/N` ;
  - le clustering est un vrai UPGMA (l'ancienne boucle était un WPGMA) ;
  - `chemicalGroup()` accepte les minuscules.

### Corrigé — résultats scientifiquement faux

- **Distance de Pearson indéfinie affichée comme nulle.** Une paire de séquences dont les
  vecteurs de z-scores diffèrent n'est plus déclarée « identique » : la distance est calculée sur
  les mots communs, ou refusée (`DomainException`) si elle est indéfinie, et le message nomme les
  deux séquences. `ACGTACGTACGT` n'est plus fusionnée en premier par l'UPGMA.
- **Traduction, cadres 4 à 6.** Le brin opposé est lu sur le reverse-complément, et non plus sur
  le complément seul (lu en 3'→5') : `ATGAAATAG` donne `LFH` en cadre 4, et non `YFI`.
- **Coefficient d'absorption molaire (1.3).** `ε = W×5500 + Y×1490 + ⌊C/2⌋×125` (Pace et al.
  1995), en M⁻¹·cm⁻¹. L'ancienne formule utilisait Ala au lieu de Trp, comptait chaque Cys au lieu
  des cystines, et divisait par la masse moléculaire.
- **Étiquettes FCGR des trinucléotides (1.4).** Sept mots étaient superposés sur AAA, et ATC, CTC
  et GTC étaient échangés. Les positions viennent maintenant de
  `ChaosGameRepresentationCalculator::cell()`, comme les cases colorées.
- **Oligo-skew sur deux brins (1.5).** La référence est symétrisée (séquence + reverse-complément),
  comme les fenêtres, et la distance est le Pearson simple du legacy au lieu d'Almeida. Une fenêtre
  égale à la séquence entière donne désormais une distance de 0. **Les courbes de l'oligo-skew à
  deux brins changent.**
- **Dendrogramme (3.3).** Un nœud est tracé à `D/2`, et non plus à `(D + hauteur du plus haut
  enfant)/2` : le dendrogramme est ultramétrique.
- **ORF en mode « trimmed » (3.4).** Un segment sans Met n'est plus surligné comme ORF, et la
  taille minimale se mesure de la Met au stop.
- **Formulaire d'alignement (3.1).** U→T et X→N ne s'appliquent plus aux protéines
  (sélénocystéine, résidu inconnu).

### Corrigé — affichage, libellés et conventions

- **G+C% (4.d).** La page de manipulation de séquence utilise la définition de BioPHP, déjà celle
  du Tm et des skews : les N et codes ambigus mêlant G/C et A/T sortent du dénominateur
  (`ACGTNNNNNN` : 50 % au lieu de 20 %).
- **Numérotation des deux brins (4.h).** Chaque ligne porte la position 1-based de sa dernière
  base.
- **Classes d'acides aminés (4.g).** D est chargé négativement et N polaire (ils étaient inversés).
- **Upload FASTA (4.f).** L'en-tête `>` et les blancs sont retirés avant validation, et un `N` est
  accepté sans être compté.
- **Formulaire Tm (4.a, 4.b, 4.e).** Libellés précisés (nearest-neighbor SantaLucia 1998, Mg²⁺
  libre), amorce passée en majuscules avant validation, validateur déléguant à `GeneticsFunctions`.
  La masse moléculaire est documentée comme celle d'un brin 5'-phosphate (4.c).

### Corrigé — plantages (erreur 500)

- FCGR dont tous les comptes sont égaux : `DivisionByZeroError` dans le calcul du dégradé (max − min
  nul). Toutes les cases prennent alors la teinte la plus claire.
- Skews tous nuls : `DivisionByZeroError` dans `createImage()` (2.1).
- Oligo-skew sur une fenêtre sans oligonucléotide (lacune de N) : le point est omis du graphe au
  lieu de lever une exception (2.2).
- Alignement sans similarité locale (`AAAA` / `TTTT`) ou avec un symbole sans score PAM250
  (`B`, `Z`, `J`, `O`, `*`) : résultat vide accompagné d'un message (2.3).
- Formulaire Tm : concentrations vides, nulles ou négatives refusées par la validation, et sel ou
  Mg²⁺ obligatoire pour le Tm nearest-neighbor (2.4).

### Ajouté

- `ChaosGameRepresentationManager` délègue le jeu du chaos (points du CGR) et la place des cases
  du FCGR à `ChaosGameRepresentationCalculator` de BioPHP, injecté par un argument de constructeur
  facultatif. Nouvelle méthode `oligoCounts($sSequence, $iOligoLen, $iStrand)` : comptes de tous les
  oligonucléotides, zéros compris, sur un ou deux brins.
- `ProteinPropertiesManager::massAbsorptionCoefficientOfProt()` : A280 d'une solution à 1 g/L
  (ε / masse moléculaire).
- `SequenceAlignmentManager::alignDNA()` et `alignProteins()` renvoient en plus `score`,
  `startA`, `endA`, `startB`, `endB` (1-based, fin incluse). Les flancs non alignés sont écrits en
  minuscules et ne reçoivent pas de barre dans `compareAlignment()`.
- `DnaToProteinManager::findOrfRegions()` accepte un code génétique (identifiant NCBI ou libellé
  du formulaire) ; `DnaToProteinManager::NCBI_GENETIC_CODES` donne la correspondance.
- Tests : nouveaux cas de non-régression pour chaque correction ci-dessus, et
  `Tests/Form/MeltingTemperatureTypeTest.php`.

### Changements incompatibles

- `ProteinPropertiesManager::molarAbsorptionCoefficientOfProt()` ne prend plus la masse
  moléculaire et renvoie le coefficient molaire, non plus un coefficient massique. Pour l'ancienne
  valeur, appeler `massAbsorptionCoefficientOfProt()`.
- Les tableaux renvoyés par `alignDNA()` et `alignProteins()` ont de nouvelles clés (`score`,
  positions, et `message` quand le résultat est vide) ; `seqa` et `seqb` contiennent des minuscules
  pour les flancs. Le code qui affiche l'alignement doit s'adapter.
- `SkewsManager::oligoSkewArrayCalculation()` peut renvoyer `null` pour une fenêtre dont la
  distance est indéfinie.

### Documentation

- `legacy/README.md` et `docs/defauts_legacy_biophp_minitools.md` (§7, §8) : le chemin par défaut
  du legacy compte bien les deux brins (le `$sequence2` non défini est sans effet numérique), et sa
  distance est un Pearson, non celle d'Almeida.

## Versions publiées

Les versions `v1.0.0` (2026-08-24), `v1.0.1` (2026-08-25), `v1.1.0` (2026-09-15), `v1.1.1`
(2026-09-17) et `v1.1.2` (2026-09-20) ne sont pas détaillées ici : voir `git log` et les tags.
