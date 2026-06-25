<?php
// /logic/process-invite.php
session_start();

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Ensure BASE_URL is defined dynamically
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
    define('BASE_URL', $protocol . "://" . $_SERVER['HTTP_HOST']);
}

checkRole(['Admin']);

// 1. Validate CSRF
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    header("Location: /admin/team.php?status=error&msg=Security+token+invalid");
    exit();
}

// 2. Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    $name  = htmlspecialchars($_POST['name'], ENT_QUOTES, 'UTF-8');
    $role  = in_array($_POST['role'], ['Field Worker', 'Manager']) ? $_POST['role'] : 'Field Worker';

    if (!$email || !$name) {
        header("Location: /admin/team.php?status=error&msg=Invalid+input");
        exit();
    }

    // 3. Check existing user
    $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$email]);
    if ($check->fetch()) {
        header("Location: /admin/team.php?status=error&msg=Email+already+exists");
        exit();
    }

    // 4. Create token + insert user using named parameters for safety
    $token = bin2hex(random_bytes(32));
    $stmt = $pdo->prepare("
        INSERT INTO users (email, name, role, is_active, password, setup_token)
        VALUES (:email, :name, :role, 0, '', :token)
    ");
    $stmt->execute([
        'email' => $email,
        'name'  => $name,
        'role'  => $role,
        'token' => $token
    ]);

    // 5. Generate links
    $truncatedToken = substr($token, 0, 12);
    $shortLink = BASE_URL . "/setup.php?token=" . $truncatedToken;

    // 6. Email setup
    $mail = new PHPMailer(true);
    $mail->CharSet = 'UTF-8';
    try {
        $mail->isSMTP();
        $mail->Host       = 'sandbox.smtp.mailtrap.io';
        $mail->SMTPAuth   = true;
        $mail->Username   = '3aff222b3a6e8d';
        $mail->Password   = '0de64fc0e1d29f';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 2525;
        $mail->setFrom('no-reply@fieldguard.com', 'FieldGuard');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Join FieldGuard';
        $mail->Body = "
            <div style='font-family: Arial; padding: 20px;'>
                <h2>Welcome, $name!</h2>
                <p>Click below to set your password:</p>
                <a href='$shortLink' style='padding:12px; background:#2563eb; color:#fff; text-decoration:none;'>Set Password</a>
                <p>Or paste this: $shortLink</p>
            </div>";
        $mail->AltBody = "Welcome to FieldGuard. Set your password here: $shortLink";
        $mail->send();
        header("Location: /admin/team.php?status=success");
        exit();
    } catch (Exception $e) {
        header("Location: /admin/team.php?status=error&msg=Email+failed");
        exit();
    }
}