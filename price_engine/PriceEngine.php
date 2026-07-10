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

        $campaigns = $this->repository->getCampaigns(
            $productId,
            $context['proveedor_id'] ?? '',
            $context['price_group_id'] ?? '',
            $priceListId
        );

        $result = new PriceResult();

        $result->productId = $productId;
        $result->priceListId = $priceListId;
        $result->trace = new PriceTrace();
        $result->context = $context;

        $result = (new RuleResolver())->resolve($context, $result);

        $result = (new OverrideResolver())->resolve($context, $result);

        $result = (new CampaignResolver())->resolve($campaigns, $result);

        $result = (new RoundingResolver())->resolve($context, $result);

        return $result;
    }
}