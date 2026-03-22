# Refactoring — La Boîte à Chimère
**Date:** 2026-03-22
**Approche choisie:** C — Refactoring en couches incrémentales
**Objectif:** Éliminer la duplication, extraire une couche service, ajouter des tests — sans jamais casser le site entre les phases.

---

## Contexte

Application Symfony 8 / PHP 8.4. Frontend Twig + Tailwind via AssetMapper, Stimulus/Turbo. Pas de tests existants.
Problèmes identifiés : 13 contrôleurs statiques identiques, logique métier dans les contrôleurs, constantes dupliquées, templates quasi-identiques, pattern JS copié-collé.

---

## Phase 1 — Gains immédiats

### 1.1 — `StaticPageController` (remplace 13 contrôleurs)

**Problème :** `JdsController`, `JdrController`, `GnController`, `AssociationController`, `NouvellesController`, `QuiSommesNousController`, `SocietesController`, `EvenementsController`, `MentionsLegalesController`, `ContactController`, `NosSoireeHebController`, `NosSoireeBihebController`, `NosSoireeMensuelleController` — chacun fait uniquement `return $this->render('xxx/index.html.twig')`.

**Pré-condition :** Vérifier avant suppression que **aucun** des 13 templates n'utilise la variable `controller_name` (passée par les contrôleurs actuels mais inutile). Si l'un l'utilise, la supprimer du template avant de supprimer le contrôleur.

**Solution :** Créer `src/Controller/StaticPageController.php` avec un tableau de configuration `route → template`. Chaque route est déclarée avec `#[Route]` dans le contrôleur. Les noms de routes existants (`app_jds`, `app_gn`, etc.) sont conservés à l'identique. Le nouveau contrôleur ne passe aucune variable inutile aux templates.

**Fichiers supprimés :** Les 13 contrôleurs listés ci-dessus.
**Fichiers créés :** `src/Controller/StaticPageController.php`
**Impact templates :** Aucun — les chemins de templates ne changent pas.

---

### 1.2 — Enum `ActivityKind`

**Problème :** La liste de types d'activité est définie à 3 endroits (`HomeController` ligne 32, `Admin\ActivityController` lignes 36 et 81). Les formulaires admin utilisent des libellés français (`'JDS (Jeux de Société)' => 'JDS'`) et une condition `is_admin` pour le type `AG`.

**Nommage :** L'enum est nommé `ActivityKind` (et non `ActivityType`) pour éviter la collision avec la classe de formulaire existante `App\Form\ActivityType`. Les deux classes de même nom dans des namespaces différents produiraient un fatal error dès qu'un fichier importe les deux.

**Solution :** Créer `src/Enum/ActivityKind.php` — backed enum string PHP 8.1 avec méthode `label()` :
```php
enum ActivityKind: string
{
    case JDS      = 'JDS';
    case JDR      = 'JDR';
    case GN       = 'GN';
    case JDF      = 'JDF';
    case AG       = 'AG';
    case PlayTest = 'Play Test';

    public function label(): string
    {
        return match($this) {
            self::JDS      => 'JDS (Jeux de Société)',
            self::JDR      => 'JDR (Jeux de Rôle)',
            self::GN       => 'GN (Grandeur Nature)',
            self::JDF      => 'JDF (Jeux de Figurines)',
            self::AG       => 'AG (Assemblée Générale)',
            self::PlayTest => 'Play Test',
        };
    }

    /** @return string[] */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
```

La validation dans `HomeController` et `Admin\ActivityController` utilise `ActivityKind::values()`. Les formulaires admin utilisent `label()` pour les choix affichés et conservent leur logique `is_admin` pour le cas `AG`.

**Fichiers créés :** `src/Enum/ActivityKind.php`
**Fichiers modifiés :** `src/Controller/HomeController.php`, `src/Controller/Admin/ActivityController.php`

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

**Problème :** Dans 3 fichiers JS (`carousel.js`, `mobile_menu.js`, `reveal.js`), le même pattern est copié :
```js
document.addEventListener('DOMContentLoaded', init);
document.addEventListener('turbo:load', init);
```

**Exclusions explicites :**
- `cookie_consent.js` — utilise un `setTimeout` délibéré et un guard `readyState`, comportement incompatible avec `registerInit`
- `join_panel.js` — utilise un guard `readyState` conditionnel et sépare `bootJoinPanel` (une seule fois) de `initJoinPanel` (à chaque Turbo visit), incompatible avec `registerInit`

**Solution :** Créer `assets/utils/init.js` :
```js
export function registerInit(fn) {
    document.addEventListener('DOMContentLoaded', fn);
    document.addEventListener('turbo:load', fn);
}
```

