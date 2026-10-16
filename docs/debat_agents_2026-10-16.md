# Débat entre agents biologistes — choix de conception du 16 octobre 2026

Après la deuxième passe de relecture (`journal_corrections_a_faire_2026-10-16.md`), onze choix de
conception restaient discutables. Deux agents `bioinfo-reviewer` en lecture seule les ont examinés avec des
rôles opposés : un **défenseur** et un **contradicteur**. Les agents ne disposent pas d'outil de messagerie ;
les arguments de chacun ont été relayés à l'autre, en deux tours. Au second tour, ils devaient lire le code
avant de répondre et citer fichier et ligne.

**Règle :** BioPHP et bioapi sont stables et ne sont pas modifiés. Seuls les choix de biotools sont jugés.

**Limite de la méthode :** au premier tour, les deux agents avaient lu très peu de code (le défenseur le
reconnaît). Les conclusions ci-dessous reposent sur le second tour. Les lignes citées n'ont pas été
relues par l'auteur de ce document.

## Désaccord de fait tranché

**Q1, distance Pearson ou Almeida pour l'oligo-skew.** Le contradicteur affirmait qu'Almeida est la méthode
d'origine. D'après `legacy/skews.php` :
- le chemin par défaut (4-mers, deux brins) appelle `Pearson_distance_136`, qui renvoie `1 - r` ;
- le chemin d'un brin appelle `Pearson_distance` (`1 - r`) ;
- le chemin générique à deux brins appelle `distance()` (Almeida), qui n'est définie nulle part : erreur
  fatale, il n'a jamais produit de résultat.

Pearson par défaut reproduit donc le legacy ; Almeida reste une option. Le contradicteur a restreint son
objection (« vraie seulement pour le chemin générique ») et demande que le changement figure au CHANGELOG, ce
qui est déjà le cas.

## Positions finales

| # | Sujet | Défenseur | Contradicteur | Décision proposée |
|---|---|---|---|---|
| Q1 | Pearson par défaut, axe par paliers de 0,8 | Garder ; concède que l'échelle variable gêne la comparaison | Documenter | Garder ; documenter l'axe (option : axe fixe 0 – 2 ou maximum affiché) |
| Q2 | Mots communs ≥ 3 et ≥ 50 % | Garder ; seuils sans source, plancher de ~10 plus honnête | Documenter les seuils ; une paire indéfinie fait échouer toute la matrice | Documenter les seuils comme heuristiques ; **traiter l'échec de la matrice** (point 3) |
| Q3 | N contre N = +2 en ADN | **Changer** | **Changer** | **Changer** (point 1) |
| Q4 | Absorption `floor(C/2)` | Garder ; ajouter la valeur à Cys réduites | Garder | Garder ; ajouter la valeur réduite (point 4) |
| Q5 | Masse : borne haute pour B, Z, X | **Changer** : exposer l'intervalle | Documenter + exposer l'intervalle si B/Z/X | **Exposer l'intervalle** (point 2) |
| Q6 | G+C% de `gcFraction`, `null` si indéfini | Garder | Garder | Garder |
| Q7 | `findORF` trimmed : taille de la Met au stop | Garder ; codons d'initiation alternatifs à documenter | Garder | Garder |
| Q8 | Nœud à D/2, graduations en D, axe logarithmique | Garder ; axe hérité du legacy | Documenter (objection « hors canevas » retirée) | Garder (légende déjà ajoutée) |
| Q9 | FASTA : enregistrements concaténés | Documenter ou séparer par un N | Documenter (objection retirée : seul le comptage de bases est fait) | Documenter (déjà dans la docblock) |
| Q10 | `oligoCounts` par segments | Garder | Garder | Garder |
| Q11 | Codon ambigu : acide aminé seulement si tous les codons s'accordent, sinon X | Garder | Garder | Garder ; documenter la perte d'information (B, Z, J) |

## Arguments retenus

**Q2, 0 comme valeur de remplacement.** Le contradicteur proposait de remplacer par 0 le z-score d'un mot
absent. Après lecture de `OligosManager::findZScore` (BioPHP), les deux s'accordent : un mot absent a une
variance nulle, donc un z-score **indéfini** ; le remplir par 0 dirait « observé égal à l'attendu » et rendrait
les séquences artificiellement semblables. Proposition retirée.

**Q2, la matrice.** `computeDistancesAmongFrequencies()` relance l'exception à la première paire indéfinie :
avec 10 séquences, une séquence trop courte bloque les 44 autres paires. Le plan (point 1.1) prévoyait
d'exclure la séquence et de prévenir l'utilisateur ; l'implémentation actuelle lève une exception qui nomme
la paire.

**Q3.** `NucleotideAmbiguityScoring` de BioPHP donne la valeur « ambiguë » (0 par défaut) à N contre tout, gère
les codes IUPAC, X et U, et prend `(match, mismatch, ambiguë)`. Biotools peut l'utiliser sans toucher BioPHP.
Un N contre N à +2 donne +100 à deux plages de 50 N, aussi bon que 50 vrais appariements.

**Q5.** `ProteinManager::molwt()` renvoie `[bas, haut]` ; biotools garde le haut. Pour X, la borne haute est
Trp (186 Da) contre environ 128 en moyenne : +58 Da par X. ProtParam refuse B, Z, X.

## Points à traiter (par priorité)

1. **N contre N en ADN** *(fait)* : `NucleotideAmbiguityScoring(2, -1, 0)` à la place de `SimpleMatchMismatchScoring`.
2. **Masse avec B, Z, X** *(fait : `proteinMolecularWeightRange()`)* : exposer l'intervalle `[min, max]` ; garder la méthode existante.
3. **Matrice de distances** *(décision du mainteneur : on garde l'exception qui nomme la première paire indéfinie)* : nommer toutes les paires indéfinies, ou exclure la séquence fautive (décision de
   conception, voir le plan 1.1).
4. **Valeur d'absorption à Cys réduites** *(fait : `molarAbsorptionCoefficientOfReducedProt()`)*, comme ProtParam.
5. **Documenter** *(fait, dans les docblocks de `DistanceAmongSequencesManager`, `SkewsManager` et `DnaToProteinManager`)* : seuils 3 et 0,5 (Q2), échelle variable de l'axe de l'oligo-skew (Q1), codons d'initiation
   alternatifs (Q7), perte d'information des codons ambigus (Q11).

## Hors périmètre

BioPHP et bioapi (stables). Les gabarits et contrôleurs qui consomment les résultats de biotools.
