<?php
namespace App\Core;

/**
 * Rule-based input validation. Rules are pipe-delimited strings, e.g.
 *   ['email' => 'required|email', 'age' => 'required|int|min:18']
 */
class Validator
{
    private array $data;
    private array $rules;
    private array $errors = [];

    public function __construct(array $data, array $rules)
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->run();
    }

    private function run(): void
    {
        foreach ($this->rules as $field => $ruleset) {
            $value = $this->data[$field] ?? null;
            foreach (explode('|', $ruleset) as $rule) {
                [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);
                $this->apply($field, $value, $name, $param);
            }
        }
    }

    private function apply(string $field, $value, string $rule, ?string $param): void
    {
        $label = ucwords(str_replace('_', ' ', $field));
        switch ($rule) {
            case 'required':
                if ($value === null || $value === '' || (is_array($value) && count($value) === 0)) {
                    $this->add($field, "$label is required.");
                }
                break;
            case 'email':
                if ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->add($field, "$label must be a valid email.");
                }
                break;
            case 'int':
                if ($value !== null && $value !== '' && filter_var($value, FILTER_VALIDATE_INT) === false) {
                    $this->add($field, "$label must be an integer.");
                }
                break;
            case 'numeric':
                if ($value !== null && $value !== '' && !is_numeric($value)) {
                    $this->add($field, "$label must be numeric.");
                }
                break;
            case 'min':
                if ($value === null || $value === '') {
                    break; // nothing to check; use 'required' to enforce presence
                }
                if (is_numeric($value)) {
                    if ((float) $value < (float) $param) {
                        $this->add($field, "$label must be at least $param.");
                    }
                } elseif (is_string($value) && mb_strlen($value) < (int) $param) {
                    $this->add($field, "$label must be at least $param characters.");
                }
                break;
            case 'max':
                if ($value === null || $value === '') {
                    break;
                }
                if (is_numeric($value)) {
                    if ((float) $value > (float) $param) {
                        $this->add($field, "$label must not exceed $param.");
                    }
                } elseif (is_string($value) && mb_strlen($value) > (int) $param) {
                    $this->add($field, "$label must not exceed $param characters.");
                }
                break;
            case 'date':
                if ($value && strtotime($value) === false) {
                    $this->add($field, "$label must be a valid date.");
                }
                break;
            case 'in':
                $options = explode(',', (string) $param);
                if ($value !== null && $value !== '' && !in_array($value, $options, true)) {
                    $this->add($field, "$label is invalid.");
                }
                break;
            case 'confirmed':
                if ($value !== ($this->data[$field . '_confirmation'] ?? null)) {
                    $this->add($field, "$label confirmation does not match.");
                }
                break;
        }
    }

    private function add(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    public function passes(): bool
    {
        return empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function flatErrors(): array
    {
        $flat = [];
        foreach ($this->errors as $messages) {
            foreach ($messages as $m) {
                $flat[] = $m;
            }
        }
        return $flat;
    }

    /** Return only the keys that had rules defined. */
    public function validated(): array
    {
        return array_intersect_key($this->data, $this->rules);
    }
}
