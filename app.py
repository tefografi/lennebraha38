import os
from flask import Flask
from flask_sqlalchemy import SQLAlchemy
from dotenv import load_dotenv
import sys
import logging

# Log seviyesini en temele çekip terminale basılmasını zorunlu kılıyoruz
logging.basicConfig(stream=sys.stdout, level=logging.DEBUG)

try:
    # Veritabanı ve uygulama ayağa kalkma kodların buraya gelecek
    uri = os.getenv("DATABASE_URL")
    print(f"DEBUG: Çekilen URI Değeri: {uri}", flush=True) # Adresin gelip gelmediğini logda göreceğiz
except Exception as e:
    print(f"KRİTİK BAŞLANGIÇ HATASI: {str(e)}", flush=True)
    logging.exception("Uygulama başlatılırken hata oluştu!")
# .env dosyasını yükle
load_dotenv()

app = Flask(__name__)

# .env dosyasından adresi çek
uri = os.getenv("DATABASE_URL")

# KONTROL: Eğer uri boş geldiyse terminale uyarı bas ve sistemi durdur
if not uri:
    raise ValueError(
        "HATA: 'DATABASE_URL' bulunamadı! \n"
        "Lütfen proje klasöründe '.env' dosyası olduğundan ve içinde "
        "DATABASE_URL=postgres://... şeklinde adresinin yazılı olduğundan emin ol."
    )

# Postgresql düzeltmesi
if uri.startswith("postgres://"):
    uri = uri.replace("postgres://", "postgresql://", 1)

# Doğru yapılandırma
app.config['SQLALCHEMY_DATABASE_URI'] = uri
app.config['SQLALCHEMY_TRACK_MODIFICATIONS'] = False

db = SQLAlchemy(app)

# Örnek bir tablo yapısı
class Kullanici(db.Model):
    __tablename__ = 'kullanicilar'
    id = db.Column(db.Integer, primary_key=True)
    isim = db.Column(db.String(80), nullable=False)

with app.app_context():
    db.create_all()

@app.route('/')
def ana_sayfa():
    return "Neon Bağlantısı Başarılı!"

if __name__ == '__main__':
    app.run(debug=True)
