<?php
/**
 * Default manufacturer prompts (Sixrace / motorcycle accessories & parts positioning).
 * Languages covered: IT, EN, PL, FR, DE, ES, PT, NL.
 * If a shop language ISO is not in this file, the installer falls back to EN, then PL.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

return [
    'description' => [
        'name' => 'Manufacturer SEO description',
        'template_it' => 'Scrivi un\'ampia descrizione SEO HTML per la pagina del produttore per lo shop e-commerce Sixrace di accessori e ricambi per motociclette.

CONTESTO:
- Produttore: {manufacturer_name}
- Categorie distinte dei prodotti di questo brand: {unique_categories:40}
- Prodotti di esempio (solo contesto, NON elencare SKU): {first_products:12}
- Sito: {site_name} ({shop_url})
- Descrizione attuale del produttore: {manufacturer_description}
- Lingua di output: Italiano

ISTRUZIONI:
- Usa le tue conoscenze del brand nel settore accessori e abbigliamento moto per arricchire il testo, integrandole con i dati forniti. Non inventare SKU o nomi di prodotti specifici non confermati dai dati.
- Scrivi ESCLUSIVAMENTE nella lingua di output indicata. Non usare altre lingue.
- HTML: usa <p>, <h2>, <strong>, <ul>, <li>. Niente Markdown.
- Focus su categorie e tipologie di prodotto; non citare nomi specifici dei prodotti d\'esempio.
- Primo paragrafo: nome del produttore + posizionamento nel settore moto.
- Due sezioni H2 con titoli descrittivi e concreti, basati sulle categorie reali del brand fornite nel contesto.
- Tono: diretto, orientato alla conversione, ricco di keyword naturali per SEO.
- Lunghezza: 450–600 parole.
- Restituisci SOLO contenuto HTML, senza backtick o commenti.',
        'template_en' => 'Write a comprehensive SEO HTML description for the manufacturer page for the Sixrace e‑commerce shop specialising in motorcycle accessories and spare parts.

CONTEXT:
- Manufacturer: {manufacturer_name}
- Distinct product categories for this brand: {unique_categories:40}
- Sample products (context only, do not list SKUs): {first_products:12}
- Site: {site_name} ({shop_url})
- Current manufacturer description: {manufacturer_description}
- Output language: English

INSTRUCTIONS:
- Use your knowledge of the brand in the motorcycle accessories and apparel sector to enrich the text, integrating it with the data provided. Do not invent SKUs or specific product names not confirmed by the data.
- Write EXCLUSIVELY in the stated output language. Do not use other languages.
- HTML: use <p>, <h2>, <strong>, <ul>, <li>. No Markdown.
- Focus on product categories and types; do not cite specific names of the sample products.
- First paragraph: manufacturer name + positioning in the motorcycle sector.
- Two H2 sections with descriptive, concrete titles based on the brand\'s real categories provided in the context.
- Tone: direct, conversion‑oriented, rich in natural SEO keywords.
- Length: 450–600 words.
- Return ONLY HTML content, with no backticks or comments.',
        'template_pl' => 'Napisz rozbudowany opis SEO w HTML na stronę producenta dla sklepu internetowego Sixrace z akcesoriami i częściami zamiennymi do motocykli.

KONTEKST:
- Producent: {manufacturer_name}
- Unikalne kategorie produktów tej marki: {unique_categories:40}
- Przykładowe produkty (tylko kontekst, bez wypisywania SKU): {first_products:12}
- Sklep: {site_name} ({shop_url})
- Aktualny opis producenta: {manufacturer_description}
- Język wyniku: polski

INSTRUKCJE:
- Wykorzystaj wiedzę o marce w segmencie akcesoriów i odzieży motocyklowej, aby wzbogacić tekst i połączyć ją z podanymi danymi. Nie wymyślaj SKU ani konkretnych nazw produktów niepotwierdzonych danymi.
- Pisz WYŁĄCZNIE w podanym języku wyniku. Nie używaj innych języków.
- HTML: użyj <p>, <h2>, <strong>, <ul>, <li>. Bez Markdown.
- Skup się na kategoriach i typach produktów; nie cytuj konkretnych nazw produktów przykładowych.
- Pierwszy akapit: nazwa producenta + pozycjonowanie w branży motocyklowej.
- Dwie sekcje H2 z konkretnymi, opisowymi tytułami, opartymi na rzeczywistych kategoriach marki z kontekstu.
- Ton: bezpośredni, nastawiony na konwersję, z naturalnymi frazami pod SEO.
- Długość: 450–600 słów.
- Zwróć WYŁĄCZNIE treść HTML, bez backticków i komentarzy.',
        'template_fr' => 'Rédige une description SEO HTML complète pour la page fabricant du e‑shop Sixrace, spécialisé dans les accessoires et pièces détachées moto.

CONTEXTE :
- Fabricant : {manufacturer_name}
- Catégories distinctes des produits de cette marque : {unique_categories:40}
- Exemples de produits (contexte uniquement, ne pas lister de SKU) : {first_products:12}
- Site : {site_name} ({shop_url})
- Description fabricant actuelle : {manufacturer_description}
- Langue de sortie : français

INSTRUCTIONS :
- Enrichis le texte en t\'appuyant sur ta connaissance de la marque dans le secteur des accessoires et équipements moto, en la combinant aux données fournies. N’invente ni SKU ni noms de produits précis non confirmés par les données.
- Rédige EXCLUSIVEMENT dans la langue de sortie indiquée. N’utilise pas d’autres langues.
- HTML : utilise <p>, <h2>, <strong>, <ul>, <li>. Pas de Markdown.
- Mets l’accent sur les catégories et types de produits ; ne cite pas les noms précis des exemples de produits.
- Premier paragraphe : nom du fabricant + positionnement dans l’univers moto.
- Deux sections H2 avec titres descriptifs et concrets, basés sur les catégories réelles de la marque fournies dans le contexte.
- Ton : direct, orienté conversion, riche en expressions naturelles pour le SEO.
- Longueur : 450–600 mots.
- Retourne UNIQUEMENT du contenu HTML, sans backticks ni commentaires.',
        'template_de' => 'Schreibe eine ausführliche SEO‑HTML‑Beschreibung für die Herstellerseite des Sixrace‑Onlineshops für Motorradzubehör und Ersatzteile.

KONTEXT:
- Hersteller: {manufacturer_name}
- Unterschiedliche Produktkategorien dieser Marke: {unique_categories:40}
- Beispielprodukte (nur Kontext, keine SKU auflisten): {first_products:12}
- Shop: {site_name} ({shop_url})
- Aktuelle Herstellerbeschreibung: {manufacturer_description}
- Ausgabesprache: Deutsch

ANWEISUNGEN:
- Nutze dein Wissen über die Marke im Bereich Motorradzubehör und ‑bekleidung, um den Text zu verfeinern und mit den Daten zu verbinden. Erfinde keine SKU oder konkrete Produktnamen, die nicht durch die Daten belegt sind.
- Schreibe AUSSCHLIESSLICH in der angegebenen Ausgabesprache. Verwende keine anderen Sprachen.
- HTML: verwende <p>, <h2>, <strong>, <ul>, <li>. Kein Markdown.
- Fokus auf Kategorien und Produkttypen; nenne keine spezifischen Namen der Beispielprodukte.
- Erster Absatz: Herstellername + Positionierung im Motorradsegment.
- Zwei H2‑Abschnitte mit beschreibenden, konkreten Überschriften, orientiert an den im Kontext genannten realen Kategorien der Marke.
- Tonalität: direkt, conversion‑stark, natürliche SEO‑Begriffe.
- Länge: 450–600 Wörter.
- Gib AUSSCHLIESSLICH HTML‑Inhalt zurück, ohne Backticks oder Kommentare.',
        'template_es' => 'Redacta una descripción SEO en HTML amplia para la página del fabricante de la tienda online Sixrace de accesorios y recambios para moto.

Contexto:
- Fabricante: {manufacturer_name}
- Categorías distintas de los productos de esta marca: {unique_categories:40}
- Productos de muestra (solo contexto, no enumerar SKU): {first_products:12}
- Sitio: {site_name} ({shop_url})
- Descripción actual del fabricante: {manufacturer_description}
- Idioma de salida: español

Instrucciones:
- Aprovecha tu conocimiento de la marca en el sector de accesorios y equipamiento moto para enriquecer el texto, integrándolo con los datos proporcionados. No inventes SKU ni nombres de productos concretos no confirmados por los datos.
- Escribe EXCLUSIVAMENTE en el idioma de salida indicado. No uses otros idiomas.
- HTML: usa <p>, <h2>, <strong>, <ul>, <li>. Nada de Markdown.
- Enfócate en categorías y tipos de producto; no cites nombres concretos de los productos de muestra.
- Primer párrafo: nombre del fabricante + posicionamiento en el sector moto.
- Dos secciones H2 con títulos descriptivos y concretos, basados en las categorías reales de la marca del contexto.
- Tono: directo, orientado a la conversión, con keywords naturales para SEO.
- Extensión: 450–600 palabras.
- Devuelve SOLO contenido HTML, sin backticks ni comentarios.',
        'template_pt' => 'Escreve uma descrição SEO em HTML ampla para a página do fabricante da loja online Sixrace de acessórios e peças de reposição para motos.

Contexto:
- Fabricante: {manufacturer_name}
- Categorias distintas dos produtos desta marca: {unique_categories:40}
- Produtos de exemplo (apenas contexto, não listar SKU): {first_products:12}
- Site: {site_name} ({shop_url})
- Descrição atual do fabricante: {manufacturer_description}
- Idioma de saída: português

Instruções:
- Usa o teu conhecimento da marca no segmento de acessórios e equipamento moto para enriquecer o texto, integrando‑o com os dados fornecidos. Não cries SKU nem nomes de produtos específicos não confirmados pelos dados.
- Escreve EXCLUSIVAMENTE no idioma de saída indicado. Não uses outros idiomas.
- HTML: usa <p>, <h2>, <strong>, <ul>, <li>. Sem Markdown.
- Foco em categorias e tipos de produto; não cites nomes específicos dos produtos de exemplo.
- Primeiro parágrafo: nome do fabricante + posicionamento no setor moto.
- Duas secções H2 com títulos descritivos e concretos, com base nas categorias reais da marca fornecidas no contexto.
- Tom: direto, orientado à conversão, com keywords naturais para SEO.
- Comprimento: 450–600 palavras.
- Devolve APENAS conteúdo HTML, sem backticks nem comentários.',
        'template_nl' => 'Schrijf een uitgebreide SEO‑HTML‑beschrijving voor de fabrikantenpagina van de Sixrace webshop voor motoraccessoires en onderdelen.

CONTEXT:
- Fabrikant: {manufacturer_name}
- Onderscheidende productcategorieën van dit merk: {unique_categories:40}
- Voorbeeldproducten (alleen context, geen SKU’s opsommen): {first_products:12}
- Site: {site_name} ({shop_url})
- Huidige fabrikantenbeschrijving: {manufacturer_description}
- Uitvoertaal: Nederlands

INSTRUCTIES:
- Gebruik je merkkennis in motoraccessoires en ‑kleding om de tekst te verrijken en te combineren met de gegeven data. Verzin geen SKU’s of specifieke productnamen die niet in de data voorkomen.
- Schrijf UITSLUITEND in de opgegeven uitvoertaal. Gebruik geen andere talen.
- HTML: gebruik <p>, <h2>, <strong>, <ul>, <li>. Geen Markdown.
- Focus op categorieën en producttypes; noem geen specifieke namen van de voorbeeldproducten.
- Eerste alinea: fabrikantnaam + positionering in de motormarkt.
- Twee H2‑secties met concrete, beschrijvende titels, gebaseerd op de echte merkcategorieën uit de context.
- Toon: direct, conversiegericht, natuurlijke zoektermen voor SEO.
- Lengte: 450–600 woorden.
- Geef ALLEEN HTML‑inhoud terug, zonder backticks of opmerkingen.',
    ],
    'short_description' => [
        'name' => 'Manufacturer ultra-short line',
        'template_it' => 'Genera UNA singola riga di testo (senza HTML) per la pagina del produttore.

Produttore: {manufacturer_name}
Categorie (contesto): {unique_categories:25}
Prodotti (contesto): {first_products:8}

REGOLE:
- Inizia con una frase di 4–5 parole che descriva la specializzazione o le categorie principali; non ripetere il nome del produttore in questa frase.
- Poi uno spazio e il nome completo del produttore esattamente come: {manufacturer_name}
- Lunghezza totale della riga al massimo 80 caratteri; se necessario accorcia la frase iniziale, non spezzare le parole.
- Restituisci SOLO quella singola riga, senza virgolette.',
        'template_en' => 'Generate ONE plain-text short line for the manufacturer page.

Manufacturer: {manufacturer_name}
Categories (context): {unique_categories:25}
Products (context): {first_products:8}

RULES:
- Start with a 4–5 word phrase describing specialization/main categories; do not repeat the brand name in this phrase.
- Then a space and the full manufacturer name exactly: {manufacturer_name}
- Total line length at most 80 characters; shorten the opening phrase if needed; do not break words awkwardly.
- Return ONLY that single line, no quotes.',
        'template_pl' => 'Wygeneruj JEDNĄ krótką linię (zwykły tekst, bez HTML) na stronę producenta.

Producent: {manufacturer_name}
Kategorie (kontekst): {unique_categories:25}
Produkty (kontekst): {first_products:8}

INSTRUKCJE:
- Najpierw 4–5 słów (jedna fraza) opisująca specjalizację lub główne kategorie bez powtarzania nazwy producenta.
- Potem spacja i pełna nazwa producenta dokładnie: {manufacturer_name}
- Łączna długość całej linii: nie więcej niż 80 znaków. Jeśli jest ryzykownie dłużej, skróć frazę na początku, nie obcinaj pojedynczych słów w środku.
- Zwróć TYLKO tę jedną linię, bez cudzysłowów.',
        'template_fr' => 'Génère UNE seule ligne en texte brut (sans HTML) pour la page du producteur.

Producteur : {manufacturer_name}
Catégories (contexte) : {unique_categories:25}
Produits (contexte) : {first_products:8}

RÈGLES :
- Commence par une expression de 4–5 mots décrivant la spécialisation / les catégories principales, sans répéter le nom de la marque.
- Puis un espace et le nom complet du producteur exactement : {manufacturer_name}
- Longueur totale 80 caractères maximum ; raccourcis l\'expression d\'ouverture si nécessaire, sans couper de mots.
- Retourne UNIQUEMENT cette ligne, sans guillemets.',
        'template_de' => 'Erzeuge EINE einzelne Klartext-Zeile (kein HTML) für die Hersteller-Seite.

Hersteller: {manufacturer_name}
Kategorien (Kontext): {unique_categories:25}
Produkte (Kontext): {first_products:8}

REGELN:
- Starte mit einer 4–5 Wörter umfassenden Wendung zur Spezialisierung / den Hauptkategorien, ohne den Markennamen darin zu wiederholen.
- Danach ein Leerzeichen und der vollständige Herstellername genau so: {manufacturer_name}
- Gesamtzeile höchstens 80 Zeichen; kürze den Einstieg falls nötig, ohne Wörter zu zerbrechen.
- Gib NUR diese eine Zeile zurück, keine Anführungszeichen.',
        'template_es' => 'Genera UNA sola línea de texto plano (sin HTML) para la página del fabricante.

Fabricante: {manufacturer_name}
Categorías (contexto): {unique_categories:25}
Productos (contexto): {first_products:8}

REGLAS:
- Empieza con una expresión de 4–5 palabras que describa la especialización o las categorías principales, sin repetir el nombre de la marca.
- Después un espacio y el nombre completo del fabricante exactamente: {manufacturer_name}
- Longitud total como máximo 80 caracteres; recorta la frase inicial si es necesario, sin cortar palabras.
- Devuelve SOLO esa línea, sin comillas.',
        'template_pt' => 'Gera UMA única linha de texto simples (sem HTML) para a página do fabricante.

Fabricante: {manufacturer_name}
Categorias (contexto): {unique_categories:25}
Produtos (contexto): {first_products:8}

REGRAS:
- Começa com uma expressão de 4–5 palavras a descrever a especialização ou as categorias principais, sem repetir o nome da marca.
- Depois um espaço e o nome completo do fabricante exatamente: {manufacturer_name}
- Comprimento total no máximo 80 caracteres; encurta a expressão inicial se necessário, sem cortar palavras.
- Devolve APENAS essa linha, sem aspas.',
        'template_nl' => 'Genereer ÉÉN platte-tekst regel (geen HTML) voor de fabrikantenpagina.

Fabrikant: {manufacturer_name}
Categorieën (context): {unique_categories:25}
Producten (context): {first_products:8}

REGELS:
- Begin met een woordcombinatie van 4–5 woorden over specialisatie / hoofdcategorieën, zonder de merknaam te herhalen.
- Daarna een spatie en de volledige fabrikantnaam exact: {manufacturer_name}
- Totale lengte maximaal 80 tekens; kort de inleidende uitdrukking in indien nodig, zonder woorden af te breken.
- Geef ALLEEN die ene regel terug, zonder aanhalingstekens.',
    ],
    'meta_title' => [
        'name' => 'Manufacturer meta title',
        'template_it' => 'Genera il meta title SEO per la pagina del produttore {manufacturer_name}.

Categorie (contesto): {unique_categories:15}

Requisiti:
- Punta a circa 80 caratteri (non superare in modo evidente; non troncare le parole a fine riga).
- Includi il nome del produttore e, se possibile, un cenno al tipo di offerta.
- Restituisci SOLO il testo del meta title.',
        'template_en' => 'Generate an SEO meta title for manufacturer page {manufacturer_name}.

Categories (context): {unique_categories:15}

Requirements:
- Target around 80 characters; stay within range without awkward word breaks.
- Include the brand and a hint of the assortment.
- Return ONLY the meta title text.',
        'template_pl' => 'Wygeneruj meta title SEO dla strony producenta {manufacturer_name}.

Kategorie (kontekst): {unique_categories:15}

Wymagania:
- Celuj w ok. 80 znaków (nie przekraczaj jeśli możesz; nie obcinaj pojedynczych słów na końcu).
- Zawrzyj nazwę producenta i ewentualnie typ oferty.
- Zwróć TYLKO tekst meta title.',
        'template_fr' => 'Génère un meta title SEO pour la page du producteur {manufacturer_name}.

Catégories (contexte) : {unique_categories:15}

Exigences :
- Vise environ 80 caractères ; ne dépasse pas inutilement et ne coupe pas de mots.
- Inclus le nom de la marque et, si possible, un indice sur l\'assortiment.
- Retourne UNIQUEMENT le texte du meta title.',
        'template_de' => 'Erzeuge einen SEO-Meta-Title für die Hersteller-Seite {manufacturer_name}.

Kategorien (Kontext): {unique_categories:15}

Anforderungen:
- Ziel etwa 80 Zeichen; bleib im Rahmen, ohne Wörter abzuschneiden.
- Markenname und ein Hinweis aufs Sortiment.
- Gib NUR den Meta-Title-Text zurück.',
        'template_es' => 'Genera un meta title SEO para la página del fabricante {manufacturer_name}.

Categorías (contexto): {unique_categories:15}

Requisitos:
- Apunta a unos 80 caracteres; mantente en rango sin cortar palabras.
- Incluye el nombre de la marca y, si cabe, una pista del surtido.
- Devuelve SOLO el texto del meta title.',
        'template_pt' => 'Gera um meta title SEO para a página do fabricante {manufacturer_name}.

Categorias (contexto): {unique_categories:15}

Requisitos:
- Aponta para cerca de 80 caracteres; mantém-te dentro do limite sem cortar palavras.
- Inclui o nome da marca e, se possível, uma referência ao sortido.
- Devolve APENAS o texto do meta title.',
        'template_nl' => 'Genereer een SEO meta title voor de fabrikantenpagina {manufacturer_name}.

Categorieën (context): {unique_categories:15}

Vereisten:
- Mik op rond de 80 tekens; blijf in dat bereik zonder woorden af te breken.
- Vermeld de merknaam en, indien mogelijk, een hint van het assortiment.
- Geef ALLEEN de meta title-tekst terug.',
    ],
    'meta_description' => [
        'name' => 'Manufacturer meta description',
        'template_it' => 'Scrivi una meta description SEO per il produttore {manufacturer_name}.

Categorie: {unique_categories:20}
Prodotti di esempio (contesto): {random_products:6}

Requisiti:
- Punta a circa 158 caratteri (evita troncamenti a metà parola).
- Call-to-action + ampiezza dell\'offerta.
- Restituisci SOLO il testo della meta description.',
        'template_en' => 'Write an SEO meta description for manufacturer {manufacturer_name}.

Categories: {unique_categories:20}
Sample products (context): {random_products:6}

Requirements:
- Target about 158 characters (avoid awkward word breaks).
- Call-to-action + breadth of range.
- Return ONLY the meta description text.',
        'template_pl' => 'Napisz meta description SEO dla producenta {manufacturer_name}.

Kategorie: {unique_categories:20}
Przykładowe produkty (kontekst): {random_products:6}

Wymagania:
- Celuj w ok. 158 znaków (unikaj urywania słów).
- Wezwanie do działania + warianty / szeroka oferta.
- Zwróć TYLKO tekst meta description.',
        'template_fr' => 'Rédige une meta description SEO pour le producteur {manufacturer_name}.

Catégories : {unique_categories:20}
Produits exemples (contexte) : {random_products:6}

Exigences :
- Cible environ 158 caractères (sans couper de mots).
- Appel à l\'action + amplitude de l\'offre.
- Retourne UNIQUEMENT le texte de la meta description.',
        'template_de' => 'Schreibe eine SEO-Meta-Description für den Hersteller {manufacturer_name}.

Kategorien: {unique_categories:20}
Beispielprodukte (Kontext): {random_products:6}

Anforderungen:
- Ziel etwa 158 Zeichen (keine Wörter abreißen).
- Call-to-Action + Breite des Sortiments.
- Gib NUR den Meta-Description-Text zurück.',
        'template_es' => 'Escribe una meta description SEO para el fabricante {manufacturer_name}.

Categorías: {unique_categories:20}
Productos de muestra (contexto): {random_products:6}

Requisitos:
- Apunta a unos 158 caracteres (sin cortar palabras).
- Llamada a la acción + amplitud del catálogo.
- Devuelve SOLO el texto de la meta description.',
        'template_pt' => 'Escreve uma meta description SEO para o fabricante {manufacturer_name}.

Categorias: {unique_categories:20}
Produtos de exemplo (contexto): {random_products:6}

Requisitos:
- Aponta para cerca de 158 caracteres (sem cortar palavras).
- Call-to-action + amplitude do sortido.
- Devolve APENAS o texto da meta description.',
        'template_nl' => 'Schrijf een SEO meta description voor fabrikant {manufacturer_name}.

Categorieën: {unique_categories:20}
Voorbeeldproducten (context): {random_products:6}

Vereisten:
- Mik op ongeveer 158 tekens (geen halve woorden).
- Call-to-action + breedte van het assortiment.
- Geef ALLEEN de meta description-tekst terug.',
    ],
    'meta_keywords' => [
        'name' => 'Manufacturer meta keywords',
        'template_it' => 'Genera parole chiave SEO separate da virgola per il produttore {manufacturer_name}.

Categorie: {unique_categories:20}

Requisiti:
- 5–12 frasi separate da virgola.
- Punta a circa 128 caratteri totali.
- Restituisci SOLO le parole chiave.',
        'template_en' => 'Generate comma-separated SEO keywords for manufacturer {manufacturer_name}.

Categories: {unique_categories:20}

Requirements:
- 5–12 phrases, comma-separated.
- Target around 128 characters total.
- Return ONLY the keywords.',
        'template_pl' => 'Wygeneruj słowa kluczowe SEO (po przecinku) dla producenta {manufacturer_name}.

Kategorie: {unique_categories:20}

Wymagania:
- 5–12 fraz, po przecinku.
- Celuj w ok. 128 znaków łącznie.
- Zwróć TYLKO słowa kluczowe.',
        'template_fr' => 'Génère des mots-clés SEO séparés par des virgules pour le producteur {manufacturer_name}.

Catégories : {unique_categories:20}

Exigences :
- 5–12 expressions séparées par des virgules.
- Vise environ 128 caractères au total.
- Retourne UNIQUEMENT les mots-clés.',
        'template_de' => 'Erzeuge kommagetrennte SEO-Keywords für den Hersteller {manufacturer_name}.

Kategorien: {unique_categories:20}

Anforderungen:
- 5–12 Begriffe, durch Komma getrennt.
- Ziel etwa 128 Zeichen insgesamt.
- Gib NUR die Keywords zurück.',
        'template_es' => 'Genera palabras clave SEO separadas por coma para el fabricante {manufacturer_name}.

Categorías: {unique_categories:20}

Requisitos:
- 5–12 expresiones separadas por coma.
- Apunta a unos 128 caracteres en total.
- Devuelve SOLO las palabras clave.',
        'template_pt' => 'Gera palavras-chave SEO separadas por vírgula para o fabricante {manufacturer_name}.

Categorias: {unique_categories:20}

Requisitos:
- 5–12 expressões separadas por vírgula.
- Aponta para cerca de 128 caracteres no total.
- Devolve APENAS as palavras-chave.',
        'template_nl' => 'Genereer komma-gescheiden SEO-trefwoorden voor fabrikant {manufacturer_name}.

Categorieën: {unique_categories:20}

Vereisten:
- 5–12 woordgroepen, gescheiden door komma\'s.
- Mik op ongeveer 128 tekens totaal.
- Geef ALLEEN de trefwoorden terug.',
    ],
];
