import Alpine from 'alpinejs';

// Configuration Alpine.js
window.Alpine = Alpine;

// Utilitaires globaux pour Tailwind
window.toggleClass = function(element, className) {
    element.classList.toggle(className);
};

// Gestion des modales
window.openModal = function(modalId) {
    document.getElementById(modalId).classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
};

window.closeModal = function(modalId) {
    document.getElementById(modalId).classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
};
// Fermer les modals avec la touche Escape
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        document.querySelectorAll('.modal-overlay:not(.hidden)').forEach(modal => {
            modal.classList.add('hidden');
        });
        document.body.classList.remove('overflow-hidden');
    }
});

// Fonction pour les notifications toast
window.showToast = function(message, type = 'info') {
    const toastContainer = document.getElementById('toast-container') || (() => {
        const container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'fixed top-4 right-4 z-50 space-y-2';
        document.body.appendChild(container);
        return container;
    })();

    const toast = document.createElement('div');
    const bgColor = {
        'success': 'bg-green-100 border-green-400 text-green-700',
        'error': 'bg-red-100 border-red-400 text-red-700',
        'warning': 'bg-yellow-100 border-yellow-400 text-yellow-700',
        'info': 'bg-blue-100 border-blue-400 text-blue-700'
    }[type] || 'bg-gray-100 border-gray-400 text-gray-700';

    toast.className = `max-w-sm w-full ${bgColor} border-l-4 p-4 shadow-lg rounded-md animate-slide-down`;
    toast.innerHTML = `
        <div class="flex justify-between items-center">
            <p class="text-sm font-medium">${message}</p>
            <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-current hover:opacity-75">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                </svg>
            </button>
        </div>
    `;

    toastContainer.appendChild(toast);

    // Auto-suppression après 5 secondes
    setTimeout(() => toast.remove(), 5000);
};

// Copier dans le presse-papiers
window.copyToClipboard = function(text) {
    navigator.clipboard.writeText(text).then(() => {
        showToast('Copié dans le presse-papiers !', 'success');
    });
};

// Démarrer Alpine
Alpine.start();