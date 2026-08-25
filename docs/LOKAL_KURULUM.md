# Lokal Kurulum ve Test Rehberi

Bu rehber Warext Studios | XenForo Minecraft Sunucu & Vote Sistemi geliştirme sürümünü Windows üzerinde lokal XenForo ve lokal Minecraft sunucusu ile birlikte test etmek içindir.

## 1. Gerekli bileşenler

- XenForo 2.3.x lokal kurulum
- PHP 8.2+; geliştirme ortamında PHP 8.4 önerilir
- MySQL veya MariaDB
- Java sürümünüzle uyumlu Paper/Purpur Minecraft sunucusu
- NuVotifier
- İsteğe bağlı olarak VotingPlugin veya başka bir Votifier listener/reward plugini

Önemli: Warext tarafında şu anda ayrı bir Minecraft `.jar` plugini yoktur. Vote köprüsü NuVotifier üzerinden kurulur. Oyun içi ödül vermek istiyorsanız NuVotifier olaylarını dinleyen VotingPlugin benzeri bir listener gerekir.

## 2. XenForo'yu lokal çalıştırma

Windows için Laragon gibi PHP/MySQL sunabilen bir lokal ortam kullanılabilir. XenForo dosyalarınızı örneğin şu klasöre kurabilirsiniz:

```text
C:\laragon\www\xenforo\
```

XenForo kurulumu tamamlandıktan sonra tarayıcıdan lokal forumunuzun açıldığını doğrulayın.

## 3. Add-on kaynaklarını XenForo'ya kopyalama

Repodaki şu klasörü:

```text
src/addons/Warext/MinecraftVote
```

lokal XenForo kurulumunuzdaki aynı konuma kopyalayın:

```text
C:\laragon\www\xenforo\src\addons\Warext\MinecraftVote
```

Sonuçta bu dosya mevcut olmalıdır:

```text
C:\laragon\www\xenforo\src\addons\Warext\MinecraftVote\addon.json
```

## 4. XenForo development mode

`src/config.php` dosyanıza lokal geliştirme ortamında aşağıdakileri ekleyin:

```php
$config['development']['enabled'] = true;
$config['development']['defaultAddOn'] = 'Warext/MinecraftVote';
```

Bunu canlı sitede kullanmayın.

## 5. Geliştirme sürümünü ilk kez kurma

Komut İstemi veya PowerShell'i XenForo kök klasöründe açın:

```powershell
cd C:\laragon\www\xenforo
php cmd.php xf:addon-install Warext/MinecraftVote
```

XenForo development output kullanmak isteyip istemediğinizi sorarsa development output seçeneğini kullanın. Bu repo şu anda `_output` geliştirme verilerini içerdiğinden lokal test için doğru yöntem CLI kurulumudur.

Gerekirse development verilerini ayrıca içeri aktarabilirsiniz:

```powershell
php cmd.php xf-dev:import --addon Warext/MinecraftVote
```

Kaynak kodunu güncelledikten sonra `_output` değişikliklerini lokal veritabanına tekrar almak için:

```powershell
php cmd.php xf-dev:import --addon Warext/MinecraftVote
```

Add-on sürümü/schema değiştiyse upgrade çalıştırın:

```powershell
php cmd.php xf:addon-upgrade Warext/MinecraftVote
```

## 6. Lokal özel IP erişimini açma

Üretim güvenliği nedeniyle add-on `127.0.0.1`, `localhost`, `192.168.x.x` ve diğer özel ağ hedeflerini varsayılan olarak engeller.

Lokal testte XenForo ACP > Setup > Options bölümünde Warext Minecraft Vote seçenek grubunu açın ve şu option'ı etkinleştirin:

```text
warextMcAllowPrivateHosts
```

Bu ayar yalnız lokal geliştirme/test ortamında açık kalmalıdır.

## 7. Lokal Minecraft sunucusu

Örnek klasör:

```text
C:\MinecraftTest\
```

Paper/Purpur sunucunuzu normal şekilde başlatın. Örnek Java sunucu portu:

```text
25565
```

XenForo ile Minecraft sunucusu aynı Windows bilgisayardaysa Warext sunucu kaydında Java adresi olarak şunu kullanabilirsiniz:

```text
127.0.0.1
```

Port:

```text
25565
```

## 8. NuVotifier kurulumu

NuVotifier JAR dosyasını Minecraft sunucunuzun `plugins` klasörüne koyun ve sunucuyu bir kez başlatıp kapatın.

NuVotifier tarafından oluşturulan `config.yml` dosyasında lokal test için dinleme adresi ve portu örneğin şöyle olabilir:

```yaml
host: 127.0.0.1
port: 8192

tokens:
  default: BURADAKI_OTOMATIK_TOKEN
```

NuVotifier'ın standart portu 8192'dir. Minecraft oyun portu olan 25565 ile NuVotifier portunu aynı yapmayın.

