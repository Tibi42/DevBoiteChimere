# Modal d'ajout d'événement depuis le calendrier — Plan d'implémentation

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Permettre aux utilisateurs connectés de créer ou proposer un événement en cliquant sur une date future dans le calendrier, via une modal Turbo Frame.

**Architecture:** Ajout de `status`/`proposedBy` sur `Activity` → nouveau contrôleur public `/activite/nouvelle` → modal Turbo Frame dans le calendrier → approbation admin dans la liste existante.

**Tech Stack:** Symfony 8.0, PHP 8.4, Doctrine ORM, Turbo Drive (`@hotwired/turbo`), AssetMapper (`importmap.php`), Tailwind CSS (symfonycasts/tailwind-bundle), Docker (container `devboitechimere-php-1`).

---

## Fichiers créés / modifiés

| Fichier | Action |
|---------|--------|
| `src/Entity/Activity.php` | Modifier — ajouter `status`, `proposedBy`, constantes |
| `src/Repository/ActivityRepository.php` | Modifier — filtrer published dans `findBetween`, status optionnel dans `findAllOrderByStartDesc` |
| `src/Controller/ActivityController.php` | Créer — route publique GET/POST `/activite/nouvelle` |
| `src/Controller/Admin/ActivityController.php` | Modifier — routes approve/reject, filtre status en index |
| `templates/activity/modal_form.html.twig` | Créer — fragment Turbo Frame (pas de base.html.twig) |
| `templates/activity/modal_form_page.html.twig` | Créer — page complète pour erreurs POST (extends base) |
| `templates/home/index.html.twig` | Modifier — jours cliquables + container modal |
| `templates/admin/activity/index.html.twig` | Modifier — filtre status, badges pending, boutons approve/reject |
| `assets/modal.js` | Créer — show/hide modal sur turbo:frame-load |
| `assets/app.js` | Modifier — ajouter `import './modal.js'` |
| `migrations/VersionYYYYMMDDHHMMSS.php` | Généré automatiquement |

---

## Task 1 — Entité Activity : status + proposedBy

**Fichiers :**
- Modifier : `src/Entity/Activity.php`

- [ ] **Step 1 : Ajouter les constantes et champs à Activity**

Ouvrir `src/Entity/Activity.php` et appliquer ces modifications :

Après `use Doctrine\ORM\Mapping as ORM;` (ligne 7), ajouter :
```php
use Doctrine\DBAL\Types\Types; // déjà présent
```

Après `{` de la classe (ligne 12), ajouter les constantes :
```php
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_PENDING   = 'pending';
```

Après le champ `$type` (ligne 37), ajouter :
```php
    #[ORM\Column(length: 16, options: ['default' => 'published'])]
    private string $status = self::STATUS_PUBLISHED;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $proposedBy = null;
```

Ajouter l'import `User` en haut du fichier avec les autres use :
```php
use App\Entity\User;
```

Après `setType()` (ligne 129), ajouter les getters/setters :
```php
    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getProposedBy(): ?User
    {
        return $this->proposedBy;
    }

    public function setProposedBy(?User $proposedBy): static
    {
        $this->proposedBy = $proposedBy;
        return $this;
    }
```

- [ ] **Step 2 : Générer la migration**

```bash
docker exec devboitechimere-php-1 php bin/console doctrine:migrations:diff
```

Résultat attendu : `Generated new migration class to ...`

- [ ] **Step 3 : Vérifier le SQL de la migration**

Ouvrir le fichier migration généré dans `migrations/`. Vérifier que le SQL contient :
- `ADD status VARCHAR(16) DEFAULT 'published' NOT NULL`
- `ADD proposed_by_id INT DEFAULT NULL`
- La FK sur `proposed_by_id` avec `ON DELETE SET NULL`

Si la DEFAULT est absente, l'ajouter manuellement dans la migration.

Ajouter également l'index après l'ADD status :
```php
$this->addSql('CREATE INDEX idx_activity_status ON activity (status)');
```

Et dans `down()` :
```php
$this->addSql('DROP INDEX idx_activity_status ON activity');
```

- [ ] **Step 4 : Appliquer la migration**

```bash
docker exec devboitechimere-php-1 php bin/console doctrine:migrations:migrate --no-interaction
```

Résultat attendu : migration exécutée sans erreur.

