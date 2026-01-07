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
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_ef5dc4f3c2d36c9d9e8a1b6e7c'] = 'Ce module améliore la gestion du budget de crawl Google, noindex sur la pagination et les résultats de filtres pour les boutiques avec de grands catalogues.';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_c888438d14855d7d96a2724ee9c306bd'] = 'Paramètres mis à jour avec succès.';

// Form labels
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_2faec1f9f8cc7f8f40d521c4dd574f49'] = 'Activer le Module';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_00d23a76e43b46dae9ec7aa9dcbebb32'] = 'Interrupteur principal pour activer/désactiver la fonctionnalité noindex.';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_enabled'] = 'Activé';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_disabled'] = 'Désactivé';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_http_header'] = 'Utiliser l\'en-tête HTTP';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_http_header_desc'] = 'Envoyer également l\'en-tête HTTP X-Robots-Tag (plus fiable pour certains robots). La balise meta est toujours ajoutée.';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_standard_params'] = 'Paramètres Standard PrestaShop';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_pagination'] = 'Pagination';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_pagination_desc'] = 'Ajouter noindex aux pages avec ?page=2+ ou ?p=2+';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_order_sort'] = 'Ordre et Tri';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_order_sort_desc'] = 'Ajouter noindex aux pages avec des paramètres de tri (?order=, ?orderby=, ?orderway=)';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_currency'] = 'Devise';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_currency_desc'] = 'Ajouter noindex aux pages avec des paramètres de devise (?id_currency=, ?SubmitCurrency)';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_search'] = 'Résultats de Recherche';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_search_desc'] = 'Ajouter noindex aux pages de résultats de recherche (?s=, ?q=, ?search_query=, controller=search)';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_price_filter'] = 'Filtres de Prix';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_price_filter_desc'] = 'Ajouter noindex aux pages avec des filtres de prix (?from=, ?to=, ?price_min=, ?price_max=)';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_items_per_page'] = 'Produits par Page';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_items_per_page_desc'] = 'Ajouter noindex aux pages avec des paramètres de produits par page (?n=, ?resultsPerPage=)';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_ps_faceted'] = 'PS Faceted Search';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_ps_faceted_desc'] = 'Ajouter noindex aux pages filtrées avec le module natif PS Faceted Search';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_amazingfilter'] = 'AmazingFilter';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_amazingfilter_desc'] = 'Ajouter noindex aux pages filtrées AmazingFilter (URLs /f-*, ?af=, ?from-xhr)';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_tracking_params'] = 'Paramètres de Suivi';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_tracking_params_desc'] = 'Ajouter noindex aux pages avec des paramètres de suivi (utm_*, gclid, fbclid, msclkid, etc.) - Optionnel, désactiver si vous utilisez des balises canonical.';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_custom_params'] = 'Paramètres Personnalisés';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_custom_params_desc'] = 'Ajouter des paramètres URL personnalisés pour déclencher noindex (un par ligne, sans "?" ni "="). Exemple: my_filter';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_settings'] = 'Paramètres NoIndex';
$_MODULE['<{mlgooglenoindex}prestashop>mlgooglenoindex_save'] = 'Enregistrer';

