<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 14px 12px; }
        body { font-family: DejaVu Sans, sans-serif; color: #111; font-size: 8.5px; line-height: 1.35; }
        .center { text-align: center; }
        .company { font-size: 12px; font-weight: bold; letter-spacing: .4px; }
        .title { margin: 10px 0 3px; font-size: 10px; font-weight: bold; }
        .code { font-size: 11px; font-weight: bold; }
        .rule { border-top: 1px dashed #333; margin: 7px 0; }
        table { width: 100%; border-collapse: collapse; }
        td, th { padding: 2px 0; vertical-align: top; }
        th { text-align: left; font-size: 7.5px; }
        .right { text-align: right; }
        .label { width: 58px; font-weight: bold; }
        .item { font-weight: bold; text-transform: uppercase; }
        .total td { font-size: 11px; font-weight: bold; padding-top: 4px; }
        .small { font-size: 7px; color: #333; }
        .thanks { margin-top: 12px; font-size: 9px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="center">
        <div class="company">{{ config('damian.company_name') }}</div>
        <div>RUC {{ config('damian.ruc') ?: 'NO CONFIGURADO' }}</div>
        @if (config('damian.address'))<div class="small">{{ config('damian.address') }}</div>@endif
        <div class="title">COTIZACIÓN DE SERVICIO TÉCNICO</div>
        <div class="code">{{ $orden->codigo }}</div>
    </div>

    <div class="rule"></div>
    <table>
        <tr><td class="label">RUC/DNI:</td><td>{{ $orden->documento_cliente ?: 'NO REGISTRADO' }}</td></tr>
        <tr><td class="label">CLIENTE:</td><td>{{ mb_strtoupper($orden->cliente) }}</td></tr>
        <tr><td class="label">EMISIÓN:</td><td>{{ ($orden->cotizacion_generada_en ?? now())->format('d-m-Y') }}</td></tr>
        <tr><td class="label">ATENCIÓN:</td><td>{{ $orden->tipo_atencion === 'mantenimiento' ? 'MANTENIMIENTO' : 'REPARACIÓN' }}</td></tr>
        <tr><td class="label">EQUIPO:</td><td>{{ mb_strtoupper(trim($orden->tipo_equipo.' '.$orden->marca.' '.$orden->modelo)) }}</td></tr>
    </table>

    <div class="rule"></div>
    <table>
        <thead><tr><th>DESCRIPCIÓN</th><th class="right">CANT.</th><th class="right">P/U</th><th class="right">TOTAL</th></tr></thead>
        <tbody>
            @foreach ($items as $item)
                <tr><td colspan="4" class="item">{{ $item['descripcion'] }}</td></tr>
                <tr>
                    <td></td>
                    <td class="right">{{ number_format($item['cantidad'], 2) }}</td>
                    <td class="right">S/ {{ number_format($item['precio'], 2) }}</td>
                    <td class="right">S/ {{ number_format($item['cantidad'] * $item['precio'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="rule"></div>
    <table>
        <tr><td>VALOR SIN IGV</td><td class="right">S/ {{ number_format((float) $orden->base_imponible, 2) }}</td></tr>
        <tr><td>IGV INCLUIDO (18%)</td><td class="right">S/ {{ number_format((float) $orden->igv_incluido, 2) }}</td></tr>
        <tr class="total"><td>TOTAL A PAGAR</td><td class="right">S/ {{ number_format((float) $orden->precio_cotizado, 2) }}</td></tr>
    </table>
    <div class="rule"></div>

    <div>{{ $importeLetras }}</div>
    <div class="rule"></div>
    <table>
        <tr><td class="label">ESTADO:</td><td>{{ $orden->decision_cliente === 'aprobada' ? 'APROBADA POR EL CLIENTE' : 'PENDIENTE DE APROBACIÓN' }}</td></tr>
        <tr><td class="label">CANAL:</td><td>{{ mb_strtoupper($orden->canal_aprobacion ?: 'NO REGISTRADO') }}</td></tr>
    </table>

    <div class="center thanks">¡GRACIAS POR SU PREFERENCIA!</div>
    <div class="center small">Documento de cotización · DAMIAN OS</div>
</body>
</html>
