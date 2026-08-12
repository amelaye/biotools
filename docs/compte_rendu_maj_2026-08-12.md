# Compte rendu — Migration de biotools vers Symfony 5 (partie 1 : diagnostic et plan)

**Date** : 12 août 2026
**Branche** : `develop`
**Périmètre** : diagnostic du blocage de version entre `biotools` et sa bibliothèque
sœur `amelaye/biophp`, décision de trajectoire, et plan de migration Symfony 4.4 → 5.4.

## 1. Demande initiale

Demande de l'utilisatrice : mettre à jour les dépendances et fichiers de `biotools`
pour Symfony 5 (SF5). `biotools` est actuellement en **Symfony 4.4 / PHP `^7.1.3`**.

## 2. Diagnostic : couplage runtime avec `amelaye/biophp`

| Constat | Détail |
|---|---|
| Dépendance `path` cassée | `composer.json` déclare un repository `path` vers `../amelaye-biophp/`, qui n'existe pas. Le vrai dossier sœur s'appelle `../biophp`. |
| Couplage réel, pas seulement déclaratif | `src/Controller/DefaultController.php` importe directement des classes de `biophp` (`TypeIIbEndonucleaseApi`, `TypeIIbEndonucleaseApiAdapter`, `Sequence`, `DatabaseInterface`, `ProteinInterface`, etc.). Il ne s'agit pas d'une dépendance optionnelle. |
| `biophp` déjà migré, plus loin que SF5 | Le `CLAUDE.md` de `biophp` (mis à jour par l'utilisatrice pendant l'échange) confirme : *« The migration off PHP 7.2/Symfony 4 is complete »* — `composer.json` de `biophp` exige `php: ^8.2` et `symfony/*: ^7.4` sur `master`/`develop`. |
| Conflit de résolution Composer | Composer ne résout qu'une seule version d'un paquet comme `symfony/framework-bundle` pour tout l'arbre de dépendances. Impossible de faire tourner `biotools` en Symfony 5.4 tant que `biophp` exige Symfony `^7.4` — `composer update` échouerait immédiatement. |
| Revenir à un ancien tag de `biophp` : non viable | Le tag `v0.1.14` (celui actuellement figé dans `composer.lock` de `biotools`) ne contient pas encore `TypeIIbEndonucleaseApi`/`TypeIIbEndonucleaseApiAdapter` — ces classes ont été ajoutées après ce tag, pendant la modernisation de `biophp` vers SF6 puis SF7. Revenir à ce tag casserait `DefaultController` (classes manquantes). |
| PHP local trop récent pour SF5.4 | Le PHP par défaut de la machine est **8.5.9**. Symfony 5.4 (LTS, EOL novembre 2025) ne le supporte pas officiellement (plage supportée : 7.2.5–8.1). En revanche, **`php@8.1` est déjà installé via Homebrew** (`/usr/local/Cellar/php@8.1/8.1.34/bin/php`), dans la plage officielle. |

## 3. Décision retenue

Question posée à l'utilisatrice : comment traiter le conflit de version avec
`biophp` (aligner `biotools` sur SF7 tout de suite / cibler SF6 / viser une vraie
étape SF5). Réponse : **vraie étape SF5, montée progressive jusqu'à SF7**.

Conséquence actée : plutôt que de revenir en arrière sur le code de `biophp`
(déjà terminé, stable, avec attributs Doctrine PHP 8 et suite de tests alignée),
on élargit uniquement les **contraintes de version** de son `composer.json` — pas
son code — sur une nouvelle branche dédiée `symfony5-compat`. Le code de la
bundle/extension DI de `biophp` (`AmelayeBioPHPExtension`, `AmelayeBioPHPBundle`)
n'utilise que des API Symfony stables depuis longtemps (`Extension`,
`ContainerBuilder`, `XmlFileLoader`, `PrependExtensionInterface`) ; aucune API
propre à SF6/7 n'y a été repérée pendant la relecture. `biotools` pointera vers
cette branche via son repository `path` (une fois le chemin corrigé).

## 4. Plan d'exécution retenu

### Côté `../biophp` (nouvelle branche, aucun code modifié)
1. Créer la branche `symfony5-compat` depuis `develop`.
2. Dans `composer.json` uniquement : `php: ^8.2` → `^8.1`, chaque `symfony/*: ^7.4`
   → `5.4.*`, `phpunit/phpunit: ^11.5` → `^9.5` (PHPUnit 11 exige PHP 8.2+, et cette
   dépendance est en `require`, donc réellement tirée par `biotools`).
3. Commit sur cette branche seule ; `master`/`develop` ne bougent pas.

### Côté `biotools`
- `composer.json` : `php` → `>=7.2.5` ; chemin du repository `path` corrigé vers
  `../biophp/` ; tous les `symfony/*` → `5.4.*` (y compris `extra.symfony.require`) ;
  retrait de `sensio/framework-extra-bundle` (mort dans ce projet : son annotation
  router est déjà désactivée dans `config/packages/sensio_framework_extra.yaml`,
  et les seules annotations utilisées dans les contrôleurs sont des `@Route`
  natives de `symfony/routing`, déjà chargées via `config/routes/annotations.yaml`) ;
  retrait de `symfony/web-server-bundle` (figé à 4.4.*, remplacé par le serveur
  local de la CLI Symfony).
- `config/bundles.php` : retrait des deux bundles ci-dessus.
- `config/packages/sensio_framework_extra.yaml` : supprimé.
- `src/Kernel.php` : **inchangé** pour cette étape — `RouteCollectionBuilder` reste
  fonctionnel jusqu'à SF6 ; sa modernisation est reportée à l'étape SF6 pour garder
  un diff minimal à chaque étape.
- Contrôleurs / Forms / Validators / Twig extensions / Services : aucun changement
  de code attendu (API stables entre 4.4 et 5.4) ; à confirmer à l'exécution plutôt
  que par relecture exhaustive.

### Vérification
`composer update` avec le binaire PHP 8.1 explicite (pas le PHP 8.5 par défaut),
résolution itérative des conflits de versions restants, `bin/console debug:router`,
suite PHPUnit existante, puis smoke-test HTTP manuel de 2–3 pages (accueil, une
page à contrôleur annoté comme `SequencesController`, un submit de formulaire)
pour confirmer que le routing par annotations et les formulaires fonctionnent
réellement sans Sensio.

## État à ce stade

- **Aucune modification de code effectuée pour l'instant** — ce document couvre le
  diagnostic et le plan approuvés par l'utilisatrice.
- Prochaine partie de ce compte-rendu : exécution du plan ci-dessus (branche
  `symfony5-compat` sur `biophp`, modifications `biotools`, résultat de
  `composer update`, résultat des tests et du smoke-test).

## Ce qui reste ouvert

- Le nom du dossier `docs/` de `biotools` est créé pour la première fois par ce
  compte-rendu — aucune convention préexistante côté `biotools` (contrairement à
  `biophp` qui a déjà plusieurs comptes rendus datés).
- La branche `symfony5-compat` de `biophp` sera un point de passage temporaire :
  à chaque étape suivante de la montée en version de `biotools` (SF6 puis SF7),
  ses contraintes devront être réajustées en conséquence.
