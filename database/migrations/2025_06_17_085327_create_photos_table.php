<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chantier_id')->constrained()->onDelete('cascade');
            $table->string('nom');
            $table->string('chemin');
            $table->string('thumbnail')->nullable();
            $table->text('description')->nullable();
            $table->bigInteger('taille')->nullable();
            $table->string('type_mime')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['chantier_id', 'created_at']);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('photos');
    }
};