# F2.2 — Matriz QA y seguridad de identidad visual

## Alcance

Revisión estática del estado de trabajo del 14 de octubre de 2026 para:

- colores configurables y contraste;
- prevención de inyección CSS;
- carga, reemplazo y retiro de logo, icono y favicon;
- caché e invalidación;
- exposición pública de recursos de marca.

Este documento es una especificación de verificación. No certifica todavía la implementación, porque F2.2 se encuentra en desarrollo concurrente.

## Estado observado

- Los colores aceptan únicamente `#RRGGBB` y se normalizan a mayúsculas.
- El primario se valida con texto blanco y el secundario con texto negro, ambos a razón mínima 4.5:1.
- Logo: JPG, JPEG, PNG o WebP, hasta 4 MB.
- Icono: PNG o WebP, hasta 1 MB.
- Favicon: PNG o ICO, hasta 512 KB.
- SVG no está permitido, lo cual reduce el riesgo de contenido activo.
- Los recursos se almacenan en el disco público y se generan nombres no controlados directamente por el usuario.
- El reemplazo persiste primero el archivo nuevo y después intenta borrar el anterior.
- La eliminación física se restringe a rutas relativas bajo `branding/` o `settings/` y rechaza `..`.
- Existe un contexto tipado de marca, pero todavía permanecen consumidores con consultas directas a `Setting` y colores codificados en vistas.
- No se observó una capa de caché de marca activa en el momento de esta revisión.

## Riesgos priorizados

| Prioridad | Riesgo | Impacto | Control o prueba exigida |
|---|---|---|---|
| Alta | Aplicación parcial al actualizar varios recursos | Puede quedar logo nuevo, icono anterior y configuración incompleta si falla una operación intermedia | Diseñar operación compensable o transaccional; probar fallo en cada paso y verificar estado coherente |
| Alta | Archivo válido por MIME pero peligroso al decodificar | Imágenes enormes/comprimidas pueden agotar memoria; archivos políglotas pueden servirse de forma insegura | Decodificar imagen, limitar dimensiones/píxeles, re-encodear formatos raster y servir con `nosniff` |
| Alta | Contraste validado solo contra un color de texto fijo | Un token puede pasar el guardado y fallar en enlaces, hover, focus, deshabilitado, gradientes o componentes que usan otro foreground | Inventariar combinaciones reales y comprobar estados normales e interactivos según WCAG 2.2 AA |
| Alta | Datos antiguos o alterados eluden validación y llegan a CSS | La regex protege el formulario actual, pero seeders, migraciones o cambios directos podrían inyectar una declaración CSS | Validar también al construir el contexto; usar allowlist y fallback antes de interpolar en `<style>` |
| Alta | Ruta persistida manipulada se expone públicamente | Un valor inesperado puede producir URLs fuera del espacio lógico de marca o referenciar recursos no gestionados | Normalizar al leer y aceptar únicamente rutas relativas bajo el prefijo administrado |
| Media | Recursos retirados permanecen en CDN, navegador o proxy | Se sigue mostrando marca anterior y puede haber confusión contractual | URLs versionadas/content-hash, invalidación documentada y prueba con `Cache-Control` |
| Media | Reemplazo borra el archivo anterior usado por respuestas en vuelo | Peticiones concurrentes pueden recibir 404 | Publicar URL nueva antes del retiro; período de gracia o nombres versionados |
| Media | Fallo al borrar deja archivos huérfanos | Acumulación y exposición histórica de logos | Registrar fallo, job de limpieza restringido y prueba de reconciliación |
| Media | Exposición pública revela metadatos de imagen | EXIF puede contener ubicación, autor o software | Re-encodear y eliminar metadatos antes de publicar |
| Media | Favicon ICO insuficientemente inspeccionado | Contenedor inválido o inesperado puede provocar comportamiento inconsistente | Validar estructura ICO real y dimensiones admitidas; preferir generación controlada desde PNG |
| Media | Nombre de empresa o rutas se usan en atributos sin contexto homogéneo | Riesgo XSS si algún consumidor usa salida sin escapar | Verificar escape HTML en título, `alt`, metadatos y JSON; prohibir `{!! !!}` para valores de marca |
| Media | Recursos públicos se mezclan con archivos privados | Certificados u otros archivos podrían terminar accesibles por configuración errónea | Discos y prefijos separados; prueba negativa de acceso a `storage/app/private` y SUNAT |
| Baja | Imagen transparente o monocroma desaparece sobre ciertos fondos | Marca ilegible aunque los tokens de color pasen contraste | Vista previa sobre fondos claro/oscuro y fallback visual |
| Baja | Ausencia de dimensiones/aspecto recomendados | Deformación, salto de layout y mala experiencia móvil | Reglas y preview para logo horizontal/cuadrado, icono 1:1 y favicon cuadrado |

