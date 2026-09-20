<?php
// app/Core/Validator.php

class Validator {
    private array $data;
    private array $errors = [];

    public function __construct(array $data) {
        $this->data = $data;
    }

    public static function make(array $data): Validator {
        return new self($data);
    }

    public function validate(array $rules): bool {
        foreach ($rules as $field => $ruleString) {
            $ruleList = is_array($ruleString) ? $ruleString : explode('|', $ruleString);
            $val = $this->data[$field] ?? null;

            foreach ($ruleList as $r) {
                $params = [];
                if (strpos($r, ':') !== false) {
                    [$ruleName, $paramStr] = explode(':', $r, 2);
                    $params = explode(',', $paramStr);
                } else {
                    $ruleName = $r;
                }

                $this->applyRule($field, $val, $ruleName, $params);
            }
        }
        return empty($this->errors);
    }

    private function applyRule(string $field, $val, string $rule, array $params): void {
        $label = ucwords(str_replace('_', ' ', $field));

        switch ($rule) {
            case 'required':
                if ($val === null || $val === '' || (is_array($val) && empty($val))) {
                    $this->addError($field, "{$label} is required.");
                }
                break;

            case 'numeric':
                if ($val !== null && $val !== '' && !is_numeric($val)) {
                    $this->addError($field, "{$label} must be a valid number.");
                }
                break;

            case 'min':
                $min = (float)($params[0] ?? 0);
                if (is_numeric($val) && (float)$val < $min) {
                    $this->addError($field, "{$label} must be at least {$min}.");
                } elseif (is_string($val) && mb_strlen($val) < $min) {
                    $this->addError($field, "{$label} must be at least {$min} characters.");
                }
                break;

            case 'max':
                $max = (float)($params[0] ?? 0);
                if (is_numeric($val) && (float)$val > $max) {
                    $this->addError($field, "{$label} may not be greater than {$max}.");
                } elseif (is_string($val) && mb_strlen($val) > $max) {
                    $this->addError($field, "{$label} may not be greater than {$max} characters.");
                }
                break;

            case 'email':
                if ($val !== null && $val !== '' && !filter_var($val, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, "{$label} must be a valid email address.");
                }
                break;

            case 'in':
                if ($val !== null && $val !== '' && !in_array((string)$val, $params, true)) {
                    $this->addError($field, "{$label} is invalid.");
                }
                break;

            case 'date':
                if ($val !== null && $val !== '') {
                    $d = DateTime::createFromFormat('Y-m-d', $val);
                    if (!$d || $d->format('Y-m-d') !== $val) {
                        $this->addError($field, "{$label} must be a valid date (YYYY-MM-DD).");
                    }
                }
                break;

            case 'positive':
                if ($val !== null && $val !== '' && (float)$val <= 0) {
                    $this->addError($field, "{$label} must be greater than zero.");
                }
                break;

            case 'non_negative':
                if ($val !== null && $val !== '' && (float)$val < 0) {
                    $this->addError($field, "{$label} cannot be negative.");
                }
                break;
        }
    }

    public function addError(string $field, string $message): void {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }

    public function getErrors(): array {
        return $this->errors;
    }

    public function getFirstErrors(): array {
        $res = [];
        foreach ($this->errors as $f => $errs) {
            $res[$f] = $errs[0] ?? '';
        }
        return $res;
    }
}
