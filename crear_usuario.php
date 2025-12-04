<?php
// crear_usuario.php - Servicio para registrar un nuevo usuario

// Incluimos la configuración de la base de datos
require_once 'db_config.php';

// Establecer la cabecera para devolver JSON
header('Content-Type: application/json');
// Permitir peticiones POST desde cualquier origen (necesario para pruebas locales/Android)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

// Solo aceptamos peticiones POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Método no permitido
    echo json_encode(["success" => false, "message" => "Método no permitido."]);
    exit();
}

// 1. Obtener los datos del cuerpo de la petición (JSON desde Android)
$data = json_decode(file_get_contents("php://input"));

// Comprobación básica de datos
if (empty($data->strNombre) || empty($data->strCorreo) || empty($data->strContrasena) || empty($data->idUsuCatRol)) {
    http_response_code(400); // Solicitud incorrecta
    echo json_encode(["success" => false, "message" => "Faltan campos requeridos (nombre, correo, contraseña, rol)."]);
    exit();
}

$nombre = $data->strNombre;
$apellido = $data->strApellido ?? null; // Si no viene, es null
$correo = $data->strCorreo;
$contrasena = $data->strContrasena;
$idRol = $data->idUsuCatRol;

// 2. Seguridad: Hashear la contraseña
// PASSWORD_DEFAULT utiliza el algoritmo de hash más fuerte disponible (actualmente BCRYPT)
$contrasena_hash = password_hash($contrasena, PASSWORD_DEFAULT);

$pdo = getConnection();

if (!$pdo) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Error interno del servidor (BD)."]);
    exit();
}

try {
    // Definimos los valores por defecto que especificaste en tu BD:
    $estado_activo = 1; // 1 = Activo (de tu tabla UsuCatEstado)

    // El strUsername lo definiremos igual que el correo para simplificar
    $username = $correo; 

    // 3. Preparar la consulta SQL para insertar el nuevo usuario
    $sql = "INSERT INTO UsuUsuario (strNombre, strApellido, strCorreo, strUsername, strContrasena, idUsuCatEstado, idUsuCatRol) 
            VALUES (:nombre, :apellido, :correo, :username, :hash, :estado, :rol)";

    $stmt = $pdo->prepare($sql);

    // 4. Ejecutar la inserción
    $stmt->execute([
        ':nombre' => $nombre,
        ':apellido' => $apellido,
        ':correo' => $correo,
        ':username' => $username,
        ':hash' => $contrasena_hash, // ¡Usamos el hash!
        ':estado' => $estado_activo,
        ':rol' => $idRol // 1=Worker, 2=Employer
    ]);

    // Éxito
    http_response_code(201); // 201 Created
    echo json_encode([
        "success" => true,
        "message" => "Usuario registrado con éxito.",
        "id_usuario" => $pdo->lastInsertId()
    ]);

} catch (PDOException $e) {
    // Si hay un error, lo más común es una violación de UNIQUE (ej. el correo ya existe)
    if ($e->getCode() == '23000') { // Código de error de MySQL para duplicado
        http_response_code(409); // Conflicto
        echo json_encode(["success" => false, "message" => "El correo o username ya está registrado."]);
    } else {
        http_response_code(500);
        error_log("Error de BD en registro: " . $e->getMessage());
        echo json_encode(["success" => false, "message" => "Error al registrar el usuario.", "error" => $e->getMessage()]);
    }
}