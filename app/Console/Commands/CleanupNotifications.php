<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\NotificationService;

class CleanupNotifications extends Command
{
    protected $signature = 'notifications:cleanup {--days=90 : Nombre de jours à conserver}';
    protected $description = 'Nettoie les anciennes notifications lues';

    public function handle()
    {
        $days = $this->option('days');
        
        $this->info("Nettoyage des notifications de plus de {$days} jours...");
        
        $deleted = NotificationService::cleanup($days);
        
        $this->info("✅ {$deleted} notification(s) supprimée(s)");
        
        return 0;
    }
}