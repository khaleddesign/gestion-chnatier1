// Variables globales pour la galerie
let photosData = [];
let currentPhotoIndex = 0;

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    initPhotoGallery();
});

// Initialiser la galerie
function initPhotoGallery() {
    // Éléments DOM pour la galerie photo
    const photoUploadForm = document.getElementById('form-upload-photos');
    const photoInput = document.getElementById('photos');
    const dropZone = document.getElementById('drop-zone');
    
    if (photoInput && dropZone) {
        // Gestion du drag & drop
        dropZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.add('border-blue-400', 'bg-blue-50');
        });
        
        dropZone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.remove('border-blue-400', 'bg-blue-50');
        });
        
        dropZone.addEventListener('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.remove('border-blue-400', 'bg-blue-50');
            
            const files = Array.from(e.dataTransfer.files).filter(file => file.type.startsWith('image/'));
            if (files.length > 0) {
                photoInput.files = createFileList(files);
                previewFiles(files);
            }
        });
        
        // Gestion sélection de fichiers
        photoInput.addEventListener('change', function(e) {
            const files = Array.from(e.target.files);
            previewFiles(files);
        });
        
        // Gestion du formulaire d'upload
        if (photoUploadForm) {
            photoUploadForm.addEventListener('submit', handlePhotoUpload);
        }
    }
    
    // Charger les photos existantes
    const chantierId = document.querySelector('input[name="chantier_id"]')?.value;
    if (chantierId) {
        fetchPhotos(chantierId);
    }
    
    // Navigation clavier pour lightbox
    document.addEventListener('keydown', function(e) {
        const lightbox = document.getElementById('modal-lightbox');
        if (lightbox && !lightbox.classList.contains('hidden')) {
            switch(e.key) {
                case 'Escape':
                    fermerLightbox();
                    break;
                case 'ArrowLeft':
                    photoPrecedente();
                    break;
                case 'ArrowRight':
                    photoSuivante();
                    break;
            }
        }
    });
}

// Création d'une FileList à partir d'un tableau de fichiers
function createFileList(files) {
    const dt = new DataTransfer();
    files.forEach(file => dt.items.add(file));
    return dt.files;
}

// Prévisualiser les fichiers sélectionnés
function previewFiles(files) {
    const preview = document.getElementById('preview-photos');
    const grid = document.getElementById('preview-grid');
    const uploadBtn = document.getElementById('btn-upload');
    const fileCount = document.getElementById('file-count');
    
    if (files.length === 0) {
        preview.classList.add('hidden');
        uploadBtn.disabled = true;
        fileCount.textContent = '(0)';
        return;
    }
    
    preview.classList.remove('hidden');
    uploadBtn.disabled = false;
    fileCount.textContent = `(${files.length})`;
    
    grid.innerHTML = '';
    
    files.forEach((file, index) => {
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const div = document.createElement('div');
                div.className = 'relative group';
                div.innerHTML = `
                    <img src="${e.target.result}" alt="${file.name}" class="w-full h-20 object-cover rounded-lg">
                    <button type="button" onclick="supprimerPreview(${index})" class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-5 h-5 text-xs hover:bg-red-600 opacity-0 group-hover:opacity-100 transition-opacity">×</button>
                    <div class="absolute bottom-0 left-0 right-0 bg-black bg-opacity-50 text-white text-xs p-1 rounded-b-lg truncate">${file.name}</div>
                `;
                grid.appendChild(div);
            };
            reader.readAsDataURL(file);
        }
    });
}

// Supprimer une prévisualisation
function supprimerPreview(index) {
    const photoInput = document.getElementById('photos');
    const files = Array.from(photoInput.files);
    files.splice(index, 1);
    
    const dt = new DataTransfer();
    files.forEach(file => dt.items.add(file));
    photoInput.files = dt.files;
    
    previewFiles(files);
}

