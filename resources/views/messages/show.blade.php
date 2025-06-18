@extends('layouts.app')

@section('title', $message->subject)

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <nav class="breadcrumb mb-6">
        <a href="{{ route('messages.index') }}" class="breadcrumb-item">Messagerie</a>
        <span class="breadcrumb-separator">/</span>
        <span class="text-gray-900">{{ $message->subject }}</span>
    </nav>

    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h1 class="text-xl font-bold text-gray-900">{{ $message->subject }}</h1>
        </div>

        <div class="px-6 py-4">
            <div class="flex justify-between items-start">
                <div class="flex items-center space-x-4">
                    <div class="h-12 w-12 rounded-full bg-gray-200 flex items-center justify-center">
                        <span class="text-lg font-medium text-gray-700">
                            {{ substr($message->sender->name, 0, 1) }}
                        </span>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900">{{ $message->sender->name }}</p>
                        <p class="text-sm text-gray-500">{{ $message->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
                
                <div class="text-sm text-gray-500">
                    @if($message->chantier)
                        <a href="{{ route('chantiers.show', $message->chantier) }}" class="inline-flex items-center text-blue-600 hover:text-blue-800">
                            <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            Chantier : {{ $message->chantier->titre }}
                        </a>
                    @endif
                </div>
            </div>

            <div class="mt-6 text-gray-700 whitespace-pre-wrap">
                {{ $message->body }}
            </div>

            <div class="mt-8 pt-6 border-t border-gray-200 flex space-x-4">
                <a href="{{ route('messages.reply', $message) }}" class="btn-primary">
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                    </svg>
                    Répondre
                </a>
                <a href="{{ route('messages.index') }}" class="btn-outline">
                    Retour à la messagerie
                </a>
            </div>
        </div>
    </div>
</div>
@endsection