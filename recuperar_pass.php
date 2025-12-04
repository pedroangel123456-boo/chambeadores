<?php
// recuperar_pass.php

// 1. Cargar librerías de PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

// 2. Cargar configuración de BD
require_once 'db_config.php';

header('Content-Type: application/json');

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Método no permitido"]);
    exit();
}

$data = json_decode(file_get_contents("php://input"));
$correo = $data->strCorreo ?? '';

if (empty($correo)) {
    echo json_encode(["success" => false, "message" => "Ingresa tu correo"]);
    exit();
}

$pdo = getConnection();

// 3. Verificar si el correo existe
$stmt = $pdo->prepare("SELECT id, strNombre FROM UsuUsuario WHERE strCorreo = ?");
$stmt->execute([$correo]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    // Por seguridad, a veces se dice "Se envió el correo" aunque no exista, 
    // pero para tu proyecto diremos la verdad para que sepas si falla.
    echo json_encode(["success" => false, "message" => "Este correo no está registrado"]);
    exit();
}

// 4. Generar código de 6 dígitos
$codigo = rand(100000, 999999);

// 5. Guardar código en la BD
// El código expirará en 15 minutos (DATE_ADD)
$sql = "UPDATE UsuUsuario SET token_recuperacion = ?, token_expiracion = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE id = ?";
$pdo->prepare($sql)->execute([$codigo, $usuario['id']]);

// 6. Enviar el correo con PHPMailer
$mail = new PHPMailer(true);

try {
    // Configuración del Servidor SMTP de Gmail
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'ruiz.monse507@gmail.com'; // TU CORREO
   $mail->Password   = 'lgrzdfjkxprtyqoe';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Usar SSL
    $mail->Port       = 465; // Puerto SSL

    // Destinatarios
    $mail->setFrom('ruiz.monse507@gmail.com', 'Soporte Chambeadores');
    $mail->addAddress($correo, $usuario['strNombre']);

    // Contenido
    $mail->isHTML(true);
    $mail->Subject = 'Recuperar Contrasena - Chambeadores';
    $mail->Body    = "
        <h3>Hola, {$usuario['strNombre']}</h3>
        <p>Has solicitado recuperar tu contraseña.</p>
        <p>Tu código de recuperación es:</p>
        <h1 style='color: #2c3e50;'>$codigo</h1>
        <p>Este código expira en 15 minutos.</p>
    ";

    $mail->send();
    echo json_encode(["success" => true, "message" => "Código enviado a tu correo"]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Error al enviar correo: {$mail->ErrorInfo}"]);
}
?>