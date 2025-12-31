<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Temporary Users Table
        Schema::create('temp_users', function (Blueprint $table) {
            $table->id();
            $table->string('external_id')->unique()->comment('ID from web database');
            $table->enum('identifier_type', ['EMAIL', 'PHONE', 'NAME'])->default('EMAIL');
            $table->string('owner_identifier')->nullable()->comment('The actual identifier value');
            $table->string('email')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('name')->nullable();
            $table->string('drupal_user_id')->nullable();
            $table->timestamps();

            $table->index(['email', 'phone_number']);
            $table->index('external_id');
        });

        // Temporary Applications Table
        Schema::create('temp_applications', function (Blueprint $table) {
            $table->id();
            $table->string('application_id')->unique()->comment('APP-20251125-RQ7PIR');
            $table->string('external_id')->nullable()->comment('ID from web database');
            $table->bigInteger('temp_user_id')->unsigned();
            $table->enum('status', ['draft', 'submitted', 'under_review', 'approved', 'rejected', 'disbursed', 'closed'])->default('draft');
            $table->enum('identifier_type', ['EMAIL', 'PHONE', 'NAME']);
            $table->string('owner_identifier');
            $table->string('phone_number')->nullable();

            // Group Information
            $table->string('group_name');
            $table->string('group_registration_number');
            $table->date('date_registered');
            $table->json('group_registration_certificate')->nullable();
            $table->json('list_of_members_certificate')->nullable();
            $table->json('group_background')->nullable();
            $table->json('key_activities')->nullable();
            $table->string('other_activities')->nullable();
            $table->json('group_credit_history')->nullable();

            // Location - will be mapped to existing tables
            $table->string('county_name');
            $table->string('constituency_name');
            $table->string('ward_name');
            $table->string('meeting_point');
            $table->string('nearest_landmark');
            $table->string('nature_of_group');

            // Loan Information
            $table->string('loan_type');
            $table->decimal('loan_amount', 12, 2);
            $table->integer('proposed_repayment_period');
            $table->string('bank_name');
            $table->string('bank_branch');
            $table->string('bank_account_number');

            // Business Information
            $table->string('nature_of_business');
            $table->json('business_type');
            $table->integer('years_in_operation');
            $table->decimal('average_income', 12, 2);
            $table->json('expenses')->nullable();

            // Foreign keys to existing tables (after mapping)
            $table->string('county_code')->nullable();
            $table->string('constituency_code')->nullable();
            $table->bigInteger('ward_id')->unsigned()->nullable();

            // Sync information
            $table->enum('sync_status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->integer('sync_attempts')->default(0);
            $table->timestamp('last_sync_at')->nullable();
            $table->text('sync_error')->nullable();
            $table->string('external_application_id')->nullable()->comment('ID in the main applications table after sync');

            $table->json('raw_data')->nullable()->comment('Original payload for reference');
            $table->timestamps();

            // Indexes
            $table->foreign('temp_user_id')->references('id')->on('temp_users')->onDelete('cascade');
            $table->index(['application_id', 'status']);
            $table->index('sync_status');
            $table->index(['county_name', 'constituency_name']);
        });

        // Temporary Group Members Table
        Schema::create('temp_group_members', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('temp_application_id')->unsigned();
            $table->string('name');
            $table->string('id_number');
            $table->enum('gender', ['male', 'female', 'other']);
            $table->string('phone_number');
            $table->string('position')->nullable();
            $table->json('photo_member_id')->nullable();
            $table->boolean('person_with_disability')->default(false);
            $table->json('certificate_of_disability')->nullable();
            $table->string('pwd_number')->nullable();

            // Confirmation fields
            $table->boolean('requires_confirmation')->default(false);
            $table->string('confirmation_token')->nullable()->unique();
            $table->timestamp('confirmation_sent_at')->nullable();
            $table->timestamp('confirmation_confirmed_at')->nullable();
            $table->enum('confirmation_status', ['pending', 'sent', 'confirmed', 'declined', 'failed'])->default('pending');

            // Mapping to existing members table
            $table->string('member_id')->nullable()->comment('Mapped to members.member_Id');

            $table->timestamps();

            // Indexes
            $table->foreign('temp_application_id')->references('id')->on('temp_applications')->onDelete('cascade');
            $table->unique(['temp_application_id', 'id_number']);
            $table->unique(['temp_application_id', 'phone_number']);
            $table->index('confirmation_token');
            $table->index('confirmation_status');
        });

        // Temporary Expenses Table (for custom expenses)
        Schema::create('temp_expenses', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('temp_application_id')->unsigned();
            $table->string('name');
            $table->decimal('amount', 12, 2);
            $table->timestamps();

            $table->foreign('temp_application_id')->references('id')->on('temp_applications')->onDelete('cascade');
        });

        // Temporary Files Table
        Schema::create('temp_files', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->string('original_name');
            $table->string('mime_type');
            $table->integer('size');
            $table->string('path');
            $table->string('url')->nullable()->comment('Public URL for the file');
            $table->bigInteger('temp_user_id')->unsigned();
            $table->bigInteger('temp_application_id')->unsigned()->nullable();
            $table->string('field_name')->comment('Field where this file is used');
            $table->timestamps();

            $table->foreign('temp_user_id')->references('id')->on('temp_users');
            $table->foreign('temp_application_id')->references('id')->on('temp_applications')->onDelete('cascade');
            $table->index(['temp_application_id', 'field_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('temp_files');
        Schema::dropIfExists('temp_expenses');
        Schema::dropIfExists('temp_group_members');
        Schema::dropIfExists('temp_applications');
        Schema::dropIfExists('temp_users');
    }
};
