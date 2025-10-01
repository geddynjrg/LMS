<?php

namespace App\Actions\Fortify;

use Laravel\Fortify\Rules\Password;

trait PasswordValidationRules
{
    /**
     * Get the validation rules used to validate passwords.
     *
     * @return array<int, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    protected function passwordRules(): array
    {
        // Require at least one uppercase character...
        //(new Password)->requireUppercase();

        // Require at least one numeric character...
        //(new Password)->requireNumeric();

        // Require at least one special character...
        ///(new Password)->requireSpecialCharacter();

        return ['required', 'string', (new Password)->requireUppercase()->requireNumeric(), 'confirmed'];
    }



}
