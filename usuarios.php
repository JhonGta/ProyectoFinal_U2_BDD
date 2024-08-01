<?php
session_start();

// Verifica si el usuario está autenticado
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

// Verifica el rol del usuario
$role = $_SESSION['role'];
if ($role != 'usuario_admin') {
    echo "<p>Acceso denegado.</p>";
    exit;
}

// Configuración de la conexión a la base de datos
$host = '192.168.100.161';
$db = 'flores';
$user = 'Postgres1';
$password = '1234';
$dsn = "pgsql:host=$host;port=5432;dbname=$db;user=$user;password=$password";

try {
    $pdo = new PDO($dsn);

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Procesar la creación y eliminación de usuarios
        if (isset($_POST['action'])) {
            if ($_POST['action'] == 'add') {
                // Agregar nuevo usuario
                $username = $_POST['username'];
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $role = $_POST['role'];

                $stmt = $pdo->prepare("INSERT INTO public.usuarios (username, password, role) VALUES (:username, :password, :role)");
                $stmt->execute([':username' => $username, ':password' => $password, ':role' => $role]);
            } elseif ($_POST['action'] == 'delete') {
                // Eliminar usuario
                $username = $_POST['username'];

                $stmt = $pdo->prepare("DELETE FROM public.usuarios WHERE username = :username");
                $stmt->execute([':username' => $username]);
            }
        }
    }

    // Obtener todos los usuarios
    $stmt = $pdo->query("SELECT * FROM public.usuarios");

    // Mostrar usuarios en tabla
    echo "<h2>Administrar Usuarios</h2>";
    echo "<form method='post' action=''>";
    echo "<label for='username'>Nombre de Usuario:</label>";
    echo "<input type='text' id='username' name='username' required>";
    echo "<label for='password'>Contraseña:</label>";
    echo "<input type='password' id='password' name='password' required>";
    echo "<label for='role'>Rol:</label>";
    echo "<select id='role' name='role'>";
    echo "<option value='usuario_admin'>Administrador</option>";
    echo "<option value='usuario_ventas'>Ventas</option>";
    echo "<option value='usuario_produccion'>Producción</option>";
    echo "</select>";
    echo "<input type='submit' name='action' value='add' />";
    echo "</form>";

    echo "<h3>Usuarios Existentes</h3>";
    echo "<table>";
    echo "<thead><tr><th>Nombre de Usuario</th><th>Rol</th><th>Acciones</th></tr></thead>";
    echo "<tbody>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['username']) . "</td>";
        echo "<td>" . htmlspecialchars($row['role']) . "</td>";
        echo "<td><form method='post' action='' style='display:inline;'><input type='hidden' name='username' value='" . htmlspecialchars($row['username']) . "'><input type='submit' name='action' value='delete'></form></td>";
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
    <title>Administrar Usuarios</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h2>Administrar Usuarios</h2>
    <!-- El formulario y la tabla de usuarios ya están en el bloque PHP -->
</body>
</html>
