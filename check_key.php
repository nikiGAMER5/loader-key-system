<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Pfad zur JSON-Datei
$jsonFile = 'keys.json';

// Funktion zum Lesen der Keys
function getKeys() {
    global $jsonFile;
    if (file_exists($jsonFile)) {
        $content = file_get_contents($jsonFile);
        return json_decode($content, true);
    }
    return [
        'valid_keys' => [],
        'used_keys' => [],
        'last_updated' => date('Y-m-d')
    ];
}

// Funktion zum Speichern der Keys
function saveKeys($data) {
    global $jsonFile;
    return file_put_contents($jsonFile, json_encode($data, JSON_PRETTY_PRINT));
}

// OPTIONS Request für CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// API-Endpunkt
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode(['success' => false, 'message' => 'Ungültiges JSON']);
        exit();
    }
    
    $action = $input['action'] ?? '';
    $key = trim($input['key'] ?? '');
    $hwid = $input['hwid'] ?? '';
    
    $keysData = getKeys();
    
    switch ($action) {
        case 'verify':
            // Prüfen ob Key gültig ist
            $isValid = in_array($key, $keysData['valid_keys']);
            $isUsed = in_array($key, $keysData['used_keys']);
            
            if ($isValid && !$isUsed) {
                // Key als verwendet markieren
                $keysData['used_keys'][] = $key;
                
                // HWID speichern wenn vorhanden
                if (!isset($keysData['hwid_locks'])) {
                    $keysData['hwid_locks'] = [];
                }
                if (!empty($hwid)) {
                    $keysData['hwid_locks'][$key] = $hwid;
                }
                
                $keysData['last_updated'] = date('Y-m-d H:i:s');
                saveKeys($keysData);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Key erfolgreich aktiviert!',
                    'expires' => date('Y-m-d', strtotime('+1 year'))
                ]);
            } else if ($isUsed) {
                // Prüfen ob gleiche HWID
                $savedHwid = $keysData['hwid_locks'][$key] ?? '';
                if (!empty($savedHwid) && $savedHwid === $hwid) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Key bereits aktiviert (gleicher PC)'
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Key wurde bereits auf einem anderen PC verwendet!'
                    ]);
                }
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Ungültiger Lizenz-Key!'
                ]);
            }
            break;
            
        case 'check':
            $isValid = in_array($key, $keysData['valid_keys']);
            $isUsed = in_array($key, $keysData['used_keys']);
            $savedHwid = $keysData['hwid_locks'][$key] ?? '';
            
            echo json_encode([
                'success' => ($isValid && !$isUsed),
                'is_used' => $isUsed,
                'hwid_match' => ($isUsed && !empty($savedHwid) && $savedHwid === $hwid),
                'message' => $isValid ? ($isUsed ? 'Key bereits verwendet' : 'Key gültig') : 'Key ungültig'
            ]);
            break;
            
        case 'list':
            // Nur für Admin-Tests
            echo json_encode([
                'success' => true,
                'valid_keys_count' => count($keysData['valid_keys']),
                'used_keys_count' => count($keysData['used_keys']),
                'valid_keys' => $keysData['valid_keys'],
                'used_keys' => $keysData['used_keys']
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
        'status' => 'online',
        'endpoint' => 'https://nikigamer5.github.io/loader-key-system/check_key.php',
        'server_time' => date('Y-m-d H:i:s')
    ]);
}
?>
