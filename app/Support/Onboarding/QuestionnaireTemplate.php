<?php

namespace App\Support\Onboarding;

use App\Models\QuestionnaireQuestion;
use App\Models\QuestionnaireSection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The questionnaire edited in the admin builder. New questionnaires copy its
 * definition when they are created.
 */
final class QuestionnaireTemplate
{
    /**
     * The current template as a definition: section key → title, intro,
     * always-included flag and fields, in questionnaire order.
     *
     * @return array<string, array{title: string, intro: string, always: bool, fields: array<string, array<string, mixed>>}>
     */
    public static function definition(): array
    {
        return QuestionnaireSection::query()
            ->with('questions')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->mapWithKeys(fn (QuestionnaireSection $section) => [$section->key => [
                'title' => $section->title,
                'intro' => (string) $section->intro,
                'always' => $section->always_included,
                'fields' => $section->questions->mapWithKeys(fn (QuestionnaireQuestion $question) => [$question->key => $question->toField()])->all(),
            ]])
            ->all();
    }

    /**
     * Fills an empty template with the original questionnaire.
     */
    public static function seedDefaults(): void
    {
        if (QuestionnaireSection::query()->exists()) {
            return;
        }

        DB::transaction(function (): void {
            $position = 0;

            foreach (QuestionnaireForm::defaults() as $sectionKey => $definition) {
                $section = new QuestionnaireSection([
                    'title' => $definition['title'],
                    'intro' => $definition['intro'],
                    'always_included' => $definition['always'],
                ]);
                $section->key = $sectionKey;
                $section->sort_order = $position++;
                $section->save();

                $order = 0;

                foreach ($definition['fields'] as $fieldKey => $field) {
                    $question = new QuestionnaireQuestion([
                        'type' => $field['type'],
                        'label' => $field['label'],
                        'hint' => $field['hint'] ?? null,
                        'is_required' => (bool) ($field['required'] ?? false),
                        'options' => isset($field['options'])
                            ? collect($field['options'])->map(fn ($label, $value) => ['value' => (string) $value, 'label' => (string) $label])->values()->all()
                            : null,
                        'max_length' => $field['max'] ?? null,
                        'max_choices' => $field['max_items'] ?? null,
                    ]);
                    $question->key = $fieldKey;
                    $question->sort_order = $order++;
                    $section->questions()->save($question);
                }
            }
        });
    }

    /**
     * A stable fingerprint of a definition, ignoring the order of keys within
     * each field, for telling whether a questionnaire uses the latest questions.
     *
     * @param  array<string, array<string, mixed>>  $definition
     */
    public static function fingerprint(array $definition): string
    {
        $canonical = [];

        foreach ($definition as $sectionKey => $section) {
            $fields = [];

            foreach ($section['fields'] ?? [] as $fieldKey => $field) {
                $field['required'] = ! empty($field['required']);
                ksort($field);
                $fields[] = [$fieldKey, $field];
            }

            $canonical[] = [$sectionKey, (string) $section['title'], (string) ($section['intro'] ?? ''), ! empty($section['always']), $fields];
        }

        return sha1((string) json_encode($canonical));
    }

    /**
     * A storage key made from a label: lowercase letters, digits and
     * underscores, starting with a letter and unique among $taken.
     *
     * @param  list<string>  $taken
     */
    public static function makeKey(string $label, array $taken, string $fallback): string
    {
        $base = trim(Str::limit(Str::slug(Str::ascii($label), '_'), 40, ''), '_') ?: $fallback;

        if (! preg_match('/^[a-z]/', $base)) {
            $base = $fallback.'_'.$base;
        }

        $key = $base;
        $suffix = 2;

        while (in_array($key, $taken, true)) {
            $key = $base.'_'.$suffix++;
        }

        return $key;
    }
}