// Configure template
$_MODULE['<{mlgooglenoindex}prestashop>configure_title'] = 'ML Google SEO NoIndex';
$_MODULE['<{mlgooglenoindex}prestashop>configure_optimize'] = 'Optimisez votre budget de crawl Google !';
$_MODULE['<{mlgooglenoindex}prestashop>configure_adds'] = 'Ce module ajoute';
$_MODULE['<{mlgooglenoindex}prestashop>configure_to_filtered'] = 'aux pages filtrées et paginées.';
$_MODULE['<{mlgooglenoindex}prestashop>configure_how_works'] = 'Comment ça fonctionne :';
$_MODULE['<{mlgooglenoindex}prestashop>configure_pagination_label'] = 'Pagination :';
$_MODULE['<{mlgooglenoindex}prestashop>configure_pagination_desc'] = 'Les pages avec ?page=2+ ou ?p=2+ seront noindexées';
$_MODULE['<{mlgooglenoindex}prestashop>configure_order_label'] = 'Ordre et Tri :';
$_MODULE['<{mlgooglenoindex}prestashop>configure_order_desc'] = 'Les paramètres de tri des produits déclencheront noindex';
$_MODULE['<{mlgooglenoindex}prestashop>configure_currency_label'] = 'Devise :';
$_MODULE['<{mlgooglenoindex}prestashop>configure_currency_desc'] = 'Les paramètres de changement de devise déclencheront noindex';
$_MODULE['<{mlgooglenoindex}prestashop>configure_search_label'] = 'Recherche :';
$_MODULE['<{mlgooglenoindex}prestashop>configure_search_desc'] = 'Les pages de résultats de recherche seront noindexées';
$_MODULE['<{mlgooglenoindex}prestashop>configure_price_label'] = 'Filtres de Prix :';
$_MODULE['<{mlgooglenoindex}prestashop>configure_price_desc'] = 'Les paramètres de filtre de prix déclencheront noindex';
$_MODULE['<{mlgooglenoindex}prestashop>configure_items_label'] = 'Produits par Page :';
$_MODULE['<{mlgooglenoindex}prestashop>configure_items_desc'] = 'Les paramètres de comptage de produits par page déclencheront noindex';
$_MODULE['<{mlgooglenoindex}prestashop>configure_tracking_label'] = 'Suivi :';
$_MODULE['<{mlgooglenoindex}prestashop>configure_tracking_desc'] = 'Paramètres de suivi UTM et plateformes publicitaires (optionnel)';
$_MODULE['<{mlgooglenoindex}prestashop>configure_custom_label'] = 'Personnalisés :';
$_MODULE['<{mlgooglenoindex}prestashop>configure_custom_desc'] = 'Ajoutez vos propres paramètres pour déclencher noindex';
$_MODULE['<{mlgooglenoindex}prestashop>configure_ps_detected'] = 'PS Faceted Search Détecté !';
$_MODULE['<{mlgooglenoindex}prestashop>configure_ps_detected_desc'] = 'Le module natif PrestaShop Faceted Search est actif. Vous pouvez activer noindex pour les pages filtrées.';
$_MODULE['<{mlgooglenoindex}prestashop>configure_ps_not_detected'] = 'PS Faceted Search Non Détecté';
$_MODULE['<{mlgooglenoindex}prestashop>configure_ps_not_detected_desc'] = 'Le module natif ps_facetedsearch n\'est pas installé ou n\'est pas actif.';
$_MODULE['<{mlgooglenoindex}prestashop>configure_af_detected'] = 'AmazingFilter Détecté !';
$_MODULE['<{mlgooglenoindex}prestashop>configure_af_detected_desc'] = 'Le module AmazingFilter est installé et actif. Vous pouvez activer noindex pour les pages filtrées.';
$_MODULE['<{mlgooglenoindex}prestashop>configure_af_not_detected'] = 'AmazingFilter Non Détecté';
$_MODULE['<{mlgooglenoindex}prestashop>configure_af_not_detected_desc'] = 'Le module AmazingFilter n\'est pas installé ou n\'est pas actif.';
$_MODULE['<{mlgooglenoindex}prestashop>configure_param_reference'] = 'Référence des Paramètres';
$_MODULE['<{mlgooglenoindex}prestashop>configure_param_desc'] = 'Les modèles d\'URL suivants déclencheront noindex lorsque leurs options respectives sont activées :';
$_MODULE['<{mlgooglenoindex}prestashop>configure_option'] = 'Option';
$_MODULE['<{mlgooglenoindex}prestashop>configure_parameters'] = 'Paramètres / Modèles';
$_MODULE['<{mlgooglenoindex}prestashop>configure_attr_patterns'] = 'modèles URL d\'attributs/caractéristiques';
$_MODULE['<{mlgooglenoindex}prestashop>configure_seo_title'] = 'Meilleures Pratiques SEO';
$_MODULE['<{mlgooglenoindex}prestashop>configure_seo_1'] = 'Ce module utilise';
$_MODULE['<{mlgooglenoindex}prestashop>configure_seo_2'] = 'pour prévenir le contenu dupliqué tout en préservant le link equity.';
$_MODULE['<{mlgooglenoindex}prestashop>configure_seo_3'] = 'Les paramètres de suivi sont désactivés par défaut - activez seulement si vous n\'utilisez pas de balises canonical.';
$_MODULE['<{mlgooglenoindex}prestashop>configure_seo_4'] = 'L\'option En-tête HTTP ajoute X-Robots-Tag que certains robots préfèrent aux balises meta.';
$_MODULE['<{mlgooglenoindex}prestashop>configure_seo_5'] = 'Surveillez Google Search Console pour les changements dans les statistiques de crawl après avoir activé ce module.';
