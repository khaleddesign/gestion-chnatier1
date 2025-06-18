// resources/views/messages/create.blade.php
@extends('layouts.app')

@section('title', isset($originalMessage) ? 'Répondre' : 'Nouveau message')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <nav class="breadcrumb mb-6">
        <a href="{{ route('messages.index') }}" class="breadcrumb-item">Messagerie</a>
        <span class="breadcrumb-separator">/</span>
        <span class="text-gray-900">{{ isset($originalMessage) ? 'Répondre' : 'Nouveau message' }}</span>
    </nav>

    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h1 class="text-xl font-bold text-gray-900">{{ isset($originalMessage) ? 'Répondre au message' : 'Nouveau message' }}</h1>
        </div>

        <form method="POST" action="{{ route('messages.store') }}">
            @csrf
            <div class="px-6 py-4 space-y-4">
                <div>
                    <label for="recipient_id" class="form-label">Destinataire *</label>
                    <select id="recipient_id" name="recipient_id" class="form-select" required {{ isset($recipientId) ? 'readonly' : '' }}>
                        <option value="">Sélectionnez un destinataire</option>
                        @foreach($recipients as $recipient)
                            <option value="{{ $recipient->id }}" {{ old('recipient_id', $recipientId ?? '') == $recipient->id ? 'selected' : '' }}>
                                {{ $recipient->name }} ({{ ucfirst($recipient->role) }})
                            </option>
                        @endforeach
                    </select>
                    @error('recipient_id')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                @if($chantier)
                    <div>
                        <label class="form-label">Chantier associé</label>
                        <div class="flex items-center px-4 py-2 border border-gray-300 rounded-md bg-gray-50">
                            <svg class="w-5 h-5 mr-2 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            <span class="text-gray-700">{{ $chantier->titre }}</span>
                        </div>
                        <input type="hidden" name="chantier_id" value="{{ $chantier->id }}">
                    </div>
                @endif

                <div>
                    <label for="subject" class="form-label">Sujet *</label>
                    <input type="text" id="subject" name="subject" class="form-input" required value="{{ old('subject', $subject ?? '') }}">
                    @error('subject')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="body" class="form-label">Message *</label>
                    <textarea id="body" name="body" rows="6" class="form-textarea" required>{{ old('body', isset($originalMessage) ? "\n\n------ Message original ------\n" . $originalMessage->body : '') }}</textarea>
                    @error('body')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="pt-4 flex justify-end space-x-3">
                    <a href="{{ url()->previous() }}" class="btn-outline">Annuler</a>
                    <button type="submit" class="btn-primary">Envoyer le message</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection