<?php
require_once __DIR__ . '/../app/controllers/UserController.php';
header('Content-Type: text/html; charset=utf-8');

$token = $_GET['token'] ?? '';
$ctrl = new UserController();
$result = $ctrl->verify($token);

?><!doctype html>
<html>
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Account Verification</title>
  <style>
    body { font-family: Arial, sans-serif; background:#f6f8fb; color:#222; padding:40px; }
    .card { max-width:680px; margin:40px auto; background:#fff; padding:28px; border-radius:12px; box-shadow:0 6px 30px rgba(0,0,0,0.08); }
    .ok { color: #0a7d3a; font-weight:700; }
    .err { color:#b00020; font-weight:700; }
    a.button { display:inline-block; margin-top:18px; background:#2b8cff; color:#fff; padding:10px 16px; text-decoration:none; border-radius:8px; }
  </style>
</head>
<body>
  <div class="card">
    <h2>Account Verification</h2>
    <p>
      <?php if ($result['success']) : ?>
        <span class="ok"><?=htmlspecialchars($result['message'])?></span>
      <?php else : ?>
        <span class="err"><?=htmlspecialchars($result['message'])?></span>
      <?php endif; ?>
    </p>
    <p>
      <a class="button" href="/">Return to site</a>
    </p>
  </div>
</body>
</html>