# Reporte de Optimización SEO - DameMiManga.cl

Este documento detalla los hallazgos técnicos tras analizar el sitio web [https://damemimanga.cl/](https://damemimanga.cl/) y ofrece recomendaciones específicas para optimizar su configuración de rastreo (Sitemaps y `robots.txt`) y sus Datos Estructurados (Schema), enfocándose en maximizar su visibilidad como tienda de cómics y mangas.

## 1. Análisis del Estado Actual

### 1.1 Sitemaps Rotos (PROBLEMA CRÍTICO 🚨)
El archivo `robots.txt` del sitio declara explícitamente dos sitemaps:
- `https://damemimanga.cl/sitemap_index.xml`
- `https://damemimanga.cl/sitio-web/sitemap.xml`

**Hallazgo:** Al intentar acceder a cualquiera de estas dos URLs, **ambas devuelven un error 404 (No Encontrado)**. Tampoco funciona el sitemap nativo de WordPress (`/wp-sitemap.xml`).
**Consecuencia:** Esto es un problema crítico de SEO técnico. Sin un sitemap funcional, Googlebot no puede descubrir eficientemente nuevos mangas, reposiciones o categorías, dependiendo únicamente de la navegación interna del sitio. Al desvincularnos de RankMath, el nuevo plugin deberá solucionar esto de raíz generando sitemaps nativos, rápidos y 100% funcionales.

### 1.2 Archivo `robots.txt`
Se encontraron reglas en el `robots.txt` que pueden perjudicar el rendimiento de rastreo:
```text
Allow: /*?add-to-cart=*
Allow: /*?vaciar-carrito=*
```
**Hallazgo:** Permitir explícitamente a los bots rastrear enlaces de acción (como añadir o vaciar el carrito) es una mala práctica. Malgasta el presupuesto de rastreo ("Crawl Budget") y puede generar múltiples URLs parametrizadas basura en el índice de Google. Además, los bots podrían estar "simulando" añadir productos al carrito constantemente, afectando el rendimiento del servidor.

### 1.3 Datos Estructurados (Schema)
**Hallazgo:** El sitio actualmente utiliza RankMath SEO Pro con un Schema en la página principal configurado como `BookStore` (Librería) y `Organization`. Sin embargo, al prescindir de RankMath, perderemos esta configuración. El nuevo plugin deberá replicar y mejorar esta estructura JSON-LD no solo para la portada, sino para todos los mangas individuales.

## 2. Recomendaciones de Optimización y Soluciones

Para solucionar estos problemas y llevar el SEO de DameMiManga al siguiente nivel, se recomiendan los siguientes pasos:

### Paso 1: Generación de Sitemaps Nativos (Reemplazo de RankMath)
Al eliminar RankMath, la principal prioridad del nuevo plugin será implementar un motor de sitemaps propio que evite el clásico error 404.
1. **Reglas de Reescritura:** El plugin usará `add_rewrite_rule()` en WordPress para capturar peticiones a `/sitemap_index.xml` y enrutarlas a una función PHP que genere el XML al vuelo.
2. **Generación Indexada:** Se crearán sub-sitemaps separados para productos, categorías, páginas y artículos (`sitemap-products-1.xml`, etc.).
3. **Caché Eficiente:** Para evitar sobrecargar el servidor de DameMiManga al consultar miles de mangas, el XML debe almacenarse mediante Transients de WordPress, renovándose automáticamente al actualizar o crear un producto.

### Paso 2: Limpieza y Control del `robots.txt`
El nuevo plugin tomará el control del archivo `robots.txt` utilizando el hook nativo `do_robotstxt` de WordPress para inyectar dinámicamente las reglas correctas y apuntar al nuevo Sitemap Index:

```text
User-agent: *
Disallow: /wp-admin/
Allow: /wp-admin/admin-ajax.php
Disallow: /wp-content/uploads/wc-logs/
Disallow: /wp-content/uploads/woocommerce_transient_files/
Disallow: /wp-content/uploads/woocommerce_uploads/

# Bloquear acciones del carrito a los bots
Disallow: /*?add-to-cart=*
Disallow: /*?vaciar-carrito=*

# Único Sitemap válido
Sitemap: https://damemimanga.cl/sitemap_index.xml
```
*(Nota: Mantener las reglas de Cloudflare que aparecen al principio del archivo).*

### Paso 3: Motor de Datos Estructurados (Schema) Propio
Dado que dejaremos de usar RankMath, el nuevo plugin asume la responsabilidad total de generar el SEO técnico. Se debe inyectar en el `<head>` (vía hook `wp_head`) un JSON-LD optimizado para cada manga.

**Propiedades a inyectar (Schema Dual `Product` + `ComicIssue`):**
- **ISBN / GTIN:** Mapear el atributo donde guardan el código de barras del manga a `isbn` o `gtin13`. Es vital para aparecer en Google Shopping y comparadores de precios.
- **Autor / Mangaka:** Mapear el atributo "Autor" a la propiedad `author` del Schema.
- **Editorial:** Mapear el atributo o taxonomía de la editorial (ej. Ivrea, Panini) a la propiedad `publisher`.
- **Formato:** Añadir estáticamente `"bookFormat": "GraphicNovel"`.

### Paso 4: Sitemaps de Taxonomías Personalizadas
El nuevo plugin de Sitemaps debe programarse para consultar las taxonomías propias que use DameMiManga (ej. "Autor", "Editorial", "Demografía"). El código deberá mapear estas taxonomías de WooCommerce y generar automáticamente archivos como `sitemap-tax-editorial.xml`, permitiendo a Google indexar páginas agrupadas que son vitales para la búsqueda de mangas.
