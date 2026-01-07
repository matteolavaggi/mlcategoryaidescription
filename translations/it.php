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
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_efaboratore5dc4f3c2d36c9d9e8a1b6e7c'] = 'Questo modulo ottimizza il crawl budget di Google, aggiungendo noindex alle pagine paginate e filtrate per negozi con cataloghi grandi.';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_c888438d14855d7d96a2724ee9c306bd'] = 'Impostazioni aggiornate con successo.';

// Form labels
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_2faec1f9f8cc7f8f40d521c4dd574f49'] = 'Abilita Modulo';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_00d23a76e43b46dae9ec7aa9dcbebb32'] = 'Attiva/disattiva la funzionalità noindex.';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_enabled'] = 'Abilitato';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_disabled'] = 'Disabilitato';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_http_header'] = 'Usa Header HTTP';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_http_header_desc'] = 'Invia anche l\'header HTTP X-Robots-Tag (più affidabile per alcuni crawler). Il meta tag viene sempre aggiunto.';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_standard_params'] = 'Parametri Standard PrestaShop';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_pagination'] = 'Paginazione';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_pagination_desc'] = 'Aggiungi noindex alle pagine con ?page=2+ o ?p=2+';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_order_sort'] = 'Ordine & Ordinamento';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_order_sort_desc'] = 'Aggiungi noindex alle pagine con parametri di ordinamento (?order=, ?orderby=, ?orderway=)';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_currency'] = 'Valuta';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_currency_desc'] = 'Aggiungi noindex alle pagine con parametri valuta (?id_currency=, ?SubmitCurrency)';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_search'] = 'Risultati di Ricerca';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_search_desc'] = 'Aggiungi noindex alle pagine dei risultati di ricerca (?s=, ?q=, ?search_query=, controller=search)';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_price_filter'] = 'Filtri Prezzo';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_price_filter_desc'] = 'Aggiungi noindex alle pagine con filtri prezzo (?from=, ?to=, ?price_min=, ?price_max=)';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_items_per_page'] = 'Prodotti per Pagina';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_items_per_page_desc'] = 'Aggiungi noindex alle pagine con parametri prodotti per pagina (?n=, ?resultsPerPage=)';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_ps_faceted'] = 'PS Faceted Search';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_ps_faceted_desc'] = 'Aggiungi noindex alle pagine filtrate con il modulo nativo PS Faceted Search';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_amazingfilter'] = 'AmazingFilter';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_amazingfilter_desc'] = 'Aggiungi noindex alle pagine filtrate con AmazingFilter (URL /f-*, ?af=, ?from-xhr)';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_tracking_params'] = 'Parametri di Tracciamento';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_tracking_params_desc'] = 'Aggiungi noindex alle pagine con parametri di tracciamento (utm_*, gclid, fbclid, msclkid, ecc.) - Opzionale, disabilita se usi i tag canonical.';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_custom_params'] = 'Parametri Personalizzati';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_custom_params_desc'] = 'Aggiungi parametri URL personalizzati per attivare noindex (uno per riga, senza "?" o "="). Esempio: my_filter';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_settings'] = 'Impostazioni NoIndex';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_save'] = 'Salva';

