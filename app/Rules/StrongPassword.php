<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class StrongPassword implements ValidationRule
{
    /**
     * Minimum password length
     */
    const MIN_LENGTH = 8;

    /**
     * Create a new rule instance.
     */
    public function __construct()
    {
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): bool
    {
        if (!is_string($value)) {
            return false;
        }

        // Check minimum length
        if (strlen($value) < self::MIN_LENGTH) {
            return false;
        }

        // Check for at least one uppercase letter
        if (!preg_match('/[A-Z]/', $value)) {
            return false;
        }

        // Check for at least one lowercase letter
        if (!preg_match('/[a-z]/', $value)) {
            return false;
        }

        // Check for at least one number
        if (!preg_match('/[0-9]/', $value)) {
            return false;
        }

        // Check for at least one special character
        if (!preg_match('/[!@#$%^&*()_+=\-\[\]{}|;:,.<>?]/', $value)) {
            return false;
        }

        // Check for common weak passwords
        $weakPasswords = [
            'password',
            '123456',
            '123456789',
            'qwerty',
            'abc123',
            'password123',
            'admin',
            'letmein',
            'welcome',
            'monkey',
            'dragon',
            'master',
            'hello',
            'freedom',
            'whatever',
            'access',
            'michael',
            'ninja',
            'shadow',
            'superman',
            'batman',
            'trustno1',
            '1234',
            '1111',
            '12345',
            '123123',
            'password1',
            'admin123',
            'root',
            'toor',
            'user',
            'test',
            'guest',
        ];

        if (in_array(strtolower($value), $weakPasswords)) {
            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return __('validation.password.strong', [
            'min_length' => self::MIN_LENGTH,
        ]);
    }

    /**
     * Get password strength score
     */
    public static function getStrengthScore(string $password): int
    {
        $score = 0;

        // Length bonus
        if (strlen($password) >= 8) {
            $score += 1;
        }
        if (strlen($password) >= 12) {
            $score += 1;
        }

        // Character variety
        if (preg_match('/[a-z]/', $password)) $score++;
        if (preg_match('/[A-Z]/', $password)) $score++;
        if (preg_match('/[0-9]/', $password)) $score++;
        if (preg_match('/[!@#$%^&*()_+=\-\[\]{}|;:,.<>?]/', $password)) $score++;

        // Penalty for common patterns
        if (preg_match('/^[a-zA-Z]+$/', $password)) $score -= 1; // All letters
        if (preg_match('/^[0-9]+$/', $password)) $score -= 1; // All numbers
        if (preg_match('/^[a-zA-Z]+[0-9]+$/', $password)) $score -= 1; // Letters then numbers
        if (preg_match('/^[0-9]+[a-zA-Z]+$/', $password)) $score -= 1; // Numbers then letters

        return max(0, min(5, $score));
    }

    /**
     * Get password strength text
     */
    public static function getStrengthText(int $score): string
    {
        if ($score <= 2) {
            return 'Weak';
        } elseif ($score <= 3) {
            return 'Fair';
        } elseif ($score <= 4) {
            return 'Good';
        } else {
            return 'Strong';
        }
    }
}
