<?php

namespace App\Enums;

enum ApplicationStage: string
{
    use Concerns;

    case Received = 'received';
    case UnderReview = 'under_review';
    case Shortlisted = 'shortlisted';
    case Interview = 'interview';
    case Offered = 'offered';
    case Hired = 'hired';
    case Declined = 'declined';
    case Withdrawn = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::Received => 'Received',
            self::UnderReview => 'Under review',
            self::Shortlisted => 'Shortlisted',
            self::Interview => 'Interview',
            self::Offered => 'Offered',
            self::Hired => 'Hired',
            self::Declined => 'Declined',
            self::Withdrawn => 'Withdrawn',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Received => 'info',
            self::UnderReview, self::Shortlisted, self::Interview => 'warning',
            self::Offered, self::Hired => 'success',
            self::Declined, self::Withdrawn => 'muted',
        };
    }

    /**
     * Whether the stage concludes the recruitment process for the applicant.
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::Hired, self::Declined, self::Withdrawn], true);
    }
}
