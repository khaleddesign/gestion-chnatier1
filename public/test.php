<?php
// test.php - Place ce fichier dans public/ et accède via /test.php

echo "<h2>Configuration PHP pour uploads</h2>";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "<br>";
echo "post_max_size: " . ini_get('post_max_size') . "<br>";
echo "max_file_uploads: " . ini_get('max_file_uploads') . "<br>";
echo "memory_limit: " . ini_get('memory_limit') . "<br>";
echo "max_execution_time: " . ini_get('max_execution_time') . "<br>";

// Test permissions
$storage_path = storage_path('app/public');
echo "<br><h3>Permissions</h3>";
echo "Storage path: $storage_path<br>";
echo "Existe: " . (is_dir($storage_path) ? 'Oui' : 'Non') . "<br>";
echo "Writable: " . (is_writable($storage_path) ? 'Oui' : 'Non') . "<br>";

// Test lien symbolique
$public_storage = public_path('storage');
echo "Public storage: $public_storage<br>";
echo "Lien symbolique existe: " . (is_link($public_storage) ? 'Oui' : 'Non') . "<br>";
?>