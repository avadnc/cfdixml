# CHANGELOG CFDIXML FOR [DOLIBARR ERP CRM](https://www.dolibarr.org)

## 5.2
- FIX: Se corrige formato pdf complemento de pago, ahora se listan cuando se pagan varias facturas, y se corregijen sobreposiciones en impresion pdf
- FIX: Facturas en moneda extranjera: valores en XML erroneos cuando las partidas tienen descuneto 
- FIX: error al timbrar cuando las partidas tienen descuento
- FIX: se corrigen permisos para las descargas de pagos
- FIX: en la línea 468 se agregó $data[0]['pago']['MonedaP'] = $conf->currency;
- Se rehace por completo la función getpayments, se agregó un diferenciador para obtener los datos si el pago se realiza en la misma moneda que el doli o por lo contrario es distinta.

- BUG: Al refacturar, la factura original no se cancela en el SAT (codgo 207)

## 1.0

Initial version
