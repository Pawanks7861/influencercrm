<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (! Schema::hasColumn('clients', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
                $table->boolean('login_enabled')->default(false)->after('status');
                $table->index('user_id');
            }
        });

        Schema::create('client_requirements', function (Blueprint $table) {
            $table->id();
            $table->string('requirement_number', 40)->unique();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('requirement_type', 50);
            $table->string('title');
            $table->string('brand_name')->nullable();
            $table->text('description')->nullable();
            $table->decimal('budget_min', 15, 2)->nullable();
            $table->decimal('budget_max', 15, 2)->nullable();
            $table->date('preferred_start_date')->nullable();
            $table->date('preferred_end_date')->nullable();
            $table->date('expected_posting_date')->nullable();
            $table->string('preferred_location')->nullable();
            $table->string('preferred_category', 50)->nullable();
            $table->json('preferred_platforms')->nullable();
            $table->unsignedInteger('influencers_required')->nullable();
            $table->string('target_audience')->nullable();
            $table->json('services_required')->nullable();
            $table->string('duration')->nullable();
            $table->string('posting_frequency')->nullable();
            $table->text('goals')->nullable();
            $table->text('additional_instructions')->nullable();
            $table->text('client_notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->string('status', 50)->default('submitted');
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('converted_campaign_id')->nullable()->constrained('campaigns')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['client_id', 'status']);
            $table->index('requirement_type');
            $table->index('assigned_to');
            $table->index('submitted_at');
        });

        Schema::create('requirement_deliverables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_requirement_id')->constrained()->cascadeOnDelete();
            $table->string('platform', 50)->nullable();
            $table->string('deliverable_type', 50);
            $table->unsignedInteger('quantity')->default(1);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('requirement_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_requirement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('sender_type', 20); // staff|client
            $table->text('message');
            $table->timestamps();
            $table->index(['client_requirement_id', 'created_at']);
        });

        Schema::create('requirement_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_requirement_id')->constrained()->cascadeOnDelete();
            $table->string('original_filename');
            $table->string('stored_path');
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('influencer_shortlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_requirement_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('status', 40)->default('draft');
            $table->timestamp('shared_at')->nullable();
            $table->foreignId('shared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('withdrawn_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->text('message')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['client_requirement_id', 'status']);
        });

        Schema::create('influencer_shortlist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('influencer_shortlist_id')->constrained()->cascadeOnDelete();
            $table->foreignId('influencer_id')->constrained()->cascadeOnDelete();
            $table->string('display_name');
            $table->string('instagram_url', 500)->nullable();
            $table->string('instagram_username')->nullable();
            $table->string('location')->nullable();
            $table->string('influencer_type', 30)->nullable();
            $table->decimal('client_price', 15, 2);
            $table->decimal('previous_client_price', 15, 2)->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->string('status', 40)->default('pending');
            $table->text('client_remark')->nullable();
            $table->boolean('show_name')->default(true);
            $table->boolean('show_instagram')->default(true);
            $table->boolean('show_price')->default(true);
            $table->boolean('show_location')->default(false);
            $table->boolean('show_type')->default(false);
            $table->boolean('show_note')->default(true);
            $table->foreignId('price_updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('price_updated_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->unique(['influencer_shortlist_id', 'influencer_id'], 'shortlist_influencer_unique');
            $table->index('status');
        });

        Schema::create('client_campaign_visibility', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('visible_to_client')->default(false);
            $table->boolean('show_budget')->default(false);
            $table->boolean('show_deliverables')->default(true);
            $table->boolean('show_influencers')->default(true);
            $table->boolean('show_posting_dates')->default(true);
            $table->boolean('show_content_links')->default(false);
            $table->boolean('show_payment_summary')->default(false);
            $table->boolean('show_notes')->default(false);
            $table->timestamp('shared_at')->nullable();
            $table->foreignId('shared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('portal_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 80);
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('link')->nullable();
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portal_notifications');
        Schema::dropIfExists('client_campaign_visibility');
        Schema::dropIfExists('influencer_shortlist_items');
        Schema::dropIfExists('influencer_shortlists');
        Schema::dropIfExists('requirement_attachments');
        Schema::dropIfExists('requirement_messages');
        Schema::dropIfExists('requirement_deliverables');
        Schema::dropIfExists('client_requirements');

        Schema::table('clients', function (Blueprint $table) {
            if (Schema::hasColumn('clients', 'user_id')) {
                $table->dropConstrainedForeignId('user_id');
                $table->dropColumn('login_enabled');
            }
        });
    }
};
