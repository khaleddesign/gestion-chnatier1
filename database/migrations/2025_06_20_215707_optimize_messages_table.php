<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('messages')) {
            Schema::table('messages', function (Blueprint $table) {
                // ✅ Ajouter des contraintes et index manquants
                
                // Index composé pour les requêtes fréquentes
                if (!Schema::hasIndex('messages', ['recipient_id', 'is_read', 'created_at'])) {
                    $table->index(['recipient_id', 'is_read', 'created_at'], 'messages_recipient_read_date');
                }
                
                // Index pour les messages envoyés
                if (!Schema::hasIndex('messages', ['sender_id', 'created_at'])) {
                    $table->index(['sender_id', 'created_at'], 'messages_sender_date');
                }
                
                // ✅ Ajouter validation de longueur si pas déjà fait
                if (!Schema::hasColumn('messages', 'subject_length_check')) {
                    // Note: La validation sera dans le modèle/contrôleur
                }
            });
        }
    }

    public function down()
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex('messages_recipient_read_date');
            $table->dropIndex('messages_sender_date');
        });
    }
};
