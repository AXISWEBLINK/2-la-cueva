# PriceEngine Specification v2

- PriceEngine es la unica fuente valida para resolver el precio final de un producto.
- Ningun modulo debe calcular precios por su cuenta; ventas, presupuestos, web, integraciones, APIs y futuros listados deben consultar siempre al motor.
- La entrada minima obligatoria es `product_id` y `price_list_id`.
- El motor arma un contexto con producto, proveedor, moneda, grupo de precio, lista activa, regla y override.
- El grupo de precio se resuelve por asignacion directa al producto, grupo default del proveedor o fallback del proveedor.
- La regla activa se obtiene desde `price_calculation_rules` usando `price_group_id` y `price_list_id`.
- El calculo normaliza el costo, aplica recargo base, margen comercial, ajuste e IVA de venta para obtener el precio bruto.
- Si existe un override activo y vigente, el precio final pasa a ser el manual configurado.
- Luego se evalúan campañas activas por producto, proveedor, grupo, marca, categoria o lista, respetando `apply_order`, prioridad, `stackable` y `stop_processing`.
- El redondeo depende solo de la lista de precios: `apply_rounding`, `rounding_mode`, `rounding_step` y `show_decimals`.
- La cotizacion de moneda puede mostrarse como referencia visual fuera del motor, pero no redefine el precio base de negocio.
- El motor siempre devuelve un `PriceResult`, nunca un numero suelto.
- Toda operacion de precio del sistema debe resolverse unicamente a traves de `PriceEngine::resolve(product_id, price_list_id)`.