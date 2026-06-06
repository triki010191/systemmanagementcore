# HFNMS — Architecture Document

> **Objective utama:** End-to-End Fiber Path Tracing  
> Semua keputusan arsitektur harus mendukung penelusuran jalur fiber dari Customer → POP.

---

## 1. Layer Architecture

```
┌─────────────────────────────────────────────────────────┐
│  Presentation (Blade + Alpine.js + TailwindCSS)         │
│  Leaflet GIS Map · QR Scanner · Realtime (Reverb)       │
├─────────────────────────────────────────────────────────┤
│  HTTP Layer (Controllers — flow only)                   │
│  Form Requests (validation) · Policies (authorization)  │
├─────────────────────────────────────────────────────────┤
│  Service Layer (business logic)                         │
│  PathTracingService · GisService · ImpactAnalysisService│
├─────────────────────────────────────────────────────────┤
│  Repository Layer (data access abstraction)             │
│  NetworkNodeRepository · NetworkLinkRepository            │
├─────────────────────────────────────────────────────────┤
│  Domain Models (Eloquent + Relationships)               │
│  Network Graph: Nodes + Links                           │
├─────────────────────────────────────────────────────────┤
│  MySQL 8 · Spatial (GPS) · Laravel AI SDK               │
└─────────────────────────────────────────────────────────┘
```

### Prinsip

| Layer | Tanggung Jawab | Larangan |
|-------|----------------|----------|
| Controller | Routing, response, delegasi ke Service | Business logic, query DB langsung |
| Form Request | Validasi input | — |
| Service | Business logic, orchestration | Query DB langsung (gunakan Repository) |
| Repository | Query, persistence | Business logic |
| Model | Relasi, accessor, scope | Business logic kompleks |

---

## 2. Network Graph Data Model

Setiap aset jaringan adalah **Node**. Setiap hubungan adalah **Link**.

### Hierarchy (Parent-Child Reference)

```
POP → OLT → OTB → ODC → Splitter → ODP → Customer
```

### Core Tables (Planned)

#### `network_nodes`

Unified node table — polymorphic asset types.

| Column | Type | Notes |
|--------|------|-------|
| id | BIGINT PK | |
| uuid | UUID UNIQUE | QR Code reference |
| type | ENUM | pop, olt, otb, odc, splitter, odp, customer |
| code | VARCHAR UNIQUE | Human-readable asset code |
| name | VARCHAR | |
| latitude | DECIMAL(10,8) | GPS — wajib |
| longitude | DECIMAL(11,8) | GPS — wajib |
| parent_id | FK → network_nodes | Hierarchy parent |
| metadata | JSON | Type-specific attributes |
| status | ENUM | active, inactive, maintenance, fault |
| qr_code_path | VARCHAR | Generated QR image |
| created_at / updated_at | TIMESTAMP | |

#### `network_links`

Connections between nodes — **foundation of path tracing**.

| Column | Type | Notes |
|--------|------|-------|
| id | BIGINT PK | |
| source_node_id | FK → network_nodes | |
| target_node_id | FK → network_nodes | |
| link_type | ENUM | fiber_cable, patch_cord, splitter_port, splice |
| cable_id | FK → fiber_cables NULL | Optional cable reference |
| core_number | TINYINT NULL | Fiber core 1–12/24/48/96 |
| tube_color | VARCHAR NULL | Tube color code |
| capacity | SMALLINT | Max connections |
| length_meters | DECIMAL | Physical length |
| metadata | JSON | OTDR data, loss dB, etc. |
| status | ENUM | active, cut, spare |

#### `fiber_cables`

Physical cable inventory.

| Column | Type | Notes |
|--------|------|-------|
| id | BIGINT PK | |
| code | VARCHAR UNIQUE | |
| cable_type | ENUM | backbone, distribution, drop |
| core_count | SMALLINT | 12, 24, 48, 96 |
| tube_count | TINYINT | |
| length_meters | DECIMAL | |
| route_geometry | JSON | GeoJSON LineString for GIS |
| metadata | JSON | Manufacturer, year, etc. |

#### `fiber_cores`

Individual core within a cable — **Core Management**.

| Column | Type | Notes |
|--------|------|-------|
| id | BIGINT PK | |
| cable_id | FK → fiber_cables | |
| core_number | TINYINT | 1-based |
| tube_number | TINYINT | |
| tube_color | VARCHAR | Standard color code |
| status | ENUM | active, spare, dark, fault |
| source_node_id | FK NULL | Termination point A |
| target_node_id | FK NULL | Termination point B |

