<?php
/**
 * 2010-2026 2win.agency
 *
 * Parser for Metatags Generator module global config MTG_SAVED_PATTERNS (JSON in ps_configuration).
 *
 * Structure (top level keys = id_lang as string|int):
 *   [id_lang][manufacturer|category][meta_title|meta_description|meta_keywords|...] = { active, value, length }
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class MtgSavedPatternsParser
{
    public const CONFIG_KEY = 'MTG_SAVED_PATTERNS';

    /**
     * @return array<string|int, mixed>|null
     */
    public static function loadDecoded()
    {
        $raw = Configuration::get(self::CONFIG_KEY);
        if ($raw === false || $raw === null || $raw === '') {
            return null;
        }

        $data = json_decode($raw, true);

        return is_array($data) ? $data : null;
    }

    /**
     * Normalized manufacturer block for one language.
     *
     * @param int $idLang
     *
     * @return array<string, array{active: bool, value: string, length: int}>
     */
    public static function getManufacturerPatternsForLang($idLang)
    {
        $data = self::loadDecoded();
        if ($data === null) {
            return [];
        }

        $idLang = (int) $idLang;
        $block = null;
        foreach ([(string) $idLang, $idLang] as $key) {
            if (isset($data[$key]['manufacturer']) && is_array($data[$key]['manufacturer'])) {
                $block = $data[$key]['manufacturer'];
                break;
            }
        }

        if ($block === null) {
            return [];
        }

        $out = [];
        foreach ($block as $fieldName => $spec) {
            if (!is_string($fieldName) || !is_array($spec)) {
                continue;
            }
            $out[$fieldName] = [
                'active' => !isset($spec['active']) || (string) $spec['active'] === '1',
                'value' => isset($spec['value']) ? (string) $spec['value'] : '',
                'length' => isset($spec['length']) ? (int) $spec['length'] : 0,
            ];
        }

        return $out;
    }

    /**
     * Normalized category block for one language.
     *
     * @param int $idLang
     *
     * @return array<string, array{active: bool, value: string, length: int}>
     */
    public static function getCategoryPatternsForLang($idLang)
    {
        $data = self::loadDecoded();
        if ($data === null) {
            return [];
        }

        $idLang = (int) $idLang;
        $block = null;
        foreach ([(string) $idLang, $idLang] as $key) {
            if (isset($data[$key]['category']) && is_array($data[$key]['category'])) {
                $block = $data[$key]['category'];
                break;
            }
        }

        if ($block === null) {
            return [];
        }

        $out = [];
        foreach ($block as $fieldName => $spec) {
            if (!is_string($fieldName) || !is_array($spec)) {
                continue;
            }
            $out[$fieldName] = [
                'active' => !isset($spec['active']) || (string) $spec['active'] === '1',
                'value' => isset($spec['value']) ? (string) $spec['value'] : '',
                'length' => isset($spec['length']) ? (int) $spec['length'] : 0,
            ];
        }

        return $out;
    }

    /**
     * MTG category fields mapped to mlcategoryaidescription category batch field types.
     *
     * @param int $idLang
     *
     * @return array<string, array{active: bool, value: string, length: int}>
     */
    public static function getMtgRulesForMlCategoryFields($idLang)
    {
        $m = self::getCategoryPatternsForLang($idLang);
        $map = [
            'meta_title' => 'meta_title',
            'meta_description' => 'meta_description',
            'meta_keywords' => 'meta_keywords',
        ];
        $out = [];
        foreach ($map as $mtgKey => $mlField) {
            if (isset($m[$mtgKey])) {
                $out[$mlField] = $m[$mtgKey];
            }
        }

        return $out;
    }

    /**
     * MTG manufacturer fields mapped to mlcategoryaidescription manufacturer batch field types.
     *
     * @param int $idLang
     *
     * @return array<string, array{active: bool, value: string, length: int}>
     */
    public static function getMtgRulesForMlManufacturerFields($idLang)
    {
        $m = self::getManufacturerPatternsForLang($idLang);
        $map = [
            'meta_title' => 'meta_title',
            'meta_description' => 'meta_description',
            'meta_keywords' => 'meta_keywords',
        ];
        $out = [];
        foreach ($map as $mtgKey => $mlField) {
            if (isset($m[$mtgKey])) {
                $out[$mlField] = $m[$mtgKey];
            }
        }

        return $out;
    }
}
