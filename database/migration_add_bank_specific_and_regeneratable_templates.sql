-- ==============================================================================
-- Migration: Add bank_id to narration_templates and is_regeneratable to narration_template_fields
-- Safe for Live Production Server:
--  - Preserves all existing templates (bank_id = NULL means All Banks / Shared)
--  - Preserves all existing dynamic fields (is_regeneratable = 0 default)
-- ==============================================================================

-- 1. Add bank_id to narration_templates
ALTER TABLE `narration_templates` 
ADD COLUMN IF NOT EXISTS `bank_id` BIGINT(20) UNSIGNED NULL DEFAULT NULL AFTER `category`;

-- Optional Index for fast filtering by bank
ALTER TABLE `narration_templates` 
ADD INDEX IF NOT EXISTS `idx_templates_bank_id` (`bank_id`);

-- 2. Add is_regeneratable to narration_template_fields
ALTER TABLE `narration_template_fields` 
ADD COLUMN IF NOT EXISTS `is_regeneratable` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_required`;
