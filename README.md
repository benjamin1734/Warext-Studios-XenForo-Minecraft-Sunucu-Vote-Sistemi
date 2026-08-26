# Warext Studios | XenForo Minecraft Sunucu & Vote Sistemi

XenForo 2.3 için Minecraft sunucu listeleme, keşif, güvenli oy verme, NuVotifier ödül teslimatı, sunucu sahipliği, ekip yönetimi, sıralama, analiz, sezon, başarım, değerlendirme, favori, karşılaştırma, sponsor ve moderasyon sistemi.

## Hazır Kurulum ZIP

GitHub **Releases** bölümündeki `Warext-MinecraftVote-1.0.0.zip` dosyası doğrudan XenForo Admin CP içinden kurulabilir. ZIP'i açmayın ve GitHub **Code > Download ZIP** arşivini kurulum paketi olarak kullanmayın.

Kurulum: **Admin CP > Add-ons > Install/upgrade from archive** > `Warext-MinecraftVote-1.0.0.zip`.

## 1.0.0 Stable

- Java, Bedrock ve Crossplay sunucu kayıt/listeme/detay sistemi
- SRV destekli güvenli sunucu ping ve uptime takibi
- Sunucu sahipliği doğrulama, ekip ve yetki yönetimi
- Oy cooldown, IP/UA HMAC fingerprint, hız limiti ve fraud score
- XenForo CAPTCHA entegrasyonu
- İsteğe bağlı yalnız doğrulanmış Minecraft hesabıyla oy verme
- NuVotifier v2 ve dayanıklı vote delivery queue/retry sistemi
- ACP vote queue, şüpheli oy ve sistem sağlık ekranları
- Minecraft/XenForo hesap bağlantısı
- Sezon, streak, başarım, sıralama, trend ve analitik
- Değerlendirme, favori, sunucu güncellemeleri ve karşılaştırma
- Sponsorlu sıralama ve isteğe bağlı XenForo Payment Profile ile 7/30 günlük sponsor satışı
- Audit ve raporlama/moderasyon araçları
- Salt okunur, varsayılan kapalı JSON API
- HTTPS/HMAC webhook, redirect ve private-network koruması
- XML sitemap ve detay sayfası SEO açıklamaları
- Eski ping kayıtlarını temizleyen ve takılı vote delivery kayıtlarını kurtaran bakım cron'u
- PHP 8.2, 8.3 ve 8.4 CI doğrulaması

## Gereksinimler

- XenForo 2.3.0+
- PHP 8.2+
- OpenSSL PHP extension

Lokal Minecraft/NuVotifier testi için ACP'deki özel ağ bağlantısı seçeneği kontrollü olarak açılabilir. Canlı ortamda varsayılan kapalı bırakılması önerilir.