- [ ] **Step 5 : Vérifier en base**

```bash
docker exec devboitechimere-php-1 php bin/console doctrine:schema:validate
```

Résultat attendu : `[OK] The mapping files are correct.` et `[OK] The database schema is in sync with the mapping files.`

- [ ] **Step 6 : Commit**

```bash
git add src/Entity/Activity.php migrations/
git commit -m "feat: add status and proposedBy fields to Activity entity"
```

---

## Task 2 — Repository : filtres status

**Fichiers :**
- Modifier : `src/Repository/ActivityRepository.php`

- [ ] **Step 1 : Mettre à jour `findBetween()` pour filtrer published**

Remplacer la méthode `findBetween()` par :
```php
public function findBetween(\DateTimeInterface $start, \DateTimeInterface $end): array
{
    return $this->createQueryBuilder('a')
        ->andWhere('a.startAt >= :start')
        ->andWhere('a.startAt <= :end')
        ->andWhere('a.status = :status')
        ->setParameter('start', $start)
        ->setParameter('end', $end)
        ->setParameter('status', Activity::STATUS_PUBLISHED)
        ->orderBy('a.startAt', 'ASC')
        ->getQuery()
        ->getResult();
}
```

- [ ] **Step 2 : Mettre à jour `findAllOrderByStartDesc()` pour le filtre admin**

Remplacer par :
```php
public function findAllOrderByStartDesc(?string $status = null): array
{
    $qb = $this->createQueryBuilder('a')
        ->leftJoin('a.proposedBy', 'u')
        ->addSelect('u')
        ->orderBy('a.startAt', 'DESC');

    if ($status !== null) {
        $qb->andWhere('a.status = :status')
           ->setParameter('status', $status);
    }

    return $qb->getQuery()->getResult();
}
```

- [ ] **Step 3 : Vider le cache**

```bash
docker exec devboitechimere-php-1 php bin/console cache:clear
```

- [ ] **Step 4 : Commit**

```bash
git add src/Repository/ActivityRepository.php
git commit -m "feat: filter published activities in findBetween, add status filter to admin query"
```

---

## Task 3 — Contrôleur public : modal form

**Fichiers :**
- Créer : `src/Controller/ActivityController.php`

- [ ] **Step 1 : Créer le contrôleur**

```php
<?php

namespace App\Controller;

use App\Entity\Activity;
use App\Form\ActivityType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ActivityController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/activite/nouvelle', name: 'app_activity_new_public', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request): Response
    {
        $activity = new Activity();

        // Pré-remplir startAt depuis le paramètre GET ?date=Y-m-d
        $dateParam = $request->query->get('date', '');
        $startAt = \DateTimeImmutable::createFromFormat('Y-m-d', $dateParam);
        if (!$startAt) {
            $startAt = new \DateTimeImmutable('today');
        }
        $activity->setStartAt($startAt);

        $form = $this->createForm(ActivityType::class, $activity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->isGranted('ROLE_ADMIN')) {
                $activity->setStatus(Activity::STATUS_PUBLISHED);
                $activity->setProposedBy(null);
                $this->addFlash('success', 'L\'activité « ' . $activity->getTitle() . ' » a été créée.');
            } else {
                $activity->setStatus(Activity::STATUS_PENDING);
                $activity->setProposedBy($this->getUser());
                $this->addFlash('success', 'Votre proposition « ' . $activity->getTitle() . ' » a été envoyée et sera examinée par un administrateur.');
            }

            $this->entityManager->persist($activity);
            $this->entityManager->flush();

            return $this->redirectToRoute('app_home');
        }

        // GET : fragment nu pour Turbo Frame
        if ($request->isMethod('GET')) {
            return $this->render('activity/modal_form.html.twig', [
                'form' => $form,
                'date' => $dateParam,
            ]);
        }

        // POST invalide : page complète avec erreurs
        return $this->render('activity/modal_form_page.html.twig', [
            'form' => $form,
        ], new Response(null, Response::HTTP_UNPROCESSABLE_ENTITY));
    }
}
```

- [ ] **Step 2 : Vérifier la route**

```bash
docker exec devboitechimere-php-1 php bin/console debug:router | grep activite
```

Résultat attendu : ligne avec `/activite/nouvelle` et `app_activity_new_public`.

