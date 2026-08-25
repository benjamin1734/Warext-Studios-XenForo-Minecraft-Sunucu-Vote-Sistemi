# WarextVerify

Warext Minecraft Vote XenForo eklentisinin güvenilen Minecraft hesap doğrulama köprüsüdür.

1. JAR dosyasını yalnız doğrulama için güvenilen Paper sunucusunun `plugins` klasörüne koyun.
2. Sunucuyu bir kez çalıştırın.
3. XenForo ACP > Minecraft Hesap Doğrulama Köprüsü bölümündeki callback URL ve bridge secret değerlerini `plugins/WarextVerify/config.yml` dosyasına yazın.
4. Sunucuyu yeniden başlatın.
5. Kullanıcı XenForo'da oluşturduğu 16 karakterlik kodu oyunda `/warextverify KOD` şeklinde kullanır.

Bridge secret yalnız güvenilen doğrulama sunucusunda tutulmalıdır. Sunucu sızdırılırsa ACP'den secret yenilenmelidir.
