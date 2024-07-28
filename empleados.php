<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Empleados</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h2>Empleados</h2>
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
    $host = '192.168.7.158';
    $db = 'flores';
    $user = 'Postgres1';
    $password = '1234';

    $dsn = "pgsql:host=$host;port=5432;dbname=$db;user=$user;password=$password";

    try {
        $pdo = new PDO($dsn);

        if ($pdo) {
            echo "<p>Conexión exitosa a la base de datos $db!</p>";

            if ($_SERVER["REQUEST_METHOD"] == "POST") {
                $nombre = $_POST['nombre'];
                $cargo = $_POST['cargo'];

                $sql = "INSERT INTO empleados (nombre, cargo) VALUES (:nombre, :cargo)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':nombre' => $nombre, ':cargo' => $cargo]);

                echo "<p>Datos insertados con éxito!</p>";
            }

            $stmt = $pdo->query("SELECT * FROM empleados");

            echo "<table>";
            echo "<tr><th>ID</th><th>Nombre</th><th>Cargo</th></tr>";

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                echo "<td>" . htmlspecialchars($row['nombre']) . "</td>";
                echo "<td>" . htmlspecialchars($row['cargo']) . "</td>";
                echo "</tr>";
            }

            echo "</table>";
        }
    } catch (PDOException $e) {
        echo "<p>Error: " . $e->getMessage() . "</p>";
    }
    ?>

    <h2>Insertar Nuevo Empleado</h2>
    <form method="post" action="">
        <label for="nombre">Nombre:</label>
        <input type="text" id="nombre" name="nombre" required>
        <br>
        <label for="cargo">Cargo:</label>
        <input type="text" id="cargo" name="cargo" required>
        <br>
        <input type="submit" value="Insertar">
    </form>
</body>
</html>
