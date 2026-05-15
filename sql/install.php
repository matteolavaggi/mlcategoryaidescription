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

$sql = [];

// Generation log table - tracks all AI generation history
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'mlcategoryai_generation_log` (
    `id_generation_log` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `entity_type` VARCHAR(20) NOT NULL DEFAULT "category" COMMENT "category|manufacturer",
    `id_category` INT(11) UNSIGNED DEFAULT NULL,
    `id_manufacturer` INT(11) UNSIGNED DEFAULT NULL,
    `id_lang` INT(11) UNSIGNED NOT NULL,
    `id_shop` INT(11) UNSIGNED NOT NULL DEFAULT 1,
    `field_type` VARCHAR(50) NOT NULL COMMENT "description|meta_title|meta_description",
    `generated_at` DATETIME NOT NULL,
    `model_used` VARCHAR(100) DEFAULT NULL,
    `prompt_hash` VARCHAR(64) DEFAULT NULL,
    `tokens_used` INT(11) DEFAULT 0,
    `status` VARCHAR(20) NOT NULL DEFAULT "success" COMMENT "success|error|pending",
    `error_message` TEXT DEFAULT NULL,
    PRIMARY KEY (`id_generation_log`),
    KEY `idx_category_lang` (`id_category`, `id_lang`),
    KEY `idx_manufacturer_lang` (`id_manufacturer`, `id_lang`),
    KEY `idx_entity_type` (`entity_type`),
    KEY `idx_generated_at` (`generated_at`),
    KEY `idx_status` (`status`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

// Job queue table - manages batch processing jobs with resume support
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'mlcategoryai_job_queue` (
    `id_job` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_shop` INT(11) UNSIGNED NOT NULL DEFAULT 1,
    `entity_type` VARCHAR(20) NOT NULL DEFAULT "category" COMMENT "category|manufacturer",
    `job_type` VARCHAR(50) NOT NULL DEFAULT "batch_generation" COMMENT "batch_generation",
    `status` VARCHAR(20) NOT NULL DEFAULT "pending" COMMENT "pending|running|paused|completed|failed",
    `total_items` INT(11) NOT NULL DEFAULT 0,
    `processed_items` INT(11) NOT NULL DEFAULT 0,
    `failed_items` INT(11) NOT NULL DEFAULT 0,
    `category_ids` TEXT NOT NULL COMMENT "JSON array of category IDs",
    `manufacturer_ids` TEXT DEFAULT NULL COMMENT "JSON array of manufacturer IDs",
    `language_ids` TEXT NOT NULL COMMENT "JSON array of language IDs",
    `fields_to_generate` VARCHAR(255) NOT NULL COMMENT "JSON array: description,meta_title,meta_description",
    `write_mode` VARCHAR(20) NOT NULL DEFAULT "fill_missing" COMMENT "overwrite|fill_missing",
    `use_google_translate` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT "0=OpenAI only, 1=OpenAI+GT",
    `primary_language_id` INT(10) UNSIGNED DEFAULT NULL COMMENT "Language ID for OpenAI generation",
    `translate_language_ids` TEXT DEFAULT NULL COMMENT "JSON array of target language IDs",
    `phase` VARCHAR(20) NOT NULL DEFAULT "openai" COMMENT "openai|translate|completed",
    `current_translate_lang_index` INT(10) UNSIGNED NOT NULL DEFAULT 0,
    `current_translate_position` INT(10) UNSIGNED NOT NULL DEFAULT 0,
    `current_position` INT(11) NOT NULL DEFAULT 0,
    `last_processed_category_id` INT(11) DEFAULT NULL,
    `last_processed_manufacturer_id` INT(11) UNSIGNED DEFAULT NULL,
    `last_processed_lang_id` INT(11) DEFAULT NULL,
    `created_at` DATETIME NOT NULL,
    `started_at` DATETIME DEFAULT NULL,
    `completed_at` DATETIME DEFAULT NULL,
    `updated_at` DATETIME NOT NULL,
    `error_log` TEXT DEFAULT NULL,
    PRIMARY KEY (`id_job`),
    KEY `idx_status` (`status`),
    KEY `idx_shop` (`id_shop`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

// Prompt template table - stores customizable prompt templates
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'mlcategoryai_prompt_template` (
    `id_prompt_template` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_shop` INT(11) UNSIGNED NOT NULL DEFAULT 1,
    `name` VARCHAR(128) NOT NULL,
    `entity_type` VARCHAR(20) NOT NULL DEFAULT "category" COMMENT "category|manufacturer",
    `field_type` VARCHAR(50) NOT NULL COMMENT "description|meta_title|meta_description",
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    PRIMARY KEY (`id_prompt_template`),
    KEY `idx_field_type` (`field_type`),
    KEY `idx_active` (`is_active`),
    KEY `idx_shop` (`id_shop`),
    KEY `idx_shop_field_entity` (`id_shop`, `field_type`, `entity_type`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

// Prompt template language table - multilanguage prompts
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'mlcategoryai_prompt_template_lang` (
    `id_prompt_template` INT(11) UNSIGNED NOT NULL,
    `id_lang` INT(11) UNSIGNED NOT NULL,
    `prompt_template` TEXT NOT NULL,
    PRIMARY KEY (`id_prompt_template`, `id_lang`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

// Performance metrics table - lightweight execution stats
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'mlcategoryai_run_stats` (
    `id_run` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_job` INT(11) UNSIGNED DEFAULT NULL,
    `id_shop` INT(11) UNSIGNED NOT NULL DEFAULT 1,
    `started_at` DATETIME NOT NULL,
    `completed_at` DATETIME DEFAULT NULL,
    `execution_time_ms` INT(11) UNSIGNED DEFAULT NULL,
    `categories_count` INT(11) UNSIGNED NOT NULL DEFAULT 0,
    `languages_count` INT(11) UNSIGNED NOT NULL DEFAULT 0,
    `fields_count` INT(11) UNSIGNED NOT NULL DEFAULT 0,
    `items_processed` INT(11) UNSIGNED NOT NULL DEFAULT 0,
    `items_skipped` INT(11) UNSIGNED NOT NULL DEFAULT 0,
    `items_failed` INT(11) UNSIGNED NOT NULL DEFAULT 0,
    `write_mode` VARCHAR(20) NOT NULL DEFAULT "fill_missing",
    `tokens_input` INT(11) UNSIGNED NOT NULL DEFAULT 0,
    `tokens_output` INT(11) UNSIGNED NOT NULL DEFAULT 0,
    `parallel_requests` TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `avg_request_time_ms` INT(11) UNSIGNED DEFAULT NULL,
    PRIMARY KEY (`id_run`),
    KEY `idx_started` (`started_at`),
    KEY `idx_job` (`id_job`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

foreach ($sql as $query) {
    if (!Db::getInstance()->execute($query)) {
        return false;
    }
}

return true;
