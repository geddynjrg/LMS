<?php

namespace App\Console\Commands;

use App\Http\Helpers\StoragePathHelper;
use App\Models\TempApplication;
use App\Models\TempGroupMember;
use Illuminate\Console\Command;

class AuditApplicationStorageFiles extends Command
{
    protected $signature = 'storage:audit-application-files {--limit=50 : Max missing records to list}';

    protected $description = 'List application/member documents referenced in DB but missing from storage';

    public function handle(): int
    {
        $missing = [];
        $checked = 0;

        $applicationDocumentFields = [
            'group_registration_certificate',
            'list_of_members_certificate',
            'group_meeting_minutes',
        ];

        TempApplication::query()
            ->select(array_merge(['id', 'application_id'], $applicationDocumentFields))
            ->orderByDesc('id')
            ->chunk(100, function ($applications) use (&$missing, &$checked, $applicationDocumentFields) {
                foreach ($applications as $application) {
                    foreach ($applicationDocumentFields as $field) {
                        $meta = $application->{$field};
                        if (! is_array($meta) || empty($meta['path'])) {
                            continue;
                        }

                        $checked++;
                        if (! StoragePathHelper::resolveAbsolutePath($meta['path'])) {
                            $missing[] = [
                                'type' => 'application',
                                'application_id' => $application->application_id,
                                'field' => $field,
                                'path' => $meta['path'],
                            ];
                        }
                    }
                }
            });

        TempGroupMember::query()
            ->select(['id', 'temp_application_id', 'photo_member_id', 'certificate_of_disability'])
            ->orderByDesc('id')
            ->chunk(200, function ($members) use (&$missing, &$checked) {
                foreach ($members as $member) {
                    foreach (['photo_member_id', 'certificate_of_disability'] as $field) {
                        $meta = $member->{$field};
                        if (! is_array($meta) || empty($meta['path'])) {
                            continue;
                        }

                        $checked++;
                        if (! StoragePathHelper::resolveAbsolutePath($meta['path'])) {
                            $missing[] = [
                                'type' => 'member',
                                'application_id' => $member->temp_application_id,
                                'field' => $field,
                                'path' => $meta['path'],
                            ];
                        }
                    }
                }
            });

        $limit = (int) $this->option('limit');
        $this->info("Checked {$checked} stored file references.");
        $this->warn('Missing on disk: ' . count($missing));

        foreach (array_slice($missing, 0, $limit) as $row) {
            $this->line(sprintf(
                '- [%s] %s %s → %s',
                $row['type'],
                $row['application_id'],
                $row['field'],
                $row['path']
            ));
        }

        if (count($missing) > $limit) {
            $this->line('... truncated. Re-run with --limit=' . count($missing) . ' to see all.');
        }

        return self::SUCCESS;
    }
}
