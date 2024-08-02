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

    // Procesar el formulario
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $pdo->beginTransaction(); // Iniciar la transacción

        try {
            $exportacion_id = $_POST['exportacion_id'];
            $fecha = $_POST['fecha'];
            $cliente_nombre = $_POST['cliente_nombre'];
            $cliente_cedula_ruc = $_POST['cliente_cedula_ruc'];
            $cliente_direccion = $_POST['cliente_direccion'];
            $cliente_correo = $_POST['cliente_correo'];
            $seguro = $_POST['seguro'];
            $costo_exportacion = $_POST['costo_exportacion'];
            $metodo_pago_id = $_POST['metodo_pago'];
            $iva = $_POST['iva'];
            $total = $_POST['total'];
            $subtotal = $_POST['subtotal'];

            // Insertar cliente
            $sql_cliente = "INSERT INTO clientes (nombre, cedula_ruc, direccion, correo_electronico) VALUES (:nombre, :cedula_ruc, :direccion, :correo_electronico) RETURNING id";
            $stmt_cliente = $pdo->prepare($sql_cliente);
            $stmt_cliente->execute([
                ':nombre' => $cliente_nombre,
                ':cedula_ruc' => $cliente_cedula_ruc,
                ':direccion' => $cliente_direccion,
                ':correo_electronico' => $cliente_correo
            ]);
            $cliente_id = $stmt_cliente->fetchColumn();

            // Insertar en la tabla facturacion
            $sql_facturacion = "INSERT INTO facturacion (exportacion_id, fecha, cliente_id, seguro, costo_exportacion, iva, total, monto, metodo_pago_id)
                                VALUES (:exportacion_id, :fecha, :cliente_id, :seguro, :costo_exportacion, :iva, :total, :monto, :metodo_pago_id)";
            $stmt_facturacion = $pdo->prepare($sql_facturacion);
            $stmt_facturacion->execute([
                ':exportacion_id' => $exportacion_id,
                ':fecha' => $fecha,
                ':cliente_id' => $cliente_id,
                ':seguro' => $seguro,
                ':costo_exportacion' => $costo_exportacion,
                ':iva' => $iva,
                ':total' => $total,
                ':monto' => $subtotal,
                ':metodo_pago_id' => $metodo_pago_id
            ]);

            $pdo->commit(); // Confirmar la transacción
            echo "<p>Datos insertados con éxito en la tabla facturacion!</p>";
        } catch (Exception $e) {
            $pdo->rollback(); // Revertir la transacción
            echo "<p>Error: " . $e->getMessage() . "</p>";
        }
    }

    // Consulta SQL para obtener los datos de facturación
    $stmt = $pdo->query("SELECT facturacion.id, facturacion.fecha, exportaciones.id AS exportacion_id, produccion.cantidad, flores.precio_unitario,
                        (produccion.cantidad * flores.precio_unitario) AS subtotal,
                        facturacion.seguro, facturacion.costo_exportacion, facturacion.iva, facturacion.total, metodo_pago.nombre AS metodo_pago
                        FROM facturacion
                        JOIN exportaciones ON facturacion.exportacion_id = exportaciones.id
                        JOIN produccion ON exportaciones.produccion_id = produccion.id
                        JOIN cosechas ON produccion.cosecha_id = cosechas.id
                        JOIN flores ON cosechas.flor_id = flores.id
                        JOIN metodo_pago ON facturacion.metodo_pago_id = metodo_pago.id");

    // Mostrar resultados en tabla
    echo "<h2>Facturaciones Realizadas</h2>";
    echo "<table>";
    echo "<thead><tr><th>ID</th><th>Fecha</th><th>Exportación</th><th>Cantidad</th><th>Precio Unitario</th><th>Subtotal</th><th>Seguro</th><th>Costo Exportación</th><th>IVA</th><th>Total</th><th>Método de Pago</th></tr></thead>";
    echo "<tbody>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['fecha']) . "</td>";
        echo "<td>" . htmlspecialchars($row['exportacion_id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['cantidad']) . "</td>";
        echo "<td>" . htmlspecialchars($row['precio_unitario']) . "</td>";
        echo "<td>" . htmlspecialchars($row['subtotal']) . "</td>";
        echo "<td>" . htmlspecialchars($row['seguro']) . "</td>";
        echo "<td>" . htmlspecialchars($row['costo_exportacion']) . "</td>";
        echo "<td>" . htmlspecialchars($row['iva']) . "</td>";
        echo "<td>" . htmlspecialchars($row['total']) . "</td>";
        echo "<td>" . htmlspecialchars($row['metodo_pago']) . "</td>";
        echo "</tr>";
    }
    echo "</tbody>";
    echo "</table>";

} catch (PDOException $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facturación</title>
    <link rel="stylesheet" href="css/esti.css">
</head>
<body>
    <header class="header">
        <div class="logo">
            <img src="img/logo.png" alt="Florería Elegante">
        </div>
        <nav class="nav">
        <ul>
            <li><a href="logout.php">Salir</a></li>
            <li><a href="inicio1.html">Inicio</a></li>
            <li><a href="flores.php">Flores</a></li>
            <li><a href="cosechas.php">Cosechas</a></li>
            <li><a href="produccion.php">Producción</a></li>
            <li><a href="exportaciones.php">Exportaciones</a></li>
            <li><a href="empleados.php">Empleados</a></li>
            <li><a href="facturacion.php">Facturación</a></li>
        </ul>
        </nav>
    </header>

    <main>
        <section>
            <h2>Facturación</h2>
            <h2>Insertar Nueva Facturación</h2>
            <form method="post" action="">
                <label for="exportacion_id">Exportación:</label>
                <select id="exportacion_id" name="exportacion_id" required>
                    <?php
                    try {
                        $stmt = $pdo->query("SELECT exportaciones.id, produccion.cantidad, flores.precio_unitario, flores.nombre AS flor
                                            FROM exportaciones
                                            JOIN produccion ON exportaciones.produccion_id = produccion.id
                                            JOIN cosechas ON produccion.cosecha_id = cosechas.id
                                            JOIN flores ON cosechas.flor_id = flores.id");
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            echo "<option value=\"" . htmlspecialchars($row['id']) . "\" data-cantidad=\"" . htmlspecialchars($row['cantidad']) . "\" data-precio-unitario=\"" . htmlspecialchars($row['precio_unitario']) . "\">" . htmlspecialchars($row['flor']) . " - Cantidad: " . htmlspecialchars($row['cantidad']) . "</option>";
                        }
                    } catch (PDOException $e) {
                        echo "<option>Error: " . $e->getMessage() . "</option>";
                    }
                    
                    ?>
                </select>
                <br>
                <label for="fecha">Fecha:</label>
                <input type="date" id="fecha" name="fecha" required>
                <br>
                <label for="cliente_nombre">Cliente Nombre:</label>
                <input type="text" id="cliente_nombre" name="cliente_nombre" required>s
                <br>
                <label for="cliente_cedula_ruc">Cliente Cédula/RUC:</label>
                <input type="text" id="cliente_cedula_ruc" name="cliente_cedula_ruc" required>
                <br>
                <label for="cliente_direccion">Cliente Dirección:</label>
                <input type="text" id="cliente_direccion" name="cliente_direccion" required>
                <br>
                <label for="cliente_correo">Cliente Correo:</label>
                <input type="email" id="cliente_correo" name="cliente_correo" required>
                <br>
                <label for="seguro">Seguro:</label>
                <input type="number" id="seguro" name="seguro" step="0.01" required>
                <br>
                <label for="costo_exportacion">Costo de Exportación:</label>
                <input type="number" id="costo_exportacion" name="costo_exportacion" step="0.01" required>
                <br>
                <label for="metodo_pago">Método de Pago:</label>
                <select id="metodo_pago" name="metodo_pago" required>
                    <?php
                    try {
                        $stmt = $pdo->query("SELECT id, nombre FROM metodo_pago");
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            echo "<option value=\"" . htmlspecialchars($row['id']) . "\">" . htmlspecialchars($row['nombre']) . "</option>";
                        }
                    } catch (PDOException $e) {
                        echo "<option>Error: " . $e->getMessage() . "</option>";
                    }
                    ?>
                </select>
                <br>
                <label for="iva">IVA:</label>
                <input type="number" id="iva" name="iva" step="0.01" required>
                <br>
                <label for="total">Total:</label>
                <input type="number" id="total" name="total" step="0.01" required readonly>
                <br>
                <label for="subtotal">Subtotal:</label>
                <input type="number" id="subtotal" name="subtotal" step="0.01" required readonly>
                <br>
                <input type="submit" value="Insertar Facturación">
            </form>
        </section>
    </main>

    <footer class="footer">
        <p>&copy; 2024 Florería Elegante</p>
    </footer>
</body>
</html>