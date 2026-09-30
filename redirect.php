<?php
// Recibimos el parámetro de la URL de forma segura
$nombre = isset($_GET['nombre']) ? htmlspecialchars($_GET['nombre']) : "Invitado";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Bienvenido</title>
</head>
<body>

    <h1>¡Bienvenido, <?php echo $nombre; ?>!</h1>
    <p>Has sido redirigido con éxito a tu panel de inicio.</p>
    
    <a href="hola.php">Volver al formulario</a>

</body>
</html>