## Matriz de pruebas automatizadas

### Colores e inyección CSS

| ID | Caso | Resultado esperado |
|---|---|---|
| COL-01 | `#1F2937` y `#F59E0B` válidos | Guarda valores normalizados y los recupera desde el contexto |
| COL-02 | minúsculas válidas | Persiste en mayúsculas sin alterar el valor visual |
| COL-03 | forma corta `#FFF`, alfa `#FFFFFFFF`, nombre `red` | Rechazo de validación |
| COL-04 | `#000000; background:url(...)` | Rechazo y cero persistencia |
| COL-05 | saltos de línea, comillas, `var()`, `url()`, `expression()` | Rechazo y respuesta sin reflejar carga activa |
| COL-06 | primario con ratio 4.49:1 frente a blanco | Rechazo |
| COL-07 | primario con ratio 4.50:1 o superior frente a blanco | Aceptación, considerando tolerancia numérica documentada |
| COL-08 | secundario con ratio 4.49/4.50 frente a negro | Rechazo/aceptación en el límite correspondiente |
| COL-09 | valor inválido introducido directamente en BD | Contexto usa fallback seguro y nunca lo interpola como CSS |
| COL-10 | actualización por usuario sin `settings.manage` | 403 y valores intactos |
| COL-11 | token aplicado a login, layout y carta | Mismo valor efectivo, sin consultas directas ni divergencias |
| COL-12 | hover, focus, active, disabled y mensajes | Todos los pares de texto/fondo alcanzan AA y el foco sigue visible |

### Recursos de marca

| ID | Caso | Resultado esperado |
|---|---|---|
| IMG-01 | Carga válida de cada formato permitido | Se almacena bajo prefijo administrado, con nombre generado y URL funcional |
| IMG-02 | Extensión permitida con contenido que no es imagen | Rechazo; no queda archivo temporal o persistente |
| IMG-03 | SVG, HTML, JavaScript, PHP o doble extensión | Rechazo independiente del nombre y `Content-Type` declarado |
| IMG-04 | Archivo sobre el límite | Rechazo y configuración anterior intacta |
| IMG-05 | Imagen con dimensiones/píxeles excesivos | Rechazo antes de consumir memoria no acotada |
| IMG-06 | Imagen truncada o corrupta | Rechazo; no se publica |
| IMG-07 | Imagen con EXIF/GPS | Publicación sin metadatos sensibles tras re-encodeado |
| IMG-08 | Reemplazo exitoso | Contexto apunta al nuevo; anterior se retira de forma controlada |
| IMG-09 | Falla al guardar archivo nuevo | Valor y archivo anterior permanecen; no hay residuos |
| IMG-10 | Falla al persistir configuración | Archivo nuevo se elimina; anterior permanece disponible |
| IMG-11 | Falla al borrar anterior | Nuevo permanece activo, se registra limpieza pendiente y no falla toda la solicitud |
| IMG-12 | Subir archivo y marcar retiro simultáneamente | Semántica definida y probada; recomendación: reemplazo prevalece con aviso claro |
| IMG-13 | Retiro sin archivo existente | Operación idempotente, sin error y con fallback visible |
| IMG-14 | Ruta anterior contiene `../`, URL absoluta o prefijo ajeno | Nunca se elimina ni se sirve como recurso administrado |
| IMG-15 | Reemplazo de logo, icono y favicon con fallo en el segundo | No queda actualización parcial silenciosa |
| IMG-16 | Dos administradores actualizan simultáneamente | Política definida; no se elimina el recurso ganador ni queda referencia rota |

### Caché y exposición pública

