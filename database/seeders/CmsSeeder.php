<?php

namespace Database\Seeders;

use App\Models\CmsModule;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class CmsSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedModules();
        $this->seedSettings();
        $this->seedNavigation();
    }

    private function seedModules(): void
    {
        $modules = [
            ['slug' => 'dashboard', 'name' => 'Dasbor Eksekutif', 'is_core' => true, 'icon' => 'chart-bar', 'sort_order' => 1],
            ['slug' => 'path-tracing', 'name' => 'Alat Penelusuran Jalur', 'is_core' => true, 'icon' => 'arrow-path', 'sort_order' => 2],
            ['slug' => 'gis-map', 'name' => 'Peta Jaringan GIS', 'is_core' => true, 'icon' => 'map', 'sort_order' => 3],
            ['slug' => 'cable-management', 'name' => 'Manajemen Kabel', 'is_core' => true, 'icon' => 'cable', 'sort_order' => 4],
            ['slug' => 'core-management', 'name' => 'Manajemen Core', 'is_core' => true, 'icon' => 'circle-stack', 'sort_order' => 5],
            ['slug' => 'splitter-management', 'name' => 'Manajemen Splitter', 'is_core' => false, 'icon' => 'share', 'sort_order' => 6],
            ['slug' => 'customer-mapping', 'name' => 'Pemetaan Pelanggan', 'is_core' => false, 'icon' => 'users', 'sort_order' => 7],
            ['slug' => 'fault-management', 'name' => 'Manajemen Gangguan', 'is_core' => false, 'icon' => 'exclamation-triangle', 'sort_order' => 8],
            ['slug' => 'maintenance', 'name' => 'Maintenance', 'is_core' => false, 'icon' => 'wrench', 'sort_order' => 9],
            ['slug' => 'qr-tracking', 'name' => 'QR Code Tracking', 'is_core' => false, 'icon' => 'qr-code', 'sort_order' => 10],
            ['slug' => 'otdr-mapping', 'name' => 'OTDR Mapping', 'is_core' => false, 'icon' => 'signal', 'sort_order' => 11],
            ['slug' => 'impact-analysis', 'name' => 'Analisis Dampak', 'is_core' => false, 'icon' => 'bolt', 'sort_order' => 12],
            ['slug' => 'cms', 'name' => 'Pengaturan CMS', 'is_core' => true, 'icon' => 'cog', 'sort_order' => 99],
        ];

        foreach ($modules as $module) {
            CmsModule::query()->updateOrCreate(
                ['slug' => $module['slug']],
                array_merge($module, ['is_enabled' => true, 'description' => null, 'config' => null]),
            );
        }
    }

    private function seedSettings(): void
    {
        $settings = [
            ['group' => 'branding', 'key' => 'app_name', 'value' => 'HFNMS', 'type' => 'string', 'label' => 'Nama Aplikasi', 'is_public' => true, 'sort_order' => 1],
            ['group' => 'branding', 'key' => 'company_name', 'value' => 'Hinet Fiber', 'type' => 'string', 'label' => 'Nama Perusahaan', 'is_public' => true, 'sort_order' => 2],
            ['group' => 'branding', 'key' => 'logo_path', 'value' => null, 'type' => 'image', 'label' => 'Logo', 'is_public' => true, 'sort_order' => 3],
            ['group' => 'branding', 'key' => 'primary_color', 'value' => '#004cca', 'type' => 'string', 'label' => 'Warna Primary', 'is_public' => true, 'sort_order' => 4],
            ['group' => 'localization', 'key' => 'default_locale', 'value' => 'id', 'type' => 'string', 'label' => 'Bahasa Default', 'is_public' => true, 'sort_order' => 1],
            ['group' => 'localization', 'key' => 'timezone', 'value' => 'Asia/Jakarta', 'type' => 'string', 'label' => 'Timezone', 'is_public' => false, 'sort_order' => 2],
            ['group' => 'features', 'key' => 'tube_standard', 'value' => 'eia_tia_598a', 'type' => 'string', 'label' => 'Standar Warna Tube', 'is_public' => false, 'sort_order' => 1],
            ['group' => 'features', 'key' => 'default_core_counts', 'value' => '["12","24","48"]', 'type' => 'json', 'label' => 'Opsi Jumlah Core', 'is_public' => false, 'sort_order' => 2],
            ['group' => 'features', 'key' => 'maintenance_notify_enabled', 'value' => '1', 'type' => 'boolean', 'label' => 'Notifikasi Maintenance', 'description' => 'Kirim notifikasi saat jadwal maintenance jatuh tempo.', 'is_public' => false, 'sort_order' => 3],
            ['group' => 'features', 'key' => 'bulk_import_enabled', 'value' => '1', 'type' => 'boolean', 'label' => 'Import Bulk Excel', 'description' => 'Aktifkan halaman import ODP dan pelanggan.', 'is_public' => false, 'sort_order' => 4],
            ['group' => 'integration', 'key' => 'map_provider', 'value' => 'openstreetmap', 'type' => 'string', 'label' => 'Provider Peta', 'is_public' => false, 'sort_order' => 1],
        ];

        foreach ($settings as $setting) {
            SystemSetting::query()->updateOrCreate(
                ['group' => $setting['group'], 'key' => $setting['key']],
                $setting,
            );
        }
    }

    private function seedNavigation(): void
    {
        $menu = NavigationMenu::query()->updateOrCreate(
            ['slug' => 'main-sidebar'],
            ['name' => 'Menu Utama', 'location' => 'sidebar', 'is_active' => true],
        );

        NavigationItem::query()->where('menu_id', $menu->id)->delete();

        $items = [
            ['label' => 'Dasbor', 'icon' => 'home', 'route_name' => 'dashboard', 'permission' => null, 'module_slug' => 'dashboard', 'sort_order' => 1],
            ['label' => 'Penelusuran Jalur', 'icon' => 'arrow-path', 'route_name' => 'path-tracing.index', 'permission' => 'path-tracing.view', 'module_slug' => 'path-tracing', 'sort_order' => 2],
            ['label' => 'Peta GIS', 'icon' => 'map', 'route_name' => 'gis.index', 'permission' => 'gis-map.view', 'module_slug' => 'gis-map', 'sort_order' => 3],
            ['label' => 'Manajemen Kabel', 'icon' => 'cable', 'route_name' => 'cables.index', 'permission' => 'cable.manage', 'module_slug' => 'cable-management', 'sort_order' => 4],
            ['label' => 'Manajemen Core', 'icon' => 'circle-stack', 'route_name' => 'cores.index', 'permission' => 'core.manage', 'module_slug' => 'core-management', 'sort_order' => 5],
            ['label' => 'Aset Jaringan', 'icon' => 'share', 'route_name' => null, 'url' => '/network/pops', 'permission' => 'network.view', 'module_slug' => 'splitter-management', 'sort_order' => 6],
            ['label' => 'Pelanggan', 'icon' => 'users', 'route_name' => null, 'url' => '/network/customers', 'permission' => 'customer.view', 'module_slug' => 'customer-mapping', 'sort_order' => 7],
            ['label' => 'Koneksi Pelanggan', 'icon' => 'share', 'route_name' => null, 'url' => '/customer-connections', 'permission' => 'customer.view', 'module_slug' => 'customer-mapping', 'sort_order' => 8],
            ['label' => 'Import Bulk', 'icon' => 'cable', 'route_name' => null, 'url' => '/bulk-import', 'permission' => 'network.create', 'module_slug' => 'qr-tracking', 'sort_order' => 9],
            ['label' => 'Gangguan', 'icon' => 'exclamation-triangle', 'route_name' => 'trouble-tickets.index', 'permission' => 'fault.view', 'module_slug' => 'fault-management', 'sort_order' => 10],
            ['label' => 'Analisis Dampak', 'icon' => 'bolt', 'route_name' => 'impact-analysis.index', 'permission' => 'network.view', 'module_slug' => 'impact-analysis', 'sort_order' => 11],
            ['label' => 'OTDR Mapping', 'icon' => 'signal', 'route_name' => 'otdr-records.index', 'permission' => 'cable.manage', 'module_slug' => 'otdr-mapping', 'sort_order' => 12],
            ['label' => 'Maintenance', 'icon' => 'wrench', 'route_name' => 'maintenance-schedules.index', 'permission' => 'maintenance.view', 'module_slug' => 'maintenance', 'sort_order' => 13],
            ['label' => 'Audit Log', 'icon' => 'cog', 'route_name' => 'audit-logs.index', 'permission' => 'audit.view', 'module_slug' => 'cms', 'sort_order' => 14],
            ['label' => 'Pengguna', 'icon' => 'users', 'route_name' => 'users.index', 'permission' => 'users.manage', 'module_slug' => 'cms', 'sort_order' => 98],
            ['label' => 'Pengaturan', 'icon' => 'cog', 'route_name' => 'settings.index', 'permission' => 'cms.settings.manage', 'module_slug' => 'cms', 'sort_order' => 99],
        ];

        foreach ($items as $item) {
            NavigationItem::query()->create(array_merge($item, ['menu_id' => $menu->id, 'is_active' => true]));
        }
    }
}
