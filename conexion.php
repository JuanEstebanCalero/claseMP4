<?php
$host = "localhost";
$dbname = "mi_sitio";
$username = "root"; // Usuario por defecto en WAMP
$password = "";     // Contraseña por defecto en WAMP (vacía)

try {
    // Creamos la conexión usando el objeto PDO
    $conexion = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    
    // Configuramos PDO para que lance excepciones en caso de errores
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Si hay un error de conexión, detiene el script y lo muestra
    die("Error crítico de conexión: " . $e->getMessage());
}
?>
