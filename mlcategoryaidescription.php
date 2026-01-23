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

// Load module classes
require_once __DIR__ . '/classes/MlCategoryAiClient.php';
require_once __DIR__ . '/classes/MlCategoryAiGenerator.php';
require_once __DIR__ . '/classes/MlCategoryAiJobQueue.php';
require_once __DIR__ . '/classes/MlCategoryAiPlaceholder.php';
require_once __DIR__ . '/classes/MlCategoryAiRunStats.php';

class Mlcategoryaidescription extends Module
{
    /**
     * API Configuration Keys
     */
    const CONFIG_API_PROVIDER = 'MLCATEGORYAI_API_PROVIDER';
    const CONFIG_API_KEY = 'MLCATEGORYAI_API_KEY';
    const CONFIG_API_ENDPOINT = 'MLCATEGORYAI_API_ENDPOINT';
    const CONFIG_API_MODEL = 'MLCATEGORYAI_API_MODEL';

    /**
     * Generation Settings Keys
     */
    const CONFIG_BATCH_SIZE = 'MLCATEGORYAI_BATCH_SIZE';
    const CONFIG_WRITE_MODE = 'MLCATEGORYAI_WRITE_MODE';
    const CONFIG_ENABLED_FIELDS = 'MLCATEGORYAI_ENABLED_FIELDS';
    const CONFIG_MAX_TOKENS = 'MLCATEGORYAI_MAX_TOKENS';
    const CONFIG_TEMPERATURE = 'MLCATEGORYAI_TEMPERATURE';
    const CONFIG_REQUEST_DELAY = 'MLCATEGORYAI_REQUEST_DELAY';
    const CONFIG_PARALLEL_REQUESTS = 'MLCATEGORYAI_PARALLEL_REQUESTS';

    /**
     * Cron Settings Keys
     */
    const CONFIG_CRON_ENABLED = 'MLCATEGORYAI_CRON_ENABLED';
    const CONFIG_CRON_TOKEN = 'MLCATEGORYAI_CRON_TOKEN';

    /**
     * Google Translate Settings Keys
     */
    const CONFIG_GOOGLE_TRANSLATE_ENABLED = 'MLCATEGORYAI_GOOGLE_TRANSLATE_ENABLED';
    const CONFIG_GOOGLE_TRANSLATE_API_KEY = 'MLCATEGORYAI_GOOGLE_TRANSLATE_API_KEY';
    const CONFIG_PRIMARY_LANGUAGE = 'MLCATEGORYAI_PRIMARY_LANGUAGE';
    const CONFIG_TRANSLATE_LANGUAGES = 'MLCATEGORYAI_TRANSLATE_LANGUAGES';

    /**
     * Module State Keys
     */
    const CONFIG_LIVE_MODE = 'MLCATEGORYAI_LIVE_MODE';

    /**
     * Supported API providers
     */
    const PROVIDER_OPENAI = 'openai';
    const PROVIDER_AZURE = 'azure';
    const PROVIDER_CUSTOM = 'custom';

    /**
     * Write modes
     */
    const WRITE_MODE_OVERWRITE = 'overwrite';
    const WRITE_MODE_FILL_MISSING = 'fill_missing';

    /**
     * Field types
     */
    const FIELD_DESCRIPTION = 'description';
    const FIELD_META_TITLE = 'meta_title';
    const FIELD_META_DESCRIPTION = 'meta_description';
    const FIELD_META_KEYWORDS = 'meta_keywords';
    const FIELD_LINK_REWRITE = 'link_rewrite';

