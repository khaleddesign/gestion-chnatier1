<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Upload</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .container { max-width: 600px; }
        .form-group { margin-bottom: 20px; }
        .btn { background: #007bff; color: white; padding: 10px 20px; border: none; cursor: pointer; }
        .result { background: #f8f9fa; padding: 20px; margin-top: 20px; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Test d'Upload de Fichiers</h1>
        
        <form action="{{ route('test.upload.process') }}" method="POST" enctype="multipart/form-data" id="uploadForm">
            @csrf
            <div class="form-group">
                <label for="test_file">Sélectionner un fichier de test :</label><br>
                <input type="file" name="test_file" id="test_file" accept="image/*,.pdf,.doc,.docx" required>
                <small>Max 10MB - Images, PDF, DOC autorisés</small>
            </div>
            
            <button type="submit" class="btn">Tester l'Upload</button>
        </form>
        
        <div id="result" class="result" style="display: none;">
            <h3>Résultat du test :</h3>
            <pre id="resultContent"></pre>
        </div>
    </div>

    <script>
        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const resultDiv = document.getElementById('result');
            const resultContent = document.getElementById('resultContent');
            
            resultContent.textContent = 'Upload en cours...';
            resultDiv.style.display = 'block';
            
            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                }
            })
            .then(response => response.json())
            .then(data => {
                resultContent.textContent = JSON.stringify(data, null, 2);
            })
            .catch(error => {
                resultContent.textContent = 'Erreur: ' + error.message;
            });
        });
    </script>
</body>
</html>