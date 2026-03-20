# Forgot Password Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implémenter la réinitialisation de mot de passe via email en utilisant `symfonycasts/reset-password-bundle`.

**Architecture:** Le bundle gère les tokens (hashés en base, expiration 1h, usage unique). Le contrôleur généré par `make:reset-password` est retravaillé pour coller au design du site (dark glass-card, Tailwind). Les liens `href="#"` dans les formulaires de connexion (desktop + mobile) sont mis à jour.

**Tech Stack:** Symfony 8 / PHP 8.4, symfonycasts/reset-password-bundle, Doctrine ORM, Symfony Mailer (smtp://mailer:1025), Twig + Tailwind CSS via AssetMapper

---

## File Map

| Fichier | Action | Responsabilité |
|---------|--------|----------------|
| `src/Controller/ResetPasswordController.php` | Créer (via make) puis modifier | Routes `/forgot-password` et `/reset-password/{token}` |
| `src/Entity/ResetPasswordRequest.php` | Créer (via make) | Entité bundle (token hashé, expiration) |
| `src/Repository/ResetPasswordRequestRepository.php` | Créer (via make) | Implémente `ResetPasswordRequestRepositoryInterface` |
| `src/Form/ResetPasswordRequestFormType.php` | Créer (via make) | Champ email pour demande |
| `src/Form/ChangePasswordFormType.php` | Créer (via make) | Champs nouveau mdp + confirmation |
| `templates/reset_password/request.html.twig` | Créer (via make) puis styliser | Page "Entrez votre email" |
| `templates/reset_password/check_email.html.twig` | Créer (via make) puis styliser | Page "Vérifiez votre email" |
| `templates/reset_password/reset.html.twig` | Créer (via make) puis styliser | Page nouveau mot de passe |
| `templates/reset_password/email.html.twig` | Créer (via make) puis adapter | Email envoyé à l'utilisateur |
| `templates/home/index.html.twig` | Modifier | Liens `href="#"` → route forgot password (×2) |
| `migrations/` | Créer (via diff) | Table `reset_password_request` |

---

## Task 1 : Installer le bundle et générer le scaffolding

**Files:**
- Modify: `composer.json` (via composer)
- Create: tous les fichiers listés dans la file map

- [ ] **Step 1: Installer le bundle**

```bash
composer require symfonycasts/reset-password-bundle
```

Expected: bundle installé, recipe Flex éventuellement appliquée.

- [ ] **Step 2: Générer le code via make:reset-password**

```bash
php bin/console make:reset-password
```

Répondre aux questions interactives :
- *Email property* : `email`
- *Redirect after reset* : `app_home`

Expected: fichiers générés dans `src/Controller/`, `src/Entity/`, `src/Form/`, `templates/reset_password/`.

- [ ] **Step 3: Générer la migration**

```bash
php bin/console doctrine:migrations:diff
```

Expected: un nouveau fichier dans `migrations/` créant la table `reset_password_request`.

- [ ] **Step 4: Exécuter la migration**

```bash
php bin/console doctrine:migrations:migrate --no-interaction
```

Expected: `[OK] Successfully executed 1 migration`.

- [ ] **Step 5: Vérifier que les routes sont enregistrées**

```bash
php bin/console debug:router | grep reset
```

Expected: routes `app_forgot_password_request` et `app_reset_password` présentes.

- [ ] **Step 6: Vider le cache**

```bash
php bin/console cache:clear
```

- [ ] **Step 7: Commit**

```bash
git add src/Controller/ResetPasswordController.php src/Entity/ResetPasswordRequest.php src/Repository/ResetPasswordRequestRepository.php src/Form/ResetPasswordRequestFormType.php src/Form/ChangePasswordFormType.php migrations/ templates/reset_password/ config/
git commit -m "feat: install reset-password-bundle and generate scaffolding"
```

---

## Task 2 : Styliser les templates Twig

Les templates générés par le bundle utilisent les classes Bootstrap par défaut. On les remplace par le design du site : fond `bg-custom-secondary`, bordures `border-custom`, boutons `bg-custom-orange`, etc.

**Files:**
- Modify: `templates/reset_password/request.html.twig`
- Modify: `templates/reset_password/check_email.html.twig`
- Modify: `templates/reset_password/reset.html.twig`
- Modify: `templates/reset_password/email.html.twig`

- [ ] **Step 1: Styliser request.html.twig (formulaire email)**

Remplacer le contenu de `templates/reset_password/request.html.twig` par :

```twig
{% extends 'base.html.twig' %}

{% block title %}Mot de passe oublié — La Boîte à Chimère{% endblock %}

{% block body %}
<div class="min-h-screen flex items-center justify-center px-4 py-20">
    <div class="w-full max-w-md">
        <div class="bg-custom-secondary border border-custom rounded-2xl shadow-2xl px-8 py-10">
            <h1 class="text-lg font-extrabold uppercase tracking-[0.15em] text-text-primary mb-2 text-center">Mot de passe oublié</h1>
            <p class="text-xs text-text-secondary text-center mb-8">Entrez votre adresse email. Vous recevrez un lien pour réinitialiser votre mot de passe.</p>

            {{ form_start(requestForm, { attr: { class: 'flex flex-col gap-4', 'data-turbo': 'false' } }) }}
                <div>
                    {{ form_label(requestForm.email, 'Email', { label_attr: { class: 'block text-[10px] font-bold uppercase tracking-wider text-text-secondary mb-1.5' } }) }}
                    {{ form_widget(requestForm.email, { attr: { class: 'w-full rounded-lg border border-custom bg-custom-tertiary px-4 py-2.5 text-sm text-text-primary placeholder-text-secondary/60 focus:border-custom-orange focus:outline-none focus:ring-1 focus:ring-custom-orange', placeholder: 'votre@email.com' } }) }}
                    {{ form_errors(requestForm.email) }}
                </div>
                <button type="submit" class="w-full rounded-lg bg-custom-orange px-6 py-3 text-[11px] font-extrabold uppercase tracking-[0.05em] text-text-primary hover:bg-orange-600 hover:scale-105 transition-all shadow-lg shadow-custom-orange/20">
                    Envoyer le lien
                </button>
            {{ form_end(requestForm) }}

            <div class="mt-6 text-center">
                <a href="{{ path('app_home') }}" class="text-xs text-text-secondary hover:text-custom-orange transition-colors">← Retour à l'accueil</a>
            </div>
        </div>
    </div>
</div>
{% endblock %}
```

- [ ] **Step 2: Styliser check_email.html.twig (confirmation envoi)**

Remplacer le contenu de `templates/reset_password/check_email.html.twig` par :

```twig
{% extends 'base.html.twig' %}

{% block title %}Email envoyé — La Boîte à Chimère{% endblock %}

{% block body %}
<div class="min-h-screen flex items-center justify-center px-4 py-20">
    <div class="w-full max-w-md">
        <div class="bg-custom-secondary border border-custom rounded-2xl shadow-2xl px-8 py-10 text-center">
            <div class="flex justify-center mb-6">
                <div class="h-14 w-14 rounded-full bg-custom-orange/10 flex items-center justify-center">
                    <svg class="h-7 w-7 text-custom-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
            </div>
            <h1 class="text-lg font-extrabold uppercase tracking-[0.15em] text-text-primary mb-3">Vérifiez votre email</h1>
            <p class="text-xs text-text-secondary leading-relaxed mb-8">
                Un email a été envoyé avec un lien de réinitialisation. Ce lien est valable <strong class="text-text-primary">1 heure</strong>.
            </p>
            <a href="{{ path('app_home') }}" class="inline-block rounded-lg bg-custom-orange px-6 py-3 text-[11px] font-extrabold uppercase tracking-[0.05em] text-text-primary hover:bg-orange-600 hover:scale-105 transition-all shadow-lg shadow-custom-orange/20">
                Retour à l'accueil
            </a>
        </div>
    </div>
</div>
{% endblock %}
```

- [ ] **Step 3: Styliser reset.html.twig (formulaire nouveau mdp)**

Remplacer le contenu de `templates/reset_password/reset.html.twig` par :

```twig
{% extends 'base.html.twig' %}

{% block title %}Nouveau mot de passe — La Boîte à Chimère{% endblock %}

{% block body %}
<div class="min-h-screen flex items-center justify-center px-4 py-20">
    <div class="w-full max-w-md">
        <div class="bg-custom-secondary border border-custom rounded-2xl shadow-2xl px-8 py-10">
            <h1 class="text-lg font-extrabold uppercase tracking-[0.15em] text-text-primary mb-2 text-center">Nouveau mot de passe</h1>
            <p class="text-xs text-text-secondary text-center mb-8">Choisissez un nouveau mot de passe pour votre compte.</p>

            {{ form_start(resetForm, { attr: { class: 'flex flex-col gap-4', 'data-turbo': 'false' } }) }}
                <div>
                    {{ form_label(resetForm.plainPassword.first, 'Nouveau mot de passe', { label_attr: { class: 'block text-[10px] font-bold uppercase tracking-wider text-text-secondary mb-1.5' } }) }}
                    {{ form_widget(resetForm.plainPassword.first, { attr: { class: 'w-full rounded-lg border border-custom bg-custom-tertiary px-4 py-2.5 text-sm text-text-primary placeholder-text-secondary/60 focus:border-custom-orange focus:outline-none focus:ring-1 focus:ring-custom-orange', placeholder: '········' } }) }}
                </div>
                <div>
                    {{ form_label(resetForm.plainPassword.second, 'Confirmer le mot de passe', { label_attr: { class: 'block text-[10px] font-bold uppercase tracking-wider text-text-secondary mb-1.5' } }) }}
                    {{ form_widget(resetForm.plainPassword.second, { attr: { class: 'w-full rounded-lg border border-custom bg-custom-tertiary px-4 py-2.5 text-sm text-text-primary placeholder-text-secondary/60 focus:border-custom-orange focus:outline-none focus:ring-1 focus:ring-custom-orange', placeholder: '········' } }) }}
                    {{ form_errors(resetForm.plainPassword) }}
                </div>
                <button type="submit" class="w-full rounded-lg bg-custom-orange px-6 py-3 text-[11px] font-extrabold uppercase tracking-[0.05em] text-text-primary hover:bg-orange-600 hover:scale-105 transition-all shadow-lg shadow-custom-orange/20">
                    Réinitialiser le mot de passe
                </button>
            {{ form_end(resetForm) }}
        </div>
    </div>
</div>
{% endblock %}
```

- [ ] **Step 4: Adapter email.html.twig**

Remplacer le contenu de `templates/reset_password/email.html.twig` par :

```twig
Bonjour,

Vous avez demandé une réinitialisation de mot de passe pour votre compte La Boîte à Chimère.

Cliquez sur le lien ci-dessous pour définir un nouveau mot de passe (valable 1 heure) :

{{ url('app_reset_password', {token: resetToken.token}) }}

Si vous n'avez pas fait cette demande, ignorez simplement cet email.

— La Boîte à Chimère
```

- [ ] **Step 5: Construire Tailwind pour intégrer les nouveaux styles**

```bash
php bin/console tailwind:build
```

- [ ] **Step 6: Commit**

```bash
git add templates/reset_password/
git commit -m "feat: style reset password templates to match site design"
```

---

## Task 3 : Connecter les liens "Mot de passe oublié ?"

**Files:**
- Modify: `templates/home/index.html.twig` (deux occurrences : desktop l.131 et mobile l.172)

- [ ] **Step 1: Mettre à jour le lien desktop (join-panel)**

Dans `templates/home/index.html.twig`, remplacer :
```twig
<a href="#" class="text-xs text-text-secondary hover:text-custom-orange transition-colors text-center">Mot de passe oublié ?</a>
```
par :
```twig
<a href="{{ path('app_forgot_password_request') }}" class="text-xs text-text-secondary hover:text-custom-orange transition-colors text-center">Mot de passe oublié ?</a>
```

- [ ] **Step 2: Mettre à jour le lien mobile (mobile-join-form)**

Dans `templates/home/index.html.twig`, remplacer :
```twig
<a href="#" class="block text-center text-xs text-text-secondary hover:text-custom-orange transition-colors mt-1">Mot de passe oublié ?</a>
```
par :
```twig
<a href="{{ path('app_forgot_password_request') }}" class="block text-center text-xs text-text-secondary hover:text-custom-orange transition-colors mt-1">Mot de passe oublié ?</a>
```

- [ ] **Step 3: Vider le cache**

```bash
php bin/console cache:clear
```

- [ ] **Step 4: Commit**

```bash
git add templates/home/index.html.twig
git commit -m "feat: wire forgot password links to reset password route"
```

---

## Task 4 : Vérifier le flux complet

- [ ] **Step 1: Vérifier que Mailpit/Mailhog est actif**

Ouvrir `http://localhost:8025` (interface Mailpit) pour intercepter les emails.

- [ ] **Step 2: Tester la page de demande**

Naviguer vers `http://devboitechimere.test/forgot-password`. La page doit afficher le formulaire email stylisé.

- [ ] **Step 3: Soumettre un email valide**

Entrer l'email d'un utilisateur existant en base → vérifier la redirection vers `check_email` + l'email reçu dans Mailpit.

- [ ] **Step 4: Cliquer le lien dans l'email**

Le lien doit mener vers `/reset-password/{token}` et afficher le formulaire de nouveau mot de passe.

- [ ] **Step 5: Soumettre le nouveau mot de passe**

Remplir les deux champs → vérifier la redirection vers l'accueil avec le panel login disponible.

- [ ] **Step 6: Vérifier qu'un token déjà utilisé est invalidé**

Recliquer le même lien email → doit afficher une erreur "lien invalide ou expiré".

- [ ] **Step 7: Soumettre un email inconnu**

Le comportement doit être identique à un email valide (pas d'information sur l'existence du compte — anti-enumération).