- [ ] **Step 3 : Commit**

```bash
git add src/Controller/ActivityController.php
git commit -m "feat: add public activity controller for modal form"
```

---

## Task 4 — Templates : modal_form + modal_form_page

**Fichiers :**
- Créer : `templates/activity/modal_form.html.twig`
- Créer : `templates/activity/modal_form_page.html.twig`

- [ ] **Step 1 : Créer le fragment `modal_form.html.twig`**

```twig
{# templates/activity/modal_form.html.twig #}
{# Fragment nu — pas d'extends. Chargé dans turbo-frame id="activity-modal-frame" #}
<turbo-frame id="activity-modal-frame">
    <div class="flex items-center justify-between mb-5">
        <h2 class="text-base font-bold uppercase tracking-wide text-text-primary">
            {% if is_granted('ROLE_ADMIN') %}
                Nouvel événement
            {% else %}
                Proposer un événement
            {% endif %}
        </h2>
        <button type="button" data-modal-close
                class="text-text-secondary hover:text-text-primary transition-colors p-1 rounded">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    {% if not is_granted('ROLE_ADMIN') %}
        <p class="text-xs text-text-secondary mb-4 p-3 rounded-lg bg-custom-orange/10 border border-custom-orange/30">
            Votre proposition sera soumise à validation par un administrateur avant d'apparaître dans le calendrier.
        </p>
    {% endif %}

    {{ form_start(form, {
        attr: {
            'data-turbo-frame': '_top',
            class: 'space-y-4'
        }
    }) }}

        {{ form_row(form.title) }}
        {{ form_row(form.type) }}
        {{ form_row(form.startAt) }}
        {{ form_row(form.location) }}
        {{ form_row(form.description) }}

        <div class="flex gap-3 pt-2">
            <button type="submit"
                    class="flex-1 rounded-lg bg-custom-orange py-3 text-xs font-extrabold uppercase tracking-widest text-text-primary hover:bg-orange-600 transition-all shadow-lg">
                {% if is_granted('ROLE_ADMIN') %}
                    Créer l'événement
                {% else %}
                    Envoyer la proposition
                {% endif %}
            </button>
            <button type="button" data-modal-close
                    class="rounded-lg border border-custom px-4 py-3 text-xs font-bold uppercase tracking-widest text-text-secondary hover:text-text-primary transition-colors">
                Annuler
            </button>
        </div>

    {{ form_end(form) }}
</turbo-frame>
```

- [ ] **Step 2 : Créer la page complète `modal_form_page.html.twig`**

```twig
{# templates/activity/modal_form_page.html.twig #}
{# Page complète pour les erreurs de validation POST (extends base) #}
{% extends 'base.html.twig' %}

{% block title %}
    {% if is_granted('ROLE_ADMIN') %}Nouvelle activité{% else %}Proposer un événement{% endif %} - La Boîte à Chimère
{% endblock %}

{% block body %}
    <div class="min-h-screen mx-auto max-w-[1240px] px-4 sm:px-8 py-8">
        <a href="{{ path('app_home') }}"
           class="inline-flex items-center gap-2 text-[10px] font-bold uppercase tracking-wider text-text-secondary hover:text-custom-orange transition-colors mb-6">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Retour à l'accueil
        </a>

        <div class="max-w-2xl rounded-2xl border border-custom bg-custom-secondary p-6 lg:p-8 shadow-2xl glass-card">
            <h1 class="text-xl font-bold text-text-primary uppercase tracking-wide mb-2">
                {% if is_granted('ROLE_ADMIN') %}Nouvelle activité{% else %}Proposer un événement{% endif %}
            </h1>

            {% if not is_granted('ROLE_ADMIN') %}
                <p class="text-xs text-text-secondary mb-6 p-3 rounded-lg bg-custom-orange/10 border border-custom-orange/30">
                    Votre proposition sera soumise à validation par un administrateur.
                </p>
            {% endif %}

            {{ form_start(form, { attr: { class: 'space-y-4' } }) }}
                {{ form_row(form.title) }}
                {{ form_row(form.type) }}
                {{ form_row(form.startAt) }}
                {{ form_row(form.location) }}
                {{ form_row(form.description) }}

                <div class="pt-2">
                    <button type="submit"
                            class="w-full rounded-lg bg-custom-orange py-3 text-xs font-extrabold uppercase tracking-widest text-text-primary hover:bg-orange-600 transition-all shadow-lg">
                        {% if is_granted('ROLE_ADMIN') %}Créer l'événement{% else %}Envoyer la proposition{% endif %}
                    </button>
                </div>
            {{ form_end(form) }}
        </div>
    </div>
{% endblock %}
```

