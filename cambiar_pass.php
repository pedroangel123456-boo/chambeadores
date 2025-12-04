<?php


require_once 'db_config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Método no permitido"]);
    exit();
}

$data = json_decode(file_get_contents("php://input"));


if (empty($data->strCorreo) || empty($data->strCodigo) || empty($data->strNuevaContrasena)) {
    echo json_encode(["success" => false, "message" => "Faltan datos."]);
    exit();
}

$correo = $data->strCorreo;
$codigoIngresado = $data->strCodigo;
$nuevaPass = $data->strNuevaContrasena;

$pdo = getConnection();

$stmt = $pdo->prepare("SELECT id, token_recuperacion, token_expiracion FROM UsuUsuario WHERE strCorreo = ?");
$stmt->execute([$correo]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    echo json_encode(["success" => false, "message" => "Usuario no encontrado."]);
    exit();
}

if ($usuario['token_recuperacion'] !== $codigoIngresado) {
    echo json_encode(["success" => false, "message" => "El código es incorrecto."]);
    exit();
}

// 3. Verificar si el token caducó (fecha actual > fecha expiración)
if (strtotime(date("Y-m-d H:i:s")) > strtotime($usuario['token_expiracion'])) {
    echo json_encode(["success" => false, "message" => "El código ha expirado. Solicita uno nuevo."]);
    exit();
}

// 4. ¡Todo bien! Encriptar la nueva contraseña
$hashNuevaPass = password_hash($nuevaPass, PASSWORD_DEFAULT);

// 5. Actualizar BD y limpiar el token para que no se pueda reusar
$sql = "UPDATE UsuUsuario SET strContrasena = ?, token_recuperacion = NULL, token_expiracion = NULL WHERE id = ?";
$stmtUpdate = $pdo->prepare($sql);

if ($stmtUpdate->execute([$hashNuevaPass, $usuario['id']])) {
    echo json_encode(["success" => true, "message" => "¡Contraseña actualizada con éxito!"]);
} else {
    echo json_encode(["success" => false, "message" => "Error al actualizar la base de datos."]);
}
?>