Uniquement `carousel.js`, `mobile_menu.js` et `reveal.js` migrent vers `registerInit`.

**Cas particulier `reveal.js` :** Ce fichier possède un troisième chemin d'initialisation pour le bfcache (navigation arrière/avant sans Turbo) :
```js
window.addEventListener('pageshow', (event) => {
    if (event.persisted) { initReveal(); }
});
```
Ce listener `pageshow` est conservé tel quel à côté de l'appel `registerInit`. Seules les deux lignes `DOMContentLoaded` + `turbo:load` du bas du fichier sont remplacées par `registerInit(initReveal)`.

**Fichiers créés :** `assets/utils/init.js`
**Fichiers modifiés :** `assets/carousel.js`, `assets/mobile_menu.js`, `assets/reveal.js`
**Fichiers NON modifiés :** `assets/cookie_consent.js`, `assets/join_panel.js`

---

## Phase 2 — Extraction de services

### 2.1 — `CalendarService`

**Problème :** `HomeController::index()` contient toute la logique du calendrier (~100 lignes) : génération de grille, calcul des jours avec activités, types par jour, navigation mois, noms de mois.

**Solution :** Créer `src/Service/CalendarService.php`. Méthode principale :
```php
public function buildCalendarData(
    int $month,
    int $year,
    ?string $filterType,
    \DateTimeImmutable $now   // injecté depuis le contrôleur pour testabilité
): array
```

Le paramètre `$now` est passé par le contrôleur (`new \DateTimeImmutable()`) — cela évite toute dépendance à l'horloge système dans le service, rendant les tests déterministes. `CalendarService` prend `ActivityRepository` en dépendance constructeur.

`HomeController::index()` se réduit à :
1. Lire et valider les query params
2. Créer `$now = new \DateTimeImmutable()`
3. Appeler `CalendarService::buildCalendarData($month, $year, $filterType, $now)`
4. Gérer le login (CSRF, erreurs) — reste dans le contrôleur
5. Rendre la vue

**Fichiers créés :** `src/Service/CalendarService.php`
**Fichiers modifiés :** `src/Controller/HomeController.php`

---

### 2.2 — `UserDashboardService`

**Problème :** `UserDashboardController` a 5 actions avec logique métier inline : changement de mot de passe, d'email, désinscription, suppression de compte, récupération des inscriptions.

**Solution :** Créer `src/Service/UserDashboardService.php`. Dépendances constructeur : `UserPasswordHasherInterface`, `EntityManagerInterface`, `InscriptionRepository`.

Méthodes :
- `changePassword(User $user, string $currentPassword, string $newPassword): void` — valide via `isPasswordValid()`, hashe via `hashPassword()`
- `changeEmail(User $user, string $newEmail): void` — valide format et unicité
- `unregister(User $user, Inscription $inscription): void` — vérifie que l'inscription appartient à l'utilisateur
- `deleteAccount(User $user): void` — interdit si `ROLE_ADMIN`
- `getUpcomingInscriptions(User $user): array` — délègue à `InscriptionRepository`
- `getPastInscriptions(User $user): array` — délègue à `InscriptionRepository`

Le contrôleur garde : validation CSRF, appel service, flash message, redirection.

**Fichiers créés :** `src/Service/UserDashboardService.php`
**Fichiers modifiés :** `src/Controller/UserDashboardController.php`

---

### 2.3 — Nouvelles méthodes `InscriptionRepository`

**Problème :** Les requêtes d'inscriptions futures/passées d'un utilisateur sont construites inline dans `UserDashboardController`. La requête part de l'entité `Inscription` (join vers `Activity`), donc elle appartient à `InscriptionRepository` — pas à `ActivityRepository`.

**Solution :** Ajouter dans `src/Repository/InscriptionRepository.php` :
```php
public function findUpcomingByUser(User $user): array
public function findPastByUser(User $user): array
```

Ces méthodes remplacent le query builder inline du contrôleur et sont appelées depuis `UserDashboardService`.

**Fichiers modifiés :** `src/Repository/InscriptionRepository.php`

---

### 2.4 — Formulaires admin et `ActivityType`

Une fois l'enum disponible (phase 1), le form type admin remplace son tableau hardcodé de choix par une boucle sur `ActivityKind::cases()` utilisant `->label()` pour les libellés affichés. La condition `is_admin` pour le type `AG` est conservée.

**Fichiers modifiés :** form type admin des activités (`src/Form/ActivityType.php` ou équivalent)

---

## Phase 3 — Tests

