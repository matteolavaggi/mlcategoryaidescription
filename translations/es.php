<?php
/**
 * 2010-2025 2win.agency
 *
 * NOTICE OF LICENSE
 *
 * This file is not open source! Each license that you purchased is only available for 1 wesite only.
 * If you want to use this file on more websites (or projects), you need to purchase additional licenses.
 * You are not allowed to redistribute, resell, lease, license, sub-license or offer our resources to any third party.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please contact us for extra customization service at an affordable price
 *
 *  @author 2win.agency
 *  @copyright  2010-2025 2win.agency
 *  @license    Valid for 1 website (or project) for each purchase of license
 *  International Registered Trademark & Property of 2win.agency
 */
global $_MODULE;
$_MODULE = [];

// Main module
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_3b3b41e0b6a53467d93e045dd86fb88c'] = 'ML Google SEO NoIndex';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_ef5dc4f3c2d36c9d9e8a1b6e7c'] = 'Este módulo mejora la gestión del presupuesto de rastreo de Google, noindex en paginación y resultados de filtros para tiendas con catálogos grandes.';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_c888438d14855d7d96a2724ee9c306bd'] = 'Configuración actualizada correctamente.';

// Form labels
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_2faec1f9f8cc7f8f40d521c4dd574f49'] = 'Activar Módulo';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_00d23a76e43b46dae9ec7aa9dcbebb32'] = 'Interruptor principal para activar/desactivar la funcionalidad noindex.';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_enabled'] = 'Activado';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_disabled'] = 'Desactivado';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_http_header'] = 'Usar Cabecera HTTP';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_http_header_desc'] = 'También enviar la cabecera HTTP X-Robots-Tag (más fiable para algunos rastreadores). La etiqueta meta siempre se añade.';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_standard_params'] = 'Parámetros Estándar de PrestaShop';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_pagination'] = 'Paginación';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_pagination_desc'] = 'Añadir noindex a páginas con ?page=2+ o ?p=2+';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_order_sort'] = 'Orden y Clasificación';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_order_sort_desc'] = 'Añadir noindex a páginas con parámetros de ordenación (?order=, ?orderby=, ?orderway=)';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_currency'] = 'Moneda';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_currency_desc'] = 'Añadir noindex a páginas con parámetros de moneda (?id_currency=, ?SubmitCurrency)';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_search'] = 'Resultados de Búsqueda';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_search_desc'] = 'Añadir noindex a páginas de resultados de búsqueda (?s=, ?q=, ?search_query=, controller=search)';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_price_filter'] = 'Filtros de Precio';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_price_filter_desc'] = 'Añadir noindex a páginas con filtros de precio (?from=, ?to=, ?price_min=, ?price_max=)';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_items_per_page'] = 'Productos por Página';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_items_per_page_desc'] = 'Añadir noindex a páginas con parámetros de productos por página (?n=, ?resultsPerPage=)';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_ps_faceted'] = 'PS Faceted Search';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_ps_faceted_desc'] = 'Añadir noindex a páginas filtradas con el módulo nativo PS Faceted Search';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_amazingfilter'] = 'AmazingFilter';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_amazingfilter_desc'] = 'Añadir noindex a páginas filtradas de AmazingFilter (URLs /f-*, ?af=, ?from-xhr)';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_tracking_params'] = 'Parámetros de Seguimiento';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_tracking_params_desc'] = 'Añadir noindex a páginas con parámetros de seguimiento (utm_*, gclid, fbclid, msclkid, etc.) - Opcional, desactivar si usas etiquetas canonical.';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_custom_params'] = 'Parámetros Personalizados';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_custom_params_desc'] = 'Añadir parámetros URL personalizados para activar noindex (uno por línea, sin "?" ni "="). Ejemplo: my_filter';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_settings'] = 'Configuración NoIndex';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_save'] = 'Guardar';

