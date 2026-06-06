<div align="center">

# 🎓 StageFlow

**Plateforme web de recherche et gestion de stages**  
CESI Nancy — CPI 2ème année | 2025–2026

[![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?logo=php)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql)](https://mysql.com)
[![Docker](https://img.shields.io/badge/Docker-Compose-2496ED?logo=docker)](https://docker.com)
[![Twig](https://img.shields.io/badge/Template-Twig-green)](https://twig.symfony.com)
[![License](https://img.shields.io/badge/Licence-Académique-lightgrey)](#licence)

</div>

---

## 📋 Présentation

StageFlow centralise les offres de stages, les entreprises partenaires et les candidatures étudiantes dans une interface unique, adaptée à chaque profil : **administrateur**, **pilote de promotion**, **étudiant** ou **visiteur anonyme**.

Le projet est développé sans framework backend ni frontend, conformément aux contraintes académiques, en suivant une architecture **MVC stricte**.

---

## 👥 Équipe

| Nom | Rôle |
|---|---|
| Clément BERGER | Développeur |
| Arthur CHANTRAINE | Développeur |
| Vladimir MAJCHER | Développeur |
| Turker CALISKAN | Développeur |

---

## ⚙️ Installation de l'environnement

> Cette section couvre l'installation complète depuis un système vierge.  
> Si Git, Docker et Composer sont déjà installés, passez directement au [Démarrage rapide](#-démarrage-rapide).

---

### 🐧 Linux Mint / Ubuntu

<details>
<summary><b>Cliquez pour afficher les instructions Ubuntu / Mint</b></summary>

#### 1. Mettre le système à jour

```bash
sudo apt update && sudo apt upgrade -y
```

#### 2. Installer Git et les dépendances

```bash
sudo apt install -y git ca-certificates curl gnupg lsb-release software-properties-common unzip php-cli
```

#### 3. Installer Docker + Docker Compose

**Ajouter la clé et le dépôt Docker :**

```bash
sudo install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg
sudo chmod a+r /etc/apt/keyrings/docker.gpg
echo   "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu   $(. /etc/os-release && echo "$VERSION_CODENAME") stable" |   sudo tee /etc/apt/sources.list.d/docker.list > /dev/null
sudo apt update
sudo apt install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
```

#### 4. Installer les extensions PHP requises

```bash
sudo apt install -y php-mysql php-xml
php -m | grep -i dom
```

> L'extension `dom` doit apparaître dans la liste — elle est requise par PHPUnit.

#### 5. Installer Composer

```bash
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
sudo mv composer.phar /usr/local/bin/composer
php -r "unlink('composer-setup.php');"
composer --version
```

</details>

---

### 🐧 CachyOS / Arch Linux

<details>
<summary><b>Cliquez pour afficher les instructions CachyOS / Arch</b></summary>

#### 1. Mettre le système à jour

```bash
sudo pacman -Syu
```

#### 2. Installer Git, PHP et Composer

```bash
sudo pacman -S --needed git curl unzip php composer
```

#### 3. Installer Docker + Docker Compose

Docker Compose v2 est inclus dans le paquet `docker` sur Arch :

```bash
sudo pacman -S --needed docker
```

**Activer et démarrer le service Docker :**

```bash
sudo systemctl enable --now docker
```

**Ajouter votre utilisateur au groupe `docker`** (évite `sudo` à chaque commande) :

```bash
sudo usermod -aG docker $USER
```

> ⚠️ Vous devez vous **déconnecter/reconnecter** (ou relancer votre session) pour que le groupe soit pris en compte. Vérifiez avec `groups | grep docker`.

#### 4. Activer les extensions PHP requises

Sur Arch/CachyOS, activez les modules dynamiques dans `/etc/php/php.ini` :

```bash
sudo nano /etc/php/php.ini
```

Décommentez uniquement ces lignes (retirez le `;`) :

```ini
extension=pdo_mysql
extension=mysqli
```

> ⚠️ Ne pas décommenter `extension=dom` ni `extension=openssl` — ils sont compilés statiquement dans le binaire PHP d'Arch. Les décommenter génère des warnings car le `.so` n'existe pas.

Vérifiez que tout est chargé sans warning :

```bash
php -m | grep -iE 'dom|openssl|pdo_mysql'
```

#### 5. Vérifier Composer

Composer est installé via pacman à l'étape 2 :

```bash
composer --version
```

</details>

---

## 🚀 Démarrage rapide

### Prérequis

- [Git](https://git-scm.com/)
- [Docker](https://www.docker.com/) + Docker Compose v2
- [Composer](https://getcomposer.org/) (dépendances PHP locales)

> **⚠️ Ports requis :** `80` (HTTP) et `443` (HTTPS)  
> Si un serveur web tourne déjà sur votre machine, libérez les ports avant de lancer :
> ```bash
> # Ubuntu / Mint
> sudo systemctl stop apache2 && sudo systemctl disable apache2
>
> # CachyOS / Arch
> sudo systemctl stop httpd && sudo systemctl disable httpd
> ```

### Installation

#### 1. Cloner le dépôt

```bash
git clone git@github.com:Vladimir-Maj/Site-web-RDS.git
cd Site-web-RDS
```

#### 2. Installer les dépendances PHP

> Obligatoire — sans cette étape, PHPUnit et certaines dépendances ne seront pas disponibles.

```bash
cd www/prod
composer install
cd ../..
```

#### 3. Configurer les noms de domaine locaux

```bash
echo "127.0.0.1 prod.stageflow.fr" | sudo tee -a /etc/hosts
echo "127.0.0.1 cdn.stageflow.fr"  | sudo tee -a /etc/hosts
```

#### 4. Construire et démarrer les conteneurs

```bash
docker compose up -d --build
```

Docker démarre automatiquement Apache, PHP et MySQL avec le schéma de base.  
**Les données de démonstration ne sont pas chargées automatiquement** : il faut importer `seed.sql` après le démarrage.

#### 5. Vérifier l'état des services

```bash
docker compose ps
docker compose logs --tail=50 web
docker compose logs --tail=50 db
```

### Accès

| Environnement | URL |
|---|---|
| Application (Vhost PROD) | [https://prod.stageflow.fr](https://prod.stageflow.fr) |
| Assets et médias (Vhost CDN) | [https://cdn.stageflow.fr](https://cdn.stageflow.fr) |

> La conf Apache redirige HTTP vers HTTPS. Acceptez le certificat auto-signé dans votre navigateur.

## 🗄️ Base de données

### Connexion

Configurée dans `www/prod/.back/util/db_connect.php` :

| Paramètre | Valeur |
|---|---|
| Hôte | `db` |
| Port | `3306` |
| Base | `sql_db` |
| Utilisateur | `website-local` |
| Mot de passe | `1234` |

> Le schéma est chargé automatiquement au démarrage depuis `mysql/init/01-create-tables.sql`.  
> ⚠️ Utiliser un ancien schéma cassera l'application (colonnes attendues : `id_user`, `email_user`, `id_company`, `id_internship_offer`, etc.)

### Charger le jeu de données

Une fois les conteneurs démarrés :

```bash
docker exec -i lamp-db mysql --default-character-set=utf8mb4 -u website-local -p1234 sql_db < seed.sql
```

> Si MySQL n'est pas encore prêt, attendez quelques secondes et relancez.

### Vérifier l'import

```bash
docker exec -it lamp-db mysql -u website-local -p1234 -e "USE sql_db; SELECT COUNT(*) AS users_count FROM user; SELECT COUNT(*) AS offers_count FROM internship_offer; SELECT COUNT(*) AS companies_count FROM company; SELECT COUNT(*) AS wishlist_count FROM wishlist;"
```

### Réinitialiser complètement

```bash
docker compose down -v --remove-orphans
docker compose up -d --build
docker exec -i lamp-db mysql --default-character-set=utf8mb4 -u website-local -p1234 sql_db < seed.sql
```

> Après une réinitialisation complète, il faut **réimporter `seed.sql`** pour restaurer les comptes et les données de démonstration.

## 👤 Comptes de test

> Ces comptes fonctionnent **uniquement si `seed.sql` a été importé**. Le schéma seul ne crée aucun utilisateur.

| Email | Mot de passe | Rôle |
|---|---|---|
| [admin@example.com](mailto:admin@example.com) | `Admin1234!` | Administrateur |
| [pilot@example.com](mailto:pilot@example.com) | `Pilote1234!` | Pilote |
| [pilot2@example.com](mailto:pilot2@example.com) | `Pilote1234!` | Pilote |
| [student1@example.com](mailto:student1@example.com) | `Etudiant1234!` | Étudiant |
| [student2@example.com](mailto:student2@example.com) | `EtudiantBis1234!` | Étudiant |
| [test@example.com](mailto:test@example.com) | `Test1234!` | Étudiant |

## 🔐 Matrice des rôles

| Action | Anonyme | Étudiant | Pilote | Admin |
|---|:---:|:---:|:---:|:---:|
| Voir les offres | ✅ | ✅ | ✅ | ✅ |
| Voir les entreprises | ✅ | ✅ | ✅ | ✅ |
| Se connecter / déconnecter | ✅ | ✅ | ✅ | ✅ |
| Postuler à une offre | ❌ | ✅ | ❌ | ❌ |
| Gérer sa wish-list | ❌ | ✅ | ❌ | ❌ |
| Gérer les offres | ❌ | ❌ | ✅ | ✅ |
| Gérer les entreprises | ❌ | ❌ | ✅ | ✅ |
| Gérer les comptes | ❌ | ❌ | ✅ | ✅ |

## ✅ État d'avancement

### Réalisé

- [x] Authentification / déconnexion
- [x] Catalogue des offres avec pagination
- [x] Détail d'une offre
- [x] Page d'accueil avec message de bienvenue
- [x] Suivi des candidatures (étudiant)
- [x] Wish-list étudiant (ajout / retrait)
- [x] Flash messages utilisateurs
- [x] Routage backend propre
- [x] Intégration Twig sur les vues principales
- [X] Gestion complète des entreprises
- [X] Mentions Légales

### En cours / à finaliser

- [ ] Gestion complète des pilotes
- [ ] Gestion complète des étudiants
- [ ] Vue complète des candidatures par étudiant
- [ ] Vue candidatures élèves pour le pilote
- [ ] Statistiques avancées
- [ ] Tests unitaires complets
- [ ] `robots.txt` et `sitemap.xml`

## 🏗️ Architecture

```text
Site-web-RDS/
├── apache/
│   ├── entrypoint.sh
│   └── vhosts.conf
├── mysql/
│   ├── init/
│   ├── migrations/
│   └── scripts/
├── scripts/
│   ├── chmod_all_scripts.sh
│   ├── clean-tree.sh
│   ├── db-logs.sh
│   ├── server-logs.sh
│   ├── units.sh
│   └── wipe_volumes_clean.sh
├── www/
│   ├── cdn/
│   │   ├── assets/
│   │   └── styles.css
│   └── prod/
│       ├── .back/
│       ├── tests/
│       ├── .htaccess
│       ├── composer.json
│       ├── composer.lock
│       └── index.php
├── .gitignore
├── CLAUDE.md
├── Dockerfile
├── docker-compose.yaml
├── README.md
└── seed.sql
```

Le projet suit une **architecture MVC stricte**, sans framework backend ni CMS.

## 🧩 Couche de données — Repositories

| Repository | Responsabilités |
|---|---|
| `OfferRepository` | Recherche multicritères, pagination, détail d'une offre |
| `CompanyRepository` | Liste, filtrage et détails des entreprises |
| `UserRepository` | Authentification, gestion des profils |
| `ApplicationRepository` | Candidatures et suivi étudiant |
| `PromotionRepository` | Promotions et affectations |
| `SkillRepository` | Compétences requises par les offres |
| `WishlistRepository` | Gestion de la wish-list étudiante |

### Logique de recherche dynamique

Chaque filtre est optionnel. Les valeurs sont injectées via des **requêtes préparées PDO**.

## 🛠️ Stack technique

| Couche | Technologie |
|---|---|
| Serveur | Apache 2.4 |
| Frontend | HTML5 / CSS3 / JavaScript vanilla |
| Backend | PHP 8.2 orienté objet |
| Templates | Twig |
| Base de données | MySQL 8 |
| Tests | PHPUnit |
| Conteneurisation | Docker + Docker Compose |
| Versionning | Git |

> Aucun framework frontend ou backend n'est utilisé, conformément aux contraintes du projet.

## 🧪 Tests

```bash
# Via le script du projet
bash scripts/units.sh

# Ou directement avec le service dédié
docker compose run --rm phpunit
```

## 🔀 Workflow Git

Le projet suit une organisation proche de **Git Flow** :

| Branche | Usage |
|---|---|
| `main` | Version stable |
| `develop` | Branche d'intégration |
| `feature/[nom]` | Développement d'une fonctionnalité |
| `fix/[nom]` | Correction de bug |

Chaque fonctionnalité passe idéalement par une **Pull Request** avant intégration dans `develop`.

## 🔒 Sécurité

- Mots de passe hashés avec `password_hash()` et vérifiés avec `password_verify()`.
- Sessions serveur avec cookies `HttpOnly`, `Secure` et `SameSite=Strict`.
- Requêtes préparées PDO (protection contre les injections SQL).
- Échappement automatique Twig (protection contre les XSS).
- Tokens CSRF sur les formulaires sensibles.
- HTTPS activé via certificat auto-signé.

## 🧰 Scripts utilitaires

```bash
bash scripts/chmod_all_scripts.sh   # Donne les droits d'exécution à tous les scripts
bash scripts/clean-tree.sh          # Nettoie l'arborescence
bash scripts/db-logs.sh             # Affiche les logs MySQL
bash scripts/server-logs.sh         # Affiche les logs Apache
bash scripts/units.sh               # Lance les tests unitaires
bash scripts/wipe_volumes_clean.sh  # Réinitialise les volumes Docker (BDD comprise)
```

## 🐳 Services Docker

| Service | Conteneur | Rôle |
|---|---|---|
| `web` | `lamp-web` | Apache + PHP, sert à la fois `prod.stageflow.fr` et `cdn.stageflow.fr` |
| `db` | `lamp-db` | Base de données MySQL |
| `phpunit` | `lamp-tests` | Exécution des tests automatisés |

#### Note légale

> Le contenu juridique source est préparé dans `LEGAL_CONTENT.md`, mais il doit être adapté au contexte réel du projet StageFlow avant toute publication hors cadre pédagogique.

## 📄 Licence

Projet académique — **CESI Nancy**  
Non destiné à un usage en production.
