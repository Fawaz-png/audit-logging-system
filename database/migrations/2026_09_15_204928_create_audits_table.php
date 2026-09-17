<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('audits', function (Blueprint $table) {
             $table->id();
            $table->uuid('uuid')->unique();
            $table->string('reference')->unique();

            $table->string('title');
            $table->text('objective');
            $table->text('scope');

            $table->date('start_date');
            $table->date('end_date')->nullable();

            $table->string('status')->default('PLANNED');

            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audits');
    }
};
