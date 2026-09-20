<?php
// app/Controllers/TemplateController.php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Models/NarrationTemplate.php';
require_once __DIR__ . '/../Models/TemplateField.php';
require_once __DIR__ . '/../Services/NarrationService.php';
require_once __DIR__ . '/../Services/AuditService.php';
require_once __DIR__ . '/../Helpers/SecurityHelper.php';

class TemplateController extends BaseController {
    private NarrationTemplate $templateModel;
    private TemplateField $fieldModel;
    private NarrationService $narrationService;
    private AuditService $auditService;

    public function __construct() {
        parent::__construct();
        $this->templateModel = new NarrationTemplate();
        $this->fieldModel = new TemplateField();
        $this->narrationService = new NarrationService();
        $this->auditService = new AuditService();
    }

    public function index(Request $request): void {
        $templates = $this->templateModel->getAllTemplates();
        $this->render('templates/index', ['templates' => $templates]);
    }

    public function create(Request $request): void {
        $this->render('templates/create');
    }

    public function edit(Request $request, array $params): void {
        $id = (int)($params['id'] ?? 0);
        $tpl = $this->narrationService->getTemplateDetails($id);
        if (!$tpl) {
            Response::redirect('/templates');
        }
        $this->render('templates/edit', ['template' => $tpl]);
    }

    public function apiActive(Request $request): void {
        $templates = $this->narrationService->getActiveTemplatesWithFields();
        $this->jsonSuccess($templates);
    }

    public function apiSave(Request $request): void {
        $data = $request->all();
        $id = !empty($data['id']) ? (int)$data['id'] : null;

        $name = SecurityHelper::clean($data['name'] ?? '');
        $code = strtoupper(preg_replace('/[^A-Za-z0-9_]/', '', $data['code'] ?? ''));
        $pattern = trim((string)($data['pattern'] ?? ''));
        $category = SecurityHelper::clean($data['category'] ?? 'general');
        $description = SecurityHelper::clean($data['description'] ?? '');
        $fields = $data['fields'] ?? [];

        if (!$name || !$code || !$pattern) {
            $this->jsonError('Template name, unique code, and pattern are required.', null, 422);
        }

        try {
            // Point 8: Server-Side Narration Token & Field Validation
            $this->narrationService->validateTemplateDefinition($pattern, $fields);
        } catch (Exception $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }

        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            if ($id) {
                $existing = $this->templateModel->find($id);
                if (!$existing) {
                    throw new Exception("Template #{$id} not found.");
                }

                // Increment version monotonically to maintain historical snapshot validity
                $newVersion = ((int)($existing['version'] ?? 1)) + 1;

                $updateData = [
                    'name' => $name,
                    'code' => $code,
                    'category' => $category,
                    'pattern' => $pattern,
                    'description' => $description,
                    'version' => $newVersion,
                    'is_active' => isset($data['is_active']) ? (int)$data['is_active'] : 1
                ];

                $this->templateModel->update($id, $updateData);
                $this->fieldModel->deleteByTemplateId($id);
                $templateId = $id;

                $this->auditService->log('TEMPLATE_UPDATE', 'narration_templates', $templateId, $existing, $updateData);
            } else {
                $insertData = [
                    'uuid' => SecurityHelper::uuid(),
                    'name' => $name,
                    'code' => $code,
                    'category' => $category,
                    'pattern' => $pattern,
                    'description' => $description,
                    'version' => 1,
                    'is_active' => 1,
                    'sort_order' => 0,
                    'created_by' => $this->userId()
                ];

                $templateId = $this->templateModel->insert($insertData);
                $this->auditService->log('TEMPLATE_CREATE', 'narration_templates', $templateId, null, $insertData);
            }

            // Save Validated Dynamic Fields
            $sort = 1;
            foreach ($fields as $f) {
                if (empty($f['field_key']) || empty($f['field_label'])) continue;

                $generatorRule = null;
                if (!empty($f['generator_rule'])) {
                    $generatorRule = is_array($f['generator_rule']) ? json_encode($f['generator_rule']) : $f['generator_rule'];
                }

                $fieldData = [
                    'template_id' => $templateId,
                    'field_key' => preg_replace('/[^A-Za-z0-9_]/', '', $f['field_key']),
                    'field_label' => SecurityHelper::clean($f['field_label']),
                    'field_type' => $f['field_type'] ?? 'text',
                    'placeholder' => SecurityHelper::clean($f['placeholder'] ?? ''),
                    'default_value' => SecurityHelper::clean($f['default_value'] ?? ''),
                    'is_required' => !empty($f['is_required']) ? 1 : 0,
                    'generator_rule' => $generatorRule,
                    'options_source' => SecurityHelper::clean($f['options_source'] ?? ''),
                    'sort_order' => $sort++
                ];

                $this->fieldModel->insert($fieldData);
            }

            $db->commit();
            $this->jsonSuccess(['id' => $templateId], 'Template saved successfully.');
        } catch (Exception $e) {
            $db->rollBack();
            $this->jsonError($e->getMessage(), null, 400);
        }
    }
}
