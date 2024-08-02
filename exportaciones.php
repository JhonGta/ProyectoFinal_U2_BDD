<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Exportaciones</title>
    <link rel="stylesheet" href="css/esti.css">
    <script>
    function actualizarPrecioTotal() {
        var produccionSelect = document.getElementById("produccion_id");
        var produccion_id = produccionSelect.value;
        if (produccion_id !== "") {
            var precio_flor = parseFloat(produccionSelect.options[produccionSelect.selectedIndex].getAttribute('data-precio'));
            var cantidad_produccion = parseFloat(produccionSelect.options[produccionSelect.selectedIndex].getAttribute('data-cantidad'));
            var precio_total = precio_flor * cantidad_produccion;
            document.getElementById("precio_total").value = precio_total.toFixed(2);
        } else {
            document.getElementById("precio_total").value = "";
        }
    }
    
    </script>
</head>
<body>
    <h2>Exportaciones</h2>
    <header class="header">
        <div class="logo">
            <img src="img/logo.png" alt="Florería Elegante">
        </div>
        <nav class="nav">
        <ul>
            <li><a href="inicio1.html">Inicio</a></li>
            <li><a href="flores.php">Flores</a></li>
            <li><a href="cosechas.php">Cosechas</a></li>
            <li><a href="produccion.php">Producción</a></li>
            <li><a href="exportaciones.php">Exportaciones</a></li>
            <li><a href="empleados.php">Empleados</a></li>
            <li><a href="facturacion.php">Facturación</a></li>
            <li><a href="logout.php">Salir</a></li>
        </ul>
    </nav>

    </header>

    <?php
session_start();

// Verifica si el usuario está autenticado
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

// Verifica el rol del usuario
$role = isset($_SESSION['role']) ? $_SESSION['role'] : null;
if ($role != 'usuario_admin' && $role != 'usuario_ventas') {
    echo "<p>Acceso denegado.</p>";
    exit;
}

// Configuración de la conexión a la base de datos
$host = '192.168.100.161';
$db = 'flores';
$user = ($role == 'usuario_admin') ? 'usuario_admin' : 'usuario_ventas';
$password = ($role == 'usuario_admin') ? 'admin_1234' : 'ventas_1234';
$dsn = "pgsql:host=$host;port=5432;dbname=$db;user=$user;password=$password";

    try {
        $pdo = new PDO($dsn);

        if ($pdo) {
            echo "<p>Conexión exitosa a la base de datos $db!</p>";

            if ($_SERVER["REQUEST_METHOD"] == "POST") {
                if (isset($_POST['update_id'])) {
                    // Manejar la actualización
                    $update_id = $_POST['update_id'];
                    $produccion_id = $_POST['produccion_id'];
                    $fecha = $_POST['fecha'];
                    $destino_id = $_POST['destino_id'];
                    $precio_total = $_POST['precio_total'];

                    $sql_update = "UPDATE exportaciones SET produccion_id = :produccion_id, fecha = :fecha, destino_id = :destino_id, precio_total = :precio_total WHERE id = :id";
                    $stmt_update = $pdo->prepare($sql_update);
                    $stmt_update->execute([':produccion_id' => $produccion_id, ':fecha' => $fecha, ':destino_id' => $destino_id, ':precio_total' => $precio_total, ':id' => $update_id]);

                    echo "<p>Exportación actualizada con éxito!</p>";
                } else {
                    // Manejar la inserción
                    $produccion_id = $_POST['produccion_id'];
                    $fecha = $_POST['fecha'];
                    $destino_id = $_POST['destino_id'];
                    $precio_total = $_POST['precio_total'];

                    // Validar que precio_total sea numérico y no esté vacío
                    if (!empty($precio_total) && is_numeric($precio_total)) {
                        // Insertar en la tabla exportaciones
                        $sql_insert = "INSERT INTO exportaciones (produccion_id, fecha, destino_id, precio_total) VALUES (:produccion_id, :fecha, :destino_id, :precio_total)";
                        $stmt_insert = $pdo->prepare($sql_insert);
                        $stmt_insert->execute([':produccion_id' => $produccion_id, ':fecha' => $fecha, ':destino_id' => $destino_id, ':precio_total' => $precio_total]);

                        // Actualizar el estado de la producción a "Exportado"
                        $sql_update = "UPDATE produccion SET estado = 'Exportado' WHERE id = :produccion_id";
                        $stmt_update = $pdo->prepare($sql_update);
                        $stmt_update->execute([':produccion_id' => $produccion_id]);

                        echo "<p>Datos insertados con éxito!</p>";
                    } else {
                        echo "<p>Error: Precio total no válido.</p>";
                    }
                }
            }

            // Manejo de edición
            if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_id'])) {
                $edit_id = $_POST['edit_id'];
                $sql = "SELECT * FROM exportaciones WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':id' => $edit_id]);
                $exportacion = $stmt->fetch(PDO::FETCH_ASSOC);
            }

            if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_id'])) {
                $update_id = $_POST['update_id'];
                $produccion_id = $_POST['produccion_id'];
                $fecha = $_POST['fecha'];
                $destino_id = $_POST['destino_id'];
                $precio_total = $_POST['precio_total'];

                $sql = "UPDATE exportaciones SET produccion_id = :produccion_id, fecha = :fecha, destino_id = :destino_id, precio_total = :precio_total WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':produccion_id' => $produccion_id, ':fecha' => $fecha, ':destino_id' => $destino_id, ':precio_total' => $precio_total, ':id' => $update_id]);

                echo "<p>Exportación actualizada con éxito!</p>";
                header("Location: exportaciones.php");
                exit();
            }

            echo "<h2>" . (isset($exportacion) ? "Editar Exportación" : "Insertar Nueva Exportación") . "</h2>";
            echo "<form method=\"post\" action=\"\">";

            if (isset($exportacion)) {
                echo "<input type=\"hidden\" name=\"update_id\" value=\"" . htmlspecialchars($exportacion['id']) . "\">";
            }

            echo "<label for=\"produccion_id\">Producción:</label>";
            echo "<select id=\"produccion_id\" name=\"produccion_id\" onchange=\"actualizarPrecioTotal()\" required>";
            try {
                $stmt = $pdo->query("SELECT produccion.id, flores.nombre AS flor, produccion.cantidad, flores.precio_unitario
                                     FROM produccion
                                     JOIN cosechas ON produccion.cosecha_id = cosechas.id
                                     JOIN flores ON cosechas.flor_id = flores.id
                                     WHERE produccion.estado = 'Para exportación'");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $selected = isset($exportacion) && $row['id'] == $exportacion['produccion_id'] ? 'selected' : '';
                    echo "<option value=\"" . htmlspecialchars($row['id']) . "\" data-precio=\"" . htmlspecialchars($row['precio_unitario']) . "\" data-cantidad=\"" . htmlspecialchars($row['cantidad']) . "\" $selected>" . htmlspecialchars($row['flor']) . " - " . htmlspecialchars($row['cantidad']) . " unidades</option>";
                }
            } catch (PDOException $e) {
                echo "<option>Error: " . $e->getMessage() . "</option>";
            }
            echo "</select>";
            echo "<br>";

            echo "<label for=\"fecha\">Fecha:</label>";
            $fecha_value = isset($exportacion) ? htmlspecialchars($exportacion['fecha']) : '';
            echo "<input type=\"date\" id=\"fecha\" name=\"fecha\" value=\"$fecha_value\" required>";
            echo "<br>";

            // Obtener datos de la tabla pais para llenar el campo destino
