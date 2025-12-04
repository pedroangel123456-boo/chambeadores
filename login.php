<?php


require_once 'db_config.php';

// Establecer cabeceras
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

// Solo aceptamos peticiones POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Método no permitido."]);
    exit();
}

// 1. Obtener los datos (correo y contraseña)
$data = json_decode(file_get_contents("php://input"));

if (empty($data->strCorreo) || empty($data->strContrasena)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Faltan el correo o la contraseña."]);
    exit();
}

$correo = $data->strCorreo;
$contrasena_ingresada = $data->strContrasena;

$pdo = getConnection();

if (!$pdo) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Error interno del servidor (BD)."]);
    exit();
}

try {
    // 2. Buscar al usuario por correo
    $sql = "SELECT id, strContrasena, strNombre, strApellido, idUsuCatRol, idUsuCatEstado 
            FROM UsuUsuario 
            WHERE strCorreo = :correo";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':correo' => $correo]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    // 3. Verificar si el usuario existe
    if (!$usuario) {
        http_response_code(401); // No autorizado
        echo json_encode(["success" => false, "message" => "Credenciales incorrectas (Correo no encontrado)."]);
        exit();
    }

    // 4. Verificar la contraseña
    // password_verify() compara la contraseña plana con el hash almacenado.
    if (password_verify($contrasena_ingresada, $usuario['strContrasena'])) {
        
        // --- 5. Éxito en el Login (Aquí va la lógica del Token) ---
        // Por ahora, solo devolvemos datos básicos, pero aquí generarías un JWT.
        
        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => "Inicio de sesión exitoso.",
            "data" => [
                "id" => $usuario['id'],
                "nombre_completo" => $usuario['strNombre'] . ' ' . $usuario['strApellido'],
                "rol" => $usuario['idUsuCatRol'],
                "token_temporal" => "GENERAR_TOKEN_REAL_AQUI" // Esto debería ser un JWT real.
            ]
        ]);
        
        // Opcional: Registrar login en UsuHistorialLogin
        // $sql_historial = "INSERT INTO UsuHistorialLogin (idUsuUsuario, strIP) VALUES (?, ?)";
        // $pdo->prepare($sql_historial)->execute([$usuario['id'], $_SERVER['REMOTE_ADDR']]);

    } else {
        // Contraseña incorrecta
        http_response_code(401);
        echo json_encode(["success" => false, "message" => "Credenciales incorrectas (Contraseña inválida)."]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Error de BD en login: " . $e->getMessage());
    echo json_encode(["success" => false, "message" => "Error al procesar el login."]);
}