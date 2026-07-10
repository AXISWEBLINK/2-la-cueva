<?php

require_once 'config.php';          // donde creás el PDO

require_once 'price_engine/PriceTrace.php';
require_once 'price_engine/PriceResult.php';
require_once 'price_engine/PriceRepository.php';
require_once 'price_engine/RuleResolver.php';
require_once 'price_engine/OverrideResolver.php';
require_once 'price_engine/CampaignResolver.php';
require_once 'price_engine/RoundingResolver.php';
require_once 'price_engine/PriceEngine.php';

$engine = new PriceEngine($pdo);

$result = $engine->resolve(
    '1124d263-6df9-11f1-afe8-fa163edb0855',
    'f4dcd91d-74e1-11f1-8ac9-fa163edb0855'
);

echo "<pre>";
print_r($result);
echo "</pre>";