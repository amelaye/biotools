# Compte rendu — Transformation de biotools en composant Packagist

**Date** : 24 août 2026
**Dépôt** : `biotools`, branche `develop`
**Fait suite à** : [compte_rendu_maj_2026-08-12_partie4.md](compte_rendu_maj_2026-08-12_partie4.md)
**Périmètre** : complétion du dernier minitool manquant, correction de deux formules
erronées héritées de biophp.org, récupération du code source original pour continuité
de licence, puis extraction du dépôt en composant Packagist `amelaye/biotools`.

## 1. Point de départ et clarification de l'objectif

La demande initiale portait sur des tests fonctionnels Behat, puis a été redirigée :
l'objectif réel est de **faire de biotools un composant Packagist** fournissant des
formulaires à intégrer dans les contrôleurs d'applications tierces, adossé à
`amelaye/biophp` pour ne pas dupliquer les données biologiques. Le point de référence
fonctionnel est le site d'origine des minitools BioPHP.

La cible a été précisée en plusieurs temps au fil de la session :

| Question | Décision |
|---|---|
| Contenu du composant | Forms **+** Managers **+** Validators (composant autonome). Un formulaire seul ne calcule rien ; n'embarquer que les `*Type` aurait obligé chaque appli à réécrire les 18 Managers — exactement la redondance à éviter. |
| Contrôleurs et templates | **Exclus du composant.** Ils appartiennent à l'appli consommatrice. |
| Nom du paquet | `amelaye/biotools` |
| Sort du dépôt courant | **Il devient le composant.** Le site de démo partira dans un dépôt séparé, plus tard. |

## 2. Inventaire des minitools : deux sources, deux comptes

Première comparaison faite sur `easyseq.com/apps/biophp/biophp_minitools/` (miroir daté
du 05/01/2009) : **19 outils**, contre 18 formulaires dans biotools.

Le seul manquant était **`useful_formulas` (Formula functions)**, dans un état
particulier : le service `FormulasManager` (19 méthodes) **et** ses 19 tests existaient
déjà, mais sans formulaire, sans route ni template — un service **orphelin**, jamais
appelé par aucun contrôleur.

Plus tard dans la session, sur demande, l'inventaire a été refait depuis la **source
officielle** `biophp.org/resources.php?mode=minitools` : **20 outils**, soit un de plus
que le miroir easyseq. Le supplémentaire est **`reader_gff_fasta`**.

**Vérification de ce 20e outil** : sa source ne contient **aucune balise `<form>`, aucun
`$_POST` ni `$_FILES`**. C'est une bibliothèque de parsing (classes `Reader`, `fasta`,
`gff`), d'un auteur différent (Eric Aguiar, cebio.org). Ce n'est donc pas un formulaire
manquant : sa place est dans `amelaye/biophp`, aux côtés des parsers GenBank,
Swiss-Prot, EMBL, PDB, PROSITE et ExPASy — biophp n'ayant à ce jour **pas** de parser
GFF. Côté biotools, il n'existait qu'un template `readerGffFasta.html.twig` contenant
« Coming soon :) ».

**Conclusion : les 19 formulaires légitimes sont migrés.**

## 3. Deux formules fausses — héritées de l'original

En comparant `FormulasManager` à l'original, deux calculs sont mathématiquement faux.
Vérification faite sur biophp.org : **le portage est fidèle, c'est la source qui est
erronée**.

| Méthode | Original (= code avant correction) | Valeur correcte | Effet |
|---|---|---|---|
| `centiToFahren()` | `32 + C × 0.555` | `32 + C × 1.8` | 100 °C donnait **87,5 °F** au lieu de 212 °F |
| `mbarToInchHg()` | `mbar × 0.0394` | `mbar × 0.02953` | surestimation d'environ **33 %** |

Dans les deux cas, l'auteur d'origine a utilisé le mauvais facteur : `0.555` (≈ 5/9) est
le facteur Fahrenheit→Celsius appliqué dans le sens inverse, et `0.0394` est le facteur
millimètre→pouce et non millibar→inHg. À noter que `farhenToCenti()` (`0.555 × (F−32)`)
est, elle, **correcte** — c'est bien le même facteur, mais dans le bon sens.

