# Compte rendu — Migration de biotools vers Symfony 6 (partie 3)

**Date** : 12 août 2026
**Branche** : `develop` (`biotools`) — `symfony6-compat` créée sur `biophp` (depuis `symfony5-compat`)
**Fait suite à** : [compte_rendu_maj_2026-08-12_partie2.md](compte_rendu_maj_2026-08-12_partie2.md)
**Périmètre** : montée Symfony 5.4 → 6.4 (LTS), sur le même principe que l'étape
précédente — nouvelle branche de compatibilité côté `biophp`, contraintes et
config Symfony côté `biotools`, correctifs des incompatibilités réelles
rencontrées à l'exécution.

## 1. `../biophp` : branche `symfony6-compat`

Créée depuis `symfony5-compat` (elle-même laissée intacte).

| Commit | Changement |
|---|---|
| `0b6539c` | Chaque `symfony/*: 5.4.*` → `6.4.*` ; `symfony/cache-contracts` (`require-dev`) `^2.0` → `^3.0` (SF6/7 utilisent les contracts v3). `php` reste `^8.1` (déjà dans la plage supportée par SF 6.4, qui exige PHP ≥8.1). |
| `4a83f93` | **Correctif de code réel** (pas seulement une contrainte) : `AmelayeBioPHPBundle::getContainerExtension()` déclarée sans type de retour, alors que `Symfony\Component\HttpKernel\Bundle\Bundle` (classe parente) déclare `: ?ExtensionInterface` depuis SF6. PHP impose la compatibilité de signature sur les méthodes surchargées → erreur fatale au chargement du kernel sans ce correctif. Signature alignée (`getContainerExtension(): ?ExtensionInterface`), aucun changement de comportement. |
| `8e8086b` | Retrait d'un `.claude/settings.json` commité par erreur (config locale de l'outil, pas du code du projet) — sans rapport avec la migration. |

C'est la première fois de cette migration qu'un vrai fichier de code de `biophp`
a dû être modifié, et non plus seulement son `composer.json` — attendu : les
changements de contrat d'API entre versions majeures de Symfony peuvent forcer
des ajustements de signature, même sur du code par ailleurs stable.

## 2. `biotools` : `composer.json`

- Tous les `symfony/*: "5.4.*"` → `"6.4.*"`, `extra.symfony.require` → `"6.4.*"`.
- `"php": ">=7.2.5"` → `">=8.1"` (Symfony 6 exige PHP 8.1 minimum).
- `"doctrine/doctrine-migrations-bundle": "^2.2"` → `"^3.2"`, `"doctrine/migrations": "^2.2"` → `"^3.5"` :
  la 2.x de `doctrine-migrations-bundle` plafonne à `symfony/framework-bundle`
  `~5.0`, incompatible avec `6.4.*`.
- `"doctrine/annotations": "^2.0"` ajouté explicitement : Symfony 6 ne le tire
  plus automatiquement (il a disparu de la résolution lors du premier
  `composer update`), mais les contrôleurs utilisent toujours la syntaxe
  PHPDoc `@Route(...)` (pas les attributs PHP 8 `#[Route(...)]`), qui en
  dépend à l'exécution pour le chargeur de routes annotées. Convertir les 19
  annotations `@Route` en attributs PHP aurait aussi résolu le problème, mais
  aurait touché 5 fichiers de contrôleurs pour un gain nul à cette étape — reporté.
- `"phpdocumentor/reflection-docblock": "^6.0"` → `"^5.2"` : `symfony/property-info`
  6.x n'est pas compatible avec `reflection-docblock` 6.x (erreur explicite au
  chargement : *"symfony/property-info v6 does not support
  phpdocumentor/reflection-docblock v6. Please stick to ^5.2"*).

## 3. `biotools` : `config/`

- **`config/packages/doctrine_migrations.yaml`** : `dir_name`/`namespace`
  (retirés de `doctrine-migrations-bundle` 3.x) → `migrations_paths:
  {'DoctrineMigrations': '%kernel.project_dir%/src/Migrations'}`.
