<?php
declare(strict_types=1);

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

    $escape = static function ($value): string {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    };

    $formatAmount = static function ($value, int $decimals = 2): string {
        return number_format((float)$value, $decimals, '.', '');
    };

    $productId = trim((string)($_GET['product_id'] ?? ''));
    $sku = trim((string)($_GET['sku'] ?? ''));
    $providerId = trim((string)($_GET['provider_id'] ?? ''));
    $manualPriceListId = trim((string)($_GET['price_list_id'] ?? ''));
    $selectedPriceListId = trim((string)($_GET['price_list_id_select'] ?? ''));
    $effectivePriceListId = $selectedPriceListId !== '' ? $selectedPriceListId : $manualPriceListId;

    $providers = $repository->getProviders();
    $priceLists = $repository->getActivePriceLists();

    $result = null;
    $errorMessage = null;
    $resolvedProductId = $productId;

    $isSubmitted =
        array_key_exists('product_id', $_GET)
        || array_key_exists('sku', $_GET)
        || array_key_exists('provider_id', $_GET)
        || array_key_exists('price_list_id', $_GET)
        || array_key_exists('price_list_id_select', $_GET);

    if ($isSubmitted) {
        if ($effectivePriceListId === '') {
            $errorMessage = 'Seleccione una lista o ingrese un Price List ID manual.';
        } elseif ($resolvedProductId === '') {
            if ($sku === '') {
                $errorMessage = 'Ingrese un Product ID manual o un SKU exacto.';
            } elseif ($providerId === '') {
                $errorMessage = 'Seleccione un proveedor para resolver por SKU exacto.';
            } else {
                $matches = [];

                foreach ($repository->searchProducts($sku, $providerId, 50) as $product) {
                    $candidateSku = trim((string)($product['sku'] ?? ''));
                    $candidateProviderId = trim((string)($product['proveedor_id'] ?? ''));

                    if ($candidateSku === $sku && $candidateProviderId === $providerId) {
                        $matches[] = $product;
                    }
                }

                if (count($matches) === 1) {
                    $resolvedProductId = trim((string)($matches[0]['id'] ?? ''));

                    if ($resolvedProductId === '') {
                        $errorMessage = 'Se encontro el SKU, pero no se pudo obtener el Product ID.';
                    }
                } elseif (count($matches) === 0) {
                    $errorMessage = 'No se encontro un producto con ese SKU exacto para el proveedor seleccionado.';
                } else {
                    $errorMessage = 'Se encontro mas de un producto con ese SKU exacto para el proveedor seleccionado. Use Product ID manual.';
                }
            }
        }

        if ($errorMessage === null && $resolvedProductId !== '') {
            try {
                $engine = new PriceEngine($pdo);
                $result = $engine->resolve($resolvedProductId, $effectivePriceListId);
            } catch (Throwable $exception) {
                $errorMessage = $exception->getMessage();
            }
        }
    }

    echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><title>Price Engine Test</title>';
    echo '<style>';
    echo 'body{font-family:Segoe UI,Arial,sans-serif;margin:24px;background:#f6f7fb;color:#1d2433}';
    echo '.layout{display:grid;gap:18px;max-width:1180px}';
    echo 'form{display:grid;gap:12px;padding:16px;background:#fff;border:1px solid #d8deea;border-radius:12px}';
    echo '.grid-2{display:grid;gap:12px;grid-template-columns:repeat(auto-fit,minmax(240px,1fr))}';
    echo 'label{display:grid;gap:6px;font-weight:600}';
    echo 'input,select{padding:10px 12px;border:1px solid #b9c4d8;border-radius:8px;font-size:14px;background:#fff}';
    echo 'button{width:max-content;padding:10px 16px;border:0;border-radius:8px;background:#1f5eff;color:#fff;font-weight:700;cursor:pointer}';
    echo '.card{padding:16px;background:#fff;border:1px solid #d8deea;border-radius:12px}';
    echo '.card h2{margin-top:0}';
    echo 'table{width:100%;border-collapse:collapse;margin-top:12px}';
    echo 'th,td{padding:10px;border-bottom:1px solid #e3e8f2;text-align:left;vertical-align:top}';
    echo 'pre{white-space:pre-wrap;word-break:break-word;background:#0f172a;color:#e2e8f0;padding:14px;border-radius:10px;overflow:auto}';
    echo '.muted{color:#5b6578;font-size:13px;line-height:1.45}';
    echo '.error{padding:12px 14px;background:#fff1f1;color:#8b1d1d;border:1px solid #efc2c2;border-radius:10px}';
    echo '</style></head><body>';

    echo '<h1>Price Engine Test</h1>';
    echo '<div class="layout">';

    echo '<form method="get">';
    echo '<div class="grid-2">';
    echo '<label>Product ID<input type="text" name="product_id" value="' . $escape($productId) . '" placeholder="UUID del producto"></label>';
    echo '<label>SKU exacto<input type="text" name="sku" value="' . $escape($sku) . '" placeholder="Ej: zzprueba1-sku1"></label>';
    echo '<label>Proveedor<select name="provider_id">';
    echo '<option value=""></option>';

    foreach ($providers as $provider) {
        $optionValue = (string)($provider['id'] ?? '');
        $selected = $optionValue === $providerId ? ' selected' : '';
        echo '<option value="' . $escape($optionValue) . '"' . $selected . '>' . $escape($provider['nombre'] ?? $optionValue) . '</option>';
    }

    echo '</select></label>';
    echo '<label>Price List ID manual<input type="text" name="price_list_id" value="' . $escape($manualPriceListId) . '" placeholder="UUID de la lista"></label>';
    echo '<label>Lista activa<select name="price_list_id_select">';
    echo '<option value=""></option>';

    foreach ($priceLists as $priceList) {
        $optionValue = (string)($priceList['id'] ?? '');
        $selected = $optionValue === $selectedPriceListId ? ' selected' : '';
        $label = trim((string)($priceList['name'] ?? '') . ' (' . (string)($priceList['code'] ?? '') . ')');
        echo '<option value="' . $escape($optionValue) . '"' . $selected . '>' . $escape($label) . '</option>';
    }

    echo '</select></label>';
    echo '</div>';
    echo '<p class="muted">Resolucion estricta: si Product ID tiene valor, se usa directo. Si esta vacio, el motor intenta resolver solo por SKU exacto + proveedor. El desplegable de lista tiene prioridad sobre el Price List ID manual.</p>';
    echo '<button type="submit">Calcular precio</button>';
    echo '</form>';

    if ($errorMessage !== null) {
        echo '<div class="error">' . $escape($errorMessage) . '</div>';
    }

    if ($result instanceof PriceResult) {
        $context = $result->context;
        $currencyRate = (float)($context['currency_rate'] ?? 0);
        $convertedFinalPrice = $result->finalPrice * $currencyRate;

        echo '<div class="card">';
        echo '<h2>Resultado</h2>';
        echo '<table>';
        echo '<tr><th>Producto</th><td>' . $escape($result->productId) . '</td></tr>';
        echo '<tr><th>Producto resuelto</th><td>' . $escape($resolvedProductId) . '</td></tr>';
        echo '<tr><th>Lista efectiva</th><td>' . $escape($effectivePriceListId) . '</td></tr>';
        echo '<tr><th>Precio Final</th><td>' . $escape($formatAmount($result->finalPrice)) . '</td></tr>';
        echo '<tr><th>Precio Bruto</th><td>' . $escape($formatAmount($result->saleGross)) . '</td></tr>';
        echo '<tr><th>Cotizacion</th><td>' . $escape($formatAmount($currencyRate, 4)) . '<div class="muted">Informativo solamente</div></td></tr>';
        echo '<tr><th>Final x cotizacion</th><td>' . $escape($formatAmount($convertedFinalPrice)) . '<div class="muted">Informativo solamente</div></td></tr>';
        echo '<tr><th>Override</th><td>' . ($result->hasOverride ? 'SI' : 'NO') . '</td></tr>';
        echo '<tr><th>Campañas aplicadas</th><td>' . $escape((string)count($result->campaigns)) . '</td></tr>';
        echo '</table>';
        echo '</div>';

        echo '<div class="card">';
        echo '<h2>Contexto resuelto</h2>';
        echo '<table>';
        echo '<tr><th>Producto ID</th><td>' . $escape($context['product_id'] ?? '') . '</td></tr>';
        echo '<tr><th>SKU</th><td>' . $escape($context['sku'] ?? '') . '</td></tr>';
        echo '<tr><th>Nombre</th><td>' . $escape(trim((string)($context['name'] ?? '') . ' ' . (string)($context['name2'] ?? ''))) . '</td></tr>';
        echo '<tr><th>Proveedor</th><td>' . $escape($context['provider_name'] ?? ($context['proveedor_name'] ?? '')) . '</td></tr>';
        echo '<tr><th>Grupo</th><td>' . $escape($context['group_name'] ?? '') . '<div class="muted">' . $escape($context['group_code'] ?? '') . ' / ' . $escape($context['price_group_source'] ?? '') . '</div></td></tr>';
        echo '<tr><th>Moneda</th><td>' . $escape($context['currency_code'] ?? ($context['moneda'] ?? '')) . '</td></tr>';
        echo '<tr><th>Cotizacion moneda</th><td>' . $escape($formatAmount($currencyRate, 4)) . '</td></tr>';
        echo '<tr><th>Costo cargado</th><td>' . $escape($formatAmount((float)($context['regular_price'] ?? 0))) . '</td></tr>';
        echo '<tr><th>Tipo de costo</th><td>' . $escape(strtoupper((string)($context['cost_type'] ?? ''))) . '</td></tr>';
        echo '<tr><th>IVA compra</th><td>' . $escape($formatAmount((float)($context['purchase_vat_percent'] ?? 0))) . '</td></tr>';
        echo '<tr><th>IVA venta</th><td>' . $escape($formatAmount((float)($context['sale_vat_percent'] ?? 0))) . '</td></tr>';
        echo '<tr><th>Lista</th><td>' . $escape($context['price_list_name'] ?? '') . '<div class="muted">' . $escape($context['price_list_code'] ?? '') . '</div></td></tr>';
        echo '</table>';
        echo '</div>';

        echo '<div class="card">';
        echo '<h2>Trace</h2>';
        echo '<table><tr><th>Paso</th><th>Valor</th></tr>';

        foreach ($result->trace?->all() ?? [] as $step) {
            $rawValue = $step['value'] ?? '';

            if (is_array($rawValue) || is_object($rawValue)) {
                $encodedValue = json_encode($rawValue, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $value = $encodedValue !== false ? $encodedValue : print_r($rawValue, true);
            } elseif (is_bool($rawValue)) {
                $value = $rawValue ? 'true' : 'false';
            } else {
                $value = (string)$rawValue;
            }

            echo '<tr><td>' . $escape($step['title'] ?? '') . '</td><td>' . $escape($value) . '</td></tr>';
        }

        echo '</table>';
        echo '</div>';

        echo '<div class="card">';
        echo '<h2>Campañas</h2>';
        echo '<pre>' . $escape(print_r($result->campaigns, true)) . '</pre>';
        echo '</div>';

        echo '<div class="card">';
        echo '<h2>Objeto completo</h2>';
        echo '<pre>' . $escape(print_r($result, true)) . '</pre>';
        echo '</div>';
    }

    echo '</div>';
    echo '</body></html>';
} catch (Throwable $exception) {
    http_response_code(500);
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><title>Price Engine Error</title></head><body>';
    echo '<h1>Error en PriceEngine</h1>';
    echo '<pre>' . htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8') . "\n\n" . htmlspecialchars($exception->getTraceAsString(), ENT_QUOTES, 'UTF-8') . '</pre>';
    echo '</body></html>';
}