### 3.1 — Tests unitaires `CalendarServiceTest`

Fichier : `tests/Unit/Service/CalendarServiceTest.php`

`CalendarService` est testé en isolation : `ActivityRepository` est mocké. Le paramètre `$now` est passé explicitement — les tests sont déterministes.

Cas couverts :
- Offset du premier jour correct (lundi = 0 cellules vides, dimanche = 6)
- Nombre total de cellules = offset + jours du mois
- Navigation mois précédent/suivant (décembre → janvier de l'année suivante inclus)
- Noms des 12 mois en français
- Borne minimale : année < 2020 → normalisé à 2020
- Borne maximale : année > 2100 → normalisé à 2100
- Filtre type invalide → ignoré (retourne toutes les activités)
- `$now` passé avec un jour courant → `today` correct dans le résultat

### 3.2 — Tests unitaires `UserDashboardServiceTest`

Fichier : `tests/Unit/Service/UserDashboardServiceTest.php`

`UserPasswordHasherInterface`, `EntityManagerInterface` et `InscriptionRepository` mockés.

Cas couverts :
- `changePassword` : succès, mot de passe actuel incorrect, nouveau mot de passe trop court
- `changeEmail` : succès, format invalide
- `unregister` : succès, inscription appartenant à un autre utilisateur → exception
- `deleteAccount` : succès pour ROLE_USER, exception pour ROLE_ADMIN

### 3.3 — Tests unitaires `ActivityKindTest`

Fichier : `tests/Unit/Enum/ActivityKindTest.php`

Cas couverts :
- `ActivityKind::from('JDS')` → `ActivityKind::JDS`
- `ActivityKind::tryFrom('INCONNU')` → `null`
- `ActivityKind::cases()` → 6 cas
- `ActivityKind::values()` → tableau de 6 strings
- Chaque cas → `label()` retourne une string non vide

### 3.4 — Tests fonctionnels routes statiques

Fichier : `tests/Functional/Controller/StaticPageControllerTest.php`

Data provider avec toutes les routes gérées par `StaticPageController`. Pour chaque route :
- Réponse HTTP 200
- Assertion sur un élément unique à la page (le `<h2>` principal visible dans le contenu) plutôt que sur le `<title>` seul, pour confirmer que le bon template a été rendu

### 3.5 — Tests fonctionnels `HomeController`

Fichier : `tests/Functional/Controller/HomeControllerTest.php`

Cas couverts :
- Page d'accueil sans paramètre → 200
- `?type=JDS` → 200
- `?type=INVALIDE` → 200 (ignoré)
- `?year=1900` → 200 (normalisé à 2020)
- `?year=2200` → 200 (normalisé à 2100)
- `?month=0` → 200 (normalisé à 1)
- `?month=13` → 200 (normalisé à 12)

---

## Contraintes et invariants

- **Aucune route ne change de nom ni d'URL** — zéro impact sur SEO, sitemap, liens existants
- **Aucun template ne change de chemin** — les `{% include %}` et `{% extends %}` existants restent valides
- **Le site doit fonctionner après chaque phase** — les phases sont indépendantes
- **PHP 8.1+ requis** pour les backed enums (satisfait : PHP 8.4)
- **Tests lancés avec** `php bin/phpunit`

---

## Fichiers créés

| Fichier | Phase |
|---|---|
| `src/Controller/StaticPageController.php` | 1 |
| `src/Enum/ActivityKind.php` | 1 |
| `templates/components/_activity_page.html.twig` | 1 |
| `assets/utils/init.js` | 1 |
| `src/Service/CalendarService.php` | 2 |
| `src/Service/UserDashboardService.php` | 2 |
| `tests/Unit/Service/CalendarServiceTest.php` | 3 |
| `tests/Unit/Service/UserDashboardServiceTest.php` | 3 |
| `tests/Unit/Enum/ActivityKindTest.php` | 3 |
| `tests/Functional/Controller/StaticPageControllerTest.php` | 3 |
| `tests/Functional/Controller/HomeControllerTest.php` | 3 |

## Fichiers supprimés

Les 13 contrôleurs statiques listés en section 1.1 (après vérification de `controller_name`).

## Fichiers modifiés

`src/Controller/HomeController.php`, `src/Controller/Admin/ActivityController.php`, `src/Controller/UserDashboardController.php`, `src/Repository/InscriptionRepository.php`, formulaire admin activités, `templates/jds/index.html.twig`, `templates/jdr/index.html.twig`, `templates/gn/index.html.twig`, `assets/carousel.js`, `assets/mobile_menu.js`, `assets/reveal.js`