- [ ] **Step 3 : Tester le rendu GET dans le navigateur**

Naviguer vers `http://localhost:8080/activite/nouvelle?date=2026-03-25` en étant connecté.
Résultat attendu : fragment HTML avec le formulaire et la date pré-remplie.

- [ ] **Step 4 : Commit**

```bash
git add templates/activity/
git commit -m "feat: add modal form templates (fragment and full page)"
```

---

## Task 5 — Modal JS

**Fichiers :**
- Créer : `assets/modal.js`
- Modifier : `assets/app.js`

- [ ] **Step 1 : Créer `assets/modal.js`**

```js
// assets/modal.js
// Gestion de la modal d'ajout d'événement depuis le calendrier.
// La modal wraps un <turbo-frame id="activity-modal-frame">.

function initModal() {
    const modal = document.getElementById('activity-modal');
    const frame = document.getElementById('activity-modal-frame');

    if (!modal || !frame) return;
    if (modal.dataset.modalInit) return;
    modal.dataset.modalInit = 'true';

    function openModal() {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
        // Vider le frame pour éviter le contenu périmé à la prochaine ouverture
        frame.innerHTML = '';
        frame.removeAttribute('src');
        delete modal.dataset.modalInit;
    }

    // Ouvrir quand le frame charge le formulaire
    frame.addEventListener('turbo:frame-load', openModal);

    // Fermer au clic sur le backdrop (l'overlay sombre)
    modal.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
    });

    // Fermer via les boutons data-modal-close (bubbling depuis le frame)
    document.addEventListener('click', (e) => {
        if (e.target.closest('[data-modal-close]')) {
            e.preventDefault();
            closeModal();
        }
    });

    // Fermer avec Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });
}

document.addEventListener('DOMContentLoaded', initModal);
document.addEventListener('turbo:load', initModal);
```

- [ ] **Step 2 : Importer Turbo et modal.js dans `assets/app.js`**

`@hotwired/turbo` est dans `importmap.php` mais n'est pas importé explicitement dans `app.js`. Sans cet import, les `<turbo-frame>` sont des éléments inconnus et les liens `data-turbo-frame` se comportent comme des liens normaux (navigation pleine page). Ajouter en haut de `assets/app.js`, avant les autres imports :

```js
import '@hotwired/turbo';
```

Puis après `import './join_panel.js';` :
```js
import './modal.js';
```

Le fichier final `assets/app.js` doit commencer par :
```js
import '@hotwired/turbo';
import './stimulus_bootstrap.js';
// ...
import './modal.js';
```

- [ ] **Step 3 : Commit**

```bash
git add assets/modal.js assets/app.js
git commit -m "feat: add modal.js for activity creation modal"
```

---

## Task 6 — Calendrier : jours cliquables + container modal

**Fichiers :**
- Modifier : `templates/home/index.html.twig`

- [ ] **Step 1 : Ajouter le container modal avant `{% endblock body %}`**

Localiser la fin du block body dans `templates/home/index.html.twig` et ajouter juste avant `{% endblock %}` :

```twig
{# Modal d'ajout d'événement - chargée via Turbo Frame depuis le calendrier #}
<div id="activity-modal"
     class="hidden fixed inset-0 z-50 bg-black/70 flex items-center justify-center p-4">
    <div class="glass-card rounded-2xl border border-custom shadow-2xl w-full max-w-lg relative p-6 max-h-[90vh] overflow-y-auto">
        <turbo-frame id="activity-modal-frame" loading="lazy"></turbo-frame>
    </div>
</div>
```

- [ ] **Step 2 : Créer un macro Twig pour le lien jour cliquable**

En haut de `index.html.twig`, après `{% block body %}`, définir un macro :

