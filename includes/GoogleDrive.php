<?php
require_once __DIR__ . '/../vendor/autoload.php';

class GoogleDrive {
    private $service;
    private $folderId;

    public function __construct() {
        $config = require __DIR__ . '/../config/google_drive_config.php';
        $oauth_file = __DIR__ . '/../credentials_oauth.json';
        
        $client = new Google\Client();
        $client->addScope(Google\Service\Drive::DRIVE);

        if (file_exists($oauth_file)) {
            try {
                $oauth_data = json_decode(file_get_contents($oauth_file), true);
                $client->setClientId($oauth_data['client_id'] ?? '');
                $client->setClientSecret($oauth_data['client_secret'] ?? '');
                $client->refreshToken($oauth_data['refresh_token'] ?? '');
                error_log("GoogleDrive - Autenticación OAuth2 habilitada.");
            } catch (Exception $e) {
                error_log("GoogleDrive - Error en OAuth2: " . $e->getMessage());
                throw new Exception("Error de autenticación. Por favor verifica el archivo credentials_oauth.json.");
            }
        } else {
            throw new Exception("No se encontraron credenciales de Google (credentials_oauth.json no existe).");
        }

        $this->service = new Google\Service\Drive($client);
        $this->folderId = $config['folder_id'];
        
        // Verificar acceso a la carpeta principal
        try {
            $this->service->files->get($this->folderId, ['fields' => 'id, name']);
            error_log("GoogleDrive - Carpeta de Acreedores ok.");
        } catch (Exception $e) {
            $rawError = $e->getMessage();
            $errorJson = json_decode($rawError, true);
            $message = $errorJson['error']['message'] ?? $rawError;
            
            error_log("GoogleDrive - ERROR de acceso a carpeta ($this->folderId): " . $rawError);
            
            if (strpos($rawError, '404') !== false) {
                throw new Exception("No se encontró la carpeta de Google Drive configurada.");
            }
            if (strpos($rawError, '403') !== false) {
                throw new Exception("Acceso denegado. Verifica que tu cuenta de Google tenga permisos sobre la carpeta.");
            }
            throw new Exception("Error de conexión con Google Drive: " . $message);
        }
    }

    /**
     * Sube un archivo a Google Drive
     * @param string $filePath Ruta local del archivo temporal
     * @param string $fileName Nombre con el que se guardará en Drive
     * @return string ID del archivo en Google Drive
     */
    public function subirArchivo($filePath, $fileName, $targetFolderId = null) {
        error_log("GoogleDrive::subirArchivo - Iniciando subida de $fileName");
        $folderToUse = $targetFolderId ?? $this->folderId;
        
        $fileMetadata = new Google\Service\Drive\DriveFile([
            'name' => $fileName,
            'parents' => [$folderToUse]
        ]);

        $content = file_get_contents($filePath);
        if ($content === false) {
            error_log("GoogleDrive::subirArchivo - No se pudo leer el archivo local: $filePath");
            return false;
        }

        // Detectar MimeType dinámicamente
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        try {
            $file = $this->service->files->create($fileMetadata, [
                'data' => $content,
                'mimeType' => $mimeType,
                'uploadType' => 'multipart',
                'fields' => 'id'
            ]);
            error_log("GoogleDrive::subirArchivo - Subida exitosa ($mimeType). ID: " . $file->id);
            return $file->id;
        } catch (Exception $e) {
            error_log("GoogleDrive::subirArchivo - Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Elimina un archivo de Google Drive por su ID
     * @param string $fileId ID del archivo en Google Drive
     */
    public function eliminarArchivo($fileId) {
        try {
            $this->service->files->delete($fileId);
            return true;
        } catch (Exception $e) {
            error_log("Error eliminando archivo de Google Drive: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Genera un enlace de visualización para el archivo
     * @param string $fileId ID del archivo en Google Drive
     * @return string URL de visualización
     */
    public function obtenerEnlaceVisualizacion($fileId) {
        return "https://drive.google.com/file/d/$fileId/view?usp=sharing";
    }
}
