# Compte rendu — Migration de biotools vers Symfony 7 (partie 4, finale)

**Date** : 12 août 2026
**Branche** : `develop` (`biotools`) — `biophp` bascule enfin sur sa vraie branche `develop`
**Fait suite à** : [compte_rendu_maj_2026-08-12_partie3.md](compte_rendu_maj_2026-08-12_partie3.md)
**Périmètre** : dernière étape de la montée progressive — Symfony 6.4 → 7.4. Contrairement
aux étapes précédentes, `biophp` n'a plus besoin de branche de compatibilité : sa
propre migration vers SF7/PHP 8.2+ est déjà terminée. Cette étape consiste donc à
faire converger `biotools` vers l'état réel de `biophp`, et à traiter les
incompatibilités de code que cette convergence révèle.

## 1. `../biophp` : retour sur `develop`

`../biophp` a été rebasculé sur sa branche `develop` réelle (`git checkout
develop`), abandonnant les branches `symfony5-compat`/`symfony6-compat` créées
pour les étapes précédentes (laissées en l'état, non supprimées).

**Un correctif de code a été nécessaire, directement sur `develop`** (donc,
contrairement aux étapes précédentes, pas sur une branche jetable) :
`AmelayeBioPHPBundle::getContainerExtension()` avait le même défaut de
signature déjà rencontré en partie 3 (`must be compatible with ...
Bundle::getContainerExtension(): ?ExtensionInterface`). La bascule sur
`develop` l'a fait réapparaître : la suite de tests de `biophp` ne boote jamais
de vrai kernel Symfony, donc ce bug — qui casse tout usage réel du bundle sous
Symfony 6 ou 7 — n'avait jamais été détecté par ses propres tests. Corrigé
(commit `0b1856c`), vérifié : suite `biophp` toujours verte (166 tests, 391
assertions). `develop` est donc désormais 1 commit en avance sur `origin/develop`
et sur `master` — rien poussé, à la discrétion de l'utilisatrice.

## 2. `biotools` : `composer.json`

- Tous les `symfony/*: "6.4.*"` → `"7.4.*"`, `extra.symfony.require` → `"7.4.*"`.
- `"php": ">=8.1"` → `">=8.2"` (exigé par Symfony 7.4 et par `biophp`).
- `"symfony/flex": "^1.3.1"` → `"^2.0"` : `symfony/http-kernel` 7.4 entre en
  conflit avec `symfony/flex` `<2.10`.
- `"doctrine/orm": "^2.20"` → `"^3.6"` : alignement sur la contrainte réelle de
  `biophp` (`^3.6`), qui n'est plus assouplie par une branche de compatibilité.
- `"phpunit/phpunit": "^9.5"` → `"^11.5"` : `biophp` déclare `phpunit/phpunit`
  en `require` (pas `require-dev`), donc cette dépendance est réellement tirée
  par `biotools` — sa vraie contrainte (`^11.5`) s'applique désormais telle
  quelle.
- `"doctrine/annotations"` retiré (ajouté en partie 3 uniquement pour les
  annotations `@Route` en PHPDoc, cf. §3).
- **PHP** : comme `biophp` teste officiellement PHP 8.2 à 8.5 en CI (vérifié
  dans son propre historique), le **PHP par défaut du poste (8.5.9)
  redevient utilisable** pour cette étape — fini le pin sur le binaire Homebrew
  `php@8.1` utilisé pour les étapes SF5/SF6.

## 3. Conversion des annotations `@Route` en attributs PHP

Symfony 7 a retiré le chargeur de routes `type: annotation` (deprecated depuis
6.4) : `bin/console cache:clear` échouait avec *"Cannot load resource
'../../src/Controller/'. Make sure there is a loader supporting the
'annotation' type."* — c'était le chantier explicitement repéré comme
« candidat naturel pour l'étape Symfony 7 » dans la partie 3.

- **19 annotations `@Route` en PHPDoc → attributs `#[Route(...)]`**, dans les 5
  contrôleurs (`ChaosGameRepresentationController` ×1, `DNAandProteinConvertController`
  ×2, `MinitoolsController` ×10, `ProteinController` ×2, `SequencesController` ×4).
  Mécanique et sans risque : même classe `Route` (elle sert à la fois
  d'annotation et d'attribut), mêmes valeurs de chemin et de nom, juste
  déplacée de la docblock vers une ligne d'attribut au-dessus de la méthode.
- `config/routes/annotations.yaml` : `type: annotation` → `type: attribute`
  (fichier conservé sous son nom actuel, changement de contenu uniquement).
- Plus aucun usage d'annotation à l'exécution dans le projet (vérifié par
  recherche) → **`doctrine/annotations` retiré** de `composer.json` (le
  paquet est de toute façon abandonné en amont).

## 4. Doctrine ORM 3.x : mapping par attributs

`doctrine/orm` 3.x a retiré le driver de mapping par annotations. Erreur au
`cache:clear` : *"The annotation driver is only available in doctrine/orm
v2."* — concernait le mapping `App` dans `config/packages/doctrine.yaml`
(`type: annotation`, pour un `src/Entity/` qui n'existe même pas dans ce
projet). Changé en `type: attribute`, comme le fait déjà `biophp` pour ses
propres entités.

`config/packages/doctrine_migrations.yaml` : déjà migré vers `migrations_paths`
en partie 3, aucun changement supplémentaire nécessaire ici.

## 5. Correctifs de code applicatif (signatures de classes)

Deux classes de `biotools` étendent des classes de base Symfony dont la
signature de méthode a évolué :

- `App\Validator\MeltingTemperature::validatedBy()` et
  `App\Validator\SequenceRecognition::validatedBy()` (toutes deux étendent
  `Symfony\Component\Validator\Constraint`) : erreur fatale *"Declaration ...
  must be compatible with Constraint::validatedBy(): string"*. `Constraint`
  déclare désormais un type de retour `string`. Corrigé dans les deux classes
  (`validatedBy(): string`), même schéma que le correctif `biophp` de la
  partie 3 — un changement de contrat entre versions majeures de Symfony, sans
  impact sur le comportement.

## 6. Suite de tests : PHPUnit 9 → 11

`phpunit/phpunit` passe de `^9.5` à `^11.5` (imposé par `biophp`, cf. §2).
Deux incompatibilités mécaniques, distinctes des correctifs Symfony :

- **`MockBuilder::setMethods()` supprimée** (dépréciée depuis PHPUnit 8,
  retirée en 10). 39 occurrences dans 12 fichiers de tests :
  - 24 appels avec liste de méthodes (`->setMethods(['getNucleotids'])`) →
    `->onlyMethods([...])`, équivalent direct.
  - 15 appels sans argument (`->setMethods()`, séquence
    `DistanceAmongSequenciesManagerTest.php` uniquement) → lignes supprimées :
    l'ancien comportement par défaut (« mocker toutes les méthodes ») est déjà
    celui de `getMockBuilder(...)->getMock()` sans appel `onlyMethods()`, donc
    un no-op une fois retiré.
- **`phpunit.xml.dist` migré** vers le nouveau schéma XML (`vendor/bin/phpunit
  --migrate-configuration`) : l'élément `<filter><whitelist>` (retiré en
  PHPUnit 10) devient `<source><include>`.

