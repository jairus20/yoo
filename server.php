<?php
/**
 * tcp_server.php
 *
 * Servidor TCP que recibe una línea de texto con el formato:
 *   "algoritmo número1 número2 ... númeroN\n"
 *
 * Ejemplo de entrada:
 *   "quick 34 7 23 32 5 62 3 15\n"
 *
 * El servidor separa el algoritmo del arreglo, ejecuta el ordenamiento usando el algoritmo
 * indicado y responde con una línea que contiene el arreglo ordenado.
 *
 * Algoritmos soportados:
 *   bubble, counting, heapsort, insertion, merge, quick, selection.
 */

set_time_limit(0);
error_reporting(E_ALL);

$host = '0.0.0.0';
$port = 12345; // Puerto para conexiones TCP

// Crear el socket TCP
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

// Bucle principal para aceptar clientes
while (true) {
    $client = socket_accept($sock);
    if ($client === false) {
        continue;
    }

    echo "Nuevo cliente conectado.\n";

    $buffer = "";
    // Acumula datos hasta encontrar un salto de línea ("\n")
    while (strpos($buffer, "\n") === false) {
        $part = socket_read($client, 2048, PHP_NORMAL_READ);
        if ($part === false || $part === "") {
            break;
        }
        $buffer .= $part;
    }
    $data = trim($buffer);
    if ($data === "") {
        socket_close($client);
        continue;
    }
    
    echo "Datos recibidos: $data\n";
    
    // Separar el algoritmo del resto de la línea
    $parts = explode(" ", $data);
    $algorithm = strtolower(array_shift($parts));
    $array = array_map('intval', $parts);
    
    if (empty($array)) {
        $errorMsg = "Error: Se recibió un arreglo vacío.\n";
        socket_write($client, $errorMsg, strlen($errorMsg));
        socket_close($client);
        continue;
    }
    
    // Ejecutar el algoritmo de ordenamiento sin medir tiempo
    switch ($algorithm) {
        case 'bubble':
            $sortedArray = bubbleSort($array);
            break;
        case 'counting':
            $sortedArray = countingSort($array);
            break;
        case 'heapsort':
            $sortedArray = heapSort($array);
            break;
        case 'insertion':
            $sortedArray = insertionSort($array);
            break;
        case 'merge':
            $sortedArray = mergeSort($array);
            break;
        case 'quick':
            $sortedArray = quickSort($array);
            break;
        case 'selection':
            $sortedArray = selectionSort($array);
            break;
        default:
            $errorMsg = "Error: Algoritmo desconocido. Usa: bubble, counting, heapsort, insertion, merge, quick, selection.\n";
            socket_write($client, $errorMsg, strlen($errorMsg));
            socket_close($client);
            continue 2;
    }
    
    // Preparar la respuesta: la línea con los números ordenados
    $sortedString = implode(" ", $sortedArray) . "\n";
    socket_write($client, $sortedString, strlen($sortedString));
    
    echo "Respuesta enviada: $sortedString\n";
    
    socket_close($client);
}

socket_close($sock);

/* ============================
   IMPLEMENTACIÓN DE ALGORITMOS
   ============================
*/

// 1. Bubble Sort
function bubbleSort($arr) {
    $n = count($arr);
    for ($i = 0; $i < $n - 1; $i++) {
        for ($j = 0; $j < $n - $i - 1; $j++) {
            if ($arr[$j] > $arr[$j + 1]) {
                $temp = $arr[$j];
                $arr[$j] = $arr[$j + 1];
                $arr[$j + 1] = $temp;
            }
        }
    }
    return $arr;
}

// 2. Counting Sort
function countingSort($arr) {
    $max = max($arr);
    $min = min($arr);
    $count = array_fill(0, $max - $min + 1, 0);
    foreach ($arr as $num) {
        $count[$num - $min]++;
    }
    $index = 0;
    foreach ($count as $i => $val) {
        while ($val-- > 0) {
            $arr[$index++] = $i + $min;
        }
    }
    return $arr;
}

// 3. HeapSort
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
    $left = 2 * $i + 1;
    $right = 2 * $i + 2;
    if ($left < $n && $arr[$left] > $arr[$largest]) {
        $largest = $left;
    }
    if ($right < $n && $arr[$right] > $arr[$largest]) {
        $largest = $right;
    }
    if ($largest != $i) {
        $temp = $arr[$i];
        $arr[$i] = $arr[$largest];
        $arr[$largest] = $temp;
        heapify($arr, $n, $largest);
    }
}

// 4. Insertion Sort
function insertionSort($arr) {
    $n = count($arr);
    for ($i = 1; $i < $n; $i++) {
        $key = $arr[$i];
        $j = $i - 1;
        while ($j >= 0 && $arr[$j] > $key) {
            $arr[$j + 1] = $arr[$j];
            $j--;
        }
        $arr[$j + 1] = $key;
    }
    return $arr;
}

// 5. Merge Sort
function mergeSort($arr) {
    if (count($arr) <= 1) return $arr;
    $mid = intval(count($arr) / 2);
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

// 6. QuickSort
function quickSort($arr) {
    if (count($arr) <= 1) return $arr;
    $pivot = array_shift($arr);
    $left = [];
    $right = [];
    foreach ($arr as $value) {
        if ($value < $pivot) {
            $left[] = $value;
        } else {
            $right[] = $value;
        }
    }
    return array_merge(quickSort($left), [$pivot], quickSort($right));
}

// 7. Selection Sort
function selectionSort($arr) {
    $n = count($arr);
    for ($i = 0; $i < $n - 1; $i++) {
        $minIndex = $i;
        for ($j = $i + 1; $j < $n; $j++) {
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
?>

