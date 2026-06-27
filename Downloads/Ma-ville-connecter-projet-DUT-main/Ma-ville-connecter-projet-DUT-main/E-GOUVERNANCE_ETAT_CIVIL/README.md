# 🏛️ E-Gouvernance État Civil — Plateforme Municipale

> Application web complète de gestion de l'État Civil municipal.
> Développée en PHP/MySQL, hébergeable localement avec XAMPP.

---

## 📋 Présentation

La plateforme **E-État Civil** est un système de gestion numérique des services d'État Civil municipal permettant :

- Aux **citoyens** de soumettre des demandes d'actes (naissance, décès, mariage) en ligne
- Aux **agents municipaux** de traiter, valider et générer les actes officiels
- Aux **administrateurs** de gérer l'ensemble du système, des utilisateurs et des statistiques

---

## 🛠️ Prérequis

- **XAMPP** v8.x ou supérieur (Apache + MySQL + PHP 8.x)
- **Visual Studio Code** (recommandé)
- **Navigateur moderne** (Chrome, Firefox, Edge)
- Connexion Internet pour les CDN Bootstrap & Font Awesome (ou utiliser les versions locales)

---

## 🚀 Installation rapide (XAMPP)

### Étape 1 — Copier le projet

```bash
Copier le dossier E-GOUVERNANCE_ETAT_CIVIL dans :
C:\xampp\htdocs\
```

### Étape 2 — Démarrer XAMPP

1. Ouvrir le **Panneau de contrôle XAMPP**
2. Démarrer **Apache**
3. Démarrer **MySQL**

### Étape 3 — Importer la base de données

1. Ouvrir **phpMyAdmin** : `http://localhost/phpmyadmin`
2. Cliquer sur **Importer**
3. Sélectionner le fichier : `database/e_gouvernance.sql`
4. Cliquer sur **Exécuter**

### Étape 4 — Configurer les mots de passe

Exécuter dans un terminal (dans le dossier du projet) :
```bash
php database/hash_passwords.php
```

Ou manuellement dans phpMyAdmin, exécuter :
```sql
-- Mettre à jour les mots de passe avec les vrais hashes PHP
-- (voir database/hash_passwords.php pour générer les hashes corrects)
```

### Étape 5 — Configurer la connexion

