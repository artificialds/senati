<?php
declare(strict_types=1);
session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'use_strict_mode' => true,
]);
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');

$store = __DIR__ . '/private/users.php';
if (!is_dir(dirname($store))) {
    mkdir(dirname($store), 0700, true);
}
function usersFile(string $path): array {
    if (!is_file($path)) return [];
    $raw = file_get_contents($path);
    $data = $raw === false ? [] : json_decode(preg_replace('/^<\\?php exit; \\?>\\s*/', '', $raw), true);
    return is_array($data) ? $data : [];
}
function saveUsers(string $path, array $users): bool {
    $payload = "<?php exit; ?>\n" . json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    return file_put_contents($path, $payload, LOCK_EX) !== false;
}
function e(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
if (!isset($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(24));
$users = usersFile($store);
$setup = count($users) === 0;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], (string)($_POST['csrf'] ?? ''))) {
        http_response_code(403);
        $error = 'Sesión vencida. Actualiza la página.';
    } else {
        $action = (string)($_POST['action'] ?? '');
        if ($action === 'logout') {
            $_SESSION = [];
            session_regenerate_id(true);
            header('Location: index.php');
            exit;
        }
        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        if ($action === 'setup' && $setup) {
            if (!preg_match('/^[a-zA-Z0-9_.-]{3,30}$/', $username)) {
                $error = 'Usuario: 3 a 30 caracteres, letras, números, punto, guion o guion bajo.';
            } elseif (strlen($password) < 12) {
                $error = 'La contraseña debe tener al menos 12 caracteres.';
            } else {
                $record = ['username' => $username, 'hash' => password_hash($password, PASSWORD_DEFAULT), 'role' => 'admin'];
                // Recheck after locking to avoid accidentally replacing an admin.
                $lock = fopen(__DIR__ . '/private/setup.lock', 'c');
                if (!$lock || !flock($lock, LOCK_EX)) {
                    $error = 'No se pudo iniciar la configuración.';
                } else {
                    if (count(usersFile($store)) > 0) {
                        $error = 'El administrador ya fue configurado. Actualiza la página.';
                    } elseif (saveUsers($store, [$record])) {
                        session_regenerate_id(true);
                        $_SESSION['user'] = $username;
                        header('Location: index.php');
                        exit;
                    } else {
                        $error = 'No se pudo guardar. Revisa los permisos de la carpeta private.';
                    }
                    flock($lock, LOCK_UN);
                    fclose($lock);
                }
            }
        } elseif ($action === 'login' && !$setup) {
            $account = null;
            foreach ($users as $u) {
                if (hash_equals((string)($u['username'] ?? ''), $username)) $account = $u;
            }
            if ($account && password_verify($password, (string)$account['hash'])) {
                session_regenerate_id(true);
                $_SESSION['user'] = $account['username'];
                header('Location: index.php');
                exit;
            }
            usleep(250000);
            $error = 'Usuario o contraseña incorrectos.';
        } else {
            $error = 'Acción no válida.';
        }
    }
}
$logged = isset($_SESSION['user']);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#09090d">
  <title>SENATI | Acceso</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <div class="glow glow-left" aria-hidden="true"></div>
  <div class="glow glow-right" aria-hidden="true"></div>
  <main class="layout">
    <section class="intro" aria-label="Presentación">
      <a class="brand" href="index.php"><span class="brand-mark">S</span><span>SENATI <small>PLATAFORMA</small></span></a>
      <div class="hero">
        <div class="eyebrow"><span class="signal"></span> SISTEMA DE ACCESO</div>
        <h1>Tu espacio.<br><em>Tu futuro.</em></h1>
        <p>Bienvenido a tu plataforma de proyectos. Ingresa de forma segura para continuar.</p>
        <div class="hero-rule"></div>
        <div class="hero-meta"><span>PROYECTOS</span><span>TECNOLOGÍA</span><span>INNOVACIÓN</span></div>
      </div>
      <p class="footer-note">© <?= date('Y') ?> SENATI · Proyecto académico</p>
    </section>
    <section class="form-side">
      <div class="login-card">
        <span class="card-icon" aria-hidden="true">⌘</span>
        <?php if ($logged): ?>
          <p class="overline">SESIÓN ACTIVA</p>
          <h2>¡Bienvenido!</h2>
          <p class="card-description">Has ingresado como <strong><?= e((string)$_SESSION['user']) ?></strong>.</p>
          <div class="success">✓ Acceso autorizado</div>
          <form method="post">
            <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
            <input type="hidden" name="action" value="logout">
            <button class="primary" type="submit">Cerrar sesión <span>↗</span></button>
          </form>
        <?php else: ?>
          <p class="overline"><?= $setup ? 'PRIMER ACCESO' : 'ACCESO PRIVADO' ?></p>
          <h2><?= $setup ? 'Crear administrador' : 'Iniciar sesión' ?></h2>
          <p class="card-description"><?= $setup ? 'Configura una cuenta de administrador para empezar.' : 'Introduce tus credenciales para acceder a tu cuenta.' ?></p>
          <?php if ($error): ?><div class="error" role="alert"><?= e($error) ?></div><?php endif; ?>
          <form method="post">
            <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
            <input type="hidden" name="action" value="<?= $setup ? 'setup' : 'login' ?>">
            <label for="username">Usuario</label>
            <div class="field"><span aria-hidden="true">◎</span><input id="username" name="username" placeholder="Tu nombre de usuario" autocomplete="username" minlength="3" maxlength="30" required autofocus></div>
            <label for="password">Contraseña</label>
            <div class="field"><span aria-hidden="true">◇</span><input id="password" name="password" type="password" placeholder="Tu contraseña" autocomplete="<?= $setup ? 'new-password' : 'current-password' ?>" minlength="<?= $setup ? '12' : '1' ?>" required><button class="show-password" type="button" aria-label="Mostrar contraseña" onclick="const p=document.getElementById('password');p.type=p.type==='password'?'text':'password';this.textContent=p.type==='password'?'Ver':'Ocultar'">Ver</button></div>
            <?php if ($setup): ?><p class="hint">Usa una contraseña de 12 caracteres o más.</p><?php endif; ?>
            <button class="primary" type="submit"><?= $setup ? 'Crear cuenta' : 'Ingresar' ?><span>↗</span></button>
          </form>
          <p class="bottom-help">Acceso restringido a usuarios autorizados.</p>
        <?php endif; ?>
      </div>
      <p class="form-footer">HECHO PARA CREAR · HECHO PARA APRENDER</p>
    </section>
  </main>
</body>
</html>
