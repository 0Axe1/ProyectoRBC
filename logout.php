<?php
// Siempre se debe iniciar la sesión antes de poder manipularla.
session_start();

// 1. Desvincula todas las variables de la sesión.
$_SESSION = [];

// 2. Destruye la sesión por completo.
session_destroy();

// 3. Redirige al usuario a la página de login.
header('Location: login.php');

// 4. Asegúrate de que el script se detenga después de la redirección.
exit;
?>