// Configure template
$_MODULE['<{mlgooglenoindex}prestashop>configure_title'] = 'ML Google SEO NoIndex';
$_MODULE['<{mlgooglenoindex}prestashop>configure_optimize'] = 'Ottimizza il tuo crawl budget Google!';
$_MODULE['<{mlgooglenoindex}prestashop>configure_adds'] = 'Questo modulo aggiunge';
$_MODULE['<{mlgooglenoindex}prestashop>configure_to_filtered'] = 'alle pagine filtrate e paginate.';
$_MODULE['<{mlgooglenoindex}prestashop>configure_how_works'] = 'Come funziona:';
$_MODULE['<{mlgooglenoindex}prestashop>configure_pagination_label'] = 'Paginazione:';
$_MODULE['<{mlgooglenoindex}prestashop>configure_pagination_desc'] = 'Le pagine con ?page=2+ o ?p=2+ saranno noindexate';
$_MODULE['<{mlgooglenoindex}prestashop>configure_order_label'] = 'Ordine & Ordinamento:';
$_MODULE['<{mlgooglenoindex}prestashop>configure_order_desc'] = 'I parametri di ordinamento prodotti attiveranno noindex';
$_MODULE['<{mlgooglenoindex}prestashop>configure_currency_label'] = 'Valuta:';
$_MODULE['<{mlgooglenoindex}prestashop>configure_currency_desc'] = 'I parametri di cambio valuta attiveranno noindex';
$_MODULE['<{mlgooglenoindex}prestashop>configure_search_label'] = 'Ricerca:';
$_MODULE['<{mlgooglenoindex}prestashop>configure_search_desc'] = 'Le pagine dei risultati di ricerca saranno noindexate';
$_MODULE['<{mlgooglenoindex}prestashop>configure_price_label'] = 'Filtri Prezzo:';
$_MODULE['<{mlgooglenoindex}prestashop>configure_price_desc'] = 'I parametri filtro prezzo attiveranno noindex';
$_MODULE['<{mlgooglenoindex}prestashop>configure_items_label'] = 'Prodotti per Pagina:';
$_MODULE['<{mlgooglenoindex}prestashop>configure_items_desc'] = 'I parametri conteggio prodotti per pagina attiveranno noindex';
$_MODULE['<{mlgooglenoindex}prestashop>configure_tracking_label'] = 'Tracciamento:';
$_MODULE['<{mlgooglenoindex}prestashop>configure_tracking_desc'] = 'Parametri di tracciamento UTM e piattaforme pubblicitarie (opzionale)';
$_MODULE['<{mlgooglenoindex}prestashop>configure_custom_label'] = 'Personalizzati:';
$_MODULE['<{mlgooglenoindex}prestashop>configure_custom_desc'] = 'Aggiungi i tuoi parametri per attivare noindex';
$_MODULE['<{mlgooglenoindex}prestashop>configure_ps_detected'] = 'PS Faceted Search Rilevato!';
$_MODULE['<{mlgooglenoindex}prestashop>configure_ps_detected_desc'] = 'Il modulo nativo PrestaShop Faceted Search è attivo. Puoi abilitare noindex per le pagine filtrate.';
$_MODULE['<{mlgooglenoindex}prestashop>configure_ps_not_detected'] = 'PS Faceted Search Non Rilevato';
$_MODULE['<{mlgooglenoindex}prestashop>configure_ps_not_detected_desc'] = 'Il modulo nativo ps_facetedsearch non è installato o non è attivo.';
$_MODULE['<{mlgooglenoindex}prestashop>configure_af_detected'] = 'AmazingFilter Rilevato!';
$_MODULE['<{mlgooglenoindex}prestashop>configure_af_detected_desc'] = 'Il modulo AmazingFilter è installato e attivo. Puoi abilitare noindex per le pagine filtrate.';
$_MODULE['<{mlgooglenoindex}prestashop>configure_af_not_detected'] = 'AmazingFilter Non Rilevato';
$_MODULE['<{mlgooglenoindex}prestashop>configure_af_not_detected_desc'] = 'Il modulo AmazingFilter non è installato o non è attivo.';
$_MODULE['<{mlgooglenoindex}prestashop>configure_param_reference'] = 'Riferimento Parametri';
$_MODULE['<{mlgooglenoindex}prestashop>configure_param_desc'] = 'I seguenti pattern URL attiveranno noindex quando le rispettive opzioni sono abilitate:';
$_MODULE['<{mlgooglenoindex}prestashop>configure_option'] = 'Opzione';
$_MODULE['<{mlgooglenoindex}prestashop>configure_parameters'] = 'Parametri / Pattern';
$_MODULE['<{mlgooglenoindex}prestashop>configure_attr_patterns'] = 'pattern URL attributi/caratteristiche';
$_MODULE['<{mlgooglenoindex}prestashop>configure_seo_title'] = 'Best Practice SEO';
$_MODULE['<{mlgooglenoindex}prestashop>configure_seo_1'] = 'Questo modulo usa';
$_MODULE['<{mlgooglenoindex}prestashop>configure_seo_2'] = 'per prevenire contenuti duplicati mantenendo il link equity.';
$_MODULE['<{mlgooglenoindex}prestashop>configure_seo_3'] = 'I parametri di tracciamento sono disabilitati di default - abilita solo se non usi i tag canonical.';
$_MODULE['<{mlgooglenoindex}prestashop>configure_seo_4'] = 'L\'opzione Header HTTP aggiunge X-Robots-Tag che alcuni crawler preferiscono rispetto ai meta tag.';
$_MODULE['<{mlgooglenoindex}prestashop>configure_seo_5'] = 'Monitora Google Search Console per cambiamenti nelle statistiche di crawling dopo aver abilitato questo modulo.';
