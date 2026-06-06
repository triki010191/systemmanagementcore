# HFNMS Project Guidelines

Baca `docs/PROJECT_OVERVIEW.md` dan `docs/NETWORK_ENGINEERING_RULES.md` sebelum menambah fitur baru.
Desain database mengikuti `docs/DATABASE_DESIGN.md` — jangan buat tabel di luar rancangan tanpa persetujuan.

## Stack

- Laravel 13, PHP 8.4+
- Blade + Alpine.js + Tailwind CSS (Breeze)
- MySQL 8
- Laravel Reverb (realtime)
- Laravel AI SDK (agents, embeddings, tool-calling)

## Arsitektur Data

Gunakan pendekatan **Network Graph** (Node + Link), bukan relasi tabel tradisional saja.

Node: POP, OTB, ODC, ODP, Splitter, Customer
Link: Kabel FO, Patch Cord, Splitter Connection

## Prioritas Fitur

1. Fiber Path Tracing
2. GIS Network Map (Leaflet + OpenStreetMap)
3. Core / Tube / Splitter Management
4. Customer Mapping, Asset Tracking, QR Code
5. OTDR Mapping, Impact Analysis

## Larangan

Jangan menambah fitur di luar ruang lingkup ISP Fiber Optic tanpa persetujuan.

## Konvensi Kode

- MVC ketat: Controller (flow), Model (DB), View (UI)
- Validasi input wajib
- Query Builder / Eloquent (hindari raw SQL tanpa binding)
