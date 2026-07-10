# Coding Rules

No utilizar IF por proveedor.

No utilizar IF por marca.

No utilizar IF por categoría.

No utilizar IF por lista.

No calcular precios dentro de controllers.

No calcular precios dentro de views.

No duplicar fórmulas.

Todo cálculo debe realizarse utilizando PriceEngine.

Nunca consultar tablas de precios directamente desde módulos de ventas.

Siempre utilizar PriceEngine.

No guardar precios calculados en productos.

Los únicos precios persistentes permitidos son:

- regular_price

- manual_price (price_product_overrides)

Todo lo demás debe calcularse dinámicamente.