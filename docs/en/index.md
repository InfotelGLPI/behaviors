# Documentation — Behaviors Plugin for GLPI

**License:** GNU AGPL v3+  
**Authors:** Infotel, Remi Collet, Nelly Mahu-Lasson  
**Repository:** https://github.com/InfotelGLPI/behaviors

---

## Table of Contents

1. [Overview](#overview)
2. [Installation](#installation)
3. [Configuration](#configuration)
   - [Automatic groups](#automatic-groups)
   - [Mandatory fields on resolution](#mandatory-fields-on-resolution)
   - [Mandatory fields on assignment](#mandatory-fields-on-assignment)
   - [Pending tasks must be completed](#pending-tasks-must-be-completed)
   - [ITIL number format](#itil-number-format)
   - [Solving technician and followup](#solving-technician-and-followup)
   - [Additional notifications](#additional-notifications)
   - [Cloning](#cloning)
4. [Features in detail](#features-in-detail)
   - [Automatic requester group](#automatic-requester-group)
   - [Automatic technician group](#automatic-technician-group)
   - [Mandatory fields before resolution](#mandatory-fields-before-resolution)
   - [Mandatory category on assignment](#mandatory-category-on-assignment)
   - [Blocking pending tasks](#blocking-pending-tasks)
   - [Ticket and change number format](#ticket-and-change-number-format)
   - [Automatic solving technician assignment](#automatic-solving-technician-assignment)
   - [Single technician mode](#single-technician-mode)
   - [Additional notifications](#additional-notifications-1)
   - [New notification recipients](#new-notification-recipients)
   - [Cloning GLPI objects](#cloning-glpi-objects)
5. [Required rights](#required-rights)
6. [Uninstallation](#uninstallation)

---

## Overview

The **Behaviors** plugin adds optional behaviors to GLPI to improve the management of tickets, problems, and changes. It allows you to:

- Make certain fields **mandatory** before resolving or closing a ticket
- **Automatically** add the requester's group or technician's group when creating or updating a ticket
- Define a **custom format** for ticket and change numbers
- Send **additional notifications** (reopening, status change, waiting, document added/removed)
- **Clone** profiles, notification templates, rules, and tickets
- Automatically assign the **solving technician** when closing a ticket

---

## Installation

1. Download the plugin from [GitHub](https://github.com/InfotelGLPI/behaviors) or the GLPI marketplace.
2. Extract the archive into the `plugins/` (or `marketplace/`) directory of your GLPI installation.
3. Log in to GLPI as an administrator.
4. Go to **Setup › General setup › Behaviours tab**, then **Install** and **Enable** *Behaviors*.

---

## Configuration

Access: **Setup › General setup › Behaviours tab**  
(Required right: `config`)

### Automatic groups

| Option | Field | Description |
|--------|-------|-------------|
| **Requester group (item)** | `use_requester_item_group` | Automatically adds the group of the associated item as the requester group |
| **Requester group (user)** | `use_requester_user_group` | Automatically adds the requester's GLPI group as the ticket's requester group. Values: `0` = disabled, `1` = first group only, `2` = all groups |
| **Technician group (on create)** | `use_assign_user_group` | Automatically adds the assigned technician's GLPI group as the tech group on ticket creation. Values: `0` = disabled, `1` = first group only, `2` = all groups |
| **Technician group (on update)** | `use_assign_user_group_update` | Same behavior as above when a ticket is updated |

### Mandatory fields on resolution

These fields must be filled in before a ticket/problem can be set to **Solved** or **Closed**:

| Option | Field | Description |
|--------|-------|-------------|
| **Solution type** | `is_ticketsolutiontype_mandatory` | A solution type must be selected |
| **Solution description** | `is_ticketsolution_mandatory` | A solution description must be entered |
| **Category** | `is_ticketcategory_mandatory` | A category must be chosen |
| **Technician** | `is_tickettech_mandatory` | A technician must be assigned |
| **Technician group** | `is_tickettechgroup_mandatory` | A technician group must be assigned |
| **Duration** | `is_ticketrealtime_mandatory` | The actual duration must be entered |
| **Location** | `is_ticketlocation_mandatory` | A location must be selected |
| **Solution type (problem)** | `is_problemsolutiontype_mandatory` | For problems: a solution type is mandatory |

> Visual warnings are displayed in the form as soon as these conditions are not met (before submission).

### Mandatory fields on assignment

| Option | Field | Description |
|--------|-------|-------------|
| **Category on assignment** | `is_ticketcategory_mandatory_on_assign` | A category must be selected when assigning a technician or group |
| **Task category** | `is_tickettaskcategory_mandatory` | A category is required for every ticket task |

### Pending tasks must be completed

| Option | Field | Description |
|--------|-------|-------------|
| **Pending tasks (ticket)** | `is_tickettasktodo` | Blocks resolution if any task is still in "To do" status |
| **Pending tasks (problem)** | `is_problemtasktodo` | Same behavior for problems |
| **Pending tasks (change)** | `is_changetasktodo` | Same behavior for changes |

### ITIL number format

| Option | Field | Description |
|--------|-------|-------------|
| **Ticket number format** | `tickets_id_format` | Prefixes the ticket ID with a date. Examples: `Y000001` (2025000001), `Ym0001` (202501 0001), `Ymd01` (20250115 01), `ymd0001` (250115 0001) |
| **Change number format** | `changes_id_format` | Same principle for changes |

> The format adjusts the `AUTO_INCREMENT` on the `glpi_tickets` table so the next ticket gets the number matching the current date. If the computed value is lower than the current maximum ID, no change is made.

### Solving technician and followup

| Option | Field | Description |
|--------|-------|-------------|
| **Assign solving technician** | `ticketsolved_updatetech` | On ticket resolution, automatically assigns the logged-in user as technician if none is assigned |
| **Assign on followup** | `addfup_updatetech` | When adding a followup, assigns the logged-in user as technician if they belong to an already-assigned group |
| **Lock creation date** | `is_ticketdate_locked` | Prevents modification of a ticket's creation date |

### Single technician mode

| Option | Field | Description |
|--------|-------|-------------|
| **Single technician mode** | `single_tech_mode` | Controls the number of technicians/groups allowed per ticket |

### Additional notifications

| Option | Field | Description |
|--------|-------|-------------|
| **Enable additional notifications** | `add_notif` | Activates 5 new notification events described below |

### Cloning

| Option | Field | Description |
|--------|-------|-------------|
| **Cloning** | `clone` | Enables the "Clone" tab on supported objects. Values: `0` = disabled, `1` = clone in active entity, `2` = clone in source entity |

---

## Features in detail

### Automatic requester group

When `use_requester_user_group` is enabled, the requester's GLPI group(s) are automatically added as the ticket's requester group on creation or update.

- **Mode 1 (first group)**: only the first group found is added.
- **Mode 2 (all groups)**: all groups of the user are added.

The logic also applies to tickets created by e-mail (mail collector).

---

### Automatic technician group

When `use_assign_user_group` (on creation) or `use_assign_user_group_update` (on update) is enabled, the assigned technician's GLPI group is automatically added as the technician group.

- **Mode 1 (first group)**: only the first group found is added.
- **Mode 2 (all groups)**: all groups of the technician are added.

---

### Mandatory fields before resolution

When a technician attempts to set a ticket to **Solved** or **Closed**, the plugin checks the configured mandatory fields. If any is missing:

- An **error message** is displayed.
- The status change is **cancelled**.
- **Visual warnings** are shown in the form as soon as it is opened (before any submission).

The same checks apply to the **solution itself** (type and description).

---

### Mandatory category on assignment

If `is_ticketcategory_mandatory_on_assign` is enabled, any attempt to assign a technician or group without a category is blocked with an error message.

---

### Blocking pending tasks

If `is_tickettasktodo` (or the problem/change variants) is enabled, resolution is blocked as long as any task is in **To do** status (`state = 1`). Additionally, the task status cannot be changed on an already-resolved ticket.

---

### Ticket and change number format

The numbering format embeds the current date in the ticket ID. On each ticket creation, the plugin computes the `AUTO_INCREMENT` value from the configured format and the current date, then sets it if it is greater than the current maximum ID.

| Format | Example (January 15, 2025) |
|--------|---------------------------|
| `Y000001` | 2025000001 |
| `Ym0001` | 202501 0001 |
| `Ymd01` | 20250115 01 |
| `ymd0001` | 250115 0001 |

---

### Automatic solving technician assignment

With `ticketsolved_updatetech` enabled:
- When a ticket is resolved, if no technician is assigned (or if the assigned technician is different from the logged-in user), the logged-in user is automatically added as technician.
- This also applies when adding a solution (`ITILSolution::afterAdd`).

With `addfup_updatetech` enabled:
- When adding a followup, if the logged-in user belongs to a group already assigned to the ticket, they are automatically set as the sole technician (previous technicians are removed).

---

### Single technician mode

This mode limits the number of technicians or groups that can be simultaneously assigned to a ticket. The exact value of the mode is configurable in the plugin's configuration table.

---

### Additional notifications

When `add_notif` is enabled, 5 new notification events become available for tickets (accessible in **Setup › Notifications**):

| Event | Trigger |
|-------|---------|
| `plugin_behaviors_ticketreopen` | **Reopening** of a ticket (moved from solved/closed status back to an open status) |
| `plugin_behaviors_ticketstatus` | **Status change** (any status change other than reopening or waiting) |
| `plugin_behaviors_ticketwaiting` | Ticket set to **Pending** (waiting) status |
| `plugin_behaviors_document_itemnew` | **Document added** to a ticket |
| `plugin_behaviors_document_itemdel` | **Document removed** from a ticket |

---

### New notification recipients

When `add_notif` is enabled, 6 new **recipients** become available in ticket notifications:

| Recipient | Description |
|-----------|-------------|
| **Last technician assigned** | The last technician added to the ticket's assignment |
| **Last group assigned** | The last group added to the ticket's assignment |
| **Last supplier assigned** | The last supplier added to the ticket's assignment |
| **Last watcher added** | The last observer/watcher added to the ticket |
| **Supervisor of last group assigned** | Manager(s) of the last group assigned |
| **Last group assigned without supervisor** | Non-manager members of the last group assigned |

These recipients do not apply to global notifications (`alertnotclosed`, `recall`, `recall_ola`).

---

### Cloning GLPI objects

When `clone` is enabled, a **Clone (Behaviours)** tab appears on the following object types:

- Notification template (`NotificationTemplate`)
- Profile (`Profile`) — with a full copy of all rights
- Rules (`RuleImportComputer`, `RuleImportEntity`, `RuleMailCollector`, `RuleRight`, `RuleSoftwareCategory`, `RuleTicket`)
- Transfer (`Transfer`)
- Ticket (`Ticket`) — with a copy of actors, associated items, and documents; a "linked to" relationship is created between the original and the clone

**Cloning modes:**
- `1`: the clone is created in the user's **active entity**.
- `2`: the clone is created in the **source entity** of the original object.

---

## Required rights

Plugin configuration requires the standard GLPI **`config`** right (read and write). No plugin-specific right is defined — the configuration page is accessible from **Setup › General setup**.

---

## Uninstallation

1. Go to **Setup › Plugins**.
2. Click **Disable** then **Uninstall** for *Behaviors*.

> **Warning:** Uninstalling removes the `glpi_plugin_behaviors_configs` table. Notifications created with plugin events (e.g. `plugin_behaviors_ticketreopen`) must be deleted manually if no longer needed.
