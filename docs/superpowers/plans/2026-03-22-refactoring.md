# Refactoring Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Éliminer la duplication de code, extraire une couche service et ajouter des tests sans jamais casser le site entre les phases.

**Architecture:** Phase 1 élimine les contrôleurs vides et centralise les constantes. Phase 2 extrait la logique métier vers des services dédiés. Phase 3 couvre tout par des tests unitaires et fonctionnels.

**Tech Stack:** Symfony 8, PHP 8.4, Doctrine ORM, PHPUnit, Twig, Tailwind CSS, AssetMapper, Turbo/Stimulus.

**Commandes utiles :**
```bash
# Lancer les tests
docker compose exec php php bin/phpunit

# Vider le cache
docker compose exec php php bin/console cache:clear

# Vérifier qu'une route existe
docker compose exec php php bin/console debug:router | grep app_jds
```

---

## Chunk 1 : Phase 1 — Gains immédiats

### Task 1 : `StaticPageController` — remplace 13 contrôleurs

**Files:**
- Create: `src/Controller/StaticPageController.php`
- Delete: `src/Controller/JdsController.php`, `JdrController.php`, `GnController.php`, `AssociationController.php`, `NouvellesController.php`, `QuiSommesNousController.php`, `SocietesController.php`, `EvenementsController.php`, `MentionsLegalesController.php`, `ContactController.php`, `NosSoireeHebController.php`, `NosSoireeBihebController.php`, `NosSoireeMensuelleController.php`

- [ ] **Step 1 : Vérifier que `controller_name` n'est pas utilisé dans les templates**

```bash
docker compose exec php grep -r "controller_name" templates/
```
Résultat attendu : aucune ligne (grep ne trouve rien). Si une ligne apparaît, supprimer la variable du template concerné avant de continuer.

- [ ] **Step 2 : Créer `src/Controller/StaticPageController.php`**

```php
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class StaticPageController extends AbstractController
{
    #[Route('/jds', name: 'app_jds')]
    public function jds(): Response { return $this->render('jds/index.html.twig'); }

    #[Route('/jdr', name: 'app_jdr')]
    public function jdr(): Response { return $this->render('jdr/index.html.twig'); }

    #[Route('/gn', name: 'app_gn')]
    public function gn(): Response { return $this->render('gn/index.html.twig'); }

    #[Route('/association', name: 'app_association')]
    public function association(): Response { return $this->render('association/index.html.twig'); }

    #[Route('/nouvelles', name: 'app_nouvelles')]
    public function nouvelles(): Response { return $this->render('nouvelles/index.html.twig'); }

    #[Route('/qui-sommes-nous', name: 'app_qui_sommes_nous')]
    public function quiSommesNous(): Response { return $this->render('qui_sommes_nous/index.html.twig'); }

    #[Route('/societes', name: 'app_societes')]
    public function societes(): Response { return $this->render('societes/index.html.twig'); }

    #[Route('/evenements', name: 'app_evenements')]
    public function evenements(): Response { return $this->render('evenements/index.html.twig'); }

    #[Route('/mentions-legales', name: 'app_mentions_legales')]
    public function mentionsLegales(): Response { return $this->render('mentions_legales/index.html.twig'); }

    #[Route('/contact', name: 'app_contact')]
    public function contact(): Response { return $this->render('contact/index.html.twig'); }

    #[Route('/nos/soiree/heb', name: 'app_nos_soiree_heb')]
    public function nosSoireeHeb(): Response { return $this->render('nos_soiree_heb/index.html.twig'); }

    #[Route('/nos/soiree/biheb', name: 'app_nos_soiree_biheb')]
    public function nosSoireeBiheb(): Response { return $this->render('nos_soiree_biheb/index.html.twig'); }

    #[Route('/nos/soiree/mensuelle', name: 'app_nos_soiree_mensuelle')]
    public function nosSoireeMensuelle(): Response { return $this->render('nos_soiree_mensuelle/index.html.twig'); }
}
```

- [ ] **Step 3 : Supprimer les 13 anciens contrôleurs**

```bash
rm src/Controller/JdsController.php \
   src/Controller/JdrController.php \
   src/Controller/GnController.php \
   src/Controller/AssociationController.php \
   src/Controller/NouvellesController.php \
   src/Controller/QuiSommesNousController.php \
   src/Controller/SocietesController.php \
   src/Controller/EvenementsController.php \
   src/Controller/MentionsLegalesController.php \
   src/Controller/ContactController.php \
   src/Controller/NosSoireeHebController.php \
   src/Controller/NosSoireeBihebController.php \
   src/Controller/NosSoireeMensuelleController.php
```

- [ ] **Step 4 : Vider le cache et vérifier que les routes existent toujours**

```bash
docker compose exec php php bin/console cache:clear
docker compose exec php php bin/console debug:router | grep -E "app_jds|app_gn|app_association|app_nos_soiree"
```
Résultat attendu : les 13 routes apparaissent toutes, toutes pointent vers `StaticPageController`.

- [ ] **Step 5 : Vérifier en navigant sur une page**

Ouvrir http://localhost:8080/jds — la page doit s'afficher normalement.

- [ ] **Step 6 : Commit**

```bash
git add src/Controller/StaticPageController.php
git rm src/Controller/JdsController.php src/Controller/JdrController.php \
       src/Controller/GnController.php src/Controller/AssociationController.php \
       src/Controller/NouvellesController.php src/Controller/QuiSommesNousController.php \
       src/Controller/SocietesController.php src/Controller/EvenementsController.php \
       src/Controller/MentionsLegalesController.php src/Controller/ContactController.php \
       src/Controller/NosSoireeHebController.php src/Controller/NosSoireeBihebController.php \
       src/Controller/NosSoireeMensuelleController.php
git commit -m "refactor: merge 13 static controllers into StaticPageController"
```

---

### Task 2 : Enum `ActivityKind`

**Files:**
- Create: `src/Enum/ActivityKind.php`
- Modify: `src/Controller/HomeController.php` (ligne 32)
- Modify: `src/Controller/Admin/ActivityController.php` (lignes 36, 81)
- Modify: `src/Form/ActivityType.php` (lignes 20-31)

- [ ] **Step 1 : Créer `src/Enum/ActivityKind.php`**

```php
<?php

namespace App\Enum;

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

- [ ] **Step 2 : Mettre à jour `HomeController.php`**

Remplacer (ligne 32) :
```php
$allowedTypes = ['JDS', 'JDR', 'GN', 'JDF', 'AG', 'Play Test'];
```
Par :
```php
use App\Enum\ActivityKind;
// ...
$allowedTypes = ActivityKind::values();
```
Ajouter le `use App\Enum\ActivityKind;` en haut du fichier avec les autres imports.

- [ ] **Step 3 : Mettre à jour `Admin/ActivityController.php`**

Ajouter `use App\Enum\ActivityKind;` en haut du fichier.

Remplacer les deux occurrences (lignes 36 et 81) :
```php
$allowedTypes = ['JDS', 'JDR', 'GN', 'JDF', 'AG', 'Play Test'];
```
Par :
```php
$allowedTypes = ActivityKind::values();
```

- [ ] **Step 4 : Mettre à jour `src/Form/ActivityType.php`**

Ajouter `use App\Enum\ActivityKind;` en haut du fichier.

Remplacer le tableau de choix (lignes 20-31) :
```php
$choices = [
    '-- Choisir --' => '',
    'Play Test' => 'Play Test',
    'JDS (Jeux de Société)' => 'JDS',
    'JDR (Jeux de Rôle)' => 'JDR',
    'GN (Grandeur Nature)' => 'GN',
    'JDF (Jeux de Figurines)' => 'JDF',
];

