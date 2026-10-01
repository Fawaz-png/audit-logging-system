<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finding_actions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('finding_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('responsible_department_id')
                ->constrained('departments')
                ->restrictOnDelete();

            $table->foreignId('responsible_user_id')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();

            $table->text('action');

            $table->date('due_date');

            $table->string('status')->default('OPEN');

            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finding_actions');
    }
};
