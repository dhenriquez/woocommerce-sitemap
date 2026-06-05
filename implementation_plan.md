# Plan de Implementación: DameMiManga SEO & Sitemap Plugin

Basado en la revisión de los documentos `README.md`, `readme_damemimanga.md` y `plugin.md`, a continuación se presenta el plan técnico detallado para el desarrollo desde cero del plugin de optimización SEO diseñado específicamente para la tienda de cómics.

## Preguntas Abiertas (Por favor responder antes de programar)
> [!IMPORTANT]
> 1. **Atributos de WooCommerce:** Para inyectar el Schema correctamente, ¿tienes la "Editorial" y el "Autor/Mangaka" guardados como atributos nativos de WooCommerce (`pa_editorial`, `pa_autor`), como Taxonomías personalizadas o como etiquetas genéricas?
> 2. **Identificador de Producto:** ¿Estás guardando el código de barras del manga (ISBN/EAN) en el campo "SKU" nativo de WooCommerce, o utilizas un plugin de campos personalizados (ACF) para el ISBN?

## Arquitectura Propuesta del Plugin

El plugin seguirá un patrón modular orientado a objetos para garantizar rendimiento y fácil escalabilidad, separando responsabilidades lógicamente.

### Estructura de Directorios

```text
damemimanga-seo/
├── damemimanga-seo.php          # Archivo principal (Bootstrap del plugin)
├── includes/
│   ├── class-dmm-sitemap.php    # Lógica de generación de Sitemaps y reescritura de URLs
│   ├── class-dmm-schema.php     # Inyección de JSON-LD y estructuración de datos
│   ├── class-dmm-seo-tags.php   # Canonicals, Noindex y modificaciones al robots.txt
│   └── admin/
│       └── class-dmm-admin.php  # Generación del Panel de Administración
```

---

## Módulos y Funcionalidades Clave

### 1. Panel de Administración (Settings API)
Se creará un menú en el panel de WordPress (`wp-admin > Ajustes > DameMiManga SEO`) que permitirá al administrador:
* Activar o desactivar módulos globales (Sitemaps, Schema, Control de Tags).
* Seleccionar qué *Post Types* (Productos, Páginas, Posts) incluir en el sitemap.
* Configurar qué taxonomías personalizadas incluir (ej. `pa_editorial`).
* Opción manual para vaciar la caché (Transients) del sitemap.

### 2. Módulo de Sitemaps (Sitemap Engine)
* **Puntos de Enlace (Endpoints):** Se registrarán reglas nativas con `add_rewrite_rule()` para interceptar solicitudes a `/sitemap_index.xml` y enrutarlas a métodos que generan XML dinámico.
* **Índice y Paginación:** `sitemap_index.xml` apuntará a sub-sitemaps como `sitemap-products-1.xml`, limitando cada uno a 5,000 URLs.
* **Exclusiones:** Reglas estrictas para excluir el carrito, checkout, mi-cuenta y URLs de búsqueda nativa.
* **Rendimiento:** Se utilizarán *Transients* de WordPress (`set_transient`, `get_transient`) para cachear los bloques XML y servirlos rápidamente, con invalidación de caché vinculada a hooks como `save_post_product`.

### 3. Módulo de Schema (JSON-LD)
* **Homepage:** Inyección en el `wp_head` (solo si es `is_front_page()`) del Schema `BookStore` y `Organization` con datos de DameMiManga.
* **Productos (Mangas):**
  * Desactivar el generador de Schema nativo de WooCommerce para evitar duplicados.
  * Generar un JSON-LD de tipo dual `["Product", "ComicIssue"]`.
  * Extraer dinámicamente: Título, Imagen destacada, Precio, Moneda (forzada a `CLP`), Disponibilidad (`InStock`/`OutOfStock`), y extraer los atributos específicos de cómics (Editorial, Autor, ISBN).
* **Breadcrumbs:** Inyección automática del schema `BreadcrumbList` basado en las categorías jerárquicas del manga actual.

### 4. Módulo de Optimización On-Page (SEO Tags)
* **Canonicals:** Implementar lógica para que cualquier URL de producto con variables (ej. `?attribute_...`) apunte a la URL limpia mediante `<link rel="canonical">`.
* **Robots.txt:** Interceptar el hook `do_robotstxt` para:
  1. Forzar directivas de `Disallow` en parámetros como `?add-to-cart=`.
  2. Apuntar el `Sitemap:` directamente a nuestra nueva ruta virtual generada.

## Plan de Verificación

### Pruebas Automatizadas
* Activar el modo `WP_DEBUG` temporalmente para verificar que no haya "Notices" o "Warnings" de PHP durante la generación de XML.
* Verificar los endpoints del servidor con peticiones cURL a `/sitemap_index.xml` para constatar headers `Content-Type: text/xml` y códigos `200 OK`.

### Verificación Manual
1. **Search Console / Rich Results Test:** El cliente deberá ingresar la URL de un producto en la herramienta [Prueba de resultados enriquecidos de Google](https://search.google.com/test/rich-results) para validar que los schemas `Product`, `ComicIssue` y `BreadcrumbList` no arrojen errores ni advertencias críticas.
2. **Validación XML:** Abrir el sitemap generado en distintos navegadores para comprobar que se renderiza el árbol XML correctamente, y que excluye las URLs solicitadas.
3. **Panel de Admin:** Verificar que guardar los ajustes en el panel refresca correctamente el estado de las reglas de reescritura (`flush_rewrite_rules`).
