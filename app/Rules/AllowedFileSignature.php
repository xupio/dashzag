<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class AllowedFileSignature implements ValidationRule
{
    /**
     * @param  array<string>  $allowedMimeTypes
     */
    public function __construct(
        protected array $allowedMimeTypes,
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            $fail('The :attribute must be a valid uploaded file.');

            return;
        }

        $detectedMimeType = $value->getMimeType();

        if (! in_array($detectedMimeType, $this->allowedMimeTypes, true)) {
            $fail('The :attribute file content is not allowed.');
        }
    }
}
