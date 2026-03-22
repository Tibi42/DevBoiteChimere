# Refactoring — La Boîte à Chimère
**Date:** 2026-03-22
**Approche choisie:** C — Refactoring en couches incrémentales
**Objectif:** Éliminer la duplication, extraire une couche service, ajouter des tests — sans jamais casser le site entre les phases.

---

## Contexte

Application Symfony 8 / PHP 8.4. Frontend Twig + Tailwind via AssetMapper. Pas de tests existants.
Problèmes identifiés : 13 contrôleurs statiques identiques, logique métier dans les contrôleurs, constantes dupliquées, templates quasi-identiques, pattern JS copié-collé.

---

## Phase 1 — Gains immédiats

### 1.1 — `StaticPageController` (remplace 13 contrôleurs)

**Problème :** `JdsController`, `JdrController`, `GnController`, `AssociationController`, `NouvellesController`, `QuiSommesNousController`, `SocietesController`, `EvenementsController`, `MentionsLegalesController`, `ContactController`, `NosSoireeHebController`, `NosSoireeBihebController`, `NosSoireeMensuelleController` — chacun fait uniquement `return $this->render('xxx/index.html.twig')`.

**Solution :** Créer `src/Controller/StaticPageController.php` avec un tableau de configuration `route → template`. Chaque route est déclarée avec `#[Route]` dans le contrôleur. Les noms de routes existants (`app_jds`, `app_gn`, etc.) sont conservés à l'identique.

**Fichiers supprimés :** Les 13 contrôleurs listés ci-dessus.
**Fichiers créés :** `src/Controller/StaticPageController.php`
**Impact templates :** Aucun — les chemins de templates ne changent pas.

---

### 1.2 — Enum `ActivityType`

**Problème :** La liste `['JDS', 'JDR', 'GN', 'JDF', 'AG', 'Play Test']` est définie à 3 endroits :
- `HomeController::index()` ligne 32
- `Admin\ActivityController` lignes 36 et 81

**Solution :** Créer `src/Enum/ActivityType.php` — backed enum string PHP 8.1 :
```php
enum ActivityType: string {
    case JDS = 'JDS';
    case JDR = 'JDR';
    case GN = 'GN';
    case JDF = 'JDF';
    case AG = 'AG';
    case PlayTest = 'Play Test';
}
```

Remplacer toutes les occurrences par `ActivityType::values()` (méthode statique à ajouter) ou `array_column(ActivityType::cases(), 'value')`.

**Fichiers créés :** `src/Enum/ActivityType.php`
**Fichiers modifiés :** `HomeController.php`, `Admin/ActivityController.php`

---

### 1.3 — Composant template `_activity_page.html.twig`

**Problème :** `templates/jds/index.html.twig`, `templates/jdr/index.html.twig`, `templates/gn/index.html.twig` sont identiques à ~85%. Seuls varient : couleur d'accent, icône SVG, texte de description, infos pratiques.

**Solution :** Créer `templates/components/_activity_page.html.twig` acceptant les variables :
- `color` — classe Tailwind de couleur (ex: `text-emerald-400`)
- `icon_path` — chemin SVG (string)
- `heading` — titre de la section principale
- `description_paragraphs` — tableau de chaînes
- `infos` — tableau de `{label, title, subtitle}`
- `other_activities` — tableau de liens vers les autres pages

Les templates `jds/`, `jdr/`, `gn/` conservent leurs blocs Twig (`meta_description`, `og_description`, `page_title`, `page_subtitle`) et incluent le composant via `{% include %}`.

**Fichiers créés :** `templates/components/_activity_page.html.twig`
**Fichiers modifiés :** `templates/jds/index.html.twig`, `templates/jdr/index.html.twig`, `templates/gn/index.html.twig`

---

### 1.4 — Utilitaire JS `registerInit`

**Problème :** Dans 5 fichiers JS (`carousel.js`, `mobile_menu.js`, `reveal.js`, `cookie_consent.js`, `join_panel.js`), le même pattern est copié :
```js
document.addEventListener('DOMContentLoaded', init);
document.addEventListener('turbo:load', init);
```

**Solution :** Créer `assets/utils/init.js` :
```js
export function registerInit(fn) {
    document.addEventListener('DOMContentLoaded', fn);
    document.addEventListener('turbo:load', fn);
}
```

Chaque fichier JS importe `registerInit` et l'utilise à la place des deux `addEventListener`.

**Fichiers créés :** `assets/utils/init.js`
**Fichiers modifiés :** `carousel.js`, `mobile_menu.js`, `reveal.js`, `cookie_consent.js`, `join_panel.js`

---

## Phase 2 — Extraction de services

### 2.1 — `CalendarService`

**Problème :** `HomeController::index()` contient toute la logique du calendrier (100+ lignes) : génération de grille, calcul des jours avec activités, types par jour, navigation mois, noms de mois.

**Solution :** Créer `src/Service/CalendarService.php`. Méthode principale :
```php
public function buildCalendarData(int $month, int $year, ?string $filterType): array
```
Retourne un tableau structuré avec toutes les données nécessaires à la vue. Prend `ActivityRepository` en dépendance.

`HomeController::index()` se réduit à :
1. Lire et valider les query params
2. Appeler `CalendarService::buildCalendarData()`
3. Gérer le login (CSRF, erreurs) — logique déjà courte, reste dans le contrôleur
4. Rendre la vue

**Fichiers créés :** `src/Service/CalendarService.php`
**Fichiers modifiés :** `src/Controller/HomeController.php`

