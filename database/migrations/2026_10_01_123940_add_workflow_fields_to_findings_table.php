<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('findings', function (Blueprint $table) {
            $table->text('auditee_response')
                ->nullable()
                ->after('status');

            $table->foreignId('response_by')
                ->nullable()
                ->after('auditee_response')
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamp('response_at')
                ->nullable()
                ->after('response_by');

            $table->foreignId('verified_by')
                ->nullable()
                ->after('response_at')
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamp('verified_at')
                ->nullable()
                ->after('verified_by');

            $table->text('verification_notes')
                ->nullable()
                ->after('verified_at');

            $table->foreignId('closed_by')
                ->nullable()
                ->after('verification_notes')
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamp('closed_at')
                ->nullable()
                ->after('closed_by');
        });
    }

    public function down(): void
    {
        Schema::table('findings', function (Blueprint $table) {
            $table->dropForeign(['response_by']);
            $table->dropForeign(['verified_by']);
            $table->dropForeign(['closed_by']);

            $table->dropColumn([
                'auditee_response',
                'response_by',
                'response_at',
                'verified_by',
                'verified_at',
                'verification_notes',
                'closed_by',
                'closed_at',
            ]);
        });
    }
};