| ID | Caso | Resultado esperado |
|---|---|---|
| PUB-01 | Usuario anónimo abre carta/landing | Solo recibe nombre, colores y recursos declarados públicos |
| PUB-02 | Petición directa a certificado SUNAT, backup o almacenamiento privado | 404/403; nunca se resuelve mediante el enlace público de storage |
| PUB-03 | Recurso de marca servido | `Content-Type` correcto, `X-Content-Type-Options: nosniff` y política de caché definida |
| PUB-04 | Cambiar o retirar un recurso con caché caliente | La siguiente navegación obtiene contexto nuevo; la URL anterior expira según política |
| PUB-05 | Cambiar solo un color | Se invalida contexto/tokens sin purgar datos no relacionados |
| PUB-06 | Caché vacía, caída de BD o recurso faltante | Fallback seguro; no se muestra traza ni ruta interna |
| PUB-07 | Distintos procesos PHP/colas | Invalidación coherente entre procesos, no solo memoria local |
| PUB-08 | Respuesta pública y panel autenticado | No se filtran correo interno, secretos, rutas físicas ni claves de configuración |
| PUB-09 | URL de recurso en HTML y metadatos sociales | URL canónica HTTPS en producción y escape contextual correcto |
| PUB-10 | Solicitud a nombre antiguo tras reemplazo | Comportamiento documentado: período de gracia o 404 sin revelar información |

## Verificación manual de contraste y UX

Ejecutar en 320, 360, 768, 1366 y 1920 px, además de zoom al 200 %:

1. Vista previa y pantalla guardada deben coincidir.
2. Logo horizontal y cuadrado no deben deformarse ni causar salto de layout.
3. Logo transparente debe conservarse visible sobre superficies claras y oscuras.
4. Si el recurso se retira o falla, debe aparecer fallback con nombre o icono neutro y texto alternativo útil.
5. El selector de color debe conservar un campo textual validable y errores asociados a su etiqueta.
6. El color no debe ser el único indicador de selección, error, estado o foco.
7. Navegación, botones, enlaces, badges, alertas y foco deben verificarse en todos sus estados.
8. La administración usa la marca como acento; los flujos POS/cocina/caja mantienen superficies neutrales y legibles.

## Política recomendada de caché

- Cachear el `BrandContext` con una clave versionada y un tag o mecanismo equivalente.
- Invalidar después de una persistencia completa y también al retirar recursos.
- No almacenar objetos `UploadedFile`, rutas físicas ni URLs dependientes del host en la caché.
- Usar nombres de archivo con contenido/hash o versión para permitir caché pública prolongada.
- Mantener HTML/configuración con TTL corto o invalidación inmediata.
- No reutilizar la misma URL para bytes distintos; evita favicons y logos obsoletos.
- Si se usa CDN, documentar purga y probarla; el borrado local no invalida automáticamente el borde.

## Criterios de aceptación de F2.2

- [ ] Toda la matriz `COL-*`, `IMG-*` y `PUB-*` prioritaria está automatizada o cuenta con evidencia manual justificada.
- [ ] Ningún valor de marca no validado llega a un contexto CSS, HTML, URL o JSON.
- [ ] Contraste AA comprobado sobre cada combinación y estado real, no solo sobre dos colores aislados.
- [ ] Logo, icono y favicon poseen reglas de formato, tamaño, dimensiones y fallback.
- [ ] Reemplazo y retiro son idempotentes, resistentes a fallos y no borran rutas ajenas.
- [ ] No quedan consultas directas de marca en Blade/controladores objetivo; todos consumen el contexto compartido.
- [ ] La invalidación funciona entre procesos y evita identidad obsoleta.
- [ ] Los recursos públicos no permiten acceder a secretos, certificados, backups o archivos privados.
- [ ] Encabezados de seguridad y caché están verificados en el servidor usado en producción.
- [ ] Pruebas responsive, zoom, teclado y lector de pantalla completadas para configuración, login, layout y carta.

## Decisiones que deben quedar explícitas

Antes de cerrar F2.2, el equipo debe registrar:

- dimensiones mínimas/máximas y proporción para cada recurso;
- si las imágenes se re-encodean y qué metadatos se eliminan;
- precedencia entre “subir” y “retirar” en una misma solicitud;
- estrategia ante actualización parcial de varios recursos;
- TTL, versionado, período de gracia y purga CDN;
- fallback visual para cada recurso ausente;
- inventario exacto de componentes y pares de contraste que usan cada token.
