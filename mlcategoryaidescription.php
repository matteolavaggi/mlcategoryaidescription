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
require_once __DIR__ . '/classes/MlCategoryAiTranslator.php';

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
        $this->version = '1.8.0';
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
                'template_en' => 'Write a compelling and SEO-optimized category description for an e-commerce website.

CONTEXT INFORMATION:
- Category path: {category_breadcrumb}
- Category name: {category_name}
- Category URL: {category_url}
- Website: {site_name} ({shop_url})
- Website description: {site_description}
- Sample products in this category (for context only): {first_products:10}
- Current category description: {category_description}

PRELIMINARY ANALYSIS (perform mentally before writing):
1. From the category path ({category_breadcrumb}), identify if there is a parent brand (e.g., first level = brand) and keep it in mind as text context
2. From the sample products, extract the functional MACRO-TYPES present (e.g., "levers", "guards", "mounts") — these should be mentioned in the text
3. For each identified macro-type, build a natural long tail keyword combining type + category name (e.g., "adjustable levers Caponord 1200", "navigator mount Aprilia Caponord")
4. Identify the main use context (e.g., touring, racing, protection, ergonomics) and use it to build the tone and benefits

IMPORTANT GUIDELINES:
The product list is provided ONLY to understand what TYPE of products the category contains. Use it to identify general types and typical use cases.

DO NOT:
- Mention specific product names, models or SKUs from the list
- Reference individual items with specific details
- Ignore the brand or parent category context if present in the breadcrumb
- Write generically without citing the actual functional product types
- Use self-referential headings about the site (e.g., "Why choose [site name]")
- Use weak formulas in the first H2 like "what you find", "what we offer", "discover"
- Reference specific site features (filters, advanced search, category navigation) that may not exist

DO:
- Name the product macro-types identified from analysis generically but with natural long tail keywords (type + category name)
- If the breadcrumb indicates a parent brand, present it in the first paragraph as selection context
- The first H2 must contain parent brand + category name as main keyword (e.g., "Evotech Performance Accessories for Aprilia Caponord 1200")
- The second H2 must be action/exploration oriented, not brand-focused (e.g., "Quality and compatibility for [category]", "Choose the right component for [category]")
- Include natural long tail keywords based on category name and breadcrumb directly in macro-type bullet points
- Focus on the customer search intent for that specific category
- The first paragraph must contain the full category name with context

REQUIRED STRUCTURE:
- Paragraph 1: introduction with main keyword + parent brand/category context + use contexts
- First H2: [parent brand] + [category name] — followed by bullet points with macro-types and naturally integrated long tail keywords
- Second H2: soft call to action oriented to exploration (NOT self-referential, NO site feature references)
- Closing: 1-2 sentences recalling parent brand + category + main benefit

REQUIREMENTS:
- Length: 250-350 words
- Natural, SEO-friendly language
- Engaging and professional tone
- Do not mention prices or specific promotions
- Use H2 headings for subsections
- Long tail keywords in bullet points must be naturally integrated, not forced',
                'template_fr' => 'Rédigez une description de catégorie captivante et optimisée SEO pour un site e-commerce.

INFORMATIONS CONTEXTUELLES :
- Chemin de la catégorie : {category_breadcrumb}
- Nom de la catégorie : {category_name}
- URL de la catégorie : {category_url}
- Site web : {site_name} ({shop_url})
- Description du site : {site_description}
- Exemples de produits dans cette catégorie (contexte uniquement) : {first_products:10}
- Description actuelle de la catégorie : {category_description}

