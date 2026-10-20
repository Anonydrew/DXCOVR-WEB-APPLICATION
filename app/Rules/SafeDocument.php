<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use ZipArchive;

/**
 * Validates CV uploads by their contents: PDF, legacy Word (.doc) or Word
 * (.docx). DOCX files are ZIP containers, so a ZIP is only accepted when it
 * really is a Word document and contains no macros.
 */
class SafeDocument implements ValidationRule
{
    /**
     * @param  list<string>  $extensions
     */
    public function __construct(protected array $extensions = ['pdf', 'doc', 'docx'], protected int $maxKilobytes = 5120) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            $fail('The :attribute could not be uploaded. Please try again.');

            return;
        }

        if ($value->getSize() > $this->maxKilobytes * 1024) {
            $fail('The :attribute must not be larger than '.round($this->maxKilobytes / 1024, 1).' MB.');

            return;
        }

        $clientExtension = strtolower($value->getClientOriginalExtension());
        $detected = strtolower((string) $value->guessExtension());

        if (! in_array($clientExtension, $this->extensions, true)) {
            $fail('The :attribute must be a '.$this->humanList().' file.');

            return;
        }

        if ($clientExtension === 'docx' && in_array($detected, ['docx', 'zip'], true)) {
            if (! $this->isWordDocument($value->getRealPath())) {
                $fail('The :attribute does not appear to be a valid Word document.');
            }

            return;
        }

        if ($detected !== $clientExtension) {
            $fail('The :attribute must be a '.$this->humanList().' file.');
        }
    }

    protected function isWordDocument(string $path): bool
    {
        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            return false;
        }

        $valid = $zip->locateName('word/document.xml') !== false
            && $zip->locateName('[Content_Types].xml') !== false
            && $zip->locateName('word/vbaProject.bin') === false;

        $zip->close();

        return $valid;
    }

    protected function humanList(): string
    {
        return collect($this->extensions)->map(fn (string $extension) => strtoupper($extension))->join(', ', ' or ');
    }
}
