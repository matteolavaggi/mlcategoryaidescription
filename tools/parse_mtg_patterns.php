#!/usr/bin/env php
<?php
/**
 * Dump MTG_SAVED_PATTERNS manufacturer & category blocks per language (ps_configuration).
 *
 * Usage (from module dir):
 *   php tools/parse_mtg_patterns.php
 *
 * Does not run a full-tree scan; only loads PrestaShop bootstrap + parser class.
 */
if (php_sapi_name() !== 'cli') {
    exit('CLI only');
}

$moduleDir = dirname(__DIR__);
$psRoot = dirname($moduleDir, 2);
$configFile = $psRoot . '/config/config.inc.php';

if (!is_readable($configFile)) {
    fwrite(STDERR, "Missing PrestaShop config: {$configFile}\n");
    exit(1);
}

chdir($psRoot);
require_once $configFile;
require_once $moduleDir . '/classes/MtgSavedPatternsParser.php';

$raw = Configuration::get(MtgSavedPatternsParser::CONFIG_KEY);
echo 'Config key: ' . MtgSavedPatternsParser::CONFIG_KEY . PHP_EOL;
echo 'Raw length (bytes): ' . ($raw !== false ? strlen($raw) : 0) . PHP_EOL;

$decoded = MtgSavedPatternsParser::loadDecoded();
if ($decoded === null) {
    echo "No data or invalid JSON.\n";
    exit(0);
}

echo 'Top-level keys (id_lang): ' . implode(', ', array_keys($decoded)) . PHP_EOL . PHP_EOL;

$langs = Language::getLanguages(true);
foreach ($langs as $lang) {
    $id = (int) $lang['id_lang'];
    $iso = $lang['iso_code'];
    $block = MtgSavedPatternsParser::getManufacturerPatternsForLang($id);
    $mapped = MtgSavedPatternsParser::getMtgRulesForMlManufacturerFields($id);
    echo "=== Lang id_lang={$id} ({$iso}) — manufacturer ===\n";
    echo json_encode($block, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    echo "--- Mapped to ML batch fields (meta_title, meta_description, meta_keywords) ---\n";
    echo json_encode($mapped, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL . PHP_EOL;

    $catBlock = MtgSavedPatternsParser::getCategoryPatternsForLang($id);
    $catMapped = MtgSavedPatternsParser::getMtgRulesForMlCategoryFields($id);
    echo "=== Lang id_lang={$id} ({$iso}) — category ===\n";
    echo json_encode($catBlock, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    echo "--- Mapped to ML batch fields (meta_title, meta_description, meta_keywords) ---\n";
    echo json_encode($catMapped, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL . PHP_EOL;
}

exit(0);
