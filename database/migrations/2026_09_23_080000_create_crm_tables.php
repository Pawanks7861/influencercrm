<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('company_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('influencers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('instagram_url')->nullable();
            $table->string('instagram_username')->nullable();
            $table->string('mobile')->nullable();
            $table->string('email')->nullable();
            $table->string('location')->nullable();
            $table->string('influencer_type')->default('medium');
            $table->decimal('default_price', 15, 2)->default(0);
            $table->text('notes_summary')->nullable();
            $table->string('status')->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('instagram_username');
            $table->index('mobile');
            $table->index('email');
            $table->index('influencer_type');
            $table->index('location');
            $table->index('status');
        });

        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('campaign_name');
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->string('brand_name')->nullable();
            $table->decimal('campaign_budget', 15, 2)->nullable();
            $table->date('start_date')->nullable();
            $table->date('deadline')->nullable();
            $table->date('posting_date')->nullable();
            $table->string('status')->default('draft');
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('start_date');
        });

        Schema::create('campaign_influencers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('campaigns')->cascadeOnDelete();
            $table->foreignId('influencer_id')->constrained('influencers')->cascadeOnDelete();
            $table->decimal('influencer_cost', 15, 2)->default(0);
            $table->decimal('grovera_fee', 15, 2)->default(0);
            $table->decimal('final_amount', 15, 2)->default(0);
            $table->boolean('final_amount_overridden')->default(false);
            $table->string('status')->default('new_lead');
            $table->decimal('negotiated_price', 15, 2)->nullable();
            $table->date('content_deadline')->nullable();
            $table->date('posting_date')->nullable();
            $table->string('content_url')->nullable();
            $table->string('content_approval_status')->default('not_submitted');
            $table->text('content_approval_notes')->nullable();
            $table->timestamp('content_approved_at')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['campaign_id', 'influencer_id']);
            $table->index('status');
        });

        Schema::create('deliverables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_influencer_id')->constrained('campaign_influencers')->cascadeOnDelete();
            $table->string('type');
            $table->unsignedInteger('quantity')->default(1);
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->date('deadline')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->string('content_url')->nullable();
            $table->string('status')->default('pending');
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index('status');
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('campaigns')->cascadeOnDelete();
            $table->foreignId('campaign_influencer_id')->constrained('campaign_influencers')->cascadeOnDelete();
            $table->foreignId('influencer_id')->constrained('influencers')->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('payment_type')->nullable();
            $table->string('payment_status')->default('paid');
            $table->date('payment_date');
            $table->string('payment_method');
            $table->string('transaction_reference')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('payment_date');
        });

        Schema::create('influencer_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('influencer_id')->constrained('influencers')->cascadeOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained('campaigns')->nullOnDelete();
            $table->string('activity_type');
            $table->timestamp('activity_at');
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('activity_at');
        });

        Schema::create('follow_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('influencer_id')->constrained('influencers')->cascadeOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained('campaigns')->nullOnDelete();
            $table->foreignId('assigned_to')->constrained('users')->cascadeOnDelete();
            $table->date('follow_up_date');
            $table->time('follow_up_time')->nullable();
            $table->text('note')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('follow_up_date');
            $table->index('status');
        });

        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->morphs('notable');
            $table->text('note');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('group')->default('general');
            $table->timestamps();
        });

        Schema::create('user_table_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('table_key');
            $table->json('columns')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'table_key']);
        });

        Schema::create('import_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type');
            $table->string('filename');
            $table->string('status')->default('pending');
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('processed_rows')->default(0);
            $table->unsignedInteger('success_count')->default(0);
            $table->unsignedInteger('error_count')->default(0);
            $table->json('errors')->nullable();
            $table->json('mapping')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_jobs');
        Schema::dropIfExists('user_table_preferences');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('notes');
        Schema::dropIfExists('follow_ups');
        Schema::dropIfExists('influencer_activities');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('deliverables');
        Schema::dropIfExists('campaign_influencers');
        Schema::dropIfExists('campaigns');
        Schema::dropIfExists('influencers');
        Schema::dropIfExists('clients');
    }
};