---

### 2.2 — `UserDashboardService`

**Problème :** `UserDashboardController` a 5 actions avec logique métier inline : changer mot de passe, changer email, se désinscrire d'une activité, supprimer le compte, récupérer les inscriptions passées/futures.

**Solution :** Créer `src/Service/UserDashboardService.php` avec les méthodes :
- `changePassword(User $user, string $current, string $new): void` — valide et hashe
- `changeEmail(User $user, string $newEmail): void` — valide le format et l'unicité
- `unregister(User $user, Inscription $inscription): void`
- `deleteAccount(User $user): void`
- `getUpcomingInscriptions(User $user): array`
- `getPastInscriptions(User $user): array`

Le contrôleur garde uniquement : validation CSRF, appel service, flash message, redirection.

**Fichiers créés :** `src/Service/UserDashboardService.php`
**Fichiers modifiés :** `src/Controller/UserDashboardController.php`

---

### 2.3 — Nouvelles méthodes `ActivityRepository`

**Problème :** Les requêtes d'inscriptions futures/passées d'un utilisateur sont construites inline dans `UserDashboardController` (query builder).

**Solution :** Ajouter dans `ActivityRepository` (ou `InscriptionRepository`) :
- `findUpcomingByUser(User $user): array`
- `findPastByUser(User $user): array`

Ces méthodes remplacent le code inline dans le contrôleur et dans `UserDashboardService`.

**Fichiers modifiés :** `src/Repository/ActivityRepository.php` ou `InscriptionRepository.php`

---

### 2.4 — Formulaires admin et `ActivityType`

Une fois l'enum disponible (phase 1), le form type admin qui génère les choix de type d'activité utilise `ActivityType::cases()` au lieu d'un tableau hardcodé.

**Fichiers modifiés :** `src/Form/ActivityFormType.php` (si existant) ou équivalent admin.

---

## Phase 3 — Tests

### 3.1 — Tests unitaires `CalendarServiceTest`

Fichier : `tests/Unit/Service/CalendarServiceTest.php`

Cas couverts :
- Génération correcte de l'offset du premier jour (lundi = 0, dimanche = 6)
- Nombre correct de cases dans la grille
- Navigation mois précédent/suivant (passage d'année inclus)
- Noms des mois en français
- Borne minimale (année < 2020 → 2020)
- Borne maximale (année > 2100 → 2100)
- Filtre par type invalide ignoré

### 3.2 — Tests unitaires `UserDashboardServiceTest`

Fichier : `tests/Unit/Service/UserDashboardServiceTest.php`

Cas couverts :
- Changement de mot de passe : succès, mots de passe différents, trop court
- Changement d'email : succès, format invalide, email déjà utilisé
- Désinscription : succès, inscription appartenant à un autre utilisateur (exception)
- Suppression de compte : admin ne peut pas se supprimer

### 3.3 — Tests unitaires `ActivityTypeTest`

Fichier : `tests/Unit/Enum/ActivityTypeTest.php`

Cas couverts :
- `ActivityType::from('JDS')` → valeur correcte
- `ActivityType::tryFrom('INCONNU')` → `null`
- `ActivityType::cases()` → tous les types présents
- `ActivityType::values()` → tableau de strings

### 3.4 — Tests fonctionnels routes statiques

Fichier : `tests/Functional/Controller/StaticPageControllerTest.php`

Data provider avec toutes les routes gérées par `StaticPageController`. Pour chaque route : réponse 200, `<title>` contient le nom de la page.

### 3.5 — Tests fonctionnels `HomeController`

Fichier : `tests/Functional/Controller/HomeControllerTest.php`

Cas couverts :
- Page d'accueil : réponse 200
- Filtre type valide → 200
- Filtre type invalide → 200 (ignoré silencieusement)
- Paramètres mois/année hors bornes → 200 (normalisé)
- Navigation calendrier → 200

---

## Contraintes et invariants

- **Aucune route ne change de nom ni d'URL** — zéro impact sur les liens existants, SEO, sitemap
- **Aucun template ne change de chemin** — `{% include %}` et `{% extends %}` existants restent valides
- **Le site doit fonctionner après chaque phase** — les phases sont indépendantes
- **PHP 8.1+ requis** pour les backed enums (déjà satisfait : PHP 8.4)
- **Tests lancés avec** `php bin/phpunit`

---

## Fichiers créés

| Fichier | Phase |
|---|---|
| `src/Controller/StaticPageController.php` | 1 |
| `src/Enum/ActivityType.php` | 1 |
| `templates/components/_activity_page.html.twig` | 1 |
| `assets/utils/init.js` | 1 |
| `src/Service/CalendarService.php` | 2 |
| `src/Service/UserDashboardService.php` | 2 |
| `tests/Unit/Service/CalendarServiceTest.php` | 3 |
| `tests/Unit/Service/UserDashboardServiceTest.php` | 3 |
| `tests/Unit/Enum/ActivityTypeTest.php` | 3 |
| `tests/Functional/Controller/StaticPageControllerTest.php` | 3 |
| `tests/Functional/Controller/HomeControllerTest.php` | 3 |

## Fichiers supprimés

Les 13 contrôleurs statiques listés en section 1.1.

## Fichiers modifiés

`HomeController.php`, `Admin/ActivityController.php`, `UserDashboardController.php`, `ActivityRepository.php`, `jds/index.html.twig`, `jdr/index.html.twig`, `gn/index.html.twig`, les 5 fichiers JS.
