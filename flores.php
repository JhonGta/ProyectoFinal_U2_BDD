<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Flores</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h2>Flores</h2>
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
            if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['nombre']) && !isset($_POST['update_id'])) {
                $nombre = $_POST['nombre'];
                $tipo_id = $_POST['tipo_id'];
                $color = $_POST['color'];
                $precio_unitario = $_POST['precio_unitario'];

                // Validación del lado del servidor
                if ($precio_unitario < 0) {
                    echo "<p>Error: El precio unitario no puede ser negativo.</p>";
                } else {
                    $sql = "INSERT INTO flores (nombre, tipo_id, color, precio_unitario) VALUES (:nombre, :tipo_id, :color, :precio_unitario)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([':nombre' => $nombre, ':tipo_id' => $tipo_id, ':color' => $color, ':precio_unitario' => $precio_unitario]);

                    echo "<p>Datos insertados con éxito!</p>";
                }
            }

            // Manejo de eliminación
            if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_id'])) {
                $delete_id = $_POST['delete_id'];

                try {


                    // Luego eliminar la fila de flores
                    $sql = "DELETE FROM flores WHERE id = :id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([':id' => $delete_id]);

                    echo "<p>Flor eliminada con éxito!</p>";
                } catch (PDOException $e) {
                    if ($e->getCode() == '23503') {
                        echo "<p>Error: Esta flor no se puede eliminar debido a que está siendo utilizada en un proceso de Cosecha.</p>";
                    } else {
                        echo "<p>Error: " . $e->getMessage() . "</p>";
                    }
                }
            }

            // Manejo de edición
            if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_id'])) {
                $edit_id = $_POST['edit_id'];
                $sql = "SELECT * FROM flores WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':id' => $edit_id]);
                $flor = $stmt->fetch(PDO::FETCH_ASSOC);
            }

            if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_id'])) {
                $update_id = $_POST['update_id'];
                $nombre = $_POST['nombre'];
                $tipo_id = $_POST['tipo_id'];
                $color = $_POST['color'];
                $precio_unitario = $_POST['precio_unitario'];

                $sql = "UPDATE flores SET nombre = :nombre, tipo_id = :tipo_id, color = :color, precio_unitario = :precio_unitario WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':nombre' => $nombre, ':tipo_id' => $tipo_id, ':color' => $color, ':precio_unitario' => $precio_unitario, ':id' => $update_id]);

                echo "<p>Flor actualizada con éxito!</p>";
                header("Location: flores.php");
                exit();
            }

            // Mostrar la tabla de flores
            $stmt = $pdo->query("SELECT flores.id, flores.nombre, tipos_flores.nombre AS tipo, flores.color, flores.precio_unitario FROM flores JOIN tipos_flores ON flores.tipo_id = tipos_flores.id");

            echo "<table>";
            echo "<tr><th>ID</th><th>Nombre</th><th>Tipo</th><th>Color</th><th>Precio Unitario</th><th>Acciones</th></tr>";

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                echo "<td>" . htmlspecialchars($row['nombre']) . "</td>";
                echo "<td>" . htmlspecialchars($row['tipo']) . "</td>";
                echo "<td>" . htmlspecialchars($row['color']) . "</td>";
                echo "<td>" . htmlspecialchars($row['precio_unitario']) . "</td>";
                echo "<td>

                <div class='action-buttons'>
                        <form method='post' action='flores.php' style='display:inline-block;'>
                            <input type='hidden' name='edit_id' value='" . htmlspecialchars($row['id']) . "'>
                            <input type='submit' value='Editar'>
                        </form>
                        <form method='post' action='flores.php' style='display:inline-block;'>
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

    <?php if (isset($flor)): ?> 
    <h2>Editar Flor</h2>
    <form method="post" action="">
        <input type="hidden" name="update_id" value="<?php echo htmlspecialchars($flor['id']); ?>">
        <label for="nombre">Nombre:</label>
        <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($flor['nombre']); ?>" required>
        <br>
        <label for="tipo_id">Tipo:</label>
        <select id="tipo_id" name="tipo_id" required>
            <?php
            try {
                $stmt = $pdo->query("SELECT id, nombre FROM tipos_flores");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $selected = $row['id'] == $flor['tipo_id'] ? 'selected' : '';
                    echo "<option value=\"" . htmlspecialchars($row['id']) . "\" $selected>" . htmlspecialchars($row['nombre']) . "</option>";
                }
            } catch (PDOException $e) {
                echo "<option>Error: " . $e->getMessage() . "</option>";
            }
            ?>
        </select>
        <br>
        <label for="color">Color:</label>
        <input type="text" id="color" name="color" value="<?php echo htmlspecialchars($flor['color']); ?>" required>
        <br>
        <label for="precio_unitario">Precio Unitario:</label>
        <input type="number" step="0.01" id="precio_unitario" name="precio_unitario" value="<?php echo htmlspecialchars($flor['precio_unitario']); ?>" min="0" required>
        <br>
        <input type="submit" value="Actualizar">
    </form>
    <?php endif; ?>

    <h2>Insertar Nueva Flor</h2>
    <form method="post" action="">
        <label for="nombre">Nombre:</label>
        <input type="text" id="nombre" name="nombre" required>
        <br>
        <label for="tipo_id">Tipo:</label>
        <select id="tipo_id" name="tipo_id" required>
            <?php
            try {
                $stmt = $pdo->query("SELECT id, nombre FROM tipos_flores");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    echo "<option value=\"" . htmlspecialchars($row['id']) . "\">" . htmlspecialchars($row['nombre']) . "</option>";
                }
            } catch (PDOException $e) {
                echo "<option>Error: " . $e->getMessage() . "</option>";
            }
            ?>
        </select>
        <br>
        <label for="color">Color:</label>
        <input type="text" id="color" name="color" required>
        <br>
        <label for="precio_unitario">Precio Unitario:</label>
        <input type="number" step="0.01" id="precio_unitario" name="precio_unitario" min="0.01" required>
        <br>
        <input type="submit" value="Insertar">
    </form>
</body>
</html>
