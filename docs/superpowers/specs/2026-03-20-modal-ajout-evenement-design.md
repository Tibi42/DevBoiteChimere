# Modal d'ajout d'événement depuis le calendrier

**Date :** 2026-03-20
**Projet :** La Boîte à Chimère — Symfony 8.0

---

## Contexte

Le calendrier public affiche les activités du mois. Les utilisateurs connectés doivent pouvoir proposer ou créer un événement directement en cliquant sur une date (aujourd'hui ou future) depuis le calendrier. Les admins créent directement (statut `published`) ; les utilisateurs simples soumettent une proposition (statut `pending`) qui doit être validée par un admin.

---

## 1. Modèle de données

### Modifications sur `Activity`

| Champ | Type | Défaut | Nullable |
|-------|------|--------|----------|
| `status` | `string(16)` | `'published'` | non |
| `proposedBy` | `ManyToOne → User` | — | oui |

**Valeurs autorisées pour `status` :** `published` ou `pending` uniquement. Définies comme constantes **sur la classe `Activity` elle-même** :
```php
public const STATUS_PUBLISHED = 'published';
public const STATUS_PENDING    = 'pending';
```

**`proposedBy` :** FK nullable avec `onDelete="SET NULL"`. Si l'utilisateur est supprimé, la proposition reste en base avec `proposedBy = null`. Le rejet d'une proposition dont l'auteur est supprimé fonctionne normalement (supprime l'`Activity`, pas l'`User`).

**Rejet :** hard delete. Pas de soft delete ni d'historique dans cette itération.

### Migration Doctrine

Générée via `doctrine:migrations:diff`. Vérifier que le SQL contient `DEFAULT 'published'` pour la colonne `status` avant d'appliquer. Ajouter un index sur `status` dans la migration pour les requêtes de filtre :
```sql
CREATE INDEX idx_activity_status ON activity (status);
```

### Méthodes repository

Deux méthodes distinctes — **ne pas modifier `findBetween()` avec un paramètre optionnel, ce qui contredirait son contrat** :

| Méthode | Usage | Filtre status |
|---------|-------|---------------|
| `findBetween($start, $end)` | Calendrier public | `= Activity::STATUS_PUBLISHED` (modifiée) |
| `findAllOrderByStartDesc(?string $status)` | Liste admin | Filtre optionnel : null = tous, sinon filtre par valeur passée |

Les constantes `Activity::STATUS_PUBLISHED` et `Activity::STATUS_PENDING` sont utilisées dans le repository (pas de chaînes littérales).

---

## 2. Formulaire

**Réutilisation de l'`ActivityType` existant.** Les champs (titre, type, description, startAt, lieu) sont utilisés tels quels. `status` et `proposedBy` **ne sont pas ajoutés à `ActivityType`** — définis exclusivement côté contrôleur.

**Pré-remplissage de `startAt` :** le contrôleur lit `?date` (format `Y-m-d`), valide avec `\DateTimeImmutable::createFromFormat('Y-m-d', $date)`. Si absent ou invalide, `startAt` est initialisé à aujourd'hui (timezone serveur). Cohérent avec le calcul "aujourd'hui" du calendrier (même timezone serveur).

**Comportement sur erreur de validation :**

Le formulaire utilise `data-turbo-frame="_top"` sur la balise `<form>`. Cela signifie que toute réponse (succès comme erreur) est traitée comme une navigation top-level par Turbo.

- **Succès (303 redirect → `app_home`) :** Turbo navigue vers la page d'accueil. Le flash message s'affiche. La modal disparaît naturellement (plus dans le DOM).
- **Erreur de validation :** le contrôleur retourne HTTP 422 avec un template **complet** (étendant `base.html.twig`) affichant le formulaire avec ses erreurs — analogue à la page `/admin/activites/nouvelle`. La modal ne réapparaît pas ; l'utilisateur voit une page de formulaire standard. C'est le comportement accepté.

Ce choix évite la contradiction `data-turbo-frame="_top"` + re-render dans le frame.

---

## 3. Routes et contrôleurs

### Nouveau `src/Controller/ActivityController.php`

Namespace : `App\Controller` (pas `App\Controller\Admin`).

| Verb | Path | Nom de route |
|------|------|--------------|
| GET | `/activite/nouvelle` | `app_activity_new_public` |
| POST | `/activite/nouvelle` | `app_activity_new_public` |

Protégé par `#[IsGranted('ROLE_USER')]` sur la méthode. Si non connecté, redirect vers `/login` ; après connexion, `default_target_path` redirige vers l'accueil (comportement par défaut du firewall, pas de changement nécessaire).

**Réponse GET :** template `activity/modal_form.html.twig` — fragment nu, **sans** `{% extends 'base.html.twig' %}`. Contient uniquement un `<turbo-frame id="activity-modal-frame">` avec le formulaire.

**Réponse POST erreur (422) :** template `activity/modal_form_page.html.twig` — **avec** `{% extends 'base.html.twig' %}`, affiche le formulaire avec ses erreurs comme une page autonome (analogue à `/admin/activites/nouvelle`). Ce sont deux templates distincts : un fragment pour le GET, une page complète pour les erreurs POST.

**Logique POST :**

```
Si isGranted('ROLE_ADMIN') :   // couvre ROLE_SUPER_ADMIN via hiérarchie des rôles
    status = Activity::STATUS_PUBLISHED
    proposedBy = null
    flash('success', 'Événement créé avec succès.')

Sinon (ROLE_USER simple) :
    status = Activity::STATUS_PENDING
    proposedBy = $this->getUser()
    flash('success', 'Votre proposition a été envoyée et sera examinée par un administrateur.')

Succès : redirect 303 → app_home
Échec validation : render template complet avec 422
```

`isGranted('ROLE_ADMIN')` s'appuie sur la hiérarchie des rôles définie dans `security.yaml` : `ROLE_SUPER_ADMIN: [ROLE_ADMIN]`, `ROLE_ADMIN: [ROLE_USER]`. ROLE_SUPER_ADMIN est défini dans cette hiérarchie.

### Ajouts dans `src/Controller/Admin/ActivityController.php` (existant)

| Verb | Path | Nom | Rôle | CSRF token id |
|------|------|-----|------|---------------|
| POST | `/admin/activites/{id}/approuver` | `app_activity_approve` | ROLE_ADMIN (access_control) | `approve-{id}` |
| POST | `/admin/activites/{id}/rejeter` | `app_activity_reject` | ROLE_ADMIN (access_control) | `reject-{id}` |

CSRF généré dans Twig via `{{ csrf_token('approve-' ~ activity.id) }}`, validé dans le contrôleur via `$this->isCsrfTokenValid('approve-' ~ $activity->getId(), $token)`.

**Approuver :** `$activity->setStatus(Activity::STATUS_PUBLISHED)` + flush + flash succès + redirect `app_activity_index`.
**Rejeter :** `$em->remove($activity)` + flush + flash info + redirect `app_activity_index`. Fonctionne que `proposedBy` soit null ou non.

---

## 4. Calendrier — jours cliquables

### Règles d'affichage

| Situation | Comportement |
|-----------|--------------|
| Jour passé | Inchangé pour tous |
| Aujourd'hui ou futur, non connecté | Inchangé |
| Aujourd'hui ou futur, `is_granted('ROLE_USER')` | Numéro du jour = lien → modal |

"Aujourd'hui" calculé côté serveur (timezone serveur). Les activités `pending` ne sont jamais affichées sur le calendrier public, y compris pour leur auteur. Aucune logique par utilisateur n'est nécessaire : `findBetween()` filtre `status = 'published'`, donc toute activité `pending` est exclue pour tout le monde.

### Structure HTML de la modal

Dans `templates/home/index.html.twig`, en dehors de tout `<turbo-frame>` existant, avant `{% endblock body %}` :

```html
<div id="activity-modal" class="hidden fixed inset-0 z-50 bg-black/60 flex items-center justify-center p-4">
    <div class="glass-card rounded-2xl border border-custom shadow-xl w-full max-w-lg relative p-6">
        <turbo-frame id="activity-modal-frame" loading="lazy"></turbo-frame>
    </div>
</div>
```

`loading="lazy"` : le frame ne charge rien au démarrage.

### Liens dans la grille (mobile et desktop)

```twig
{% if is_granted('ROLE_USER') and jour >= today %}
    <a href="{{ path('app_activity_new_public', {date: ...}) }}"
       data-turbo-frame="activity-modal-frame"
       class="...cursor-pointer hover:text-custom-orange ...">{{ jour }}</a>
{% else %}
    <span class="...">{{ jour }}</span>
{% endif %}
```

Le lien cible le frame `activity-modal-frame` : Turbo charge le fragment dans le frame, puis `modal.js` affiche la modal.

### `assets/modal.js`

**Enregistrement :** ajouté dans `config/importmap.php` comme entrée locale. Importé explicitement via `<script type="module">` dans `templates/base.html.twig` avec `{{ importmap('modal') }}` ou en ajoutant `import 'modal';` dans `assets/app.js` s'il existe — **utiliser le même mécanisme que les autres modules (`carousel.js`, `mobile_menu.js`)**.

**Comportement :**

```js
// Écoute sur l'élément frame (pas document) pour turbo:frame-load
const frame = document.getElementById('activity-modal-frame');
const modal = document.getElementById('activity-modal');

frame.addEventListener('turbo:frame-load', () => {
    modal.classList.remove('hidden');
});

function closeModal() {
    modal.classList.add('hidden');
    // Vider le frame : supprimer son contenu et réinitialiser src
    frame.innerHTML = '';
    frame.removeAttribute('src');
}

modal.addEventListener('click', (e) => {
    if (e.target === modal) closeModal(); // clic sur le backdrop seulement
});

document.addEventListener('click', (e) => {
    if (e.target.closest('[data-modal-close]')) closeModal();
});
```

Le bouton "Fermer" dans `modal_form.html.twig` a l'attribut `data-modal-close` et `type="button"` (ne soumet pas le formulaire).

---

## 5. Interface admin — liste `/admin/activites`

### Filtre statut

Query param GET `?status` passé à `findAllOrderByStartDesc(?string $status)` :

| Valeur | Résultat |
|--------|----------|
| (absent / null) | Toutes |
| `pending` | En attente |
| `published` | Publiées |

### Ligne `pending`

- **Badge** : `<span class="bg-custom-orange text-white text-[10px] font-bold uppercase px-2 py-0.5 rounded-full">En attente</span>` à côté du titre
- **Colonne "Proposé par"** : email ou nom de `proposedBy` (null-safe : afficher "—" si null)
- **Boutons** (remplacent "Modifier") : deux `<form method="post">` inline, `Approuver` (vert) et `Rejeter` (rouge), chacun avec `_token` caché

### Ligne `published`

Inchangée.

### Notification

Hors scope de cette itération. Aucun email envoyé.

---

## 6. Sécurité

### Action requise : décommenter `access_control`

Dans `security.yaml`, décommenter la ligne suivante **avant mise en production** :
```yaml
access_control:
    - { path: ^/admin, roles: ROLE_ADMIN }
```
Sans cela, tout utilisateur `ROLE_USER` peut accéder aux routes admin (approve/reject).

### Récapitulatif des protections

| Route | Protection |
|-------|-----------|
| `GET/POST /activite/nouvelle` | `#[IsGranted('ROLE_USER')]` sur la méthode |
| `POST /admin/activites/{id}/approuver` | `access_control ^/admin ROLE_ADMIN` |
| `POST /admin/activites/{id}/rejeter` | `access_control ^/admin ROLE_ADMIN` |
| Formulaire `/activite/nouvelle` | CSRF implicite via `ActivityType` (Symfony Forms) |
| Approve/Reject | CSRF explicite, token dynamique par id |

### Hors scope

- Rate limiting sur `/activite/nouvelle`
- Verrouillage optimiste sur l'approbation concurrente

---

## 7. Ce qui ne change pas

- `Admin\ActivityController` CRUD (new/edit/delete).
- `ActivityType` (aucun champ ajouté).
- `ActivityRegisterController` (inscriptions).
- Carousel et ses slides.
