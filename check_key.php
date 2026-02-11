<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');

// Pfad zur JSON-Datei
$jsonFile = 'keys.json';

// Funktion zum Lesen der Keys
function getKeys() {
    global $jsonFile;
    if (file_exists($jsonFile)) {
        $content = file_get_contents($jsonFile);
        return json_decode($content, true);
    }
    return null;
}

// Funktion zum Speichern der Keys
function saveKeys($data) {
    global $jsonFile;
    return file_put_contents($jsonFile, json_encode($data, JSON_PRETTY_PRINT));
}

// API-Endpunkt
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    $key = $input['key'] ?? '';
    
    $keysData = getKeys();
    
    switch ($action) {
        case 'verify':
            // Prüfen ob Key gültig ist
            $isValid = in_array($key, $keysData['valid_keys']);
            $isUsed = in_array($key, $keysData['used_keys']);
            
            if ($isValid && !$isUsed) {
                // Key als verwendet markieren
                $keysData['used_keys'][] = $key;
                saveKeys($keysData);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Key ist gültig!',
                    'hwid_locked' => false
                ]);
            } else if ($isUsed) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Key wurde bereits verwendet!'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Ungültiger Key!'
                ]);
            }
            break;
            
        case 'check':
            // Nur prüfen ohne zu verbrauchen
            $isValid = in_array($key, $keysData['valid_keys']);
            $isUsed = in_array($key, $keysData['used_keys']);
            
            echo json_encode([
                'success' => ($isValid && !$isUsed),
                'is_used' => $isUsed,
                'message' => $isValid ? ($isUsed ? 'Key bereits verwendet' : 'Key gültig') : 'Key ungültig'
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Unbekannte Aktion']);
    }
} else {
    // GET Request - Info
    echo json_encode([
        'name' => 'Loader Key System',
        'version' => '1.0',
        'status' => 'online'
    ]);
}
?>