```twig
{% macro dayCell(day, calendarYear, calendarMonth, today, nowYear, nowMonth, isClickable) %}
    {% if isClickable %}
        <a href="{{ path('app_activity_new_public', {date: calendarYear ~ '-' ~ '%02d'|format(calendarMonth) ~ '-' ~ '%02d'|format(day)}) }}"
           data-turbo-frame="activity-modal-frame"
           class="block py-2 rounded text-custom-orange font-bold underline underline-offset-2 hover:bg-custom-orange/20 transition-colors cursor-pointer"
           title="Ajouter un événement le {{ day }}/{{ '%02d'|format(calendarMonth) }}/{{ calendarYear }}">{{ day }}</a>
    {% else %}
        <span class="py-2">{{ day }}</span>
    {% endif %}
{% endmacro %}
```

En pratique pour ce projet, il est plus simple d'intégrer la logique directement dans les deux boucles de calendrier (mobile et desktop). Voir Step 3 et 4.

- [ ] **Step 3 : Modifier la grille mobile (dans `<turbo-frame id="calendar-frame">`)**

Localiser le bloc de la grille calendrier mobile (autour de la ligne 218-234). Remplacer la partie `{% else %}` (jours sans activité, ligne 226-229) par :

```twig
{% elseif today == day and calendarYear == "now"|date('Y')|number_format and calendarMonth == "now"|date('n')|number_format %}
    {# Aujourd'hui dans le mois courant #}
    {% if is_granted('ROLE_USER') %}
        <a href="{{ path('app_activity_new_public', {date: calendarYear ~ '-' ~ '%02d'|format(calendarMonth) ~ '-' ~ '%02d'|format(day)}) }}"
           data-turbo-frame="activity-modal-frame"
           class="block py-2 text-custom-orange font-bold underline underline-offset-2 hover:bg-custom-orange/20 rounded transition-colors"
           title="Ajouter un événement">{{ day }}</a>
    {% else %}
        <span class="py-2 text-custom-orange font-bold underline underline-offset-2">{{ day }}</span>
    {% endif %}
{% else %}
    {# Jour ordinaire #}
    {% set nowYear = "now"|date('Y') %}
    {% set nowMonth = "now"|date('n') %}
    {% set nowDay = "now"|date('j') %}
    {% set isFuture = (calendarYear > nowYear) or (calendarYear == nowYear and calendarMonth > nowMonth) or (calendarYear == nowYear and calendarMonth == nowMonth and day > nowDay) %}
    {% if is_granted('ROLE_USER') and isFuture %}
        <a href="{{ path('app_activity_new_public', {date: calendarYear ~ '-' ~ '%02d'|format(calendarMonth) ~ '-' ~ '%02d'|format(day)}) }}"
           data-turbo-frame="activity-modal-frame"
           class="block py-2 hover:text-custom-orange hover:bg-custom-orange/10 rounded transition-colors cursor-pointer"
           title="Ajouter un événement">{{ day }}</a>
    {% else %}
        <span class="py-2">{{ day }}</span>
    {% endif %}
{% endif %}
```

**Note :** `calendarYear` et `calendarMonth` sont des entiers passés par le contrôleur. En Twig, les comparaisons `==` fonctionnent entre int et int. `"now"|date('Y')` retourne une chaîne — utiliser `calendarYear == "now"|date('Y')|number_format` ou mieux, passer `nowYear`/`nowMonth` depuis le contrôleur (voir Step 5).

- [ ] **Step 4 : Modifier la grille desktop (dans `<turbo-frame id="calendar-desktop-frame">`)**

Appliquer le même remplacement que Step 3 dans la boucle desktop (lignes ~353-368).

- [ ] **Step 5 : Passer nowYear et nowMonth depuis HomeController pour simplifier Twig**

Dans `src/Controller/HomeController.php`, ajouter dans le tableau `render()` :
```php
'nowYear'  => (int) (new \DateTimeImmutable())->format('Y'),
'nowMonth' => (int) (new \DateTimeImmutable())->format('n'),
'nowDay'   => (int) (new \DateTimeImmutable())->format('j'),
```

Puis simplifier la condition Twig :
```twig
{% set isFuture = (calendarYear > nowYear)
    or (calendarYear == nowYear and calendarMonth > nowMonth)
    or (calendarYear == nowYear and calendarMonth == nowMonth and day > nowDay) %}
```

