<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => $secure,
        'samesite' => 'Lax',
        'path' => '/',
    ]);
    session_start();
}

const APP_ROOT = __DIR__ . '/..';

function installed(): bool { return is_file(APP_ROOT . '/config/config.php'); }
function app_config(): array {
    static $cfg = null;
    if ($cfg !== null) return $cfg;
    if (!installed()) return $cfg = [];
    return $cfg = require APP_ROOT . '/config/config.php';
}
function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    $cfg = app_config()['db'] ?? null;
    if (!$cfg) throw new RuntimeException('La aplicación todavía no está instalada.');
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $cfg['host'], $cfg['name'], $cfg['charset'] ?? 'utf8mb4');
    $pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}
function h(?string $v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function money(float $v): string { return 'Bs ' . number_format($v, 2, '.', ''); }
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function verify_csrf(?string $token): void {
    if (!$token || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $token)) {
        http_response_code(419); exit('Solicitud expirada. Recarga la página e inténtalo nuevamente.');
    }
}
function setting(string $key, $default = null) {
    if (!installed()) return $default;
    static $cache = [];
    if (array_key_exists($key, $cache)) return $cache[$key];
    try {
        $s = db()->prepare('SELECT setting_value FROM settings WHERE setting_key=? LIMIT 1');
        $s->execute([$key]);
        $r=$s->fetchColumn();
        return $cache[$key] = ($r === false ? $default : $r);
    } catch (Throwable $e) { return $default; }
}
function set_setting(string $key, string $value): void {
    $s=db()->prepare('INSERT INTO settings(setting_key,setting_value) VALUES(?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)');
    $s->execute([$key,$value]);
}
function default_products(): array {
    return [
        ['id'=>1,'slug'=>'clasico','name'=>'Clásico','description'=>'Ají cambita de colores en rodajitas, cebollita verde, pimentón de colores, pepinillos, vinagre y salsa soya.','price'=>60,'image'=>'assets/img/clasico.webp','stock'=>999,'active'=>1],
        ['id'=>2,'slug'=>'palmito','name'=>'Con Palmito','description'=>'La receta clásica con un toque de palmito.','price'=>60,'image'=>'assets/img/palmito.webp','stock'=>999,'active'=>1],
        ['id'=>3,'slug'=>'champinones','name'=>'Con Champiñones','description'=>'La receta clásica con champiñones.','price'=>60,'image'=>'assets/img/champinones.webp','stock'=>999,'active'=>1],
        ['id'=>4,'slug'=>'repollito','name'=>'Con Repollito','description'=>'La receta clásica con repollito.','price'=>60,'image'=>'assets/img/repollito.webp','stock'=>999,'active'=>1],
    ];
}
function products(): array {
    if (!installed()) return default_products();
    try {
        $rows=db()->query('SELECT id,slug,name,description,price,image,stock,active FROM products WHERE active=1 ORDER BY sort_order,id')->fetchAll();
        return $rows ?: default_products();
    } catch(Throwable $e){ return default_products(); }
}
function delivery_zones(): array {
    if (!installed()) return [
        ['id'=>1,'name'=>'Retiro personal en La Guardia','price'=>0,'requires_quote'=>0,'max_distance_km'=>null],
        ['id'=>2,'name'=>'Delivery hasta 4.º anillo','price'=>15,'requires_quote'=>0,'max_distance_km'=>null],
        ['id'=>3,'name'=>'Delivery zona central / 1.er anillo','price'=>25,'requires_quote'=>0,'max_distance_km'=>null],
        ['id'=>4,'name'=>'Otra zona (precio por confirmar)','price'=>0,'requires_quote'=>1,'max_distance_km'=>null],
    ];
    return db()->query('SELECT id,name,price,requires_quote,max_distance_km FROM delivery_zones WHERE active=1 ORDER BY sort_order,id')->fetchAll();
}
function public_order_id(): string { return 'DE-' . date('y') . '-' . strtoupper(substr(bin2hex(random_bytes(5)),0,7)); }
function public_token(): string { return bin2hex(random_bytes(24)); }
function admin_required(): void {
    if (empty($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
}
function upload_file(array $file, string $kind, array $allowedExt, int $maxBytes=5242880): ?string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) throw new RuntimeException('No se pudo subir el archivo.');
    if (($file['size'] ?? 0) > $maxBytes) throw new RuntimeException('El archivo excede el tamaño permitido.');
    $ext=strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext,$allowedExt,true)) throw new RuntimeException('Tipo de archivo no permitido.');
    $finfo=new finfo(FILEINFO_MIME_TYPE); $mime=$finfo->file($file['tmp_name']);
    $allowedMime=['image/jpeg','image/png','image/webp','application/pdf'];
    if (!in_array($mime,$allowedMime,true)) throw new RuntimeException('Contenido de archivo no permitido.');
    $dir=APP_ROOT . '/storage/uploads/' . $kind; if(!is_dir($dir)) mkdir($dir,0755,true);
    $name=bin2hex(random_bytes(18)).'.'.$ext;
    if(!move_uploaded_file($file['tmp_name'],$dir.'/'.$name)) throw new RuntimeException('No se pudo guardar el archivo.');
    return $kind.'/'.$name;
}
function whatsapp_url(string $phone, string $message): string {
    $digits=preg_replace('/\D+/','',$phone);
    return 'https://wa.me/'.$digits.'?text='.rawurlencode($message);
}