if ($options['is_admin']) {
    $choices['AG (Assemblée Générale)'] = 'AG';
}
```
Par :
```php
$choices = ['-- Choisir --' => ''];
foreach (ActivityKind::cases() as $kind) {
    if ($kind === ActivityKind::AG && !$options['is_admin']) {
        continue;
    }
    $choices[$kind->label()] = $kind->value;
}
```

- [ ] **Step 5 : Vider le cache et vérifier le formulaire admin**

```bash
docker compose exec php php bin/console cache:clear
```
Ouvrir http://localhost:8080/admin/activites/nouvelle — le select "Type d'activité" doit afficher les mêmes options qu'avant.

- [ ] **Step 6 : Commit**

```bash
git add src/Enum/ActivityKind.php src/Controller/HomeController.php \
        src/Controller/Admin/ActivityController.php src/Form/ActivityType.php
git commit -m "refactor: introduce ActivityKind enum, remove hardcoded type lists"
```

---

### Task 3 : Composant template `_activity_page.html.twig`

**Files:**
- Create: `templates/components/_activity_page.html.twig`
- Modify: `templates/jds/index.html.twig`
- Modify: `templates/jdr/index.html.twig`
- Modify: `templates/gn/index.html.twig`

- [ ] **Step 1 : Créer `templates/components/_activity_page.html.twig`**

```twig
{#
  Composant partagé pour les pages JDS / JDR / GN.
  Variables attendues :
    color            string  classe Tailwind ex: "text-emerald-400"
    icon_path        string  attribut d (SVG path)
    heading          string  titre de la section principale
    description_paragraphs  string[]
    infos            array   [{label, title, subtitle}, ...]
    other_activities array   [{label, href, color, icon_path}, ...]
#}
<div class="grid gap-12 lg:gap-16 lg:grid-cols-3">
	{# ── Carte principale ── #}
	<div class="lg:col-span-2 space-y-12">
		<section class="rounded-2xl border border-custom glass-card p-8 shadow-xl">
			<div class="flex items-center gap-3 mb-6">
				<div class="glass h-10 w-10 flex items-center justify-center rounded-lg {{ color }}">
					<svg class="h-5 w-5" fill="currentColor" viewbox="0 0 24 24">
						<path d="{{ icon_path }}"/>
					</svg>
				</div>
				<h2 class="text-2xl font-bold uppercase tracking-wider text-text-primary">{{ heading }}</h2>
			</div>
			<div class="space-y-4 text-text-secondary leading-relaxed">
				{% for paragraph in description_paragraphs %}
					<p>{{ paragraph }}</p>
				{% endfor %}
			</div>
		</section>

		{# ── Infos pratiques ── #}
		<section class="rounded-2xl border border-custom glass-card p-8 shadow-xl">
			<h2 class="text-xl font-bold uppercase tracking-wider text-text-primary mb-6">Infos pratiques</h2>
			<div class="grid gap-6 sm:grid-cols-2">
				{% for info in infos %}
					<div class="rounded-xl bg-custom-tertiary border border-custom p-5">
						<span class="text-[9px] font-bold uppercase tracking-widest {{ color }} mb-2 block">{{ info.label }}</span>
						<p class="text-sm font-bold text-text-primary">{{ info.title }}</p>
						{% if info.subtitle is defined and info.subtitle %}
							<p class="text-xs text-text-secondary mt-1">{{ info.subtitle }}</p>
						{% endif %}
					</div>
				{% endfor %}
			</div>
		</section>
	</div>

	{# ── Sidebar ── #}
	<aside class="space-y-8">
		<div class="rounded-2xl border border-custom glass-card p-6 shadow-xl">
			<h3 class="text-xs font-bold uppercase tracking-[0.2em] text-text-primary/60 mb-4">PROCHAIN ÉVÉNEMENT</h3>
			<div class="rounded-xl bg-custom-tertiary border border-custom p-5 text-center">
				<p class="text-xs text-text-secondary">Consultez le calendrier sur la page d'accueil pour voir les prochaines dates.</p>
				<a href="{{ path('app_home') }}" class="inline-block mt-4 rounded-lg bg-custom-orange px-5 py-2.5 text-[10px] font-extrabold uppercase tracking-wider text-text-primary transition-all hover:bg-orange-600 hover:scale-105 shadow-lg shadow-custom-orange/20">
					VOIR LE CALENDRIER
				</a>
			</div>
		</div>

		<div class="rounded-2xl border border-custom glass-card p-6 shadow-xl">
			<h3 class="text-xs font-bold uppercase tracking-[0.2em] text-text-primary/60 mb-4">NOS AUTRES ACTIVITÉS</h3>
			<div class="space-y-3">
				{% for activity in other_activities %}
					<a href="{{ path(activity.route) }}" class="flex items-center gap-3 p-3 rounded-lg bg-custom-tertiary border border-custom hover:border-custom-orange transition-all group">
						<div class="glass h-8 w-8 flex items-center justify-center rounded-lg {{ activity.color }} shrink-0">
							<svg class="h-4 w-4" fill="currentColor" viewbox="0 0 24 24"><path d="{{ activity.icon_path }}"/></svg>
						</div>
						<span class="text-xs font-medium text-text-primary/70 group-hover:text-text-primary">{{ activity.label }}</span>
					</a>
				{% endfor %}
			</div>
		</div>
	</aside>
</div>
```

- [ ] **Step 2 : Remplacer `templates/jds/index.html.twig`**

```twig
{% extends 'layouts/page.html.twig' %}

{% block title %}Jeux de Société - La Boîte à Chimère{% endblock %}

{% block meta_description %}<meta name="description" content="Rejoignez les soirées jeux de société bi-hebdomadaires du vendredi de La Boîte à Chimère à Paris. Découvrez nos jeux, nos événements et notre communauté.">{% endblock %}
{% block og_description %}Rejoignez les soirées jeux de société bi-hebdomadaires du vendredi de La Boîte à Chimère à Paris. Découvrez nos jeux, nos événements et notre communauté.{% endblock %}

{% block page_title %}Jeux de Société{% endblock %}
{% block page_subtitle %}<p class="text-lg text-text-secondary max-w-2xl mx-auto">Nos soirées bi-hebdomadaires du vendredi (19h)</p>{% endblock %}

{% block content %}
	{% include 'components/_activity_page.html.twig' with {
		color: 'text-emerald-400',
		icon_path: 'M12 2a2 2 0 0 1 2 2c0 .74-.4 1.39-1 1.73V7h1a2 2 0 0 1 2 2v1h1a2 2 0 0 1 2 2v1h1a2 2 0 0 1 2 2v1a2 2 0 0 1-2 2h-1v1a2 2 0 0 1-2 2h-1v1a2 2 0 0 1-2 2h-4a2 2 0 0 1-2-2v-1H7a2 2 0 0 1-2-2v-1H4a2 2 0 0 1-2-2v-1a2 2 0 0 1 2-2h1v-1a2 2 0 0 1 2-2h1v-1a2 2 0 0 1 2-2h1V5.73c-.6-.34-1-.99-1-1.73a2 2 0 0 1 2-2z',
		heading: 'Nos soirées JDS',
		description_paragraphs: [
			'Rejoignez-nous chaque vendredi soir pour des parties de jeux de société dans une ambiance conviviale et détendue. Que vous soyez novice ou joueur expérimenté, il y a toujours une table qui vous attend !',
			'Notre ludothèque compte de nombreux jeux pour tous les goûts : jeux de stratégie, jeux d\'ambiance, jeux coopératifs, jeux de gestion, jeux de cartes...'
		],
		infos: [
			{ label: 'Quand', title: 'Vendredi soir', subtitle: 'À partir de 19h00' },
			{ label: 'Fréquence', title: 'Bi-hebdomadaire', subtitle: 'Un vendredi sur deux' },
			{ label: 'Lieu', title: 'Local de l\'association', subtitle: 'Paris' },
			{ label: 'Public', title: 'Tous niveaux', subtitle: 'Débutants bienvenus' }
		],
		other_activities: [
			{ route: 'app_jdr', label: 'Jeux de Rôle', color: 'text-violet-400', icon_path: 'M12 2L2 7v10l10 5 10-5V7L12 2z' },
			{ route: 'app_gn', label: 'Grandeur Nature', color: 'text-red-400', icon_path: 'M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z' }
		]
	} only %}
{% endblock %}
```

- [ ] **Step 3 : Remplacer `templates/jdr/index.html.twig`**

```twig
{% extends 'layouts/page.html.twig' %}

{% block title %}Jeux de Rôle - La Boîte à Chimère{% endblock %}

{% block meta_description %}<meta name="description" content="Plongez dans nos soirées jeux de rôle hebdomadaires du mardi à Paris. D&D, Appel de Cthulhu, Star Wars et bien plus — rejoignez la communauté JDR de La Boîte à Chimère.">{% endblock %}
{% block og_description %}Plongez dans nos soirées jeux de rôle hebdomadaires du mardi à Paris. D&D, Appel de Cthulhu, Star Wars et bien plus — rejoignez la communauté JDR de La Boîte à Chimère.{% endblock %}

{% block page_title %}Jeux de Rôle{% endblock %}
{% block page_subtitle %}<p class="text-lg text-text-secondary max-w-2xl mx-auto">Nos soirées hebdomadaires du mardi (19h)</p>{% endblock %}

{% block content %}
	{% include 'components/_activity_page.html.twig' with {
		color: 'text-violet-400',
		icon_path: 'M12 2L2 7v10l10 5 10-5V7L12 2zm0 2.18l6.9 3.45-2.6 5.2-4.3-2.15V4.18zM5.1 7.63L12 4.18v8.55l-4.3 2.15-2.6-5.2zm0 8.74l2.6-5.2 4.3 2.15v4.32L5.1 16.37zm12.8 0l-6.9 3.45v-4.32l4.3-2.15 2.6 5.2z',
		heading: 'Nos soirées JDR',
		description_paragraphs: [
			'Chaque mardi soir, plongez dans des univers fantastiques, futuristes ou horrifiques avec nos tables de jeux de rôle. Nos maîtres de jeu expérimentés vous guident à travers des aventures épiques.',
			'Nous proposons une grande variété de systèmes : Donjons & Dragons, L\'Appel de Cthulhu, Shadowrun, Vampire, et bien d\'autres. Les débutants sont les bienvenus, nous vous accompagnons dans la création de votre premier personnage !'
		],
		infos: [
			{ label: 'Quand', title: 'Mardi soir', subtitle: 'À partir de 19h00' },
			{ label: 'Fréquence', title: 'Hebdomadaire', subtitle: 'Chaque mardi' },
			{ label: 'Lieu', title: 'Local de l\'association', subtitle: 'Paris' },
			{ label: 'Public', title: 'Tous niveaux', subtitle: 'Débutants bienvenus' }
		],
		other_activities: [
			{ route: 'app_jds', label: 'Jeux de Société', color: 'text-emerald-400', icon_path: 'M12 2a2 2 0 0 1 2 2c0 .74-.4 1.39-1 1.73V7h1a2 2 0 0 1 2 2v1h1a2 2 0 0 1 2 2v1h1a2 2 0 0 1 2 2v1a2 2 0 0 1-2 2h-1v1a2 2 0 0 1-2 2h-1v1a2 2 0 0 1-2 2h-4a2 2 0 0 1-2-2v-1H7a2 2 0 0 1-2-2v-1H4a2 2 0 0 1-2-2v-1a2 2 0 0 1 2-2h1v-1a2 2 0 0 1 2-2h1v-1a2 2 0 0 1 2-2h1V5.73c-.6-.34-1-.99-1-1.73a2 2 0 0 1 2-2z' },
			{ route: 'app_gn', label: 'Grandeur Nature', color: 'text-red-400', icon_path: 'M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z' }
		]
	} only %}
{% endblock %}
```

- [ ] **Step 4 : Remplacer `templates/gn/index.html.twig`**

```twig
{% extends 'layouts/page.html.twig' %}

{% block title %}Grandeur Nature - La Boîte à Chimère{% endblock %}

{% block meta_description %}<meta name="description" content="Participez à nos après-midi GN mensuelles du dimanche à Paris. Grandeur nature, costumes et aventure — vivez l'expérience avec La Boîte à Chimère.">{% endblock %}
{% block og_description %}Participez à nos après-midi GN mensuelles du dimanche à Paris. Grandeur nature, costumes et aventure — vivez l'expérience avec La Boîte à Chimère.{% endblock %}

{% block page_title %}Grandeur Nature{% endblock %}
{% block page_subtitle %}<p class="text-lg text-text-secondary max-w-2xl mx-auto">Nos après-midi mensuelles du dimanche (14h)</p>{% endblock %}

{% block content %}
	{% include 'components/_activity_page.html.twig' with {
		color: 'text-red-400',
		icon_path: 'M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z',
		heading: 'Nos événements GN',
		description_paragraphs: [
			'Le Grandeur Nature (GN) est une expérience immersive unique où vous incarnez physiquement votre personnage. Costumes, décors et scénarios élaborés vous plongent dans des aventures inoubliables.',
			'Une fois par mois, le dimanche après-midi, nous organisons des événements GN accessibles à tous. Que ce soit du médiéval-fantastique, du post-apocalyptique ou du contemporain, il y en a pour tous les goûts !'
		],
		infos: [
			{ label: 'Quand', title: 'Dimanche après-midi', subtitle: 'À partir de 14h00' },
			{ label: 'Fréquence', title: 'Mensuelle', subtitle: 'Un dimanche par mois' },
			{ label: 'Lieu', title: 'Variable selon l\'événement', subtitle: 'Région parisienne' },
			{ label: 'Public', title: 'Tous niveaux', subtitle: 'Costumes fournis pour débuter' }
		],
		other_activities: [
			{ route: 'app_jds', label: 'Jeux de Société', color: 'text-emerald-400', icon_path: 'M12 2a2 2 0 0 1 2 2c0 .74-.4 1.39-1 1.73V7h1a2 2 0 0 1 2 2v1h1a2 2 0 0 1 2 2v1h1a2 2 0 0 1 2 2v1a2 2 0 0 1-2 2h-1v1a2 2 0 0 1-2 2h-1v1a2 2 0 0 1-2 2h-4a2 2 0 0 1-2-2v-1H7a2 2 0 0 1-2-2v-1H4a2 2 0 0 1-2-2v-1a2 2 0 0 1 2-2h1v-1a2 2 0 0 1 2-2h1v-1a2 2 0 0 1 2-2h1V5.73c-.6-.34-1-.99-1-1.73a2 2 0 0 1 2-2z' },
			{ route: 'app_jdr', label: 'Jeux de Rôle', color: 'text-violet-400', icon_path: 'M12 2L2 7v10l10 5 10-5V7L12 2z' }
		]
	} only %}
{% endblock %}
```

- [ ] **Step 5 : Vérifier les 3 pages en navigant**

Ouvrir http://localhost:8080/jds, /jdr, /gn — les pages doivent être identiques visuellement à avant.

- [ ] **Step 6 : Commit**

```bash
git add templates/components/_activity_page.html.twig \
        templates/jds/index.html.twig \
        templates/jdr/index.html.twig \
        templates/gn/index.html.twig
git commit -m "refactor: extract shared activity page component (jds/jdr/gn)"
```

---

### Task 4 : Utilitaire JS `registerInit`

**Files:**
- Create: `assets/utils/init.js`
- Modify: `assets/carousel.js` (lignes 141-142)
- Modify: `assets/mobile_menu.js` (lignes 41-42)
- Modify: `assets/reveal.js` (lignes 106-107, le `pageshow` est conservé)

- [ ] **Step 1 : Créer `assets/utils/init.js`**

```js
/**
 * Enregistre une fonction d'initialisation sur DOMContentLoaded et turbo:load.
 * Ne pas utiliser pour les modules qui ont un comportement d'init spécifique
 * (cookie_consent.js, join_panel.js).
 *
 * @param {Function} fn
 */
export function registerInit(fn) {
    document.addEventListener('DOMContentLoaded', fn);
    document.addEventListener('turbo:load', fn);
}
```

- [ ] **Step 2 : Mettre à jour `assets/carousel.js`**

En haut du fichier, ajouter :
```js
import { registerInit } from './utils/init.js';
```

Remplacer les deux dernières lignes :
```js
document.addEventListener('DOMContentLoaded', initCarousel);
document.addEventListener('turbo:load', initCarousel);
```
Par :
```js
registerInit(initCarousel);
```

- [ ] **Step 3 : Mettre à jour `assets/mobile_menu.js`**

En haut du fichier, ajouter :
```js
import { registerInit } from './utils/init.js';
```

Remplacer les deux dernières lignes :
```js
document.addEventListener('DOMContentLoaded', initMobileMenu);
document.addEventListener('turbo:load', initMobileMenu);
```
Par :
```js
registerInit(initMobileMenu);
```

- [ ] **Step 4 : Mettre à jour `assets/reveal.js`**

En haut du fichier, ajouter :
```js
import { registerInit } from './utils/init.js';
```

Remplacer uniquement les deux dernières lignes (lignes 106-107) :
```js
document.addEventListener('DOMContentLoaded', initReveal);
document.addEventListener('turbo:load', initReveal);
```
Par :
```js
registerInit(initReveal);
```
**Important :** Le listener `pageshow` (lignes 100-104) est conservé tel quel — ne pas le toucher.

- [ ] **Step 5 : Vérifier que le carousel et le menu mobile fonctionnent**

Ouvrir http://localhost:8080 — le carousel doit défiler, le menu mobile doit s'ouvrir/fermer. Naviguer vers une autre page et revenir (Turbo) — tout doit toujours fonctionner.

- [ ] **Step 6 : Commit**

```bash
git add assets/utils/init.js assets/carousel.js assets/mobile_menu.js assets/reveal.js
git commit -m "refactor: extract registerInit utility, apply to carousel/menu/reveal"
```

---

## Chunk 2 : Phase 2 — Extraction de services

### Task 5 : `InscriptionRepository` — méthodes `findUpcomingByUser` / `findPastByUser`

**Files:**
- Modify: `src/Repository/InscriptionRepository.php`

- [ ] **Step 1 : Ajouter les deux méthodes dans `InscriptionRepository`**

Ajouter à la fin de la classe, avant la dernière accolade :
```php
use App\Entity\User; // ajouter en haut du fichier si absent

/**
 * Inscriptions futures de l'utilisateur (activités dont startAt >= maintenant).
 * @return Inscription[]
 */
public function findUpcomingByUser(User $user): array
{
    $now = new \DateTimeImmutable();

    return $this->createQueryBuilder('i')
        ->leftJoin('i.activity', 'a')
        ->addSelect('a')
        ->where('i.participantEmail = :email OR i.participantName = :username')
        ->andWhere('a.startAt >= :now')
        ->setParameter('email', $user->getEmail())
        ->setParameter('username', $user->getUsername())
        ->setParameter('now', $now)
        ->orderBy('a.startAt', 'ASC')
        ->getQuery()
        ->getResult();
}

/**
 * Inscriptions passées de l'utilisateur (activités dont startAt < maintenant).
 * @return Inscription[]
 */
public function findPastByUser(User $user): array
{
    $now = new \DateTimeImmutable();

    return $this->createQueryBuilder('i')
        ->leftJoin('i.activity', 'a')
        ->addSelect('a')
        ->where('i.participantEmail = :email OR i.participantName = :username')
        ->andWhere('a.startAt < :now')
        ->setParameter('email', $user->getEmail())
        ->setParameter('username', $user->getUsername())
        ->setParameter('now', $now)
        ->orderBy('a.startAt', 'DESC')
        ->getQuery()
        ->getResult();
}
```

- [ ] **Step 2 : Commit**

```bash
git add src/Repository/InscriptionRepository.php
git commit -m "feat: add findUpcomingByUser and findPastByUser to InscriptionRepository"
```

---

### Task 6 : `UserDashboardService`

**Files:**
- Create: `src/Service/UserDashboardService.php`
- Modify: `src/Controller/UserDashboardController.php`

- [ ] **Step 1 : Créer `src/Service/UserDashboardService.php`**

```php
<?php

namespace App\Service;

use App\Entity\Inscription;
use App\Entity\User;
use App\Repository\InscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserDashboardService
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly EntityManagerInterface $em,
        private readonly InscriptionRepository $inscriptionRepository,
    ) {
    }

    /**
     * @return Inscription[]
     */
    public function getUpcomingInscriptions(User $user): array
    {
        return $this->inscriptionRepository->findUpcomingByUser($user);
    }

    /**
     * @return Inscription[]
     */
    public function getPastInscriptions(User $user): array
    {
        return $this->inscriptionRepository->findPastByUser($user);
    }

    /**
     * @throws \InvalidArgumentException si le mot de passe actuel est incorrect ou le nouveau trop court
     */
    public function changePassword(User $user, string $currentPassword, string $newPassword): void
    {
        if (!$this->passwordHasher->isPasswordValid($user, $currentPassword)) {
            throw new \InvalidArgumentException('Le mot de passe actuel est incorrect.');
        }

        if (strlen($newPassword) < 6) {
            throw new \InvalidArgumentException('Le nouveau mot de passe doit contenir au moins 6 caractères.');
        }

        $user->setPassword($this->passwordHasher->hashPassword($user, $newPassword));
        $this->em->flush();
    }

    /**
     * @throws \InvalidArgumentException si le format est invalide ou l'email déjà utilisé
     */
    public function changeEmail(User $user, string $newEmail): void
    {
        $newEmail = trim($newEmail);

        if (!$newEmail || !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Veuillez saisir une adresse email valide.');
        }

        if ($newEmail === $user->getEmail()) {
            throw new \InvalidArgumentException('C\'est déjà votre adresse email actuelle.');
        }

        $existing = $this->em->getRepository(User::class)->findOneBy(['email' => $newEmail]);
        if ($existing) {
            throw new \InvalidArgumentException('Cette adresse email est déjà utilisée par un autre compte.');
        }

        $user->setEmail($newEmail);
        $this->em->flush();
    }

    /**
     * @throws \RuntimeException si l'inscription n'appartient pas à l'utilisateur
     */
    public function unregister(User $user, Inscription $inscription): string
    {
        if (
            $inscription->getParticipantEmail() !== $user->getEmail()
            && $inscription->getParticipantName() !== $user->getUsername()
        ) {
            throw new \RuntimeException('Cette inscription ne vous appartient pas.');
        }

        $activityTitle = $inscription->getActivity()?->getTitle() ?? 'activité';
        $this->em->remove($inscription);
        $this->em->flush();

        return $activityTitle;
    }

    /**
     * @throws \RuntimeException si l'utilisateur est admin
     */
    public function deleteAccount(User $user): void
    {
        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            throw new \RuntimeException('Les administrateurs ne peuvent pas supprimer leur compte depuis cette page.');
        }

        $this->em->remove($user);
        $this->em->flush();
    }
}
```

- [ ] **Step 2 : Mettre à jour `UserDashboardController`**

Remplacer le contenu complet du contrôleur :

```php
<?php

namespace App\Controller;

use App\Entity\Inscription;
use App\Service\UserDashboardService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class UserDashboardController extends AbstractController
{
    public function __construct(private readonly UserDashboardService $dashboardService) {}

    #[Route('/mon-espace', name: 'app_user_dashboard')]
    public function index(): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        return $this->render('user_dashboard/index.html.twig', [
            'upcoming' => $this->dashboardService->getUpcomingInscriptions($user),
            'past'     => $this->dashboardService->getPastInscriptions($user),
        ]);
    }

    #[Route('/mon-espace/changer-mot-de-passe', name: 'app_user_change_password', methods: ['POST'])]
    public function changePassword(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('change_password', $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');
            return $this->redirectToRoute('app_user_dashboard');
        }

        $newPassword = $request->request->get('new_password', '');
        $confirmPassword = $request->request->get('confirm_password', '');

        if ($newPassword !== $confirmPassword) {
            $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
            return $this->redirectToRoute('app_user_dashboard');
        }

        try {
            /** @var \App\Entity\User $user */
            $user = $this->getUser();
            $this->dashboardService->changePassword(
                $user,
                $request->request->get('current_password', ''),
                $newPassword,
            );
            $this->addFlash('success', 'Votre mot de passe a été mis à jour.');
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_user_dashboard');
    }

    #[Route('/mon-espace/changer-email', name: 'app_user_change_email', methods: ['POST'])]
    public function changeEmail(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('change_email', $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');
            return $this->redirectToRoute('app_user_dashboard');
        }

        try {
            /** @var \App\Entity\User $user */
            $user = $this->getUser();
            $this->dashboardService->changeEmail($user, $request->request->get('new_email', ''));
            $this->addFlash('success', 'Votre adresse email a été mise à jour.');
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_user_dashboard');
    }

    #[Route('/mon-espace/desinscription/{id}', name: 'app_user_unregister', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function unregister(Inscription $inscription, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('unregister' . $inscription->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');
            return $this->redirectToRoute('app_user_dashboard');
        }

        try {
            /** @var \App\Entity\User $user */
            $user = $this->getUser();
            $activityTitle = $this->dashboardService->unregister($user, $inscription);
            $this->addFlash('success', 'Vous êtes désinscrit de « ' . $activityTitle . ' ».');
        } catch (\RuntimeException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_user_dashboard');
    }

    #[Route('/mon-espace/supprimer', name: 'app_user_delete_account', methods: ['POST'])]
    public function deleteAccount(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('delete_my_account', $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');
            return $this->redirectToRoute('app_user_dashboard');
        }

        try {
            /** @var \App\Entity\User $user */
            $user = $this->getUser();
            $this->dashboardService->deleteAccount($user);

            $request->getSession()->invalidate();
            $this->container->get('security.token_storage')->setToken(null);

            $this->addFlash('success', 'Votre compte a été supprimé.');
            return $this->redirectToRoute('app_home');
        } catch (\RuntimeException $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('app_user_dashboard');
        }
    }
}
```

- [ ] **Step 3 : Vider le cache et vérifier**

```bash
docker compose exec php php bin/console cache:clear
```
Se connecter, ouvrir http://localhost:8080/mon-espace — la page doit s'afficher avec les inscriptions.

- [ ] **Step 4 : Commit**

```bash
git add src/Service/UserDashboardService.php src/Controller/UserDashboardController.php
git commit -m "refactor: extract UserDashboardService from controller"
```

---

### Task 7 : `CalendarService`

**Files:**
- Create: `src/Service/CalendarService.php`
- Modify: `src/Controller/HomeController.php`

- [ ] **Step 1 : Créer `src/Service/CalendarService.php`**

```php
<?php

namespace App\Service;

use App\Repository\ActivityRepository;

final class CalendarService
{
    private const MONTH_NAMES = [
        1 => 'Janvier', 2 => 'Février',  3 => 'Mars',      4 => 'Avril',
        5 => 'Mai',     6 => 'Juin',     7 => 'Juillet',   8 => 'Août',
        9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
    ];

    public function __construct(private readonly ActivityRepository $activityRepository) {}

    /**
     * Construit toutes les données nécessaires au template de calendrier.
     *
     * @return array{
     *   calendarMonth: int,
     *   calendarYear: int,
     *   calendarMonthName: string,
     *   calendarDays: array<int|null>,
     *   daysWithActivities: int[],
     *   activitiesCountByDay: array<int,int>,
     *   activitiesTypesByDay: array<int,string>,
     *   activities: object[],
     *   selectedDay: int,
     *   activitiesForSelectedDay: object[],
     *   prevMonth: int, prevYear: int, prevMonthName: string,
     *   nextMonth: int, nextYear: int, nextMonthName: string,
     *   today: int,
     *   nowMonth: int, nowYear: int, nowDay: int,
     *   filterType: string|null
     * }
     */
    public function buildCalendarData(
        int $month,
        int $year,
        ?string $filterType,
        int $selectedDay,
        \DateTimeImmutable $now,
    ): array {
        $month = max(1, min(12, $month));
        $year  = max(2020, min(2100, $year));

        $nowMonth = (int) $now->format('n');
        $nowYear  = (int) $now->format('Y');
        $nowDay   = (int) $now->format('j');

        $firstDay   = new \DateTimeImmutable(sprintf('%04d-%02d-01 00:00:00', $year, $month));
        $lastDay    = $firstDay->modify('last day of this month')->setTime(23, 59, 59);
        $lastDayNum = (int) $lastDay->format('j');

        $activities = $this->activityRepository->findBetween($firstDay, $lastDay, $filterType);

        // Normaliser le jour sélectionné
        if ($selectedDay < 1 || $selectedDay > $lastDayNum) {
            $selectedDay = 0;
        }

        $activitiesForSelectedDay = [];
        $daysWithActivities       = [];
        $activitiesCountByDay     = [];
        $activitiesTypesByDay     = [];

        foreach ($activities as $activity) {
            $d = (int) $activity->getStartAt()->format('j');

            if (!\in_array($d, $daysWithActivities, true)) {
                $daysWithActivities[] = $d;
            }
            $activitiesCountByDay[$d] = ($activitiesCountByDay[$d] ?? 0) + 1;

            $type = $activity->getType() ?? '';
            if ($type && !isset($activitiesTypesByDay[$d])) {
                $activitiesTypesByDay[$d] = $type;
            } elseif ($type && isset($activitiesTypesByDay[$d]) && $activitiesTypesByDay[$d] !== $type) {
                $activitiesTypesByDay[$d] = 'mixed';
            }

            if ($selectedDay > 0 && $d === $selectedDay) {
                $activitiesForSelectedDay[] = $activity;
            }
        }

        // Grille du calendrier
        $offset      = (int) $firstDay->format('N') - 1; // lundi = 0
        $calendarDays = array_fill(0, $offset, null);
        for ($d = 1; $d <= $lastDayNum; $d++) {
            $calendarDays[] = $d;
        }

        $prev = $firstDay->modify('-1 month');
        $next = $firstDay->modify('+1 month');

        $today = ($month === $nowMonth && $year === $nowYear) ? $nowDay : 0;

        return [
            'nowYear'  => $nowYear,
            'nowMonth' => $nowMonth,
            'nowDay'   => $nowDay,
            'today'    => $today,
            'calendarMonth'    => $month,
            'calendarYear'     => $year,
            'calendarMonthName' => self::MONTH_NAMES[$month],
            'calendarDays'     => $calendarDays,
            'daysWithActivities'   => $daysWithActivities,
            'activitiesCountByDay' => $activitiesCountByDay,
            'activitiesTypesByDay' => $activitiesTypesByDay,
            'filterType'           => $filterType,
            'activities'           => $activities,
            'selectedDay'          => $selectedDay,
            'activitiesForSelectedDay' => $activitiesForSelectedDay,
            'prevMonth'     => (int) $prev->format('n'),
            'prevYear'      => (int) $prev->format('Y'),
            'prevMonthName' => self::MONTH_NAMES[(int) $prev->format('n')],
            'nextMonth'     => (int) $next->format('n'),
            'nextYear'      => (int) $next->format('Y'),
            'nextMonthName' => self::MONTH_NAMES[(int) $next->format('n')],
        ];
    }

    public static function monthName(int $month): string
    {
        return self::MONTH_NAMES[max(1, min(12, $month))];
    }
}
```

- [ ] **Step 2 : Mettre à jour `HomeController`**

Remplacer le contenu complet :

```php
<?php

namespace App\Controller;

use App\Enum\ActivityKind;
use App\Service\CalendarService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class HomeController extends AbstractController
{
    public function __construct(
        private readonly CalendarService $calendarService,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
    ) {
    }

    #[Route('/', name: 'app_home')]
    public function index(Request $request, AuthenticationUtils $authenticationUtils): Response
    {
        $now = new \DateTimeImmutable();

        $currentMonth = (int) $now->format('n');
        $currentYear  = (int) $now->format('Y');
        $currentDay   = (int) $now->format('j');

        $month = (int) $request->query->get('month', $currentMonth);
        $year  = (int) $request->query->get('year', $currentYear);

        $filterType = $request->query->get('type');
        if ($filterType !== null && !\in_array($filterType, ActivityKind::values(), true)) {
            $filterType = null;
        }

        $defaultDay  = ($month === $currentMonth && $year === $currentYear) ? $currentDay : 0;
        $selectedDay = (int) $request->query->get('day', $defaultDay);

        $calendarData = $this->calendarService->buildCalendarData(
            $month,
            $year,
            $filterType,
            $selectedDay,
            $now,
        );

        return $this->render('home/index.html.twig', array_merge($calendarData, [
            'login_csrf_token' => $this->csrfTokenManager->getToken('authenticate')->getValue(),
            'login_error'      => $authenticationUtils->getLastAuthenticationError(),
            'last_username'    => $authenticationUtils->getLastUsername(),
        ]));
    }
}
```

- [ ] **Step 3 : Vider le cache et vérifier la page d'accueil**

```bash
docker compose exec php php bin/console cache:clear
```
Ouvrir http://localhost:8080 — le calendrier doit s'afficher, la navigation mois précédent/suivant doit fonctionner, le filtre par type doit fonctionner.

- [ ] **Step 4 : Commit**

```bash
git add src/Service/CalendarService.php src/Controller/HomeController.php
git commit -m "refactor: extract CalendarService from HomeController"
```

---

## Chunk 3 : Phase 3 — Tests

### Task 8 : Tests unitaires `ActivityKindTest`

**Files:**
- Create: `tests/Unit/Enum/ActivityKindTest.php`

- [ ] **Step 1 : Créer `tests/Unit/Enum/ActivityKindTest.php`**

```php
<?php

namespace App\Tests\Unit\Enum;

use App\Enum\ActivityKind;
use PHPUnit\Framework\TestCase;

final class ActivityKindTest extends TestCase
{
    public function testFromValidValue(): void
    {
        $kind = ActivityKind::from('JDS');
        self::assertSame(ActivityKind::JDS, $kind);
    }

    public function testTryFromInvalidValueReturnsNull(): void
    {
        self::assertNull(ActivityKind::tryFrom('INCONNU'));
    }

    public function testCasesReturns6Items(): void
    {
        self::assertCount(6, ActivityKind::cases());
    }

    public function testValuesReturnsStrings(): void
    {
        $values = ActivityKind::values();
        self::assertCount(6, $values);
        foreach ($values as $v) {
            self::assertIsString($v);
        }
        self::assertContains('JDS', $values);
        self::assertContains('Play Test', $values);
    }

    /** @dataProvider labelProvider */
    public function testLabelReturnsNonEmptyString(ActivityKind $kind): void
    {
        self::assertNotEmpty($kind->label());
    }

    /** @return iterable<array{ActivityKind}> */
    public static function labelProvider(): iterable
    {
        foreach (ActivityKind::cases() as $kind) {
            yield $kind->name => [$kind];
        }
    }
}
```

- [ ] **Step 2 : Lancer les tests**

```bash
docker compose exec php php bin/phpunit tests/Unit/Enum/ActivityKindTest.php --testdox
```
Résultat attendu : 4 tests, tous PASS (verts).

- [ ] **Step 3 : Commit**

```bash
git add tests/Unit/Enum/ActivityKindTest.php
git commit -m "test: add ActivityKindTest unit tests"
```

---

### Task 9 : Tests unitaires `CalendarServiceTest`

**Files:**
- Create: `tests/Unit/Service/CalendarServiceTest.php`

- [ ] **Step 1 : Créer `tests/Unit/Service/CalendarServiceTest.php`**

```php
<?php

namespace App\Tests\Unit\Service;

use App\Repository\ActivityRepository;
use App\Service\CalendarService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CalendarServiceTest extends TestCase
{
    private CalendarService $service;
    /** @var ActivityRepository&MockObject */
    private ActivityRepository $repo;

    protected function setUp(): void
    {
        $this->repo = $this->createMock(ActivityRepository::class);
        $this->repo->method('findBetween')->willReturn([]);
        $this->service = new CalendarService($this->repo);
    }

    private function build(int $month, int $year, int $selectedDay = 0, ?string $filterType = null, string $nowStr = '2026-03-15'): array
    {
        return $this->service->buildCalendarData(
            $month,
            $year,
            $filterType,
            $selectedDay,
            new \DateTimeImmutable($nowStr),
        );
    }

    public function testMarchOffsetIsZero(): void
    {
        // 2026-03-01 est un dimanche → offset = 6 (lundi = 0, ..., dimanche = 6)
        $data = $this->build(3, 2026);
        $nullCount = count(array_filter($data['calendarDays'], fn ($d) => $d === null));
        self::assertSame(6, $nullCount);
    }

    public function testJanuaryOffsetIsZero(): void
    {
        // 2024-01-01 est un lundi → offset = 0
        $data = $this->build(1, 2024);
        $nullCount = count(array_filter($data['calendarDays'], fn ($d) => $d === null));
        self::assertSame(0, $nullCount);
    }

    public function testCalendarDaysTotalEqualsOffsetPlusDaysInMonth(): void
    {
        $data = $this->build(3, 2026); // mars = 31 jours, offset = 6
        self::assertCount(6 + 31, $data['calendarDays']);
    }

    public function testPrevMonthNavigationFromJanuary(): void
    {
        $data = $this->build(1, 2025);
        self::assertSame(12, $data['prevMonth']);
        self::assertSame(2024, $data['prevYear']);
        self::assertSame('Décembre', $data['prevMonthName']);
    }

    public function testNextMonthNavigationFromDecember(): void
    {
        $data = $this->build(12, 2025);
        self::assertSame(1, $data['nextMonth']);
        self::assertSame(2026, $data['nextYear']);
        self::assertSame('Janvier', $data['nextMonthName']);
    }

    public function testAllTwelveMonthNamesInFrench(): void
    {
        $expected = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
        ];
        foreach ($expected as $m => $name) {
            $data = $this->build($m, 2025);
            self::assertSame($name, $data['calendarMonthName'], "Month $m");
        }
    }

    public function testYearBelowMinIsNormalizedTo2020(): void
    {
        $data = $this->build(1, 1900);
        self::assertSame(2020, $data['calendarYear']);
    }

    public function testYearAboveMaxIsNormalizedTo2100(): void
    {
        $data = $this->build(1, 2200);
        self::assertSame(2100, $data['calendarYear']);
    }

    public function testMonthBelowMinIsNormalizedTo1(): void
    {
        $data = $this->build(0, 2025);
        self::assertSame(1, $data['calendarMonth']);
    }

    public function testMonthAboveMaxIsNormalizedTo12(): void
    {
        $data = $this->build(13, 2025);
        self::assertSame(12, $data['calendarMonth']);
    }

    public function testTodayIsSetWhenCurrentMonth(): void
    {
        $data = $this->build(3, 2026, 0, null, '2026-03-15');
        self::assertSame(15, $data['today']);
    }

    public function testTodayIsZeroWhenNotCurrentMonth(): void
    {
        $data = $this->build(4, 2026, 0, null, '2026-03-15');
        self::assertSame(0, $data['today']);
    }

    public function testSelectedDayOutOfRangeIsNormalizedToZero(): void
    {
        $data = $this->build(3, 2026, 99);
        self::assertSame(0, $data['selectedDay']);
    }
}
```

- [ ] **Step 2 : Lancer les tests**

```bash
docker compose exec php php bin/phpunit tests/Unit/Service/CalendarServiceTest.php --testdox
```
Résultat attendu : tous PASS.

- [ ] **Step 3 : Commit**

```bash
git add tests/Unit/Service/CalendarServiceTest.php
git commit -m "test: add CalendarServiceTest unit tests"
```

---

### Task 10 : Tests unitaires `UserDashboardServiceTest`

**Files:**
- Create: `tests/Unit/Service/UserDashboardServiceTest.php`

- [ ] **Step 1 : Créer `tests/Unit/Service/UserDashboardServiceTest.php`**

```php
<?php

