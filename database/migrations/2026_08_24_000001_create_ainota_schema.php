<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_platform_admin')->default(false)->after('email');
        });

        Schema::create('plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('max_workspaces')->default(1);
            $table->unsignedInteger('max_members')->default(3);
            $table->unsignedInteger('pages_per_month')->default(50);
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('workspaces', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('owner_user_id')->constrained('users')->restrictOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('legal_name')->nullable();
            $table->char('currency', 3)->default('IDR');
            $table->string('timezone')->default('Asia/Jakarta');
            $table->string('locale')->default('id');
            $table->string('status')->default('active');
            $table->foreignUuid('plan_id')->nullable()->constrained('plans')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('workspace_members', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role');
            $table->json('permissions')->nullable();
            $table->string('status')->default('active');
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();
            $table->unique(['workspace_id', 'user_id']);
        });

        Schema::create('workspace_invitations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->string('role');
            $table->string('token_hash');
            $table->timestamp('expires_at');
            $table->foreignId('invited_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('accepted_at')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
            $table->index(['workspace_id', 'email']);
        });

        Schema::create('workspace_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete()->unique();
            $table->boolean('require_approval')->default(true);
            $table->boolean('require_separate_approver')->default(false);
            $table->boolean('allow_approver_edit')->default(true);
            $table->boolean('allow_export_unapproved')->default(false);
            $table->boolean('uploader_can_view_amounts')->default(true);
            $table->boolean('ai_processing_enabled')->default(true);
            $table->decimal('duplicate_threshold', 5, 2)->default(75);
            $table->uuid('default_cash_account_id')->nullable();
            $table->string('coa_template_slug')->nullable();
            $table->json('extra')->nullable();
            $table->timestamps();
        });

        Schema::create('upload_links', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('token_hash')->unique();
            $table->string('public_token', 64)->unique();
            $table->timestamp('expires_at')->nullable();
            $table->unsignedInteger('max_uploads')->nullable();
            $table->unsignedInteger('upload_count')->default(0);
            $table->boolean('require_uploader_name')->default(true);
            $table->string('pin_hash')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('account_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('account_template_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('account_template_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('type');
            $table->string('normal_balance');
            $table->string('parent_code')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['account_template_id', 'code']);
        });

        Schema::create('accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->uuid('parent_id')->nullable();
            $table->string('code');
            $table->string('name');
            $table->string('type');
            $table->string('normal_balance');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['workspace_id', 'code']);
        });

        Schema::create('payment_account_mappings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('keyword');
            $table->string('label');
            $table->foreignUuid('account_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->unique(['workspace_id', 'keyword']);
        });

        Schema::create('vendors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('normalized_name');
            $table->string('tax_id')->nullable();
            $table->json('aliases')->nullable();
            $table->foreignUuid('default_expense_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignUuid('default_payable_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['workspace_id', 'normalized_name']);
        });

        Schema::create('vendor_aliases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('vendor_id')->constrained()->cascadeOnDelete();
            $table->string('alias');
            $table->string('normalized_alias');
            $table->timestamps();
            $table->unique(['workspace_id', 'normalized_alias']);
        });

        Schema::create('vendor_account_mappings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('vendor_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('debit_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignUuid('credit_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->string('source')->nullable();
            $table->timestamps();
            $table->unique(['workspace_id', 'vendor_id']);
        });

        Schema::create('upload_batches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name')->nullable();
            $table->unsignedInteger('total_files')->default(0);
            $table->unsignedInteger('processed_files')->default(0);
            $table->unsignedInteger('failed_files')->default(0);
            $table->string('status')->default('processing');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('batch_id')->nullable()->constrained('upload_batches')->nullOnDelete();
            $table->foreignUuid('upload_link_id')->nullable()->constrained('upload_links')->nullOnDelete();
            $table->string('source')->default('app');
            $table->string('status');
            $table->string('original_filename');
            $table->string('mime_type');
            $table->unsignedBigInteger('file_size');
            $table->string('storage_disk');
            $table->string('storage_path');
            $table->char('sha256', 64);
            $table->string('perceptual_hash')->nullable();
            $table->unsignedInteger('current_version')->default(1);
            $table->unsignedInteger('page_count')->default(1);
            $table->string('document_type')->nullable();
            $table->date('transaction_date')->nullable();
            $table->foreignUuid('vendor_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('grand_total', 20, 2)->nullable();
            $table->char('currency', 3)->nullable();
            $table->decimal('duplicate_score', 5, 2)->nullable();
            $table->uuid('possible_duplicate_of')->nullable();
            $table->string('content_fingerprint')->nullable();
            $table->json('flags')->nullable();
            $table->string('failure_code')->nullable();
            $table->text('failure_message')->nullable();
            $table->unsignedTinyInteger('progress')->default(0);
            $table->string('guest_uploader_name')->nullable();
            $table->timestamp('processing_started_at')->nullable();
            $table->timestamp('processing_completed_at')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'created_at']);
            $table->index(['workspace_id', 'status']);
            $table->index(['workspace_id', 'sha256']);
            $table->index(['workspace_id', 'transaction_date']);
            $table->index(['workspace_id', 'grand_total']);
        });

        Schema::table('workspace_settings', function (Blueprint $table) {
            $table->foreign('default_cash_account_id')->references('id')->on('accounts')->nullOnDelete();
        });

        Schema::create('document_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('document_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('storage_path');
            $table->string('preview_path')->nullable();
            $table->string('ai_optimized_path')->nullable();
            $table->char('sha256', 64);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['document_id', 'version']);
        });

        Schema::create('document_pages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('document_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('document_version_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('page_number');
            $table->string('preview_path')->nullable();
            $table->string('ai_optimized_path')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->timestamps();
            $table->unique(['document_id', 'page_number']);
        });

        Schema::create('document_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('document_id')->constrained()->cascadeOnDelete();
            $table->string('description')->nullable();
            $table->decimal('quantity', 20, 4)->nullable();
            $table->string('unit')->nullable();
            $table->decimal('unit_price', 20, 2)->nullable();
            $table->decimal('amount', 20, 2)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('document_hashes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('document_id')->constrained()->cascadeOnDelete();
            $table->string('hash_type');
            $table->string('hash_value');
            $table->timestamps();
            $table->index(['workspace_id', 'hash_type', 'hash_value']);
        });

        Schema::create('duplicate_candidates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('document_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('matched_document_id')->constrained('documents')->cascadeOnDelete();
            $table->decimal('score', 5, 2);
            $table->json('signals')->nullable();
            $table->string('resolution')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_extractions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('document_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('document_version')->default(1);
            $table->string('provider');
            $table->string('model');
            $table->string('schema_version');
            $table->string('prompt_version');
            $table->json('raw_response')->nullable();
            $table->json('normalized_data')->nullable();
            $table->decimal('confidence', 5, 4)->nullable();
            $table->unsignedBigInteger('input_tokens')->nullable();
            $table->unsignedBigInteger('output_tokens')->nullable();
            $table->decimal('estimated_cost', 18, 8)->nullable();
            $table->unsignedBigInteger('latency_ms')->nullable();
            $table->string('status')->default('pending');
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->string('idempotency_key')->unique();
            $table->timestamps();
            $table->index(['document_id', 'document_version']);
        });

        Schema::create('ai_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('document_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('ai_extraction_id')->nullable()->constrained()->nullOnDelete();
            $table->string('purpose');
            $table->string('model');
            $table->unsignedBigInteger('input_tokens')->nullable();
            $table->unsignedBigInteger('output_tokens')->nullable();
            $table->decimal('estimated_cost', 18, 8)->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->string('status');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_account_suggestions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('document_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('entry_type');
            $table->string('source');
            $table->decimal('confidence', 5, 4)->nullable();
            $table->text('reason')->nullable();
            $table->boolean('accepted')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_feedback', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('document_id')->nullable()->constrained()->nullOnDelete();
            $table->string('field');
            $table->text('predicted_value')->nullable();
            $table->text('final_value')->nullable();
            $table->foreignUuid('vendor_id')->nullable()->constrained()->nullOnDelete();
            $table->json('context')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('document_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('vendor_id')->nullable()->constrained()->nullOnDelete();
            $table->date('transaction_date');
            $table->string('reference_number')->nullable();
            $table->text('description')->nullable();
            $table->char('currency', 3)->default('IDR');
            $table->decimal('subtotal', 20, 2)->default(0);
            $table->decimal('discount_amount', 20, 2)->default(0);
            $table->decimal('tax_amount', 20, 2)->default(0);
            $table->decimal('service_charge', 20, 2)->default(0);
            $table->decimal('total_amount', 20, 2)->default(0);
            $table->string('payment_method')->nullable();
            $table->string('status')->default('DRAFT');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('review_note')->nullable();
            $table->text('approval_note')->nullable();
            $table->timestamps();
            $table->index(['workspace_id', 'transaction_date']);
            $table->index(['workspace_id', 'status']);
            $table->index(['workspace_id', 'vendor_id']);
        });

        Schema::create('transaction_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('transaction_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('account_id')->constrained()->restrictOnDelete();
            $table->string('entry_type');
            $table->decimal('amount', 20, 2);
            $table->text('description')->nullable();
            $table->string('recommendation_source')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('document_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->string('status');
            $table->text('note')->nullable();
            $table->boolean('remember_vendor_mapping')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('approvals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('transaction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('approver_id')->constrained('users')->cascadeOnDelete();
            $table->string('decision');
            $table->text('note')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('subject_type')->nullable();
            $table->string('subject_id')->nullable();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->json('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['workspace_id', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
        });

        Schema::create('export_presets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('slug');
            $table->string('name');
            $table->string('type');
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        Schema::create('export_preset_columns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('export_preset_id')->constrained()->cascadeOnDelete();
            $table->string('header');
            $table->string('source_key');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('exports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->string('type');
            $table->string('format');
            $table->string('status')->default('QUEUED');
            $table->foreignUuid('preset_id')->nullable()->constrained('export_presets')->nullOnDelete();
            $table->json('filters')->nullable();
            $table->unsignedInteger('row_count')->nullable();
            $table->string('storage_disk')->nullable();
            $table->string('storage_path')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('downloaded_at')->nullable();
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('plan_id')->constrained()->restrictOnDelete();
            $table->string('status')->default('active');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('renews_at')->nullable();
            $table->timestamps();
        });

        Schema::create('usage_ledgers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->string('usage_type');
            $table->integer('quantity');
            $table->string('reference_type')->nullable();
            $table->string('reference_id')->nullable();
            $table->decimal('provider_cost', 18, 8)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['workspace_id', 'usage_type', 'created_at']);
        });

        Schema::create('workspace_usages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->char('period', 7);
            $table->unsignedInteger('ai_pages')->default(0);
            $table->decimal('provider_cost', 18, 8)->default(0);
            $table->timestamps();
            $table->unique(['workspace_id', 'period']);
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('workspace_usages');
        Schema::dropIfExists('usage_ledgers');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('exports');
        Schema::dropIfExists('export_preset_columns');
        Schema::dropIfExists('export_presets');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('approvals');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('transaction_entries');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('ai_feedback');
        Schema::dropIfExists('ai_account_suggestions');
        Schema::dropIfExists('ai_requests');
        Schema::dropIfExists('ai_extractions');
        Schema::dropIfExists('duplicate_candidates');
        Schema::dropIfExists('document_hashes');
        Schema::dropIfExists('document_items');
        Schema::dropIfExists('document_pages');
        Schema::dropIfExists('document_versions');
        Schema::table('workspace_settings', function (Blueprint $table) {
            $table->dropForeign(['default_cash_account_id']);
        });
        Schema::dropIfExists('documents');
        Schema::dropIfExists('upload_batches');
        Schema::dropIfExists('vendor_account_mappings');
        Schema::dropIfExists('vendor_aliases');
        Schema::dropIfExists('vendors');
        Schema::dropIfExists('payment_account_mappings');
        Schema::dropIfExists('accounts');
        Schema::dropIfExists('account_template_items');
        Schema::dropIfExists('account_templates');
        Schema::dropIfExists('upload_links');
        Schema::dropIfExists('workspace_settings');
        Schema::dropIfExists('workspace_invitations');
        Schema::dropIfExists('workspace_members');
        Schema::dropIfExists('workspaces');
        Schema::dropIfExists('plans');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_platform_admin');
        });
    }
};
