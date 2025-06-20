<?php


if (!function_exists('getNotificationIcon')) {
    function getNotificationIcon($type) {
        return match($type) {
            'nouveau_chantier' => 'fas fa-plus-circle',
            'changement_statut' => 'fas fa-sync',
            'nouvelle_etape' => 'fas fa-tasks',
            'etape_terminee' => 'fas fa-check-circle',
            'nouveau_document' => 'fas fa-file',
            'nouveau_commentaire_client' => 'fas fa-comment',
            'nouveau_commentaire_commercial' => 'fas fa-reply',
            'chantier_retard' => 'fas fa-exclamation-triangle',
            'nouveau_message' => 'fas fa-envelope',
            'compte_cree' => 'fas fa-user-plus',
            default => 'fas fa-bell'
        };
    }
}