#### `tube_colors`

Standard tube color reference — **Tube Color Management**.

| Column | Type | Notes |
|--------|------|-------|
| id | BIGINT PK | |
| standard | ENUM | eia598a, tia598 |
| tube_number | TINYINT | |
| color_name | VARCHAR | Blue, Orange, Green... |
| hex_code | CHAR(7) | #RRGGBB |

---

## 3. Path Tracing Algorithm

```
Input: customer_node_id
Output: ordered path[] from Customer → POP

1. Start at customer node
2. Traverse network_links (target → source) upward via parent_id
3. At each splitter: resolve input/output port from link metadata
4. At each ODC/ODP: resolve core + tube from fiber_cores
5. Continue until type = 'pop'
6. Return full path with GPS coordinates for GIS rendering
```

Service: `App\Services\Network\PathTracingService`

---

## 4. Module Map (Screen → Module)

| Stitch Screen | Laravel Module | Priority |
|---------------|----------------|----------|
| Login System | Auth (Breeze) + Roles | P0 |
| Dasbor Eksekutif | Dashboard + KPI widgets | P1 |
| Alat Penelusuran Jalur | PathTracingService + UI | P0 |
| Manajemen Kabel | FiberCable + Core + Tube CRUD | P0 |
| Peta Jaringan GIS | Leaflet map + node overlay | P0 |
| Design System | Tailwind theme from DESIGN.md | P0 |

---

## 5. Design System Integration

Source: `docs/DESIGN.md` + Stitch Design System screen

- Font: Inter (UI), JetBrains Mono (data/IP/MAC)
- Primary: `#004cca` (Telco Blue)
- Sidebar: Deep Navy `#0F172A`, fixed 260px
- Content: `#F8FAFC` canvas, white cards
- Status: Green (active), Amber (warning), Red (fault)

Tailwind config will extend these tokens from `DESIGN.md`.

---

## 6. Development Order

```
1. Migrations (network_nodes, network_links, fiber_cables, fiber_cores, tube_colors)
2. Models + Relationships
3. Repositories
4. Services (PathTracingService first)
5. Form Requests + Controllers
6. Blade views (from Stitch HTML reference)
7. Tests (PathTracingService, Repository)
```

---

## 7. CMS Architecture (Owner Customization)

Pemilik aplikasi dapat mengkustomisasi tanpa ubah kode:

| Komponen | Tabel | Fungsi |
|----------|-------|--------|
| Settings | `system_settings` | Branding, locale, warna, integrasi |
| Modules | `cms_modules` | Aktif/nonaktif fitur per modul |
| Navigation | `navigation_menus` + `navigation_items` | Menu sidebar dinamis + permission gate |
| Pages | `cms_pages` | Halaman konten kustom (SOP, help) |

**Services:**
- `SettingsService` — get/set dengan cache
- `ModuleService` — cek modul enabled
- `NavigationService` — menu filtered by role + module

**Roles (Spatie Permission):** super-admin, noc, teknisi, customer-service, manager

## 8. Dokumen Acuan Engineering

| Dokumen | Fungsi |
|---------|--------|
| [`NETWORK_ENGINEERING_RULES.md`](NETWORK_ENGINEERING_RULES.md) | Standar tunggal engineering — **prioritas tertinggi** |
| [`DATABASE_DESIGN.md`](DATABASE_DESIGN.md) | Desain tabel & tracing algorithm |
| [`PROJECT_OVERVIEW.md`](PROJECT_OVERVIEW.md) | Ruang lingkup fitur |

## 9. Engineering Decisions (Confirmed)

| Item | Decision |
|------|----------|
| Tube standard | EIA/TIA-598-A (12 tube) |
| Core counts | 12, 24, 48, 96 |
| Core status | available, used, reserved, broken, maintenance |
| Port status | empty, active, reserved, broken, maintenance |
| Customer code | HNT000001 format |
| OTB/ODC/ODP code | OTB-001, ODC-001, ODP-001 |
| Splitter | Port input/output tracking via `splitter_ports` |
| Authorization | Spatie Laravel Permission |

## 10. Rules (Non-Negotiable)

1. Never change architecture without approval
2. Every migration documented in `docs/migrations/`
3. Every model has explicit relationships
4. All assets: GPS + QR Code
5. Customer-to-Core always traceable
6. No duplicate data structures
7. GIS Map, Core Management, Tube Color = core features
