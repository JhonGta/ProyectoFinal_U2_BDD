<?php
session_start();

// Configuración de la conexión a la base de datos
$host = '192.168.100.161';
$db = 'flores';
$user = 'Postgres1';
$password = '1234';
$dsn = "pgsql:host=$host;port=5432;dbname=$db;user=$user;password=$password";

try {
    $pdo = new PDO($dsn);

    // Procesar el formulario de inicio de sesión
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $username = $_POST['username'];
        $password = $_POST['password'];

        // Consultar el usuario
        $stmt = $pdo->prepare("SELECT * FROM public.usuarios WHERE username = :username");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            header('Location: index.php');
            exit;
        } else {
            echo "<p>Nombre de usuario o contraseña incorrectos.</p>";
        }
    }
} catch (PDOException $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
</head>
<body>
    <h2>Iniciar sesión</h2>
    <form method="post" action="">
        <label for="username">Nombre de Usuario:</label>
        <input type="text" id="username" name="username" required>
        <br>
        <label for="password">Contraseña:</label>
        <input type="password" id="password" name="password" required>
        <br>
        <input type="submit" value="Iniciar sesión">
    </form>
</body>
</html>
