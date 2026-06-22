<?php

namespace App\Support;

final class PhaseDocumentType
{
    public const MINUTES = 'minutes';
    public const BANK_RECONCILIATION = 'bank_reconciliation';
    public const APPROVED_CODE_LIST = 'approved_code_list';
    public const SIGNED_APPROVED_CODE_LIST = 'signed_approved_code_list';
    public const MANAGEMENT_PROPOSAL_REVIEW_MINUTES = 'management_proposal_review_minutes';
    public const SUPPORTING_DOCS = 'supporting_docs';

    public static function all(): array
    {
        return [
            self::MINUTES,
            self::BANK_RECONCILIATION,
            self::APPROVED_CODE_LIST,
            self::SIGNED_APPROVED_CODE_LIST,
            self::MANAGEMENT_PROPOSAL_REVIEW_MINUTES,
            self::SUPPORTING_DOCS,
        ];
    }
}
