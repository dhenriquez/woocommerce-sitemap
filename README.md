# WooCommerce Sitemap & SEO Plugin - Guía de Desarrollo

Este documento resume los requerimientos técnicos y las mejores prácticas basadas en la documentación oficial de [Google Search Central](https://developers.google.com/search/docs) para desarrollar un plugin de sitemaps y SEO enfocado específicamente en tiendas online creadas con WooCommerce.

## 1. Visión General y Objetivos
El objetivo principal del plugin es servir como un **reemplazo completo a soluciones genéricas de SEO (como RankMath o Yoast)** para una tienda de cómics basada en WooCommerce. Garantizará que los productos, categorías y páginas sean descubiertos, rastreados e indexados correctamente por Google, asumiendo el control total sobre la generación de Sitemaps, el archivo `robots.txt` y la inyección de Datos Estructurados (JSON-LD).

## 2. Requerimientos de Sitemaps según Google
De acuerdo con la documentación de rastreo e indexación de Google, el plugin debe implementar lo siguiente:

### Especificaciones Técnicas Básicas
- **Formato:** Los sitemaps deben usar el formato XML basado en el estándar `sitemaps.org`.
- **Límites:** Un archivo sitemap no puede contener más de **50,000 URLs** ni pesar más de **50 MB** (sin comprimir).
- **Índice de Sitemaps (Sitemap Index):** Dado que una tienda WooCommerce puede crecer significativamente, el plugin debe generar un "Sitemap Index" (ej. `sitemap_index.xml`) que enlace a sitemaps secundarios organizados por contenido.
- **Tipos de Sitemaps requeridos:**
  - `sitemap-products.xml` (Productos de WooCommerce)
  - `sitemap-product-categories.xml` (Categorías de productos)
  - `sitemap-product-tags.xml` (Etiquetas de productos)
  - `sitemap-pages.xml` (Páginas de WordPress)
  - `sitemap-posts.xml` (Artículos de blog)
  - **Sitemaps de Taxonomías Personalizadas (Especial Cómics):** Tiendas de cómics suelen utilizar taxonomías como `Editorial` (Marvel, DC), `Personajes` (Batman, Spider-Man), `Guionistas` y `Dibujantes`. El plugin debe ser capaz de detectar estas taxonomías personalizadas y generar sus respectivos sitemaps (`sitemap-tax-publisher.xml`, `sitemap-tax-characters.xml`, etc.).

### Optimización de Medios (Imágenes y Videos)
- **Sitemaps de Imágenes:** Google recomienda encarecidamente incluir información de imágenes para productos e-commerce. El sitemap de productos debe utilizar las etiquetas de extensión `<image:image>` y `<image:loc>` para las imágenes destacadas y las galerías de los productos.
- **Sitemaps de Video:** Si los productos contienen videos, deben incluirse mediante la extensión `<video:video>`.

### Metadatos del XML
- Atributos requeridos: `<loc>` (URL absoluta).
- Atributos recomendados: `<lastmod>` (Fecha de última modificación en formato W3C Datetime, crucial para que Google sepa si el contenido ha cambiado y debe volver a rastrearlo).
- *Nota:* Google actualmente ignora `<priority>` y `<changefreq>`, por lo que su inclusión es opcional pero no aporta valor directo al posicionamiento.

## 3. Optimización Específica para Ecommerce (WooCommerce)
Basado en la sección "Site-specific guides: Ecommerce" de Google Search Docs:

### Gestión de URLs y Canonicalización
- Los e-commerces suelen generar contenido duplicado a través de variables de URL (ej. filtros de tallas, colores o paginación: `?color=rojo`, `?orderby=price`).
- El plugin debe asegurar que las URLs incluidas en el sitemap sean las **versiones canónicas** de los productos (limpias, sin parámetros de sesión o filtros temporales).

### Datos Estructurados (Structured Data - JSON-LD)
Para mejorar la apariencia en los resultados de búsqueda ("Rich Results" o Fragmentos Enriquecidos), el plugin debería poder inyectar código JSON-LD en el `<head>`.

**Para productos generales:**
- Usar esquema `Product` con propiedades clave: `name`, `image`, `description`, `sku`, `brand`, y sobre todo el nodo `offers` que incluya `price`, `priceCurrency` y `availability` (InStock / OutOfStock).
- También se deben incluir esquemas para `Review` y `AggregateRating` (reseñas y puntuaciones).

**Particularidades para una Tienda de Cómics:**
Para maximizar la visibilidad de los cómics en Google, el schema debe ampliarse para dar contexto sobre la obra.
- **Tipo Dual (`@type`):** Se recomienda usar `["Product", "ComicIssue"]` (o `Book`). Esto informa a Google de que se vende un producto que, a su vez, es una publicación gráfica.
- **Propiedades Clave para Cómics:**
  - `isbn`, `gtin13` o `upc`: El código de barras es vital. Los cómics y tomos (TPB) usan códigos EAN/ISBN que Google utiliza para agrupar ediciones en Google Shopping y Search.
  - `publisher`: Editorial (ej. Marvel Comics, DC Comics, ECC Ediciones).
  - `author`: Guionista principal (tipo `Person`).
  - `illustrator`: Dibujante o portadista (tipo `Person`).
  - `bookFormat`: Formato de la publicación (ej. `GraphicNovel`, `Paperback` o `Hardcover`).
  - `issueNumber`: El número de grapa, volumen o tomo dentro de la colección.
  - `isPartOf`: Nodo para relacionar el producto con la serie principal (tipo `ComicSeries`), vital para que Google entienda el orden de lectura.

### Páginas Fuera de Índice (Noindex)
- El plugin debe excluir automáticamente del sitemap aquellas páginas que WooCommerce considera privadas o que no aportan valor SEO, como:
  - Páginas del Carrito (`/cart`)
  - Checkout (`/checkout`)
  - Mi Cuenta (`/my-account`)
  - Resultados de búsqueda interna (`/?s=`)

## 4. Arquitectura y Características del Plugin

Para implementar todo esto en WordPress/WooCommerce, el plugin debe contar con las siguientes funcionalidades:

### 1. Generación Dinámica y Endpoints
- No generar archivos físicos en el servidor para evitar problemas de permisos o desincronización. En su lugar, utilizar la API de reescritura de WordPress (`WP_Rewrite` o `add_rewrite_rule()`) para capturar URLs como `/sitemap.xml` y renderizar el contenido XML al vuelo.

### 2. Sistema de Caché (Transients)
- Como las tiendas grandes pueden tardar mucho en consultar todos los productos, el XML generado debe almacenarse en caché utilizando la API de Transients de WordPress.
- **Invalidación de caché:** Los transients deben purgarse automáticamente usando hooks de WooCommerce cuando se añade, actualiza o elimina un producto (ej. `save_post_product`, `woocommerce_update_product`).

### 3. Pings a los Motores de Búsqueda
- Cuando el sitemap cambie (por ejemplo, al publicar un nuevo producto), el plugin debería hacer "ping" automáticamente a Google mediante una solicitud HTTP a:
  `https://www.google.com/ping?sitemap=URL_DEL_SITEMAP`

### 4. Integración con `robots.txt`
- Usar el hook `do_robotstxt` de WordPress para añadir dinámicamente la directiva al archivo robots.txt virtual de la tienda:
  ```text
  Sitemap: https://www.tusitio.com/sitemap_index.xml
  ```

### 5. Interfaz de Administración (UI)
El plugin debe incluir una página de ajustes en el panel de WordPress (`wp-admin`) que permita al usuario:
- Habilitar o deshabilitar sitemaps específicos (ej. apagar sitemap de etiquetas).
- Excluir IDs de productos o categorías concretas.
- Seleccionar si incluir o excluir las imágenes del sitemap de productos.
- Ver la URL de su Sitemap Index para poder enviarlo a Google Search Console.

## 5. Próximos Pasos para el Desarrollo
1. **Configurar el esqueleto del plugin:** Archivo principal con los headers de WordPress.
2. **Definir las rutas (Rewrites):** Configurar `add_rewrite_rule` y los `query_vars` para servir los XML.
3. **Controladores de Sitemaps:** Crear las consultas a la base de datos de WordPress (`WP_Query`) optimizadas para obtener los CPT (Custom Post Types) `product`, `page`, `post` y las taxonomías `product_cat`.
4. **Generador XML:** Clase encargada de ensamblar las etiquetas XML respetando la codificación UTF-8 y escapando caracteres especiales (`esc_url()`).
5. **Panel de Opciones:** Usar la Settings API de WordPress para la configuración.