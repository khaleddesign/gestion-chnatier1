<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Nettoyer les notifications anciennes chaque semaine
        $schedule->command('notifications:cleanup')
                 ->weekly()
                 ->sundays()
                 ->at('02:00');
        
        // Nettoyer les logs d'erreur chaque mois
        $schedule->command('log:clear')
                 ->monthly();
        
        // Exemple d'autres tâches que tu peux ajouter :
        
        // Sauvegarder la base de données quotidiennement
        // $schedule->command('backup:run')
        //          ->daily()
        //          ->at('01:00');
        
        // Envoyer des notifications de rappel
        // $schedule->command('notifications:send-reminders')
        //          ->dailyAt('09:00');
        
        // Nettoyer les fichiers temporaires
        // $schedule->command('storage:cleanup')
        //          ->weekly();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}