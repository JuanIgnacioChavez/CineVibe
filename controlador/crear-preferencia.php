<?php
session_start();

// Configurar headers y configuración de error
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

function debug_log($message) {
    error_log(date('Y-m-d H:i:s') . ": " . print_r($message, true));
}

function send_json_response($data, $status_code = 200) {
    http_response_code($status_code);
    echo json_encode($data);
    exit;
}

try {
    debug_log("Iniciando proceso de creación de preferencia");

    // Manejo de CORS para el método OPTIONS
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        send_json_response(['status' => 'ok'], 200);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    // Obtener y verificar datos de entrada
    $rawInput = file_get_contents('php://input');
    debug_log("Datos recibidos: " . $rawInput);

    if (empty($rawInput)) {
        throw new Exception('No se recibieron datos');
    }

    $input = json_decode($rawInput, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Error decodificando JSON: ' . json_last_error_msg());
    }

    // Verificar que funcionId y asientos están presentes en la solicitud
    if (!isset($input['funcionId']) || !isset($input['asientos']) || !is_array($input['asientos'])) {
        throw new Exception('Datos de función o asientos incompletos');
    }

    // Guardar datos en la sesión
    $_SESSION['funcionId'] = $input['funcionId'];
    $_SESSION['asientos'] = $input['asientos'];

    // Guardar aperitivos en la sesión si existen
    if (isset($input['aperitivos']) && is_array($input['aperitivos'])) {
        $_SESSION['aperitivos'] = $input['aperitivos'];
        debug_log("Aperitivos guardados en la sesión: " . json_encode($_SESSION['aperitivos']));
    } else {
        $_SESSION['aperitivos'] = [];
    }

    debug_log("Datos guardados en la sesión: " . json_encode([
        'funcionId' => $_SESSION['funcionId'],
        'asientos' => $_SESSION['asientos'],
        'aperitivos' => $_SESSION['aperitivos']
    ]));

    // Cargar el autoloader de Composer y configurar MercadoPago
    require_once __DIR__ . '/../vendor/autoload.php';
    \MercadoPago\SDK::setAccessToken('APP_USR-2323788171765170-110810-1678d55203462454c7bca2dde7f3f5fe-2086356036');

    // Procesar items de la compra (entradas)
    $items = [];
    foreach ($input['items'] as $item) {
        if (!isset($item['name'], $item['quantity'], $item['price'])) {
            throw new Exception('Item con datos incompletos: ' . json_encode($item));
        }

        $item_mp = new \MercadoPago\Item();
        $item_mp->title = $item['name'];
        $item_mp->quantity = (int)$item['quantity'];
        $item_mp->unit_price = (float)$item['price'];
        $items[] = $item_mp;
    }

    // Procesar aperitivos como items adicionales
    if (isset($input['aperitivos']) && is_array($input['aperitivos'])) {
        foreach ($input['aperitivos'] as $aperitivo) {
            if (!isset($aperitivo['nombre'], $aperitivo['cantidad'], $aperitivo['precio'])) {
                throw new Exception('Aperitivo con datos incompletos: ' . json_encode($aperitivo));
            }

            $item_mp = new \MercadoPago\Item();
            $item_mp->title = "Aperitivo: " . $aperitivo['nombre'];
            $item_mp->quantity = (int)$aperitivo['cantidad'];
            $item_mp->unit_price = (float)$aperitivo['precio'];
            $items[] = $item_mp;
        }
    }

    // Crear preferencia en MercadoPago
    $preference = new \MercadoPago\Preference();
    $preference->items = $items;
    $preference->back_urls = [
        "success" => "https://waveos.tail5980dd.ts.net/tesisMVC/vista/pago_exitoso.php",
        "failure" => "https://waveos.tail5980dd.ts.net/tesisMVC/vista/pago_fallido.php",
        "pending" => "https://waveos.tail5980dd.ts.net/tesisMVC/vista/pago_pendiente.php"
    ];
    $preference->auto_return = "approved";

    if (!$preference->save()) {
        throw new Exception('Error al guardar preferencia: ' . json_encode($preference->errors));
    }

    debug_log("Preferencia creada exitosamente con ID: " . $preference->id);
    send_json_response([
        'status' => 'success',
        'id' => $preference->id
    ]);

} catch (Exception $e) {
    debug_log("Error: " . $e->getMessage());
    send_json_response([
        'status' => 'error',
        'message' => $e->getMessage()
    ], 500);
}