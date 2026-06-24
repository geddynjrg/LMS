<?php

namespace App\Support;

final class PhaseDocumentType
{
    public const MINUTES = 'minutes';
    public const BANK_RECONCILIATION = 'bank_reconciliation';
    public const BANK_RECONCILIATION_MAIN_ACCOUNT = 'bank_reconciliation_main_account';
    public const BANK_RECONCILIATION_LOAN_REPAYMENT = 'bank_reconciliation_loan_repayment';
    public const REQUEST_FOR_TRANSFER_LETTER = 'request_for_transfer_letter';
    public const PROCESSED_CODE_LIST = 'processed_code_list';
    /** @deprecated Use PROCESSED_CODE_LIST */
    public const APPROVED_CODE_LIST = self::PROCESSED_CODE_LIST;
    public const SIGNED_APPROVED_CODE_LIST = 'signed_approved_code_list';
    public const MANAGEMENT_PROPOSAL_REVIEW_MINUTES = 'management_proposal_review_minutes';
    public const SUPPORTING_DOCS = 'supporting_docs';

    /** Legacy DB value before rename to processed_code_list. */
    public const LEGACY_APPROVED_CODE_LIST = 'approved_code_list';

    public static function all(): array
    {
        return [
            self::MINUTES,
            self::BANK_RECONCILIATION,
            self::BANK_RECONCILIATION_MAIN_ACCOUNT,
            self::BANK_RECONCILIATION_LOAN_REPAYMENT,
            self::REQUEST_FOR_TRANSFER_LETTER,
            self::PROCESSED_CODE_LIST,
            self::SIGNED_APPROVED_CODE_LIST,
            self::MANAGEMENT_PROPOSAL_REVIEW_MINUTES,
            self::SUPPORTING_DOCS,
        ];
    }

    /** Document types that represent the HQ-processed Excel code list. */
    public static function processedCodeListTypes(): array
    {
        return [
            self::PROCESSED_CODE_LIST,
            self::LEGACY_APPROVED_CODE_LIST,
        ];
    }

    public static function labels(): array
    {
        return [
            self::MINUTES => 'Constituency management committee minutes',
            self::BANK_RECONCILIATION => 'Bank reconciliation (legacy)',
            self::BANK_RECONCILIATION_MAIN_ACCOUNT => 'Main account bank reconciliation',
            self::BANK_RECONCILIATION_LOAN_REPAYMENT => 'Loan repayment bank reconciliation',
            self::REQUEST_FOR_TRANSFER_LETTER => 'Request for transfer letter',
            self::PROCESSED_CODE_LIST => 'Processed code list',
            self::LEGACY_APPROVED_CODE_LIST => 'Processed code list',
            self::SIGNED_APPROVED_CODE_LIST => 'Signed processed code list',
            self::MANAGEMENT_PROPOSAL_REVIEW_MINUTES => 'Management proposal review committee minutes',
            self::SUPPORTING_DOCS => 'Supporting documents',
        ];
    }

    public static function label(string $type): string
    {
        return self::labels()[$type] ?? ucwords(str_replace('_', ' ', $type));
    }

    public static function bankReconciliationTypes(): array
    {
        return [
            self::BANK_RECONCILIATION,
            self::BANK_RECONCILIATION_MAIN_ACCOUNT,
            self::BANK_RECONCILIATION_LOAN_REPAYMENT,
        ];
    }

    /**
     * @return list<string>
     */
    public static function requiredBankReconciliationTypes(): array
    {
        return [
            self::BANK_RECONCILIATION_MAIN_ACCOUNT,
            self::BANK_RECONCILIATION_LOAN_REPAYMENT,
        ];
    }

    /**
     * Documents a constituency must upload before submitting a phase to HQ.
     *
     * @return list<string>
     */
    public static function requiredForConstituencyHqSubmission(): array
    {
        return array_merge(
            [self::MINUTES],
            self::requiredBankReconciliationTypes(),
            [self::REQUEST_FOR_TRANSFER_LETTER],
        );
    }
}
