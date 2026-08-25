# Warext Studios | XenForo Minecraft Sunucu & Vote Sistemi

XenForo için Minecraft sunucu listeleme, keşif, oylama, NuVotifier entegrasyonu, sıralama ve sunucu yönetim sistemi.

## Sürüm

0.15.0 Beta

## Kurulum

Kurulum ZIP dosyaları yalnızca GitHub Releases bölümünde yayımlanır.

Güncel paket: `v0.15.0`

XenForo ACP üzerinden Add-ons bölümündeki arşiv yükleme alanından ZIP dosyasını seçerek kurabilirsiniz.

## Özellikler

- Minecraft Java ve Bedrock sunucu listeleme
- Sunucu durum ve oyuncu istatistikleri
- Sunucu adı/adres/açıklama araması
- Kategori, ülke, sürüm ve oyun modu filtreleri
- Minimum online oyuncu, doğrulama, premium ve crack filtreleri
- Popüler, yükselişte, oy, oyuncu, uptime ve yeni sunucu sıralamaları
- Güvenli vote sistemi
- NuVotifier entegrasyonu
- Vote kuyruğu ve yeniden gönderim
- Sunucu sahipliği, doğrulama ve güvenli sahiplik devri
- Minecraft hesabı bağlantısı
- Vote streak ve sunucu başarımları
- Yorumlar, favoriler ve bildirimler
- Kullanıcı sunucu raporlama ve ACP rapor moderasyonu
- XenForo kullanıcı grubu izinleri
- Ham IP saklamayan vote request rate-limit sistemi
- Organik sıralamadan bağımsız sponsorlu listelemeler
- Şüpheli oy inceleme ve audit sistemi
- Gelişmiş sunucu sahibi ve ekip paneli
- Kritik işlemler için audit kayıtları
- XenForo ACP moderasyon araçları

## XenForo Add-on ID

`Warext/MinecraftVote`

## Gereksinimler

- XenForo 2.3+
- PHP 8.2+
- MySQL 8.0+ veya MariaDB eşdeğeri
- NuVotifier
- İsteğe bağlı VotingPlugin veya uyumlu başka bir vote listener

## Lokal geliştirme

`docs/LOKAL_KURULUM.md`

Kaynak geliştirme yapısı XenForo `_output` verilerini içerir. Kurulum paketleri build sırasında `_data` master data ile oluşturulur ve yalnızca GitHub Releases bölümünde yayımlanır.
