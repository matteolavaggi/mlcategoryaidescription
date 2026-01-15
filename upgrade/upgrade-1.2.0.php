<?php
/**
 * 2010-2026 2win.agency
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
 * @author    2win.agency
 * @copyright 2010-2026 2win.agency
 * @license   Valid for 1 website (or project) for each purchase of license
 *            International Registered Trademark & Property of 2win.agency
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Upgrade to version 1.2.0
 * - Added meta_keywords and link_rewrite field support
 * - Added French and Italian prompt translations for ALL fields
 * - Removed Enable Module toggle
 * - Added prompt template editor
 *
 * @param Mlcategoryaidescription $module
 *
 * @return bool
 */
function upgrade_module_1_2_0($module)
{
    $idShop = (int) Shop::getContextShopID();
    $languages = Language::getLanguages(true);

    // All prompts with translations
    $allPrompts = [
        Mlcategoryaidescription::FIELD_DESCRIPTION => [
            'name' => 'Default Description Prompt',
            'template_en' => 'Write a compelling and SEO-friendly product category description for an e-commerce website.

Category: {category_name}
Parent Category: {parent_category_name}
Website: {site_name}

Products in this category include: {first_products:10}

Requirements:
- 150-300 words
- Include relevant keywords naturally
- Highlight benefits and variety
- Use engaging, professional tone
- Do not mention prices or specific promotions',
            'template_fr' => 'Rédigez une description de catégorie de produits attrayante et optimisée pour le SEO pour un site e-commerce.

Catégorie : {category_name}
Catégorie parente : {parent_category_name}
Site web : {site_name}

Produits dans cette catégorie : {first_products:10}

Exigences :
- 150-300 mots
- Inclure naturellement les mots-clés pertinents
- Mettre en avant les avantages et la variété
- Utiliser un ton engageant et professionnel
- Ne pas mentionner les prix ou promotions spécifiques',
            'template_it' => 'Scrivi una descrizione di categoria prodotti accattivante e SEO-friendly per un sito e-commerce.

Categoria: {category_name}
Categoria principale: {parent_category_name}
Sito web: {site_name}

Prodotti in questa categoria: {first_products:10}

Requisiti:
- 150-300 parole
- Includere naturalmente le parole chiave rilevanti
- Evidenziare i vantaggi e la varietà
- Usare un tono coinvolgente e professionale
- Non menzionare prezzi o promozioni specifiche',
        ],
        Mlcategoryaidescription::FIELD_META_TITLE => [
            'name' => 'Default Meta Title Prompt',
            'template_en' => 'Generate an SEO-optimized meta title for this e-commerce category page.

Category: {category_name}
Website: {site_name}

Requirements:
- Maximum 60 characters
- Include category name and brand if space allows
- Make it compelling for search results
- Return ONLY the meta title text, no explanations',
            'template_fr' => 'Générez un meta title optimisé pour le SEO pour cette page de catégorie e-commerce.

Catégorie : {category_name}
Site web : {site_name}

Exigences :
- Maximum 60 caractères
- Inclure le nom de la catégorie et la marque si possible
- Rendre le titre attrayant pour les résultats de recherche
- Retourner UNIQUEMENT le texte du meta title, sans explications',
            'template_it' => 'Genera un meta title ottimizzato per la SEO per questa pagina di categoria e-commerce.

Categoria: {category_name}
Sito web: {site_name}

Requisiti:
- Massimo 60 caratteri
- Includere il nome della categoria e il brand se possibile
- Rendere il titolo accattivante per i risultati di ricerca
- Restituire SOLO il testo del meta title, senza spiegazioni',
        ],
        Mlcategoryaidescription::FIELD_META_DESCRIPTION => [
            'name' => 'Default Meta Description Prompt',
            'template_en' => 'Write an SEO-friendly meta description for this e-commerce category page.

Category: {category_name}
Products available: {product_count}
Sample products: {random_products:5}

Requirements:
- Maximum 155 characters
- Include call-to-action
- Mention variety/selection
- Return ONLY the meta description text, no explanations',
            'template_fr' => 'Rédigez une meta description optimisée SEO pour cette page de catégorie e-commerce.

Catégorie : {category_name}
Produits disponibles : {product_count}
Exemples de produits : {random_products:5}

Exigences :
- Maximum 155 caractères
- Inclure un appel à l\'action
- Mentionner la variété/sélection
- Retourner UNIQUEMENT le texte de la meta description, sans explications',
            'template_it' => 'Scrivi una meta description SEO-friendly per questa pagina di categoria e-commerce.

Categoria: {category_name}
Prodotti disponibili: {product_count}
Esempi di prodotti: {random_products:5}

Requisiti:
- Massimo 155 caratteri
- Includere una call-to-action
- Menzionare la varietà/selezione
- Restituire SOLO il testo della meta description, senza spiegazioni',
        ],
        Mlcategoryaidescription::FIELD_META_KEYWORDS => [
            'name' => 'Default Meta Keywords Prompt',
            'template_en' => 'Generate SEO keywords for this e-commerce category page.

Category: {category_name}
Parent Category: {parent_category_name}
Sample products: {first_products:5}

Requirements:
- 5-10 relevant keywords separated by commas
- Include category name and variations
- Include product type keywords
- Return ONLY the keywords, comma-separated, no explanations',
            'template_fr' => 'Générez des mots-clés SEO pour cette page de catégorie e-commerce.

Catégorie : {category_name}
Catégorie parente : {parent_category_name}
Exemples de produits : {first_products:5}

Exigences :
- 5-10 mots-clés pertinents séparés par des virgules
- Inclure le nom de la catégorie et ses variations
- Inclure les mots-clés du type de produit
- Retourner UNIQUEMENT les mots-clés, séparés par des virgules, sans explications',
            'template_it' => 'Genera parole chiave SEO per questa pagina di categoria e-commerce.

Categoria: {category_name}
Categoria principale: {parent_category_name}
Esempi di prodotti: {first_products:5}

Requisiti:
- 5-10 parole chiave rilevanti separate da virgole
- Includere il nome della categoria e le sue variazioni
- Includere parole chiave del tipo di prodotto
- Restituire SOLO le parole chiave, separate da virgole, senza spiegazioni',
        ],
        Mlcategoryaidescription::FIELD_LINK_REWRITE => [
            'name' => 'Default Friendly URL Prompt',
            'template_en' => 'Generate a SEO-friendly URL slug for this e-commerce category page.

Category: {category_name}
Parent Category: {parent_category_name}

Requirements:
- Use lowercase letters only
- Use hyphens to separate words
- Maximum 50 characters
- Remove special characters
- Return ONLY the URL slug, no explanations (example: mens-running-shoes)',
            'template_fr' => 'Générez un slug URL SEO-friendly pour cette page de catégorie e-commerce.

Catégorie : {category_name}
Catégorie parente : {parent_category_name}

Exigences :
- Utiliser uniquement des lettres minuscules
- Utiliser des tirets pour séparer les mots
- Maximum 50 caractères
- Supprimer les caractères spéciaux
- Retourner UNIQUEMENT le slug URL, sans explications (exemple : chaussures-homme-running)',
            'template_it' => 'Genera uno slug URL SEO-friendly per questa pagina di categoria e-commerce.

Categoria: {category_name}
Categoria principale: {parent_category_name}

Requisiti:
- Usare solo lettere minuscole
- Usare trattini per separare le parole
- Massimo 50 caratteri
- Rimuovere i caratteri speciali
- Restituire SOLO lo slug URL, senza spiegazioni (esempio: scarpe-uomo-running)',
        ],
    ];

    foreach ($allPrompts as $fieldType => $promptData) {
        // Check if prompt already exists
        $idTemplate = (int) Db::getInstance()->getValue(
            'SELECT `id_prompt_template` FROM `' . _DB_PREFIX_ . 'mlcategoryai_prompt_template`
            WHERE `field_type` = "' . pSQL($fieldType) . '" AND `id_shop` = ' . $idShop
        );

        if (!$idTemplate) {
            // Create new prompt template
            Db::getInstance()->insert('mlcategoryai_prompt_template', [
                'id_shop' => $idShop,
                'name' => pSQL($promptData['name']),
                'field_type' => pSQL($fieldType),
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $idTemplate = (int) Db::getInstance()->Insert_ID();
        }

        // Update/Insert language-specific prompts with translations
        foreach ($languages as $lang) {
            $isoCode = strtolower($lang['iso_code']);
            $templateKey = 'template_' . $isoCode;

            // Fall back to English if translation not available
            if (!isset($promptData[$templateKey])) {
                $templateKey = 'template_en';
            }

            // Check if language prompt exists
            $exists = (int) Db::getInstance()->getValue(
                'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'mlcategoryai_prompt_template_lang`
                WHERE `id_prompt_template` = ' . $idTemplate . ' AND `id_lang` = ' . (int) $lang['id_lang']
            );

            if ($exists) {
                // Update existing - only if it's still using English (not customized)
                // Check if current prompt matches English template
                $currentPrompt = Db::getInstance()->getValue(
                    'SELECT `prompt_template` FROM `' . _DB_PREFIX_ . 'mlcategoryai_prompt_template_lang`
                    WHERE `id_prompt_template` = ' . $idTemplate . ' AND `id_lang` = ' . (int) $lang['id_lang']
                );

                // Only update if current prompt is English and we have a translation
                if ($isoCode !== 'en' && isset($promptData['template_' . $isoCode])) {
                    // Check if it looks like English prompt (contains English words)
                    if (strpos($currentPrompt, 'Write a compelling') !== false
                        || strpos($currentPrompt, 'Generate an SEO') !== false
                        || strpos($currentPrompt, 'Generate SEO keywords') !== false
                        || strpos($currentPrompt, 'Requirements:') !== false
                    ) {
                        Db::getInstance()->update(
                            'mlcategoryai_prompt_template_lang',
                            ['prompt_template' => pSQL($promptData[$templateKey], true)],
                            '`id_prompt_template` = ' . $idTemplate . ' AND `id_lang` = ' . (int) $lang['id_lang']
                        );
                    }
                }
            } else {
                // Insert new
                Db::getInstance()->insert('mlcategoryai_prompt_template_lang', [
                    'id_prompt_template' => $idTemplate,
                    'id_lang' => (int) $lang['id_lang'],
                    'prompt_template' => pSQL($promptData[$templateKey], true),
                ]);
            }
        }
    }

    return true;
}