// Configure template
$_MODULE['<{mlgooglenoindex}prestashop>configure_title'] = 'ML Google SEO NoIndex';
$_MODULE['<{mlgooglenoindex}prestashop>configure_optimize'] = '¡Optimiza tu presupuesto de rastreo de Google!';
$_MODULE['<{mlgooglenoindex}prestashop>configure_adds'] = 'Este módulo añade';
$_MODULE['<{mlgooglenoindex}prestashop>configure_to_filtered'] = 'a páginas filtradas y paginadas.';
$_MODULE['<{mlgooglenoindex}prestashop>configure_how_works'] = 'Cómo funciona:';
$_MODULE['<{mlgooglenoindex}prestashop>configure_pagination_label'] = 'Paginación:';
$_MODULE['<{mlgooglenoindex}prestashop>configure_pagination_desc'] = 'Las páginas con ?page=2+ o ?p=2+ serán noindexadas';
$_MODULE['<{mlgooglenoindex}prestashop>configure_order_label'] = 'Orden y Clasificación:';
$_MODULE['<{mlgooglenoindex}prestashop>configure_order_desc'] = 'Los parámetros de ordenación de productos activarán noindex';
$_MODULE['<{mlgooglenoindex}prestashop>configure_currency_label'] = 'Moneda:';
$_MODULE['<{mlgooglenoindex}prestashop>configure_currency_desc'] = 'Los parámetros de cambio de moneda activarán noindex';
$_MODULE['<{mlgooglenoindex}prestashop>configure_search_label'] = 'Búsqueda:';
$_MODULE['<{mlgooglenoindex}prestashop>configure_search_desc'] = 'Las páginas de resultados de búsqueda serán noindexadas';
$_MODULE['<{mlgooglenoindex}prestashop>configure_price_label'] = 'Filtros de Precio:';
$_MODULE['<{mlgooglenoindex}prestashop>configure_price_desc'] = 'Los parámetros de filtro de precio activarán noindex';
$_MODULE['<{mlgooglenoindex}prestashop>configure_items_label'] = 'Productos por Página:';
$_MODULE['<{mlgooglenoindex}prestashop>configure_items_desc'] = 'Los parámetros de conteo de productos por página activarán noindex';
$_MODULE['<{mlgooglenoindex}prestashop>configure_tracking_label'] = 'Seguimiento:';
$_MODULE['<{mlgooglenoindex}prestashop>configure_tracking_desc'] = 'Parámetros de seguimiento UTM y plataformas publicitarias (opcional)';
$_MODULE['<{mlgooglenoindex}prestashop>configure_custom_label'] = 'Personalizados:';
$_MODULE['<{mlgooglenoindex}prestashop>configure_custom_desc'] = 'Añade tus propios parámetros para activar noindex';
$_MODULE['<{mlgooglenoindex}prestashop>configure_ps_detected'] = '¡PS Faceted Search Detectado!';
$_MODULE['<{mlgooglenoindex}prestashop>configure_ps_detected_desc'] = 'El módulo nativo PrestaShop Faceted Search está activo. Puedes activar noindex para páginas filtradas.';
$_MODULE['<{mlgooglenoindex}prestashop>configure_ps_not_detected'] = 'PS Faceted Search No Detectado';
$_MODULE['<{mlgooglenoindex}prestashop>configure_ps_not_detected_desc'] = 'El módulo nativo ps_facetedsearch no está instalado o no está activo.';
$_MODULE['<{mlgooglenoindex}prestashop>configure_af_detected'] = '¡AmazingFilter Detectado!';
$_MODULE['<{mlgooglenoindex}prestashop>configure_af_detected_desc'] = 'El módulo AmazingFilter está instalado y activo. Puedes activar noindex para páginas filtradas.';
$_MODULE['<{mlgooglenoindex}prestashop>configure_af_not_detected'] = 'AmazingFilter No Detectado';
$_MODULE['<{mlgooglenoindex}prestashop>configure_af_not_detected_desc'] = 'El módulo AmazingFilter no está instalado o no está activo.';
$_MODULE['<{mlgooglenoindex}prestashop>configure_param_reference'] = 'Referencia de Parámetros';
$_MODULE['<{mlgooglenoindex}prestashop>configure_param_desc'] = 'Los siguientes patrones de URL activarán noindex cuando sus respectivas opciones estén habilitadas:';
$_MODULE['<{mlgooglenoindex}prestashop>configure_option'] = 'Opción';
$_MODULE['<{mlgooglenoindex}prestashop>configure_parameters'] = 'Parámetros / Patrones';
$_MODULE['<{mlgooglenoindex}prestashop>configure_attr_patterns'] = 'patrones URL de atributos/características';
$_MODULE['<{mlgooglenoindex}prestashop>configure_seo_title'] = 'Mejores Prácticas SEO';
$_MODULE['<{mlgooglenoindex}prestashop>configure_seo_1'] = 'Este módulo usa';
$_MODULE['<{mlgooglenoindex}prestashop>configure_seo_2'] = 'para prevenir contenido duplicado manteniendo el link equity.';
$_MODULE['<{mlgooglenoindex}prestashop>configure_seo_3'] = 'Los parámetros de seguimiento están desactivados por defecto - activa solo si no usas etiquetas canonical.';
$_MODULE['<{mlgooglenoindex}prestashop>configure_seo_4'] = 'La opción de Cabecera HTTP añade X-Robots-Tag que algunos rastreadores prefieren sobre las etiquetas meta.';
$_MODULE['<{mlgooglenoindex}prestashop>configure_seo_5'] = 'Monitoriza Google Search Console para cambios en las estadísticas de rastreo después de activar este módulo.';