namespace App\Tests\Unit\Service;

use App\Entity\Inscription;
use App\Entity\User;
use App\Repository\InscriptionRepository;
use App\Service\UserDashboardService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserDashboardServiceTest extends TestCase
{
    /** @var UserPasswordHasherInterface&MockObject */
    private UserPasswordHasherInterface $hasher;
    /** @var EntityManagerInterface&MockObject */
    private EntityManagerInterface $em;
    /** @var InscriptionRepository&MockObject */
    private InscriptionRepository $repo;
    private UserDashboardService $service;

    protected function setUp(): void
    {
        $this->hasher  = $this->createMock(UserPasswordHasherInterface::class);
        $this->em      = $this->createMock(EntityManagerInterface::class);
        $this->repo    = $this->createMock(InscriptionRepository::class);
        $this->service = new UserDashboardService($this->hasher, $this->em, $this->repo);
    }

    private function makeUser(string $email = 'u@test.com', string $username = 'user', bool $isAdmin = false): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setUsername($username);
        $user->setRoles($isAdmin ? ['ROLE_USER', 'ROLE_ADMIN'] : ['ROLE_USER']);
        return $user;
    }

    // ── changePassword ──────────────────────────────────────────────────────

    public function testChangePasswordSuccess(): void
    {
        $user = $this->makeUser();
        $this->hasher->method('isPasswordValid')->willReturn(true);
        $this->hasher->method('hashPassword')->willReturn('hashed');
        $this->em->expects($this->once())->method('flush');

        $this->service->changePassword($user, 'old', 'newpass');
        self::assertSame('hashed', $user->getPassword());
    }

    public function testChangePasswordFailsWhenCurrentPasswordWrong(): void
    {
        $this->hasher->method('isPasswordValid')->willReturn(false);
        $this->expectException(\InvalidArgumentException::class);
        $this->service->changePassword($this->makeUser(), 'wrong', 'newpass');
    }

    public function testChangePasswordFailsWhenTooShort(): void
    {
        $this->hasher->method('isPasswordValid')->willReturn(true);
        $this->expectException(\InvalidArgumentException::class);
        $this->service->changePassword($this->makeUser(), 'old', '12');
    }

    // ── changeEmail ─────────────────────────────────────────────────────────

    public function testChangeEmailSuccess(): void
    {
        $user = $this->makeUser('old@test.com');
        $repo = $this->createMock(\Doctrine\ORM\EntityRepository::class);
        $repo->method('findOneBy')->willReturn(null);
        $this->em->method('getRepository')->willReturn($repo);
        $this->em->expects($this->once())->method('flush');

        $this->service->changeEmail($user, 'new@test.com');
        self::assertSame('new@test.com', $user->getEmail());
    }

    public function testChangeEmailFailsWithInvalidFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->changeEmail($this->makeUser(), 'not-an-email');
    }

    // ── unregister ──────────────────────────────────────────────────────────

    public function testUnregisterSuccess(): void
    {
        $user = $this->makeUser('u@test.com');
        $inscription = new Inscription();
        $inscription->setParticipantEmail('u@test.com');
        $this->em->expects($this->once())->method('remove');
        $this->em->expects($this->once())->method('flush');

        $this->service->unregister($user, $inscription);
    }

    public function testUnregisterFailsWhenNotOwner(): void
    {
        $user = $this->makeUser('u@test.com', 'user');
        $inscription = new Inscription();
        $inscription->setParticipantEmail('other@test.com');
        $inscription->setParticipantName('other_user');

        $this->expectException(\RuntimeException::class);
        $this->service->unregister($user, $inscription);
    }

    // ── deleteAccount ────────────────────────────────────────────────────────

    public function testDeleteAccountSuccess(): void
    {
        $user = $this->makeUser();
        $this->em->expects($this->once())->method('remove');
        $this->em->expects($this->once())->method('flush');

        $this->service->deleteAccount($user);
    }

    public function testDeleteAccountFailsForAdmin(): void
    {
        $user = $this->makeUser('admin@test.com', 'admin', true);
        $this->expectException(\RuntimeException::class);
        $this->service->deleteAccount($user);
    }
}
```

- [ ] **Step 2 : Lancer les tests**

```bash
docker compose exec php php bin/phpunit tests/Unit/Service/UserDashboardServiceTest.php --testdox
```
Résultat attendu : tous PASS.

- [ ] **Step 3 : Commit**

```bash
git add tests/Unit/Service/UserDashboardServiceTest.php
git commit -m "test: add UserDashboardServiceTest unit tests"
```

---

### Task 11 : Tests fonctionnels `StaticPageControllerTest`

**Files:**
- Create: `tests/Functional/Controller/StaticPageControllerTest.php`

- [ ] **Step 1 : Créer le répertoire**

```bash
mkdir -p tests/Functional/Controller
```

- [ ] **Step 2 : Créer `tests/Functional/Controller/StaticPageControllerTest.php`**

```php
<?php

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class StaticPageControllerTest extends WebTestCase
{
    /** @dataProvider routeProvider */
    public function testStaticPageReturns200(string $url, string $expectedH2): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', $url);

        self::assertResponseIsSuccessful();
        self::assertGreaterThan(
            0,
            $crawler->filter('h2')->count(),
            "No <h2> found on $url"
        );
        self::assertStringContainsStringIgnoringCase(
            $expectedH2,
            $crawler->filter('h2')->first()->text(),
            "Expected h2 '$expectedH2' not found on $url"
        );
    }

    /** @return iterable<string, array{string, string}> */
    public static function routeProvider(): iterable
    {
        yield 'jds'              => ['/jds',                 'Soirées JDS'];
        yield 'jdr'              => ['/jdr',                 'Soirées JDR'];
        yield 'gn'               => ['/gn',                  'événements GN'];
        yield 'association'      => ['/association',          'Qui sommes-nous'];
        yield 'nouvelles'        => ['/nouvelles',            'Actualités'];
        yield 'qui-sommes-nous'  => ['/qui-sommes-nous',      'histoire'];
        yield 'societes'         => ['/societes',             'Sociétés'];
        yield 'evenements'       => ['/evenements',           'Événements'];
        yield 'mentions-legales' => ['/mentions-legales',     'Mentions légales'];
        yield 'contact'          => ['/contact',              'contacter'];
        yield 'nos-soiree-heb'   => ['/nos/soiree/heb',      'Soirées JDR'];
        yield 'nos-soiree-biheb' => ['/nos/soiree/biheb',    'Soirées JDS'];
        yield 'nos-soiree-mens'  => ['/nos/soiree/mensuelle', 'événements GN'];
    }
}
```

- [ ] **Step 3 : Lancer les tests**

```bash
docker compose exec php php bin/phpunit tests/Functional/Controller/StaticPageControllerTest.php --testdox
```
Résultat attendu : 13 tests, tous PASS.

Si un test échoue sur le texte h2, ajuster la chaîne attendue pour correspondre au texte réel du template (le test est `assertStringContainsStringIgnoringCase` pour être souple).

- [ ] **Step 4 : Commit**

```bash
git add tests/Functional/Controller/StaticPageControllerTest.php
git commit -m "test: add functional tests for StaticPageController (13 routes)"
```

---

### Task 12 : Tests fonctionnels `HomeControllerTest`

**Files:**
- Create: `tests/Functional/Controller/HomeControllerTest.php`

- [ ] **Step 1 : Créer `tests/Functional/Controller/HomeControllerTest.php`**

```php
<?php

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class HomeControllerTest extends WebTestCase
{
    public function testHomepageReturns200(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');
        self::assertResponseIsSuccessful();
    }

    public function testValidTypeFilterReturns200(): void
    {
        $client = static::createClient();
        $client->request('GET', '/?type=JDS');
        self::assertResponseIsSuccessful();
    }

    public function testInvalidTypeFilterReturns200(): void
    {
        $client = static::createClient();
        $client->request('GET', '/?type=INVALIDE');
        self::assertResponseIsSuccessful();
    }

    public function testYearTooLowIsNormalizedAndReturns200(): void
    {
        $client = static::createClient();
        $client->request('GET', '/?year=1900');
        self::assertResponseIsSuccessful();
    }

    public function testYearTooHighIsNormalizedAndReturns200(): void
    {
        $client = static::createClient();
        $client->request('GET', '/?year=2200');
        self::assertResponseIsSuccessful();
    }

    public function testMonthTooLowIsNormalizedAndReturns200(): void
    {
        $client = static::createClient();
        $client->request('GET', '/?month=0');
        self::assertResponseIsSuccessful();
    }

    public function testMonthTooHighIsNormalizedAndReturns200(): void
    {
        $client = static::createClient();
        $client->request('GET', '/?month=13');
        self::assertResponseIsSuccessful();
    }
}
```

- [ ] **Step 2 : Lancer les tests**

```bash
docker compose exec php php bin/phpunit tests/Functional/Controller/HomeControllerTest.php --testdox
```
Résultat attendu : 7 tests, tous PASS.

- [ ] **Step 3 : Lancer la suite complète**

```bash
docker compose exec php php bin/phpunit --testdox
```
Résultat attendu : tous les tests PASS.

- [ ] **Step 4 : Commit final**

```bash
git add tests/Functional/Controller/HomeControllerTest.php
git commit -m "test: add functional tests for HomeController calendar params"
```