ANALYSE PRÉLIMINAIRE (à effectuer mentalement avant d\'écrire) :
1. À partir du chemin de catégorie ({category_breadcrumb}), identifiez s\'il existe une marque parente (ex. premier niveau = marque) et gardez-la en contexte
2. À partir des produits exemples, extrayez les MACRO-TYPES fonctionnels présents (ex. "leviers", "protections", "supports") — ceux-ci doivent être mentionnés dans le texte
3. Pour chaque macro-type identifié, construisez un mot-clé longue traîne naturel combinant type + nom de catégorie
4. Identifiez le contexte d\'utilisation principal (ex. touring, racing, protection, ergonomie) et utilisez-le pour construire le ton et les avantages

DIRECTIVES IMPORTANTES :
La liste de produits est fournie UNIQUEMENT pour comprendre quel TYPE de produits contient la catégorie. Utilisez-la pour identifier les types généraux et cas d\'utilisation typiques.

NE PAS FAIRE :
- Mentionner des noms de produits spécifiques, modèles ou SKU de la liste
- Faire référence à des articles individuels avec des détails spécifiques
- Ignorer le contexte de marque ou catégorie parente si présent dans le fil d\'Ariane
- Écrire de manière générique sans citer les types fonctionnels réels des produits
- Utiliser des titres autoréférentiels sur le site (ex. "Pourquoi choisir [nom du site]")
- Utiliser des formules faibles dans le premier H2 comme "ce que vous trouvez", "ce que nous offrons", "découvrez"
- Faire référence à des fonctionnalités spécifiques du site qui pourraient ne pas exister

À FAIRE :
- Nommer les macro-types de produits identifiés de manière générique mais avec des mots-clés longue traîne naturels
- Si le fil d\'Ariane indique une marque parente, la présenter dans le premier paragraphe comme contexte
- Le premier H2 doit contenir marque parente + nom de catégorie comme mot-clé principal
- Le second H2 doit être orienté action/exploration, pas centré sur la marque
- Inclure des mots-clés longue traîne naturels directement dans les points des macro-types
- Se concentrer sur l\'intention de recherche du client pour cette catégorie spécifique
- Le premier paragraphe doit contenir le nom complet de la catégorie avec contexte

STRUCTURE OBLIGATOIRE :
- Paragraphe 1 : introduction avec mot-clé principal + contexte marque/catégorie parente + contextes d\'utilisation
- Premier H2 : [marque parente] + [nom catégorie] — suivi de points avec macro-types et mots-clés longue traîne intégrés
- Second H2 : appel à l\'action soft orienté exploration (PAS autoréférentiel, PAS de références aux fonctionnalités du site)
- Conclusion : 1-2 phrases rappelant marque parente + catégorie + avantage principal

EXIGENCES :
- Longueur : 250-350 mots
- Langage naturel et optimisé SEO
- Ton engageant et professionnel
- Ne pas mentionner les prix ou promotions
- Utiliser des titres H2 pour les sous-sections
- Les mots-clés longue traîne doivent être intégrés naturellement',
                'template_it' => 'Scrivi una descrizione di categoria accattivante e ottimizzata SEO per un sito e-commerce.

INFORMAZIONI DI CONTESTO:
- Percorso categoria: {category_breadcrumb}
- Nome categoria: {category_name}
- URL categoria: {category_url}
- Sito web: {site_name} ({shop_url})
- Descrizione del sito: {site_description}
- Prodotti di esempio in questa categoria (solo per contesto): {first_products:10}
- Descrizione attuale della categoria: {category_description}

ANALISI PRELIMINARE (esegui mentalmente prima di scrivere):
1. Dal percorso categoria ({category_breadcrumb}), identifica se esiste un brand padre (es. primo livello = brand) e tienilo presente come contesto del testo
2. Dai prodotti di esempio, estrai le MACRO-TIPOLOGIE funzionali presenti (es. "leve", "protezioni", "supporti") — queste vanno nominate nel testo
3. Per ogni macro-tipologia identificata, costruisci una keyword long tail naturale combinando tipologia + nome categoria (es. "leve regolabili Caponord 1200", "supporto navigatore Aprilia Caponord")
4. Identifica il contesto d\'uso principale (es. touring, racing, protezione, ergonomia) e usalo per costruire il tono e i benefit

LINEE GUIDA IMPORTANTI:
L\'elenco prodotti è fornito SOLO per capire che TIPO di prodotti contiene la categoria. Usalo per identificare le tipologie generali e i casi d\'uso tipici.

NON FARE:
- Menzionare nomi di prodotti specifici, modelli o SKU dalla lista
- Riferirsi a singoli articoli con dettagli specifici
- Ignorare il contesto del brand o categoria padre se presente nel breadcrumb
- Scrivere in modo generico senza citare le tipologie funzionali reali dei prodotti
- Usare heading autoreferenziali sul sito (es. "Perché scegliere [nome sito]")
- Usare formule deboli nel primo H2 come "cosa trovi", "cosa offriamo", "scopri"
- Fare riferimento a funzionalità specifiche del sito (filtri, ricerca avanzata, navigazione per categoria) che potrebbero non essere presenti

FARE:
- Nominare le macro-tipologie di prodotti identificate dall\'analisi in modo generico ma con keyword long tail naturali (tipologia + nome categoria)
- Se il breadcrumb indica un brand padre, presentarlo nel primo paragrafo come contesto della selezione
- Il primo H2 deve contenere brand padre + nome categoria come keyword principale (es. "Accessori Evotech Performance per Aprilia Caponord 1200")
- Il secondo H2 deve essere orientato all\'azione/esplorazione, non al brand (es. "Qualità e compatibilità per [categoria]", "Scegli il componente giusto per [categoria]")
- Includere keyword long tail naturali basate su nome categoria e breadcrumb direttamente nei bullet point delle macro-tipologie
- Concentrarsi sull\'intento di ricerca del cliente per quella specifica categoria
- Il primo paragrafo deve contenere il nome categoria completo con contesto

STRUTTURA OBBLIGATORIA:
- Paragrafo 1: introduzione con keyword principale + contesto brand/categoria padre + contesti d\'uso
- H2 primo: [brand padre] + [nome categoria] — seguito da bullet point con macro-tipologie e keyword long tail integrate naturalmente
- H2 secondo: call to action soft orientata all\'esplorazione (NON autoreferenziale, NON riferimenti a funzionalità del sito)
- Chiusura: 1-2 frasi che richiamano brand padre + categoria + beneficio principale

REQUISITI:
- Lunghezza: 250-350 parole
- Linguaggio naturale e SEO-friendly
- Tono coinvolgente e professionale
- Non menzionare prezzi o promozioni specifiche
- Usa heading H2 per le sottosezioni
- Le keyword long tail nei bullet point devono essere integrate naturalmente, non forzate',
                'template_de' => 'Schreiben Sie eine ansprechende und SEO-optimierte Kategoriebeschreibung für eine E-Commerce-Website.

KONTEXTINFORMATIONEN:
- Kategoriepfad: {category_breadcrumb}
- Kategoriename: {category_name}
- Kategorie-URL: {category_url}
- Website: {site_name} ({shop_url})
- Website-Beschreibung: {site_description}
- Beispielprodukte in dieser Kategorie (nur als Kontext): {first_products:10}
- Aktuelle Kategoriebeschreibung: {category_description}

VORANALYSE (mental durchführen vor dem Schreiben):
1. Aus dem Kategoriepfad ({category_breadcrumb}) identifizieren, ob eine übergeordnete Marke existiert (z.B. erste Ebene = Marke) und als Textkontext behalten
2. Aus den Beispielprodukten die vorhandenen funktionalen MAKRO-TYPEN extrahieren (z.B. "Hebel", "Schutzvorrichtungen", "Halterungen") — diese sollten im Text erwähnt werden
3. Für jeden identifizierten Makro-Typ ein natürliches Long-Tail-Keyword erstellen, das Typ + Kategoriename kombiniert
4. Den Hauptnutzungskontext identifizieren (z.B. Touring, Racing, Schutz, Ergonomie) und für Ton und Vorteile nutzen

WICHTIGE RICHTLINIEN:
Die Produktliste dient NUR dazu zu verstehen, welche ART von Produkten die Kategorie enthält. Nutzen Sie sie zur Identifikation allgemeiner Typen und typischer Anwendungsfälle.

NICHT TUN:
- Spezifische Produktnamen, Modelle oder SKUs aus der Liste erwähnen
- Auf einzelne Artikel mit spezifischen Details verweisen
- Den Marken- oder übergeordneten Kategoriekontext ignorieren, wenn im Breadcrumb vorhanden
- Generisch schreiben ohne die tatsächlichen funktionalen Produkttypen zu nennen
- Selbstreferenzielle Überschriften über die Website verwenden (z.B. "Warum [Seitenname] wählen")
- Schwache Formeln in der ersten H2 verwenden wie "was Sie finden", "was wir anbieten", "entdecken"
- Auf spezifische Website-Funktionen verweisen, die möglicherweise nicht existieren

TUN:
- Die aus der Analyse identifizierten Produkt-Makro-Typen generisch aber mit natürlichen Long-Tail-Keywords benennen
- Wenn der Breadcrumb eine übergeordnete Marke anzeigt, diese im ersten Absatz als Auswahlkontext präsentieren
- Die erste H2 muss übergeordnete Marke + Kategoriename als Haupt-Keyword enthalten
- Die zweite H2 muss aktions-/explorationssorientiert sein, nicht markenfokussiert
- Natürliche Long-Tail-Keywords direkt in den Makro-Typ-Aufzählungspunkten einbinden
- Sich auf die Suchabsicht des Kunden für diese spezifische Kategorie konzentrieren
- Der erste Absatz muss den vollständigen Kategorienamen mit Kontext enthalten

PFLICHTSTRUKTUR:
- Absatz 1: Einführung mit Haupt-Keyword + übergeordneter Marken-/Kategoriekontext + Nutzungskontexte
- Erste H2: [übergeordnete Marke] + [Kategoriename] — gefolgt von Aufzählungspunkten mit Makro-Typen und natürlich integrierten Long-Tail-Keywords
- Zweite H2: Soft-Call-to-Action orientiert an Exploration (NICHT selbstreferenziell, KEINE Website-Funktionsverweise)
- Abschluss: 1-2 Sätze die übergeordnete Marke + Kategorie + Hauptvorteil aufgreifen

ANFORDERUNGEN:
- Länge: 250-350 Wörter
- Natürliche, SEO-freundliche Sprache
- Ansprechender, professioneller Ton
- Keine Preise oder spezifische Aktionen erwähnen
- H2-Überschriften für Unterabschnitte verwenden
- Long-Tail-Keywords müssen natürlich integriert sein, nicht erzwungen',
                'template_es' => 'Escribe una descripción de categoría atractiva y optimizada para SEO para un sitio e-commerce.

INFORMACIÓN DE CONTEXTO:
- Ruta de categoría: {category_breadcrumb}
- Nombre de categoría: {category_name}
- URL de categoría: {category_url}
- Sitio web: {site_name} ({shop_url})
- Descripción del sitio: {site_description}
- Productos de ejemplo en esta categoría (solo contexto): {first_products:10}
- Descripción actual de la categoría: {category_description}

ANÁLISIS PRELIMINAR (realizar mentalmente antes de escribir):
1. Desde la ruta de categoría ({category_breadcrumb}), identificar si existe una marca padre (ej. primer nivel = marca) y tenerla presente como contexto del texto
2. De los productos de ejemplo, extraer los MACRO-TIPOS funcionales presentes (ej. "palancas", "protecciones", "soportes") — estos deben mencionarse en el texto
3. Para cada macro-tipo identificado, construir una keyword long tail natural combinando tipo + nombre de categoría
4. Identificar el contexto de uso principal (ej. touring, racing, protección, ergonomía) y usarlo para construir el tono y beneficios

DIRECTRICES IMPORTANTES:
La lista de productos se proporciona SOLO para entender qué TIPO de productos contiene la categoría. Úsala para identificar tipos generales y casos de uso típicos.

NO HACER:
- Mencionar nombres de productos específicos, modelos o SKUs de la lista
- Referirse a artículos individuales con detalles específicos
- Ignorar el contexto de marca o categoría padre si está presente en el breadcrumb
- Escribir de forma genérica sin citar los tipos funcionales reales de productos
- Usar encabezados autorreferenciales sobre el sitio (ej. "Por qué elegir [nombre sitio]")
- Usar fórmulas débiles en el primer H2 como "qué encuentras", "qué ofrecemos", "descubre"
- Hacer referencia a funcionalidades específicas del sitio que podrían no existir

HACER:
- Nombrar los macro-tipos de productos identificados del análisis de forma genérica pero con keywords long tail naturales
- Si el breadcrumb indica una marca padre, presentarla en el primer párrafo como contexto de la selección
- El primer H2 debe contener marca padre + nombre de categoría como keyword principal
- El segundo H2 debe estar orientado a la acción/exploración, no a la marca
- Incluir keywords long tail naturales directamente en los bullet points de macro-tipos
- Concentrarse en la intención de búsqueda del cliente para esa categoría específica
- El primer párrafo debe contener el nombre completo de la categoría con contexto

ESTRUCTURA OBLIGATORIA:
- Párrafo 1: introducción con keyword principal + contexto marca/categoría padre + contextos de uso
- Primer H2: [marca padre] + [nombre categoría] — seguido de bullet points con macro-tipos y keywords long tail integradas naturalmente
- Segundo H2: call to action soft orientado a la exploración (NO autorreferencial, NO referencias a funcionalidades del sitio)
- Cierre: 1-2 frases que recuerden marca padre + categoría + beneficio principal

REQUISITOS:
- Longitud: 250-350 palabras
- Lenguaje natural y optimizado para SEO
- Tono atractivo y profesional
- No mencionar precios ni promociones específicas
- Usar encabezados H2 para las subsecciones
- Las keywords long tail deben integrarse naturalmente, no forzadas',
                'template_pt' => 'Escreva uma descrição de categoria atraente e otimizada para SEO para um site e-commerce.

INFORMAÇÕES DE CONTEXTO:
- Caminho da categoria: {category_breadcrumb}
- Nome da categoria: {category_name}
- URL da categoria: {category_url}
- Site: {site_name} ({shop_url})
- Descrição do site: {site_description}
- Produtos de exemplo nesta categoria (apenas contexto): {first_products:10}
- Descrição atual da categoria: {category_description}

ANÁLISE PRELIMINAR (realizar mentalmente antes de escrever):
1. Do caminho da categoria ({category_breadcrumb}), identificar se existe uma marca pai (ex. primeiro nível = marca) e mantê-la como contexto do texto
2. Dos produtos de exemplo, extrair os MACRO-TIPOS funcionais presentes (ex. "alavancas", "proteções", "suportes") — estes devem ser mencionados no texto
3. Para cada macro-tipo identificado, construir uma keyword long tail natural combinando tipo + nome da categoria
4. Identificar o contexto de uso principal (ex. touring, racing, proteção, ergonomia) e usá-lo para construir o tom e benefícios

DIRETRIZES IMPORTANTES:
A lista de produtos é fornecida APENAS para entender que TIPO de produtos a categoria contém. Use-a para identificar tipos gerais e casos de uso típicos.

NÃO FAZER:
- Mencionar nomes de produtos específicos, modelos ou SKUs da lista
- Referir-se a itens individuais com detalhes específicos
- Ignorar o contexto de marca ou categoria pai se presente no breadcrumb
- Escrever de forma genérica sem citar os tipos funcionais reais dos produtos
- Usar títulos autorreferenciais sobre o site (ex. "Por que escolher [nome do site]")
- Usar fórmulas fracas no primeiro H2 como "o que você encontra", "o que oferecemos", "descubra"
- Fazer referência a funcionalidades específicas do site que podem não existir

FAZER:
- Nomear os macro-tipos de produtos identificados da análise de forma genérica mas com keywords long tail naturais
- Se o breadcrumb indicar uma marca pai, apresentá-la no primeiro parágrafo como contexto da seleção
- O primeiro H2 deve conter marca pai + nome da categoria como keyword principal
- O segundo H2 deve ser orientado à ação/exploração, não à marca
- Incluir keywords long tail naturais diretamente nos bullet points dos macro-tipos
- Concentrar-se na intenção de busca do cliente para aquela categoria específica
- O primeiro parágrafo deve conter o nome completo da categoria com contexto

ESTRUTURA OBRIGATÓRIA:
- Parágrafo 1: introdução com keyword principal + contexto marca/categoria pai + contextos de uso
- Primeiro H2: [marca pai] + [nome categoria] — seguido de bullet points com macro-tipos e keywords long tail integradas naturalmente
- Segundo H2: call to action soft orientado à exploração (NÃO autorreferencial, NÃO referências a funcionalidades do site)
- Fechamento: 1-2 frases que relembrem marca pai + categoria + benefício principal

REQUISITOS:
- Comprimento: 250-350 palavras
- Linguagem natural e otimizada para SEO
- Tom envolvente e profissional
- Não mencionar preços ou promoções específicas
- Usar títulos H2 para as subseções
- As keywords long tail devem ser integradas naturalmente, não forçadas',
                'template_nl' => 'Schrijf een aantrekkelijke en SEO-geoptimaliseerde categoriebeschrijving voor een e-commerce website.

CONTEXTINFORMATIE:
- Categoriepad: {category_breadcrumb}
- Categorienaam: {category_name}
- Categorie-URL: {category_url}
- Website: {site_name} ({shop_url})
- Website beschrijving: {site_description}
- Voorbeeldproducten in deze categorie (alleen context): {first_products:10}
- Huidige categoriebeschrijving: {category_description}

VOORAFGAANDE ANALYSE (mentaal uitvoeren voor het schrijven):
1. Uit het categoriepad ({category_breadcrumb}) identificeren of er een bovenliggend merk bestaat (bijv. eerste niveau = merk) en dit als tekstcontext onthouden
2. Uit de voorbeeldproducten de aanwezige functionele MACRO-TYPES extraheren (bijv. "hendels", "beschermingen", "houders") — deze moeten in de tekst genoemd worden
3. Voor elk geïdentificeerd macro-type een natuurlijk long tail keyword bouwen dat type + categorienaam combineert
4. De belangrijkste gebruikscontext identificeren (bijv. touring, racing, bescherming, ergonomie) en gebruiken voor toon en voordelen

BELANGRIJKE RICHTLIJNEN:
De productlijst is ALLEEN bedoeld om te begrijpen welk TYPE producten de categorie bevat. Gebruik het om algemene types en typische gebruiksscenario\'s te identificeren.

NIET DOEN:
- Specifieke productnamen, modellen of SKU\'s uit de lijst noemen
- Verwijzen naar individuele artikelen met specifieke details
- De merk- of bovenliggende categoriecontext negeren indien aanwezig in het breadcrumb
- Generiek schrijven zonder de werkelijke functionele producttypes te noemen
- Zelfrefererende koppen over de website gebruiken (bijv. "Waarom kiezen voor [sitenaam]")
- Zwakke formules in de eerste H2 gebruiken zoals "wat u vindt", "wat we bieden", "ontdek"
- Verwijzen naar specifieke websitefuncties die mogelijk niet bestaan

WEL DOEN:
- De uit de analyse geïdentificeerde product-macro-types generiek benoemen maar met natuurlijke long tail keywords
- Als het breadcrumb een bovenliggend merk aangeeft, dit in de eerste paragraaf presenteren als selectiecontext
- De eerste H2 moet bovenliggend merk + categorienaam als hoofdkeyword bevatten
- De tweede H2 moet actie-/verkenningsgericht zijn, niet merkgericht
- Natuurlijke long tail keywords direct in de macro-type bullet points opnemen
- Focussen op de zoekintentie van de klant voor die specifieke categorie
- De eerste paragraaf moet de volledige categorienaam met context bevatten

VERPLICHTE STRUCTUUR:
- Paragraaf 1: introductie met hoofdkeyword + bovenliggend merk/categoriecontext + gebruikscontexten
- Eerste H2: [bovenliggend merk] + [categorienaam] — gevolgd door bullet points met macro-types en natuurlijk geïntegreerde long tail keywords
- Tweede H2: zachte call to action gericht op verkenning (NIET zelfreferend, GEEN websitfunctieverwijzingen)
- Afsluiting: 1-2 zinnen die bovenliggend merk + categorie + hoofdvoordeel herinneren

VEREISTEN:
- Lengte: 250-350 woorden
- Natuurlijke, SEO-vriendelijke taal
- Boeiende, professionele toon
- Geen prijzen of specifieke promoties noemen
- H2-koppen gebruiken voor subsecties
- Long tail keywords moeten natuurlijk geïntegreerd zijn, niet geforceerd',
                'template_pl' => 'Napisz atrakcyjny i zoptymalizowany pod kątem SEO opis kategorii dla sklepu internetowego.

INFORMACJE KONTEKSTOWE:
- Ścieżka kategorii: {category_breadcrumb}
- Nazwa kategorii: {category_name}
- URL kategorii: {category_url}
- Strona: {site_name} ({shop_url})
- Opis strony: {site_description}
- Przykładowe produkty w tej kategorii (tylko kontekst): {first_products:10}
- Aktualny opis kategorii: {category_description}

WSTĘPNA ANALIZA (wykonaj mentalnie przed pisaniem):
1. Ze ścieżki kategorii ({category_breadcrumb}) zidentyfikuj, czy istnieje marka nadrzędna (np. pierwszy poziom = marka) i zachowaj ją jako kontekst tekstu
2. Z przykładowych produktów wyodrębnij obecne funkcjonalne MAKRO-TYPY (np. "dźwignie", "osłony", "uchwyty") — te powinny być wymienione w tekście
3. Dla każdego zidentyfikowanego makro-typu zbuduj naturalne słowo kluczowe long tail łącząc typ + nazwę kategorii
4. Zidentyfikuj główny kontekst użycia (np. touring, racing, ochrona, ergonomia) i użyj go do budowania tonu i korzyści

WAŻNE WYTYCZNE:
Lista produktów jest podana TYLKO po to, aby zrozumieć, jaki TYP produktów zawiera kategoria. Użyj jej do identyfikacji ogólnych typów i typowych przypadków użycia.

NIE RÓB:
- Nie wspominaj konkretnych nazw produktów, modeli lub SKU z listy
- Nie odwołuj się do pojedynczych artykułów ze szczegółowymi informacjami
- Nie ignoruj kontekstu marki lub kategorii nadrzędnej, jeśli jest obecny w breadcrumb
- Nie pisz ogólnikowo bez wymieniania rzeczywistych funkcjonalnych typów produktów
- Nie używaj nagłówków samoreferencyjnych o stronie (np. "Dlaczego wybrać [nazwa strony]")
- Nie używaj słabych formuł w pierwszym H2 jak "co znajdziesz", "co oferujemy", "odkryj"
- Nie odwołuj się do specyficznych funkcji strony, które mogą nie istnieć

RÓB:
- Nazwij makro-typy produktów zidentyfikowane z analizy w sposób ogólny, ale z naturalnymi słowami kluczowymi long tail
- Jeśli breadcrumb wskazuje markę nadrzędną, przedstaw ją w pierwszym akapicie jako kontekst wyboru
- Pierwszy H2 musi zawierać markę nadrzędną + nazwę kategorii jako główne słowo kluczowe
- Drugi H2 musi być zorientowany na akcję/eksplorację, nie na markę
- Włącz naturalne słowa kluczowe long tail bezpośrednio w punktach makro-typów
- Skup się na intencji wyszukiwania klienta dla tej konkretnej kategorii
- Pierwszy akapit musi zawierać pełną nazwę kategorii z kontekstem

OBOWIĄZKOWA STRUKTURA:
- Akapit 1: wprowadzenie z głównym słowem kluczowym + kontekst marki/kategorii nadrzędnej + konteksty użycia
- Pierwszy H2: [marka nadrzędna] + [nazwa kategorii] — następnie punkty z makro-typami i naturalnie zintegrowanymi słowami kluczowymi long tail
- Drugi H2: miękkie wezwanie do działania zorientowane na eksplorację (NIE samoreferencyjne, BEZ odniesień do funkcji strony)
- Zakończenie: 1-2 zdania przypominające markę nadrzędną + kategorię + główną korzyść

WYMAGANIA:
- Długość: 250-350 słów
- Naturalny, przyjazny dla SEO język
- Angażujący, profesjonalny ton
- Nie wspominaj cen ani konkretnych promocji
- Używaj nagłówków H2 dla podsekcji
- Słowa kluczowe long tail muszą być zintegrowane naturalnie, nie wymuszone',
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
Sample products: {random_products:5}

Requirements:
- Maximum 155 characters
- Include call-to-action
- Mention variety/selection
- Return ONLY the meta description text, no explanations',
                'template_fr' => 'Rédigez une meta description optimisée SEO pour cette page de catégorie e-commerce.

Chemin de la catégorie : {category_breadcrumb}
Catégorie : {category_name}
Exemples de produits : {random_products:5}

Exigences :
- Maximum 155 caractères
- Inclure un appel à l\'action
- Mentionner la variété/sélection
- Retourner UNIQUEMENT le texte de la meta description, sans explications',
                'template_it' => 'Scrivi una meta description SEO-friendly per questa pagina di categoria e-commerce.

Percorso categoria: {category_breadcrumb}
Categoria: {category_name}
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
            // Google Translate settings
            'google_translate_enabled' => (bool) Configuration::get(self::CONFIG_GOOGLE_TRANSLATE_ENABLED),
            'google_translate_configured' => !empty(Configuration::get(self::CONFIG_GOOGLE_TRANSLATE_API_KEY)),
            'primary_language_id' => (int) Configuration::get(self::CONFIG_PRIMARY_LANGUAGE),
            'translate_language_ids' => array_map('intval', json_decode(Configuration::get(self::CONFIG_TRANSLATE_LANGUAGES), true) ?: []),
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

        // Get last generation info for all categories
        $generationInfo = $this->getCategoriesGenerationInfo();

        return $this->buildCategoryTreeArray($categories, $generationInfo);
    }

    /**
     * Get last generation info for all categories
     *
     * @return array Indexed by id_category
     */
    protected function getCategoriesGenerationInfo()
    {
        $idShop = (int) $this->context->shop->id;

        // Get last generation date and count of fields generated per category
        $sql = 'SELECT 
                    gl.id_category,
                    MAX(gl.generated_at) as last_generated,
                    COUNT(DISTINCT gl.field_type) as fields_count,
                    GROUP_CONCAT(DISTINCT gl.field_type) as fields_list
                FROM `' . _DB_PREFIX_ . 'mlcategoryai_generation_log` gl
                WHERE gl.id_shop = ' . $idShop . '
                AND gl.status = "success"
                GROUP BY gl.id_category';

        $results = Db::getInstance()->executeS($sql);

        $info = [];
        foreach ($results as $row) {
            // Determine if it was a full run (4 fields) or partial
            $fieldsCount = (int) $row['fields_count'];
            $type = $fieldsCount >= 4 ? 'full' : 'partial';

            $info[(int) $row['id_category']] = [
                'last_generated' => $row['last_generated'],
                'last_generated_short' => date('d/m', strtotime($row['last_generated'])),
                'fields_count' => $fieldsCount,
                'type' => $type,
            ];
        }

        return $info;
    }

    /**
     * Build tree array from nested categories
     *
     * @param array $categories
     * @param array $generationInfo Generation info indexed by category ID
     *
     * @return array
     */
    protected function buildCategoryTreeArray($categories, $generationInfo = [])
    {
        $result = [];

        foreach ($categories as $category) {
            $idCategory = (int) $category['id_category'];

            $node = [
                'id_category' => $idCategory,
                'name' => $category['name'],
                'children' => [],
                'last_generated' => isset($generationInfo[$idCategory]) ? $generationInfo[$idCategory]['last_generated_short'] : null,
                'generation_type' => isset($generationInfo[$idCategory]) ? $generationInfo[$idCategory]['type'] : null,
            ];

            if (!empty($category['children'])) {
                $node['children'] = $this->buildCategoryTreeArray($category['children'], $generationInfo);
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
     * Get all pending/running/paused/failed jobs
     * v1.7.1: Include failed jobs so they can be managed from UX
     *
     * @return array
     */
    protected function getAllPendingJobs()
    {
        $results = Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'mlcategoryai_job_queue`
            WHERE `status` IN ("pending", "running", "paused", "failed")
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
            $this->getConfigFormTranslation(),
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
     * Google Translate settings form
     *
     * @return array
     */
    protected function getConfigFormTranslation()
    {
        $languages = Language::getLanguages(true);
        $languageOptions = [];
        foreach ($languages as $lang) {
            $languageOptions[] = [
                'id' => $lang['id_lang'],
                'name' => $lang['name'],
            ];
        }

        return [
            'form' => [
                'legend' => [
                    'title' => $this->l('Google Translate Settings'),
                    'icon' => 'icon-globe',
                ],
                'description' => $this->l('Use Google Translate to automatically translate content from the primary language to other languages. This reduces OpenAI API calls and processing time.'),
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->l('Enable Google Translate'),
                        'name' => self::CONFIG_GOOGLE_TRANSLATE_ENABLED,
                        'is_bool' => true,
                        'desc' => $this->l('When enabled, content is generated in the primary language only, then translated to other languages using Google Translate API.'),
                        'values' => [
                            ['id' => 'gt_on', 'value' => true, 'label' => $this->l('Enabled')],
                            ['id' => 'gt_off', 'value' => false, 'label' => $this->l('Disabled')],
                        ],
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Google Translate API Key'),
                        'name' => self::CONFIG_GOOGLE_TRANSLATE_API_KEY,
                        'desc' => $this->l('Your Google Cloud Translation API key. Get one from: https://console.cloud.google.com/apis'),
                        'class' => 'fixed-width-xxl',
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->l('Primary Language'),
                        'name' => self::CONFIG_PRIMARY_LANGUAGE,
                        'desc' => $this->l('Content will be generated in this language using OpenAI, then translated to other languages.'),
                        'options' => [
                            'query' => $languageOptions,
                            'id' => 'id',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'html',
                        'label' => $this->l('Translate to Languages'),
                        'name' => 'translate_languages_select',
                        'html_content' => $this->getTranslateLanguagesHtml(),
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ],
            ],
        ];
    }

    /**
     * Get HTML for translate languages selection
     *
     * @return string
     */
    protected function getTranslateLanguagesHtml()
    {
        $languages = Language::getLanguages(true);
        $selectedLangs = array_map('intval', json_decode(Configuration::get(self::CONFIG_TRANSLATE_LANGUAGES), true) ?: []);
        $primaryLang = (int) Configuration::get(self::CONFIG_PRIMARY_LANGUAGE);

        $html = '<div class="translate-languages-container">';
        foreach ($languages as $lang) {
            $checked = in_array((int) $lang['id_lang'], $selectedLangs) ? 'checked' : '';
            $disabled = ((int) $lang['id_lang'] === $primaryLang) ? 'disabled' : '';
            $primaryNote = ((int) $lang['id_lang'] === $primaryLang) ? ' <em>(' . $this->l('primary') . ')</em>' : '';

            $html .= '<div class="checkbox">';
            $html .= '<label>';
            $html .= '<input type="checkbox" name="translate_languages[]" value="' . (int) $lang['id_lang'] . '" ' . $checked . ' ' . $disabled . '>';
            $html .= htmlspecialchars($lang['name'], ENT_QUOTES, 'UTF-8') . $primaryNote;
            $html .= '</label>';
            $html .= '</div>';
        }
        $html .= '</div>';
        $html .= '<p class="help-block">' . $this->l('Select the languages to translate into. The primary language is automatically excluded.') . '</p>';

        return $html;
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
            // Google Translate settings
            self::CONFIG_GOOGLE_TRANSLATE_ENABLED => Configuration::get(self::CONFIG_GOOGLE_TRANSLATE_ENABLED),
            self::CONFIG_GOOGLE_TRANSLATE_API_KEY => $this->decryptApiKey(Configuration::get(self::CONFIG_GOOGLE_TRANSLATE_API_KEY)),
            self::CONFIG_PRIMARY_LANGUAGE => Configuration::get(self::CONFIG_PRIMARY_LANGUAGE),
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
            // Google Translate settings (except API key and translate_languages)
            self::CONFIG_GOOGLE_TRANSLATE_ENABLED,
            self::CONFIG_PRIMARY_LANGUAGE,
        ];

        foreach ($configKeys as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }

        // Handle Google Translate API key encryption
        $gtApiKey = Tools::getValue(self::CONFIG_GOOGLE_TRANSLATE_API_KEY);
        if (!empty($gtApiKey)) {
            Configuration::updateValue(self::CONFIG_GOOGLE_TRANSLATE_API_KEY, $this->encryptApiKey($gtApiKey));
        }

        // Handle translate languages (array of checkboxes)
        $translateLanguages = Tools::getValue('translate_languages');
        if (is_array($translateLanguages)) {
            // Filter out the primary language
            $primaryLang = (int) Tools::getValue(self::CONFIG_PRIMARY_LANGUAGE);
            $translateLanguages = array_filter($translateLanguages, function ($langId) use ($primaryLang) {
                return (int) $langId !== $primaryLang;
            });
            Configuration::updateValue(self::CONFIG_TRANSLATE_LANGUAGES, json_encode(array_values(array_map('intval', $translateLanguages))));
        } else {
            Configuration::updateValue(self::CONFIG_TRANSLATE_LANGUAGES, '[]');
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
            $this->context->controller->addCSS($this->_path . 'views/css/back.css' . $cacheBuster, 'all', null, false);

            // Add AJAX configuration for JavaScript
            Media::addJsDef([
                'mlcategoryai_ajax_url' => $this->getAjaxUrl(),
                'mlcategoryai_token' => $this->getAjaxToken(),
            ]);
        }
    }
}