Après ces deux correctifs, la suite s'exécute proprement (plus d'erreur fatale
ni de configuration dépréciée). Le détail des échecs restants est en §7.

## 7. Dette de test : la même famille, un peu plus visible

**52 tests en échec sur 125** (contre 47 aux étapes SF5/SF6) — même famille de
cause que documentée en partie 2 §6 (code applicatif non validé avant d'appeler
des fonctions PHP natives strictes, ou assertions sensibles à la précision des
flottants), mais **5 cas de plus révélés par le changement de comportement de
PHPUnit 9 → 11**, pas par Symfony :

| Avant (PHPUnit 9) | Après (PHPUnit 11) |
|---|---|
| Un `E_WARNING` PHP (ex. `foreach()` sur `false`, division par zéro) levé pendant un test était converti en exception catchable, satisfaisant `expectException(\Exception::class)`. | PHPUnit 10+ a retiré cette conversion automatique : le warning est désormais rapporté séparément (*"1 test triggered 1 PHP warning"*) et n'est plus catché comme une exception — le test échoue avec *"Failed asserting that exception of type Exception is thrown"*. |

5 tests dans `DistanceAmongSequenciesManagerTest` et `DnaToProteinManagerTest`
tombent dans ce cas (vérifié individuellement : `foreach() argument must be of
type array|object, false given`, `DivisionByZeroError: Division by zero` —
exactement la même catégorie « le code applicatif ne valide pas son entrée
avant d'utiliser une fonction native », juste révélée différemment). À
l'inverse, **2 tests qui échouaient avant repassent au vert**
(`ChaosGameRepresentationManagerTest::testCreateFCGRImageWithOligoLen2/3`) —
non creusé plus avant, poids négligeable, mais peut refléter un changement de
robustesse de `onlyMethods()` par rapport à l'ancien `setMethods()`.

Cette dette reste **volontairement non traitée** dans cette migration, comme
annoncé dans les parties précédentes.

Par ailleurs, PHPUnit 11 signale désormais **26 dépréciations** de type
*"Creation of dynamic property ... is deprecated"* (propriétés de test comme
`$this->apiNucleoMock` jamais déclarées formellement dans la classe — PHP 8.2+)
et **~90 dépréciations PHPUnit** de type *"`->will($this->returnValue(x))` est
déprécié, utiliser `->willReturn(x)`"* (retiré en PHPUnit 12, donc non
bloquant ici). Non corrigées non plus — même logique de dette reportée, à
traiter dans un futur nettoyage dédié plutôt que noyée dans une migration de
version.

