# Warext Studios | XenForo Minecraft Sunucu & Vote Sistemi

XenForo için Minecraft sunucu listeleme, keşif, oylama, NuVotifier entegrasyonu, sıralama ve sunucu yönetim sistemi.

## Durum

Geliştirme sürümü: 0.12.1 Beta

## Kurulum ZIP

`releases/Warext-MinecraftVote-0.12.1-Beta.zip`

XenForo ACP üzerinden Add-ons bölümündeki arşiv yükleme alanından ZIP dosyasını seçerek kurabilirsiniz.

## Özellikler

- Minecraft Java ve Bedrock sunucu listeleme
- Sunucu durum ve oyuncu istatistikleri
- Güvenli vote sistemi
- NuVotifier entegrasyonu
- Vote kuyruğu ve yeniden gönderim
- Sunucu sahipliği, doğrulama ve güvenli sahiplik devri
- Minecraft hesabı bağlantısı
- Vote streak ve sunucu başarımları
- Yorumlar, favoriler ve bildirimler
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
- NuVotifier (oyların Minecraft sunucusuna teslimi için)
- İsteğe bağlı VotingPlugin veya uyumlu başka bir vote listener

## Lokal geliştirme

Windows/XenForo/Paper-NuVotifier lokal test akışı için:

`docs/LOKAL_KURULUM.md`

Kaynak geliştirme yapısı XenForo `_output` verilerini içerir. `releases/` altındaki paket ise `_data` master data üretilmiş doğrudan kurulum paketidir.
