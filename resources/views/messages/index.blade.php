@extends('layouts.app')

@section('title', 'Messagerie')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900 flex items-center">
            <svg class="w-7 h-7 mr-3 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
            Messagerie
        </h1>
        <a href="{{ route('messages.create') }}" class="btn-primary">
            <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nouveau message
        </a>
    </div>

    {{-- ✅ NAVIGATION CORRIGÉE --}}
    <div class="bg-white shadow rounded-lg">
        <div class="border-b border-gray-200">
            <nav class="flex -mb-px">
                <a href="{{ route('messages.index') }}" class="py-4 px-6 border-b-2 {{ !request()->routeIs('messages.sent') ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }} font-medium text-sm">
                    <svg class="w-5 h-5 mr-2 inline-block" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                    </svg>
                    Reçus
                    @if($unreadCount > 0)
                        <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">{{ $unreadCount }}</span>
                    @endif
                </a>
                <a href="{{ route('messages.sent') }}" class="py-4 px-6 border-b-2 {{ request()->routeIs('messages.sent') ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }} font-medium text-sm">
                    <svg class="w-5 h-5 mr-2 inline-block" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                    Envoyés
                </a>
            </nav>
        </div>

        {{-- ✅ LISTE DES MESSAGES AMÉLIORÉE --}}
        <div class="divide-y divide-gray-200">
            @forelse($messages as $message)
                <div class="px-6 py-4 hover:bg-gray-50 {{ $message->is_read ? 'bg-white' : 'bg-blue-50 border-l-4 border-blue-400' }} transition-colors">
                    <a href="{{ route('messages.show', $message) }}" class="block">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <div class="flex items-center space-x-3 mb-2">
                                    {{-- Avatar --}}
                                    <div class="h-10 w-10 rounded-full bg-gradient-to-r from-blue-400 to-blue-600 flex items-center justify-center">
                                        <span class="text-lg font-semibold text-white">
                                            {{ substr($message->sender->name, 0, 1) }}
                                        </span>
                                    </div>
                                    
                                    {{-- Info expéditeur --}}
                                    <div>
                                        <p class="font-semibold text-gray-900">{{ $message->sender->name }}</p>
                                        <p class="text-sm text-gray-500">{{ $message->created_at->format('d/m/Y H:i') }}</p>
                                    </div>
                                </div>
                                
                                {{-- Sujet du message --}}
                                <h3 class="font-medium {{ $message->is_read ? 'text-gray-700' : 'text-gray-900 font-semibold' }} mb-1">
                                    {{ $message->subject }}
                                </h3>
                                
                                {{-- Aperçu du contenu --}}
                                <p class="text-sm text-gray-600 line-clamp-2">
                                    {{ Str::limit(strip_tags($message->body), 120) }}
                                </p>
                            </div>
                            
                            {{-- Indicateurs et métadonnées --}}
                            <div class="flex flex-col items-end ml-4 space-y-2">
                                @if(!$message->is_read)
                                    <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800">
                                        <span class="w-2 h-2 bg-blue-600 rounded-full mr-1"></span>
                                        Non lu
                                    </span>
                                @endif
                                
                                @if($message->chantier)
                                    <div class="flex items-center text-xs text-gray-500 hover:text-blue-600 transition-colors">
                                        <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                        </svg>
                                        <span class="truncate max-w-32">{{ $message->chantier->titre }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </a>
                </div>
            @empty
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                    </svg>
                    <h3 class="mt-2 text-lg font-medium text-gray-900">Pas de messages</h3>
                    <p class="mt-1 text-gray-500">Vous n'avez pas encore reçu de messages.</p>
                    <div class="mt-6">
                        <a href="{{ route('messages.create') }}" class="btn-primary">
                            Envoyer votre premier message
                        </a>
                    </div>
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if($messages->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $messages->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

