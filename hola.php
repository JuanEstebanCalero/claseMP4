<?php
require_once 'conexion.php'; // Trae la conexión aquí

$seccion_actual = isset($_GET['seccion']) ? $_GET['seccion'] : 'inicio';

// Variable para mostrar mensajes de éxito o error
$mensaje_db = "";

// Colores de acento por marca, para que las tarjetas no se vean todas iguales
$acento_marca = [
    'Hydroflask' => '#0284C7',
    'Stanley'    => '#FF6B5B',
    'Owala'      => '#38BDF8',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hydr8 | Botellas y termos premium</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="icon" href="assets/hydr8_icon.svg" type="image/svg+xml">
</head>
<body>

    <header>
        <div class="logo">
            <img src="assets/hydr8_logo_light.svg" alt="Hydr8">
        </div>
        <nav>
            <ul>
                <li><a href="hola.php?seccion=inicio">Inicio</a></li>
                <li><a href="hola.php?seccion=catalogo">Catálogo</a></li>
                <li><a href="hola.php?seccion=comprar">Comprar</a></li>
                <li><a href="hola.php?seccion=contacto">Contacto</a></li>
            </ul>
        </nav>

        <svg class="ola-divisor" viewBox="0 0 1440 80" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
            <path fill="#FAF8F5" d="M0,32 C240,80 480,0 720,24 C960,48 1200,88 1440,40 L1440,80 L0,80 Z"></path>
        </svg>
    </header>

    <div id="contenedor-principal">
        <div>
            <?php
            switch ($seccion_actual) {

                // ---------------------------------------------------
                case 'inicio':
                    echo "<h2>Hidrátate con estilo</h2>";
                    echo "<p>Bienvenido a Hydr8, tu tienda de botellas y termos premium: Hydroflask, Stanley y Owala. Explora el catálogo y encuentra tu compañera de hidratación ideal.</p>";
                    break;

                // ---------------------------------------------------
                case 'catalogo':
                    echo "<h2>Nuestro Catálogo</h2>";

                    try {
                        $sql = "SELECT * FROM productos ORDER BY marca, id";
                        $stmt = $conexion->query($sql);
                        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        if (count($productos) > 0) {
                            echo '<div class="grid-productos">';
                            foreach ($productos as $p) {
                                $nombre    = htmlspecialchars($p['nombre']);
                                $marca     = htmlspecialchars($p['marca']);
                                $color     = htmlspecialchars($p['color']);
                                $capacidad = htmlspecialchars($p['capacidad_ml']);
                                $precio    = number_format($p['precio'], 0, ',', '.');
                                $imagen    = htmlspecialchars($p['imagen_url']);
                                $borde     = isset($acento_marca[$p['marca']]) ? $acento_marca[$p['marca']] : '#0284C7';

                                echo '<div class="tarjeta-producto" style="border-top: 4px solid ' . $borde . ';">';

                                if (!empty($imagen)) {
                                    echo '<img class="imagen-producto" src="' . $imagen . '" alt="' . $nombre . '" onerror="this.onerror=null;this.src=\'assets/productos/placeholder.jpg\';">';
                                } else {
                                    echo '<div class="imagen-placeholder">Imagen próximamente</div>';
                                }

                                echo '  <div class="info">
                                            <div class="marca">' . $marca . '</div>
                                            <h3>' . $nombre . '</h3>
                                            <p class="detalle">' . $capacidad . ' ml &middot; ' . $color . '</p>
                                            <div class="precio">$' . $precio . '</div>
                                            <a class="btn-comprar" href="hola.php?seccion=comprar&producto_id=' . $p['id'] . '">Comprar</a>
                                        </div>
                                      </div>';
                            }
                            echo '</div>';
                        } else {
                            echo "<p>Aún no hay productos cargados en el catálogo.</p>";
                        }
                    } catch (PDOException $e) {
                        echo "<p style='color:red;'>Error al cargar productos: " . $e->getMessage() . "</p>";
                    }
                    break;

                // ---------------------------------------------------
                case 'comprar':
                    echo "<h2>Finalizar Compra</h2>";

                    // Traemos todos los productos para el selector
                    $productos = [];
                    try {
                        $stmt = $conexion->query("SELECT id, nombre, marca, precio FROM productos ORDER BY marca, id");
                        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    } catch (PDOException $e) {
                        echo "<p style='color:red;'>Error al cargar productos: " . $e->getMessage() . "</p>";
                    }

                    $producto_preseleccionado = isset($_GET['producto_id']) ? (int)$_GET['producto_id'] : (isset($_POST['producto_id']) ? (int)$_POST['producto_id'] : 0);
                    $mensaje_compra = "";
                    $compra_exitosa = false;

                    // Procesamos el formulario cuando el usuario da clic en "Pagar"
                    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['numero_tarjeta'])) {
                        $producto_id     = (int)$_POST['producto_id'];
                        $nombre_titular  = trim($_POST['nombre_titular']);
                        // Quitamos espacios que el usuario pudo haber escrito entre los números
                        $numero_tarjeta  = preg_replace('/\D/', '', $_POST['numero_tarjeta']);
                        $mes_exp         = (int)$_POST['mes_exp'];
                        $anio_exp        = (int)$_POST['anio_exp'];

                        $errores = [];

                        if (empty($nombre_titular)) {
                            $errores[] = "El nombre del titular es obligatorio.";
                        }
                        if (strlen($numero_tarjeta) !== 16) {
                            $errores[] = "El número de tarjeta debe tener exactamente 16 dígitos.";
                        }
                        if ($producto_id <= 0) {
                            $errores[] = "Debes seleccionar un producto.";
                        }
                        // Verificamos que la fecha de expiración no esté vencida
                        $anio_actual = (int)date("Y");
                        $mes_actual  = (int)date("n");
                        if ($anio_exp < $anio_actual || ($anio_exp == $anio_actual && $mes_exp < $mes_actual)) {
                            $errores[] = "La tarjeta está vencida.";
                        }

                        if (empty($errores)) {
                            try {
                                // Buscamos el precio real del producto en la BD (nunca confiamos en el precio del formulario)
                                $stmt = $conexion->prepare("SELECT precio FROM productos WHERE id = :id");
                                $stmt->execute([':id' => $producto_id]);
                                $producto_comprado = $stmt->fetch(PDO::FETCH_ASSOC);

                                if ($producto_comprado) {
                                    $ultimos4 = substr($numero_tarjeta, -4);

                                    $sql = "INSERT INTO pedidos (producto_id, nombre_titular, tarjeta_ultimos4, mes_exp, anio_exp, monto)
                                            VALUES (:producto_id, :nombre_titular, :ultimos4, :mes_exp, :anio_exp, :monto)";
                                    $stmt = $conexion->prepare($sql);
                                    $stmt->execute([
                                        ':producto_id'    => $producto_id,
                                        ':nombre_titular' => $nombre_titular,
                                        ':ultimos4'       => $ultimos4,
                                        ':mes_exp'        => $mes_exp,
                                        ':anio_exp'       => $anio_exp,
                                        ':monto'          => $producto_comprado['precio'],
                                    ]);

                                    $compra_exitosa = true;
                                    $mensaje_compra = "<p style='color: green; font-weight: bold;'>¡Compra simulada exitosa! Gracias, " . htmlspecialchars($nombre_titular) . ". (Esta es una pasarela ficticia, no se realizó ningún cobro real).</p>";
                                } else {
                                    $mensaje_compra = "<p style='color: red;'>El producto seleccionado no existe.</p>";
                                }
                            } catch (PDOException $e) {
                                $mensaje_compra = "<p style='color: red;'>Error al procesar la compra: " . $e->getMessage() . "</p>";
                            }
                        } else {
                            $mensaje_compra = "<ul style='color: red;'><li>" . implode("</li><li>", $errores) . "</li></ul>";
                        }
                    }

                    echo $mensaje_compra;

                    // Si la compra fue exitosa no volvemos a mostrar el formulario
                    if (!$compra_exitosa) {
                        echo '<form action="hola.php?seccion=comprar" method="POST" class="form-pago">';

                        echo '<div>
                                <label for="producto_id">Producto</label><br>
                                <select id="producto_id" name="producto_id" required>
                                    <option value="">-- Selecciona un producto --</option>';
                        foreach ($productos as $p) {
                            $sel = ($producto_preseleccionado == $p['id']) ? 'selected' : '';
                            $precio = number_format($p['precio'], 0, ',', '.');
                            echo '<option value="' . $p['id'] . '" ' . $sel . '>' . htmlspecialchars($p['marca'] . ' - ' . $p['nombre']) . ' ($' . $precio . ')</option>';
                        }
                        echo '  </select>
                              </div>';

                        echo '  <div>
                                    <label for="nombre_titular">Nombre del titular</label><br>
                                    <input type="text" id="nombre_titular" name="nombre_titular" placeholder="Como aparece en la tarjeta" required>
                                </div>

                                <div>
                                    <label for="numero_tarjeta">Número de tarjeta (16 dígitos)</label><br>
                                    <input type="text" id="numero_tarjeta" name="numero_tarjeta" placeholder="0000 0000 0000 0000" inputmode="numeric" maxlength="19" pattern="[0-9 ]{16,19}" required>
                                </div>

                                <div class="fila-doble">
                                    <div>
                                        <label for="mes_exp">Mes de expiración</label><br>
                                        <select id="mes_exp" name="mes_exp" required>';
                        for ($m = 1; $m <= 12; $m++) {
                            echo '<option value="' . $m . '">' . str_pad($m, 2, '0', STR_PAD_LEFT) . '</option>';
                        }
                        echo '          </select>
                                    </div>
                                    <div>
                                        <label for="anio_exp">Año de expiración</label><br>
                                        <select id="anio_exp" name="anio_exp" required>';
                        $anio_actual = (int)date("Y");
                        for ($a = $anio_actual; $a <= $anio_actual + 10; $a++) {
                            echo '<option value="' . $a . '">' . $a . '</option>';
                        }
                        echo '          </select>
                                    </div>
                                </div>

                                <button type="submit">Pagar</button>
                              </form>';

                        echo '<p class="nota-ficticia">Esta es una pasarela de pago ficticia creada solo para fines del proyecto. No ingreses datos reales de tarjetas.</p>';
                    }
                    break;

                // ---------------------------------------------------
                case 'contacto':
                    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['nombre'])) {
                        $nombre_contacto = trim($_POST['nombre']);
                        $correo_contacto = trim($_POST['correo']);
                        $mensaje_texto   = trim($_POST['mensaje']);

                        if (!empty($nombre_contacto) && !empty($correo_contacto) && !empty($mensaje_texto)) {
                            if (filter_var($correo_contacto, FILTER_VALIDATE_EMAIL)) {
                                try {
                                    $sql = "INSERT INTO contactos (nombre, correo, mensaje) VALUES (:nombre, :correo, :mensaje)";
                                    $stmt = $conexion->prepare($sql);
                                    $stmt->execute([
                                        ':nombre'  => $nombre_contacto,
                                        ':correo'  => $correo_contacto,
                                        ':mensaje' => $mensaje_texto
                                    ]);
                                    $mensaje_db = "<p style='color: green; font-weight: bold;'>¡Tu mensaje ha sido enviado con éxito!</p>";
                                } catch (PDOException $e) {
                                    $mensaje_db = "<p style='color: red;'>Error en la base de datos: " . $e->getMessage() . "</p>";
                                }
                            } else {
                                $mensaje_db = "<p style='color: red;'>Por favor, introduce un correo electrónico válido.</p>";
                            }
                        } else {
                            $mensaje_db = "<p style='color: red;'>Todos los campos son obligatorios.</p>";
                        }
                    }

                    echo "<h2>Contáctanos</h2>";
                    echo $mensaje_db;

                    echo '<form action="hola.php?seccion=contacto" method="POST" class="form-pago" style="max-width: 400px;">
                            <div>
                                <label for="nombre">Tu Nombre:</label><br>
                                <input type="text" id="nombre" name="nombre" required>
                            </div>
                            <div>
                                <label for="correo">Correo Electrónico:</label><br>
                                <input type="email" id="correo" name="correo" required>
                            </div>
                            <div>
                                <label for="mensaje">Tu Mensaje:</label><br>
                                <textarea id="mensaje" name="mensaje" rows="4" required></textarea>
                            </div>
                            <button type="submit">Enviar Mensaje</button>
                          </form>';
                    break;

                // ---------------------------------------------------
                default:
                    echo "<h2>Error 404</h2>";
                    echo "<p>La sección solicitada no existe.</p>";
                    break;
            }
            ?>
        </div>
    </div>

    <footer>
        <p>&copy; <?php echo date("Y"); ?> Hydr8 - Todos los derechos reservados.</p>
    </footer>

</body>
</html>