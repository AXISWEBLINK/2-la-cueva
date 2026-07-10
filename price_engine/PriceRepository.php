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

        p.id                          AS product_id,
        p.sku,
        p.name,
        p.regular_price,
        p.proveedor_id,
        p.price_group_id,
        p.moneda,

        pr.nombre                     AS provider_name,

        pg.group_code,
        pg.nombre                     AS group_name,

        pl.id                         AS price_list_id,
        pl.code                       AS price_list_code,
        pl.name                       AS price_list_name,
        pl.channel,

        pl.apply_rounding,
        pl.rounding_mode,
        pl.rounding_step,
        pl.show_decimals,
        pl.prices_include_vat,

        r.id                          AS rule_id,

        r.base_markup_percent,
        r.vat_percent,
        r.price_markup_percent,
        r.adjustment_percent,

        o.id                          AS override_id,

        o.manual_price_net,
        o.manual_price_gross,

        o.starts_at                   AS override_starts_at,
        o.ends_at                     AS override_ends_at,

        o.is_active                   AS override_active

    FROM productos p

    INNER JOIN proveedores pr
        ON pr.id = p.proveedor_id

    INNER JOIN price_provider_groups pg
        ON pg.id = p.price_group_id

    INNER JOIN price_calculation_rules r
        ON r.price_group_id = p.price_group_id

    INNER JOIN price_lists_name pl
        ON pl.id = r.price_list_id

    LEFT JOIN price_product_overrides o
        ON o.producto_id = p.id
       AND o.price_list_id = pl.id
       AND o.is_active = 1

    WHERE
        p.id = :product_id

    AND
        pl.id = :price_list_id

    LIMIT 1
    ";

    $stmt = $this->pdo->prepare($sql);

    $stmt->execute([
        ':product_id'    => $productId,
        ':price_list_id' => $priceListId
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: [];
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