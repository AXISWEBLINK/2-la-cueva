<?php
declare(strict_types=1);

class PriceEngine
{
    private PriceRepository $repository;

    public function __construct(
        private PDO $pdo
    ) {
        $this->repository = new PriceRepository($pdo);
    }

    public function resolve(
        string $productId,
        string $priceListId
    ): PriceResult {

        $context = $this->repository->getContext(
            $productId,
            $priceListId
        );

        if ($context === []) {
            throw new RuntimeException('No se pudo resolver el contexto de precio para el producto y la lista indicados.');
        }

        $campaigns = $this->repository->getCampaigns($context, $priceListId);

        $result = new PriceResult();

        $result->productId = $productId;
        $result->priceListId = $priceListId;
        $result->trace = new PriceTrace();
        $result->context = $context;

        $result->trace->add('Producto', $context['name'] ?? $productId);
        $result->trace->add('Proveedor', $context['provider_name'] ?? ($context['proveedor_id'] ?? ''));
        $result->trace->add('Grupo de precio', [
            'id' => $context['price_group_id'] ?? null,
            'code' => $context['group_code'] ?? null,
            'name' => $context['group_name'] ?? null,
            'source' => $context['price_group_source'] ?? null,
        ]);
        $result->trace->add('Regla', $context['rule_id'] ?? null);

        $result = (new RuleResolver())->resolve($context, $result);

        $result = (new OverrideResolver())->resolve($context, $result);

        $result = (new CampaignResolver())->resolve($campaigns, $result);

        $result = (new RoundingResolver())->resolve($context, $result);

        return $result;
    }
}