**Décision retenue : corriger.** Chaque divergence est commentée à l'endroit du calcul,
dans le test correspondant, dans `legacy/README.md` et dans le README du composant.

## 4. Complétion du 19e outil

- **`Form/FormulasType.php`** créé : sélecteur groupé en 4 catégories reprenant la
  structure de l'original (Working with DNA / Working with RNA / Temperatures and
  pressure / Quantification of proteins), 21 formules, plus les champs d'entrée
  (séquence, quantité, valeur, masse moléculaire).
- **Deux méthodes ajoutées** à `FormulasManager`, correspondant aux catégories `id11` et
  `id12` de l'original qui n'avaient aucun équivalent :
  - `pmolToMicrogProtein()` — conversions molaires pour les protéines
    (`µg = pmol × kDa / 1000`)
  - `kDaToBasePairs()` — conversions protéine/ADN (`bp = kDa × 1000 / 37`, sur la base
    « 1 kb d'ADN code 333 acides aminés = 3,7 × 10⁴ Da »)
- **`FormulasManagerTest` porté de 19 à 23 tests, tous verts** : les 2 attentes
  invalidées par les corrections ont été mises à jour, 4 tests ajoutés pour les nouvelles
  méthodes, et les **7 assertions à précision flottante tronquée** qui échouaient déjà
  avant ont été réparées (`assertEqualsWithDelta`). Sur la suite globale, les échecs
  passent de 52 à 44.

Un contrôleur et un template avaient été créés dans la foulée, puis **annulés** :
ils n'ont pas leur place dans un composant de formulaires.

## 5. Récupération du code legacy (continuité de licence)

Sur demande, le code source original a été récupéré pour des raisons de droits — même
pratique que le dossier `Legacy/` de biophp.

- **20 sources** téléchargées depuis `biophp.org/minitools/<outil>/index.php?action=download`
  (servies en `code.php.gz`), décompressées dans `legacy/`. 20 récupérées, 0 échec.
- Auteur : Joseba Bikandi. Licence : **GNU GPL v2**, celle sous laquelle le paquet est
  distribué (`GPL-2.0-only`).
- **`legacy/README.md`** documente la provenance, la date de récupération, la table de
  correspondance entre chaque fichier legacy et son couple `*Type` / `*Manager`, et les
  deux divergences assumées — avec la consigne explicite de ne pas « corriger » ces
  fichiers, conservés verbatim comme référence amont.

## 6. Extraction en composant

Structure calquée sur `amelaye/biophp`, déjà un bundle Symfony avec extension DI et
câblage XML : les deux paquets deviennent structurellement identiques.

```
biotools/
├── composer.json                  amelaye/biotools, type symfony-bundle
├── AmelayeBioToolsBundle.php
├── DependencyInjection/           Extension + Configuration
├── Resources/config/
│   ├── services.xml               câblage des 41 classes
│   └── defaults.php               valeurs par défaut de configuration
├── Form/       19 · Service/  18 · Validator/ 4
├── legacy/     20 sources originales + README
├── Tests/ · phpunit.xml · README.md · docs/
```

- **Namespace** : `App\` → `Amelaye\BioTools\`, mappé PSR-4 sur la racine du dépôt,
  comme biophp mappe `Amelaye\BioPHP\`. Déplacements faits en `git mv` pour préserver
  l'historique (`tests` → `Tests` a nécessité un renommage en deux temps, le système de
  fichiers étant insensible à la casse).
- **Câblage** : `services.xml` déclare les 18 Managers (dont 12 à dépendances sur les
  adaptateurs d'API biophp, déjà aliasés côté biophp), les 19 formulaires (tagués
  `form.type`) et les 2 `ConstraintValidator` (tagués `validator.constraint_validator`).
- **Configuration** : les 2 paramètres d'application (`nucleotids_graphs`,
  `protein_colors`) deviennent de la configuration de bundle sous la clé
  `amelaye_biotools`, avec les valeurs actuelles en défaut — extraites
  programmatiquement de `config/parameters.yaml` vers `Resources/config/defaults.php`
  plutôt que retranscrites à la main (20 palettes de couleurs). Le composant fonctionne
  ainsi sans aucune configuration, tout en restant surchargeable. Déclarées en
  `variableNode()` : leurs clés sont des données (lettres d'acides aminés, noms de
  palettes `20`, `Murphy15`, `3IMG`…), pas un schéma qu'un arbre typé décrirait.
- **`generic_colors`**, utilisé uniquement par `App\Twig\AppExtension`, part avec la
  démo et non dans le composant.
- **Sorti de l'arbre de travail** (conservé dans l'historique git pour le futur dépôt de
  démo) : 109 fichiers — `config/` (33), `templates/` (32), `public/` (18), `src/`
  contrôleurs, Kernel et extensions Twig (14), `bin/`, `migrations/`, `translations/`,
  `.env*`, `symfony.lock`, `composer.lock`, les dumps SQL.
- **`composer.json`** réécrit : dépendances réduites au strict nécessaire — `php ^8.2`,
  `ext-gd` (les managers Chaos et Skews produisent des PNG), `amelaye/biophp`,
  `symfony/form`, `symfony/validator`, `symfony/options-resolver`,
  `symfony/http-foundation`, `symfony/http-kernel`, `symfony/dependency-injection`,
  `symfony/config`. Tout le reste (framework-bundle, twig, doctrine, monolog, flex,
  mailer…) relevait de l'application.
- **`composer.lock` retiré et ignoré**, convention de bibliothèque déjà appliquée sur
  biophp.

Un point de vigilance repéré au passage a été traité :
`Tests/Service/samples/TripletsSpecies.php` déclarait `namespace App\DataFixtures` et
importait `App\Entity\TripletSpecie` — une classe **inexistante** (`src/Entity/` était
absent) — ainsi que deux classes Doctrine jamais utilisées. Le fichier n'instancie que
des `TripletSpecieDTO` de biophp : les 3 imports morts ont été supprimés et le namespace
aligné sur celui de ses voisins.

## 7. Vérifications

| Contrôle | Résultat |
|---|---|
| `php -l` sur tous les fichiers déplacés | tous OK |
| Suite de tests | **129 tests, 12 erreurs, 32 échecs** — strictement identique à la référence d'avant extraction : aucune régression |
| `FormulasManagerTest` | **23/23** |
| Compilation du conteneur | **OK** |
| Instanciation des services | **41/41** (après correction d'un bug de biophp, cf. §8) |

Le test d'intégration du câblage a été mené en compilant un vrai `ContainerBuilder`
chargeant les deux extensions (BioPHP et BioTools) puis en instanciant chaque service —
seule façon de prouver que le XML est correct, une erreur de câblage étant invisible
autrement. Il a fallu y injecter un faux service `doctrine` (la `prepend()` de biophp
configure Doctrine ORM) et un vrai `jms_serializer`.

Ce banc de test a fait apparaître un 41e service en échec (`MeltingTemperatureManager`),
d'abord pris pour un artefact du harnais. Investigation faite, c'était un **vrai bug de
biophp** — voir la section suivante.

## 8. Bug découvert et corrigé dans biophp : `ElementDTO`

Le service `MeltingTemperatureManager` échouait sur
`RuntimeException: You must define a type for ElementDTO::$id`. Le diagnostic a
remonté une chaîne complète :

1. **13 des 14 classes d'API** de biophp désérialisent en `'array'` puis hydratent
   leur DTO à la main : JMS n'a besoin d'aucune métadonnée. **`ElementApi::getElement()`
   est la seule** à désérialiser directement dans une classe
   (`deserialize(..., ElementDTO::class, 'json')`), ce qui **exige** des métadonnées de
   type par propriété.
2. C'est précisément pourquoi `ElementDTO` était le seul DTO à porter des annotations
   `@Type` — l'auteur les avait ajoutées pour ce cas.
3. Mais ces annotations étaient en **docblock**, et la migration PHP 8 / Symfony 7 a
   retiré `doctrine/annotations` (absent du `composer.json` de biophp comme du vendor).
   JMS 3.32 ne peut donc plus les lire : **elles étaient devenues silencieusement
   mortes**.
4. Conséquence : `SequenceManager::__construct()` appelle `$elementApi->getElement(6)`
   pour initialiser `$this->water`. **Toute instanciation de `SequenceManager`** — le
   service central des séquences, aliasé `SequenceInterface` — échouait donc en
   application réelle.
5. Le bug était **invisible pour la suite de tests** : tous les tests de
   `SequenceManagerTest` *mockent* `ElementApi`, et le seul test qui empruntait le vrai
   chemin, `ElementApiTest::testgetElement()`, était **commenté** (`/* ... */`).

Bug reproduit isolément dans l'environnement propre de biophp, indépendamment de
biotools, ce qui exclut l'artefact de harnais.

**Correctif appliqué** (2 fichiers dans biophp, non commités) :

- `Api/DTO/ElementDTO.php` : les 3 annotations `@Type` en docblock converties en
  **attributs PHP 8** `#[Type('integer')]`, `#[Type('string')]`, `#[Type('float')]`.
  C'est la direction déjà prise par le projet, dont le mapping Doctrine est passé aux
  attributs lors de la même migration. `JMS\Serializer\Annotation\Type` est déclaré
  `#[\Attribute]`, donc utilisable tel quel.
- `Tests/Api/ElementApiTest.php` : `testGetElement()` **réactivé et réécrit**. Il ne
  pouvait pas fonctionner en l'état — le `MockHandler` de `setUp()` ne fournit qu'une
  réponse *liste*, alors que `getElement()` attend un élément unique. Le test dispose
  désormais de son propre client mocké avec la bonne réponse, et emprunte le vrai
  sérialiseur.

**Vérifications** : correctif prouvé avant/après sur un script isolé ; régression
confirmée attrapée par le test réactivé (retrait temporaire des attributs → le test
échoue) ; suite complète de biophp **231 tests / 626 assertions, tout vert** ; câblage
biotools passé de 40/41 à **41/41**.

## 9. Modernisation des graphiques : GD → SVG

Les quatre graphiques (CGR, FCGR, skews, dendrogramme) étaient produits par **119 appels
à GD** dans 9 méthodes de 3 managers : bitmap à taille fixe, texte tracé par
`imagestring()` avec les polices bitmap intégrées de GD. Trois défauts structurels s'y
ajoutaient, aggravés par le passage en composant.

### Défauts constatés — et démontrés

- **`SkewsManager` ignorait la configuration** : il écrivait en dur dans
  `"created_files/"`, chemin **relatif au répertoire courant du processus**, alors que
  `ChaosGameRepresentationManager` utilisait bien `path_graphs`. Il ne recevait même pas
  la config. Vérifié en conditions réelles : `createImage()` retournait `"demo_skews.png"`
  comme si tout allait bien, alors qu'**aucun fichier n'était écrit** — `imagepng()`
  échouait en silence faute de répertoire.
- **Noms de fichiers fixes** (`CGR.png`, `FCGR.png`, `dendogram.png`) : deux requêtes
  simultanées s'écrasaient mutuellement.
- **`createCGRImage()` ne retournait rien** : l'appelant devait deviner le chemin.

### Ce qui a été fait

- **`Service/Graphics/SvgCanvas.php`** : petit canevas dont les méthodes remplacent les
  primitives GD une pour une (`rect`, `line`, `pixel`, `text`, `save`), ce qui a rendu la
  conversion mécanique. Trois points de soin : police **monospace** obligatoire (le code
  d'origine positionne ses libellés en supposant des glyphes à largeur fixe), décalage
  vers la **ligne de base** (GD place le texte par son coin haut-gauche, SVG par la
  ligne de base), et **échappement XML** de tout texte — les noms de séquence viennent de
  la saisie utilisateur et un SVG embarqué inline serait sinon un vecteur d'injection.
- **Les 3 managers convertis**, logique de calcul inchangée. Le tableau de coordonnées de
  la carte d'image FCGR, indépendant du moteur de rendu, est conservé tel quel.
- **Sorties assainies** : `SkewsManager` reçoit désormais `nucleotids_graphs` (câblé dans
  `services.xml`), les noms portent un suffixe aléatoire, chaque méthode **retourne le
  chemin écrit**, et un répertoire absent ou non inscriptible lève une **exception
  explicite** au lieu d'un échec silencieux.
- **`createFCGRImage()` retourne désormais `['map' => [...], 'file' => '...']`** — seul
  contrat testé modifié, 3 assertions adaptées.
- **`ext-gd` retiré** de `composer.json` : plus aucun appel GD dans le composant. C'est
  un allègement réel, GD n'étant pas toujours compilé. À noter au passage que
  `imagedestroy()` est **déprécié depuis PHP 8.5** — la sortie de GD était de toute façon
  à prévoir.

### Optimisation de la taille des dessins

Un nuage de points naïf produit un élément SVG par point. Mesuré sur une séquence de
420 bases : **421 `<rect>` pour seulement 77 points distincts**, soit 82 % de doublons —
et le ratio croît avec la longueur. Ajout de `SvgCanvas::pixelCloud()`, qui déduplique
puis fusionne le nuage en un seul `<path>` :

| Graphique | Avant | Après | Gain |
|---|---|---|---|
| CGR | 25 362 o | 1 337 o | **19×** |
| Skews | 102 324 o | 24 629 o | **4×** |

### Vérifications

- Plus aucun appel GD (motif de recherche tolérant aux espaces — un premier passage avait
  laissé 17 `imageline (` avec une espace avant la parenthèse, que le grep initial et le
  regex de conversion avaient tous deux manqués).
- Suite de tests : **129 tests, 12 erreurs, 32 échecs**, liste strictement identique à la
  référence d'avant conversion. Aucune régression.
- Câblage : **41/41** après l'ajout de l'argument de constructeur de `SkewsManager`.
- **Contrôle visuel** : les quatre graphiques ont été générés en PNG avec le code
  d'origine, puis en SVG après conversion, et comparés. Rendus fidèles — mêmes cellules,
  mêmes gris, mêmes comptages, mêmes positions de texte et d'échelles — avec un texte
  nettement plus net et lisible à tout niveau de zoom.

## 10. Remise à zéro de la suite de tests

Objectif fixé : **plus aucune erreur**, et déterminer pour chaque échec s'il vient du
code porté ou du legacy — les 20 sources originales étant disponibles dans `legacy/`,
la comparaison a pu être faite pièce par pièce.

Point de départ : 129 tests, **12 erreurs et 32 échecs**.

### Défauts côté tests (26 corrigés)

| Cause | Nombre | Détail |
|---|---|---|
| `require_once` dans `setUp()` | **10 erreurs** | PHPUnit tournant dans un seul processus, le fichier de fixtures était déjà chargé par une autre classe : le `require_once` ne faisait rien, la variable n'existait pas dans le scope, et le mock renvoyait `null`. Exactement les 3 fichiers utilisant `require_once` produisaient ces 10 erreurs. |
| `OligosManager` intégralement mocké | **2 erreurs** | `DistanceAmongSequenciesManagerTest` mockait le service sans stubber `findOligos()`, qui renvoyait donc du vide — alors que le test attendait ensuite des fréquences précises. Le test se sabordait : il n'exécutait aucun calcul. Remplacé par une vraie instance, comme le faisait déjà `SkewsManagerTest`. |
| Attentes flottantes tronquées | **14 échecs** | Valeurs écrites à 13 chiffres significatifs (`2.0869565217391`) contre `2.0869565217391304` obtenu. Converties en `assertEqualsWithDelta`. |

Une fois `OligosManager` réellement instancié, les fréquences calculées correspondent aux
attentes à 13 chiffres significatifs : **les calculs métier sont justes.**

### Défauts hérités du legacy (19 échecs)

Les 19 échecs restants avaient tous la même forme : un test passe une entrée invalide
(un tableau là où une chaîne est attendue) et attend une `\Exception` métier ; PHP 8 lève
un `TypeError` avant que le code n'ait rien pu vérifier.

Vérification faite sur les sources originales : **aucune des 20 ne contient `is_string(`,
`is_array(` ni `throw new`.** Zéro validation d'entrée, zéro exception dans tout le
corpus. Le portage est fidèle — `find_palindromic_seqs` appelle `strlen($seq)` sans
vérification, exactement comme sa version modernisée. Les `try/catch(\Exception)` ajoutés
au portage ne pouvaient rien attraper, `TypeError` étendant `\Error` et non `\Exception`.

**Ces 19 tests n'avaient donc jamais rien vérifié.** Sous PHP 7 et PHPUnit 9, passer un
tableau à `strlen()` émettait un *warning* que PHPUnit convertissait automatiquement en
exception, ce qui satisfaisait `expectException(\Exception::class)`. Ils étaient verts
pour une mauvaise raison ; PHPUnit 10 a supprimé cette conversion et révélé qu'ils ne
testaient rien.

**Décision : ajouter la validation.** 19 méthodes publiques valident désormais leurs
arguments et lèvent une `\Exception` explicite. Les tests passent pour la bonne raison,
et le composant ne remonte plus de `TypeError` depuis ses entrailles — ce qui compte pour
une bibliothèque destinée à être publiée. La divergence avec le legacy est documentée.

### Nettoyage du bruit

- `->will($this->returnValue(x))` → `->willReturn(x)` : **25 conversions** (retiré en
  PHPUnit 12).
- **24 propriétés dynamiques déclarées** (dépréciées depuis PHP 8.2).
- 3 annotations `@test` / `@expectedException` obsolètes retirées des docblocks.

### Résultat

**129 tests, 133 assertions, 0 erreur, 0 échec, 0 dépréciation.** Seul subsiste
l'avertissement « No code coverage driver available », purement environnemental (xdebug
non installé), déjà rendu non bloquant par `failOnPhpunitWarning="false"`.

Câblage revérifié à **41/41** et génération des 4 graphiques toujours fonctionnelle après
l'ajout des gardes.

### Compte rendu pour les auteurs du legacy

Les défauts constatés dans les sources originales sont consignés séparément, en anglais,
dans **[defauts_legacy_biophp_minitools.md](defauts_legacy_biophp_minitools.md)** : les
deux formules fausses, la division par zéro non protégée, l'absence totale de validation
d'entrée, `imagedestroy()` déprécié depuis PHP 8.5, et une coquille d'unité dans une
table de référence. Chaque point est cité depuis la source originale.

## État final

- Le dépôt est devenu le composant **`amelaye/biotools`** : 19 formulaires, 18 managers,
  4 validators, bundle Symfony complet, 20 sources legacy documentées, README
  d'installation et d'usage.
- Suite de tests : **129 tests, 0 erreur, 0 échec, 0 dépréciation**.
- Les graphiques sont produits en **SVG**, sans dépendance à `ext-gd`, avec écriture
  fiable (chemin configuré, noms uniques, erreurs explicites).
- **Aucun commit** n'a été fait : la restructuration (109 suppressions, 71 renommages,
  6 ajouts) est laissée à la relecture.
