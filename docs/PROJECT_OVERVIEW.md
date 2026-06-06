PROJECT OVERVIEW

Nama Project

Hinet Fiber Network Management System (HFNMS)

Tujuan Utama

Membangun sistem manajemen jaringan Fiber Optic untuk ISP Hinet yang mampu mendokumentasikan, memvisualisasikan, dan melakukan penelusuran jalur jaringan dari Core Network hingga pelanggan akhir secara akurat.

Sistem ini bukan sekadar inventaris aset, melainkan platform Network Asset Management dan Fiber Path Tracing yang digunakan oleh NOC, Teknisi Lapangan, dan Manajemen.

⸻

Ruang Lingkup Sistem

Sistem harus mampu mengelola:

* Selter / POP
* OLT
* OTB
* ODC
* ODP
* Splitter
* Kabel Fiber Optic
* Tube
* Core Fiber
* Pelanggan
* Gangguan
* Maintenance
* Dokumentasi Lapangan

⸻

Prinsip Utama Sistem

Seluruh jaringan harus dapat ditelusuri secara end-to-end.

Contoh:

Pelanggan
↓
ODP
↓
Splitter
↓
ODC
↓
OTB
↓
OLT
↓
POP

Sistem harus mampu menampilkan seluruh jalur yang dilalui oleh pelanggan secara otomatis.

⸻

Fitur Prioritas Tinggi

1. Fiber Path Tracing
2. GIS Network Map
3. Core Management
4. Tube Management
5. Splitter Management
6. Customer Mapping
7. Asset Tracking
8. QR Code Tracking
9. OTDR Mapping
10. Impact Analysis

⸻

Arsitektur Data

Gunakan pendekatan Network Graph.

Jangan hanya menggunakan relasi tabel tradisional.

Setiap aset jaringan harus dianggap sebagai Node.

Contoh Node:

* POP
* OTB
* ODC
* ODP
* Splitter
* Customer

Setiap hubungan dianggap sebagai Link.

Contoh Link:

* Kabel FO
* Patch Cord
* Splitter Connection

Dengan pendekatan ini sistem dapat melakukan:

* Path Tracing
* Route Analysis
* Impact Analysis
* Capacity Planning

⸻

Target Pengguna

1. Super Admin
2. NOC
3. Teknisi
4. Customer Service
5. Manager

⸻

Teknologi

Backend:

* Laravel 13
* PHP 8.4+

Frontend:

* Blade
* AlpineJS
* TailwindCSS

Database:

* MySQL 8

Map:

* LeafletJS
* OpenStreetMap

Realtime:

* Laravel Reverb

AI:

* Laravel AI Native

⸻

Larangan Pengembangan

Jangan menambahkan fitur di luar ruang lingkup ISP Fiber Optic tanpa persetujuan.

Fokus utama sistem adalah dokumentasi jaringan, tracing jalur, monitoring aset, dan analisis gangguan.

Semua keputusan pengembangan harus mendukung tujuan tersebut.