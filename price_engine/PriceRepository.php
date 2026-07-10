<?php
declare(strict_types=1);

class PriceRepository
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    public function getContext(
        string $productId,
        string $priceListId
    ): array {

        // SQL PriceContext

        return [];
    }

    public function getCampaigns(
        string $productId,
        string $providerId,
        string $groupId,
        string $priceListId
    ): array {

        // SQL CampaignContext

        return [];
    }
}