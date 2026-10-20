<?php

namespace App\Enums;

enum EmploymentType: string
{
    use Concerns;

    case FullTime = 'full_time';
    case PartTime = 'part_time';
    case Contract = 'contract';
    case Internship = 'internship';
    case NationalService = 'national_service';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::FullTime => 'Full-time',
            self::PartTime => 'Part-time',
            self::Contract => 'Contract',
            self::Internship => 'Internship',
            self::NationalService => 'National service',
            self::Other => 'Other',
        };
    }

    /**
     * Map to the schema.org JobPosting employmentType vocabulary.
     */
    public function schemaValue(): string
    {
        return match ($this) {
            self::FullTime => 'FULL_TIME',
            self::PartTime => 'PART_TIME',
            self::Contract => 'CONTRACTOR',
            self::Internship, self::NationalService => 'INTERN',
            self::Other => 'OTHER',
        };
    }
}
