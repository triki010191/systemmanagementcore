# NETWORK ENGINEERING RULES

**Hinet Fiber Network Management System**  
**Network Engineering Rules & Standards**

**Version:** 1.0

---

## TUJUAN

Dokumen ini menjadi standar tunggal dalam pengelolaan jaringan Fiber Optic Hinet.

Semua modul aplikasi, database, API, dashboard, GIS, dan Fiber Path Tracing wajib mengikuti aturan dalam dokumen ini.

Jika terjadi konflik antara kode program dan dokumen ini, maka **dokumen ini menjadi acuan utama**.

---

## HIERARKI JARINGAN

Struktur jaringan Hinet mengikuti urutan berikut:

```
POP
↓
OLT
↓
OTB
↓
ODC
↓
Splitter
↓
ODP
↓
Customer
```

Setiap pelanggan harus memiliki jalur lengkap yang dapat ditelusuri dari Customer hingga POP.

**Tidak boleh ada pelanggan yang tidak memiliki jalur tracing yang lengkap.**

---

## DEFINISI ASSET

### POP

Point of Presence.

Berisi:

- Internet Upstream
- Router Core
- OLT
- Sistem Monitoring

### OLT

Optical Line Terminal.

Fungsi:

- Distribusi layanan GPON
- Menghubungkan pelanggan ke jaringan ISP

### OTB

Optical Termination Box.

Fungsi:

- Terminasi kabel backbone
- Penghubung antara OLT dan ODC

### ODC

Optical Distribution Cabinet.

Fungsi:

- Distribusi kabel menuju beberapa ODP

### SPLITTER

Perangkat pembagi sinyal optik.

Tipe:

- 1:2
- 1:4
- 1:8
- 1:16
- 1:32
- 1:64

### ODP

Optical Distribution Point.

Fungsi:

- Titik distribusi terakhir sebelum pelanggan

### CUSTOMER

Pelanggan akhir yang menerima layanan internet.

---

## STANDAR WARNA FIBER CORE

Urutan warna standar:

1. Biru
2. Orange
3. Hijau
4. Coklat
5. Abu Abu
6. Putih
7. Merah
8. Hitam
9. Kuning
10. Ungu
11. Pink
12. Aqua

---

## STANDAR TUBE

Urutan warna tube:

| Tube | Warna |
|------|-------|
| Tube 1 | Biru |
| Tube 2 | Orange |
| Tube 3 | Hijau |
| Tube 4 | Coklat |
| Tube 5 | Abu Abu |
| Tube 6 | Putih |
| Tube 7 | Merah |
| Tube 8 | Hitam |
| Tube 9 | Kuning |
| Tube 10 | Ungu |
| Tube 11 | Pink |
| Tube 12 | Aqua |

---

## KABEL 12 CORE

- **Jumlah Tube:** 1
- **Jumlah Core:** 12
- **Tube 1:** Core 1–12 (warna sesuai standar di atas)

---

## KABEL 24 CORE

- **Jumlah Tube:** 2
- **Tube 1:** Core 1–12
- **Tube 2:** Core 13–24

---

## KABEL 48 CORE

- **Jumlah Tube:** 4
- **Tube 1:** Core 1–12
- **Tube 2:** Core 13–24
- **Tube 3:** Core 25–36
- **Tube 4:** Core 37–48

---

## KABEL 96 CORE

- **Jumlah Tube:** 8
- **Tube 1:** Core 1–12
- **Tube 2:** Core 13–24
- **Tube 3:** Core 25–36
- **Tube 4:** Core 37–48
- **Tube 5:** Core 49–60
- **Tube 6:** Core 61–72
- **Tube 7:** Core 73–84
- **Tube 8:** Core 85–96

---

## STATUS CORE

Setiap core wajib memiliki status.

| Status | Keterangan |
|--------|------------|
| Available | Siap digunakan |
| Used | Terpakai / aktif |
| Reserved | Direservasi |
| Broken | Rusak / gangguan |
| Maintenance | Dalam perbaikan |

**Tidak boleh ada core tanpa status.**

---

## STATUS PORT

| Status | Keterangan |
|--------|------------|
| Empty | Kosong |
| Active | Aktif terpakai |
| Reserved | Direservasi |
| Broken | Rusak |
| Maintenance | Dalam perbaikan |

---

## PENOMORAN OTB

Format: `OTB-001`, `OTB-002`, `OTB-003`, dst.

---

## PENOMORAN ODC

Format: `ODC-001`, `ODC-002`, `ODC-003`, dst.

---

## PENOMORAN ODP

Format: `ODP-001`, `ODP-002`, `ODP-003`, dst.

---

## PENOMORAN PELANGGAN

Format: `HNT000001`, `HNT000002`, `HNT000003`, dst.

---

## STANDAR GPS

Semua aset wajib memiliki **Latitude** dan **Longitude**.

Asset yang wajib memiliki koordinat:

- POP
- OTB
- ODC
- ODP
- Customer

---

## STANDAR FOTO

Semua aset wajib mendukung dokumentasi foto.

Minimal:

- 1 foto lokasi
- 1 foto perangkat

---

## QR CODE TRACKING

Setiap aset wajib memiliki QR Code unik yang mengarah ke halaman detail aset.

Berlaku untuk:

- POP
- OTB
- ODC
- ODP
- Splitter
- Kabel
- Customer

---

## FIBER PATH TRACING

Sistem wajib mampu melakukan tracing:

```
Customer → ODP → Splitter → ODC → OTB → OLT → POP
```

Tracing harus dapat dilakukan secara **real-time**.

---

## IMPACT ANALYSIS

Jika terjadi gangguan pada Core, Kabel, ODP, ODC, atau OTB, sistem wajib menghitung:

- Jumlah pelanggan terdampak
- Lokasi terdampak
- Jalur terdampak

---

## OTDR MANAGEMENT

Setiap hasil OTDR harus dapat disimpan.

Data minimal:

- Tanggal
- Teknisi
- Core
- Jarak Gangguan
- Loss (dB)
- Catatan

---

## GIS MAP

Seluruh aset harus tampil pada peta.

Layer minimal:

- POP
- OTB
- ODC
- ODP
- Customer
- Kabel Backbone
- Kabel Distribusi

---

## TROUBLE TICKET

| Status | Keterangan |
|--------|------------|
| Open | Baru dibuka |
| Assigned | Ditugaskan |
| On Progress | Sedang ditangani |
| Pending | Menunggu |
| Resolved | Selesai ditangani |
| Closed | Ditutup |

---

## AUDIT LOG

Semua perubahan data harus tercatat.

Minimal:

- User
- Aktivitas
- Waktu
- Data Lama
- Data Baru

---

## RULE PALING PENTING

Semua data harus mendukung:

## END TO END FIBER PATH TRACING

Fitur utama sistem adalah kemampuan menelusuri jalur pelanggan dari rumah pelanggan hingga ke POP secara lengkap dan akurat.
