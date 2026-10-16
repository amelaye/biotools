# Plan de correction scientifique de biotools — 16 octobre 2026

Revue biologique de biotools après le commit `bc5be93` (délégation des calculs à BioPHP 2.0.1).

**Règle :** BioPHP 2.0.1 est la référence revue et corrigée, on n'y touche pas. Toutes les
corrections ci-dessous se font dans biotools.

**Méthode.** Deux relectures biologiques indépendantes, puis vérification de chaque point :
- par exécution sur biotools avec BioPHP 2.0.1 installé, via des tests jetables qui réutilisent
  les fixtures existantes et ne sont pas versionnés ;
- par lecture du code et comparaison avec `legacy/`.

**Statut de chaque point :**
- **Confirmé (exécuté)** : résultat obtenu en lançant le code.
- **Confirmé (code)** : constaté à la lecture.
- **Partiel** : vrai en partie seulement.
- **À vérifier** : dépend de données hors du dépôt.

**Constat d'ensemble :** biotools appelle BioPHP correctement (arguments, unités, coordonnées,
matrices). Les défauts sont dans la logique propre à biotools, et la plupart viennent du legacy.

---

## Avancement (mis à jour après les corrections)

Tout le plan est réalisé et commité sur `develop`, un test de non-régression par point (suite : 500
tests). `CHANGELOG.md` résume l'ensemble. Ce qui reste ouvert est listé en fin de section.

| Point | Statut | Commit |
|---|---|---|
| 1.1 Distance de Pearson indéfinie | Fait | `36d4ff6` |
| 1.2 Cadres 4 à 6 | Fait | `c27ab2d` |
| 1.3 Coefficient d'absorption | Fait | `d64e7e9` |
| 1.4 Étiquettes FCGR | Fait | `ef84b80` |
| 1.5 Oligo-skew deux brins | Fait | `7df3c6e` |
| 2.1, 2.2 Skews nuls, fenêtres sans oligo | Fait | `7df3c6e` |
| 2.3 Alignement vide ou symbole sans score | Fait | `4771ced` |
| 2.4 Formulaire Tm | Fait | `35ac5da` |
| 3.1 Formulaire d'alignement | Fait | `44c6202` |
| 3.2 Affichage de l'alignement local | Fait | `5e3d30c` |
| 3.3 Dendrogramme | Fait | `7274abc` |
| 3.4 `findORF()` trimmed, 3.5 code génétique des ORF | Fait | `1a27003` |
| 4.a, 4.b, 4.c, 4.e Formulaire et validateur Tm | Fait (4.c : documenté) | `5a18c93` |
| 4.d G+C%, 4.h numérotation | Fait | `c59a2eb` |
| 4.f Upload FASTA | Fait | `3301db8` |
| 4.g Asn / Asp | Fait | `2afad80` |
| 4.i Codons ambigus, délégation 4.2 | Fait | `74059a3` |
| Délégation 4.1 CGR / FCGR | Faite | `bc51988` |
| Délégation 4.3 pI et charge | Faite | `e02ac3b` |
| Délégation 4.4 masse moléculaire | Faite | `5ed9d06` |

**Écarts par rapport au plan, à connaître :**
- **1.3 :** les deux valeurs de ProtParam (Cys réduites et toutes en cystines) ne sont pas affichées ;
  la formule suppose toutes les cystines possibles formées. L'affichage de ε et de A280 séparément
  revient au code appelant, hors de ce dépôt (`massAbsorptionCoefficientOfProt()` existe).
- **1.5 :** l'oracle « recopier `search_oligos_4bothstrands` + `Pearson_distance_136` » n'est pas
  écrit ; les valeurs attendues viennent d'un script Python indépendant (k = 2) et du test
  « fenêtre = séquence entière ⇒ d = 0 ». Pearson est la valeur par défaut ; Almeida reste
  disponible par un paramètre du service et un champ du formulaire (`SkewsManager::DISTANCE_*`).
- **2.4 :** validation du formulaire (et non valeurs par défaut).
- **3.1 :** sans choix ADN / protéine dans le formulaire, une séquence qui ne contient que
  A, C, G, T, U, N, X est lue comme nucléique.
- **3.3 :** la note sur l'axe (hauteur = D/2) n'est pas ajoutée.
- **3.4 :** la taille minimale se mesure de la Met au stop, ce qui a modifié l'attendu de
  `testFindORF` (recalculé indépendamment).
