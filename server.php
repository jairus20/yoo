<?php
// Configuración del servidor
$address = '0.0.0.0'; // Escuchar en todas las interfaces
$port = 2020;
$server = stream_socket_server("tcp://$address:$port", $errno, $errstr);
if (!$server) {
    die("Error al iniciar el servidor: $errstr ($errno)\n");
}

echo "Servidor iniciado en $address:$port\n";

// Bucle principal para aceptar conexiones
while ($client = @stream_socket_accept($server)) {
    // Leer la solicitud del cliente (se asume que la cadena completa cabe en 9000 bytes)
    $data = fread($client, 9000);
    if ($data === false || trim($data) === "") {
        fclose($client);
        continue;
    }
    
    // Se espera un formato: "algoritmo,num1,num2,..."
    $parts = explode(',', trim($data));
    $algorithm = strtolower(array_shift($parts));
    $numbers = array_map('intval', $parts);
    
    // Seleccionar y ejecutar el algoritmo de ordenamiento solicitado
    switch ($algorithm) {
        case 'bubble':
            $sorted = bubbleSort($numbers);
            break;
        case 'counting':
            $sorted = countingSort($numbers);
            break;
        case 'heapsort':
            $sorted = heapSort($numbers);
            break;
        case 'insertion':
            $sorted = insertionSort($numbers);
            break;
        case 'merge':
            $sorted = mergeSort($numbers);
            break;
        case 'quick':
            $sorted = quickSort($numbers);
            break;
        case 'selection':
            $sorted = selectionSort($numbers);
            break;
        default:
            $sorted = $numbers;
            break;
    }
    
    // Preparar la respuesta: arreglo ordenado como cadena separada por comas
    $response = implode(',', $sorted);
    fwrite($client, $response);
    fclose($client);
}

// --- Funciones de ordenamiento ---

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

function countingSort($arr) {
    if(empty($arr)) return $arr;
    $min = min($arr);
    $max = max($arr);
    $range = $max - $min + 1;
    $count = array_fill(0, $range, 0);
    foreach ($arr as $num) {
        $count[$num - $min]++;
    }
    $result = [];
    for ($i = 0; $i < $range; $i++) {
        while ($count[$i]-- > 0) {
            $result[] = $i + $min;
        }
    }
    return $result;
}

function heapSort($arr) {
    $n = count($arr);
    // Construir el heap (reorganizar el arreglo)
    for ($i = intval($n / 2) - 1; $i >= 0; $i--) {
        heapify($arr, $n, $i);
    }
    // Extraer elementos uno a uno
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
    if ($l < $n && $arr[$l] > $arr[$largest])
        $largest = $l;
    if ($r < $n && $arr[$r] > $arr[$largest])
        $largest = $r;
    if ($largest != $i) {
        $temp = $arr[$i];
        $arr[$i] = $arr[$largest];
        $arr[$largest] = $temp;
        heapify($arr, $n, $largest);
    }
}

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

function mergeSort($arr) {
    if(count($arr) < 2) return $arr;
    $middle = intval(count($arr) / 2);
    $left = mergeSort(array_slice($arr, 0, $middle));
    $right = mergeSort(array_slice($arr, $middle));
    return merge($left, $right);
}
function merge($left, $right) {
    $result = [];
    while(count($left) && count($right)) {
        if($left[0] < $right[0]) {
            $result[] = array_shift($left);
        } else {
            $result[] = array_shift($right);
        }
    }
    return array_merge($result, $left, $right);
}

function quickSort($arr) {
    if(count($arr) < 2) return $arr;
    $pivot = $arr[0];
    $left = $right = [];
    for($i = 1; $i < count($arr); $i++){
        if($arr[$i] < $pivot) {
            $left[] = $arr[$i];
        } else {
            $right[] = $arr[$i];
        }
    }
    return array_merge(quickSort($left), [$pivot], quickSort($right));
}

function selectionSort($arr) {
    $n = count($arr);
    for ($i = 0; $i < $n - 1; $i++) {
        $min_index = $i;
        for ($j = $i + 1; $j < $n; $j++) {
            if ($arr[$j] < $arr[$min_index]) {
                $min_index = $j;
            }
        }
        $temp = $arr[$i];
        $arr[$i] = $arr[$min_index];
        $arr[$min_index] = $temp;
    }
    return $arr;
}
?>
