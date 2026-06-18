<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

checkRole(['Admin']);

// 1. Validate CSRF
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    header("Location: /admin/team.php?status=error&msg=Security+token+invalid");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    $name  = htmlspecialchars($_POST['name'], ENT_QUOTES, 'UTF-8');
    // Capture the role from the modal
    $role  = in_array($_POST['role'], ['Field Worker', 'Manager']) ? $_POST['role'] : 'Field Worker';

    if (!$email || !$name) {
        header("Location: /admin/team.php?status=error&msg=Invalid+input");
        exit();
    }

    // 2. Check for existing user
    $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$email]);
    if ($check->fetch()) {
        header("Location: /admin/team.php?status=error&msg=Email+already+exists");
        exit();
    }

    // 3. Insert record using the dynamic role
    $token = bin2hex(random_bytes(32));
    $stmt = $pdo->prepare("INSERT INTO users (email, name, role, is_active, password, setup_token) VALUES (?, ?, ?, 0, '', ?)");
    $stmt->execute([$email, $name, $role, $token]);

    // 4. Send email
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'sandbox.smtp.mailtrap.io';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'a7c6d2bbe8e2d7'; 
        $mail->Password   = '681e6de4560e35'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 2525;
        $mail->setFrom('no-reply@fieldguard.com', 'FieldGuard');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Join FieldGuard';
        $mail->Body    = "Hi $name, welcome to FieldGuard. <br><br> Set your password here: <a href='https://fieldguard.test/setup.php?token=$token'>this link</a>.";
        
        $mail->send();
        header("Location: /admin/team.php?status=success");
    } catch (Exception $e) {
        header("Location: /admin/team.php?status=error&msg=Email+failed");
    }
    exit();
}