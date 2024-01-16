<?php // src/Validator/ContainsAlphanumeric.php
namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ContainsAlphanumeric extends Constraint
{
    public string $message = 'The string "{{ string }}" contains an illegal character:'."\n".' it can only contain letters (accents allowed), numbers, hyphens or underscores.';
    // If the constraint has configuration options, define them as public properties
    public string $mode = 'strict';
}
