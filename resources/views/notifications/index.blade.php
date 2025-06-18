@extends('layouts.app')

@section('title', 'Mes Notifications')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900 flex items-center mb-4 sm:mb-0">
            <i class="fas fa-bell mr-3 text-primary-600"></i>Mes Notifications
        </h1>
        @if($notifications->where('lu', false)->count() > 0)
            <form method="POST" action="{{ route('notifications.mark-all-read') }}">
                @csrf
                <button type="submit" class="btn btn-outline-primary">
                    <i class="fas fa-check-double mr-2"></i>Tout marquer comme lu
                </button>
            </form>
        @endif
    </div>
    
    <!-- Filtres -->
    <div class="card mb-6">
        <div class="card-body">
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('notifications.index') }}" 
                   class="btn {{ !request('filter') ? 'btn-primary' : 'btn-outline-primary' }}">
                    Toutes
                </a>
                <a href="{{ route('notifications.index', ['filter' => 'unread']) }}" 
                   class="btn {{ request('filter') == 'unread' ? 'btn-primary' : 'btn-outline-primary' }}">
                    Non lues ({{ Auth::user()->notifications()->where('lu', false)->count() }})
                </a>
                <a href="{{ route('notifications.index', ['filter' => 'read']) }}" 
                   class="btn {{ request('filter') == 'read' ? 'btn-primary' : 'btn-outline-primary' }}">
                    Lues
                </a>
            </div>
        </div>
    </div>
    
    <!-- Liste des notifications -->
    @if($notifications->count() > 0)
        <div class="space-y-4">
            @foreach($notifications as $notification)
                <div class="card {{ !$notification->lu ? 'border-primary-400 bg-primary-50' : 'border-gray-200' }} hover:shadow-md transition-shadow">
                    <div class="card-body">
                        <div class="flex items-center space-x-4">
                            <!-- Icône de notification -->
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 rounded-full {{ !$notification->lu ? 'bg-primary-500' : 'bg-gray-400' }} text-white flex items-center justify-center">
                                    <i class="{{ getNotificationIcon($notification->type) }} text-lg"></i>
                                </div>
                            </div>
                            
                            <!-- Contenu principal -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <h5 class="text-lg font-semibold text-gray-900 mb-1 flex items-center">
                                            {{ $notification->titre }}
                                            @if(!$notification->lu)
                                                <span class="badge badge-primary ml-2">Nouveau</span>
                                            @endif
                                        </h5>
                                        <p class="text-gray-700 mb-2">{{ $notification->message }}</p>
                                        <div class="flex flex-wrap items-center gap-4 text-sm text-gray-500">
                                            <span class="flex items-center">
                                                <i class="fas fa-clock mr-1"></i>{{ $notification->created_at->diffForHumans() }}
                                            </span>
                                            @if($notification->chantier)
                                                <span class="flex items-center">
                                                    <i class="fas fa-building mr-1"></i>
                                                    <a href="{{ route('notifications.view', $notification) }}" 
                                                       class="text-primary-600 hover:text-primary-800 font-medium">
                                                        {{ $notification->chantier->titre }}
                                                    </a>
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <!-- Actions MISES À JOUR -->
                                    <div class="flex items-center space-x-2 ml-4">
                                        @if(!$notification->lu)
                                            <form method="POST" action="{{ route('notifications.read', $notification) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-secondary btn-sm" title="Marquer comme lu seulement">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                        @endif
                                        @if($notification->chantier)
                                            <a href="{{ route('notifications.view', $notification) }}" 
                                               class="btn btn-primary btn-sm">
                                                <i class="fas fa-eye mr-1"></i>Voir{{ !$notification->lu ? ' & Marquer lu' : '' }}
                                            </a>
                                        @else
                                            {{-- Pour les notifications sans chantier --}}
                                            @if(!$notification->lu)
                                                <a href="{{ route('notifications.view', $notification) }}" 
                                                   class="btn btn-primary btn-sm">
                                                    <i class="fas fa-check mr-1"></i>Marquer comme lu
                                                </a>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @if($notification->lu && $notification->lu_at)
                        <div class="card-footer bg-gray-50">
                            <small class="text-gray-500">
                                Lu le {{ $notification->lu_at->format('d/m/Y à H:i') }}
                            </small>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
        
        <!-- Pagination -->
        <div class="flex justify-center mt-8">
            {{ $notifications->links() }}
        </div>
    @else
        <div class="card">
            <div class="card-body text-center py-12">
                <i class="fas fa-bell-slash text-6xl text-gray-400 mb-6"></i>
                <h4 class="text-xl font-semibold text-gray-900 mb-2">Aucune notification</h4>
                <p class="text-gray-500">
                    @if(request('filter') == 'unread')
                        Vous avez lu toutes vos notifications !
                    @else
                        Vous n'avez pas encore reçu de notifications.
                    @endif
                </p>
            </div>
        </div>
    @endif
</div>

@php
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
        default => 'fas fa-bell'
    };
}
@endphp
@endsection