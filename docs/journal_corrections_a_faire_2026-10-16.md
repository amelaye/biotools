# Journal des corrections à faire — relecture biologique du 16 octobre 2026

Suite du plan `plan_corrections_bio_2026-10-16.md` (entièrement réalisé). Trois agents
`bioinfo-reviewer` ont relu les corrections par domaine (lecture seule, calculs faits à la main,
rien n'a été exécuté par eux). Verdict commun : **passe avec réserves**, aucune valeur numérique
des tests n'est fausse, mais il reste des défauts de bords et des libellés trompeurs.

**Règle :** BioPHP est stable et n'est pas modifié. Toutes les corrections ci-dessous se font dans
biotools. Quand un comportement vient de BioPHP, on le comprend d'abord (section « Cause » du point)
et on décide si biotools l'utilise mal ou si c'est une limite à signaler.

**Statut des constats :**
- **Vérifié** : reproduit ou relu dans le code après la relecture des agents.
- **Agent** : constat d'un agent, pas encore revérifié par une exécution.

Cases : `[ ]` à faire, `[x]` fait.

---

## Groupe 1 — résultats faux ou changement silencieux

### [x] J1. `oligoCounts()` fabrique des mots à travers les N — Vérifié (code)
- **Fichier :** `Service/ChaosGameRepresentationManager.php` — `toDnaSequence()` puis `oligoCounts()`.
- **Problème :** tous les non-ACGT sont retirés avant le comptage. `oligoCounts("acgNtt", 2, 1)` donne
  GT = 1, alors que G et T sont séparés par un N : le bon résultat est GT = 0 (AC, CG, TT = 1).
  Mon test `testOligoCountsDelegatesToTheCalculator` affirme la mauvaise valeur, avec un commentaire
  qui la justifie.
- **Cause :** biotools. Le calculateur de BioPHP saute déjà correctement les mots qui contiennent un
  symbole non ACGT ; c'est le retrait préalable qui les recolle.
- **Correction :** compter chaque segment ACGT séparément (`preg_split('/[^ACGT]+/')`) et additionner,
  chaque segment ayant son propre reverse-complément sur deux brins. Pour le CGR (`points()`), ignorer
  les N reste acceptable (BioPHP : un symbole non ACGT « ne déplace rien »).
- **Test :** corriger GT = 0 ; deux brins sur `ACGNTT` : AC 1, CG 2, GT 1, TT 1, AA 1 (à recalculer à
  la main avant d'écrire l'assertion).

### [x] J2. ARN en cadres 4 à 6 — Vérifié (code BioPHP lu)
- **Fichier :** `Service/DnaToProteinManager.php` — `customTreatment()` et `definedTreatment()`.
- **Problème :** pour de l'ARN, `AUG` devient `CUT` au lieu de `CAU` ; les cadres 1 à 3 sont corrects
  (le traducteur normalise U en T), pas les cadres 4 à 6.
- **Cause :** biotools. `compDNA()` / `revCompDNA()` sont des fonctions d'**ADN** (codes dégénérés,
  sans U). BioPHP traite l'ARN à part (`RnaSequence`, avec `toDna()` qui remplace U par T). Biotools
  passe de l'ARN à une fonction d'ADN.
- **Correction :** convertir U en T (`strtr`) en tête des deux traitements, comme le fait `toDna()`.
  Le chemin de secours par expressions régulières (sans traducteur) ne reconnaît pas non plus U : la
  même conversion le corrige.
- **Test :** `definedTreatment(6, 'standard', 'AUGAAAUAG')` : cadre 4 = `LFH`, cadre 5 = `YF`,
  cadre 6 = `IS` (comme `ATGAAATAG`).

### [x] J3. `molarAbsorptionCoefficientOfProt()` : changement d'unité silencieux — Agent
- **Fichier :** `Service/ProteinPropertiesManager.php`.
- **Problème :** l'ancienne signature était `(contenu, masse)`. PHP ignore un argument en trop : un
  appelant qui passe encore la masse reçoit ε en M⁻¹·cm⁻¹ là où il recevait un coefficient massique,
  sans erreur.
- **Correction :** avertissement d'obsolescence (`E_USER_DEPRECATED`) si un second argument est passé,
  qui renvoie vers `massAbsorptionCoefficientOfProt()` ; ou confirmer que le code appelant (hors de ce
  dépôt) est migré.
- **Test :** appel avec un second argument ⇒ avertissement, résultat inchangé.

---

## Groupe 2 — distances, axe, G+C%

### [x] J4. Axe du dendrogramme trompeur — Agent
- **Fichier :** `Service/DistanceAmongSequencesManager.php` (graduations ~l. 387, nœuds ~l. 438).
- **Problème :** un nœud est tracé à `D/2` (correct pour un UPGMA ultramétrique) mais les graduations
  0,1 à 2,0 se lisent comme des distances : une fusion à D = 1,0 tombe sur la graduation « 0,5 ». D de
  Pearson ne dépasse pas 2, donc la moitié droite de l'axe reste vide.
