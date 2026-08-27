# Lokal Test

## Gereksinimler

- XenForo 2.3.x
- PHP 8.2+
- MySQL veya MariaDB
- Paper/Purpur
- NuVotifier
- Oyun içi ödül için isteğe bağlı VotingPlugin veya uyumlu listener

## XenForo

Kaynak klasörü:

```text
src/addons/Warext/MinecraftVote
```

Lokal XenForo kurulumunda aynı konuma kopyalayın. Development mode kullanıyorsanız:

```php
$config['development']['enabled'] = true;
$config['development']['defaultAddOn'] = 'Warext/MinecraftVote';
```

Kurulum ve veri aktarımı:

```powershell
php cmd.php xf:addon-install Warext/MinecraftVote
php cmd.php xf-dev:import --addon Warext/MinecraftVote
```

Sürüm yükseltmesinde:

```powershell
php cmd.php xf:addon-upgrade Warext/MinecraftVote
```

## Lokal Ağ

ACP seçeneklerinden `warextMcAllowPrivateHosts` yalnız lokal test için etkinleştirilmelidir.

Örnek Java sunucusu:

```text
127.0.0.1:25565
```

Örnek NuVotifier:

```yaml
host: 127.0.0.1
port: 8192

tokens:
  default: TOKEN
```

Warext NuVotifier ayarlarında host `127.0.0.1`, port `8192` ve aynı token kullanılmalıdır.

NuVotifier testi:

```text
/testvote TestOyuncu serviceName=Warext
```

NuVotifier oyu teslim eder; oyun içi para, eşya veya komut ödülü için VotingPlugin benzeri bir listener gerekir.

## Oy Testi

1. Minecraft ve XenForo'yu çalıştırın.
2. Sunucuyu `127.0.0.1:25565` ile ekleyip onaylayın.
3. NuVotifier ayarlarını `127.0.0.1:8192` ve doğru token ile kaydedin.
4. Bağlantı testini çalıştırın.
5. Sunucu detayından oy verin.
6. Vote queue ve NuVotifier teslimatını kontrol edin.

## Sorun Giderme

- `Connection refused`: NuVotifier çalışmıyor veya host/port yanlış.
- Protocol V2 hatası: yanlış porta veya uyumsuz Votifier servisine bağlanılıyor.
- Token/signature hatası: XenForo ve NuVotifier tokenları eşleşmiyor.
- Oy ulaşıyor fakat ödül yok: ödül listener'ı kurulmamış veya yapılandırılmamış.
- Ping çalışıyor fakat oy gitmiyor: Minecraft portu `25565`, NuVotifier portu `8192` olarak ayrı kontrol edilmelidir.

`warextMcAllowPrivateHosts` canlı ortamda kapalı tutulmalıdır.
