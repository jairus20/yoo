<?php
/**
 * tcp_server.php
 *
 * Servidor TCP que recibe una línea de números enteros separados por espacios,
 * los ordena utilizando Quick Sort y responde con los números ordenados.
 */

set_time_limit(0);
error_reporting(E_ALL);

$host = '0.0.0.0';
$port = 12345; // Puerto para conexiones TCP

// Crear socket TCP
$sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
if (!$sock) {
    die("Error al crear el socket: " . socket_strerror(socket_last_error()) . "\n");
}

if (!socket_bind($sock, $host, $port)) {
    die("Error en bind: " . socket_strerror(socket_last_error($sock)) . "\n");
}

if (!socket_listen($sock, 5)) {
    die("Error en listen: " . socket_strerror(socket_last_error($sock)) . "\n");
}

echo "Servidor TCP iniciado en {$host}:{$port}\n";

$clients = [];

while (true) {
    $read = array_merge([$sock], $clients);
    if (socket_select($read, $write = NULL, $except = NULL, 0, 10) < 1) {
        continue;
    }

    // Aceptar nuevas conexiones
    if (in_array($sock, $read)) {
        $newClient = socket_accept($sock);
        if ($newClient !== false) {
            $clients[] = $newClient;
            echo "Nuevo cliente conectado.\n";
        }
        $sockIndex = array_search($sock, $read);
        unset($read[$sockIndex]);
    }

    // Procesar datos de cada cliente conectado
    foreach ($read as $client) {
        $buffer = "";
        while (strpos($buffer, "\n") === false) {
            $part = socket_read($client, 2048, PHP_NORMAL_READ);
            if ($part === false || $part === "") {
                break;
            }
            $buffer .= $part;
        }
        $data = trim($buffer);
        if ($data === "") {
            continue;
        }

        echo "Datos recibidos: $data\n";

        // Convertir la línea en un arreglo de enteros
        $array = array_map('intval', explode(' ', $data));

        // Verificar si el array es válido
        if (empty($array)) {
            $errorMsg = "Error: Se recibió un arreglo vacío.\n";
            socket_write($client, $errorMsg, strlen($errorMsg));
            continue;
        }

        // Medir el tiempo de ejecución del algoritmo de ordenamiento
        $startTime = microtime(true);
        $sortedArray = quickSort($array); // Se usa Quick Sort por defecto
        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        // Enviar el resultado al cliente
        $sortedString = implode(' ', $sortedArray) . "\n";
        socket_write($client, $sortedString, strlen($sortedString));
        echo "Respuesta enviada al cliente: $sortedString (Tiempo: " . round($duration, 5) . "s)\n";
    }
}

socket_close($sock);

/**
 * Implementación de Quick Sort
 */
function quickSort($arr) {
    if (count($arr) <= 1) {
        return $arr;
    }
    $pivot = $arr[0];
    $left = $right = [];
    for ($i = 1; $i < count($arr); $i++) {
        if ($arr[$i] < $pivot) {
            $left[] = $arr[$i];
        } else {
            $right[] = $arr[$i];
        }
    }
    return array_merge(quickSort($left), [$pivot], quickSort($right));
}
?>
