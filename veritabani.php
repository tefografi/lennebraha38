<?php
header('Content-Type: application/json; charset=utf-8');

// --- CORS (ortam değişkeninden al, yoksa varsayılan) ---
$allowedOrigin = getenv('CORS_ORIGIN') ?: 'https://son-hali-gercekten.vercel.app';
header("Access-Control-Allow-Origin: $allowedOrigin");
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

function logMsg(string $lvl, string $msg): void {
    error_log(date('Y-m-d H:i:s') . " [$lvl] $msg");
}

// --- SUPABASE BAĞLANTI BİLGİLERİ (ortam değişkenlerinden) ---
define('DB_HOST', getenv('DB_HOST') ?: 'db.shvdsyclykgflyzgpdsi.supabase.co');
define('DB_PORT', getenv('DB_PORT') ?: '5432');
define('DB_NAME', getenv('DB_NAME') ?: 'postgres');
define('DB_USER', getenv('DB_USER') ?: 'postgres');
define('DB_PASS', getenv('DB_PASS') ?: '');

function jsonError(string $msg, int $code = 500): void {
    http_response_code($code);
    echo json_encode(['error' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

function getDB(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    try {
        $pdo = new PDO(
            'pgsql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME,
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
        logMsg('INFO', 'Supabase PostgreSQL bağlantısı başarılı.');
    } catch (PDOException $e) {
        logMsg('ERROR', 'DB hatası: ' . $e->getMessage());
        jsonError('Veritabanı bağlantısı kurulamadı!', 500);
    }
    return $pdo;
}

function createTables(): void {
    try {
        $pdo = getDB();
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS kullanicilar (
                id          SERIAL PRIMARY KEY,
                isim        VARCHAR(80)  NOT NULL,
                eposta      VARCHAR(120) NOT NULL UNIQUE,
                bio         TEXT,
                yetenekler  VARCHAR(255),
                sehir       VARCHAR(100),
                musaitlik   VARCHAR(50)  DEFAULT 'Müsait',
                avatar      VARCHAR(500),
                olusturulma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS ilanlar (
                id              SERIAL PRIMARY KEY,
                baslik          VARCHAR(255) NOT NULL,
                aciklama        TEXT,
                kategori        VARCHAR(50),
                sehir           VARCHAR(100),
                etiketler       VARCHAR(255),
                kisi            VARCHAR(50),
                tip             VARCHAR(50),
                durum           VARCHAR(20)  DEFAULT 'Açık',
                kullanici_id    INT,
                olusturulma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
        ");
        logMsg('INFO', 'Tablolar hazır.');
    } catch (PDOException $e) {
        logMsg('ERROR', 'Tablo oluşturma hatası: ' . $e->getMessage());
    }
}

// --- TABLOLARI OLUŞTUR ---
createTables();

// --- ROUTING ---
$method = $_SERVER['REQUEST_METHOD'];
$path   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Basit router
$routes = [
    'GET'  => [],
    'POST' => [],
    'DELETE' => [],
];

// --- KULLANICILAR ---
$routes['GET']['/api/kullanicilar'] = function () {
    $pdo   = getDB();
    $stmt  = $pdo->query("SELECT * FROM kullanicilar ORDER BY id DESC");
    $users = $stmt->fetchAll();
    echo json_encode($users, JSON_UNESCAPED_UNICODE);
};

$routes['POST']['/api/kullanicilar'] = function () {
    $data   = json_decode(file_get_contents('php://input'), true);
    $pdo    = getDB();
    $stmt   = $pdo->prepare("INSERT INTO kullanicilar (isim, eposta, bio, yetenekler, sehir, musaitlik, avatar) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $data['isim']       ?? '',
        $data['eposta']     ?? '',
        $data['bio']        ?? '',
        $data['yetenekler'] ?? '',
        $data['sehir']      ?? '',
        $data['musaitlik']  ?? 'Müsait',
        $data['avatar']     ?? '',
    ]);
    $id = $pdo->lastInsertId();
    echo json_encode(['id' => (int)$id, 'mesaj' => 'Kullanıcı eklendi.'], JSON_UNESCAPED_UNICODE);
};

$routes['DELETE']['/api/kullanicilar'] = function () {
    $data = json_decode(file_get_contents('php://input'), true);
    $pdo  = getDB();
    $stmt = $pdo->prepare("DELETE FROM kullanicilar WHERE id = ?");
    $stmt->execute([$data['id'] ?? 0]);
    echo json_encode(['mesaj' => 'Kullanıcı silindi.'], JSON_UNESCAPED_UNICODE);
};

// --- İLANLAR ---
$routes['GET']['/api/ilanlar'] = function () {
    $pdo   = getDB();
    $stmt  = $pdo->query("SELECT * FROM ilanlar ORDER BY id DESC");
    $items = $stmt->fetchAll();
    echo json_encode($items, JSON_UNESCAPED_UNICODE);
};

$routes['POST']['/api/ilanlar'] = function () {
    $data = json_decode(file_get_contents('php://input'), true);
    $pdo  = getDB();
    $stmt = $pdo->prepare("INSERT INTO ilanlar (baslik, aciklama, kategori, sehir, etiketler, kisi, tip, durum, kullanici_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $data['baslik']       ?? '',
        $data['aciklama']     ?? '',
        $data['kategori']     ?? '',
        $data['sehir']        ?? '',
        $data['etiketler']    ?? '',
        $data['kisi']         ?? '',
        $data['tip']          ?? '',
        $data['durum']        ?? 'Açık',
        $data['kullanici_id'] ?? null,
    ]);
    $id = $pdo->lastInsertId();
    echo json_encode(['id' => (int)$id, 'mesaj' => 'İlan eklendi.'], JSON_UNESCAPED_UNICODE);
};

$routes['DELETE']['/api/ilanlar'] = function () {
    $data = json_decode(file_get_contents('php://input'), true);
    $pdo  = getDB();
    $stmt = $pdo->prepare("DELETE FROM ilanlar WHERE id = ?");
    $stmt->execute([$data['id'] ?? 0]);
    echo json_encode(['mesaj' => 'İlan silindi.'], JSON_UNESCAPED_UNICODE);
};

// --- ROUTER ÇALIŞTIR ---
if (isset($routes[$method][$path])) {
    try {
        $routes[$method][$path]();
    } catch (PDOException $e) {
        logMsg('ERROR', 'Route hatası: ' . $e->getMessage());
        jsonError('Sunucu hatası oluştu.', 500);
    }
} else {
    jsonError('Endpoint bulunamadı.', 404);
}