- Les dates « Last modified » ont été mises à jour sur les **41 fichiers source** portant
  cette convention. Les fichiers de `Tests/` n'ont aucun docblock d'en-tête ; aucun n'a
  été inventé.

## Ce qui reste ouvert

- **Dépendance non figée** : `composer.json` requiert `amelaye/biophp: "@dev"`, le
  repository `path` exposant `dev-develop` depuis que biophp n'a plus de champ
  `version`. À figer sur un tag avant publication sur Packagist.
- **Site de démo** : contrôleurs, templates et configuration sont sortis de l'arbre mais
  restent récupérables dans l'historique git pour monter le dépôt séparé.
- **Parser GFF** : `legacy/reader_gff_fasta.php` attend d'être modernisé côté biophp,
  aux côtés des autres parsers de bases de données.
- **biophp** : le correctif `ElementDTO` + test réactivé (§8) est **non commité** dans
  `../biophp`, sur la branche `develop`. À relire et committer séparément.
  *Audit complémentaire effectué* : recherche de tout autre test désactivé dans biophp
  (blocs de test en commentaire détectés par analyse des tokens PHP, `markTestSkipped`,
  `markTestIncomplete`) — **aucun autre**. Le cas `ElementApiTest` était isolé.
- **Dette de tests** : les 44 échecs/erreurs restants sont la dette PHP 8 préexistante
  documentée dans les comptes rendus du 12 août, inchangée et toujours non traitée.
- **Tests fonctionnels Behat** : la demande d'origine de la session, jamais abordée —
  le toolchain Behat avait d'ailleurs été retiré lors de la migration Symfony 5 faute
  d'utilisation réelle (aucun fichier `.feature`, aucun `behat.yml`).
