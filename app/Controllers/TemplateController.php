<?php
// app/Controllers/TemplateController.php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Models/NarrationTemplate.php';
require_once __DIR__ . '/../Models/TemplateField.php';
require_once __DIR__ . '/../Models/MasterBank.php';
require_once __DIR__ . '/../Models/CustomFieldType.php';
require_once __DIR__ . '/../Services/NarrationService.php';
require_once __DIR__ . '/../Services/AuditService.php';
require_once __DIR__ . '/../Helpers/SecurityHelper.php';

class TemplateController extends BaseController {
    private NarrationTemplate $templateModel;
    private TemplateField $fieldModel;
    private MasterBank $bankModel;
    private CustomFieldType $customTypeModel;
    private NarrationService $narrationService;
    private AuditService $auditService;

    public function __construct() {
        parent::__construct();
        $this->templateModel = new NarrationTemplate();
        $this->fieldModel = new TemplateField();
        $this->bankModel = new MasterBank();
        $this->customTypeModel = new CustomFieldType();
        $this->narrationService = new NarrationService();
        $this->auditService = new AuditService();
    }

    public function index(Request $request): void {
        $templates = $this->templateModel->getAllTemplates();
        $this->render('templates/index', ['templates' => $templates]);
    }

    public function create(Request $request): void {
        $banks = $this->bankModel->getActiveBanks();
        $customTypes = $this->customTypeModel->getActiveTypes();
        $this->render('templates/create', [
            'banks' => $banks,
            'customTypes' => $customTypes
        ]);
    }

    public function edit(Request $request, array $params): void {
        $id = (int)($params['id'] ?? 0);
        $tpl = $this->narrationService->getTemplateDetails($id);
        if (!$tpl) {
            Response::redirect('/templates');
        }
        $banks = $this->bankModel->getActiveBanks();
        $customTypes = $this->customTypeModel->getActiveTypes();
        $this->render('templates/edit', [
            'template' => $tpl, 
            'banks' => $banks,
            'customTypes' => $customTypes
        ]);
    }

    public function apiActive(Request $request): void {
        $bankId = $request->get('bank_id');
        $bankId = ($bankId !== null && $bankId !== '') ? (int)$bankId : null;
        $templates = $this->narrationService->getActiveTemplatesWithFields($bankId);
        $this->jsonSuccess($templates);
    }

    public function apiSave(Request $request): void {
        $data = $request->all();
        $id = !empty($data['id']) ? (int)$data['id'] : null;

        $name = SecurityHelper::clean($data['name'] ?? '');
        $code = strtoupper(preg_replace('/[^A-Za-z0-9_]/', '', $data['code'] ?? ''));
        $pattern = trim((string)($data['pattern'] ?? ''));
        $category = SecurityHelper::clean($data['category'] ?? 'general');
        $bankId = (!empty($data['bank_id']) && (int)$data['bank_id'] > 0) ? (int)$data['bank_id'] : null;
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
                    'bank_id' => $bankId,
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
                    'bank_id' => $bankId,
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
                } elseif (!empty($f['options_source'])) {
                    $opt = trim((string)$f['options_source']);
                    // If options_source contains pattern tokens or type is custom_pattern, save as generator_rule
                    if (strpos($opt, '#') !== -1 || strpos($opt, '@') !== -1 || strpos($opt, '[') !== -1 || ($f['field_type'] ?? '') === 'custom_pattern') {
                        $generatorRule = json_encode(['pattern' => $opt, 'type' => $f['field_type'] ?? 'custom']);
                    }
                }

                $isRegen = (!empty($f['is_regeneratable']) || !empty($f['regenerate'])) ? 1 : 0;

                $fieldData = [
                    'template_id' => $templateId,
                    'field_key' => preg_replace('/[^A-Za-z0-9_]/', '', $f['field_key']),
                    'field_label' => SecurityHelper::clean($f['field_label']),
                    'field_type' => $f['field_type'] ?? 'text',
                    'placeholder' => SecurityHelper::clean($f['placeholder'] ?? ''),
                    'default_value' => SecurityHelper::clean($f['default_value'] ?? ''),
                    'is_required' => !empty($f['is_required']) ? 1 : 0,
                    'is_regeneratable' => $isRegen,
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

    public function apiCustomTypesList(Request $request): void {
        $types = $this->customTypeModel->getActiveTypes();
        $this->jsonSuccess($types);
    }

    public function apiCustomTypesSave(Request $request): void {
        $data = $request->all();
        $name = SecurityHelper::clean($data['type_name'] ?? '');
        $code = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $data['type_code'] ?? ''));
        $pattern = trim((string)($data['pattern'] ?? ''));
        $description = SecurityHelper::clean($data['description'] ?? '');

        if (!$name || !$code || !$pattern) {
            $this->jsonError('Type Name, unique Code, and Pattern are required.', null, 422);
        }

        $id = !empty($data['id']) ? (int)$data['id'] : 0;
        
        $existing = $this->customTypeModel->findByCode($code);
        if ($existing && ($id <= 0 || (int)$existing['id'] !== $id)) {
            $this->jsonError("Type code '{$code}' already exists. Please choose a unique code.", null, 422);
        }

        $savedId = $this->customTypeModel->saveType([
            'id' => $id,
            'type_code' => $code,
            'type_name' => $name,
            'pattern' => $pattern,
            'description' => $description,
            'is_regeneratable' => 1,
            'is_active' => 1
        ]);

        $saved = $this->customTypeModel->find($savedId);
        $this->jsonSuccess($saved, 'Custom banking type saved successfully.');
    }

    public function apiCustomTypesDelete(Request $request, array $params): void {
        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            $this->jsonError('Invalid custom type ID.');
        }
        $this->customTypeModel->update($id, ['is_active' => 0, 'updated_at' => date('Y-m-d H:i:s')]);
        $this->jsonSuccess(null, 'Custom type removed.');
    }
}