Et pour "aujourd'hui" :
```twig
{% elseif today == day %}
    {% if is_granted('ROLE_USER') %}
        <a href="..." data-turbo-frame="activity-modal-frame" class="...">{{ day }}</a>
    {% else %}
        <span class="py-2 text-custom-orange font-bold underline underline-offset-2">{{ day }}</span>
    {% endif %}
```

- [ ] **Step 6 : Rebuild Tailwind et tester visuellement**

```bash
docker exec devboitechimere-php-1 php bin/console tailwind:build
```

Naviguer sur l'accueil connecté. Les jours d'aujourd'hui et futurs doivent afficher un curseur pointer. Cliquer → la modal s'ouvre avec le formulaire et la date pré-remplie.

- [ ] **Step 7 : Commit**

```bash
git add templates/home/index.html.twig src/Controller/HomeController.php
git commit -m "feat: add clickable calendar days and modal container for event creation"
```

---

## Task 7 — Admin : routes approve/reject

**Fichiers :**
- Modifier : `src/Controller/Admin/ActivityController.php`

- [ ] **Step 1 : Ajouter les routes et les imports dans Admin\ActivityController**

Ajouter après le `use` de `Symfony\Component\Routing\Attribute\Route` :

```php
use Symfony\Component\Security\Http\Attribute\IsGranted;
```

Ajouter le filtre status dans la méthode `index()` :
```php
#[Route('', name: 'index', methods: ['GET'])]
public function index(Request $request): Response
{
    $status = $request->query->get('status');
    // Valider que status est une valeur connue
    if ($status !== null && !in_array($status, [Activity::STATUS_PUBLISHED, Activity::STATUS_PENDING], true)) {
        $status = null;
    }

    $activities = $this->activityRepository->findAllOrderByStartDesc($status);

    return $this->render('admin/activity/index.html.twig', [
        'activities' => $activities,
        'currentStatus' => $status,
    ]);
}
```

Ajouter les deux nouvelles méthodes **avant** la méthode `delete()` (qui a le pattern `/{id}` sans suffixe). Les `requirements: ['id' => '\d+']` sont obligatoires pour que ces routes ne soient pas confondues avec la route de suppression :
```php
#[Route('/{id}/approuver', name: 'approve', requirements: ['id' => '\d+'], methods: ['POST'])]
public function approve(Request $request, Activity $activity): Response
{
    $token = $request->request->get('_token');
    if (!$this->isCsrfTokenValid('approve' . $activity->getId(), $token)) {
        $this->addFlash('error', 'Jeton de sécurité invalide.');
        return $this->redirectToRoute('app_activity_index');
    }

    $activity->setStatus(Activity::STATUS_PUBLISHED);
    $this->entityManager->flush();
    $this->addFlash('success', 'L\'activité « ' . $activity->getTitle() . ' » a été approuvée et est maintenant visible.');

    return $this->redirectToRoute('app_activity_index');
}

#[Route('/{id}/rejeter', name: 'reject', requirements: ['id' => '\d+'], methods: ['POST'])]
public function reject(Request $request, Activity $activity): Response
{
    $token = $request->request->get('_token');
    if (!$this->isCsrfTokenValid('reject' . $activity->getId(), $token)) {
        $this->addFlash('error', 'Jeton de sécurité invalide.');
        return $this->redirectToRoute('app_activity_index');
    }

    $title = $activity->getTitle();
    $this->entityManager->remove($activity);
    $this->entityManager->flush();
    $this->addFlash('success', 'La proposition « ' . $title . ' » a été rejetée et supprimée.');

    return $this->redirectToRoute('app_activity_index');
}
```

- [ ] **Step 2 : Ajouter le `use Request` si absent**

