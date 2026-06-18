# Documentation — Plugin Behaviors pour GLPI

**Licence :** GNU AGPL v3+  
**Auteurs :** Infotel, Remi Collet, Nelly Mahu-Lasson  
**Dépôt :** https://github.com/InfotelGLPI/behaviors

---

## Table des matières

1. [Présentation](#présentation)
2. [Installation](#installation)
3. [Configuration](#configuration)
   - [Groupes automatiques](#groupes-automatiques)
   - [Champs obligatoires à la résolution](#champs-obligatoires-à-la-résolution)
   - [Champs obligatoires lors de laffectation](#champs-obligatoires-lors-de-laffectation)
   - [Tâches obligatoires terminées](#tâches-obligatoires-terminées)
   - [Format des numéros ITIL](#format-des-numéros-itil)
   - [Technicien résolvant et suivi](#technicien-résolvant-et-suivi)
   - [Notifications supplémentaires](#notifications-supplémentaires)
   - [Clonage](#clonage)
4. [Fonctionnalités détaillées](#fonctionnalités-détaillées)
   - [Ajout automatique de groupe demandeur](#ajout-automatique-de-groupe-demandeur)
   - [Ajout automatique de groupe technicien](#ajout-automatique-de-groupe-technicien)
   - [Champs obligatoires avant résolution](#champs-obligatoires-avant-résolution)
   - [Catégorie obligatoire à laffectation](#catégorie-obligatoire-à-laffectation)
   - [Tâches à faire bloquantes](#tâches-à-faire-bloquantes)
   - [Format des numéros de ticket et de changement](#format-des-numéros-de-ticket-et-de-changement)
   - [Affectation automatique du technicien résolvant](#affectation-automatique-du-technicien-résolvant)
   - [Mode technicien unique](#mode-technicien-unique)
   - [Notifications supplémentaires](#notifications-supplémentaires-1)
   - [Nouveaux destinataires de notification](#nouveaux-destinataires-de-notification)
   - [Clonage dobjets GLPI](#clonage-dobjets-glpi)
5. [Droits requis](#droits-requis)
6. [Désinstallation](#désinstallation)

---

## Présentation

Le plugin **Behaviors** ajoute des comportements optionnels à GLPI pour améliorer la gestion des tickets, problèmes et changements. Il permet de :

- Rendre certains champs **obligatoires** avant de résoudre ou clôturer un ticket
- Ajouter **automatiquement** le groupe du demandeur ou du technicien lors de la création/modification d'un ticket
- Définir un **format personnalisé** pour les numéros de ticket et de changement
- Envoyer des **notifications supplémentaires** (réouverture, changement de statut, mise en attente, document ajouté/supprimé)
- **Cloner** des profils, modèles de notification, règles et tickets
- Affecter automatiquement le **technicien résolvant** lors de la clôture

---

## Installation

1. Télécharger le plugin depuis [GitHub](https://github.com/InfotelGLPI/behaviors) ou la marketplace GLPI.
2. Décompresser l'archive dans le dossier `plugins/` (ou `marketplace/`) de votre installation GLPI.
3. Se connecter à GLPI en tant qu'administrateur.
4. Aller dans **Configuration › Plugins**, cliquer sur **Installer** puis **Activer** pour *Behaviors*.

---

## Configuration

Accès : **Configuration › Configuration générale › onglet Behaviours**  
(Droit requis : `config`)

### Groupes automatiques

| Option | Champ | Description |
|--------|-------|-------------|
| **Groupe du demandeur (objet)** | `use_requester_item_group` | Ajoute automatiquement le groupe de l'objet associé au ticket comme groupe demandeur |
| **Groupe du demandeur (utilisateur)** | `use_requester_user_group` | Ajoute le groupe GLPI du demandeur comme groupe demandeur du ticket. Valeurs : `0` = désactivé, `1` = premier groupe, `2` = tous les groupes |
| **Groupe du technicien (création)** | `use_assign_user_group` | Ajoute le groupe GLPI du technicien affecté comme groupe technique à la création. Valeurs : `0` = désactivé, `1` = premier groupe, `2` = tous les groupes |
| **Groupe du technicien (modification)** | `use_assign_user_group_update` | Même comportement que ci-dessus lors d'une mise à jour du ticket |

### Champs obligatoires à la résolution

Ces champs doivent être renseignés avant de passer un ticket/problème en statut **Résolu** ou **Clôturé** :

| Option | Champ | Description |
|--------|-------|-------------|
| **Type de solution** | `is_ticketsolutiontype_mandatory` | Le type de solution doit être sélectionné |
| **Description de la solution** | `is_ticketsolution_mandatory` | La description de la solution doit être saisie |
| **Catégorie** | `is_ticketcategory_mandatory` | Une catégorie doit être choisie |
| **Technicien affecté** | `is_tickettech_mandatory` | Un technicien doit être affecté |
| **Groupe technicien** | `is_tickettechgroup_mandatory` | Un groupe technicien doit être affecté |
| **Durée** | `is_ticketrealtime_mandatory` | La durée réelle doit être saisie |
| **Emplacement** | `is_ticketlocation_mandatory` | Un emplacement doit être sélectionné |
| **Type de solution (problème)** | `is_problemsolutiontype_mandatory` | Pour les problèmes : type de solution obligatoire |

> Des avertissements visuels sont affichés dans le formulaire dès que ces conditions ne sont pas remplies.

### Champs obligatoires lors de l'affectation

| Option | Champ | Description |
|--------|-------|-------------|
| **Catégorie à l'affectation** | `is_ticketcategory_mandatory_on_assign` | Une catégorie doit être choisie lors de l'affectation d'un technicien ou groupe |
| **Catégorie de tâche** | `is_tickettaskcategory_mandatory` | Une catégorie est obligatoire pour toute tâche de ticket |

### Tâches obligatoires terminées

| Option | Champ | Description |
|--------|-------|-------------|
| **Tâches à faire (ticket)** | `is_tickettasktodo` | Bloque la résolution si des tâches sont encore en statut « À faire » |
| **Tâches à faire (problème)** | `is_problemtasktodo` | Même comportement pour les problèmes |
| **Tâches à faire (changement)** | `is_changetasktodo` | Même comportement pour les changements |

### Format des numéros ITIL

| Option | Champ | Description |
|--------|-------|-------------|
| **Format ticket** | `tickets_id_format` | Préfixe le numéro de ticket par une date. Exemples : `Y000001` (2025000001), `Ym0001` (2025010001), `Ymd01` (202501150001), `ymd0001` (250115 0001) |
| **Format changement** | `changes_id_format` | Même principe pour les changements |

> Le format modifie l'`AUTO_INCREMENT` de la table `glpi_tickets` de sorte que le prochain ticket créé prend le numéro correspondant à la date courante. Si la valeur calculée est inférieure à l'ID actuel, aucune modification n'est faite.

### Technicien résolvant et suivi

| Option | Champ | Description |
|--------|-------|-------------|
| **Affecter le résolvant** | `ticketsolved_updatetech` | Lors de la résolution d'un ticket, affecte automatiquement l'utilisateur connecté comme technicien si aucun n'est affecté |
| **Affecter lors d'un suivi** | `addfup_updatetech` | Lors de l'ajout d'un suivi, affecte l'utilisateur connecté comme technicien si celui-ci appartient à un groupe déjà affecté au ticket |
| **Date de création verrouillée** | `is_ticketdate_locked` | Empêche la modification de la date de création d'un ticket |

### Mode technicien unique

| Option | Champ | Description |
|--------|-------|-------------|
| **Mode technicien unique** | `single_tech_mode` | Contrôle le nombre de techniciens/groupes autorisés par ticket (valeurs définies dans la table de configuration) |

### Notifications supplémentaires

| Option | Champ | Description |
|--------|-------|-------------|
| **Activer les notifications supplémentaires** | `add_notif` | Active les 5 nouveaux événements de notification décrits ci-dessous |

### Clonage

| Option | Champ | Description |
|--------|-------|-------------|
| **Clonage** | `clone` | Active l'onglet « Cloner » sur les objets cibles. Valeurs : `0` = désactivé, `1` = cloner dans l'entité active, `2` = cloner dans l'entité d'origine |

---

## Fonctionnalités détaillées

### Ajout automatique de groupe demandeur

Lorsque `use_requester_user_group` est activé, le ou les groupes GLPI du demandeur sont automatiquement ajoutés comme groupe demandeur à la création ou la modification du ticket.

- **Mode 1 (premier groupe)** : seul le premier groupe trouvé est ajouté.
- **Mode 2 (tous les groupes)** : tous les groupes de l'utilisateur sont ajoutés.

La logique s'applique également pour les tickets créés par e-mail (collecteur de mails).

---

### Ajout automatique de groupe technicien

Lorsque `use_assign_user_group` (création) ou `use_assign_user_group_update` (modification) est activé, le groupe GLPI du technicien affecté est automatiquement ajouté comme groupe technicien.

- **Mode 1 (premier groupe)** : seul le premier groupe trouvé est ajouté.
- **Mode 2 (tous les groupes)** : tous les groupes du technicien sont ajoutés.

---

### Champs obligatoires avant résolution

Quand un technicien tente de passer un ticket en **Résolu** ou **Clôturé**, le plugin vérifie les champs configurés comme obligatoires. Si l'un d'eux manque :

- Un **message d'erreur** est affiché.
- Le changement de statut est **annulé**.
- Des **avertissements visuels** sont affichés dans le formulaire dès l'ouverture (avant même la soumission).

Les mêmes vérifications s'appliquent à la **solution** elle-même (type et description).

---

### Catégorie obligatoire à l'affectation

Si `is_ticketcategory_mandatory_on_assign` est activé, toute tentative d'affectation d'un technicien ou groupe sans catégorie est bloquée avec un message d'erreur.

---

### Tâches à faire bloquantes

Si `is_tickettasktodo` (ou les variantes problème/changement) est activé, la résolution est bloquée tant qu'une tâche est encore en statut **À faire** (`state = 1`). De plus, le statut des tâches ne peut plus être modifié sur un ticket déjà résolu.

---

### Format des numéros de ticket et de changement

Le format de numérotation permet d'intégrer la date dans l'identifiant du ticket. À chaque création de ticket, le plugin calcule la valeur de l'`AUTO_INCREMENT` à partir du format configuré et de la date courante, puis la positionne si elle est supérieure à l'ID maximum existant.

| Format | Exemple (15 janvier 2025) |
|--------|--------------------------|
| `Y000001` | 2025000001 |
| `Ym0001` | 202501 0001 |
| `Ymd01` | 20250115 01 |
| `ymd0001` | 250115 0001 |

---

### Affectation automatique du technicien résolvant

Avec `ticketsolved_updatetech` activé :
- Lors de la résolution d'un ticket, si aucun technicien n'est affecté (ou si le technicien affecté est différent de l'utilisateur connecté), l'utilisateur connecté est automatiquement ajouté comme technicien.
- Ce comportement s'applique aussi lors de l'ajout d'une solution (`ITILSolution::afterAdd`).

Avec `addfup_updatetech` activé :
- Lors de l'ajout d'un suivi, si l'utilisateur appartient à un groupe déjà affecté au ticket, il est automatiquement affecté comme technicien (les techniciens précédents sont remplacés).

---

### Mode technicien unique

Ce mode limite le nombre de techniciens ou groupes pouvant être affectés simultanément à un ticket. La valeur exacte du mode est configurable dans la table de configuration du plugin.

---

### Notifications supplémentaires

Quand `add_notif` est activé, 5 nouveaux événements de notification sont disponibles pour les tickets (accessibles dans **Configuration › Notifications**) :

| Événement | Déclencheur |
|-----------|-------------|
| `plugin_behaviors_ticketreopen` | **Réouverture** d'un ticket (passage d'un statut résolu/clôturé vers un statut ouvert) |
| `plugin_behaviors_ticketstatus` | **Changement de statut** (tout changement hors réouverture et mise en attente) |
| `plugin_behaviors_ticketwaiting` | Ticket passé en statut **En attente** |
| `plugin_behaviors_document_itemnew` | **Ajout d'un document** sur un ticket |
| `plugin_behaviors_document_itemdel` | **Suppression d'un document** sur un ticket |

---

### Nouveaux destinataires de notification

Quand `add_notif` est activé, 6 nouveaux **destinataires** sont disponibles dans les notifications de ticket :

| Destinataire | Description |
|--------------|-------------|
| **Dernier technicien affecté** | Dernier technicien ajouté à l'affectation du ticket |
| **Dernier groupe affecté** | Dernier groupe ajouté à l'affectation du ticket |
| **Dernier fournisseur affecté** | Dernier fournisseur ajouté à l'affectation du ticket |
| **Dernier observateur ajouté** | Dernier observateur ajouté au ticket |
| **Responsable du dernier groupe affecté** | Responsable(s) du dernier groupe affecté |
| **Dernier groupe affecté (sans responsable)** | Membres du dernier groupe affecté hors responsables |

Ces destinataires ne s'appliquent pas aux notifications globales (`alertnotclosed`, `recall`, `recall_ola`).

---

### Clonage d'objets GLPI

Quand `clone` est activé, un onglet **Cloner (Behaviours)** apparaît sur les types d'objets suivants :

- Modèle de notification (`NotificationTemplate`)
- Profil (`Profile`) — avec copie complète des droits
- Règles (`RuleImportComputer`, `RuleImportEntity`, `RuleMailCollector`, `RuleRight`, `RuleSoftwareCategory`, `RuleTicket`)
- Transfert (`Transfer`)
- Ticket (`Ticket`) — avec copie des acteurs, des objets associés et des documents ; un lien « est lié à » est créé entre l'original et le clone

**Modes de clonage :**
- `1` : le clone est créé dans l'**entité active** de l'utilisateur.
- `2` : le clone est créé dans l'**entité d'origine** de l'objet.

---

## Droits requis

La configuration du plugin nécessite le droit GLPI standard **`config`** (lecture et écriture). Aucun droit spécifique au plugin n'est défini — la page de configuration est accessible depuis **Configuration › Configuration générale**.

---

## Désinstallation

1. Aller dans **Configuration › Plugins**.
2. Cliquer sur **Désactiver** puis **Désinstaller** pour *Behaviors*.

> **Attention :** La désinstallation supprime la table `glpi_plugin_behaviors_configs`. Les notifications créées avec les événements du plugin (ex. `plugin_behaviors_ticketreopen`) doivent être supprimées manuellement si elles ne sont plus nécessaires.
