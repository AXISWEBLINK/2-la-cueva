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

    $repository = new PriceRepository($pdo);

    $defaultProductId = '1124d263-6df9-11f1-afe8-fa163edb0855';
    $defaultPriceListId = 'f4dcd91d-74e1-11f1-8ac9-fa163edb0855';

    $productId = trim((string)($_REQUEST['product_id'] ?? $defaultProductId));
    $priceListId = trim((string)($_REQUEST['price_list_id'] ?? $defaultPriceListId));
    $productSearch = trim((string)($_REQUEST['product_search'] ?? ''));

    $priceLists = $repository->getActivePriceLists();
    $searchResults = $repository->searchProducts($productSearch);
    $result = null;
    $errorMessage = null;

    if ($productId !== '' && $priceListId !== '') {
        try {
            $engine = new PriceEngine($pdo);
            $result = $engine->resolve($productId, $priceListId);
        } catch (Throwable $exception) {
            $errorMessage = $exception->getMessage();
        }
    }

    $buildUrl = static function (array $params): string {
        return '?' . http_build_query($params);
    };

    echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><title>Price Engine Test</title>';
    echo '<style>body{font-family:Segoe UI,Arial,sans-serif;margin:24px;background:#f6f7fb;color:#1d2433}';
    echo '.layout{display:grid;gap:18px;max-width:1180px}';
    echo 'form{display:grid;gap:12px;padding:16px;background:#fff;border:1px solid #d8deea;border-radius:12px}';
    echo '.grid-2{display:grid;gap:12px;grid-template-columns:repeat(auto-fit,minmax(240px,1fr))}';
    echo 'label{display:grid;gap:6px;font-weight:600}input,select{padding:10px 12px;border:1px solid #b9c4d8;border-radius:8px;font-size:14px;background:#fff}';
    echo 'button{width:max-content;padding:10px 16px;border:0;border-radius:8px;background:#1f5eff;color:#fff;font-weight:700;cursor:pointer}';
    echo '.card{margin-top:18px;padding:16px;background:#fff;border:1px solid #d8deea;border-radius:12px}';
    echo '.card h2,.card h3{margin-top:0}';
    echo 'table{width:100%;border-collapse:collapse;margin-top:12px}th,td{padding:10px;border-bottom:1px solid #e3e8f2;text-align:left;vertical-align:top}';
    echo 'pre{white-space:pre-wrap;word-break:break-word;background:#0f172a;color:#e2e8f0;padding:14px;border-radius:10px;overflow:auto}';
    echo '.muted{color:#5b6578;font-size:13px}.error{padding:12px 14px;background:#fff1f1;color:#8b1d1d;border:1px solid #efc2c2;border-radius:10px}';
    echo '.pill-list{display:flex;flex-wrap:wrap;gap:8px;margin-top:10px}.pill{display:inline-flex;align-items:center;padding:6px 10px;border-radius:999px;background:#eef3ff;color:#23439a;text-decoration:none;font-size:13px;border:1px solid #cfdcff}';
    echo '.actions a{color:#1f5eff;text-decoration:none;font-weight:600}.actions a:hover{text-decoration:underline}';
    echo '</style></head><body>';

    echo '<h1>Price Engine Test</h1>';
    echo '<div class="layout">';

    echo '<form method="get">';
    echo '<div class="grid-2">';
    echo '<label>Product ID<input type="text" name="product_id" value="' . htmlspecialchars($productId, ENT_QUOTES) . '"></label>';
    echo '<label>Price List ID<input type="text" name="price_list_id" value="' . htmlspecialchars($priceListId, ENT_QUOTES) . '"></label>';
    echo '</div>';
    echo '<label>Buscador de producto por ID, SKU o nombre<input type="text" name="product_search" value="' . htmlspecialchars($productSearch, ENT_QUOTES) . '" placeholder="Ej: 101-CTG-1182ML-BC o Contigo"></label>';
    echo '<button type="submit">Calcular precio</button>';
    echo '</form>';

    if ($priceLists !== []) {
        echo '<div class="card">';
        echo '<h2>Listas activas</h2>';
        echo '<div class="pill-list">';

        foreach ($priceLists as $priceList) {
            $label = $priceList['name'] . ' (' . $priceList['code'] . ')';
            echo '<a class="pill" href="' . htmlspecialchars($buildUrl([
                'product_id' => $productId,
                'price_list_id' => $priceList['id'],
                'product_search' => $productSearch,
            ]), ENT_QUOTES) . '">' . htmlspecialchars($label, ENT_QUOTES) . '</a>';
        }

        echo '</div>';
        echo '</div>';
    }

    if ($searchResults !== []) {
        echo '<div class="card">';
        echo '<h2>Resultados de busqueda</h2>';
        echo '<table>';
        echo '<tr><th>SKU</th><th>Producto</th><th>Proveedor</th><th>Costo</th><th>Accion</th></tr>';

        foreach ($searchResults as $product) {
            $productName = trim((string)($product['name'] ?? '') . ' ' . (string)($product['name2'] ?? ''));
            $providerName = (string)($product['provider_name'] ?: $product['proveedor_name'] ?: '');
            $costLabel = number_format((float)$product['regular_price'], 2, '.', '')
                . ' / '
                . strtoupper((string)$product['cost_type'])
                . ' / IVA compra '
                . number_format((float)$product['purchase_vat_percent'], 2, '.', '');

            echo '<tr>';
            echo '<td>' . htmlspecialchars((string)$product['sku'], ENT_QUOTES) . '</td>';
            echo '<td>' . htmlspecialchars($productName, ENT_QUOTES) . '<div class="muted">' . htmlspecialchars((string)$product['id'], ENT_QUOTES) . '</div></td>';
            echo '<td>' . htmlspecialchars($providerName, ENT_QUOTES) . '</td>';
            echo '<td>' . htmlspecialchars($costLabel, ENT_QUOTES) . '</td>';
            echo '<td class="actions"><a href="' . htmlspecialchars($buildUrl([
                'product_id' => $product['id'],
                'price_list_id' => $priceListId !== '' ? $priceListId : $defaultPriceListId,
                'product_search' => $productSearch,
            ]), ENT_QUOTES) . '">Usar producto</a></td>';
            echo '</tr>';
        }

        echo '</table>';
        echo '</div>';
    } elseif ($productSearch !== '') {
        echo '<div class="card"><h2>Resultados de busqueda</h2><p class="muted">No se encontraron productos con ese criterio.</p></div>';
    }

    if ($errorMessage !== null) {
        echo '<div class="error">' . htmlspecialchars($errorMessage, ENT_QUOTES) . '</div>';
    }

    if ($result instanceof PriceResult) {
        $context = $result->context;

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
        echo '<h2>Contexto resuelto</h2>';
        echo '<table>';
        echo '<tr><th>Nombre</th><td>' . htmlspecialchars((string)($context['name'] ?? ''), ENT_QUOTES) . '</td></tr>';
        echo '<tr><th>Proveedor</th><td>' . htmlspecialchars((string)($context['provider_name'] ?? $context['proveedor_name'] ?? ''), ENT_QUOTES) . '</td></tr>';
        echo '<tr><th>Grupo</th><td>' . htmlspecialchars((string)($context['group_name'] ?? ''), ENT_QUOTES) . '<div class="muted">' . htmlspecialchars((string)($context['group_code'] ?? ''), ENT_QUOTES) . ' / ' . htmlspecialchars((string)($context['price_group_source'] ?? ''), ENT_QUOTES) . '</div></td></tr>';
        echo '<tr><th>Moneda</th><td>' . htmlspecialchars((string)($context['currency_code'] ?? $context['moneda'] ?? ''), ENT_QUOTES) . '</td></tr>';
        echo '<tr><th>Costo cargado</th><td>' . htmlspecialchars(number_format((float)($context['regular_price'] ?? 0), 2, '.', ''), ENT_QUOTES) . '</td></tr>';
        echo '<tr><th>Tipo de costo</th><td>' . htmlspecialchars(strtoupper((string)($context['cost_type'] ?? '')), ENT_QUOTES) . '</td></tr>';
        echo '<tr><th>IVA compra</th><td>' . htmlspecialchars(number_format((float)($context['purchase_vat_percent'] ?? 0), 2, '.', ''), ENT_QUOTES) . '</td></tr>';
        echo '<tr><th>IVA venta</th><td>' . htmlspecialchars(number_format((float)($context['sale_vat_percent'] ?? 0), 2, '.', ''), ENT_QUOTES) . '</td></tr>';
        echo '<tr><th>Lista</th><td>' . htmlspecialchars((string)($context['price_list_name'] ?? ''), ENT_QUOTES) . '<div class="muted">' . htmlspecialchars((string)($context['price_list_code'] ?? ''), ENT_QUOTES) . '</div></td></tr>';
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
    }

    echo '</div>';
    echo '</body></html>';
} catch (Throwable $exception) {
    http_response_code(500);
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><title>Price Engine Error</title></head><body>';
    echo '<h1>Error en PriceEngine</h1>';
    echo '<pre>' . htmlspecialchars($exception->getMessage(), ENT_QUOTES) . "\n\n" . htmlspecialchars($exception->getTraceAsString(), ENT_QUOTES) . '</pre>';
    echo '</body></html>';
}