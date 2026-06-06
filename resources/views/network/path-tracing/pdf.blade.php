<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Path Trace — {{ $trace->customer->code }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a1a; }
        h1 { font-size: 18px; color: #004cca; margin-bottom: 4px; }
        h2 { font-size: 13px; color: #555; margin: 16px 0 8px; border-bottom: 1px solid #ddd; padding-bottom: 4px; }
        .meta { color: #666; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #ddd; padding: 6px 8px; text-align: left; }
        th { background: #f5f5f5; font-size: 10px; text-transform: uppercase; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: bold; }
        .badge-ok { background: #dcfce7; color: #166534; }
        .badge-fail { background: #fee2e2; color: #991b1b; }
        .footer { margin-top: 24px; font-size: 9px; color: #999; }
    </style>
</head>
<body>
    <h1>{{ __('hfnms.path_trace_for', ['name' => $trace->customer->name]) }}</h1>
    <div class="meta">
        {{ $trace->customer->code }} · Trace ID: {{ $trace->traceId }} ·
        @if ($trace->isComplete)
            <span class="badge badge-ok">{{ __('hfnms.path_complete') }}</span>
        @else
            <span class="badge badge-fail">{{ __('hfnms.path_incomplete') }}</span>
        @endif
    </div>

    <h2>{{ __('hfnms.hop_details') }}</h2>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Type</th>
                <th>{{ __('hfnms.code') }}</th>
                <th>{{ __('hfnms.name') }}</th>
                <th>Port</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($trace->hops as $index => $hop)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $hop['type_label'] }}</td>
                    <td style="font-family: monospace;">{{ $hop['code'] }}</td>
                    <td>{{ $hop['name'] }}</td>
                    <td>{{ $hop['port'] ?? '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>{{ __('hfnms.connection_specs') }}</h2>
    <table>
        <tr><th>ONU Serial</th><td>{{ $trace->customer->onu_serial ?? '—' }}</td></tr>
        <tr><th>VLAN</th><td>{{ $trace->customer->vlan_tag ?? '—' }}</td></tr>
        <tr><th>IP</th><td>{{ $trace->customer->ip_address ?? '—' }}</td></tr>
        <tr><th>RX Power</th><td>{{ $trace->metrics['rx_power_dbm'] ?? '—' }} dBm</td></tr>
        <tr><th>TX Power</th><td>{{ $trace->metrics['tx_power_dbm'] ?? '—' }} dBm</td></tr>
        <tr><th>{{ __('hfnms.distance') }}</th><td>{{ $trace->metrics['distance_km'] ?? '—' }} km</td></tr>
    </table>

    @if (! $trace->isComplete)
        <p style="color: #991b1b; margin-top: 12px;">
            <strong>{{ __('hfnms.missing_segments') }}:</strong> {{ implode(', ', $trace->missingSegments) }}
        </p>
    @endif

    <div class="footer">
        HFNMS — {{ __('hfnms.generated_at') }}: {{ $generatedAt->format('d M Y H:i') }}
    </div>
</body>
</html>
