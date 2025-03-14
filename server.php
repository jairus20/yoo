<?php
// Servidor TCP multihilo en PHP con soporte para múltiples algoritmos de ordenamiento
// Ejecutar en Fedora (CLI de PHP)
// Requiere las extensiones "sockets" y "pcntl"

$host = "0.0.0.0";  // Escuchar en todas las interfaces
$port = 2020;      // Puerto de escucha (usar puerto >1024)

error_reporting(E_ALL);
set_time_limit(0);

// Crear el socket TCP
$serverSocket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
if ($serverSocket === false) {
    die("Error al crear el socket: " . socket_strerror(socket_last_error()) . "\n");
}

// Permitir reutilización de la dirección
socket_set_option($serverSocket, SOL_SOCKET, SO_REUSEADDR, 1);

// Vincular el socket a la IP y puerto especificados
if (socket_bind($serverSocket, $host, $port) === false) {
    die("Error al vincular el socket en $host:$port - " . socket_strerror(socket_last_error($serverSocket)) . "\n");
}

// Poner el socket en modo escucha
if (socket_listen($serverSocket, 5) === false) {
    die("Error al poner en escucha el socket: " . socket_strerror(socket_last_error($serverSocket)) . "\n");
}

echo "Servidor iniciado en $host:$port. Esperando conexiones...\n";

// Evitar procesos zombie ignorando SIGCHLD (disponible en sistemas Unix)
if (function_exists('pcntl_signal')) {
    pcntl_signal(SIGCHLD, SIG_IGN);
}

/* --- Funciones de Ordenamiento --- */

// Bubble Sort
function bubbleSort($arr) {
    $n = count($arr);
    for ($i = 0; $i < $n - 1; $i++) {
        for ($j = 0; $j < $n - $i - 1; $j++) {
            if ($arr[$j] > $arr[$j+1]) {
                $temp = $arr[$j];
                $arr[$j] = $arr[$j+1];
                $arr[$j+1] = $temp;
            }
        }
    }
    return $arr;
}

// Counting Sort (asume que los números están en un rango razonable)
function countingSort($arr) {
    if (empty($arr)) return $arr;
    $min = min($arr);
    $max = max($arr);
    $range = $max - $min + 1;
    $count = array_fill(0, $range, 0);
    $output = array_fill(0, count($arr), 0);
    foreach ($arr as $value) {
        $count[$value - $min]++;
    }
    for ($i = 1; $i < $range; $i++) {
        $count[$i] += $count[$i - 1];
    }
    for ($i = count($arr) - 1; $i >= 0; $i--) {
        $output[--$count[$arr[$i] - $min]] = $arr[$i];
    }
    return $output;
}

// Insertion Sort
function insertionSort($arr) {
    $n = count($arr);
    for ($i = 1; $i < $n; $i++) {
        $key = $arr[$i];
        $j = $i - 1;
        while ($j >= 0 && $arr[$j] > $key) {
            $arr[$j+1] = $arr[$j];
            $j--;
        }
        $arr[$j+1] = $key;
    }
    return $arr;
}

// Merge Sort y función auxiliar merge
function mergeSort($arr) {
    if (count($arr) <= 1) return $arr;
    $mid = floor(count($arr) / 2);
    $left = mergeSort(array_slice($arr, 0, $mid));
    $right = mergeSort(array_slice($arr, $mid));
    return merge($left, $right);
}
function merge($left, $right) {
    $result = [];
    while (count($left) > 0 && count($right) > 0) {
        if ($left[0] <= $right[0]) {
            $result[] = array_shift($left);
        } else {
            $result[] = array_shift($right);
        }
    }
    return array_merge($result, $left, $right);
}