- **`src/Kernel.php`** modernisé (chantier explicitement reporté à cette étape
  depuis la partie 2) : `RouteCollectionBuilder`, retiré en Symfony 6, remplacé
  par la configuration par callables introduite en 5.3 —
  `configureContainer(ContainerConfigurator $container)` et
  `configureRoutes(RoutingConfigurator $routes)`, avec les mêmes chemins/glob
  qu'avant. Les deux `setParameter('container.dumper.inline_class_loader'
  /'inline_factories', ...)` ont été retirés : c'étaient des réglages
  d'optimisation pour PHP <7.4, inertes ici et absents du squelette Symfony
  6 actuel.

## 4. Correctif de code applicatif : `$this->get('form.factory')`

Après résolution des dépendances, `cache:clear` passait mais les pages à
formulaire renvoyaient une **erreur 500** :
*"Attempted to call an undefined method named "get" of class
"App\Controller\SequencesController". Did you mean to call
"getSubscribedServices"?"*

Cause : `AbstractController::get()` (accès direct au conteneur pour un service
listé dans `getSubscribedServices()`) a été **supprimé** en Symfony 6 — plus
seulement déprécié. Le motif `$this->get('form.factory')->create(XxxType::class)`
était utilisé **18 fois dans 5 contrôleurs** (`ChaosGameRepresentationController`,
`DNAandProteinConvertController`, `MinitoolsController`, `ProteinController`,
`SequencesController`), toujours de façon identique. Remplacé partout par le
helper `$this->createForm(XxxType::class)`, déjà disponible sur
`AbstractController` depuis Symfony 2 et qui fait exactement ça — changement
mécanique, aucun comportement modifié.

## 5. Vérifications

- **`bin/console about`** : `Symfony 6.4.43`, kernel démarre.
- **`bin/console debug:router`** : les 39 routes toujours présentes.
- **Suite PHPUnit** : **125 tests, 12 erreurs, 35 échecs — strictement le même
  jeu de tests en échec qu'à l'étape Symfony 5** (diff des deux listes :
  identique). Confirme que la dette PHP 8 documentée en partie 2 §6 est
  inchangée et qu'aucune régression n'a été introduite par la montée vers SF6.
- **Smoke-test HTTP** (`php -S`, PHP 8.1, `APP_ENV=dev`) :
  - Avant le correctif `createForm()` : `/`  → 200, mais toutes les pages à
    formulaire (`random-seqs`, `pcr-amplification`, `find-palindromes` GET et
    POST) → **500**, confirmant l'incompatibilité `AbstractController::get()`.
  - Après correctif : les 4 pages → 200, POST sur `find-palindromes` (séquence
    `GAATTC`) → résultat `AATT` bien présent dans la page rendue.

## État final

- `biotools` tourne sur **Symfony 6.4.43 / PHP 8.1.34**.
- `amelaye/biophp` résolu via la branche `symfony6-compat` (contraintes
  élargies + un correctif de signature réel, documenté et minimal).
- Suite PHPUnit : même état qu'en partie 2 (125 tests, 78 passent, 47
  échecs/erreurs = dette PHP 8 préexistante, toujours non traitée dans cette
  étape).
- Rien n'a été poussé (`git push`) ni côté `biotools` ni côté `biophp`.

## Ce qui reste ouvert

- Toujours la dette PHP 8 du compte-rendu partie 2 (§6), inchangée.
- Les annotations `@Route` en PHPDoc (19 occurrences, 5 contrôleurs) pourraient
  être converties en attributs PHP `#[Route(...)]` pour retirer la dépendance à
  `doctrine/annotations` (abandonné en amont) — pas bloquant, candidat naturel
  pour l'étape Symfony 7.
- La branche `symfony6-compat` de `biophp` reste un point de passage : à
  l'étape Symfony 7, ses contraintes convergeront enfin vers celles de
  `develop`/`master` (déjà en SF 7.4) — ce sera probablement la première étape
  où plus aucun élargissement de contrainte ne sera nécessaire côté `biophp`.
