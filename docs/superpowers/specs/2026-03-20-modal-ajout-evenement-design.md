# Modal d'ajout d'événement depuis le calendrier

**Date :** 2026-03-20
**Projet :** La Boîte à Chimère — Symfony 8.0

---

## Contexte

Le calendrier public affiche les activités du mois. Les utilisateurs connectés doivent pouvoir proposer ou créer un événement directement en cliquant sur une date (aujourd'hui ou future) depuis le calendrier. Les admins créent directement ; les utilisateurs simples soumettent une proposition en attente de validation.

---

## 1. Modèle de données

### Modifications sur `Activity`

Deux nouveaux champs :

| Champ | Type | Défaut | Nullable |
|-------|------|--------|----------|
| `status` | `string(16)` | `'published'` | non |
| `proposedBy` | `ManyToOne → User` | — | oui |

**Valeurs de `status` :** `published` \| `pending`

- Toutes les activités existantes migrent vers `status = 'published'`.
- `proposedBy` est `null` pour les activités créées par un admin ; renseigné pour les propositions utilisateur.

### Migration Doctrine

Générée via `doctrine:migrations:diff`. La colonne `status` a une valeur DEFAULT SQL `'published'` pour ne pas casser les données existantes.

### Repository

`ActivityRepository::findBetween()` est mis à jour pour filtrer `status = 'published'` uniquement, afin que les propositions en attente n'apparaissent pas sur le calendrier public.

---

## 2. Routes et contrôleur

### Nouveau `src/Controller/ActivityController.php` (public)

| Verb | Path | Nom de route | Rôle requis |
|------|------|--------------|-------------|
| GET | `/activite/nouvelle` | `app_activity_new_modal` | `ROLE_USER` |
| POST | `/activite/nouvelle` | `app_activity_new_modal` | `ROLE_USER` |

Paramètre GET `date` (format `Y-m-d`) : pré-remplit le champ `startAt` du formulaire.

**Logique POST :**
- Si `ROLE_ADMIN` ou `ROLE_SUPER_ADMIN` : `status = 'published'`, `proposedBy = null`. Flash "Événement créé."
- Si `ROLE_USER` simple : `status = 'pending'`, `proposedBy = $this->getUser()`. Flash "Votre proposition a été envoyée et sera examinée par un administrateur."
- Dans les deux cas : redirect vers `app_home`.

**Réponse GET :** template partiel `activity/modal_form.html.twig` contenant un `<turbo-frame id="activity-modal-frame">` avec le formulaire `ActivityType` et la date pré-remplie.

### Ajouts dans `src/Controller/Admin/ActivityController.php`

| Verb | Path | Nom de route | Action |
|------|------|--------------|--------|
| POST | `/admin/activites/{id}/approuver` | `app_activity_approve` | `status → published`, flash succès |
| POST | `/admin/activites/{id}/rejeter` | `app_activity_reject` | Supprime l'activité, flash info |

Les deux actions utilisent un token CSRF. Redirect vers `app_activity_index`.

---

## 3. Calendrier — jours cliquables

### Règles d'affichage

| Situation | Comportement |
|-----------|--------------|
| Jour passé, tout utilisateur | Inchangé |
| Aujourd'hui ou futur, non connecté | Inchangé |
| Aujourd'hui ou futur, `is_granted('ROLE_USER')` | Numéro du jour = lien vers la modal |

### Mécanique Turbo Frame

Un conteneur modal est ajouté dans `templates/home/index.html.twig` (hors des turbo-frames existants) :

```html
<div id="activity-modal" class="hidden fixed inset-0 z-50 bg-black/60 flex items-center justify-center">
  <div class="glass-card rounded-2xl border border-custom shadow-xl w-full max-w-lg mx-4 p-6 relative">
    <turbo-frame id="activity-modal-frame"></turbo-frame>
  </div>
</div>
```

Les jours cliquables dans la grille calendrier (mobile et desktop) :

```html
<a href="/activite/nouvelle?date={{ year }}-{{ '%02d'|format(month) }}-{{ '%02d'|format(day) }}"
   data-turbo-frame="activity-modal-frame"
   class="...">{{ day }}</a>
```

### JS (nouveau fichier `assets/modal.js`)

- Écoute `turbo:frame-load` sur `#activity-modal-frame` → retire la classe `hidden` de `#activity-modal`
- Bouton "Fermer" dans le formulaire : vide le frame et remet `hidden`
- Clic sur le backdrop (`#activity-modal`) : même effet que fermer

S'applique aux deux versions du calendrier (mobile turbo-frame `calendar-frame` et desktop `calendar-desktop-frame`).

---

## 4. Interface admin

### Liste `/admin/activites`

**Filtre statut** en haut de liste — query param GET `?status` :
- `(vide)` : toutes les activités
- `?status=pending` : en attente uniquement
- `?status=published` : publiées uniquement

**Par ligne `pending` :**
- Badge orange **"En attente"** à côté du titre
- Nom de l'utilisateur `proposedBy` affiché
- Boutons **Approuver** (vert) et **Rejeter** (rouge) avec formulaires POST + CSRF, en lieu et place du bouton "Modifier"

**Par ligne `published` :** apparence et boutons actuels inchangés.

---

## 5. Formulaire

Réutilisation de l'`ActivityType` existant sans modification. Le champ `startAt` est pré-rempli via l'option `data` passée au formulaire à partir du paramètre GET `date`.

---

## 6. Sécurité

- La route `/activite/nouvelle` est protégée par `#[IsGranted('ROLE_USER')]` sur le contrôleur.
- Les routes `/admin/activites/{id}/approuver` et `/admin/activites/{id}/rejeter` sont couvertes par l'`access_control` existant `^/admin → ROLE_ADMIN`.
- Tokens CSRF sur toutes les actions POST (approbation, rejet, suppression).

---

## 7. Ce qui ne change pas

- Le comportement des activités existantes et de l'affichage calendrier.
- L'`AdminActivityController` CRUD existant (new/edit/delete) reste intact.
- L'`ActivityType` n'est pas modifié.
- Les inscriptions (`ActivityRegisterController`) ne sont pas affectées.