// Gérer l'upload des photos
async function handlePhotoUpload(e) {
    e.preventDefault();
    
    const formData = new FormData();
    const photoInput = document.getElementById('photos');
    const chantierId = document.querySelector('input[name="chantier_id"]').value;
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    
    if (photoInput.files.length === 0) return;
    
    Array.from(photoInput.files).forEach(file => {
        formData.append('photos[]', file);
    });
    
    formData.append('chantier_id', chantierId);
    formData.append('_token', csrfToken);
    
    const progressContainer = document.getElementById('upload-progress');
    const progressBar = document.getElementById('progress-bar');
    const progressText = document.getElementById('progress-text');
    const uploadBtn = document.getElementById('btn-upload');
    const cancelBtn = document.getElementById('btn-cancel');
    
    progressContainer.classList.remove('hidden');
    uploadBtn.disabled = true;
    cancelBtn.disabled = true;
    
    try {
        const xhr = new XMLHttpRequest();
        
        xhr.upload.addEventListener('progress', function(e) {
            if (e.lengthComputable) {
                const percentComplete = (e.loaded / e.total) * 100;
                progressBar.style.width = percentComplete + '%';
                progressText.textContent = Math.round(percentComplete) + '%';
            }
        });
        
        xhr.addEventListener('load', function() {
            if (xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    showToast('Photos uploadées avec succès !', 'success');
                    closeModal('modal-upload-photos');
                    fetchPhotos(chantierId);
                    resetUploadForm();
                } else {
                    showToast('Erreur: ' + response.message, 'error');
                }
            } else {
                showToast('Erreur lors de l\'upload', 'error');
            }
            
            progressContainer.classList.add('hidden');
            uploadBtn.disabled = false;
            cancelBtn.disabled = false;
        });
        
        xhr.addEventListener('error', function() {
            showToast('Erreur réseau lors de l\'upload', 'error');
            progressContainer.classList.add('hidden');
            uploadBtn.disabled = false;
            cancelBtn.disabled = false;
        });
        
        xhr.open('POST', '/api/photos/upload');
        xhr.send(formData);
        
    } catch (error) {
        console.error('Erreur upload:', error);
        showToast('Erreur lors de l\'upload', 'error');
    }
}

// Réinitialiser le formulaire d'upload
function resetUploadForm() {
    document.getElementById('photos').value = '';
    document.getElementById('preview-photos').classList.add('hidden');
    document.getElementById('preview-grid').innerHTML = '';
    document.getElementById('btn-upload').disabled = true;
    document.getElementById('file-count').textContent = '(0)';
    document.getElementById('upload-progress').classList.add('hidden');
    document.getElementById('progress-bar').style.width = '0%';
    document.getElementById('progress-text').textContent = '0%';
}

// Récupérer les photos d'un chantier
async function fetchPhotos(chantierId) {
    try {
        const response = await fetch(`/api/chantiers/${chantierId}/photos`);
        const data = await response.json();
        
        if (data.success) {
            photosData = data.photos;
            mettreAJourGalerie();
        }
    } catch (error) {
        console.error('Erreur chargement photos:', error);
    }
}

// Mettre à jour l'affichage de la galerie
function mettreAJourGalerie() {
    const galerieContainer = document.getElementById('galerie-photos');
    const photosCount = document.querySelector('.bg-blue-100.text-blue-800');
    
    if (!galerieContainer) return;
    
    if (photosCount) {
        photosCount.textContent = photosData.length;
    }
    
    if (photosData.length === 0) {
        galerieContainer.innerHTML = `
            <div class="text-center py-8">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">Aucune photo</h3>
                <p class="mt-1 text-sm text-gray-500">Commencez par ajouter des photos de ce chantier.</p>
                <button onclick="openModal('modal-upload-photos')" class="mt-4 btn-sm btn-primary">
                    <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Ajouter des photos
                </button>
            </div>
        `;
        return;
    }
    
    let html = `
        <div class="flex space-x-2">
            <!-- Photo principale (format 16:9) -->
            <div class="w-full md:w-2/3 relative cursor-pointer" onclick="ouvrirLightbox(0)">
                <img src="${photosData[0].thumbnail}" 
                     alt="${photosData[0].nom}" 
                     class="w-full h-32 object-cover rounded-lg">
            </div>
            
            <!-- Mini-photos en grille 2x2 -->
            <div class="w-full md:w-1/3 grid grid-cols-2 grid-rows-2 gap-2">
    `;
    
    // Ajouter les 3 miniatures suivantes
    for (let i = 1; i < Math.min(4, photosData.length); i++) {
        if (i === 3 && photosData.length > 4) {
            // Case "+X" pour indiquer qu'il y a plus de photos
            html += `
                <div class="relative cursor-pointer bg-gray-800 h-12 rounded-lg flex items-center justify-center text-white font-medium"
                     onclick="voirToutesPhotos()">
                    +${photosData.length - 3}
                </div>
            `;
        } else {
            html += `
                <div class="relative cursor-pointer" onclick="ouvrirLightbox(${i})">
                    <img src="${photosData[i].thumbnail}" 
                         alt="${photosData[i].nom}" 
                         class="w-full h-12 object-cover rounded-lg">
                </div>
            `;
        }
    }
    
    html += `
            </div>
        </div>
    `;
    
    galerieContainer.innerHTML = html;
}