    public function __construct()
    {
        $this->name = 'mlcategoryaidescription';
        $this->tab = 'administration';
        $this->version = '1.6.0';
        $this->author = '2win.agency';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('ML Category AI Description');
        $this->description = $this->l('Generate category descriptions, meta titles and meta descriptions using AI (OpenAI compatible).');

        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => '9.99.99'];
    }

    /**
     * Module installation
     *
     * @return bool
     */
    public function install()
    {
        // Set default configuration values ONLY if they don't already exist
        // This preserves settings when reinstalling the module
        $defaults = [
            self::CONFIG_LIVE_MODE => false,
            self::CONFIG_API_PROVIDER => self::PROVIDER_OPENAI,
            self::CONFIG_API_KEY => '',
            self::CONFIG_API_ENDPOINT => 'https://api.openai.com/v1',
            self::CONFIG_API_MODEL => 'gpt-4o-mini',
            self::CONFIG_BATCH_SIZE => 5,
            self::CONFIG_WRITE_MODE => self::WRITE_MODE_FILL_MISSING,
            self::CONFIG_ENABLED_FIELDS => json_encode([self::FIELD_DESCRIPTION, self::FIELD_META_TITLE, self::FIELD_META_DESCRIPTION]),
            self::CONFIG_MAX_TOKENS => 1000,
            self::CONFIG_TEMPERATURE => '0.7',
            self::CONFIG_REQUEST_DELAY => 1,
            self::CONFIG_CRON_ENABLED => false,
            self::CONFIG_CRON_TOKEN => Tools::passwdGen(32),
            self::CONFIG_PARALLEL_REQUESTS => true,
            // Google Translate settings
            self::CONFIG_GOOGLE_TRANSLATE_ENABLED => false,
            self::CONFIG_GOOGLE_TRANSLATE_API_KEY => '',
            self::CONFIG_PRIMARY_LANGUAGE => (int) Configuration::get('PS_LANG_DEFAULT'),
            self::CONFIG_TRANSLATE_LANGUAGES => '[]',
        ];

        foreach ($defaults as $key => $defaultValue) {
            // Only set if config doesn't exist or is empty (except for booleans)
            $existingValue = Configuration::get($key);
            if ($existingValue === false || $existingValue === '') {
                Configuration::updateValue($key, $defaultValue);
            }
        }

        // Include SQL install script
        if (!$this->executeSqlFromFile(dirname(__FILE__) . '/sql/install.php')) {
            return false;
        }

        // Install default prompt templates
        if (!$this->installDefaultPromptTemplates()) {
            return false;
        }

        // Install admin controller tab (hidden)
        $this->installAdminTab();

        return parent::install()
            && $this->registerHook('displayBackOfficeHeader');
    }

    /**
     * Install admin tab for AJAX controller
     *
     * @return bool
     */
    protected function installAdminTab()
    {
        // Install hidden AJAX tab
        $ajaxTab = new Tab();
        $ajaxTab->class_name = 'AdminMlCategoryAiAjax';
        $ajaxTab->module = $this->name;
        $ajaxTab->id_parent = -1; // Hidden tab
        $ajaxTab->active = true;

        foreach (Language::getLanguages(true) as $lang) {
            $ajaxTab->name[$lang['id_lang']] = 'ML Category AI AJAX';
        }

        $ajaxTab->add();

        // Install visible menu tab under Catalog
        $catalogTabId = (int) Tab::getIdFromClassName('AdminCatalog');
        if (!$catalogTabId) {
            // Fallback: try to find SELL parent tab (PS 1.7.7+)
            $catalogTabId = (int) Tab::getIdFromClassName('SELL');
        }

        $menuTab = new Tab();
        $menuTab->class_name = 'AdminMlCategoryAi';
        $menuTab->module = $this->name;
        $menuTab->id_parent = $catalogTabId;
        $menuTab->position = 99; // At the bottom of Catalog menu
        $menuTab->active = true;
        $menuTab->icon = 'category'; // Material icon

        foreach (Language::getLanguages(true) as $lang) {
            $menuTab->name[$lang['id_lang']] = 'ML Category AI';
        }

        return $menuTab->add();
    }

    /**
     * Module uninstallation
     *
     * @return bool
     */
    public function uninstall()
    {
        // Note: Configuration values (API key, settings) are intentionally NOT deleted
        // to preserve settings when reinstalling the module.
        // If you need to completely remove all data, uncomment the following block:
        /*
        $configKeys = [
            self::CONFIG_LIVE_MODE,
            self::CONFIG_API_PROVIDER,
            self::CONFIG_API_KEY,
            self::CONFIG_API_ENDPOINT,
            self::CONFIG_API_MODEL,
            self::CONFIG_BATCH_SIZE,
            self::CONFIG_WRITE_MODE,
            self::CONFIG_ENABLED_FIELDS,
            self::CONFIG_MAX_TOKENS,
            self::CONFIG_TEMPERATURE,
            self::CONFIG_REQUEST_DELAY,
            self::CONFIG_CRON_ENABLED,
            self::CONFIG_CRON_TOKEN,
            self::CONFIG_GOOGLE_TRANSLATE_ENABLED,
            self::CONFIG_GOOGLE_TRANSLATE_API_KEY,
            self::CONFIG_PRIMARY_LANGUAGE,
            self::CONFIG_TRANSLATE_LANGUAGES,
        ];

        foreach ($configKeys as $key) {
            Configuration::deleteByName($key);
        }
        */

        // Uninstall admin tab
        $this->uninstallAdminTab();

        // Include SQL uninstall script (only removes job queue and logs)
        $this->executeSqlFromFile(dirname(__FILE__) . '/sql/uninstall.php');

        return parent::uninstall();
    }

    /**
     * Uninstall admin tab
     *
     * @return bool
     */
    protected function uninstallAdminTab()
    {
        // Remove AJAX hidden tab
        $idAjaxTab = (int) Tab::getIdFromClassName('AdminMlCategoryAiAjax');
        if ($idAjaxTab) {
            $tab = new Tab($idAjaxTab);
            $tab->delete();
        }

        // Remove visible menu tab
        $idMenuTab = (int) Tab::getIdFromClassName('AdminMlCategoryAi');
        if ($idMenuTab) {
            $tab = new Tab($idMenuTab);
            $tab->delete();
        }

        return true;
    }

    /**
     * Execute SQL from file
     *
     * @param string $filePath
     *
     * @return bool
     */
    protected function executeSqlFromFile($filePath)
    {
        if (file_exists($filePath)) {
            return include $filePath;
        }

        return true;
    }

    /**
     * Install default prompt templates for all active languages
     *
     * @return bool
     */
    public function installDefaultPromptTemplates()
    {
        $languages = Language::getLanguages(true);
        $idShop = (int) Shop::getContextShopID();

        // Default prompts with translations
        $defaultPrompts = [
            self::FIELD_DESCRIPTION => [
                'name' => 'Default Description Prompt',
                'template_en' => 'Write a compelling and SEO-friendly category description for an e-commerce website.

CONTEXT INFORMATION:
- Category path: {category_breadcrumb}
- Category name: {category_name}
- Category URL: {category_url}
- Website: {site_name} ({shop_url})
- Website description: {site_description}
- Sample products in this category (for context only): {first_products:10}

IMPORTANT GUIDELINES:
The product list above is provided ONLY to help you understand what TYPE of products this category contains. Use it to identify the general product category and typical use cases.

DO NOT:
- Mention specific product names, models, or unique features from the sample list
- Reference individual items like "our conical pendant lamp" or "the vintage brass fixture"
- Include any product-specific details that only apply to individual items

DO:
- Write about the general product TYPE (e.g., "pendant lamps", "LED lighting", "outdoor fixtures")
- Describe benefits relevant to the entire category
- Include keywords for the product category, not specific items
- Focus on what customers look for when shopping this category
- Highlight variety, quality, and shopping experience

REQUIREMENTS:
- Length: 250-350 words
- Use natural, SEO-friendly language
- Include relevant category keywords (not product names)
- Write in an engaging, professional tone
- Structure with clear paragraphs
- Do not mention prices or specific promotions',
                'template_fr' => 'Rédigez une description de catégorie captivante et optimisée SEO pour un site e-commerce.

INFORMATIONS CONTEXTUELLES :
- Chemin de la catégorie : {category_breadcrumb}
- Nom de la catégorie : {category_name}
- URL de la catégorie : {category_url}
- Site web : {site_name} ({shop_url})
- Description du site : {site_description}
- Exemples de produits dans cette catégorie (contexte uniquement) : {first_products:10}

DIRECTIVES IMPORTANTES :
La liste de produits ci-dessus est fournie UNIQUEMENT pour vous aider à comprendre quel TYPE de produits cette catégorie contient. Utilisez-la pour identifier la catégorie générale et les cas d\'utilisation typiques.

NE PAS :
- Mentionner des noms de produits spécifiques, modèles ou caractéristiques uniques
- Faire référence à des articles individuels
- Inclure des détails spécifiques qui ne s\'appliquent qu\'à certains produits

À FAIRE :
- Écrire sur le TYPE général de produits
- Décrire les avantages pertinents pour toute la catégorie
- Inclure des mots-clés pour la catégorie, pas pour des produits spécifiques
- Se concentrer sur ce que les clients recherchent
- Mettre en avant la variété, la qualité et l\'expérience d\'achat

EXIGENCES :
- Longueur : 250-350 mots
- Langage naturel et optimisé SEO
- Ton engageant et professionnel
- Ne pas mentionner les prix ou promotions',
                'template_it' => 'Scrivi una descrizione di categoria accattivante e ottimizzata SEO per un sito e-commerce.

INFORMAZIONI DI CONTESTO:
- Percorso categoria: {category_breadcrumb}
- Nome categoria: {category_name}
- URL categoria: {category_url}
- Sito web: {site_name} ({shop_url})
- Descrizione del sito: {site_description}
- Prodotti di esempio in questa categoria (solo per contesto): {first_products:10}

LINEE GUIDA IMPORTANTI:
L\'elenco prodotti sopra è fornito SOLO per aiutarti a capire che TIPO di prodotti contiene questa categoria. Usalo per identificare la categoria generale e i casi d\'uso tipici.

NON FARE:
- Menzionare nomi di prodotti specifici, modelli o caratteristiche uniche dalla lista
- Fare riferimento a singoli articoli come "la nostra lampada a sospensione" o "il lampadario vintage"
- Includere dettagli specifici che si applicano solo a singoli prodotti

FARE:
- Scrivere sul TIPO generale di prodotti (es. "lampade a sospensione", "illuminazione LED")
- Descrivere i vantaggi rilevanti per l\'intera categoria
- Includere parole chiave per la categoria, non per singoli prodotti
- Concentrarsi su cosa cercano i clienti quando acquistano in questa categoria
- Evidenziare varietà, qualità ed esperienza di acquisto

REQUISITI:
- Lunghezza: 250-350 parole
- Linguaggio naturale e SEO-friendly
- Tono coinvolgente e professionale
- Non menzionare prezzi o promozioni specifiche',
                'template_de' => 'Schreiben Sie eine ansprechende und SEO-optimierte Kategoriebeschreibung für eine E-Commerce-Website.

KONTEXTINFORMATIONEN:
- Kategoriepfad: {category_breadcrumb}
- Kategoriename: {category_name}
- Kategorie-URL: {category_url}
- Website: {site_name} ({shop_url})
- Website-Beschreibung: {site_description}
- Beispielprodukte in dieser Kategorie (nur als Kontext): {first_products:10}

WICHTIGE RICHTLINIEN:
Die obige Produktliste dient NUR dazu, Ihnen zu helfen zu verstehen, welche ART von Produkten diese Kategorie enthält. Nutzen Sie sie, um die allgemeine Produktkategorie zu identifizieren.

NICHT TUN:
- Spezifische Produktnamen, Modelle oder einzigartige Merkmale erwähnen
- Auf einzelne Artikel verweisen
- Produktspezifische Details einbeziehen

TUN:
- Über den allgemeinen Produkttyp schreiben
- Vorteile beschreiben, die für die gesamte Kategorie relevant sind
- Schlüsselwörter für die Kategorie einbeziehen, nicht für einzelne Produkte
- Vielfalt, Qualität und Einkaufserlebnis hervorheben

ANFORDERUNGEN:
- Länge: 250-350 Wörter
- Natürliche, SEO-freundliche Sprache
- Ansprechender, professioneller Ton
- Keine Preise oder spezifische Aktionen erwähnen',
                'template_es' => 'Escribe una descripción de categoría atractiva y optimizada para SEO para un sitio e-commerce.

INFORMACIÓN DE CONTEXTO:
- Ruta de categoría: {category_breadcrumb}
- Nombre de categoría: {category_name}
- URL de categoría: {category_url}
- Sitio web: {site_name} ({shop_url})
- Descripción del sitio: {site_description}
- Productos de ejemplo en esta categoría (solo contexto): {first_products:10}

DIRECTRICES IMPORTANTES:
La lista de productos anterior se proporciona SOLO para ayudarte a entender qué TIPO de productos contiene esta categoría. Úsala para identificar la categoría general.

NO HACER:
- Mencionar nombres de productos específicos, modelos o características únicas
- Hacer referencia a artículos individuales
- Incluir detalles específicos de productos individuales

HACER:
- Escribir sobre el TIPO general de productos
- Describir beneficios relevantes para toda la categoría
- Incluir palabras clave para la categoría, no para productos específicos
- Destacar variedad, calidad y experiencia de compra

REQUISITOS:
- Longitud: 250-350 palabras
- Lenguaje natural y optimizado para SEO
- Tono atractivo y profesional
- No mencionar precios ni promociones específicas',
                'template_pt' => 'Escreva uma descrição de categoria atraente e otimizada para SEO para um site e-commerce.

INFORMAÇÕES DE CONTEXTO:
- Caminho da categoria: {category_breadcrumb}
- Nome da categoria: {category_name}
- URL da categoria: {category_url}
- Site: {site_name} ({shop_url})
- Descrição do site: {site_description}
- Produtos de exemplo nesta categoria (apenas contexto): {first_products:10}

DIRETRIZES IMPORTANTES:
A lista de produtos acima é fornecida APENAS para ajudá-lo a entender que TIPO de produtos esta categoria contém. Use-a para identificar a categoria geral.

NÃO FAZER:
- Mencionar nomes de produtos específicos, modelos ou características únicas
- Fazer referência a itens individuais
- Incluir detalhes específicos de produtos individuais

FAZER:
- Escrever sobre o TIPO geral de produtos
- Descrever benefícios relevantes para toda a categoria
- Incluir palavras-chave para a categoria, não para produtos específicos
- Destacar variedade, qualidade e experiência de compra

REQUISITOS:
- Comprimento: 250-350 palavras
- Linguagem natural e otimizada para SEO
- Tom envolvente e profissional
- Não mencionar preços ou promoções específicas',
                'template_nl' => 'Schrijf een aantrekkelijke en SEO-geoptimaliseerde categoriebeschrijving voor een e-commerce website.

CONTEXTINFORMATIE:
- Categoriepad: {category_breadcrumb}
- Categorienaam: {category_name}
- Categorie-URL: {category_url}
- Website: {site_name} ({shop_url})
- Website beschrijving: {site_description}
- Voorbeeldproducten in deze categorie (alleen context): {first_products:10}

BELANGRIJKE RICHTLIJNEN:
De bovenstaande productlijst is ALLEEN bedoeld om u te helpen begrijpen welk TYPE producten deze categorie bevat. Gebruik het om de algemene productcategorie te identificeren.

NIET DOEN:
- Specifieke productnamen, modellen of unieke kenmerken noemen
- Verwijzen naar individuele artikelen
- Productspecifieke details opnemen

WEL DOEN:
- Schrijven over het algemene producttype
- Voordelen beschrijven die relevant zijn voor de hele categorie
- Zoekwoorden voor de categorie opnemen, niet voor specifieke producten
- Variëteit, kwaliteit en winkelervaring benadrukken

VEREISTEN:
- Lengte: 250-350 woorden
- Natuurlijke, SEO-vriendelijke taal
- Boeiende, professionele toon
- Geen prijzen of specifieke promoties noemen',
                'template_pl' => 'Napisz atrakcyjny i zoptymalizowany pod kątem SEO opis kategorii dla sklepu internetowego.

INFORMACJE KONTEKSTOWE:
- Ścieżka kategorii: {category_breadcrumb}
- Nazwa kategorii: {category_name}
- URL kategorii: {category_url}
- Strona: {site_name} ({shop_url})
- Opis strony: {site_description}
- Przykładowe produkty w tej kategorii (tylko kontekst): {first_products:10}

WAŻNE WYTYCZNE:
Powyższa lista produktów jest podana TYLKO po to, aby pomóc Ci zrozumieć, jaki TYP produktów zawiera ta kategoria. Użyj jej do identyfikacji ogólnej kategorii produktów.

NIE RÓB:
- Nie wspominaj konkretnych nazw produktów, modeli lub unikalnych cech
- Nie odwołuj się do pojedynczych artykułów
- Nie uwzględniaj szczegółów dotyczących konkretnych produktów

RÓB:
- Pisz o ogólnym TYPIE produktów
- Opisuj korzyści istotne dla całej kategorii
- Uwzględniaj słowa kluczowe dla kategorii, nie dla konkretnych produktów
- Podkreślaj różnorodność, jakość i doświadczenie zakupowe

WYMAGANIA:
- Długość: 250-350 słów
- Naturalny, przyjazny dla SEO język
- Angażujący, profesjonalny ton
- Nie wspominaj cen ani konkretnych promocji',
            ],
            self::FIELD_META_TITLE => [
                'name' => 'Default Meta Title Prompt',
                'template_en' => 'Generate an SEO-optimized meta title for this e-commerce category page.

Category path: {category_breadcrumb}
Category: {category_name}
Website: {site_name}

Requirements:
- Maximum 60 characters
- Include category name and brand if space allows
- Make it compelling for search results
- Return ONLY the meta title text, no explanations',
                'template_fr' => 'Générez un meta title optimisé pour le SEO pour cette page de catégorie e-commerce.

Chemin de la catégorie : {category_breadcrumb}
Catégorie : {category_name}
Site web : {site_name}

Exigences :
- Maximum 60 caractères
- Inclure le nom de la catégorie et la marque si possible
- Rendre le titre attrayant pour les résultats de recherche
- Retourner UNIQUEMENT le texte du meta title, sans explications',
                'template_it' => 'Genera un meta title ottimizzato per la SEO per questa pagina di categoria e-commerce.

Percorso categoria: {category_breadcrumb}
Categoria: {category_name}
Sito web: {site_name}

Requisiti:
- Massimo 60 caratteri
- Includere il nome della categoria e il brand se possibile
- Rendere il titolo accattivante per i risultati di ricerca
- Restituire SOLO il testo del meta title, senza spiegazioni',
                'template_de' => 'Generieren Sie einen SEO-optimierten Meta-Titel für diese E-Commerce-Kategorieseite.

Kategoriepfad: {category_breadcrumb}
Kategorie: {category_name}
Website: {site_name}

Anforderungen:
- Maximal 60 Zeichen
- Kategoriename und Marke einbeziehen, wenn Platz vorhanden
- Ansprechend für Suchergebnisse gestalten
- NUR den Meta-Titel-Text zurückgeben, keine Erklärungen',
                'template_es' => 'Genera un meta title optimizado para SEO para esta página de categoría de e-commerce.

Ruta de categoría: {category_breadcrumb}
Categoría: {category_name}
Sitio web: {site_name}

Requisitos:
- Máximo 60 caracteres
- Incluir nombre de categoría y marca si hay espacio
- Hacerlo atractivo para resultados de búsqueda
- Devolver SOLO el texto del meta title, sin explicaciones',
            ],
            self::FIELD_META_DESCRIPTION => [
                'name' => 'Default Meta Description Prompt',
                'template_en' => 'Write an SEO-friendly meta description for this e-commerce category page.

Category path: {category_breadcrumb}
Category: {category_name}
Products available: {product_count}
Sample products: {random_products:5}

Requirements:
- Maximum 155 characters
- Include call-to-action
- Mention variety/selection
- Return ONLY the meta description text, no explanations',
                'template_fr' => 'Rédigez une meta description optimisée SEO pour cette page de catégorie e-commerce.

Chemin de la catégorie : {category_breadcrumb}
Catégorie : {category_name}
Produits disponibles : {product_count}
Exemples de produits : {random_products:5}

Exigences :
- Maximum 155 caractères
- Inclure un appel à l\'action
- Mentionner la variété/sélection
- Retourner UNIQUEMENT le texte de la meta description, sans explications',
                'template_it' => 'Scrivi una meta description SEO-friendly per questa pagina di categoria e-commerce.

Percorso categoria: {category_breadcrumb}
Categoria: {category_name}
Prodotti disponibili: {product_count}
Esempi di prodotti: {random_products:5}

Requisiti:
- Massimo 155 caratteri
- Includere una call-to-action
- Menzionare la varietà/selezione
- Restituire SOLO il testo della meta description, senza spiegazioni',
            ],
            self::FIELD_META_KEYWORDS => [
                'name' => 'Default Meta Keywords Prompt',
                'template_en' => 'Generate SEO keywords for this e-commerce category page.

Category path: {category_breadcrumb}
Category: {category_name}
Sample products: {first_products:5}

Requirements:
- 5-10 relevant keywords separated by commas
- Include category name and variations
- Include product type keywords
- Return ONLY the keywords, comma-separated, no explanations',
                'template_fr' => 'Générez des mots-clés SEO pour cette page de catégorie e-commerce.

Chemin de la catégorie : {category_breadcrumb}
Catégorie : {category_name}
Exemples de produits : {first_products:5}

Exigences :
- 5-10 mots-clés pertinents séparés par des virgules
- Inclure le nom de la catégorie et ses variations
- Inclure les mots-clés du type de produit
- Retourner UNIQUEMENT les mots-clés, séparés par des virgules, sans explications',
                'template_it' => 'Genera parole chiave SEO per questa pagina di categoria e-commerce.

Percorso categoria: {category_breadcrumb}
Categoria: {category_name}
Esempi di prodotti: {first_products:5}

Requisiti:
- 5-10 parole chiave rilevanti separate da virgole
- Includere il nome della categoria e le sue variazioni
- Includere parole chiave del tipo di prodotto
- Restituire SOLO le parole chiave, separate da virgole, senza spiegazioni',
            ],
            self::FIELD_LINK_REWRITE => [
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

        foreach ($defaultPrompts as $fieldType => $promptData) {
            // Insert main prompt template
            $result = Db::getInstance()->insert('mlcategoryai_prompt_template', [
                'id_shop' => $idShop,
                'name' => pSQL($promptData['name']),
                'field_type' => pSQL($fieldType),
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            if (!$result) {
                return false;
            }

            $idPromptTemplate = (int) Db::getInstance()->Insert_ID();

            // Insert language-specific prompts
            foreach ($languages as $lang) {
                // Select appropriate template based on language ISO code
                $isoCode = strtolower($lang['iso_code']);
                $templateKey = 'template_' . $isoCode;

                // Fall back to English if translation not available
                if (!isset($promptData[$templateKey])) {
                    $templateKey = 'template_en';
                }

                $result = Db::getInstance()->insert('mlcategoryai_prompt_template_lang', [
                    'id_prompt_template' => $idPromptTemplate,
                    'id_lang' => (int) $lang['id_lang'],
                    'prompt_template' => pSQL($promptData[$templateKey], true),
                ]);

                if (!$result) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Load the configuration form
     *
     * @return string
     */
    public function getContent()
    {
        $output = '';

        // Handle form submission
        if ((bool) Tools::isSubmit('submitMlcategoryaidescriptionModule') == true) {
            $this->postProcess();
            $output .= $this->displayConfirmation($this->l('Settings updated successfully.'));
        }

        // Header info template variables
        $this->context->smarty->assign([
            'module_dir' => $this->_path,
            'module_display_name' => $this->displayName,
            'module_description' => $this->description,
            'module_version' => $this->version,
            'documentation_url' => 'https://2win.agency/docs/mlcategoryaidescription',
            'support_url' => 'https://addons.prestashop.com/en/contact-us?id_product=YOUR_PRODUCT_ID',
            'rate_url' => 'https://addons.prestashop.com/en/ratings.php',
        ]);

        // Assign additional variables for configuration page
        $this->context->smarty->assign([
            'cron_url' => $this->getCronUrl(),
            'cron_enabled' => (bool) Configuration::get(self::CONFIG_CRON_ENABLED),
            'ajax_url' => $this->getAjaxUrl(),
            'ajax_token' => $this->getAjaxToken(),
            'languages' => Language::getLanguages(true),
            'categories' => $this->getCategoriesForSelect(),
            'categories_tree' => $this->getCategoriesTree(),
            'current_job' => $this->getCurrentRunningJob(),
            'pending_jobs' => $this->getAllPendingJobs(),
            'run_stats' => MlCategoryAiRunStats::getRecentRuns(10),
            'run_stats_aggregate' => MlCategoryAiRunStats::getAggregateStats(),
            'has_meta_keywords' => MlCategoryAiGenerator::hasMetaKeywordsSupport(),
        ]);

        // Add header info panel FIRST
        $output .= $this->context->smarty->fetch($this->local_path . 'views/templates/admin/header_info.tpl');

        // Add job status dashboard (shows pending jobs and cron setup)
        $output .= $this->context->smarty->fetch($this->local_path . 'views/templates/admin/job_status.tpl');

        // Add module-specific templates
        $output .= $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');

        // Add API benchmark tool
        $output .= $this->context->smarty->fetch($this->local_path . 'views/templates/admin/api_benchmark.tpl');

        // Add performance stats panel
        $output .= $this->context->smarty->fetch($this->local_path . 'views/templates/admin/performance_stats.tpl');

        return $output . $this->renderForm();
    }

    /**
     * Get cron URL with security token
     *
     * @return string
     */
    public function getCronUrl()
    {
        $token = Configuration::get(self::CONFIG_CRON_TOKEN);

        return $this->context->link->getModuleLink(
            $this->name,
            'cron',
            ['token' => $token],
            true
        );
    }

    /**
     * Get AJAX URL for batch processing (uses Admin controller)
     *
     * @return string
     */
    public function getAjaxUrl()
    {
        return $this->context->link->getAdminLink('AdminMlCategoryAiAjax');
    }

    /**
     * Generate a secure AJAX token for the current admin session
     * Note: Using admin controller, token is handled by PrestaShop's built-in security
     *
     * @return string
     */
    public function getAjaxToken()
    {
        // Admin controller uses PrestaShop's built-in token from getAdminLink
        return Tools::getAdminTokenLite('AdminMlCategoryAiAjax');
    }

    /**
     * Get categories for select input (flat list with hierarchy info)
     *
     * @return array
     */
    protected function getCategoriesForSelect()
    {
        $rootCategory = Category::getRootCategory();
        $categories = Category::getNestedCategories(
            $rootCategory->id,
            $this->context->language->id,
            true
        );

        $result = [];
        $this->flattenCategoryTree($categories, $result, 0, null);

        return $result;
    }

    /**
     * Get nested categories tree structure
     *
     * @return array
     */
    protected function getCategoriesTree()
    {
        $rootCategory = Category::getRootCategory();
        $categories = Category::getNestedCategories(
            $rootCategory->id,
            $this->context->language->id,
            true
        );

        return $this->buildCategoryTreeArray($categories);
    }

    /**
     * Build tree array from nested categories
     *
     * @param array $categories
     *
     * @return array
     */
    protected function buildCategoryTreeArray($categories)
    {
        $result = [];

        foreach ($categories as $category) {
            $node = [
                'id_category' => (int) $category['id_category'],
                'name' => $category['name'],
                'children' => [],
            ];

            if (!empty($category['children'])) {
                $node['children'] = $this->buildCategoryTreeArray($category['children']);
            }

            $result[] = $node;
        }

        return $result;
    }

    /**
     * Flatten category tree for select
     *
     * @param array $categories
     * @param array $result
     * @param int $level
     * @param int|null $parentId
     */
    protected function flattenCategoryTree($categories, &$result, $level = 0, $parentId = null)
    {
        foreach ($categories as $category) {
            $result[] = [
                'id_category' => (int) $category['id_category'],
                'name' => str_repeat('— ', $level) . $category['name'],
                'name_plain' => $category['name'],
                'level' => $level,
                'id_parent' => $parentId,
            ];

            if (!empty($category['children'])) {
                $this->flattenCategoryTree($category['children'], $result, $level + 1, (int) $category['id_category']);
            }
        }
    }

    /**
     * Get current running job if any
     *
     * @return array|null
     */
    protected function getCurrentRunningJob()
    {
        $result = Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . 'mlcategoryai_job_queue`
            WHERE `status` IN ("pending", "running", "paused")
            AND `id_shop` = ' . (int) $this->context->shop->id . '
            ORDER BY `created_at` DESC'
        );

        return $result ?: null;
    }

    /**
     * Get all pending/running/paused jobs
     *
     * @return array
     */
    protected function getAllPendingJobs()
    {
        $results = Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'mlcategoryai_job_queue`
            WHERE `status` IN ("pending", "running", "paused")
            AND `id_shop` = ' . (int) $this->context->shop->id . '
            ORDER BY `created_at` DESC'
        );

        if ($results) {
            foreach ($results as &$job) {
                // Check if job is stuck (running but no updates for 5+ minutes)
                $lastUpdate = strtotime($job['updated_at']);
                $job['is_stuck'] = ($job['status'] === 'running' && (time() - $lastUpdate) > 300);
            }
        }

        return $results ?: [];
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     *
     * @return string
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitMlcategoryaidescriptionModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm([
            $this->getConfigFormApi(),
            $this->getConfigFormGeneration(),
            $this->getConfigFormPrompts(),
            $this->getConfigFormCron(),
        ]);
    }

    /**
     * API settings form
     *
     * @return array
     */
    protected function getConfigFormApi()
    {
        return [
            'form' => [
                'legend' => [
                    'title' => $this->l('API Configuration'),
                    'icon' => 'icon-cloud',
                ],
                'input' => [
                    [
                        'type' => 'select',
                        'label' => $this->l('API Provider'),
                        'name' => self::CONFIG_API_PROVIDER,
                        'desc' => $this->l('Select the AI API provider.'),
                        'options' => [
                            'query' => [
                                ['id' => self::PROVIDER_OPENAI, 'name' => 'OpenAI'],
                                ['id' => self::PROVIDER_AZURE, 'name' => 'Azure OpenAI'],
                                ['id' => self::PROVIDER_CUSTOM, 'name' => $this->l('Custom Endpoint')],
                            ],
                            'id' => 'id',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('API Key'),
                        'name' => self::CONFIG_API_KEY,
                        'prefix' => '<i class="icon icon-key"></i>',
                        'desc' => $this->l('Enter your API key. It will be stored securely.'),
                        'class' => 'fixed-width-xxl',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('API Endpoint'),
                        'name' => self::CONFIG_API_ENDPOINT,
                        'prefix' => '<i class="icon icon-link"></i>',
                        'desc' => $this->l('API endpoint URL. Default: https://api.openai.com/v1'),
                        'class' => 'fixed-width-xxl',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Model'),
                        'name' => self::CONFIG_API_MODEL,
                        'prefix' => '<i class="icon icon-cog"></i>',
                        'desc' => $this->l('AI model to use (e.g., gpt-4o-mini, gpt-4, gpt-3.5-turbo).'),
                        'class' => 'fixed-width-xl',
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ],
            ],
        ];
    }

    /**
     * Generation settings form
     *
     * @return array
     */
    protected function getConfigFormGeneration()
    {
        return [
            'form' => [
                'legend' => [
                    'title' => $this->l('Generation Settings'),
                    'icon' => 'icon-magic',
                ],
                'input' => [
                    [
                        'type' => 'select',
                        'label' => $this->l('Write Mode'),
                        'name' => self::CONFIG_WRITE_MODE,
                        'desc' => $this->l('How to handle existing content.'),
                        'options' => [
                            'query' => [
                                [
                                    'id' => self::WRITE_MODE_FILL_MISSING,
                                    'name' => $this->l('Fill missing only - Keep existing content'),
                                ],
                                [
                                    'id' => self::WRITE_MODE_OVERWRITE,
                                    'name' => $this->l('Overwrite - Replace all content'),
                                ],
                            ],
                            'id' => 'id',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Batch Size'),
                        'name' => self::CONFIG_BATCH_SIZE,
                        'desc' => $this->l('Number of items to process per batch (recommended: 3-10). With parallel enabled, all items run simultaneously.'),
                        'class' => 'fixed-width-sm',
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Parallel API Requests'),
                        'name' => self::CONFIG_PARALLEL_REQUESTS,
                        'desc' => $this->l('Process batch items simultaneously using curl_multi. Significantly faster but uses more API quota. Disable if you hit rate limits.'),
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'parallel_on',
                                'value' => 1,
                                'label' => $this->l('Enabled'),
                            ],
                            [
                                'id' => 'parallel_off',
                                'value' => 0,
                                'label' => $this->l('Disabled'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Max Tokens'),
                        'name' => self::CONFIG_MAX_TOKENS,
                        'desc' => $this->l('Maximum tokens per API request. Set to 0 to let the model decide (recommended for newer models like gpt-5-nano).'),
                        'class' => 'fixed-width-sm',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Temperature'),
                        'name' => self::CONFIG_TEMPERATURE,
                        'desc' => $this->l('AI creativity (0.0 = focused, 2.0 = creative). Recommended: 0.7'),
                        'class' => 'fixed-width-sm',
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ],
            ],
        ];
    }

    /**
     * Prompt templates settings form
     *
     * @return array
     */
    protected function getConfigFormPrompts()
    {
        $languages = Language::getLanguages(true);
        $inputs = [];

        // Field type selector
        $inputs[] = [
            'type' => 'html',
            'name' => 'prompt_intro',
            'html_content' => $this->getPromptEditorHtml(),
        ];

        return [
            'form' => [
                'legend' => [
                    'title' => $this->l('Prompt Templates'),
                    'icon' => 'icon-file-text-o',
                ],
                'description' => $this->l('Customize the prompts used to generate content for each field type.'),
                'input' => $inputs,
            ],
        ];
    }

    /**
     * Get prompt editor HTML from template
     *
     * @return string
     */
    protected function getPromptEditorHtml()
    {
        $languages = Language::getLanguages(true);
        $fieldTypes = [
            self::FIELD_DESCRIPTION => $this->l('Description'),
            self::FIELD_META_TITLE => $this->l('Meta Title'),
            self::FIELD_META_DESCRIPTION => $this->l('Meta Description'),
            self::FIELD_LINK_REWRITE => $this->l('Friendly URL'),
        ];

        // Add meta_keywords only if supported (PS < 9.0)
        if (MlCategoryAiGenerator::hasMetaKeywordsSupport()) {
            // Insert after meta_description for proper ordering
            $newFieldTypes = [];
            foreach ($fieldTypes as $key => $value) {
                $newFieldTypes[$key] = $value;
                if ($key === self::FIELD_META_DESCRIPTION) {
                    $newFieldTypes[self::FIELD_META_KEYWORDS] = $this->l('Meta Keywords');
                }
            }
            $fieldTypes = $newFieldTypes;
        }

        // Load current prompts from database
        $prompts = $this->loadPromptTemplates();

        $this->context->smarty->assign([
            'languages' => $languages,
            'field_types' => $fieldTypes,
            'prompts' => $prompts,
            'ajax_url' => $this->getAjaxUrl(),
        ]);

        return $this->context->smarty->fetch($this->local_path . 'views/templates/admin/prompt_editor.tpl');
    }

    /**
     * Load all prompt templates from database
     *
     * @return array
     */
    protected function loadPromptTemplates()
    {
        $idShop = (int) $this->context->shop->id;
        $prompts = [];

        $sql = 'SELECT pt.*, ptl.id_lang, ptl.prompt_template
                FROM `' . _DB_PREFIX_ . 'mlcategoryai_prompt_template` pt
                LEFT JOIN `' . _DB_PREFIX_ . 'mlcategoryai_prompt_template_lang` ptl
                    ON pt.id_prompt_template = ptl.id_prompt_template
                WHERE pt.id_shop = ' . $idShop . '
                AND pt.is_active = 1
                ORDER BY pt.field_type, ptl.id_lang';

        $results = Db::getInstance()->executeS($sql);

        foreach ($results as $row) {
            $fieldType = $row['field_type'];
            $idLang = (int) $row['id_lang'];

            if (!isset($prompts[$fieldType])) {
                $prompts[$fieldType] = [];
            }

            $prompts[$fieldType][$idLang] = $row['prompt_template'];
        }

        return $prompts;
    }

    /**
     * Cron settings form
     *
     * @return array
     */
    protected function getConfigFormCron()
    {
        return [
            'form' => [
                'legend' => [
                    'title' => $this->l('Cron Settings'),
                    'icon' => 'icon-clock-o',
                ],
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->l('Enable Cron Processing'),
                        'name' => self::CONFIG_CRON_ENABLED,
                        'is_bool' => true,
                        'desc' => $this->l('Allow background processing via cron.'),
                        'values' => [
                            ['id' => 'cron_on', 'value' => true, 'label' => $this->l('Enabled')],
                            ['id' => 'cron_off', 'value' => false, 'label' => $this->l('Disabled')],
                        ],
                    ],
                    [
                        'type' => 'html',
                        'label' => $this->l('Cron URL'),
                        'name' => 'cron_url_display',
                        'html_content' => $this->getCronUrlHtml(),
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ],
            ],
        ];
    }

    /**
     * Get cron URL HTML from template
     *
     * @return string
     */
    protected function getCronUrlHtml()
    {
        $this->context->smarty->assign([
            'cron_url' => $this->getCronUrl(),
            'cron_label' => $this->l('Cron URL:'),
            'cron_desc' => $this->l('Add this URL to your cron jobs to enable background processing.'),
        ]);

        return $this->context->smarty->fetch($this->local_path . 'views/templates/admin/cron_url.tpl');
    }

    /**
     * Set values for the inputs.
     *
     * @return array
     */
    protected function getConfigFormValues()
    {
        return [
            self::CONFIG_LIVE_MODE => Configuration::get(self::CONFIG_LIVE_MODE),
            self::CONFIG_API_PROVIDER => Configuration::get(self::CONFIG_API_PROVIDER),
            self::CONFIG_API_KEY => $this->decryptApiKey(Configuration::get(self::CONFIG_API_KEY)),
            self::CONFIG_API_ENDPOINT => Configuration::get(self::CONFIG_API_ENDPOINT),
            self::CONFIG_API_MODEL => Configuration::get(self::CONFIG_API_MODEL),
            self::CONFIG_BATCH_SIZE => Configuration::get(self::CONFIG_BATCH_SIZE),
            self::CONFIG_PARALLEL_REQUESTS => Configuration::get(self::CONFIG_PARALLEL_REQUESTS),
            self::CONFIG_WRITE_MODE => Configuration::get(self::CONFIG_WRITE_MODE),
            self::CONFIG_MAX_TOKENS => Configuration::get(self::CONFIG_MAX_TOKENS),
            self::CONFIG_TEMPERATURE => Configuration::get(self::CONFIG_TEMPERATURE),
            self::CONFIG_REQUEST_DELAY => Configuration::get(self::CONFIG_REQUEST_DELAY),
            self::CONFIG_CRON_ENABLED => Configuration::get(self::CONFIG_CRON_ENABLED),
        ];
    }

    /**
     * Save form data.
     *
     * @return void
     */
    protected function postProcess()
    {
        // Handle API key encryption
        $apiKey = Tools::getValue(self::CONFIG_API_KEY);
        if (!empty($apiKey)) {
            Configuration::updateValue(self::CONFIG_API_KEY, $this->encryptApiKey($apiKey));
        }

        // Save other values
        $configKeys = [
            self::CONFIG_LIVE_MODE,
            self::CONFIG_API_PROVIDER,
            self::CONFIG_API_ENDPOINT,
            self::CONFIG_API_MODEL,
            self::CONFIG_BATCH_SIZE,
            self::CONFIG_PARALLEL_REQUESTS,
            self::CONFIG_WRITE_MODE,
            self::CONFIG_MAX_TOKENS,
            self::CONFIG_TEMPERATURE,
            self::CONFIG_REQUEST_DELAY,
            self::CONFIG_CRON_ENABLED,
        ];

        foreach ($configKeys as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    /**
     * Encrypt API key for secure storage
     *
     * @param string $apiKey
     *
     * @return string
     */
    public function encryptApiKey($apiKey)
    {
        if (empty($apiKey)) {
            return '';
        }

        return base64_encode(openssl_encrypt(
            $apiKey,
            'AES-256-CBC',
            _COOKIE_KEY_,
            0,
            substr(md5(_COOKIE_KEY_), 0, 16)
        ));
    }

    /**
     * Decrypt API key for use
     *
     * @param string $encryptedKey
     *
     * @return string
     */
    public function decryptApiKey($encryptedKey)
    {
        if (empty($encryptedKey)) {
            return '';
        }

        $decrypted = openssl_decrypt(
            base64_decode($encryptedKey),
            'AES-256-CBC',
            _COOKIE_KEY_,
            0,
            substr(md5(_COOKIE_KEY_), 0, 16)
        );

        return $decrypted !== false ? $decrypted : '';
    }

    /**
     * Get decrypted API key
     *
     * @return string
     */
    public function getApiKey()
    {
        return $this->decryptApiKey(Configuration::get(self::CONFIG_API_KEY));
    }

    /**
     * Add the CSS & JavaScript files you want to be loaded in the BO.
     *
     * @return void
     */
    public function hookDisplayBackOfficeHeader()
    {
        if (Tools::getValue('configure') == $this->name) {
            // Add version parameter to bust CDN/proxy caches (e.g., Cloudflare)
            $cacheBuster = '?v=' . $this->version;
            $this->context->controller->addJS($this->_path . 'views/js/back.js' . $cacheBuster);
            $this->context->controller->addCSS($this->_path . 'views/css/back.css' . $cacheBuster);

            // Add AJAX configuration for JavaScript
            Media::addJsDef([
                'mlcategoryai_ajax_url' => $this->getAjaxUrl(),
                'mlcategoryai_token' => $this->getAjaxToken(),
            ]);
        }
    }
}