// Quick Sort
function quickSort($arr) {
    if (count($arr) < 2) return $arr;
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

// Selection Sort
function selectionSort($arr) {
    $n = count($arr);
    for ($i = 0; $i < $n - 1; $i++) {
        $minIndex = $i;
        for ($j = $i+1; $j < $n; $j++) {
            if ($arr[$j] < $arr[$minIndex]) {
                $minIndex = $j;
            }
        }
        $temp = $arr[$i];
        $arr[$i] = $arr[$minIndex];
        $arr[$minIndex] = $temp;
    }
    return $arr;
}

// Heap Sort y función auxiliar heapify
function heapSort($arr) {
    $n = count($arr);
    for ($i = intval($n / 2) - 1; $i >= 0; $i--) {
        heapify($arr, $n, $i);
    }
    for ($i = $n - 1; $i > 0; $i--) {
        $temp = $arr[0];
        $arr[0] = $arr[$i];
        $arr[$i] = $temp;
        heapify($arr, $i, 0);
    }
    return $arr;
}
function heapify(&$arr, $n, $i) {
    $largest = $i;
    $l = 2 * $i + 1;
    $r = 2 * $i + 2;
    if ($l < $n && $arr[$l] > $arr[$largest]) {
        $largest = $l;
    }
    if ($r < $n && $arr[$r] > $arr[$largest]) {
        $largest = $r;
    }
    if ($largest != $i) {
        $temp = $arr[$i];
        $arr[$i] = $arr[$largest];
        $arr[$largest] = $temp;
        heapify($arr, $n, $largest);
    }
}

/* --- Bucle principal para aceptar conexiones --- */
while (true) {
    // Aceptar conexión de un cliente
    $clientSocket = socket_accept($serverSocket);
    if ($clientSocket === false) {
        echo "Error en socket_accept: " . socket_strerror(socket_last_error($serverSocket)) . "\n";
        continue;
    }
    socket_getpeername($clientSocket, $clientAddr, $clientPort);
    echo "Cliente conectado desde {$clientAddr}:{$clientPort}\n";
    
    // Crear proceso hijo para atender al cliente
    $pid = pcntl_fork();
    if ($pid == -1) {
        echo "Error al crear proceso para el cliente\n";
        socket_close($clientSocket);
        continue;
    }
    
    if ($pid == 0) {
        // Proceso hijo: cerrar el socket principal y atender al cliente
        socket_close($serverSocket);
        $data = "";
        while (true) {
            $buf = socket_read($clientSocket, 2048, PHP_BINARY_READ);
            if ($buf === false) {
                echo "Error al leer datos del cliente: " . socket_strerror(socket_last_error($clientSocket)) . "\n";
                break;
            }
            if ($buf === "") { // EOF
                break;
            }
            $data .= $buf;
        }
        
        // Procesar la cadena recibida (se espera: algoritmo,tamaño1,tamaño2,...)
        $data = trim($data);
        $parts = explode(",", $data);
        if (count($parts) < 2) {
            $errorMsg = "Formato incorrecto. Se esperaba: algoritmo,tamaño1,tamaño2,...";
            socket_write($clientSocket, $errorMsg);
        } else {
            $algorithm = strtolower(trim($parts[0]));
            $results = [];
            // Para cada tamaño recibido, generar un arreglo aleatorio y medir el tiempo de ordenamiento
            for ($i = 1; $i < count($parts); $i++) {
                $size = (int) trim($parts[$i]);
                if ($size <= 0) continue;
                $array = [];
                for ($j = 0; $j < $size; $j++) {
                    $array[] = rand(1, 10000);
                }
                $start = microtime(true);
                switch ($algorithm) {
                    case "bubble":
                        $sorted = bubbleSort($array);
                        break;
                    case "counting":
                        $sorted = countingSort($array);
                        break;
                    case "heap":
                    case "heapsort":
                        $sorted = heapSort($array);
                        break;
                    case "insertion":
                        $sorted = insertionSort($array);
                        break;
                    case "merge":
                        $sorted = mergeSort($array);
                        break;
                    case "quick":
                        $sorted = quickSort($array);
                        break;
                    case "selection":
                        $sorted = selectionSort($array);
                        break;
                    default:
                        // Si el algoritmo no se reconoce, usar el sort interno de PHP
                        sort($array);
                        $sorted = $array;
                        $algorithm = "default (PHP sort)";
                        break;
                }
                $end = microtime(true);
                $timeElapsed = $end - $start;
                $results[] = "{$size} -> " . number_format($timeElapsed, 6, '.', '');
            }
            $resultString = "Algoritmo: {$algorithm}. Tiempos: " . implode(", ", $results);
            socket_write($clientSocket, $resultString);
        }
        socket_close($clientSocket);
        exit(0);
    } else {
        // Proceso padre: cierra el socket del cliente y sigue aceptando conexiones
        socket_close($clientSocket);
    }
}
?>
