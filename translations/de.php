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
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_ef5dc4f3c2d36c9d9e8a1b6e7c'] = 'Dieses Modul verbessert die Google Crawl-Budget-Verwaltung, noindex bei Paginierung und Filterergebnisse für Shops mit großen Katalogen.';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_c888438d14855d7d96a2724ee9c306bd'] = 'Einstellungen erfolgreich aktualisiert.';

// Form labels
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_2faec1f9f8cc7f8f40d521c4dd574f49'] = 'Modul aktivieren';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_00d23a76e43b46dae9ec7aa9dcbebb32'] = 'Hauptschalter zum Aktivieren/Deaktivieren der NoIndex-Funktionalität.';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_enabled'] = 'Aktiviert';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_disabled'] = 'Deaktiviert';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_http_header'] = 'HTTP-Header verwenden';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_http_header_desc'] = 'Auch X-Robots-Tag HTTP-Header senden (zuverlässiger für einige Crawler). Der Meta-Tag wird immer hinzugefügt.';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_standard_params'] = 'Standard PrestaShop Parameter';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_pagination'] = 'Paginierung';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_pagination_desc'] = 'NoIndex zu Seiten mit ?page=2+ oder ?p=2+ hinzufügen';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_order_sort'] = 'Sortierung';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_order_sort_desc'] = 'NoIndex zu Seiten mit Sortierungsparametern hinzufügen (?order=, ?orderby=, ?orderway=)';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_currency'] = 'Währung';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_currency_desc'] = 'NoIndex zu Seiten mit Währungsparametern hinzufügen (?id_currency=, ?SubmitCurrency)';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_search'] = 'Suchergebnisse';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_search_desc'] = 'NoIndex zu Suchergebnisseiten hinzufügen (?s=, ?q=, ?search_query=, controller=search)';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_price_filter'] = 'Preisfilter';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_price_filter_desc'] = 'NoIndex zu Seiten mit Preisfiltern hinzufügen (?from=, ?to=, ?price_min=, ?price_max=)';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_items_per_page'] = 'Artikel pro Seite';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_items_per_page_desc'] = 'NoIndex zu Seiten mit Artikel-pro-Seite-Parametern hinzufügen (?n=, ?resultsPerPage=)';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_ps_faceted'] = 'PS Faceted Search';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_ps_faceted_desc'] = 'NoIndex zu gefilterten Seiten des nativen PS Faceted Search Moduls hinzufügen';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_amazingfilter'] = 'AmazingFilter';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_amazingfilter_desc'] = 'NoIndex zu AmazingFilter gefilterten Seiten hinzufügen (/f-* URLs, ?af=, ?from-xhr)';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_tracking_params'] = 'Tracking-Parameter';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_tracking_params_desc'] = 'NoIndex zu Seiten mit Tracking-Parametern hinzufügen (utm_*, gclid, fbclid, msclkid, usw.) - Optional, deaktivieren wenn Sie Canonical-Tags verwenden.';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_custom_params'] = 'Benutzerdefinierte Parameter';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_custom_params_desc'] = 'Benutzerdefinierte URL-Parameter hinzufügen, um NoIndex auszulösen (einer pro Zeile, ohne "?" oder "="). Beispiel: my_filter';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_settings'] = 'NoIndex-Einstellungen';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_save'] = 'Speichern';

