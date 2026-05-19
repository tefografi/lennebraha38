import os
from datetime import datetime
from flask import Flask, render_template, request, redirect, url_for, session
from flask_sqlalchemy import SQLAlchemy
from werkzeug.security import generate_password_hash, check_password_hash

app = Flask(__name__)
app.config['SECRET_KEY'] = 'teknofest_api_projem_2026'

# --- SUPABASE VERİTABANI BAĞLANTI AYARI ---
# Faruk, aşağıdaki satırda PROJE_KODUNU_BURAYA_YAZ ve SIFRENIZI_BURAYA_YAZ yerlerini kendi bilgilerinle doldur!
app.config['SQLALCHEMY_DATABASE_URI'] = 'postgresql+psycopg2://postgres.eqgqbkqjszkiokktamhi:lennebraha38@aws-0-eu-central-1.pooler.supabase.com:6543/postgres'
app.config['SQLALCHEMY_TRACK_MODIFICATIONS'] = False

db = SQLAlchemy(app)

# --- 1. TABLO: KULLANICILAR ---
class User(db.Model):
    __tablename__ = 'users'
    id = db.Column(db.Integer, primary_key=True)
    username = db.Column(db.String(80), unique=True, nullable=False)
    password = db.Column(db.String(200), nullable=False)

# --- 2. TABLO: OTOMATİK SİTE LOGLARI (Sitede Yapılan Her Şey) ---
class SiteLog(db.Model):
    __tablename__ = 'site_logs'
    id = db.Column(db.Integer, primary_key=True)
    ip_address = db.Column(db.String(50))      # İşlemi yapanın IP adresi
    username = db.Column(db.String(80))        # Giriş yapmışsa kullanıcı adı
    action = db.Column(db.String(200))         # Hangi sayfaya girdi veya ne yaptı?
    timestamp = db.Column(db.DateTime, default=datetime.utcnow) # Ne zaman yaptı?

# --- SİHİRLİ DOKUNUŞ: HER HAREKETİ OTOMATİK KAYDET ---
# Kullanıcı hiçbir şey fark etmeden, tıkladığı her link arka planda veritabanına yazılır
@app.before_request
def log_every_action():
    # CSS, JS veya resim gibi statik dosyaların isteklerini loglamasın diye filtreliyoruz
    if request.endpoint and 'static' not in request.endpoint:
        aktif_user = session.get('username', 'Giriş Yapılmamış Ziyaretçi')
        sayfa = f"Sayfa İstendi: {request.path} [{request.method}]"
        
        yeni_log = SiteLog(
            ip_address=request.remote_addr,
            username=aktif_user,
            action=sayfa
        )
        db.session.add(yeni_log)
        db.session.commit()

# --- SİTE ROTASI (GİRİŞ - ÇIKIŞ - KAYIT) ---

@app.route('/')
def index():
    if 'logged_in' in session:
        return f"Hoş geldiniz, {session['username']}! Sistem şu an yaptığınız her hareketi Supabase veritabanına logluyor. <a href='/logout'>Çıkış Yap</a>"
    return redirect(url_for('login_page'))

@app.route('/login', methods=['GET', 'POST'])
def login_page():
    if request.method == 'POST':
        username = request.form.get('username')
        password = request.form.get('password')
        
        user = User.query.filter_by(username=username).first()
        
        if user and check_password_hash(user.password, password):
            session['logged_in'] = True
            session['username'] = user.username
            
            # Başarılı giriş işlemini özel olarak loglayalım
            ozel_log = SiteLog(ip_address=request.remote_addr, username=username, action="Başarılı Giriş Yaptı")
            db.session.add(ozel_log)
            db.session.commit()
            
            return redirect(url_for('index'))
        else:
            return "Kullanıcı adı veya şifre yanlış!", 401
            
    try: return render_template('index.html')
    except: return render_template('login.html')

@app.route('/register', methods=['POST'])
def register():
    username = request.form.get('username')
    password = request.form.get('password')
    
    if not username or not password: return "Boş bırakılamaz!", 400
        
    user_exists = User.query.filter_by(username=username).first()
    if user_exists: return "Bu kullanıcı adı zaten alınmış!", 400
        
    hashed_password = generate_password_hash(password, method='pbkdf2:sha256')
    new_user = User(username=username, password=hashed_password)
    db.session.add(new_user)
    
    # Yeni hesap açma işlemini loglayalım
    ozel_log = SiteLog(ip_address=request.remote_addr, username=username, action="Yeni Hesap Oluşturdu")
    db.session.add(ozel_log)
    
    db.session.commit()
    return "Kayıt başarılı! Şimdi giriş yapabilirsiniz.", 201

@app.route('/logout')
def logout():
    if 'username' in session:
        ozel_log = SiteLog(ip_address=request.remote_addr, username=session['username'], action="Sistemden Çıkış Yaptı")
        db.session.add(ozel_log)
        db.session.commit()
    session.clear()
    return redirect(url_for('login_page'))

if __name__ == '__main__':
    with app.app_context():
        db.create_all() # Supabase üzerinde tabloları (users ve site_logs) otomatik olarak oluşturur!
    app.run(host='0.0.0.0', port=5000, debug=True)
