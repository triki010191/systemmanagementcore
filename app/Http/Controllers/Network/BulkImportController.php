<?php

namespace App\Http\Controllers\Network;

use App\Exports\CustomerTemplateExport;
use App\Exports\OdpTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\CustomerImport;
use App\Imports\OdpImport;
use App\Services\Cms\SettingsService;
use App\Services\Network\AssetCodeGenerator;
use App\Services\Network\CustomerConnectionService;
use App\Services\Network\NetworkAssetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BulkImportController extends Controller
{
    public function __construct(
        private readonly SettingsService $settingsService,
    ) {}

    public function index(): View
    {
        $this->authorize('network.create');
        abort_unless($this->settingsService->featureEnabled('bulk_import_enabled'), 403);

        return view('network.bulk-import.index');
    }

    public function template(string $type): BinaryFileResponse
    {
        $this->authorize('network.create');
        abort_unless($this->settingsService->featureEnabled('bulk_import_enabled'), 403);

        return match ($type) {
            'odp' => Excel::download(new OdpTemplateExport, 'template-import-odp.xlsx'),
            'customer' => Excel::download(new CustomerTemplateExport, 'template-import-pelanggan.xlsx'),
            default => abort(404),
        };
    }

    public function import(Request $request, string $type): RedirectResponse
    {
        $this->authorize('network.create');
        abort_unless($this->settingsService->featureEnabled('bulk_import_enabled'), 403);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $importer = match ($type) {
            'odp' => new OdpImport(
                app(NetworkAssetService::class),
                app(AssetCodeGenerator::class),
            ),
            'customer' => new CustomerImport(
                app(NetworkAssetService::class),
                app(CustomerConnectionService::class),
                app(AssetCodeGenerator::class),
            ),
            default => abort(404),
        };

        Excel::import($importer, $request->file('file'));

        $message = match ($type) {
            'odp' => __('hfnms.import_odp_result', ['count' => $importer->imported]),
            'customer' => __('hfnms.import_customer_result', [
                'count' => $importer->imported,
                'connections' => $importer->connectionsCreated,
            ]),
        };

        return back()
            ->with('success', $message)
            ->with('import_errors', $importer->errors);
    }
}