Sunucuyu yeniden başlatın. Konsolda Votifier/NuVotifier'ın aktif olduğunu ve 8192 portunu dinlediğini doğrulayın.

## 9. XenForo tarafında NuVotifier ayarı

Önce XenForo'da sunucu kaydınızı oluşturun ve ACP'den aktif/onaylı hale getirin.

Sunucu detay sayfasından `NuVotifier Ayarları` bölümüne girin.

Lokal tek makine kurulumu için:

```text
Etkin:       Evet
Host:        127.0.0.1
Port:        8192
Servis adı:  Warext
Token:       NuVotifier config.yml içindeki tokens.default değeri
```

Ayarları kaydedin.

`Test` seçeneği ile bağlantı testi yaptığınızda Warext sistemi NuVotifier V2 üzerinden `WarextTest` isimli bir test oyu gönderir.

## 10. NuVotifier'ı bağımsız test etme

Minecraft sunucu konsolunda veya OP hesabıyla NuVotifier'ın test komutunu da kullanabilirsiniz:

```text
/testvote TestOyuncu serviceName=Warext
```

Bu test NuVotifier'ın listener tarafını kontrol eder. Warext'in ACP/sunucu panelindeki test ise XenForo -> TCP 8192 -> NuVotifier zincirini test eder.

## 11. Oyun içi ödül istiyorsanız

NuVotifier oyları alır ancak kendi başına para, item, kasa anahtarı veya komut ödülü vermez.

Bunun için örneğin VotingPlugin kurabilirsiniz:

```text
plugins/
  Votifier.jar
  VotingPlugin.jar
```

VotingPlugin tarafında `Warext` servisinden gelen vote için istediğiniz komut/ödülü tanımlayın. Böylece akış şu olur:

```text
XenForo
  -> Warext Minecraft Vote
  -> NuVotifier V2 / 127.0.0.1:8192
  -> VotifierEvent
  -> VotingPlugin
  -> oyuncu ödülü
```

VotingPlugin kurmadan da Warext -> NuVotifier bağlantısını test edebilirsiniz; yalnız oyun içi ödül oluşmaz.

## 12. Gerçek oy testi

1. Minecraft sunucusunu açın.
2. Lokal XenForo'yu açın.
3. `warextMcAllowPrivateHosts` açık olsun.
4. XenForo'da sunucu adresini `127.0.0.1:25565` olarak kaydedin.
5. ACP'den sunucuyu onaylayın.
6. NuVotifier ayarlarını `127.0.0.1:8192` ve doğru token ile kaydedin.
7. Önce `Test` ile bağlantıyı doğrulayın.
8. Sunucu detayında `Oy Ver` butonuna basın.
9. Minecraft kullanıcı adınızı girin.
10. Oy Warext kuyruğuna girer.
11. Vote delivery job çalıştığında NuVotifier'a gönderilir.
12. Listener kuruluysa ödül uygulanır.

## 13. Job ve cron testleri

Lokal ortamda XenForo cron tetiklemeleri gerçek trafik az olduğunda gecikebilir. ACP'deki cron entries alanından ilgili Warext cronlarını manuel çalıştırabilirsiniz.

Özellikle kontrol edilmesi gerekenler:

- Sunucu durum/ping kontrolü
- Vote delivery queue
- Vote counter rebuild
- Ranking rebuild
- Achievement işlemleri

Vote gönderdiğinizde sistem ayrıca delivery job'ını unique job olarak kuyruğa almaya çalışır.

## 14. Sorun giderme

### `127.0.0.1 güvenli değil / private host engellendi`

`warextMcAllowPrivateHosts` açık değildir. Lokal test için açın.

### `Connection refused`

NuVotifier çalışmıyor, 8192 portunu dinlemiyor veya host/port yanlış.

Windows üzerinde kontrol:

```powershell
netstat -ano | findstr :8192
```

### `Hedef sunucu NuVotifier Protocol V2 yanıtı vermedi`

Yanlış porta bağlanıyor olabilirsiniz veya NuVotifier V2 yerine başka bir servis cevap veriyor olabilir.

### `signature / token` hatası

XenForo'daki token ile NuVotifier `tokens.default` değeri aynı değildir.

### Oy geliyor ama ödül yok

Bu NuVotifier bağlantı hatası olmak zorunda değildir. NuVotifier bir vote listener değildir. VotingPlugin/SuperbVote benzeri bir ödül listener'ı kurup yapılandırın.

### Ping çalışıyor ama vote gitmiyor

Minecraft status portu ile NuVotifier portu farklıdır:

```text
Minecraft Java: 25565
NuVotifier:      8192
```

İki ayarı birbirine karıştırmayın.

## 15. Lokal güvenlik notu

`warextMcAllowPrivateHosts` üretim sunucusunda kapalı tutulmalıdır. Bu seçenek yalnız localhost/LAN geliştirme senaryosu içindir.