## 8. Nettoyage

- `.gitignore` : ajout de `.phpunit.cache/` (nouveau répertoire de cache créé
  par PHPUnit 11) et `/config/reference.php` (fichier d'auto-complétion IDE
  auto-généré par Symfony au premier `cache:clear`, à ne pas committer).
  `FCGR.png` et `config/reference.php`, générés pendant les smoke-tests de
  cette session, supprimés de l'arbre de travail.

## 9. Vérifications

- **`bin/console about`** : `Symfony v7.4.16`, PHP `8.5.9` (binaire système,
  plus besoin de binaire épinglé).
- **`bin/console debug:router`** : les 39 routes toujours présentes, chargées
  par attributs.
- **Suite PHPUnit** : 125 tests, 12 erreurs + 40 échecs (52 au total, détail
  §7), aucune erreur fatale, configuration à jour.
- **Smoke-test HTTP** (`php -S`, PHP système 8.5.9, `APP_ENV=dev`) : `/`,
  `/minitools/random-seqs`, `/minitools/pcr-amplification`,
  `/minitools/show-vendors/EcoRI` (route à paramètre `{enzyme}`, réponse
  JSON), `/minitools/find-palindromes` en GET et POST → **200 partout**,
  résultat `AATT` bien présent sur le POST, aucune trace d'exception dans
  aucune des 5 pages testées.

## État final de la migration (SF4.4 → SF7.4)

- `biotools` tourne sur **Symfony 7.4.16 / PHP 8.5.9** — le PHP par défaut du
  poste, plus aucun besoin de binaire épinglé.
- `amelaye/biophp` résolu directement sur sa branche `develop` réelle (plus de
  branche de compatibilité à maintenir) ; 1 commit de correctif dessus
  (`0b1856c`), pas encore poussé.
- Bundles retirés en cours de route : `sensio/framework-extra-bundle`,
  `symfony/web-server-bundle`, `csa/guzzle-bundle` (orphelin), tout le
  toolchain `behat/*` (inutilisé), `doctrine/annotations` (plus nécessaire
  après passage aux attributs).
- Routing entièrement par attributs PHP natifs ; mapping Doctrine par
  attributs.
- Suite PHPUnit passée de la 7.5.0 (2019, PHP 7) à la 11.5 (support PHP 8.5) ;
  52 échecs documentés comme dette applicative préexistante, indépendante de
  Symfony, non traitée par choix (hors périmètre d'une migration de version).
- Rien n'a été poussé (`git push`), ni côté `biotools` ni côté `biophp` : tout
  le travail des 4 parties reste local, sur `develop` (`biotools`, non
  commité) et `develop` (`biophp`, 1 commit d'avance sur `origin`).

## Ce qui reste ouvert

- La dette de tests cumulée sur les 4 parties (52 échecs/erreurs, + dépréciations
  PHPUnit) mérite sa propre session de nettoyage, indépendante de toute
  migration de version.
- Les branches `symfony5-compat` et `symfony6-compat` de `biophp` peuvent être
  supprimées si elles ne servent plus (elles ne sont plus utilisées par
  `biotools`, qui pointe maintenant sur `develop`) — laissées en l'état, à la
  décision de l'utilisatrice.
- Rien n'est commité côté `biotools` : les 4 parties de ce compte-rendu
  couvrent un unique ensemble de changements non committés, à relire avant
  tout commit/push.
