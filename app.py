import os
from flask import Flask, render_template, request, jsonify
from datetime import datetime

app = Flask(__name__, template_folder='templates', static_folder='static')

# Log dosyasının kaydedileceği konum
current_dir = os.path.dirname(os.path.abspath(__file__))
log_file_path = os.path.join(current_dir, 'ziyaretciler.txt')

@app.route('/')
def home():
    try:
        # Ziyaretçinin IP adresini ve şu anki saati alıp dosyaya yazıyoruz
        ziyaretci_ip = request.headers.get('X-Forwarded-For', request.remote_addr)
        su_an = datetime.now().strftime('%d-%m-%Y %H:%M:%S')
        
        with open(log_file_path, 'a', encoding='utf-8') as f:
            f.write(f"Zaman: {su_an} | IP: {ziyaretci_ip} | Islem: Ana sayfaya girildi\n")
            
    except Exception as e:
        print("Log yazilirken bir hata olustu:", e)
        
    return render_template('index.html')

@app.route('/loglar')
def loglari_goster():
    try:
        if not os.path.exists(log_file_path):
            return jsonify({"son_ziyaretler": [], "mesaj": "Henuz kaydedilmis bir log bulunamadi."})
            
        with open(log_file_path, 'r', encoding='utf-8') as f:
            satirlar = f.readlines()
            
        log_listesi = [satir.strip() for satir in satirlar][-50:][::-1]
        return jsonify({"son_ziyaretler": log_listesi})
        
    except Exception as e:
        return jsonify({"hata": f"Loglar okunurken hata olustu: {str(e)}"})

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000)
