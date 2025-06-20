<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // ✅ Assurer que la table notifications a la bonne structure
        if (Schema::hasTable('notifications')) {
            Schema::table('notifications', function (Blueprint $table) {
                // Ajouter l'index pour les requêtes fréquentes
                if (!Schema::hasIndex('notifications', ['user_id', 'lu'])) {
                    $table->index(['user_id', 'lu']);
                }
                
                // Ajouter l'index pour les requêtes par type
                if (!Schema::hasIndex('notifications', 'type')) {
                    $table->index('type');
                }
                
                // Ajouter l'index pour les requêtes par date
                if (!Schema::hasIndex('notifications', 'created_at')) {
                    $table->index('created_at');
                }
            });
        }
    }

    public function down()
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'lu']);
            $table->dropIndex(['type']);
            $table->dropIndex(['created_at']);
        });
    }
};