<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id('projectId');
            $table->string('projectName');
            $table->date('projectStartDate');
            $table->decimal('projectBudget', 10, 2); // Allows for large numbers with 2 decimal places
            $table->string('projectFile')->nullable(); // Making it nullable in case file isn't uploaded immediately
            $table->unsignedBigInteger('managerId');
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('managerId')
                  ->references('managerId')
                  ->on('project_managers')
                  ->onDelete('cascade'); // If manager is deleted, delete their projects

            $table->foreign('genreId')
                  ->references('genreId')
                  ->on('genres')
                  ->onDelete('cascade'); // If genre is deleted, delete their projects
        });
    }

    public function down()
    {
        Schema::dropIfExists('projects');
    }
}; 