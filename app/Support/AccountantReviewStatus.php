<?php

namespace App\Support;

final class AccountantReviewStatus
{
    public const PENDING = 'pending';
    public const APPROVED = 'approved';
    public const RETURNED_TO_HQ = 'returned_to_hq';
    public const RETURNED_TO_CONSTITUENCY = 'returned_to_constituency';

    public static function labels(): array
    {
        return [
            self::PENDING => 'Pending accountant review',
            self::APPROVED => 'Approved by accounts',
            self::RETURNED_TO_HQ => 'Returned to HQ',
            self::RETURNED_TO_CONSTITUENCY => 'Returned to constituency',
        ];
    }
}
