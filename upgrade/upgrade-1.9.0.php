<?php
/**
 * 2010-2026 2win.agency
 *
 * @author    2win.agency
 * @copyright 2010-2026 2win.agency
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * v1.9.0: Manufacturer AI generation, entity-scoped prompts, optional generation_log/job columns.
 *
 * @param Mlcategoryaidescription $module
 *
 * @return bool
 */
function upgrade_module_1_9_0($module)
{
    $db = Db::getInstance();
    $p = _DB_PREFIX_;

    $addColumnIfMissing = function ($table, $column, $definitionSql) use ($db, $p) {
        $row = $db->getRow('SHOW COLUMNS FROM `' . $p . bqSQL($table) . '` LIKE \'' . pSQL($column) . '\'');
        if (!$row) {
            return $db->execute('ALTER TABLE `' . $p . bqSQL($table) . '` ADD `' . bqSQL($column) . '` ' . $definitionSql);
        }

        return true;
    };

    // --- mlcategoryai_job_queue ---
    $addColumnIfMissing('mlcategoryai_job_queue', 'entity_type', 'VARCHAR(20) NOT NULL DEFAULT \'category\' AFTER `id_shop`');
    $addColumnIfMissing('mlcategoryai_job_queue', 'manufacturer_ids', 'TEXT NULL AFTER `category_ids`');
    $addColumnIfMissing('mlcategoryai_job_queue', 'last_processed_manufacturer_id', 'INT(11) UNSIGNED NULL AFTER `last_processed_category_id`');

    // --- mlcategoryai_generation_log ---
    $addColumnIfMissing('mlcategoryai_generation_log', 'entity_type', 'VARCHAR(20) NOT NULL DEFAULT \'category\' AFTER `id_generation_log`');
    $addColumnIfMissing('mlcategoryai_generation_log', 'id_manufacturer', 'INT(11) UNSIGNED NULL AFTER `id_category`');
    $db->execute('ALTER TABLE `' . $p . 'mlcategoryai_generation_log` MODIFY `id_category` INT(11) UNSIGNED NULL');

    // --- mlcategoryai_prompt_template ---
    $addColumnIfMissing('mlcategoryai_prompt_template', 'entity_type', 'VARCHAR(20) NOT NULL DEFAULT \'category\' AFTER `name`');
    $db->execute(
        'UPDATE `' . $p . 'mlcategoryai_prompt_template` SET `entity_type` = \'category\' WHERE `entity_type` = \'\' OR `entity_type` IS NULL'
    );

    $idx = $db->executeS('SHOW INDEX FROM `' . $p . 'mlcategoryai_prompt_template` WHERE Key_name = \'idx_shop_field_entity\'');
    if (!$idx) {
        $db->execute(
            'ALTER TABLE `' . $p . 'mlcategoryai_prompt_template` ADD INDEX `idx_shop_field_entity` (`id_shop`, `field_type`, `entity_type`)'
        );
    }

    return (bool) $module->installManufacturerDefaultPromptTemplates();
}
