// resources/views/messages/partials/modal.blade.php
<div id="modal-message" class="modal-overlay hidden" onclick="if(event.target === this) closeModal('modal-message')">
    <div class="modal-content max-w-md">
        <div class="modal-header flex justify-between items-center">
            <h3 class="text-lg font-medium text-gray-900">Envoyer un message</h3>
            <button onclick="closeModal('modal-message')" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        
        <form method="POST" action="{{ route('messages.store') }}" id="message-form">
            @csrf
            <div class="modal-body space-y-4 p-4">
                @if($recipient)
                    <div>
                        <label class="form-label">Destinataire</label>
                        <div class="flex items-center px-4 py-2 border border-gray-300 rounded-md bg-gray-50">
                            <span class="text-gray-700">{{ $recipient->name }} ({{ ucfirst($recipient->role) }})</span>
                        </div>
                        <input type="hidden" name="recipient_id" value="{{ $recipient->id }}">
                    </div>
                @else
                    <div>
                        <label for="modal_recipient_id" class="form-label">Destinataire *</label>
                        <select id="modal_recipient_id" name="recipient_id" class="form-select" required>
                            <option value="">Sélectionnez un destinataire</option>
                            @foreach(Auth::user()->isClient() 
                                ? \App\Models\User::where('role', 'commercial')->where('active', true)->get() 
                                : \App\Models\User::where('role', 'client')->where('active', true)->get() as $user)
                                <option value="{{ $user->id }}">{{ $user->name }} ({{ ucfirst($user->role) }})</option>
                            @endforeach
                        </select>
                    </div>
                @endif

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
                    <label for="modal_subject" class="form-label">Sujet *</label>
                    <input type="text" id="modal_subject" name="subject" class="form-input" required value="{{ $subject }}">
                </div>

                <div>
                    <label for="modal_body" class="form-label">Message *</label>
                    <textarea id="modal_body" name="body" rows="4" class="form-textarea" required></textarea>
                </div>
            </div>
            
            <div class="modal-footer flex justify-end space-x-3 p-4 border-t border-gray-200">
                <button type="button" onclick="closeModal('modal-message')" class="btn-outline">Annuler</button>
                <button type="submit" class="btn-primary">Envoyer</button>
            </div>
        </form>
    </div>
</div>