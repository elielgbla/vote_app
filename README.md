# Vote annuel : plateforme de vote en ligne

Application web de vote pour désigner chaque année le **meilleur travailleur** et le **meilleur rendement** d'une entreprise. Le vote est ouvert aux **employés** et aux **clients**. L'entreprise ne disposant d'aucune liste d'électeurs, l'accès repose sur des **codes de vote à usage unique**, distribués par les RH (employés) et par la caisse (clients).

La plateforme est conçue pour être **réutilisée chaque année** : un seul scrutin est actif à la fois, les anciens scrutins sont conservés en archive.

## Fonctionnalités

**Espace électeur**
- Accès par **CNI + code de vote**
- Bulletin avec une catégorie par choix, photo et service du candidat
- Confirmation de vote sans rappel du choix
- Limitation des tentatives de saisie du code

**Back-office (administrateurs)**
- Gestion des scrutins : création, cycle de statuts, duplication d'une année sur l'autre
- Gestion des catégories, candidatures, individus (employés et clients), sites de distribution
- Génération de codes par lots, distribution, annulation, export PDF (planches de codes) et CSV
- Résultats en temps réel, graphiques, taux de participation, export Excel et PDF
- Journal d'audit des actions
- Comptes administrateurs avec rôles

**Automatisation**
- Ouverture et clôture automatiques du scrutin selon les dates (`php spark scrutin:verifier`)
- Résultats écrits automatiquement en base, classement figé à la clôture

## Technologies

| Élément | Choix |
|---|---|
| Framework | CodeIgniter 4.7 (PHP, architecture MVC) |
| Base de données | MariaDB 10.6+ / MySQL 8.0.16+ (InnoDB, utf8mb4) |
| Interface | Bootstrap 5, Chart.js (fichiers locaux, sans CDN) |
| PDF | Dompdf |
| Excel | PhpSpreadsheet |
| Dépendances | Composer |

## Prérequis

- PHP 8.1 ou plus récent avec les extensions : `intl`, `mbstring`, `mysqli`, `gd`, `zip`, `xml`, `fileinfo`
- Composer
- MariaDB ou MySQL
- Un serveur local (Laragon ou XAMPP) pour le développement

Vérification des extensions :

```
php -m | findstr /i "gd zip mbstring intl xml fileinfo mysqli"
```

Pour activer une extension (par exemple GD), retire le `;` devant `extension=gd` dans le `php.ini` (`php --ini` indique son emplacement), puis relance le terminal.

## Installation

```
git clone <adresse-du-depot> vote
cd vote
composer install
copy env .env
php spark key:generate
```

### Configuration (`.env`)

```
CI_ENVIRONMENT = development
app.baseURL = 'http://localhost:8080/'

database.default.hostname = localhost
database.default.database = vote_db
database.default.username = root
database.default.password =
database.default.DBDriver = MySQLi
database.default.port = 3306

# Clé secrète servant à hasher les codes de vote
VOTE_CODE_KEY = <64 caractères hexadécimaux>
```

Génère la clé du vote avec :

```
php -r "echo bin2hex(random_bytes(32));"
```

> **Important :** ne change jamais `VOTE_CODE_KEY` après avoir généré des codes. Tous les codes existants deviendraient invalides. Ne versionne jamais le fichier `.env`.

### Base de données

Importe le script (le script contient les tables, la vue, la procédure et les triggers) :

```
mysql -u root -p < vote_db.sql
```

Vérifie que les 7 triggers et la procédure sont présents :

```
mysql -u root -e "SHOW TRIGGERS FROM vote_db"
mysql -u root -e "SHOW PROCEDURE STATUS WHERE Db = 'vote_db'"
```

Les migrations CodeIgniter ne sont pas utilisées : le script SQL est la référence du schéma.

### Compte administrateur initial

```
php spark db:seed AdminSeeder
```

Connexion : `http://localhost:8080/admin/login`, identifiant `superadmin`.
Le mot de passe initial est défini dans `app/Database/Seeds/AdminSeeder.php` : **change-le dès la première connexion**.

### Lancement

```
php spark serve
```

L'application est disponible sur `http://localhost:8080`.

## Règles métier

1. **Un électeur, une identité.** L'électeur saisit son CNI et son code. Le CNI doit exister dans la table `INDIVIDU`.
2. **Codes à usage unique.** Codes aléatoires de 10 caractères, stockés uniquement sous forme de hash (`HMAC-SHA256` avec `VOTE_CODE_KEY`). Le code en clair n'est visible qu'une fois, à la génération.
3. **Vote secret.** Aucun lien en base entre un électeur et son choix :
   - `EMARGEMENT` indique **qui** a voté (sans le choix)
   - `VOTE` indique **ce qui** a été voté (sans le CNI)
   - `CODE_VOTE` n'est lié à aucun individu
   - les dates de vote sont enregistrées sans l'heure
4. **Un seul vote par personne.** Garanti par la clé primaire d'`EMARGEMENT` (scrutin + CNI) et par la consommation atomique du code, dans une seule transaction.
5. **Cycle de vie du scrutin :** `brouillon` → `planifie` → `actif` → `cloture` → `publie` → `archive`. Une seule étape à la fois, imposée par la base. Un seul scrutin actif à la fois, un seul scrutin par année.
6. **Verrouillage.** Catégories et candidatures ne se modifient qu'en `brouillon` ou `planifie`. Un vote enregistré ne peut ni être modifié ni supprimé.
7. **Résultats.** La table `RESULTAT` est mise à jour automatiquement à chaque vote (triggers). À la clôture, le classement est figé. Les exports lisent la vue `V_RESULTATS`. Les résultats publics ne s'affichent qu'au statut `publie`.

## Rôles

| Rôle | Droits |
|---|---|
| `super_admin` | Tout : scrutins, catégories, candidatures, sites, administrateurs, résultats, journal, individus, codes |
| `rh` | Employés (individus) et codes des employés |
| `caissier` | Clients (individus) et codes des clients |

## Structure du projet

```
app/
├── Commands/          VerifierScrutin (ouverture/clôture automatiques)
├── Config/            Routes, Filters, Security, Session, App…
├── Controllers/
│   ├── Accueil.php, Resultats.php
│   ├── Vote/          Acces, Bulletin, Confirmation
│   └── Admin/         Auth, Dashboard, Scrutins, Categories, Individus,
│                      Candidatures, Sites, Codes, Resultats, Journal, Admins
├── Database/Seeds/    AdminSeeder
├── Filters/           ScrutinActifFilter, AdminAuthFilter, RoleFilter, ThrottleFilter
├── Helpers/           vote_helper.php
├── Language/fr/       Messages en français
├── Libraries/         CodeGenerator, VoteService, ScrutinService,
│                      JournalService, PdfService, ExcelService
├── Models/            13 modèles (un par table, plus lecture des résultats)
└── Views/             layouts, partials, accueil, vote, resultats, admin, pdf, errors
public/
├── assets/            css, js, img (Bootstrap et Chart.js en local)
└── uploads/           Photos des candidats
```

## Sécurité

- Protection CSRF sur tous les formulaires, en-têtes de sécurité activés
- Sorties échappées avec `esc()`, validation CodeIgniter sur toutes les entrées
- Mots de passe administrateur avec `password_hash` / `password_verify`
- Limitation des tentatives de connexion et de saisie de code
- Session régénérée à la connexion
- Journal d'audit des actions d'administration et des changements de statut
- Triggers de protection en base : vote refusé hors scrutin actif, votes non modifiables
- Utilisateur MySQL dédié à l'application en production (jamais `root`)
- HTTPS obligatoire en production

> Les noms de tables sont en **majuscules** (`SCRUTIN`, `VOTE`…). Sous Linux, MySQL/MariaDB distingue les majuscules : respecte exactement cette casse dans le code.

## Cycle d'utilisation annuel

1. Créer le scrutin de l'année (ou dupliquer celui de l'année précédente)
2. Définir les catégories et les candidatures
3. Générer les lots de codes, puis les imprimer ou les exporter
4. Distribuer les codes (RH pour les employés, caisse pour les clients) et les marquer « distribués »
5. Passer le scrutin en `planifie`, puis laisser l'ouverture automatique ou l'activer manuellement
6. À la fin du vote, clôture automatique ou manuelle, puis vérification des résultats
7. Publication des résultats, puis archivage

## Tâche planifiée

Ouverture et clôture automatiques, à lancer chaque minute.

Linux (cron) :

```
* * * * * php /var/www/vote/spark scrutin:verifier
```

Windows (planificateur de tâches) : exécuter `php C:\chemin\vote\spark scrutin:verifier` toutes les minutes.

## Déploiement en production

1. Serveur Linux (VPS), Nginx + PHP-FPM + MariaDB, avec la racine web pointant sur le dossier `public/`
2. Sous-domaine dédié, par exemple `vote.entreprise.ci`, enregistrement DNS vers le serveur
3. Certificat HTTPS Let's Encrypt
4. `.env` de production :
   ```
   CI_ENVIRONMENT = production
   app.baseURL = 'https://vote.entreprise.ci/'
   app.forceGlobalSecureRequests = true
   ```
5. Dépendances sans les outils de développement :
   ```
   composer install --no-dev --optimize-autoloader
   ```
6. Import de `vote_db.sql`, création d'un utilisateur MySQL dédié, droit d'écriture sur `writable/`
7. Tâche planifiée `scrutin:verifier`
8. Sauvegarde quotidienne de la base (`mysqldump`) stockée hors du serveur

## Commandes utiles

```
php spark serve                 Lancer le serveur de développement
php spark routes                Lister les routes
php spark db:table --show       Lister les tables de la base
php spark db:seed AdminSeeder   Créer le compte administrateur initial
php spark scrutin:verifier      Ouvrir ou clôturer les scrutins selon les dates
```

## Avancement

- [x] Étape 0 : Environnement et base de données
- [x] Étape 1 : Configuration de base, layouts, helpers
- [x] Étape 2 : Modèles
- [ ] Étape 3 : Services métier (`CodeGenerator`, `VoteService`, `ScrutinService`, `JournalService`, `PdfService`, `ExcelService`)
- [ ] Étape 4 : Filtres
- [ ] Étape 5 : Authentification administrateur
- [ ] Étape 6 : Back-office (scrutins, catégories, individus, candidatures, sites, administrateurs)
- [ ] Étape 7 : Gestion des codes
- [ ] Étape 8 : Espace électeur
- [ ] Étape 9 : Résultats et exports
- [ ] Étape 10 : Journal d'audit
- [ ] Étape 11 : Commande `scrutin:verifier`
- [ ] Étape 12 : Sécurité, finitions et tests
- [ ] Étape 13 : Déploiement

## Auteur

GBLA TONGA ELIEL : projet de vote en ligne, CodeIgniter 4.
