<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('influencers', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('youtube_url', 500)->nullable()->after('instagram_username');
            $table->string('facebook_url', 500)->nullable()->after('youtube_url');
            $table->string('linkedin_url', 500)->nullable()->after('facebook_url');
            $table->string('twitter_url', 500)->nullable()->after('linkedin_url');
            $table->string('other_social_url', 500)->nullable()->after('twitter_url');
            $table->boolean('login_enabled')->default(false)->after('status');
            $table->index('user_id');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->string('mobile', 30)->nullable()->after('phone');
            $table->string('alternate_mobile', 30)->nullable()->after('mobile');
            $table->string('website', 500)->nullable()->after('email');
            $table->string('instagram_url', 500)->nullable()->after('website');
            $table->string('facebook_url', 500)->nullable()->after('instagram_url');
            $table->string('linkedin_url', 500)->nullable()->after('facebook_url');
            $table->string('youtube_url', 500)->nullable()->after('linkedin_url');
            $table->string('twitter_url', 500)->nullable()->after('youtube_url');
            $table->text('address')->nullable()->after('twitter_url');
            $table->text('notes')->nullable()->after('address');
            $table->foreignId('created_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->index('status');
            $table->index('company_name');
            $table->index('mobile');
        });

        // Align legacy phone into mobile where mobile empty
        if (Schema::hasColumn('clients', 'phone')) {
            \Illuminate\Support\Facades\DB::statement('UPDATE clients SET mobile = phone WHERE (mobile IS NULL OR mobile = "") AND phone IS NOT NULL');
        }

        Schema::table('campaigns', function (Blueprint $table) {
            $table->string('campaign_type', 50)->default('influencer_marketing')->after('brand_name');
            $table->json('platforms')->nullable()->after('campaign_type');
            $table->decimal('service_fee', 15, 2)->nullable()->after('campaign_budget');
            $table->index('campaign_type');
        });

        Schema::table('campaign_influencers', function (Blueprint $table) {
            $table->decimal('additional_cost', 15, 2)->default(0)->after('influencer_cost');
        });

        Schema::table('follow_ups', function (Blueprint $table) {
            $table->foreignId('client_id')->nullable()->after('influencer_id')->constrained()->nullOnDelete();
            $table->foreignId('influencer_id')->nullable()->change();
            $table->index('client_id');
        });

        Schema::table('deliverables', function (Blueprint $table) {
            $table->foreignId('campaign_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->foreignId('campaign_influencer_id')->nullable()->change();
            $table->string('platform', 50)->nullable()->after('type');
            $table->index('campaign_id');
        });

        Schema::create('client_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->string('activity_type', 50);
            $table->timestamp('activity_at');
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['client_id', 'activity_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_activities');

        Schema::table('deliverables', function (Blueprint $table) {
            $table->dropConstrainedForeignId('campaign_id');
            $table->dropColumn('platform');
        });

        Schema::table('follow_ups', function (Blueprint $table) {
            $table->dropConstrainedForeignId('client_id');
        });

        Schema::table('campaign_influencers', function (Blueprint $table) {
            $table->dropColumn('additional_cost');
        });

        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn(['campaign_type', 'platforms', 'service_fee']);
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropConstrainedForeignId('created_by');
            $table->dropConstrainedForeignId('updated_by');
            $table->dropColumn([
                'mobile', 'alternate_mobile', 'website', 'instagram_url', 'facebook_url',
                'linkedin_url', 'youtube_url', 'twitter_url', 'address', 'notes',
            ]);
        });

        Schema::table('influencers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn([
                'youtube_url', 'facebook_url', 'linkedin_url', 'twitter_url',
                'other_social_url', 'login_enabled',
            ]);
        });
    }
};
