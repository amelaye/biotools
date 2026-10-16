# Passe finale des agents biologistes — 16 octobre 2026

Dernière relecture scientifique de biotools, après les corrections du plan, du journal et du débat.

## Méthode

Deux agents `bioinfo-reviewer` en lecture seule (A et B) ont relu biotools indépendamment, sur le même
périmètre (`Service/`, `Form/`, valeurs figées par `Tests/`). Leurs constats ont ensuite été relayés à l'autre,
qui devait répondre point par point (d'accord, en désaccord, en partie), après relecture du code.

**Règles :**
1. BioPHP et bioapi sont stables : hors périmètre, lus seulement pour comprendre ce que biotools reçoit.
2. Ce qui figure au `CHANGELOG.md`, dans `debat_agents_2026-10-16.md` ou est clos dans le journal n'est pas
   rouvert.
3. Un constat sans entrée concrète qui le démontre n'est pas retenu.

**Limite :** les agents n'ont rien exécuté ; les valeurs sont calculées à la main à partir du code et des
fixtures. Trois constats ont été revérifiés à la lecture du code par l'auteur de ce document : B1
(`RestrictionDigestManager.php:157`), B7 (`ReduceAlphabetType.php:38-39`) et la boucle des microsatellites
(`MicrosatelliteRepeatsFinderManager.php:75`).

**Résultat :** les zones déjà corrigées (Tm, skews, oligo-skew, CGR/FCGR, distances Pearson/UPGMA, traduction,
score d'alignement, pI/masse/ε) n'ont rien donné de nouveau. Les défauts trouvés sont dans des outils que le
plan n'avait pas couverts. Chaque agent a d'abord déclaré propre une zone où l'autre a trouvé un défaut, puis l'a
reconnu (A : digestion de restriction et microarray ; B : propriétés des protéines et upload FASTA).

## Constats retenus

Accord des deux agents sur tous les points, sauf mention « en partie ».

| # | Outil | Défaut | Exemple | Gravité |
|---|---|---|---|---|
| F1 | Digestion de restriction | `reduceEnzymesArray()` cherche le nom choisi comme sous-chaîne et renvoie la première entrée du pool trié | `wre=EcoRI` renvoie l'entrée AjnI (`AjnI,EcoRII,…`, site CCWGG) sous le nom « EcoRI » ; idem HpaI→HpaII, XhoI→XhoII, SacI→SacII | **haute** |
| F2 | Microarray adaptatif | Un spot dont le signal ≤ bruit de fond est plancher à 1e-4 puis traité comme une mesure | GeneWeak de la fixture : log ratio ±6,3, soit un facteur ~2 000 000 ; le test ne vérifie que `is_finite` | moyenne-haute |
| F3 | Digestion de restriction | Une enzyme de type IIs choisie seule n'est cherchée que dans un sens : l'entrée `X@` n'est jamais retenue | `AAAAAAAAAAGAGACCAAAA` : BsaI coupe avec le pool IIs, pas avec `wre=BsaI` | moyenne |
| F4 | Digestion multi-séquences | « Seulement les différences » : compteur non remis à zéro, séquence non coupée ignorée au lieu de 0 coupure, positions non comparées (`array_diff` sur des valeurs toujours `""`) | AluI+EcoRI, `AGCTAAAAAA` / `AGCTGAATTC` → `[]` au lieu de `["EcoRI"]` ; `GAATTCAAAA` / `AAAAGAATTC` non signalé | moyenne |
| F5 | Microsatellites | Après une répétition trouvée, la base suivante est sautée (`$i += …` puis `$i++`) et la boucle `j` continue | `CACACAGTGTGT` (2–3, ≥3 répétitions) : seul (CA)3 trouvé, (GT)3 manqué | moyenne |
| F6 | Palindromes | Pas de normalisation de la saisie ; comparaison sensible à la casse (`revCompDNA` renvoie des majuscules) | `gaattc` → `[]` au lieu de `GAATTC`, `AATT` ; un retour à la ligne décale les positions | moyenne |
| F7 | Propriétés des protéines | Pas de normalisation de la saisie ; `aminoacidContent()` compte les caractères bruts | `mwkw` → ε = 0 au lieu de 11000, alors que la masse est juste ; un `\n` fait échouer la masse | moyenne |
| F8 | PCR in silico | « Un mésappariement » : `includeN()` reçoit l'amorce brute, N reste une lettre littérale | `GANCANTTGG` trouve le site en mode exact, plus avec le mésappariement autorisé | moyenne |
| F9 | Réduction d'alphabet | « Do not reduce » vaut 2, comme « Hydrophilic/Hydrophobic » ; l'alphabet neutre est 20 | `ARND` → `PPPP` au lieu de `ARND` | moyenne |
| F10 | Distances (euclidienne) | Une séquence sans mot de longueur k (plus courte que k, ou que des N) a une somme nulle : `DivisionByZeroError`, non attrapée | `computeOligonucleotidsFrequenciesEuclidean(["s1" => "AC"], 3)` | moyenne (plantage) |
| F11 | Upload FASTA | Seuls les 1 000 000 premiers octets sont lus, sans avertissement, alors que le formulaire accepte ~100 Mo | un FASTA de 5 Mo : comptages tronqués | moyenne-basse |
| F12 | PCR in silico | Codes IUPAC autres que N (R, Y…) dans les amorces gardés comme lettres littérales | `GAGRAGTTGG` ne trouve pas `GAGAAGTTGG` | basse-moyenne |
| F13 | ORF | `findOrfRegions()` refuse l'ARN (alphabet `DnaSequence` sans U) alors que la traduction en six phases le convertit | `AUGAAAUAG` lève une exception | basse-moyenne |
| F14 | PCR in silico | La fenêtre de recherche commence après l'amorce : un produit peut dépasser le maximum de la longueur de l'amorce | amorces de 10 nt, max 100 : produit de 110 accepté | basse |
| F15 | Séquences aléatoires | Chaque effectif est arrondi séparément : la longueur obtenue diffère de la demandée | 4 × 25 %, longueur 10 → 12 bases | basse |
| F16 | Conversions | °F → °C avec 0,555 au lieu de 5/9 | 212 °F → 99,9 °C | basse |
| F17 | Formulaire PCR | La seconde amorce est libellée « Primer 1 (5' -> 3') » | — | basse (libellé) |

## Points discutés (accord en partie)

**Barre sous N face à N dans l'alignement d'ADN** (`SequenceAlignmentManager.php:194`). A : défaut, l'affichage
contredit le score 0 décidé au débat (`ANNT`/`ANNT` affiche `||||` pour un score de 4). B : en partie, c'est une
convention d'affichage, pas un nombre faux, mais l'écart avec le score est réel. **Position commune :** basse
gravité, pas de barre (ou un symbole distinct) sous un code ambigu.

**Dernière fenêtre du skew** (`SkewsManager.php:232`). B : `<` exclut la dernière fenêtre complète, et une
fenêtre égale à la longueur dessine un faux 0. A : d'accord sur `<=`, mais le cas « fenêtre = longueur » est
inaccessible depuis le formulaire (`SkewsType` exige longueur ≥ fenêtre + 1400). **Position commune :** corriger
la borne de la boucle ; le faux 0 ne concerne qu'un appel direct du service.

**U, B, Z, O supprimés en rétrotraduction** (`ProteinToDnaType.php:93`). B : perte silencieuse de codons. A : en
partie, le service n'a pas de codon pour eux, les garder n'aiderait pas ; B → RAY et Z → SAR demanderaient des
données bioapi (hors périmètre). **Position commune :** un message de validation au lieu d'une suppression
silencieuse.

## Hors périmètre (noté seulement)

Données bioapi de la rétrotraduction : pour Blepharisma, Q = `YAR` couvre aussi TAA, stop dans la table 15 ; les
unions de codons dégénérés (`WSN` pour Ser) couvrent d'autres acides aminés. Inhérent à une représentation à un
seul codon ; biotools peut le mentionner dans une docblock.

## Suite donnée

Tous les constats (F1 à F17) et les trois points discutés sont corrigés, chacun avec un test dont la
valeur attendue est calculée à la main et qui échoue sur l'ancien code (27 tests). Deux tests existants
figeaient un défaut et ont été corrigés : `testEnzymesForMultiSeqWreNullShowdiffs` (AfaI et BstSNI
diffèrent aussi) et `testComputeImageDelegatesToSkewCalculator` (la fenêtre 8 manquait) ; deux autres
attendaient 99 bases pour une longueur demandée de 100. La note hors périmètre sur la rétrotraduction
est dans la docblock de `ProteinToDnaManager::translateProteinToDNA()`. Voir le `CHANGELOG.md`.

## Vérification des correctifs

Un troisième agent `bioinfo-reviewer` a relu les correctifs : aucun n'introduit d'erreur, chacun corrige
ce qu'il annonce, et les valeurs attendues des tests sont justes. Ses réserves :

- **Traitées :** une enzyme demandée et introuvable renvoyait tout le pool (elle ne renvoie plus rien ;
  une entrée à liste vide comme PleI est trouvée par sa clé) ; `convertInto3lettersCode()` sans repli
  pour J (`???`) ; la docblock de WSN (AGR, Arg) ; tests ajoutés pour un isoschizomère hors clé (Eco31I
  et Eco31I@), une amorce dégénérée avec mésappariement, des poids tous nuls, une fenêtre de skew égale
  à la longueur.
- **À la charge de l'application hôte (hors de biotools) :** passer `"DNA"` ou `"PROTEIN"` au filtre
  `compare_alignment`, sans quoi seul X perd sa barre ; gérer les médianes `null` du microarray ;
  surveiller la mémoire pour un FASTA proche de 100 Mo, lu désormais en entier (environ 3 à 4 fois la
  taille du fichier au pic).

## Ordre suivi

1. F1, F3, F4 : digestion de restriction (un même fichier, résultat faux sous le bon nom).
2. F2 : spots invalides du microarray.
3. F5, F6, F7, F8, F9 : microsatellites, palindromes, protéines, PCR avec N, réduction d'alphabet.
4. F10, F11, F13 : plantages et troncature.
5. F12, F14, F15, F16, F17 et les trois points discutés.

Chaque correction viendra avec un test dont la valeur attendue est calculée à la main, qui échoue sur le code
actuel.
