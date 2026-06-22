<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('phase_application_documents')
            ->where('document_type', 'approved_code_list')
            ->update(['document_type' => 'processed_code_list']);
    }

    public function down(): void
    {
        DB::table('phase_application_documents')
            ->where('document_type', 'processed_code_list')
            ->update(['document_type' => 'approved_code_list']);
    }
};