Éditer le fichier `config/database.php` si nécessaire :
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'e_gouvernance_etat_civil');
define('DB_USER', 'root');
define('DB_PASS', '');  // Vide par défaut sur XAMPP
```

### Étape 6 — Accéder à l'application

Ouvrir dans le navigateur :
```
http://localhost/E-GOUVERNANCE_ETAT_CIVIL/
```

---

## 🔑 Comptes de démonstration

| Rôle          | Email                      | Mot de passe   |
|---------------|----------------------------|----------------|
| Administrateur| admin@mairie.cm            | Admin@2024     |
| Agent 1       | agent@mairie.cm            | Agent@2024     |
| Agent 2       | agent2@mairie.cm           | Agent@2024     |
| Citoyen 1     | citoyen@example.cm         | Citoyen@2024   |
| Citoyen 2     | amina.biyong@gmail.com     | Citoyen@2024   |

---

## 📁 Structure du projet

```
E-GOUVERNANCE_ETAT_CIVIL/
│
├── index.php                    ← Page d'accueil publique
├── .htaccess                    ← Config Apache + sécurité
│
├── config/
│   ├── database.php             ← Connexion PDO (Singleton)
│   └── app.php                  ← Config générale + helpers
│
├── controllers/
│   └── AuthController.php       ← Authentification (login/register/logout)
│
├── models/                      ← (Extension future MVC)
│
├── views/
│   ├── partials/
│   │   ├── header.php           ← <head> HTML commun
│   │   ├── navbar.php           ← Navigation principale
│   │   └── footer.php           ← Pied de page + scripts JS
│   ├── citoyen/
│   │   └── sidebar.php          ← Sidebar espace citoyen
│   ├── agent/
│   └── admin/
│
├── assets/
│   ├── css/style.css            ← Design system complet
│   ├── js/app.js                ← JavaScript interactif
│   └── images/favicon.svg       ← Icône de l'application
│
├── auth/
│   ├── login.php                ← Page connexion/inscription
│   ├── register.php             ← Redirect → login (onglet inscription)
│   └── logout.php               ← Déconnexion sécurisée
│
├── citoyen/
│   ├── dashboard.php            ← Tableau de bord citoyen
│   ├── demande-naissance.php    ← Formulaire acte de naissance
│   ├── demande-deces.php        ← Formulaire acte de décès
│   ├── demande-mariage.php      ← Formulaire acte de mariage
│   ├── mes-demandes.php         ← Liste de ses demandes
│   ├── detail-demande.php       ← Détail + suivi timeline
│   └── telecharger-acte.php     ← Téléchargement PDF
│
├── agent/
│   ├── dashboard.php            ← Tableau de bord agent
│   ├── demandes.php             ← Liste toutes demandes (filtres)
│   └── traiter-demande.php      ← Validation/Rejet/Génération acte
│
├── admin/
│   ├── dashboard.php            ← Administration générale
│   ├── utilisateurs.php         ← CRUD utilisateurs + agents
│   ├── demandes.php             ← Vue admin + export CSV
│   ├── parametres.php           ← Paramètres système
│   └── journal.php              ← Journal d'activité
│
├── api/
│   └── notifications.php        ← API AJAX notifications
│
├── uploads/
│   ├── .htaccess                ← Sécurité (pas d'exec PHP)
│   ├── naissances/              ← Pièces jointes naissances
│   ├── deces/                   ← Pièces jointes décès
│   ├── mariages/                ← Pièces jointes mariages
│   └── documents/actes/         ← Actes générés (HTML/PDF)
│
└── database/
    ├── e_gouvernance.sql        ← Schéma + données de démo
    └── hash_passwords.php       ← Générateur de hashes bcrypt
```

---

## 🔐 Sécurité implémentée

| Mesure                    | Détail                                          |
|---------------------------|-------------------------------------------------|
| Hachage mots de passe     | `password_hash()` Bcrypt cost=12                |
| Requêtes préparées        | PDO avec paramètres liés, anti-injection SQL    |
| Protection CSRF           | Tokens aléatoires par session (15 min)          |
| Sessions sécurisées       | `httponly`, `samesite=Lax`, timeout 1h          |
| Contrôle des rôles        | `requireRole()` sur chaque page protégée        |
| Validation uploads        | Extension + MIME type + taille max (5 Mo)       |
| Pas d'exec PHP / uploads  | `.htaccess` dans dossier uploads                |
| En-têtes sécurisés        | X-Content-Type-Options, X-Frame-Options, XSS    |
| Journal d'audit           | Toutes les actions sensibles tracées            |

---

## 🎨 Technologies utilisées

| Couche         | Technologie                          |
|----------------|--------------------------------------|
| Frontend       | HTML5, CSS3, Bootstrap 5.3, Font Awesome 6 |
| Backend        | PHP 8.x POO                          |
| Base de données| MySQL 8 (compatible XAMPP)           |
| Architecture   | MVC simplifié + Singleton PDO        |
| Sécurité       | PDO préparé, CSRF, Bcrypt, Sessions  |
| Serveur local  | Apache via XAMPP                     |

---

## 📊 Base de données — Tables principales

| Table                 | Description                              |
|-----------------------|------------------------------------------|
| `roles`               | Rôles : admin, agent, citoyen            |
| `users`               | Comptes utilisateurs (tous rôles)        |
| `citoyens`            | Profil étendu des citoyens               |
| `agents`              | Profil des agents (matricule, poste)     |
| `demandes`            | Toutes les demandes d'actes              |
| `naissances`          | Détails actes de naissance               |
| `deces`               | Détails actes de décès                   |
| `mariages`            | Détails actes de mariage                 |
| `documents`           | Pièces jointes uploadées                 |
| `notifications`       | Notifications utilisateurs               |
| `historique_actions`  | Journal d'audit complet                  |
| `parametres_systeme`  | Configuration dynamique du système       |

---

## 🔧 Extensions VS Code recommandées

```json
{
    "recommendations": [
        "bmewburn.vscode-intelephense-client",
        "formulahendry.auto-close-tag",
        "esbenp.prettier-vscode",
        "streetsidesoftware.code-spell-checker-french",
        "bradlc.vscode-tailwindcss",
        "mechatroner.rainbow-csv",
        "ms-azuretools.vscode-docker"
    ]
}
```

---

## 🔄 Workflow d'une demande

```
Citoyen soumet → [soumis]
      ↓
Agent prend en charge → [en_cours]
      ↓
    ┌─────────────────┐
    │                 │
   Valide           Rejette
  [valide]         [rejete]
    ↓
Acte PDF généré
    ↓
Citoyen notifié + téléchargement disponible
```

---

## 📞 Support

- **Documentation** : Ce fichier README.md
- **Base de données** : `database/e_gouvernance.sql`
- **Mots de passe** : `database/hash_passwords.php`
- **Configuration** : `config/database.php` et `config/app.php`

---

## 📄 Licence

Projet académique / municipal. Développé pour la Mairie de Douala, Cameroun.
Tous droits réservés © 2024.

---

*Plateforme E-Gouvernance État Civil v1.0.0 — PHP/MySQL/Bootstrap 5*