// Obtener datos de la tabla pais para llenar el campo destino
echo "<label for=\"destino_id\">Destino:</label>";
echo "<select id=\"destino_id\" name=\"destino_id\" required>";
try {
    $stmt = $pdo->query("SELECT p.id, pi.nombre FROM pais p JOIN pais_info pi ON p.id = pi.pais_id");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $selected = isset($exportacion) && $row['id'] == $exportacion['destino_id'] ? 'selected' : '';
        echo "<option value=\"" . htmlspecialchars($row['id']) . "\" $selected>" . htmlspecialchars($row['nombre']) . "</option>";
    }
} catch (PDOException $e) {
    echo "<option>Error: " . $e->getMessage() . "</option>";
}
echo "</select>";
echo "<br>";


            $precio_total_value = isset($exportacion) ? htmlspecialchars($exportacion['precio_total']) : '';
            echo "<label for=\"precio_total\">Precio Total:</label>";
            echo "<input type=\"text\" id=\"precio_total\" name=\"precio_total\" value=\"$precio_total_value\" readonly>";
            echo "<br>";

            echo "<input type=\"submit\" value=\"" . (isset($exportacion) ? "Actualizar" : "Insertar") . "\">";
            echo "</form>";

            // Mostrar la tabla de exportaciones
            $stmt = $pdo->query("SELECT ex.id, ex.fecha, pi.nombre AS pais_destino, pr.cantidad, fl.nombre AS flor, ex.precio_total
            FROM exportaciones ex
            JOIN produccion pr ON ex.produccion_id = pr.id
            JOIN cosechas co ON pr.cosecha_id = co.id
            JOIN flores fl ON co.flor_id = fl.id
            JOIN pais p ON ex.destino_id = p.id
            JOIN pais_info pi ON p.id = pi.pais_id");

echo "<h2>Exportaciones Registradas</h2>";
echo "<table>";
echo "<tr><th>ID</th><th>Fecha</th><th>Destino</th><th>Cantidad</th><th>Flor</th><th>Precio Total</th><th>Acciones</th></tr>";

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
echo "<tr>";
echo "<td>" . htmlspecialchars($row['id']) . "</td>";
echo "<td>" . htmlspecialchars($row['fecha']) . "</td>";
echo "<td>" . htmlspecialchars($row['pais_destino']) . "</td>";
echo "<td>" . htmlspecialchars($row['cantidad']) . "</td>";
echo "<td>" . htmlspecialchars($row['flor']) . "</td>";
echo "<td>" . htmlspecialchars($row['precio_total']) . "</td>";
echo "<td>";
echo "<form method=\"post\" action=\"\" style=\"display:inline-block;\">";
echo "<input type=\"hidden\" name=\"edit_id\" value=\"" . htmlspecialchars($row['id']) . "\">";
echo "<input type=\"submit\" value=\"Editar\">";
echo "</form>";
echo "</td>";
echo "</tr>";
}

echo "</table>";

        }
    } catch (PDOException $e) {
        echo "<p>Error: " . $e->getMessage() . "</p>";
    }
    ?>

</body>
</html>
