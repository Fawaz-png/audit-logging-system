<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('audits', function (Blueprint $table) {
            $table->foreignId('department_id')
                ->after('scope')
                ->constrained()
                ->restrictOnDelete();

            $table->string('audit_mode')
                ->after('department_id');

            $table->string('methodology')
                ->after('audit_mode');

            $table->string('location')
                ->nullable()
                ->after('methodology');

            $table->string('previous_audit_reference')
                ->nullable()
                ->after('location');
        });
    }

    public function down(): void
    {
        Schema::table('audits', function (Blueprint $table) {
            $table->dropForeign(['department_id']);

            $table->dropColumn([
                'department_id',
                'audit_mode',
                'methodology',
                'location',
                'previous_audit_reference',
            ]);
        });
    }
};