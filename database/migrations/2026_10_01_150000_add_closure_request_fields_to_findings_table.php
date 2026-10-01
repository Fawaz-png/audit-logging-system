<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('findings', function (Blueprint $table): void {
            $table->foreignId('closure_requested_by')
                ->nullable()
                ->after('closed_at')
                ->constrained('users')
                ->restrictOnDelete();
            $table->timestamp('closure_requested_at')->nullable()->after('closure_requested_by');
            $table->foreignId('closure_reviewed_by')
                ->nullable()
                ->after('closure_requested_at')
                ->constrained('users')
                ->restrictOnDelete();
            $table->text('closure_request_notes')->nullable()->after('closure_reviewed_by');
            $table->timestamp('closure_reviewed_at')->nullable()->after('closure_request_notes');
            $table->text('closure_review_notes')->nullable()->after('closure_reviewed_at');
            $table->text('closure_rejection_reason')->nullable()->after('closure_review_notes');
        });
    }

    public function down(): void
    {
        Schema::table('findings', function (Blueprint $table): void {
            $table->dropForeign(['closure_requested_by']);
            $table->dropForeign(['closure_reviewed_by']);
            $table->dropColumn([
                'closure_requested_by',
                'closure_requested_at',
                'closure_reviewed_by',
                'closure_request_notes',
                'closure_reviewed_at',
                'closure_review_notes',
                'closure_rejection_reason',
            ]);
        });
    }
};