Vérifier que `use Symfony\Component\HttpFoundation\Request;` est déjà présent (il l'est, ligne 10).

- [ ] **Step 3 : Vérifier les routes**

```bash
docker exec devboitechimere-php-1 php bin/console debug:router | grep activity
```

Résultat attendu : lignes pour `app_activity_approve` et `app_activity_reject`.

- [ ] **Step 4 : Commit**

```bash
git add src/Controller/Admin/ActivityController.php
git commit -m "feat: add approve/reject routes and status filter to admin activity controller"
```

---

## Task 8 — Template admin : filtre + badges + boutons

**Fichiers :**
- Modifier : `templates/admin/activity/index.html.twig`

- [ ] **Step 1 : Ajouter le filtre status après le titre h1**

Localiser le `<div class="flex flex-col sm:flex-row ...">` (ligne 12). Ajouter après cette div (avant le tableau) :

```twig
{# Filtre status #}
<div class="flex gap-2 mb-4">
    <a href="{{ path('app_activity_index') }}"
       class="px-3 py-1.5 rounded-lg text-[10px] font-bold uppercase tracking-wider transition-colors {{ currentStatus is null ? 'bg-custom-orange text-text-primary' : 'border border-custom text-text-secondary hover:text-text-primary' }}">
        Toutes
    </a>
    <a href="{{ path('app_activity_index', {status: 'pending'}) }}"
       class="px-3 py-1.5 rounded-lg text-[10px] font-bold uppercase tracking-wider transition-colors {{ currentStatus == 'pending' ? 'bg-custom-orange text-text-primary' : 'border border-custom text-text-secondary hover:text-text-primary' }}">
        En attente
    </a>
    <a href="{{ path('app_activity_index', {status: 'published'}) }}"
       class="px-3 py-1.5 rounded-lg text-[10px] font-bold uppercase tracking-wider transition-colors {{ currentStatus == 'published' ? 'bg-custom-orange text-text-primary' : 'border border-custom text-text-secondary hover:text-text-primary' }}">
        Publiées
    </a>
</div>
```

- [ ] **Step 2 : Ajouter la colonne "Proposé par" dans le `<thead>`**

Après la `<th>` "Lieu" et avant la `<th>` "Actions" :
```twig
<th class="px-4 py-3 text-[10px] font-bold uppercase tracking-wider text-text-secondary">Proposé par</th>
```

- [ ] **Step 3 : Modifier les lignes du tableau pour les activités pending**

Remplacer le bloc `<tr>` de la boucle (lignes 35-54) par :

```twig
<tr class="border-b border-custom/50 hover:bg-white/5 transition-colors {{ activity.status == 'pending' ? 'bg-custom-orange/5' : '' }}">
    <td class="px-4 py-3 text-sm text-text-primary whitespace-nowrap">
        {{ activity.startAt|date('d/m/Y H:i') }}
    </td>
    <td class="px-4 py-3">
        <div class="flex items-center gap-2">
            <span class="font-medium text-text-primary">{{ activity.title }}</span>
            {% if activity.status == 'pending' %}
                <span class="bg-custom-orange text-white text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full shrink-0">
                    En attente
                </span>
            {% endif %}
        </div>
    </td>
    <td class="px-4 py-3">
        <span class="text-[9px] font-bold uppercase tracking-widest text-custom-orange px-2 py-0.5 rounded bg-custom-orange/10">{{ activity.type|default('—') }}</span>
    </td>
    <td class="px-4 py-3 text-sm text-text-secondary">{{ activity.location|default('—') }}</td>
    <td class="px-4 py-3 text-sm text-text-secondary">
        {% if activity.proposedBy %}
            <span class="text-text-secondary">{{ activity.proposedBy.username ?? activity.proposedBy.email }}</span>
        {% else %}
            <span class="text-text-secondary/40">—</span>
        {% endif %}
    </td>
    <td class="px-4 py-3 text-right whitespace-nowrap">
        {% if activity.status == 'pending' %}
            {# Boutons approve/reject pour les propositions en attente #}
            <form method="post" action="{{ path('app_activity_approve', { id: activity.id }) }}" class="inline">
                <input type="hidden" name="_token" value="{{ csrf_token('approve' ~ activity.id) }}">
                <button type="submit" class="text-[10px] font-bold uppercase tracking-wider text-green-400 hover:text-green-300 transition-colors mr-3">Approuver</button>
            </form>
            <form method="post" action="{{ path('app_activity_reject', { id: activity.id }) }}" class="inline"
                  onsubmit="return confirm('Rejeter et supprimer cette proposition ?')">
                <input type="hidden" name="_token" value="{{ csrf_token('reject' ~ activity.id) }}">
                <button type="submit" class="text-[10px] font-bold uppercase tracking-wider text-red-400 hover:text-red-300 transition-colors mr-3">Rejeter</button>
            </form>
        {% else %}
            {# Boutons normaux pour les activités publiées #}
            <a href="{{ path('app_activity_edit', { id: activity.id }) }}" class="inline-flex items-center gap-1 text-[10px] font-bold uppercase tracking-wider text-custom-orange hover:text-orange-400 transition-colors mr-3">Modifier</a>
            <a href="{{ path('app_activity_register', { id: activity.id }) }}" class="inline-flex items-center gap-1 text-[10px] font-bold uppercase tracking-wider text-text-secondary hover:text-text-primary transition-colors mr-3" target="_blank" rel="noopener">Voir inscription</a>
        {% endif %}
        <form method="post" action="{{ path('app_activity_delete', { id: activity.id }) }}" class="inline"
              onsubmit="return confirm('Supprimer cette activité ? Les inscriptions associées seront aussi supprimées.')">
            <input type="hidden" name="_token" value="{{ csrf_token('delete' ~ activity.id) }}">
            <button type="submit" class="text-[10px] font-bold uppercase tracking-wider text-red-400 hover:text-red-300 transition-colors">Supprimer</button>
        </form>
    </td>
</tr>
```

- [ ] **Step 4 : Rebuild Tailwind**

```bash
docker exec devboitechimere-php-1 php bin/console tailwind:build
```

- [ ] **Step 5 : Tester visuellement**

Naviguer vers `http://localhost:8080/admin/activites`. Vérifier :
- Les 3 filtres de statut s'affichent
- Une proposition en attente affiche le badge orange et les boutons Approuver/Rejeter

- [ ] **Step 6 : Commit**

```bash
git add templates/admin/activity/index.html.twig
git commit -m "feat: add status filter, pending badges and approve/reject buttons to admin activity list"
```

---

## Task 9 — Tests de bout en bout

- [ ] **Step 1 : Tester le flux complet en tant que ROLE_USER**

1. Se connecter avec un compte `ROLE_USER`
2. Aller sur l'accueil
3. Cliquer sur une date future dans le calendrier (mobile ou desktop)
4. Vérifier : la modal s'ouvre, le formulaire affiche "Proposer un événement" et le message informatif
5. Remplir le formulaire (titre obligatoire, date pré-remplie)
6. Soumettre
7. Vérifier : redirect vers l'accueil, flash "Votre proposition a été envoyée..."
8. Vérifier : l'activité N'apparaît PAS dans le calendrier (status=pending)

- [ ] **Step 2 : Tester le flux complet en tant que ROLE_ADMIN**

1. Se connecter avec un compte admin
2. Cliquer sur une date future
3. Vérifier : le formulaire affiche "Nouvel événement" (pas de message informatif)
4. Soumettre → redirect accueil, flash "Événement créé..."
5. Vérifier : l'activité APPARAÎT dans le calendrier

- [ ] **Step 3 : Tester l'approbation d'une proposition**

1. En tant qu'admin, naviguer vers `http://localhost:8080/admin/activites?status=pending`
2. Vérifier : la proposition créée en Step 1 est listée avec le badge "En attente"
3. Cliquer "Approuver"
4. Vérifier : flash succès, la proposition disparaît du filtre "En attente"
5. Aller sur l'accueil : l'activité est maintenant visible dans le calendrier

- [ ] **Step 4 : Tester le rejet**

1. Créer une nouvelle proposition en tant que ROLE_USER
2. En tant qu'admin, cliquer "Rejeter"
3. Confirmer la suppression
4. Vérifier : flash succès, la proposition est supprimée

- [ ] **Step 5 : Tester la fermeture de la modal**

1. Ouvrir la modal en cliquant une date
2. Cliquer le bouton X (fermer) → modal disparaît
3. Rouvrir la modal → formulaire propre (pas de données résiduelles)
4. Ouvrir la modal → cliquer sur le backdrop sombre → modal ferme
5. Ouvrir la modal → appuyer Escape → modal ferme

- [ ] **Step 6 : Tester les erreurs de validation**

1. Ouvrir la modal → soumettre sans titre
2. Vérifier : page complète s'affiche avec le message d'erreur Symfony sur le champ titre

- [ ] **Step 7 : Vider le cache et commit final**

```bash
docker exec devboitechimere-php-1 php bin/console cache:clear
```

```bash
git add .
git commit -m "feat: complete modal event creation from calendar with admin approval workflow"
```