- **Correction :** relibeller l'axe « hauteur UPGMA = D/2 » (ou tracer à D), et préciser que l'échelle
  est logarithmique (`log(h+1)`). C'est la note sur l'axe que le plan demandait au point 3.3.
- **Test :** graduation et position du trait pour la convention choisie.

### [x] J5. Distance de Pearson sur des vecteurs constants — Vérifié (exécuté)
- **Fichier :** `Service/DistanceAmongSequencesManager.php` — `pearsonDistance()` (~l. 532).
- **Problème :** seule `DivisionByZeroError` est interceptée. Mesuré :
  - `[0.1, 0.1, 0.1]` contre `[1, 2, 4]` lève bien l'erreur ;
  - `[0.7, 0.7, 0.7]` contre `[1, 2, 4]` renvoie 0,99999997 **sans erreur**, alors que la distance n'est
    pas définie (même résultat pour 7 valeurs de 1,3).
  Le rapport de l'agent parlait d'un `NAN` : le défaut réel est un nombre plausible, donc plus grave.
- **Cause :** limite numérique de `MathematicsFunctions::PearsonDistance` de BioPHP : variance en un
  seul passage (Σy² − (Σy)²/n), qui donne un bruit d'arrondi (~1e-9) au lieu de 0 sur des décimaux
  constants. BioPHP ne documente `DivisionByZeroError` que pour une série exactement constante.
- **Correction dans biotools :** vérifier que les deux séries ne sont pas constantes (tolérance
  relative) avant d'appeler BioPHP, sinon `DomainException` comme pour les autres distances indéfinies ;
  refuser aussi tout résultat non fini.
- **À signaler côté BioPHP (décision de l'auteur, hors périmètre ici) :** variance en deux passages ou
  tolérance relative dans `PearsonDistance`.
- **Test :** vecteurs constants non entiers (`[0.7,0.7,0.7]`) ⇒ `DomainException`.

### [x] J6. Seuil « au moins 2 mots communs » trop faible — Agent
- **Fichier :** `Service/DistanceAmongSequencesManager.php`.
- **Problème :** avec 2 mots communs, r vaut toujours ±1 (d = 0 ou 2) : « identiques » sur presque
  aucune information. Les paires sont aussi comparées sur des sous-ensembles de mots différents
  (BioPHP écarte les mots à variance nulle séquence par séquence), donc d(A,B) et d(A,C) ne sont pas
  strictement comparables.
- **Correction :** seuil plus sévère (une fraction des 256 mots, ou un minimum fixe, à choisir et à
  justifier), `DomainException` en dessous ; documenter la limite de comparabilité.

### [x] J7. G+C% : dénominateur et docblock — Agent
- **Fichier :** `Service/SequenceManipulationAndDataManager.php`.
- **Problèmes :**
  - un tiret ou un chiffre change maintenant le dénominateur (`AC-GT` : 40 % devient 50 %), non
    documenté ;
  - `NNNN` donne 0, ce qui se lit « 0 % de GC » alors que la valeur est indéfinie ;
  - la docblock affirme que les skews utilisent la même définition, ce qui est faux (ils calculent
    (G−C)/(G+C)) ; le Tm utilise bien `gcFraction`, arrondi à 1 décimale contre 2 ici.
- **Correction :** documenter le comportement des symboles non-base ; retourner `null` (ou un message)
  quand aucune base n'est informative ; corriger la docblock.

---

## Groupe 3 — formulaire d'alignement, classes, FASTA, fixture

### [ ] J8. Formulaire et résultat d'alignement — Agent
- **Fichiers :** `Form/SequenceAlignmentType.php`, `Service/SequenceAlignmentManager.php`.
- **Problèmes :**
  - l'heuristique « nucléique » (seulement A, C, G, T, U, N, X) prend pour de l'ADN un peptide court
    de ces lettres, et ne reconnaît pas un ARN avec codes IUPAC (R, Y, K, M, S, W, B, D, H, V), où U
    n'est alors pas converti ;
  - le résultat vide n'a pas les mêmes clés que le résultat normal : un gabarit Twig en mode strict
    échoue sur `.score` ou `.message`.
- **Correction :** étendre l'alphabet nucléique à l'IUPAC ; renvoyer toujours les mêmes clés
  (`score`, positions, `message`, avec `null` quand il n'y a pas d'alignement) ; si le code appelant
  connaît le type (ADN / protéine), le laisser primer sur l'heuristique.
- **Tests :** `GATTACA` contre `GATTAGA` ; ARN avec codes IUPAC ; clés identiques dans les deux cas.

### [ ] J9. Classes d'acides aminés — Agent
- **Fichier :** `Service/ProteinPropertiesManager.php` — `proteinAminoacidNature1()` et `…Nature2()`.
- **Problèmes :** libellés inexacts dans la classification 2 (S et T sont polaires et non « petits
  apolaires » ; F, Y, M, W ne sont pas « petits » ; Y et W sont à peine hydrophobes). Les résidus hors
  des groupes (minuscules, B, Z, X, U, O, `*`) sont supprimés sans avertissement : la chaîne colorée
  perd des résidus. Les couleurs viennent du minitool d'origine et sont conventionnelles ; ce sont les
  libellés qui sont faux.
- **Correction :** renommer les libellés ; émettre une couleur neutre pour un résidu non classé.
- **Test :** `proteinAminoacidNature2("aX", …)` ne doit plus renvoyer `[]`.

### [ ] J10. Upload FASTA — Agent
- **Fichier :** `Service/FastaUploaderManager.php`.
- **Problèmes :** fins de ligne `\r` seules (ancien Mac) : tout après `>` devient un en-tête, la
  séquence est vide et acceptée avec des comptes nuls ; fichier sans séquence accepté sans erreur ;
  lignes de commentaire `;` non gérées ; concaténation multi-enregistrements non documentée (correcte
  pour la composition en bases, fausse pour des statistiques de dinucléotides) ; les N ne sont pas
  comptés, ce qui biaise un pourcentage calculé comme compte / `$iLength` côté appelant.
- **Correction :** normaliser `\r\n` et `\r`, refuser une séquence vide (« no sequence »), ignorer les
  lignes `;`, documenter la concaténation.

### [ ] J11. Fixture du ver plat — Agent
- **Fichier :** `Tests/Service/samples/TripletsSpecies.php:521-522` : le 14ᵉ triplet est `ATH` au lieu
  de `AAH` (Asn). Sans effet sur la traduction avec traducteur ; seul le chemin de secours est faussé.
- **À faire :** corriger la fixture de test ; **vérifier la copie dans bioapi** (hors de ce dépôt).

---

## Mineur et tests manquants

- [ ] Les erreurs de `proteinIsoelectricPoint()` / `proteinCharge()` sont ré-enveloppées en
  `\Exception($e)` : le message devient une trace et le type est perdu. `proteinMolecularWeight()` ne
  nomme pas le symbole sans poids (`J`).
- [ ] « 7 bp » du CGR compte aussi les espaces et les N (`strlen`).
- [ ] `createGraphForTrinucleo()` instancie son propre calculateur au lieu d'utiliser celui qui est
  injecté.
- [ ] N contre N compte comme un match (+2) en ADN (héritage legacy, discutable).
- [ ] `compareAlignment()` traite toute minuscule comme un flanc : à documenter (n'alimenter qu'avec la
  sortie de `alignDNA()` / `alignProteins()`).
- [ ] `findORF()` non trimmed surligne encore les segments sans Met (comportement legacy documenté).
- [ ] Docblocks : « comme le legacy » pour l'oligo-skew est un peu fort (136 classes repliées contre
  256 mots) ; à k = 1 sur deux brins, la distance ne vaut que 0 ou 2.
