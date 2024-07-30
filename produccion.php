<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Producción</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h2>Producción</h2>
    <nav>
        <ul>
            <li><a href="index.php">Inicio</a></li>
            <li><a href="flores.php">Flores</a></li>
            <li><a href="cosechas.php">Cosechas</a></li>
            <li><a href="produccion.php">Producción</a></li>
            <li><a href="exportaciones.php">Exportaciones</a></li>
            <li><a href="empleados.php">Empleados</a></li>
            <li><a href="facturacion.php">Facturación</a></li>
        </ul>
    </nav>

    <?php

session_start();

// Verifica si el usuario está autenticado
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

// Verifica el rol del usuario
$role = $_SESSION['role'];
if ($role != 'usuario_admin' && $role != 'usuario_produccion') {
    echo "<p>Acceso denegado.</p>";
    exit;
}

    
    $host = '192.168.100.161';
    $db = 'flores';
    $user = 'Postgres1';
    $password = '1234';

    $dsn = "pgsql:host=$host;port=5432;dbname=$db;user=$user;password=$password";

    try {
        $pdo = new PDO($dsn);

        if ($pdo) {
            echo "<p>Conexión exitosa a la base de datos $db!</p>";

            // Manejo de inserción
            if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cosecha_id']) && !isset($_POST['update_id'])) {
                $cosecha_id = $_POST['cosecha_id'];
                $fecha = $_POST['fecha'];
                $cantidad = $_POST['cantidad'];

                // Obtener la cantidad disponible de la cosecha
                $stmt = $pdo->prepare("SELECT cantidad FROM cosechas WHERE id = :cosecha_id");
                $stmt->execute([':cosecha_id' => $cosecha_id]);
                $cosecha = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$cosecha) {
                    echo "<p>Error: No se encontró la cosecha.</p>";
                } else {
                    $cantidad_disponible = $cosecha['cantidad'];

                    // Validar si la cantidad solicitada es mayor a la cantidad disponible
                    if ($cantidad > $cantidad_disponible) {
                        echo "<p>Error: La cantidad solicitada supera la cantidad disponible en la cosecha ($cantidad_disponible).</p>";
                    } else {
                        // Insertar la producción
                        $sql = "INSERT INTO produccion (cosecha_id, fecha, cantidad, estado) VALUES (:cosecha_id, :fecha, :cantidad, 'Para exportación')";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([':cosecha_id' => $cosecha_id, ':fecha' => $fecha, ':cantidad' => $cantidad]);

                        // Actualizar la cantidad disponible en la cosecha
                        $nuevo_stock = $cantidad_disponible - $cantidad;
                        $updateStmt = $pdo->prepare("UPDATE cosechas SET cantidad = :nuevo_stock WHERE id = :cosecha_id");
                        $updateStmt->execute([':nuevo_stock' => $nuevo_stock, ':cosecha_id' => $cosecha_id]);

                        echo "<p>Datos insertados con éxito!</p>";
                    }
                }
            }

            // Manejo de eliminación
            if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_id'])) {
                $delete_id = $_POST['delete_id'];

                try {
                    // Eliminar la fila de producción
                    $sql = "DELETE FROM produccion WHERE id = :id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([':id' => $delete_id]);

                    echo "<p>Producción eliminada con éxito!</p>";
                } catch (PDOException $e) {
                    if ($e->getCode() == '23503') {
                        echo "<p>Error: Esta flor no se puede eliminar debido a que está siendo utilizada en un proceso de exportación.</p>";
                    } else {
                        echo "<p>Error: " . $e->getMessage() . "</p>";
                    }
                }
            }

            // Manejo de edición
            if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_id'])) {
                $edit_id = $_POST['edit_id'];
                $sql = "SELECT * FROM produccion WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':id' => $edit_id]);
                $produccion = $stmt->fetch(PDO::FETCH_ASSOC);
            }

            if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_id'])) {
                $update_id = $_POST['update_id'];
                $cosecha_id = $_POST['cosecha_id'];
                $fecha = $_POST['fecha'];
                $cantidad = $_POST['cantidad'];
                $estado = $_POST['estado'];

                $sql = "UPDATE produccion SET cosecha_id = :cosecha_id, fecha = :fecha, cantidad = :cantidad, estado = :estado WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':cosecha_id' => $cosecha_id, ':fecha' => $fecha, ':cantidad' => $cantidad, ':estado' => $estado, ':id' => $update_id]);

                echo "<p>Producción actualizada con éxito!</p>";
                header("Location: produccion.php");
                exit();
            }

            // Mostrar la tabla de producción
            $stmt = $pdo->query("SELECT produccion.id, cosechas.fecha AS cosecha_fecha, produccion.fecha AS produccion_fecha, produccion.cantidad, flores.nombre AS flor, produccion.estado
                                 FROM produccion
                                 JOIN cosechas ON produccion.cosecha_id = cosechas.id
                                 JOIN flores ON cosechas.flor_id = flores.id");

            echo "<table>";
            echo "<tr><th>ID</th><th>Fecha Cosecha</th><th>Fecha Producción</th><th>Cantidad</th><th>Flor</th><th>Estado</th><th>Acciones</th></tr>";

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                echo "<td>" . htmlspecialchars($row['cosecha_fecha']) . "</td>";
                echo "<td>" . htmlspecialchars($row['produccion_fecha']) . "</td>";
                echo "<td>" . htmlspecialchars($row['cantidad']) . "</td>";
                echo "<td>" . htmlspecialchars($row['flor']) . "</td>";
                echo "<td>" . htmlspecialchars($row['estado']) . "</td>";
                echo "<td>
                <div class='action-buttons'>
                        <form method='post' action='produccion.php' style='display:inline-block;'>
                            <input type='hidden' name='edit_id' value='" . htmlspecialchars($row['id']) . "'>
                            <input type='submit' value='Editar'>
                        </form>
                        <form method='post' action='produccion.php' style='display:inline-block;'>
                            <input type='hidden' name='delete_id' value='" . htmlspecialchars($row['id']) . "'>
                            <input type='submit' value='Eliminar'>
                        </form>

                    </div>
                      </td>";
                echo "</tr>";
            }

            echo "</table>";
        }
    } catch (PDOException $e) {
        echo "<p>Error: " . $e->getMessage() . "</p>";
    }
    ?>

    <?php if (isset($produccion)): ?>
    <h2>Editar Producción</h2>
    <form method="post" action="">
        <input type="hidden" name="update_id" value="<?php echo htmlspecialchars($produccion['id']); ?>">
        <label for="cosecha_id">Cosecha:</label>
        <select id="cosecha_id" name="cosecha_id" required>
            <?php
            try {
                $stmt = $pdo->query("SELECT cosechas.id, flores.nombre AS flor, cosechas.fecha FROM cosechas JOIN flores ON cosechas.flor_id = flores.id");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $selected = $row['id'] == $produccion['cosecha_id'] ? 'selected' : '';
                    echo "<option value=\"" . htmlspecialchars($row['id']) . "\" $selected>" . htmlspecialchars($row['flor']) . " - " . htmlspecialchars($row['fecha']) . "</option>";
                }
            } catch (PDOException $e) {
                echo "<option>Error: " . $e->getMessage() . "</option>";
            }
            ?>
        </select>
        <br>
        <label for="fecha">Fecha:</label>
        <input type="date" id="fecha" name="fecha" value="<?php echo htmlspecialchars($produccion['fecha']); ?>" required>
        <br>
        <label for="cantidad">Cantidad:</label>
        <input type="number" id="cantidad" name="cantidad" value="<?php echo htmlspecialchars($produccion['cantidad']); ?>" min="0" required>
        <br>
        <label for="estado">Estado:</label>
        <input type="text" id="estado" name="estado" value="<?php echo htmlspecialchars($produccion['estado']); ?>" required>
        <br>
        <input type="submit" value="Actualizar">
    </form>
    <?php else: ?>
    <h2>Insertar Nueva Producción</h2>
    <form method="post" action="">
        <label for="cosecha_id">Cosecha:</label>
        <select id="cosecha_id" name="cosecha_id" required>
            <?php
            try {
                $stmt = $pdo->query("SELECT cosechas.id, flores.nombre AS flor, cosechas.fecha FROM cosechas JOIN flores ON cosechas.flor_id = flores.id");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    echo "<option value=\"" . htmlspecialchars($row['id']) . "\">" . htmlspecialchars($row['flor']) . " - " . htmlspecialchars($row['fecha']) . "</option>";
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
        <label for="cantidad">Cantidad:</label>
        <input type="number" id="cantidad" name="cantidad" min="0" required>
        <br>
        <input type="submit" value="Insertar">
    </form>
    <?php endif; ?>
</body>
</html>
