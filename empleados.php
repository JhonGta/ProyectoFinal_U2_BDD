<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Empleados</title>
    <link rel="stylesheet" href="css/esti.css">
</head>
<body>
    <h2>Empleados</h2>
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
if ($role != 'usuario_admin') {
    echo "<p>Acceso denegado.</p>";
    exit;
}

// Configuración de la conexión a la base de datos
$host = '192.168.100.161';
$db = 'flores';
$user = ($role == 'usuario_admin') ? 'usuario_admin' : 'usuario_produccion';
$password = ($role == 'usuario_admin') ? 'admin_1234' : 'produccion_1234';
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
