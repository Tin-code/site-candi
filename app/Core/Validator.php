<?php

namespace App\Core;

class Validator
{
    private array $errors = [];
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public static function make(array $data, array $rules): self
    {
        $v = new self($data);
        foreach ($rules as $field => $ruleSet) {
            $v->applyRules($field, $ruleSet);
        }
        return $v;
    }

    public function passes(): bool
    {
        return empty($this->errors);
    }

    public function fails(): bool
    {
        return !$this->passes();
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }

    // -------------------------------------------------------------------------
    // Application des règles
    // -------------------------------------------------------------------------

    private function applyRules(string $field, string|array $ruleSet): void
    {
        $rules = is_string($ruleSet) ? explode('|', $ruleSet) : $ruleSet;
        $value = $this->data[$field] ?? null;

        foreach ($rules as $rule) {
            [$ruleName, $param] = array_pad(explode(':', $rule, 2), 2, null);

            if ($ruleName === 'required' && ($value === null || $value === '')) {
                $this->addError($field, "Le champ {$field} est requis.");
                return; // Pas la peine de continuer si requis manquant
            }

            if ($value === null || $value === '') {
                continue; // Champ optionnel vide : on ignore les autres règles
            }

            match ($ruleName) {
                'email'     => filter_var($value, FILTER_VALIDATE_EMAIL) === false
                                    ? $this->addError($field, "L'adresse email est invalide.") : null,
                'min'       => strlen((string) $value) < (int) $param
                                    ? $this->addError($field, "Le champ {$field} doit contenir au moins {$param} caractères.") : null,
                'max'       => strlen((string) $value) > (int) $param
                                    ? $this->addError($field, "Le champ {$field} ne doit pas dépasser {$param} caractères.") : null,
                'numeric'   => !is_numeric($value)
                                    ? $this->addError($field, "Le champ {$field} doit être un nombre.") : null,
                'integer'   => filter_var($value, FILTER_VALIDATE_INT) === false
                                    ? $this->addError($field, "Le champ {$field} doit être un entier.") : null,
                'phone'     => !preg_match('/^\+?[0-9\s\-]{8,20}$/', (string) $value)
                                    ? $this->addError($field, "Le numéro de téléphone est invalide.") : null,
                'confirmed' => ($this->data[$field . '_confirmation'] ?? null) !== $value
                                    ? $this->addError($field, "La confirmation du champ {$field} ne correspond pas.") : null,
                'in'        => !in_array($value, explode(',', $param ?? ''), true)
                                    ? $this->addError($field, "La valeur du champ {$field} est invalide.") : null,
                'url'       => filter_var($value, FILTER_VALIDATE_URL) === false
                                    ? $this->addError($field, "L'URL est invalide.") : null,
                'date'      => strtotime((string) $value) === false
                                    ? $this->addError($field, "La date est invalide.") : null,
                'otp'       => !preg_match('/^\d{6}$/', (string) $value)
                                    ? $this->addError($field, "Le code OTP doit contenir 6 chiffres.") : null,
                default     => null,
            };
        }
    }

    private function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }
}
