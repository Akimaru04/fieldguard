<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

checkRole(['Admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $name  = htmlspecialchars($_POST['name'], ENT_QUOTES, 'UTF-8');

    // 1. Check for existing user
    $check = $pdo->prepare("SELECT id, is_active FROM users WHERE email = ?");
    $check->execute([$email]);
    $existing = $check->fetch();

    if ($existing) {
        header("Location: /admin/team.php?status=error&msg=Email+already+exists");
        exit();
    }

    // 2. Prepare user record
    $token = bin2hex(random_bytes(32));
    $temp_pass = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);

    // 3. Insert into database
    $stmt = $pdo->prepare("INSERT INTO users (email, name, role, is_active, password, setup_token) VALUES (?, ?, 'Field Worker', 0, ?, ?)");
    $stmt->execute([$email, $name, $temp_pass, $token]);

    // 4. Send invitation email
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'sandbox.smtp.mailtrap.io';
        $mail->SMTPAuth   = true;
        $mail->Username   = getenv('a7c6d2bbe8e2d7'); 
        $mail->Password   = getenv('681e6de4560e35'); 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 2525;
        $mail->setFrom('no-reply@fieldguard.com', 'FieldGuard');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Join FieldGuard';
        
        $link = "https://fieldguard.com/setup.php?token=" . $token;
        $mail->Body    = "Hi $name, welcome to FieldGuard. <br><br> Please set your password here: <a href='$link'>$link</a>";
        
        $mail->send();
        header("Location: /admin/team.php?status=success");
    } catch (Exception $e) {
        header("Location: /admin/team.php?status=error&msg=Email+failed");
    }
    exit();
}