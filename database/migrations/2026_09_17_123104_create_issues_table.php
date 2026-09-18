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
        Schema::create('issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reporter_id')->constrained('users');
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();

            $table->unsignedBigInteger('number');

            $table->string('title');
            $table->text('description')->nullable();

            $table->string('status', 30)->default('todo');
            $table->string('priority', 30)->default('medium');

            $table->timestamps();

            $table->unique(['project_id', 'number']);

            $table->index(['project_id', 'status']);
            $table->index(['assignee_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('issues');
    }
};
