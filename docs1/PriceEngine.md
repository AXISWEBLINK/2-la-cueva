# PriceEngine v1.0

## Objetivo

PriceEngine es el único componente autorizado para resolver el precio de un producto.

Ningún otro módulo (Ventas, Presupuestos, Caja, Web, Mercado Libre, API, etc.) debe calcular precios.

Todos los módulos deben solicitar el precio al PriceEngine.

------------------------------------------------------------

ENTRADA

- producto_id
- price_list_id

Futuro:

- cliente_id
- fecha
- cantidad
- sucursal

------------------------------------------------------------

PASO 1

Leer el producto.

Obtener:

- regular_price
- proveedor_id
- price_group_id
- moneda

------------------------------------------------------------

PASO 2

Buscar la regla de cálculo utilizando:

price_group_id
+
price_list_id

Tabla:

price_calculation_rules

------------------------------------------------------------

PASO 3

Normalizar costo

Costo Comercial =
Costo +
base_markup_percent

Ejemplo

Costo
10000

+

21%

=

12100

------------------------------------------------------------

PASO 4

Aplicar margen

Precio Neto =
Costo Comercial
+
price_markup_percent

------------------------------------------------------------

PASO 5

Aplicar ajuste

Si adjustment_percent <> 0

Aplicar.

------------------------------------------------------------

PASO 6

Aplicar IVA

Precio Bruto =
Precio Neto
+
vat_percent

------------------------------------------------------------

PASO 7

Buscar Override

Tabla:

price_product_overrides

Si existe un override activo:

Utilizar el precio manual.

No modificar el cálculo.

Simplemente reemplazar el precio bruto.

------------------------------------------------------------

PASO 8

Buscar campañas

Tablas

price_campaigns

price_campaign_targets

Orden:

apply_order

priority

Respetar:

stackable

stop_processing

------------------------------------------------------------

PASO 9

Aplicar redondeo

Utilizar únicamente la configuración de:

price_lists_name

Nunca redondear utilizando reglas del proveedor.

------------------------------------------------------------

SALIDA

PriceEngine devuelve un objeto.

Nunca devuelve solamente un número.

Debe devolver:

- costo
- costo comercial
- precio neto
- iva
- precio bruto
- override
- campañas aplicadas
- precio final

------------------------------------------------------------

El cálculo de precios nunca debe realizarse fuera de PriceEngine.