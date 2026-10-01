<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finding_actions', function (Blueprint $table) {
            $table->date('proposed_due_date')
                ->nullable()
                ->after('action');

            $table->date('agreed_due_date')
                ->nullable()
                ->after('proposed_due_date');

            $table->foreignId('approved_by')
                ->nullable()
                ->after('agreed_due_date')
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamp('approved_at')
                ->nullable()
                ->after('approved_by');

            $table->text('approval_comment')
                ->nullable()
                ->after('approved_at');

            $table->timestamp('completed_at')
                ->nullable()
                ->after('approval_comment');

            $table->text('rejection_reason')
                ->nullable()
                ->after('completed_at');
        });

        /*
         * Preserve existing due dates as proposed dates before
         * removing the old field.
         */
        DB::statement(
            'UPDATE finding_actions
             SET proposed_due_date = due_date
             WHERE due_date IS NOT NULL'
        );

        Schema::table('finding_actions', function (Blueprint $table) {
            $table->dropColumn('due_date');
        });
    }

    public function down(): void
    {
        Schema::table('finding_actions', function (Blueprint $table) {
            $table->date('due_date')
                ->nullable()
                ->after('action');
        });

        DB::statement(
            'UPDATE finding_actions
             SET due_date = COALESCE(agreed_due_date, proposed_due_date)'
        );

        Schema::table('finding_actions', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);

            $table->dropColumn([
                'proposed_due_date',
                'agreed_due_date',
                'approved_by',
                'approved_at',
                'approval_comment',
                'completed_at',
                'rejection_reason',
            ]);
        });
    }
};
