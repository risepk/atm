<?php
// app/Controllers/PresetController.php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Services/PresetService.php';

class PresetController extends BaseController {
    private PresetService $presetService;

    public function __construct() {
        parent::__construct();
        $this->presetService = new PresetService();
    }

    public function apiGetForTemplate(Request $request, array $params): void {
        $templateId = (int)($params['template_id'] ?? 0);
        $presets = $this->presetService->getPresetsForTemplate($templateId, $this->userId());
        $this->jsonSuccess($presets);
    }

    public function apiSave(Request $request): void {
        $data = $request->all();
        $templateId = (int)($data['template_id'] ?? 0);
        $presetName = trim((string)($data['preset_name'] ?? ''));
        $fieldValues = is_array($data['field_values'] ?? null) ? $data['field_values'] : json_decode($data['field_values'] ?? '{}', true);
        $isGlobal = !empty($data['is_global']) && $this->isAdmin();

        if (!$templateId || !$presetName || empty($fieldValues)) {
            $this->jsonError('Template, preset name, and field values are required.', null, 422);
        }

        try {
            $preset = $this->presetService->savePreset($templateId, $presetName, $fieldValues, $this->userId(), $isGlobal, $this->isAdmin());
            $this->jsonSuccess($preset, 'Preset saved successfully.');
        } catch (Exception $e) {
            $this->jsonError($e->getMessage(), null, 400);
        }
    }

    public function apiDelete(Request $request, array $params): void {
        $id = (int)($params['id'] ?? 0);
        try {
            $this->presetService->deletePreset($id, $this->userId(), $this->isAdmin());
            $this->jsonSuccess(null, 'Preset deleted successfully.');
        } catch (Exception $e) {
            $this->jsonError($e->getMessage(), null, 400);
        }
    }
}
