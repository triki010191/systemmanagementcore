# Migration Documentation Index

Semua migrasi HFNMS didokumentasikan di folder ini.

## Network Graph (Fiber Path Tracing)

| Migration | Table | Purpose |
|-----------|-------|---------|
| `2026_06_05_143001` | `tube_colors` | EIA/TIA-598-A 12-tube color reference |
| `2026_06_05_143002` | `network_nodes` | Unified nodes: POP→Customer, GPS, QR |
| `2026_06_05_143003` | `fiber_cables` | Cable inventory 12/24/48/96 core, GIS route |
| `2026_06_05_143004` | `fiber_cores` | Per-core tracking + tube assignment |
| `2026_06_05_143005` | `splitter_ports` | Input/output port per splitter |
| `2026_06_05_143006` | `network_links` | Graph edges — core tracing foundation |

## Domain Extension (Fase 1)

| Migration | Table | Purpose |
|-----------|-------|---------|
| `2026_06_05_150001` | `pops` | POP/Selter domain attributes |
| `2026_06_05_150002` | `olts` | OLT equipment linked to POP |
| `2026_06_05_150003` | `otbs` | OTB termination boxes |
| `2026_06_05_150004` | `odcs` | ODC distribution cabinets |
| `2026_06_05_150005` | `odps` | ODP access points |
| `2026_06_05_150006` | `splitters` | Splitter ratio & port counts |
| `2026_06_05_150007` | `customers` | Customer service records |

## Cable Enhancement (Fase 2)

| Migration | Table | Purpose |
|-----------|-------|---------|
| `2026_06_05_150008` | `cable_tubes` | Tube layer per cable (EIA/TIA) |
| `2026_06_05_150009` | `fiber_cables` alter | Manufacturer, 96-core, QR |
| `2026_06_05_150010` | `fiber_cores` alter | `cable_tube_id`, engineering status enum |
| `2026_06_05_150011` | `splitter_ports` refactor | FK → `splitters`, port status enum |

## Tracing Foundation (Fase 3)

| Migration | Table | Purpose |
|-----------|-------|---------|
| `2026_06_05_150012` | `network_links` alter | `direction`, `loss_db` |
| `2026_06_05_150013` | `customer_connections` | Customer → ODP/core/port binding |

## Operations (Fase 4)

| Migration | Table | Purpose |
|-----------|-------|---------|
| `2026_06_05_150014` | `asset_photos` | Location & device documentation |
| `2026_06_05_150015` | `trouble_tickets` | NOC incident tracking |
| `2026_06_05_150016` | `otdr_records` | OTDR measurement per core |
| `2026_06_05_150017` | `audit_logs` | Change audit trail |

## CMS (Owner Customization)

| Migration | Table | Purpose |
|-----------|-------|---------|
| `2026_06_05_143010` | `system_settings` | Branding, locale, feature config |
| `2026_06_05_143011` | `navigation_menus` | Menu containers (sidebar, header) |
| `2026_06_05_143012` | `navigation_items` | Menu items + permission gates |
| `2026_06_05_143013` | `cms_modules` | Feature on/off per module |
| `2026_06_05_143014` | `cms_pages` | Custom content pages |

## Auth & AI

| Migration | Table | Purpose |
|-----------|-------|---------|
| `2026_06_05_142809` | Spatie permission tables | Roles & permissions |
| `2026_06_05_140414` | `agent_conversations` | Laravel AI SDK memory |