- [ ] L'échelle de l'axe de l'oligo-skew change d'un graphe à l'autre (0,8 / 1,6 / 2,4).
- [ ] `displayBothStrands()` : étiqueter les extrémités 5'/3' (la ligne du bas est le complément simple,
  lu 3'→5').
- [ ] Tm : une amorce en minuscules passée directement au service (sans le formulaire) est jugée
  « dégénérée » ; aucune case Tm cochée n'est signalée.
- [ ] Garder dans le dépôt le script Python qui a produit les valeurs Almeida et celles de l'oligo-skew
  à deux brins (les agents ne peuvent pas les vérifier).

**Tests manquants relevés par les agents :** frames 4 à 6 avec le vrai traducteur et une longueur non
multiple de 3 ; traduction en minuscules ; `findOrfRegions()` avec un code non standard sur un vrai
`OrfFinder` (`ATGTGATAG` : Trp sous les tables 2 et 4, stop sous la table 1) ; un flanc vide d'un côté
seulement ; `compareAlignment` avec `-` contre `-` ; invariance par reverse-complément de la courbe
d'oligo-skew ; pH 0 et 14 pour la charge ; pI invariant à l'ordre des clés ; Tm avec amorce dégénérée
et nearest-neighbor coché.

---

## Ordre proposé

1. Groupe 1 (J1, J2, J3), un commit par point avec son test.
2. Groupe 2 (J5, J6, J4, J7).
3. Groupe 3 (J8 à J11), puis les points mineurs rapides.

## Hors périmètre de ce dépôt

- Gabarits et contrôleurs qui consomment les nouvelles clés (`score`, positions, `message`,
  `oligoCounts()`, choix de la distance de l'oligo-skew) et l'appel de l'absorption.
- Données bioapi : tables NCBI 24, 25, 26, 29, 30, 32, 33, et le triplet du ver plat (J11).
- Variance de `PearsonDistance` dans BioPHP (J5), décision de l'auteur de BioPHP.
