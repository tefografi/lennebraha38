<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

function logMsg(string $lvl, string $msg): void {
    error_log(date('Y-m-d H:i:s') . " [$lvl] $msg");
}

// --- SUPABASE BAĞLANTI BİLGİLERİ ---
define('DB_HOST', 'db.shvdsyclykgflyzgpdsi.supabase.co'); 
define('DB_PORT', '5432');                                
define('DB_NAME', 'postgres');                            
define('DB_USER', 'postgres');                            
define('DB_PASS', 'Lennebraha38.'); // <--- BURAYA KENDİ ŞİFRENİ YAZ!

function getDB(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    try {
        // Tamamen PostgreSQL (pgsql) sürücüsüne uyumlu bağlantı
        $pdo = new PDO(
            'pgsql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME,
            DB_USER, DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
        logMsg('INFO', 'Supabase PostgreSQL bağlantısı başarılı.');
    } catch (PDOException $e) {
        logMsg('ERROR', 'DB hatası: ' . $e->getMessage());
        // Hatayı net görebilmek için teknik mesajı ekrana basıyoruz
        jsonError('Veritabanı bağlantısı kurulamadı! Detay: ' . $e->getMessage(), 500);
    }
    return $pdo;
}

function createTables(): void {
    try {
        $pdo = getDB();
        // PostgreSQL uyumlu SERIAL veri tipli tablolar
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS kullanicilar (
                id     SERIAL PRIMARY KEY,
                isim   VARCHAR(80)  NOT NULL,
                eposta VARCHAR(120) NOT NULL UNIQUE
            );

            CREATE TABLE IF NOT EXISTS ilanlar (
                id           SERIAL PRIMARY KEY,
                baslik       VARCHAR(255) NOT NULL,
                aciklama     TEXT,
                kategori     VARCHAR(50),
                sehir        VARCHAR(100),
                etiketler    VARCHAR(255),
                kisi         VARCHAR(50),
                tip          VARCHAR(50),
                kullanici_id INT,
                olusturulma
