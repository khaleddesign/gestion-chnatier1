<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Pour SQLite, nous devons recréer la table car ALTER COLUMN n'est pas supporté
        if (DB::connection()->getDriverName() === 'sqlite') {
            // Créer une table temporaire avec la nouvelle structure
            Schema::create('notifications_temp', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('chantier_id')->nullable()->constrained()->onDelete('set null');
                $table->string('type');
                $table->string('titre');
                $table->text('message');
                $table->boolean('lu')->default(false);
                $table->timestamp('lu_at')->nullable();
                $table->timestamps();
            });

            // Copier les données existantes (en excluant celles avec chantier_id NULL)
            DB::statement('
                INSERT INTO notifications_temp (id, user_id, chantier_id, type, titre, message, lu, lu_at, created_at, updated_at)
                SELECT id, user_id, chantier_id, type, titre, message, lu, lu_at, created_at, updated_at
                FROM notifications
                WHERE chantier_id IS NOT NULL
            ');

            // Supprimer l'ancienne table
            Schema::dropIfExists('notifications');

            // Renommer la table temporaire
            Schema::rename('notifications_temp', 'notifications');
        } else {
            // Pour MySQL/PostgreSQL
            Schema::table('notifications', function (Blueprint $table) {
                $table->dropForeign(['chantier_id']);
                $table->unsignedBigInteger('chantier_id')->nullable()->change();
                $table->foreign('chantier_id')->references('id')->on('chantiers')->onDelete('set null');
            });
        }
    }

    public function down()
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            // Recréer avec l'ancienne structure
            Schema::create('notifications_temp', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('chantier_id')->constrained()->onDelete('cascade');
                $table->string('type');
                $table->string('titre');
                $table->text('message');
                $table->boolean('lu')->default(false);
                $table->timestamp('lu_at')->nullable();
                $table->timestamps();
            });

            DB::statement('
                INSERT INTO notifications_temp (id, user_id, chantier_id, type, titre, message, lu, lu_at, created_at, updated_at)
                SELECT id, user_id, chantier_id, type, titre, message, lu, lu_at, created_at, updated_at
                FROM notifications
                WHERE chantier_id IS NOT NULL
            ');

            Schema::dropIfExists('notifications');
            Schema::rename('notifications_temp', 'notifications');
        } else {
            Schema::table('notifications', function (Blueprint $table) {
                $table->dropForeign(['chantier_id']);
                $table->unsignedBigInteger('chantier_id')->nullable(false)->change();
                $table->foreign('chantier_id')->references('id')->on('chantiers')->onDelete('cascade');
            });
        }
    }
};