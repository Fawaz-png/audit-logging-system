<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('findings', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('audit_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('reference')->unique();

            $table->string('title');

            $table->text('description');

            $table->text('criteria');

            $table->text('effect');

            $table->text('recommendation');

            $table->string('process');

            $table->string('risk_rating');

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
        Schema::dropIfExists('findings');
    }
};
