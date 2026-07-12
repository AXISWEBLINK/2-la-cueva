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

        $sql = "
        SELECT
            p.id AS product_id,
            p.sku,
            p.name,
            p.regular_price,
            p.proveedor_id,
            p.moneda,
            p.marca_id,
            p.marca AS brand_name,
            pr.nombre AS provider_name,
            COALESCE(pg_product.id, pg_default.id, pg_fallback.id) AS price_group_id,
            COALESCE(pg_product.group_code, pg_default.group_code, pg_fallback.group_code) AS group_code,
            COALESCE(pg_product.nombre, pg_default.nombre, pg_fallback.nombre) AS group_name,
            CASE
                WHEN pg_product.id IS NOT NULL THEN 'product'
                WHEN pg_default.id IS NOT NULL THEN 'default'
                WHEN pg_fallback.id IS NOT NULL THEN 'provider_fallback'
                ELSE 'missing'
            END AS price_group_source,
            pl.id AS price_list_id,
            pl.code AS price_list_code,
            pl.name AS price_list_name,
            pl.channel,
            pl.apply_rounding,
            pl.rounding_mode,
            pl.rounding_step,
            pl.show_decimals,
            pl.prices_include_vat,
            r.id AS rule_id,
            r.base_markup_percent,
            r.vat_percent,
            r.price_markup_percent,
            r.adjustment_percent,
            o.id AS override_id,
            o.manual_price_net,
            o.manual_price_gross,
            o.starts_at AS override_starts_at,
            o.ends_at AS override_ends_at,
            o.is_active AS override_active
        FROM productos p
        INNER JOIN provider pr
            ON pr.id = p.proveedor_id
        LEFT JOIN price_provider_groups pg_product
            ON pg_product.id = p.price_group_id
           AND pg_product.is_active = 1
        LEFT JOIN price_provider_groups pg_default
            ON pg_default.proveedor_id = p.proveedor_id
           AND pg_default.is_default = 1
           AND pg_default.is_active = 1
        LEFT JOIN price_provider_groups pg_fallback
            ON pg_fallback.id = (
                SELECT pg_lookup.id
                FROM price_provider_groups pg_lookup
                WHERE pg_lookup.proveedor_id = p.proveedor_id
                  AND pg_lookup.is_active = 1
                ORDER BY pg_lookup.is_default DESC, pg_lookup.orden ASC, pg_lookup.created_at ASC, pg_lookup.id ASC
                LIMIT 1
            )
        INNER JOIN price_lists_name pl
            ON pl.id = :price_list_id
           AND pl.is_active = 1
        INNER JOIN price_calculation_rules r
            ON r.price_group_id = COALESCE(pg_product.id, pg_default.id, pg_fallback.id)
           AND r.price_list_id = pl.id
           AND r.is_active = 1
        LEFT JOIN price_product_overrides o
            ON o.producto_id = p.id
           AND o.price_list_id = pl.id
           AND o.is_active = 1
        WHERE p.id = :product_id
        LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':product_id' => $productId,
            ':price_list_id' => $priceListId,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return [];
        }

        $row['category_ids'] = $this->getProductCategoryIds($productId);

        return $row;
    }

    public function getCampaigns(array $context, string $priceListId): array
    {
        $sql = "
        SELECT
            c.id,
            c.code,
            c.name,
            c.discount_type,
            c.discount_value,
            c.priority AS campaign_priority,
            c.stackable,
            c.stop_processing,
            t.id AS target_id,
            t.target_type,
            t.target_uuid,
            t.price_list_id AS target_price_list_id,
            t.apply_order,
            t.priority AS target_priority,
            t.match_mode
        FROM price_campaigns c
        INNER JOIN price_campaign_targets t
            ON t.campaign_id = c.id
        WHERE c.is_active = 1
          AND t.is_active = 1
          AND (c.starts_at IS NULL OR c.starts_at <= NOW())
          AND (c.ends_at IS NULL OR c.ends_at >= NOW())
          AND (t.price_list_id IS NULL OR t.price_list_id = :price_list_id)
        ORDER BY t.apply_order ASC, c.priority ASC, t.priority ASC, c.id ASC, t.id ASC
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':price_list_id' => $priceListId,
        ]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($rows === []) {
            return [];
        }

        $campaigns = [];

        foreach ($rows as $row) {
            $campaignId = $row['id'];

            if (!isset($campaigns[$campaignId])) {
                $campaigns[$campaignId] = [
                    'id' => $row['id'],
                    'code' => $row['code'],
                    'name' => $row['name'],
                    'discount_type' => $row['discount_type'],
                    'discount_value' => $row['discount_value'],
                    'campaign_priority' => (int)$row['campaign_priority'],
                    'stackable' => $row['stackable'],
                    'stop_processing' => $row['stop_processing'],
                    'targets' => [],
                ];
            }

            $campaigns[$campaignId]['targets'][] = [
                'id' => $row['target_id'],
                'target_type' => $row['target_type'],
                'target_uuid' => $row['target_uuid'],
                'price_list_id' => $row['target_price_list_id'],
                'apply_order' => (int)$row['apply_order'],
                'target_priority' => (int)$row['target_priority'],
                'match_mode' => $row['match_mode'],
            ];
        }

        $matchedCampaigns = [];

        foreach ($campaigns as $campaign) {
            $matchedCampaign = $this->matchCampaignToContext($campaign, $context);

            if ($matchedCampaign !== null) {
                $matchedCampaigns[] = $matchedCampaign;
            }
        }

        usort(
            $matchedCampaigns,
            static function (array $left, array $right): int {
                return [
                    $left['apply_order'],
                    $left['campaign_priority'],
                    $left['target_priority'],
                    $left['id'],
                ] <=> [
                    $right['apply_order'],
                    $right['campaign_priority'],
                    $right['target_priority'],
                    $right['id'],
                ];
            }
        );

        return $matchedCampaigns;
    }

    private function getProductCategoryIds(string $productId): array
    {
        $sql = "
        SELECT categoria_id
        FROM categorias_productos
        WHERE producto_id = :product_id
          AND is_active = 1
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':product_id' => $productId,
        ]);

        return array_values(array_unique($stmt->fetchAll(PDO::FETCH_COLUMN)));
    }

    private function matchCampaignToContext(array $campaign, array $context): ?array
    {
        $matchedIncludes = [];
        $matchedExcludes = [];

        foreach ($campaign['targets'] as $target) {
            if (!$this->targetMatchesContext($target, $context)) {
                continue;
            }

            if ($target['match_mode'] === 'exclude') {
                $matchedExcludes[] = $target;
                continue;
            }

            $matchedIncludes[] = $target;
        }

        if ($matchedExcludes !== []) {
            return null;
        }

        if ($matchedIncludes === []) {
            return null;
        }

        usort(
            $matchedIncludes,
            static function (array $left, array $right): int {
                return [
                    $left['apply_order'],
                    $left['target_priority'],
                    $left['id'],
                ] <=> [
                    $right['apply_order'],
                    $right['target_priority'],
                    $right['id'],
                ];
            }
        );

        $primaryMatch = $matchedIncludes[0];

        $campaign['apply_order'] = $primaryMatch['apply_order'];
        $campaign['target_priority'] = $primaryMatch['target_priority'];
        $campaign['matched_targets'] = $matchedIncludes;

        unset($campaign['targets']);

        return $campaign;
    }

    private function targetMatchesContext(array $target, array $context): bool
    {
        return match ($target['target_type']) {
            'all_products' => true,
            'product' => $target['target_uuid'] === ($context['product_id'] ?? ''),
            'provider' => $target['target_uuid'] === ($context['proveedor_id'] ?? ''),
            'price_group' => $target['target_uuid'] === ($context['price_group_id'] ?? ''),
            'brand' => $target['target_uuid'] === ($context['marca_id'] ?? ''),
            'category' => in_array($target['target_uuid'], $context['category_ids'] ?? [], true),
            default => false,
        };
    }
}
