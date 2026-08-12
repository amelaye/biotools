# Compte rendu — Migration de biotools vers Symfony 5 (partie 2 : exécution)

**Date** : 12 août 2026
**Branche** : `develop` (`biotools`) — `symfony5-compat` créée sur `biophp`
**Fait suite à** : [compte_rendu_maj_2026-08-12.md](compte_rendu_maj_2026-08-12.md)
**Périmètre** : exécution du plan de migration Symfony 4.4 → 5.4, résolution des
conflits Composer rencontrés, correctifs minimaux pour que l'application et sa
suite de tests tournent réellement sur Symfony 5.4 / PHP 8.1, et inventaire de la
dette PHP 8 mise au jour (non corrigée à ce stade).

## 1. `../biophp` : branche `symfony5-compat`

| Commit | Changement |
|---|---|
| `be6bc02` | `php: ^8.2` → `^8.1` ; chaque `symfony/*: ^7.4` → `5.4.*` ; `phpunit/phpunit: ^11.5` → `^9.5` (PHP 8.2 requis par PHPUnit 11, et cette dépendance est en `require`, donc tirée par `biotools`) ; `symfony/cache-contracts` (`require-dev`) `^3.0` → `^2.0`. |
| `fcca2cf` | `doctrine/orm: ^3.6` → `^2.14` — découvert en résolvant les dépendances de `biotools` : `doctrine/orm` ≥3.5 exige `symfony/var-exporter ^6.3.9+`, incompatible avec le pin `5.4.*`. `doctrine/orm` 2.14+ garde le support du mapping par attributs PHP 8 déjà utilisé par les entités de `biophp`. |

Aucun fichier de code de `biophp` modifié — uniquement `composer.json`, comme prévu.
`master`/`develop` n'ont pas bougé.

## 2. `biotools` : `composer.json` et chemin du repository `path`

- `"url": "../amelaye-biophp/"` → `"url": "../biophp/"` (le chemin déclaré ne
  correspondait à aucun dossier réel).
- `"amelaye/biophp": "*@dev"` → `"*"` — la contrainte `*@dev` provoquait une
  erreur *"Invalid version string"* dans le plugin Symfony Flex lors de sa
  passe de relock (`Symfony\Flex\Flex->recordOperations`), qui tente de
  normaliser cette chaîne littéralement. `biophp` déclare une version stable
  explicite (`v0.2.0`) dans son propre `composer.json`, donc `*` suffit.
- `symfony.lock` contenait aussi une entrée figée `"amelaye/biophp": {"version":
  "*@dev"}` (cache des recettes Flex, généré lors du tout premier `composer
  require` du projet), qui déclenchait la même erreur. Corrigée en
  `"v0.2.0"` — c'est la version réellement installée.
- Tous les `symfony/*: "4.4.*"` → `"5.4.*"`, `extra.symfony.require` →
  `"5.4.*"`.
- `"php": "^7.1.3"` → `">=7.2.5"` (plancher officiel Symfony 5).
- `"ext-gd": "^7.3"` → `"*"` : cette contrainte de version sur une extension
  PHP n'a pas de sens (`ext-gd` n'a pas de numérotation sémantique alignée sur
  PHP) et bloquait la résolution sous PHP 8.1.
- `"phpunit/phpunit": "7.5.0"` → `"^9.5"` : PHPUnit 7 ne fonctionne pas sous
  PHP 8 ; `^9.5` correspond à ce que `biophp` utilise aussi côté `require`.
- `"jms/serializer-bundle": "^3.5"` → `"^5.5"` : `biophp` v0.2.0 exige `^5.5`,
  et un seul jeu de versions peut être résolu pour tout l'arbre.
- **`sensio/framework-extra-bundle` retiré** : `config/packages/sensio_framework_extra.yaml`
  désactivait déjà `router.annotations`, et les seules annotations utilisées
  dans les contrôleurs sont des `@Route` natives de `symfony/routing`, déjà
  chargées via `config/routes/annotations.yaml`. Bundle mort dans ce projet,
  confirmé après coup par `bin/console debug:router` qui liste bien toutes les
  routes annotées une fois le bundle retiré.
