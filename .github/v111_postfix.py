from pathlib import Path

path = Path('.github/security_regression.py')
text = path.read_text(encoding='utf-8')
text = text.replace(
    "require('_output/templates/public/warext_mc_server_add.html', ['kategori seçimi yeni form alanı açmaz', 'Ana sunucu adresi', 'Crossplay'])",
    "require('_output/templates/public/warext_mc_server_add.html', ['Görsel Kimlik', 'Ana sunucu adresi', 'Crossplay', 'discussion_thread_url'])"
)
path.write_text(text, encoding='utf-8')
