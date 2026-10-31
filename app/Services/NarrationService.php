<?php
// app/Services/NarrationService.php

require_once __DIR__ . '/../Models/NarrationTemplate.php';
require_once __DIR__ . '/../Models/TemplateField.php';
require_once __DIR__ . '/../Models/NarrationPreset.php';
require_once __DIR__ . '/../Models/MasterBank.php';
require_once __DIR__ . '/GeneratorService.php';

class NarrationService {
    private NarrationTemplate $templateModel;
    private TemplateField $fieldModel;
    private NarrationPreset $presetModel;
    private MasterBank $bankModel;

    public function __construct() {
        $this->templateModel = new NarrationTemplate();
        $this->fieldModel = new TemplateField();
        $this->presetModel = new NarrationPreset();
        $this->bankModel = new MasterBank();
    }

    public function getActiveTemplatesWithFields(?int $bankId = null): array {
        $templates = $this->templateModel->getActiveTemplates($bankId);
        foreach ($templates as &$tpl) {
            $tpl['fields'] = $this->fieldModel->getFieldsByTemplateId($tpl['id']);
            foreach ($tpl['fields'] as &$field) {
                if ($field['generator_rule']) {
                    $field['generator_rule'] = is_string($field['generator_rule']) 
                        ? json_decode($field['generator_rule'], true) 
                        : $field['generator_rule'];
                }
            }
        }
        return $templates;
    }

    public function getTemplateDetails(int $id): ?array {
        $tpl = $this->templateModel->findWithBank($id);
        if (!$tpl) return null;
        
        $fields = $this->fieldModel->getFieldsByTemplateId($id);
        foreach ($fields as &$f) {
            if ($f['generator_rule']) {
                $f['generator_rule'] = is_string($f['generator_rule']) 
                    ? json_decode($f['generator_rule'], true) 
                    : $f['generator_rule'];
            }
        }
        $tpl['fields'] = $fields;
        return $tpl;
    }

    /**
     * Validate template pattern and field definitions (Admin template management)
     */
    public function validateTemplateDefinition(string $pattern, array $fields): void {
        if (trim($pattern) === '') {
            throw new Exception("Pattern template string cannot be empty.");
        }

        // Extract tokens like {sender_name}, {stan}
        preg_match_all('/\{([a-zA-Z0-9_]+)\}/', $pattern, $matches);
        $patternTokens = $matches[1] ?? [];

        // Check for malformed braces like {sender name} or {unclosed
        if (preg_match('/\{[^a-zA-Z0-9_}]+\}/', $pattern)) {
            throw new Exception("Pattern contains invalid token syntax. Tokens must be alphanumeric with underscores only, e.g. {sender_name}.");
        }

        $submittedKeys = [];
        foreach ($fields as $f) {
            $key = trim($f['field_key'] ?? '');
            if ($key === '') {
                throw new Exception("Field key cannot be empty.");
            }
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $key)) {
                throw new Exception("Field key '{$key}' contains invalid characters. Only alphanumeric and underscores are allowed.");
            }
            if (in_array($key, $submittedKeys, true)) {
                throw new Exception("Duplicate field key '{$key}' detected in template fields.");
            }
            $submittedKeys[] = $key;

            $validTypes = [
                'text', 'number', 'amount', 'date', 'time', 'bank', 
                'account', 'masked_account', 'iban', 'masked_iban', 
                'reference_no', 'transaction_id', 'stan', 
                'card_number', 'masked_card', 'dropdown', 'textarea', 'pakistani_name',
                'atm_code', 'custom_pattern', 'custom'
            ];
            $type = $f['field_type'] ?? 'text';
            if (!in_array($type, $validTypes, true) && !$this->isCustomTypeValid($type)) {
                throw new Exception("Invalid field type '{$type}' for field '{$key}'.");
            }
        }

        // Verify every token in pattern has a matching defined field
        foreach ($patternTokens as $token) {
            if (!in_array($token, $submittedKeys, true)) {
                throw new Exception("Pattern placeholder '{".$token."}' does not have a corresponding dynamic field defined.");
            }
        }
    }

    /**
     * Check if a field type is registered in narration_custom_types
     */
    public function isCustomTypeValid(string $type): bool {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $type)) {
            return false;
        }
        try {
            require_once __DIR__ . '/../Models/CustomFieldType.php';
            $customTypeModel = new CustomFieldType();
            $found = $customTypeModel->findByCode($type);
            return !empty($found);
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Server-side validation of template payload when saving a transaction
     */
    public function validateTransactionPayload(int $templateId, ?int $version, array $payload): array {
        $tpl = $this->getTemplateDetails($templateId);
        if (!$tpl || (int)$tpl['is_active'] !== 1 || $tpl['deleted_at'] !== null) {
            throw new Exception("Template #{$templateId} is inactive or does not exist.");
        }

        if ($version !== null && (int)$tpl['version'] !== $version) {
            throw new Exception("Template version mismatch: submitted v{$version}, but active template is v{$tpl['version']}. Please refresh.");
        }

        $fieldsByKey = [];
        foreach ($tpl['fields'] as $f) {
            $fieldsByKey[$f['field_key']] = $f;
        }

        // 1. Check for required fields
        foreach ($fieldsByKey as $key => $f) {
            if ((int)$f['is_required'] === 1) {
                if (!isset($payload[$key]) || trim((string)$payload[$key]) === '') {
                    throw new Exception("Required template field '{$f['field_label']}' ({$key}) is missing or empty.");
                }
            }
        }

        // 2. Reject arbitrary unknown keys
        $sanitizedPayload = [];
        foreach ($payload as $k => $v) {
            if (!isset($fieldsByKey[$k])) {
                throw new Exception("Unexpected arbitrary field '{$k}' submitted for template '{$tpl['name']}'.");
            }
            $sanitizedPayload[$k] = trim((string)$v);
        }

        return [
            'template' => $tpl,
            'sanitized_payload' => $sanitizedPayload
        ];
    }

    /**
     * Compile narration by substituting tokens
     */
    public function compileNarration(string $pattern, array $values, bool $rejectUnreplaced = false): string {
        $compiled = $pattern;
        foreach ($values as $key => $val) {
            $token = '{' . $key . '}';
            $compiled = str_replace($token, trim((string)$val), $compiled);
        }

        if ($rejectUnreplaced && preg_match('/\{[a-zA-Z0-9_]+\}/', $compiled, $rem)) {
            throw new Exception("Narration compilation failed: Unreplaced placeholder '{$rem[0]}' found in description.");
        }

        return trim(preg_replace('/\s+/', ' ', $compiled));
    }
}
