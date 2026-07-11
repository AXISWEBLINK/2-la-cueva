<?php

try {
    require_once __DIR__ . '/config.php';

    require_once __DIR__ . '/PriceTrace.php';
    require_once __DIR__ . '/PriceResult.php';
    require_once __DIR__ . '/PriceRepository.php';
    require_once __DIR__ . '/RuleResolver.php';
    require_once __DIR__ . '/OverrideResolver.php';
    require_once __DIR__ . '/CampaignResolver.php';
    require_once __DIR__ . '/RoundingResolver.php';
    require_once __DIR__ . '/PriceEngine.php';

    $defaultProductId = '1124d263-6df9-11f1-afe8-fa163edb0855';
    $defaultPriceListId = 'f4dcd91d-74e1-11f1-8ac9-fa163edb0855';

    $productId = trim((string)($_REQUEST['product_id'] ?? $defaultProductId));
    $priceListId = trim((string)($_REQUEST['price_list_id'] ?? $defaultPriceListId));

    if ($productId === '' || $priceListId === '') {
        throw new InvalidArgumentException('Debes indicar product_id y price_list_id.');
    }

    $engine = new PriceEngine($pdo);

    $result = $engine->resolve($productId, $priceListId);

    echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><title>Price Engine Test</title>';
    echo '<style>body{font-family:Segoe UI,Arial,sans-serif;margin:24px;background:#f6f7fb;color:#1d2433}';
    echo 'form{display:grid;gap:12px;max-width:900px;padding:16px;background:#fff;border:1px solid #d8deea;border-radius:12px}';
    echo 'label{display:grid;gap:6px;font-weight:600}input{padding:10px 12px;border:1px solid #b9c4d8;border-radius:8px;font-size:14px}';
    echo 'button{width:max-content;padding:10px 16px;border:0;border-radius:8px;background:#1f5eff;color:#fff;font-weight:700;cursor:pointer}';
    echo '.card{margin-top:18px;padding:16px;background:#fff;border:1px solid #d8deea;border-radius:12px}';
    echo 'table{width:100%;border-collapse:collapse;margin-top:12px}th,td{padding:10px;border-bottom:1px solid #e3e8f2;text-align:left;vertical-align:top}';
    echo 'pre{white-space:pre-wrap;word-break:break-word;background:#0f172a;color:#e2e8f0;padding:14px;border-radius:10px;overflow:auto}</style></head><body>';

    echo '<h1>Price Engine Test</h1>';
    echo '<form method="get">';
    echo '<label>Product ID<input type="text" name="product_id" value="' . htmlspecialchars($productId, ENT_QUOTES) . '"></label>';
    echo '<label>Price List ID<input type="text" name="price_list_id" value="' . htmlspecialchars($priceListId, ENT_QUOTES) . '"></label>';
    echo '<button type="submit">Calcular precio</button>';
    echo '</form>';

    echo '<div class="card">';
    echo '<h2>Resultado</h2>';
    echo '<table>';
    echo '<tr><th>Producto</th><td>' . htmlspecialchars($result->productId, ENT_QUOTES) . '</td></tr>';
    echo '<tr><th>Lista</th><td>' . htmlspecialchars($result->priceListId, ENT_QUOTES) . '</td></tr>';
    echo '<tr><th>Precio Final</th><td>' . htmlspecialchars(number_format($result->finalPrice, 2, '.', ''), ENT_QUOTES) . '</td></tr>';
    echo '<tr><th>Precio Bruto</th><td>' . htmlspecialchars(number_format($result->saleGross, 2, '.', ''), ENT_QUOTES) . '</td></tr>';
    echo '<tr><th>Override</th><td>' . ($result->hasOverride ? 'SI' : 'NO') . '</td></tr>';
    echo '<tr><th>Campañas aplicadas</th><td>' . htmlspecialchars((string)count($result->campaigns), ENT_QUOTES) . '</td></tr>';
    echo '</table>';
    echo '</div>';

    echo '<div class="card">';
    echo '<h2>Trace</h2>';
    echo '<table><tr><th>Paso</th><th>Valor</th></tr>';

    foreach ($result->trace?->all() ?? [] as $step) {
        $value = is_array($step['value']) ? json_encode($step['value'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : (string)$step['value'];
        echo '<tr><td>' . htmlspecialchars((string)$step['title'], ENT_QUOTES) . '</td><td>' . htmlspecialchars($value, ENT_QUOTES) . '</td></tr>';
    }

    echo '</table>';
    echo '</div>';

    echo '<div class="card">';
    echo '<h2>Campañas</h2><pre>';
    print_r($result->campaigns);
    echo '</pre></div>';

    echo '<div class="card">';
    echo '<h2>Objeto completo</h2><pre>';
    print_r($result);
    echo '</pre></div>';

    echo '</body></html>';
} catch (Throwable $exception) {
    http_response_code(500);
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><title>Price Engine Error</title></head><body>';
    echo '<h1>Error en PriceEngine</h1>';
    echo '<pre>' . htmlspecialchars($exception->getMessage(), ENT_QUOTES) . "\n\n" . htmlspecialchars($exception->getTraceAsString(), ENT_QUOTES) . '</pre>';
    echo '</body></html>';
}