- **4.c :** seule la docblock est corrigée ; le libellé affiché est dans un template hors de ce dépôt.
- **4.d :** définition retenue : celle de BioPHP (`gcFraction`), déjà utilisée par le Tm et les skews.
- **4.3, 4.4 :** les valeurs changent légèrement (pI par bissection ; masse : +0,17 Da sur 17 résidus).

**Reste ouvert :**
- Les gabarits et contrôleurs qui consomment les nouvelles clés (`score`, positions, `message` de
  l'alignement ; `oligoCounts()`) sont hors de ce dépôt.

---

## Priorité 1 — résultats scientifiquement faux

### 1.1 Distance entre séquences : des distances indéfinies affichées comme nulles — **fait** (`36d4ff6`)
- **Fichier :** `Service/DistanceAmongSequencesManager.php` — `pearsonDistance()`
- **Statut :** confirmé (exécuté).
- **Problème :** BioPHP 2.0.1 (`OligosManager::findZScore`) omet les tétranucléotides dont la
  variance est nulle. Les vecteurs de z-scores n'ont donc plus la même taille d'une séquence à
  l'autre. Face à des tailles différentes, biotools renvoie `0`, ce qui veut dire « identiques ».
- **Exemple :** `ACGTACGTACGT` donne 0 z-score et la séquence longue de test en donne 204. La
  distance renvoyée vaut `0`, et l'UPGMA fusionne cette paire en premier.
- **Correction :**
  - calculer la distance sur les clés communes (`array_intersect_key`), avec un minimum de points
    communs ;
  - sinon, distance indéfinie : exclure la séquence et prévenir l'utilisateur, ne jamais renvoyer 0 ;
  - même règle pour une paire de vecteurs vides, qui provoque aujourd'hui une division par zéro.
- **Test :** `ACGTACGTACGT` contre une séquence longue ne doit pas donner 0. Ajouter aussi un test
  avec des tailles égales mais des clés différentes.

### 1.2 Traduction : cadres 4 à 6 lus sur le brin complémentaire non inversé — **fait** (`c27ab2d`)
- **Fichier :** `Service/DnaToProteinManager.php:111` et `:143` (`customTreatment`, `definedTreatment`)
- **Statut :** confirmé (exécuté). Le défaut vient du legacy.
- **Problème :** le code utilise `compDNA()`, c'est-à-dire le complément sans inversion. Le brin
  inverse est donc lu en 3'→5'.
- **Exemple :** pour `ATGAAATAG`, le cadre 4 donne `YFI` (TAC TTT ATC). La bonne valeur est
  `LFH` (CTA TTT CAT). Ce résultat contredit `findOrfRegions()`, qui s'appuie sur
  `OrfFinder` et donc sur le vrai reverse-complément.
- **Correction :**
  - utiliser `revCompDNA()` ;
  - vérifier l'alignement d'affichage dans `showTranslationsAlignedComplementary()` (et
    l'extension Twig), qui suppose aujourd'hui une lecture position par position.
- **Test :** cadres 4 à 6 de `ATGAAATAG` = `LFH`, `YF`, `IS`. Ces valeurs sont à calculer à la
  main sur `CTATTTCAT` avant d'écrire l'assertion.

### 1.3 Coefficient d'absorption : mauvais acide aminé et mauvaise unité — **fait** (`d64e7e9`)
- **Fichier :** `Service/ProteinPropertiesManager.php` — `molarAbsorptionCoefficientOfProt()`
- **Statut :** confirmé (exécuté). Le défaut vient de `legacy/protein_properties.php:285`.
- **Problème :** le calcul utilise `A×5500 + Y×1490 + C×125`. La formule de Pace et al. (1995)
  est `W×5500 + Y×1490 + cystines×125`, où une cystine est un pont disulfure, soit deux Cys.
  Le résultat est en plus divisé par la masse moléculaire : on obtient un coefficient massique
  (A280 d'une solution à 1 g/L), alors que le libellé dit « molaire ».
- **Exemples :**
  - `AAAAAAAAAA` donne 75,47 au lieu de 0 ;
  - `WWWWW` donne 0 au lieu de 27 500 M⁻¹·cm⁻¹, soit environ 28,98 pour 1 g/L.
- **Correction :**
  - utiliser `ε = W×5500 + Y×1490 + floor(C/2)×125` ;
  - afficher séparément ε molaire et A280 (1 g/L) = ε / MW ;
  - envisager d'afficher deux valeurs, Cys réduites et Cys toutes en cystines, comme ProtParam.
- **Test :** W, Y, `CC`, polyalanine.

### 1.4 Étiquettes FCGR trinucléotides mal placées — **fait** (`ef84b80`)
- **Fichier :** `Service/ChaosGameRepresentationManager.php` — `createGraphForTrinucleo()`
- **Statut :** confirmé (exécuté). Sur les 64 positions, 10 sont fausses par rapport à
  `ChaosGameRepresentationCalculator::cell()`. Les cases colorées (`mapAreaData`) sont, elles,
  conformes à `cell()`. Les dinucléotides sont corrects.
- **Problèmes :**
  - **Dernière rangée** (TAA, ATA, TTA, AAT, TAT, ATT, TTT) : toutes en colonne 0, superposées
    sur AAA. C'est une **régression biotools** : le legacy les plaçait en x = 42 … 234.
  - **Trois cases mal étiquetées**, défaut **hérité du legacy** :

    | Case (colonne, rangée) | Étiquette affichée | Bonne étiquette |
    |---|---|---|
    | (2, 2) | ATC | CTC |
    | (3, 2) | CTC | GTC |
    | (2, 3) | GTC | ATC |

    La case de l'un de ces mots est lue comme celle d'un autre.
- **Correction :** calculer la position des étiquettes avec `cell()` au lieu de les coder en dur,
  pour tous les k. Voir aussi la délégation 4.1.
- **Test :** pour chacun des 64 mots, position de l'étiquette = `cell(mot)` × 32 + décalage.

### 1.5 Oligo-skew « deux brins » : la courbe mesure en partie l'asymétrie des brins — **fait** (`7df3c6e`)
- **Fichiers :**
  - `Service/SkewsManager.php:86` (référence) et `:101` (distance) — `oligoSkewArrayCalculation()`, strands = 2
- **Statut :** confirmé (exécuté). Ce point a été vérifié par deux agents indépendants (relecture
  du legacy, mesure numérique) et recontrôlé ici.
- **Deux écarts avec le chemin par défaut du legacy (k=4, deux brins), qui changent tous deux la
  courbe :**
  1. **Référence non symétrisée.**
     - Les fenêtres sont comptées sur les deux brins (fenêtre + reverse-complément), mais la
       référence de la séquence entière est comptée sur le brin direct seul.
     - Le legacy symétrisait les deux (`search_oligos_4bothstrands`, `legacy/skews.php:295` et `:306`).
     - La distance contient donc un terme qui mesure l'asymétrie globale entre les brins, et non
       l'écart local de composition que l'oligo-skew doit détecter.
  2. **Distance d'Almeida au lieu d'un Pearson simple.**
     - Le legacy utilise `Pearson_distance_136` (`legacy/skews.php:309`), un Pearson simple
       (1 − r) sur les 256 mots ; le commentaire l.573-574 justifie ce choix.
     - Aucune fonction `distance()` n'existe dans `legacy/` : le chemin générique du legacy pour
       deux brins n'a jamais pu s'exécuter, et l'emploi d'Almeida vient du portage biotools.
- **Ampleur mesurée (fenêtre 5 kb, séquences synthétiques de 30 kb) :**

  | Séquence | Écart moyen sur d | Sur le graphe | Forme du profil |
  |---|---|---|---|
  | Équilibrée entre les deux brins | ≤ 0,008 | ≤ 4 px, invisible | Inchangée |
  | Asymétrique, type brin leading | 0,22 à 0,41 | 110 à 215 px | Avec Almeida, profil déformé (corrélation 0,82) et maximum déplacé hors de l'îlot AT témoin |
  | Inversion du skew au milieu | 0,09 à 0,19 | 47 à 96 px | Avec Almeida, le minimum change de moitié de séquence |

  - Avec Pearson, le biais n'est qu'affine : `1 − d_X = c·(1 − d_Xs)`, avec c constant. La forme
    du profil est conservée, mais il est décalé et compressé.
  - Avec Almeida, la pondération x·y casse cette propriété et déforme le profil.
- **Test révélateur :** une fenêtre égale à la séquence entière doit donner d = 0.
  - Recontrôlé ici sur une séquence asymétrique (G, T 30 % ; C, A 20 %), k = 4 : le service
    actuel donne **0,408**, la référence symétrisée donne 0.
  - Le test « la courbe de revcomp(S) est le miroir de celle de S » ne révèle rien : il passe
    avec ou sans le défaut. La distance est en effet invariante quand chaque mot est remplacé par
    son reverse-complément, ce qui a été démontré et vérifié numériquement (écart 0). Il ne faut
    pas l'utiliser comme test de non-régression.
- **Corrections :**
  1. Pour strands = 2, calculer la référence sur `séquence + " " + revCompDNA(séquence)`.
  2. Pour strands = 2, utiliser `PearsonDistance`, comme le legacy, au lieu d'`AlmeidaDistance`.
     Ce choix de métrique est à valider explicitement : il rétablit le comportement du legacy et
     rend le biais résiduel au plus affine.
- **Tests :**
  - Fenêtre = séquence entière : d = 0.
  - Oracle legacy : recopier `search_oligos_4bothstrands` + `Pearson_distance_136` dans le test,
    puis comparer à biotools pour k = 4 et deux brins sur une séquence asymétrique.
  - Recalculer les valeurs attendues de `testOligoSkewArrayCalculationTwoStrands`
    (`Tests/Service/SkewsManagerTest.php:221` et suivantes). La séquence de ce test est très riche
    en G, et ses valeurs actuelles incluent l'artefact.
- **Documentation erronée à corriger :**
  - `legacy/README.md:59-72` et `docs/defauts_legacy_biophp_minitools.md` §7 affirment que le
    chemin par défaut du legacy n'analyse qu'un brin. C'est faux : le repliement en 120 + 16
    classes compte bien les deux brins. Le `$sequence2` non défini (`legacy/skews.php:304`) n'a
    aucun effet numérique, car les comptes sont seulement doublés et Pearson ne dépend pas de
    l'échelle.
  - `docs/defauts_legacy_biophp_minitools.md:162-164` et le docblock de
    `SkewsManager::oligoSkewArrayCalculation` attribuent au legacy la distance d'Almeida. L'en-tête
    du legacy dit « Pearson distances » (`legacy/skews.php:245`).

---

## Priorité 2 — plantages (erreur 500) sur des entrées biologiquement plausibles — **fait** (`7df3c6e`, `4771ced`, `35ac5da`)

Tous ces cas sont confirmés à l'exécution.

| Cas | Entrée | Erreur actuelle |
|---|---|---|
| Skews tous nuls | `"ACGT"×2000`, fenêtre 100 | `DivisionByZeroError` dans `createImage()` (`200 / $fNmax`) |
| Oligo-skew, 1 brin, fenêtre entièrement en N | lacune de 6 000 N, fenêtre 5000 | `DivisionByZeroError` (`PearsonDistance`), non interceptée par `catch (\Exception)` |
| Oligo-skew, 2 brins, même cas | idem | `InvalidArgumentException` (`AlmeidaDistance`) ré-emballée en `\Exception` |
| Alignement sans similarité locale | `AAAA` / `TTTT` | `InvalidAlignmentInputException: Aligned sequences must not be empty` |
| Protéine avec B, Z, J, O ou `*` | `MKBL` / `MKDL` | `noScoreForSymbolPair` (PAM250) |
| Tm, concentrations vides ou nulles | `""` / `0` | `InvalidArgumentException` de BioPHP, affichée comme une trace de pile |

**Corrections :**
- **2.1 Skews :** si `fNmax == 0`, utiliser une échelle par défaut au lieu de diviser.
- **2.2 Oligo-skew :** sur une fenêtre où la distance est indéfinie (exception BioPHP ou
  `DivisionByZeroError`), mettre `null` et ne pas tracer le point.
- **2.3 Alignement :**
  - intercepter `InvalidAlignmentInputException` ;
  - si aucune similarité locale n'est trouvée, renvoyer un résultat vide explicite ;
  - si un symbole n'a pas de score, afficher un message clair « symbole non pris en charge par PAM250 ».
- **2.4 Formulaire Tm :**
  - contraintes `Positive` (amorce), `PositiveOrZero` (sel, Mg), type numérique ;
  - au moins un des deux, sel ou Mg, doit être > 0 ;
  - ou valeurs par défaut 200 / 50 / 0, comme le legacy.

Chaque cas reçoit son test de non-régression.

---

## Priorité 3 — résultats trompeurs ou incomplets

### 3.1 Formulaire d'alignement : U→T et X→N appliqués aux protéines — **fait** (`44c6202`)
- **Fichier :** `Form/SequenceAlignmentType.php:111-119`
- **Statut :** confirmé (code + exécution).
- **Problème :** les remplacements s'appliquent avant le choix ADN/protéine. La sélénocystéine
  (U) devient Thr, et le résidu inconnu X devient Asn, puis reçoit un score PAM250 positif.
  `MKXXL` contre `MKNNL` s'affiche ainsi comme une identité parfaite.
- **Correction :** n'appliquer ces remplacements qu'aux acides nucléiques.

### 3.2 Affichage de l'alignement local — **fait** (`5e3d30c`)
- **Fichier :** `Service/SequenceAlignmentManager.php` — `showWithFlanks()` (introduit par `bc5be93`)
- **Statut :** confirmé (exécuté).
- **Problème :** les flancs non alignés sont juxtaposés comme s'ils étaient alignés.
  `TTACGTAA` / `GGACGTCC` s'affiche sur 8 colonnes, alors que Smith-Waterman n'a aligné que
  `ACGT`. Les `-` de remplissage ressemblent à des indels, et le score et les positions sont perdus.
- **Correction :**
  - renvoyer en plus le score et les positions de début/fin, en 1-based pour l'affichage ;
  - marquer les flancs, par exemple en minuscules ;
  - exclure les colonnes de flanc des barres de `compareAlignment()`.

### 3.3 Dendrogramme non ultramétrique — **fait** (`7274abc`)
- **Fichier :** `Service/DistanceAmongSequencesManager.php:427` (`createDendrogram`)
- **Statut :** confirmé (code). Le défaut vient du legacy.
- **Problème :** un nœud interne est tracé à `(D + x_enfant_max) / 2` au lieu de `D / 2`, alors
  que les fusions de deux feuilles sont bien tracées à `D / 2`.
- **Exemple :** avec d(0,1) = 0,2 et d(0,2) = d(1,2) = 1,0, la racine est tracée à 0,55 au lieu
  de 0,5.
- **Correction :** `fXmax = fVal2 / 2`. Préciser sur l'axe s'il gradue la hauteur, c'est-à-dire D/2.

### 3.4 `findORF()` en mode « trimmed » — **fait** (`1a27003`)
- **Fichier :** `Service/DnaToProteinManager.php` — `findORF()`
- **Statut :** confirmé (exécuté). Le défaut vient du legacy.
- **Problème :** quand un segment ne contient pas de Met, `strpos` renvoie `false` et le segment
  entier est mis en évidence comme ORF. `KKKKKKKKKK*` avec une taille minimale de 10 est ainsi
  affiché comme ORF.
- **Correction :** ne pas surligner un segment sans Met en mode trimmed, et mesurer la longueur de
  Met jusqu'au stop.

### 3.5 `findOrfRegions()` ignore le code génétique — **fait** (`1a27003`)
- **Statut :** confirmé (code).
- **Problème :** `OrfFinderInterface::findOrfs()` accepte `int $iGeneticCode` (24 tables NCBI),
  mais biotools ne le transmet pas. Pour une séquence mitochondriale de vertébré, TGA (Trp dans ce
  code) est donc lu comme un stop.
- **Correction :** ajouter le paramètre, avec une correspondance entre le libellé de code
  génétique de biotools et l'identifiant NCBI.

---

## Priorité 4 — défauts mineurs (affichage, libellés, conventions) — **fait** (voir le tableau d'avancement)

| # | Fichier | Constat | Statut | Correction |
|---|---|---|---|---|
| 4.a | `Form/MeltingTemperatureType.php:61` | La case nearest-neighbor est intitulée « Basic Tm » | Confirmé (code) | Libellé « Nearest-neighbor Tm (SantaLucia 1998), sans base dégénérée » |
| 4.b | `Form/MeltingTemperatureType.php` | BioPHP attend du Mg²⁺ **libre** ; le formulaire n'a pas de champ dNTP | Confirmé (code BioPHP) | Le préciser dans le libellé |
| 4.c | `MeltingTemperatureManager::molwt()` | Masse d'un brin 5'-phosphate (convention BioPHP), environ 79,98 Da au-dessus d'une amorce de synthèse 5'-OH | Confirmé (docblock BioPHP) | Libellé « 5'-phosphate » ou soustraction de HPO₃ |
| 4.d | Tm et skews comparés à `SequenceManipulationAndDataManager::gcContent()` | Deux définitions du G+C% : `ACGTNNNNNN` donne 50 % côté Tm (legacy : 20 %), `SSSSAAAAAA` 40 % (legacy : 0) ; la manipulation de séquence divise par la longueur totale | Confirmé (exécuté) | Choisir une définition (`AbstractNucleicSequence::getGcPercent`) et la documenter |
| 4.e | `Validator/MeltingTemperatureValidator.php` | Majuscules seulement, pas de `strtoupper` en PRE_SUBMIT ; la validation recode `countACGT` et `countYRWSKMDVHB` | Confirmé (code) | Passer en majuscules en PRE_SUBMIT et déléguer à `GeneticsFunctions` |
| 4.f | `Service/FastaUploaderManager.php:78` | Validation sur toute la chaîne, comptage sur les `$iLength` premiers caractères ; un seul N, un en-tête `>` ou un retour ligne fait rejeter le fichier | Confirmé (code) | Nettoyer l'en-tête et les blancs avant validation, et définir le traitement des N |
| 4.g | `ProteinPropertiesManager::proteinAminoacidNature2()` | D et N inversés : D classé polaire, N classé chargé négativement | Confirmé (exécuté), défaut du legacy | `" NQH"` polaire, `" DE"` chargé négativement |
| 4.h | `SequenceManipulationAndDataManager::displayBothStrands()` | Numérotation incohérente : début 0-based sur les lignes pleines, fin sur la dernière ligne | Confirmé (code), défaut du legacy | Numérotation 1-based homogène |
| 4.i | Traduction avec codons ambigus | `AGR` et `YTR` donnent `X` au lieu de `R` et `L` | Confirmé (exécuté) | Résolu par la délégation 4.2 (fait) |

---

## Délégations vers BioPHP 2.0.1 — **faites** (4.1 à 4.4, voir le tableau d'avancement)

| # | biotools | BioPHP 2.0.1 | Gain |
|---|---|---|---|
| 4.1 | Calcul CGR/FCGR de `ChaosGameRepresentationManager` | `ChaosGameRepresentationCalculator` (`points`, `oligoCounts`, `cell`, `frequencyMatrix`) | Corrige 1.4. Attention : k va de 1 à 8 dans BioPHP et de 2 à 7 dans biotools ; `DnaSequence` refuse les symboles hors IUPAC ; l'ombrage (max − min) doit être protégé contre zéro |
| 4.2 | Traduction par expressions régulières (`translateDNAToProtein`) | `AlternateGeneticCodeTranslator::translateCodon($codon, $tableId)` (24 tables NCBI) | Corrige 4.i. Le code personnalisé de 64 lettres reste dans biotools |
| 4.3 | pI (`proteinIsoelectricPoint`, `proteinCharge`) | `ProteinPropertiesCalculator::isoelectricPoint()` et `netCharge()` | Même modèle et mêmes jeux de pK ; la recherche par bisection est plus robuste |
| 4.4 | Masse moléculaire des protéines (table biotools + 18,02) | `ProteinManager::molwt()` (masses moyennes, eau 18,0153) | Une seule référence ; la sélénocystéine compte aujourd'hui pour 0 dans biotools |

**Données bioapi :** bioapi et BioPHP sont considérés comme stables et ne sont pas modifiés ; la
question des tables NCBI absentes des codes servis par bioapi est donc retirée du plan (la délégation
4.2 traduit déjà par les tables NCBI de BioPHP).

---

## Ordre de réalisation proposé

1. Priorité 1 (1.1 à 1.5), un commit par point, chacun avec son test de non-régression.
2. Priorité 2, les plantages.
3. Délégations 4.1 (CGR, qui clôt aussi 1.4) et 4.2 (traduction).
4. Priorité 3.
5. Priorité 4 et délégations 4.3 et 4.4.

Pour chaque correction, les valeurs attendues des tests sont calculées à la main ou par un script
indépendant **avant** d'écrire l'assertion. Elles ne sont jamais recopiées depuis la sortie du code.
