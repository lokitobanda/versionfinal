# TP6 – Normalización de la base de datos de SKYGAMES

Sistema: tienda web de videojuegos con catálogo de productos, usuarios con roles
y formulario de consultas.

## 1. Entidad principal sin normalizar

Tabla única `catalogo_bruto`, tal como se podría haber cargado en una planilla:

| id | titulo | categoria | desc_categoria | plataformas | vendedor | email_vendedor | telefonos_vendedor | rol_vendedor | precio | estado |
|----|--------|-----------|----------------|-------------|----------|----------------|--------------------|--------------|--------|--------|
| 1 | Elden Ring | Rol | Juegos de rol y aventura | PS5, Xbox, PC | Ana Pérez | ana@sky.com | 1122334455, 1166778899 | admin | 59.99 | Activo |
| 2 | FIFA 26 | Deportes | Simuladores deportivos | PS5, PC | Ana Pérez | ana@sky.com | 1122334455, 1166778899 | admin | 69.99 | Activo |
| 3 | Mario Kart | Carreras | Juegos de conducción | Switch | Luis Gómez | luis@sky.com | 1100112233 | usuario | 49.99 | Agotado |

Problemas detectados:

- **Campos multivalorados:** `plataformas` y `telefonos_vendedor` guardan listas en un solo campo.
- **Redundancia:** los datos de Ana Pérez y la descripción de cada categoría se repiten en cada fila.
- **Anomalías:** si cambia el email de Ana hay que modificar varias filas (actualización);
  si se borra el único juego de una categoría se pierde la categoría (borrado).

## 2. Proceso de normalización

### 1FN – valores atómicos, sin grupos repetitivos

Se separa cada valor de las listas en su propia fila. Clave primaria compuesta: (`id_producto`, `plataforma`, `telefono`).

| id_producto | titulo | categoria | desc_categoria | plataforma | vendedor | email_vendedor | telefono | rol_vendedor | precio | estado |
|---|---|---|---|---|---|---|---|---|---|---|
| 1 | Elden Ring | Rol | Juegos de rol y aventura | PS5 | Ana Pérez | ana@sky.com | 1122334455 | admin | 59.99 | Activo |
| 1 | Elden Ring | Rol | Juegos de rol y aventura | PS5 | Ana Pérez | ana@sky.com | 1166778899 | admin | 59.99 | Activo |
| 1 | Elden Ring | Rol | Juegos de rol y aventura | Xbox | Ana Pérez | ana@sky.com | 1122334455 | admin | 59.99 | Activo |
| … | … | … | … | … | … | … | … | … | … | … |

Todos los campos son atómicos, pero hay muchísima repetición: aparecen dependencias parciales.

### 2FN – sin dependencias parciales de la clave

Con la clave compuesta (`id_producto`, `plataforma`, `telefono`):

- `titulo`, `categoria`, `precio`, `estado`, `vendedor` dependen **solo** de `id_producto`.
- `email_vendedor` y `rol_vendedor` dependen del vendedor, no de la plataforma.
- `telefono` depende del vendedor, no del producto.

Se descompone en:

- `productos(id_producto, titulo, categoria, desc_categoria, precio, estado, vendedor, email, rol)`
- `producto_plataforma(id_producto, plataforma)` → relación N:M
- `usuarios` (vendedor) con sus datos y teléfono

### 3FN – sin dependencias transitivas

Atributos no clave que dependen de otro atributo no clave:

- `desc_categoria` depende de `categoria` (no de `id_producto`) → tabla `categorias`.
- `estado` de la consulta es un catálogo de valores → tabla `estados_consulta`; en productos se reduce a un indicador `activo`.
- `rol` es un catálogo → tabla `roles`.
- `plataforma` es una entidad con nombre propio → tabla `plataformas`.
- `email` y `nombre` del vendedor dependen del usuario, no del producto → quedan solo en `usuarios`.

## 3. Listado de tablas resultante

| Tabla | PK | FK |
|---|---|---|
| `roles` | `id` | – |
| `usuarios` | `id` | `rol_id` → `roles.id` |
| `categorias` | `id` | – |
| `plataformas` | `id` | – |
| `productos` | `id` | `categoria_id` → `categorias.id` |
| `producto_plataforma` (N:M) | (`producto_id`, `plataforma_id`) | `producto_id` → `productos.id`; `plataforma_id` → `plataformas.id` |
| `estados_consulta` | `id` | – |
| `consultas` | `id` | `usuario_id` → `usuarios.id` (opcional); `categoria_id` → `categorias.id`; `estado_id` → `estados_consulta.id` |

Nota: los teléfonos múltiples se simplificaron a un único `telefono` opcional por usuario,
porque el sistema no los utiliza; de ser necesario sería una tabla `telefonos_usuario`.

### Diagrama (texto)

```
roles 1───N usuarios 1───N consultas N───1 estados_consulta
                                │
categorias 1───N productos      └──N───1 categorias
                    │
                    N
           producto_plataforma
                    N
                    │
              plataformas 1
```