// Ouvrir la lightbox
function ouvrirLightbox(index) {
    currentPhotoIndex = index;
    const modal = document.getElementById('modal-lightbox');
    const img = document.getElementById('lightbox-image');
    const title = document.getElementById('lightbox-title');
    const info = document.getElementById('lightbox-info');
    const counter = document.getElementById('lightbox-counter');
    
    const photo = photosData[index];
    
    img.src = photo.url;
    img.alt = photo.nom;
    title.textContent = photo.nom;
    info.textContent = `Ajouté le ${photo.date}`;
    counter.textContent = `${index + 1} / ${photosData.length}`;
    
    modal.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
}

// Fermer la lightbox
function fermerLightbox() {
    document.getElementById('modal-lightbox').classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
}

// Navigation dans la lightbox
function photoPrecedente() {
    if (currentPhotoIndex > 0) {
        ouvrirLightbox(currentPhotoIndex - 1);
    }
}

function photoSuivante() {
    if (currentPhotoIndex < photosData.length - 1) {
        ouvrirLightbox(currentPhotoIndex + 1);
    }
}

// Télécharger la photo actuelle
function telechargerPhoto() {
    const photo = photosData[currentPhotoIndex];
    const link = document.createElement('a');
    link.href = `/api/photos/${photo.id}/download`;
    link.download = photo.nom;
    link.click();
}

// Supprimer la photo actuelle
async function supprimerPhoto() {
    if (!confirm('Êtes-vous sûr de vouloir supprimer cette photo ?')) return;
    
    const photo = photosData[currentPhotoIndex];
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    
    try {
        const response = await fetch(`/api/photos/${photo.id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Photo supprimée avec succès', 'success');
            fermerLightbox();
            
            const chantierId = document.querySelector('input[name="chantier_id"]').value;
            fetchPhotos(chantierId);
        } else {
            showToast('Erreur lors de la suppression', 'error');
        }
    } catch (error) {
        console.error('Erreur suppression:', error);
        showToast('Erreur lors de la suppression', 'error');
    }
}

// Afficher toutes les photos dans une modal
function voirToutesPhotos() {
    const modal = document.getElementById('modal-toutes-photos');
    const grid = document.getElementById('toutes-photos-grid');
    
    let html = '';
    photosData.forEach((photo, index) => {
        html += `
            <div class="relative group cursor-pointer" onclick="ouvrirLightbox(${index}); closeModal('modal-toutes-photos');">
                <img src="${photo.thumbnail}" alt="${photo.nom}" class="w-full h-24 object-cover rounded-lg hover:opacity-75 transition-opacity">
                <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 transition-opacity rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-white opacity-0 group-hover:opacity-100 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7" />
                    </svg>
                </div>
                <div class="absolute bottom-0 left-0 right-0 bg-black bg-opacity-50 text-white text-xs p-1 rounded-b-lg">
                    <p class="truncate">${photo.nom}</p>
                </div>
            </div>
        `;
    });
    
    grid.innerHTML = html;
    openModal('modal-toutes-photos');
}

// Exposer les fonctions nécessaires globalement
window.ouvrirLightbox = ouvrirLightbox;
window.fermerLightbox = fermerLightbox;
window.photoPrecedente = photoPrecedente;
window.photoSuivante = photoSuivante;
window.telechargerPhoto = telechargerPhoto;
window.supprimerPhoto = supprimerPhoto;
window.voirToutesPhotos = voirToutesPhotos;
window.supprimerPreview = supprimerPreview;