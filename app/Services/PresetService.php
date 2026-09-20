<?php
// app/Services/PresetService.php

require_once __DIR__ . '/../Models/NarrationPreset.php';
require_once __DIR__ . '/../Models/NarrationTemplate.php';
require_once __DIR__ . '/../Models/TemplateField.php';
require_once __DIR__ . '/../Helpers/SecurityHelper.php';
require_once __DIR__ . '/AuditService.php';

class PresetService {
    private NarrationPreset $presetModel;
    private NarrationTemplate $templateModel;
    private TemplateField $fieldModel;
    private AuditService $auditService;

    public function __construct() {
        $this->presetModel = new NarrationPreset();
        $this->templateModel = new NarrationTemplate();
        $this->fieldModel = new TemplateField();
        $this->auditService = new AuditService();
    }

    public function getPresetsForTemplate(int $templateId, ?int $userId = null): array {
        return $this->presetModel->getPresetsForTemplate($templateId, $userId);
    }

    public function savePreset(int $templateId, string $presetName, array $fieldValues, ?int $userId, bool $isGlobal, bool $isAdmin): array {
        // 1. Validate Template Exists & is Active
        $template = $this->templateModel->find($templateId);
        if (!$template || (int)$template['is_active'] !== 1 || $template['deleted_at'] !== null) {
            throw new Exception("Template #{$templateId} does not exist or is inactive.");
        }

        // 2. Authorization on Global Presets
        if ($isGlobal && !$isAdmin) {
            throw new Exception("Only administrators can create global presets.");
        }

        // 3. Validate Preset Fields belong to this template
        $templateFields = $this->fieldModel->getFieldsByTemplateId($templateId);
        $validKeys = array_column($templateFields, 'field_key');

        $sanitizedValues = [];
        foreach ($fieldValues as $key => $val) {
            if (!in_array($key, $validKeys, true)) {
                throw new Exception("Invalid preset field key '{$key}' does not belong to template '{$template['name']}'.");
            }
            $sanitizedValues[$key] = trim((string)$val);
        }

        if (empty($sanitizedValues)) {
            throw new Exception("Preset must contain at least one valid field value.");
        }

        $presetData = [
            'uuid' => SecurityHelper::uuid(),
            'template_id' => $templateId,
            'user_id' => $isGlobal ? null : $userId,
            'preset_name' => SecurityHelper::clean($presetName),
            'field_values' => json_encode($sanitizedValues),
            'is_active' => 1,
            'sort_order' => 0
        ];

        $id = $this->presetModel->insert($presetData);
        $this->auditService->log('PRESET_CREATE', 'narration_presets', $id, null, $presetData);

        return $this->presetModel->find($id);
    }

    public function deletePreset(int $presetId, int $userId, bool $isAdmin): bool {
        $preset = $this->presetModel->find($presetId);
        if (!$preset) {
            throw new Exception("Preset not found.");
        }

        if (!$isAdmin && (int)$preset['user_id'] !== $userId) {
            throw new Exception("Unauthorized: You do not own this preset.");
        }

        $this->presetModel->softDelete($presetId, $userId);
        $this->auditService->log('PRESET_DELETE', 'narration_presets', $presetId, $preset, ['deleted_at' => date('Y-m-d H:i:s')]);
        return true;
    }
}
