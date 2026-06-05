# Especificación Técnica: Plugin de Optimización SEO para Tienda de Mangas (WooCommerce)

## 1. Objetivo
Desarrollar un plugin para WordPress/WooCommerce que automatice la gestión de **Sitemaps XML** y la implementación de **Datos Estructurados (JSON-LD)**, garantizando la indexación eficiente y el posicionamiento en *rich snippets* para una tienda de cómics y mangas en Chile.

## 2. Requerimientos de Sitemap XML
El plugin debe generar un sitemap dinámico y eficiente para optimizar el *crawl budget*.

* **Arquitectura:** Generar un `sitemap_index.xml` que segmente automáticamente el contenido:
    * `sitemap-products.xml`: Solo productos.
    * `sitemap-product-categories.xml`: Categorías/Colecciones.
    * `sitemap-posts.xml`: Blog/Noticias.
* **Limpieza de URLs:**
    * Excluir obligatoriamente: `/carrito/`, `/finalizar-compra/`, `/mi-cuenta/`, páginas de filtros de atributos (`?filter_...`), y variaciones de producto que no tengan página propia.
* **Rendimiento:**
    * Implementar caché de sitemap con expiración automática (o exclusión de caché en plugins como WP Rocket/LiteSpeed).
    * Límite de 5,000 URLs por sub-sitemap para asegurar carga rápida.
* **Formato:** Cumplir estrictamente con el estándar de Google (codificación UTF-8, declaración XML válida).

## 3. Marcado Schema (JSON-LD) para Productos
Inyección automática de datos estructurados para cada producto (manga/cómic):

* **Esquema `Product` (JSON-LD):**
    * `name`: Nombre completo del manga.
    * `image`: URL de imagen destacada (alta resolución).
    * `sku`: SKU del producto.
    * `brand`: Editorial (debe extraerse del atributo de producto).
    * `aggregateRating`: Inyección dinámica basada en reseñas de clientes de WooCommerce (estrellas).
    * `offers`:
        * `price`: Precio actual.
        * `priceCurrency`: "CLP" (Moneda local chilena).
        * `availability`: `https://schema.org/InStock` o `OutOfStock`.
* **Esquema `BreadcrumbList`:** * Implementación obligatoria para reflejar la jerarquía de categorías y mejorar el posicionamiento.

## 4. Gestión de Contenido Duplicado
* **Canonicals:** El plugin debe asegurar que cualquier URL con parámetros (ej. `?attribute_edicion=coleccionista`) contenga una etiqueta `rel="canonical"` apuntando a la URL del producto base.
* **Noindex:** Capacidad de marcar taxones o páginas de resultados de búsqueda como `noindex, nofollow` desde la configuración.

## 5. Especificaciones para el Desarrollador
* **Modularidad:** Uso de *Hooks* (actions/filters) de WordPress y WooCommerce para evitar modificaciones directas en el core.
* **Configuración:** Panel de control en el escritorio de WordPress:
    * Activación/desactivación de módulos (Sitemap / Schema).
    * Selector de tipos de post para incluir en sitemap.
* **Validación técnica:**
    * El plugin debe pasar las pruebas de:
        * [Google Rich Results Test](https://search.google.com/test/rich-results) (sin errores).
        * [Schema Markup Validator](https://validator.schema.org/).
        * Google Search Console (sin errores de "Couldn't fetch").

## 6. Checklist de Entrega
- [ ] Sitemap generado y accesible en `midominio.cl/sitemap_index.xml`.
- [ ] Schema `Product` renderizado en el código fuente de las páginas de producto.
- [ ] Moneda `CLP` configurada correctamente en las ofertas.
- [ ] Exclusión de páginas innecesarias del sitemap funcional.
- [ ] Documentación técnica básica de los filtros usados.