- **`symfony/web-server-bundle` retiré** (`require-dev`) : resté figé à
  `4.4.*`, remplacé par le serveur intégré (`php -S`) ou la CLI Symfony.
- **`behat/*` retiré entièrement** (`behat/behat`, `behat/mink`,
  `behat/mink-browserkit-driver`, `behat/mink-extension`,
  `behat/mink-goutte-driver`, `behat/mink-selenium2-driver`) : aucune
  utilisation réelle dans le projet (pas de `behat.yml`, pas de fichier
  `.feature`, aucune classe `Behat\`/`Mink\` référencée dans `src/` ou
  `tests/`) et `behat/mink-goutte-driver` / `behat/mink-extension` sont
  abandonnés, bloquant la résolution sous Symfony 5 (ils exigent
  `symfony/config` ≤ 4.0). Toolchain mort retiré plutôt que forcé.
- **`geshi/geshi`** : bloqué par une alerte de sécurité (`PKSA-ns3q-qtk3-d35r`,
  CVE-2025-2123, XSS dans `contrib/cssgen.php`). Aucune version corrigée
  n'existe côté `geshi/geshi` (dernière release : celle déjà utilisée). Le
  script vulnérable n'est pas atteignable depuis ce projet (seule la classe
  principale de coloration syntaxique est utilisée, dans `DefaultController`).
  Alerte ignorée explicitement et documentée via `config.audit.ignore` dans
  `composer.json`, avec la justification en commentaire — pas de désactivation
  générale de l'audit de sécurité.
- **`config.allow-plugins`** ajouté (`symfony/flex`, `composer/package-versions-deprecated`)
  : Composer 2.10 bloque désormais l'exécution de plugins non listés
  explicitement.
- Symfony Flex a ensuite **« déballé » automatiquement** les meta-packages
  (`symfony/orm-pack`, `serializer-pack`, `twig-pack`, `debug-pack`,
  `profiler-pack`, `test-pack`) en leurs dépendances directes (`doctrine/orm`,
  `symfony/serializer`, `symfony/twig-bundle`, `symfony/web-profiler-bundle`,
  `symfony/phpunit-bridge`, etc.) — comportement standard de Flex, pas une
  intervention manuelle.

## 3. `config/bundles.php` et `config/packages/`

- Retrait de `SensioFrameworkExtraBundle` et `WebServerBundle`.
- **Retrait imprévu de `CsaGuzzleBundle`** : `cache:clear` échouait après le
  premier `composer update` réussi avec `ClassNotFoundError: Csa\Bundle\GuzzleBundle\CsaGuzzleBundle`.
  Cause : la nouvelle version de `biophp` (`v0.2.0`) n'utilise plus
  `csa/guzzle-bundle` (remplacé par `guzzlehttp/guzzle` utilisé directement
  dans ses adaptateurs `Api/`), alors que `biotools` continuait d'enregistrer
  ce bundle. Le paquet n'était déjà plus une dépendance déclarée nulle part —
  ni dans `biotools`, ni dans la nouvelle `biophp`. Bundle et
  `config/packages/csa_guzzle.yaml` retirés ; aucune classe du projet ne les
  référence (vérifié par recherche).
- `config/packages/sensio_framework_extra.yaml` supprimé.

`src/Kernel.php` **inchangé**, comme prévu au plan : `RouteCollectionBuilder`
reste fonctionnel jusqu'à Symfony 6.

## 4. `composer update` : résolution itérative

Ordre des blocages rencontrés et correctifs, dans l'ordre où Composer les a
signalés (chaque correctif a fait réapparaître le suivant) :

1. `ext-gd ^7.3` invalide → `*`.
2. `jms/serializer-bundle` : conflit `^3.5` (biotools) vs `^5.5` (biophp) →
   aligné sur `^5.5`.
3. `behat/mink-browserkit-driver ^1.3` bloqué par une alerte de sécurité sur
   `symfony/dom-crawler` 2.x/3.x/4.x → tenté `^2.2`, puis conflit avec
   `behat/mink-goutte-driver` (abandonné, exige `~1.2@dev`) → tout le
   toolchain Behat/Mink retiré (inutilisé, cf. §2).
4. `behat/mink-extension` bloquait aussi sur `symfony/config` ≤ 4.0 — résolu
   par le même retrait.
5. `doctrine/orm ^3.6` (biophp) exige `symfony/var-exporter ^6.3.9+` →
   `doctrine/orm` relâché à `^2.14` côté `biophp` (§1).
6. Plugin `symfony/flex` bloqué par `allow-plugins` (Composer 2.10) → ajouté
   explicitement.
7. `symfony.lock` contenait `"*@dev"` en dur pour `amelaye/biophp`, cassant la
   passe de relock de Flex → corrigé en `"v0.2.0"` (§2).
8. `CsaGuzzleBundle` introuvable à l'exécution de `cache:clear` → bundle et
   config retirés (§3).

Après ces corrections, `composer update` aboutit proprement :
`cache:clear` et `assets:install` s'exécutent sans erreur
(`Executing script cache:clear [OK]`).

## 5. Vérifications

- **`bin/console about`** : `Symfony 5.4.53 (env: dev, debug: true)`, PHP
  `8.1.34`. Kernel démarre.
- **`bin/console debug:router`** : les 39 routes de l'application (dont toutes
  les routes annotées `@Route` des contrôleurs `Minitools`, `Sequences`,
  `ChaosGameRepresentation`, `Protein`, `DNAandProteinConvert`) apparaissent —
  confirme que le retrait de Sensio n'a rien cassé côté routing.
- **Suite PHPUnit** (`vendor/bin/phpunit`, PHP 8.1) :
  - D'abord bloquée par une erreur fatale : `Declaration of ...::setUp() must
    be compatible with PHPUnit\Framework\TestCase::setUp(): void` — PHPUnit 9
    impose la signature `setUp(): void`. **13 fichiers de tests** corrigés
    (ajout de `: void`) ; c'était encore le style PHPUnit 7 d'origine.
  - Une dépréciation Symfony 5.1 relevée et corrigée : `config/bootstrap.php`
    appelait `new Dotenv(false)` (constructeur avec booléen, déprécié) →
    `new Dotenv()`.
  - Suite complète ensuite exécutable : **125 tests, 113 assertions, 12
    erreurs, 35 échecs** (détail en §6). Aucune de ces erreurs/échecs n'est
    liée à Symfony 5 ou aux changements de ce compte-rendu — voir §6.
- **Smoke-test HTTP** (`php -S` avec PHP 8.1, `APP_ENV=dev`) :
  - `GET /` → 200, `<title>Welcome!</title>`.
  - `GET /minitools/random-seqs` → 200, `<title>Random sequences</title>`.
  - `GET /minitools/pcr-amplification` → 200, `<title>In silico PCR
    amplification</title>`.
  - `GET /minitools/find-palindromes` → 200, formulaire affiché.
  - `POST /minitools/find-palindromes` (séquence `GAATTC`, min 4, max 10) →
    200, résultat `AATT` bien présent dans la page rendue : la logique métier
    s'exécute de bout en bout (pas de CSRF sur ce formulaire —
    `csrf_protection` n'est pas activé dans `config/packages/framework.yaml`).
  - Aucune page testée n'affiche de trace d'exception.

## 6. Dette PHP 8 mise au jour (non corrigée dans cette étape)

Les 12 erreurs et 35 échecs restants dans la suite PHPUnit ne sont **pas** des
régressions Symfony 5 : ce sont des bugs latents du code applicatif, restés
invisibles sous PHP 7.1 (permissif) et révélés par le passage à PHP 8.1
(nécessaire pour rester dans la plage supportée par Symfony 5.4). Trois
catégories, vérifiées individuellement :

| Catégorie | Exemple | Cause |
|---|---|---|
| Variables non définies devenues bloquantes (23 cas) | `Undefined variable $aPam250Matrix` (`SequenceAlignmentManagerTest`), `$aNucleoObjects` (`SkewsManagerTest`), `$aReductions` (`ReduceProteinAlphabetManagerTest`) | PHP 7 : notice silencieuse. PHP 8 : warning, que `symfony/phpunit-bridge` convertit en échec de test. Fixtures/tests à corriger individuellement. |
| Fonctions string appelées avec un tableau (10 cas) | `strlen(): Argument #1 ($string) must be of type string, array given` (`MicrosatelliteRepeatsFinderManagerTest`) | PHP 7 convertissait silencieusement (avec warning) ; PHP 8 lève un `TypeError`. Les tests visaient à déclencher l'exception métier (`Exception` applicative), pas un `TypeError` PHP natif — la validation d'entrée du code applicatif doit intervenir avant l'appel à la fonction string. |
| Précision des flottants (16 cas) | `ProteinPropertiesManagerTest::testProteinMolecularWeight` : `1947.0699999999997` vs `1947.07` attendu | Format de sérialisation des flottants différent entre PHP 7 et PHP 8 (`serialize_precision`) ; comportement numérique inchangé, seule la représentation textuelle diffère. Tests à ajuster (assertions avec delta, ou arrondi explicite côté code). |
| Fixture manquante (2 cas, `ChaosGameRepresentationManagerTest`) | `Undefined array key "path_graphs"` | Le tableau de fixture du test (propriété `$this->aNucleotidGraph`) est incomplet par rapport à ce que `parameters.yaml` fournit réellement en prod — vérifié : `parameters.yaml` contient bien `path_graphs`, donc l'app elle-même n'est pas affectée, seul le fixture de test est incomplet. |

Ce travail — comparable à ce que `biophp` a déjà traversé lors de sa propre
migration PHP 8 (reconstruction des entités, cf. son
`compte_rendu_maj_2026-08-12_partie5.md`) — est une dette PHP 8 à part entière,
indépendante de la version de Symfony. Il est délibérément **laissé de côté**
pour cette étape (qui portait sur les dépendances et la configuration Symfony),
et documenté ici comme feuille de route pour une prochaine partie.

## État final

- `biotools` tourne sur **Symfony 5.4.53 / PHP 8.1.34** (binaire Homebrew
  `php@8.1`, le PHP par défaut du poste restant 8.5.9, hors plage supportée par
  SF 5.4).
- Kernel, routing (annotations, sans Sensio), formulaires (GET+POST) et rendu
  Twig fonctionnent, vérifiés par smoke-test HTTP réel.
- `amelaye/biophp` résolu via le repository `path` corrigé, sur la branche
  `symfony5-compat` (contraintes élargies, aucun code touché).
- Suite PHPUnit exécutable : 125 tests, 78 passent, 12 erreurs + 35 échecs
  documentés comme dette PHP 8 préexistante (§6), non corrigée ici.
- **Rien n'a été poussé** (`git push`) ni côté `biotools` ni côté `biophp` — les
  changements restent locaux, sur `develop` (biotools, non commité) et
  `symfony5-compat` (biophp, 2 commits locaux).

## Ce qui reste ouvert

- Décider si/quand corriger la dette PHP 8 du §6 (probablement sa propre partie
  de compte rendu, comme ce que `biophp` a fait).
- La branche `symfony5-compat` de `biophp` est un point de passage : à chaque
  étape suivante de `biotools` (SF6 puis SF7), ses contraintes devront être
  réajustées, jusqu'à converger vers `develop`/`master` (déjà en SF7.4).
- Rien n'est commité côté `biotools` à ce stade — à faire valider avant tout
  commit/push.
