from pathlib import Path

setup = Path('src/addons/Warext/MinecraftVote/Setup.php')
s = setup.read_text(encoding='utf-8')
s = s.replace("'verification_token_date' => ['int', None, 'verification_token']", "'verification_token_date' => ['int', null, 'verification_token']")
s = s.replace("'verified_date' => ['int', None, 'verification_token_date']", "'verified_date' => ['int', null, 'verification_token_date']")
setup.write_text(s, encoding='utf-8')

reg = Path('.github/security_regression.py')
r = reg.read_text(encoding='utf-8')
marker = "    if 'assertPostOnly()' in text:\n        raise SystemExit(f'{path}: genel POST-only hata ekranı oluşturabilecek assertPostOnly kaldı')\n\n"
insert = marker + "    if 'None' in text:\n        raise SystemExit(f'{path}: PHP içinde Python None sabiti bulundu')\n\n"
if "PHP içinde Python None" not in r:
    r = r.replace(marker, insert, 1)
reg.write_text(r, encoding='utf-8')

Path('.github/v104_runtime_patch.py').unlink(missing_ok=True)
Path('.github/workflows/v104-runtime-patch.yml').unlink(missing_ok=True)
