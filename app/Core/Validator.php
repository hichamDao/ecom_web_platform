<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Validation simple des données d'un formulaire.
 *
 * Exemple de règles :
 *   ['email' => 'required|email|max:160', 'password' => 'required|min:6']
 */
final class Validator
{
    private array $errors = [];

    /** @var array<string, string> */
    private array $validated = [];

    public function __construct(private array $data, private array $rules)
    {
        $this->run();
    }

    public static function make(array $data, array $rules): self
    {
        return new self($data, $rules);
    }

    private function run(): void
    {
        foreach ($this->rules as $field => $ruleString) {
            $rules = explode('|', $ruleString);
            $value = $this->data[$field] ?? null;

            foreach ($rules as $rule) {
                [$name, $parameter] = array_pad(explode(':', $rule, 2), 2, null);
                $label = $this->label($field);

                $error = match ($name) {
                    'required' => ($value === null || $value === '' || (is_array($value) && $value === []))
                        ? "Le champ $label est obligatoire."
                        : null,
                    'email' => ($value === null || $value === '' || filter_var($value, FILTER_VALIDATE_EMAIL))
                        ? null
                        : "Le champ $label doit être une adresse e-mail valide.",
                    'numeric' => ($value === null || $value === '' || is_numeric($value))
                        ? null
                        : "Le champ $label doit être un nombre.",
                    'integer' => ($value === null || $value === '' || filter_var($value, FILTER_VALIDATE_INT) !== false)
                        ? null
                        : "Le champ $label doit être un entier.",
                    'min' => ($value === null || $value === '' || $this->length($value) >= (float) $parameter)
                        ? null
                        : "$label doit contenir au moins $parameter caractères.",
                    'max' => ($value === null || $value === '' || $this->length($value) <= (float) $parameter)
                        ? null
                        : "$label ne doit pas dépasser $parameter caractères.",
                    'in' => ($value === null || $value === '' || in_array((string) $value, explode(',', (string) $parameter), true))
                        ? null
                        : "La valeur de $label est invalide.",
                    'confirmed' => $value === ($this->data[$field . '_confirmation'] ?? null)
                        ? null
                        : "La confirmation de $label ne correspond pas.",
                    'date' => ($value === null || $value === '' || strtotime((string) $value) !== false)
                        ? null
                        : "$label doit être une date valide.",
                    'boolean' => null,
                    default => null,
                };

                if ($error !== null) {
                    $this->errors[$field] = $error;
                    break;
                }
            }

            if (!isset($this->errors[$field])) {
                $this->validated[$field] = $value;
            }
        }
    }

    private function length(mixed $value): float
    {
        if (is_array($value)) {
            return (float) count($value);
        }
        if (is_numeric($value) && !is_string($value)) {
            return (float) mb_strlen((string) $value);
        }

        return (float) mb_strlen(trim((string) $value));
    }

    private function label(string $field): string
    {
        return ucfirst(str_replace(['_', 'password', 'email'], [' ', 'mot de passe', 'e-mail'], $field));
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    public function passes(): bool
    {
        return $this->errors === [];
    }

    /** @return array<string, string> */
    public function errors(): array
    {
        return $this->errors;
    }

    /** @return array<string, mixed> */
    public function validated(): array
    {
        return $this->validated;
    }
}