// Configure template
$_MODULE['<{mlgooglenoindex}prestashop>configure_title'] = 'ML Google SEO NoIndex';
$_MODULE['<{mlgooglenoindex}prestashop>configure_optimize'] = 'Optimieren Sie Ihr Google Crawl-Budget!';
$_MODULE['<{mlgooglenoindex}prestashop>configure_adds'] = 'Dieses Modul fügt hinzu';
$_MODULE['<{mlgooglenoindex}prestashop>configure_to_filtered'] = 'zu gefilterten und paginierten Seiten.';
$_MODULE['<{mlgooglenoindex}prestashop>configure_how_works'] = 'Wie es funktioniert:';
$_MODULE['<{mlgooglenoindex}prestashop>configure_pagination_label'] = 'Paginierung:';
$_MODULE['<{mlgooglenoindex}prestashop>configure_pagination_desc'] = 'Seiten mit ?page=2+ oder ?p=2+ werden noindexiert';
$_MODULE['<{mlgooglenoindex}prestashop>configure_order_label'] = 'Sortierung:';
$_MODULE['<{mlgooglenoindex}prestashop>configure_order_desc'] = 'Produktsortierungsparameter lösen NoIndex aus';
$_MODULE['<{mlgooglenoindex}prestashop>configure_currency_label'] = 'Währung:';
$_MODULE['<{mlgooglenoindex}prestashop>configure_currency_desc'] = 'Währungswechsel-Parameter lösen NoIndex aus';
$_MODULE['<{mlgooglenoindex}prestashop>configure_search_label'] = 'Suche:';
$_MODULE['<{mlgooglenoindex}prestashop>configure_search_desc'] = 'Suchergebnisseiten werden noindexiert';
$_MODULE['<{mlgooglenoindex}prestashop>configure_price_label'] = 'Preisfilter:';
$_MODULE['<{mlgooglenoindex}prestashop>configure_price_desc'] = 'Preisfilter-Parameter lösen NoIndex aus';
$_MODULE['<{mlgooglenoindex}prestashop>configure_items_label'] = 'Artikel pro Seite:';
$_MODULE['<{mlgooglenoindex}prestashop>configure_items_desc'] = 'Artikel-pro-Seite-Parameter lösen NoIndex aus';
$_MODULE['<{mlgooglenoindex}prestashop>configure_tracking_label'] = 'Tracking:';
$_MODULE['<{mlgooglenoindex}prestashop>configure_tracking_desc'] = 'UTM- und Werbeplattform-Tracking-Parameter (optional)';
$_MODULE['<{mlgooglenoindex}prestashop>configure_custom_label'] = 'Benutzerdefiniert:';
$_MODULE['<{mlgooglenoindex}prestashop>configure_custom_desc'] = 'Fügen Sie Ihre eigenen Parameter hinzu, um NoIndex auszulösen';
$_MODULE['<{mlgooglenoindex}prestashop>configure_ps_detected'] = 'PS Faceted Search erkannt!';
$_MODULE['<{mlgooglenoindex}prestashop>configure_ps_detected_desc'] = 'Das native PrestaShop Faceted Search Modul ist aktiv. Sie können NoIndex für gefilterte Seiten aktivieren.';
$_MODULE['<{mlgooglenoindex}prestashop>configure_ps_not_detected'] = 'PS Faceted Search nicht erkannt';
$_MODULE['<{mlgooglenoindex}prestashop>configure_ps_not_detected_desc'] = 'Das native ps_facetedsearch Modul ist nicht installiert oder nicht aktiv.';
$_MODULE['<{mlgooglenoindex}prestashop>configure_af_detected'] = 'AmazingFilter erkannt!';
$_MODULE['<{mlgooglenoindex}prestashop>configure_af_detected_desc'] = 'Das AmazingFilter Modul ist installiert und aktiv. Sie können NoIndex für gefilterte Seiten aktivieren.';
$_MODULE['<{mlgooglenoindex}prestashop>configure_af_not_detected'] = 'AmazingFilter nicht erkannt';
$_MODULE['<{mlgooglenoindex}prestashop>configure_af_not_detected_desc'] = 'Das AmazingFilter Modul ist nicht installiert oder nicht aktiv.';
$_MODULE['<{mlgooglenoindex}prestashop>configure_param_reference'] = 'Parameter-Referenz';
$_MODULE['<{mlgooglenoindex}prestashop>configure_param_desc'] = 'Die folgenden URL-Muster lösen NoIndex aus, wenn ihre jeweiligen Optionen aktiviert sind:';
$_MODULE['<{mlgooglenoindex}prestashop>configure_option'] = 'Option';
$_MODULE['<{mlgooglenoindex}prestashop>configure_parameters'] = 'Parameter / Muster';
$_MODULE['<{mlgooglenoindex}prestashop>configure_attr_patterns'] = 'Attribut/Merkmal URL-Muster';
$_MODULE['<{mlgooglenoindex}prestashop>configure_seo_title'] = 'SEO Best Practices';
$_MODULE['<{mlgooglenoindex}prestashop>configure_seo_1'] = 'Dieses Modul verwendet';
$_MODULE['<{mlgooglenoindex}prestashop>configure_seo_2'] = 'um doppelte Inhalte zu verhindern und gleichzeitig den Link Equity zu erhalten.';
$_MODULE['<{mlgooglenoindex}prestashop>configure_seo_3'] = 'Tracking-Parameter sind standardmäßig deaktiviert - aktivieren Sie sie nur, wenn Sie keine Canonical-Tags verwenden.';
$_MODULE['<{mlgooglenoindex}prestashop>configure_seo_4'] = 'Die HTTP-Header-Option fügt X-Robots-Tag hinzu, das einige Crawler gegenüber Meta-Tags bevorzugen.';
$_MODULE['<{mlgooglenoindex}prestashop>configure_seo_5'] = 'Überwachen Sie die Google Search Console auf Änderungen der Crawl-Statistiken nach der Aktivierung dieses Moduls.';
