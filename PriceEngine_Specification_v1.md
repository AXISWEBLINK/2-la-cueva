# PriceEngine Specification v1.0

## Objetivo

PriceEngine es el único responsable de resolver el precio final de cualquier producto.

Ningún módulo del sistema (Ventas, Presupuestos, Web, Mercado Libre, API, etc.) debe calcular precios directamente.

Todos deben utilizar PriceEngine.

---

# Entrada

El motor recibe como mínimo:

- producto_id
- price_list_id

Opcionalmente podrá recibir en el futuro:

- cliente_id
- cantidad
- fecha
- sucursal
- vendedor

---

# Orden obligatorio del cálculo

## 1) Obtener el producto

Leer:

- productos

Obtener:

- regular_price
- proveedor_id
- price_group_id
- moneda_id

---

## 2) Obtener la regla de cálculo

Buscar en:

price_calculation_rules

usando:

- price_group_id
- price_list_id

Obtener:

- base_markup_percent
- vat_percent
- price_markup_percent
- adjustment_percent

---

## 3) Normalizar el costo

Si base_markup_percent es distinto de cero:

Costo Comercial =
Costo +
(base_markup_percent)

Ejemplo:

Costo = 10000

base_markup_percent = 21%

Costo Comercial = 12100

---

## 4) Aplicar margen comercial

Precio Neto =
Costo Comercial +
price_markup_percent

---

## 5) Aplicar ajuste

Si adjustment_percent es distinto de cero:

Precio Neto =
Precio Neto +
adjustment_percent

---

## 6) Aplicar IVA

Precio Bruto =
Precio Neto +
vat_percent

---

## 7) Buscar Override

Buscar en:

price_product_overrides

Si existe un override activo:

El Precio Bruto pasa a ser el Precio Manual.

El cálculo anterior NO se elimina.

Simplemente queda reemplazado.

---

## 8) Buscar campañas

Buscar campañas activas en:

price_campaigns

y

price_campaign_targets

Ordenar siempre por:

1. apply_order
2. priority

Aplicar únicamente campañas activas.

Si una campaña tiene:

stop_processing = 1

no continuar buscando campañas.

Si stackable = 0

no combinar con otras campañas.

---

## 9) Aplicar redondeo

Leer la configuración desde:

price_lists_name

Campos:

- apply_rounding
- rounding_mode
- rounding_step
- show_decimals

Nunca utilizar configuraciones de redondeo fuera de esta tabla.

---

## 10) Resultado

PriceEngine siempre devuelve un objeto.

Nunca devuelve solamente un número.

Debe contener como mínimo:

- cost_net
- cost_adjusted
- sale_net
- sale_vat
- sale_gross
- override
- campaigns
- final_price
- trace

---

# Price Trace

El motor debe ser capaz de explicar completamente cómo obtuvo un precio.

Ejemplo:

Costo................10000

IVA costo............21%

Costo Comercial......12100

Margen...............60%

Precio Neto..........16000

IVA Venta............3360

Precio Bruto.........19360

Override.............NO

Campaña..............Hot Sale

Redondeo.............Psychological

Precio Final.........17999

---

# Reglas

No utilizar IF por proveedor.

No utilizar IF por marca.

No utilizar IF por lista.

Toda decisión debe salir de las tablas del motor.

El único responsable del precio final es PriceEngine.

Fin de la especificación.