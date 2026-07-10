<?php

try {
    require_once dirname(__DIR__) . '/config.php';

    require_once __DIR__ . '/PriceTrace.php';
    require_once __DIR__ . '/PriceResult.php';
    require_once __DIR__ . '/PriceRepository.php';
    require_once __DIR__ . '/RuleResolver.php';
    require_once __DIR__ . '/OverrideResolver.php';
    require_once __DIR__ . '/CampaignResolver.php';
    require_once __DIR__ . '/RoundingResolver.php';
    require_once __DIR__ . '/PriceEngine.php';

    $engine = new PriceEngine($pdo);

    $result = $engine->resolve(
        '1124d263-6df9-11f1-afe8-fa163edb0855',
        'f4dcd91d-74e1-11f1-8ac9-fa163edb0855'
    );

echo '<h2>FINAL PRICE:</h2>';
var_dump($result->finalPrice);

echo '<h2>CAMPAÑAS:</h2>';
print_r($result->campaigns);

echo '<h2>TRACE:</h2>';
print_r($result->trace);

echo '<h2>OBJETO COMPLETO:</h2>';
print_r($result);

    echo '<pre>';
    print_r($result);
    echo '</pre>';
} catch (Throwable $exception) {
    http_response_code(500);
   echo "<pre>";

print_r($result);

echo "</pre>";
}