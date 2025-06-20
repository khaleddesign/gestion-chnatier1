@extends('layouts.app')

@section('title', 'Mes Notifications')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900 flex items-center mb-4 sm:mb-0">
            <svg class="w-8 h-8 mr-3 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM9 7H4l5-5v5zm6 6l-2-2v6l2-2h6v-2H15z"/>
            </svg>
            Mes Notifications
        </h1>
        
        @if(Auth::user()->getNotificationsNonLues() > 0)
            <form method="POST" action="{{ route('notifications.mark-all-read') }}" class="inline">
                @csrf
                <button type="submit" class="btn-outline">
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Tout marquer comme lu
                </button>
            </form>
        @endif
    </div>
    
    {{-- ✅ FILTRES AMÉLIORÉS --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
        <div class="px-6 py-4">
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('notifications.index') }}" 
                   class="px-4 py-2 rounded-md text-sm font-medium {{ !request('filter') ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    Toutes ({{ Auth::user()->notifications()->count() }})
                </a>
                <a href="{{ route('notifications.index', ['filter' => 'unread']) }}" 
                   class="px-4 py-2 rounded-md text-sm font-medium {{ request('filter') == 'unread' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    Non lues ({{ Auth::user()->notifications()->where('lu', false)->count() }})
                </a>
                <a href="{{ route('notifications.index', ['filter' => 'read']) }}" 
                   class="px-4 py-2 rounded-md text-sm font-medium {{ request('filter') == 'read' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    Lues
                </a>
            </div>
        </div>
    </div>
    
    {{-- ✅ LISTE DES NOTIFICATIONS AMÉLIORÉE --}}
    @if($notifications->count() > 0)
        <div class="space-y-4">
            @foreach($notifications as $notification)
                <div class="bg-white rounded-lg shadow-sm border {{ !$notification->lu ? 'border-blue-300 bg-blue-50' : 'border-gray-200' }} hover:shadow-md transition-shadow">
                    <div class="p-6">
                        <div class="flex items-start space-x-4">
                            {{-- Icône de notification --}}
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 rounded-full {{ !$notification->lu ? 'bg-blue-500' : 'bg-gray-400' }} text-white flex items-center justify-center">
                                    <i class="{{ getNotificationIcon($notification->type) }} text-lg"></i>
                                </div>
                            </div>
                            
                            {{-- Contenu principal --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <h3 class="text-lg font-semibold text-gray-900 mb-1 flex items-center">
                                            {{ $notification->titre }}
                                            @if(!$notification->lu)
                                                <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                    Nouveau
                                                </span>
                                            @endif
                                        </h3>
                                        <p class="text-gray-700 mb-3">{{ $notification->message }}</p>
                                        
                                        <div class="flex flex-wrap items-center gap-4 text-sm text-gray-500">
                                            <span class="flex items-center">
                                                <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                {{ $notification->created_at->diffForHumans() }}
                                            </span>
                                            
                                            @if($notification->chantier)
                                                <span class="flex items-center">
                                                    <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                                    </svg>
                                                    {{ $notification->chantier->titre }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    {{-- ✅ ACTIONS CORRIGÉES --}}
                                    <div class="flex items-center space-x-2 ml-4">
                                        @if(!$notification->lu)
                                            {{-- Bouton marquer comme lu seulement --}}
                                            <form method="POST" action="{{ route('notifications.read', $notification) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="inline-flex items-center px-3 py-1 border border-gray-300 rounded-md text-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" title="Marquer comme lu">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                </button>
                                            </form>
                                        @endif
                                        
                                        {{-- Bouton voir (avec chantier) ou marquer lu (sans chantier) --}}
                                        @if($notification->chantier)
                                            <a href="{{ route('notifications.view', $notification) }}"
                                               class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                                <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                                Voir{{ !$notification->lu ? ' & Marquer lu' : '' }}
                                            </a>
                                        @else
                                            @if(!$notification->lu)
                                                <a href="{{ route('notifications.view', $notification) }}" 
                                                   class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                                                    <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                    Marquer comme lu
                                                </a>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Pied de notification pour les lues --}}
                    @if($notification->lu && $notification->lu_at)
                        <div class="px-6 py-3 bg-gray-50 border-t border-gray-200 rounded-b-lg">
                            <p class="text-sm text-gray-500">
                                Lu le {{ $notification->lu_at->format('d/m/Y à H:i') }}
                            </p>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
        
        {{-- Pagination --}}
        @if($notifications->hasPages())
            <div class="mt-8">
                {{ $notifications->links() }}
            </div>
        @endif
    @else
        {{-- État vide --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM9 7H4l5-5v5z"/>
                </svg>
                <h3 class="mt-2 text-lg font-medium text-gray-900">Aucune notification</h3>
                <p class="mt-1 text-gray-500">
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
@endsection
