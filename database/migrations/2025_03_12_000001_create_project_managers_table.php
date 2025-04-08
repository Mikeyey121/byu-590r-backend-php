<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('project_managers', function (Blueprint $table) {
            $table->id('managerId');
            $table->string('managerName');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('project_managers');
    }
}; 