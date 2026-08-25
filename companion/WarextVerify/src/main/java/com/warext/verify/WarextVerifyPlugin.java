package com.warext.verify;

import org.bukkit.command.Command;
import org.bukkit.command.CommandSender;
import org.bukkit.entity.Player;
import org.bukkit.plugin.java.JavaPlugin;

import javax.crypto.Mac;
import javax.crypto.spec.SecretKeySpec;
import java.io.InputStream;
import java.net.HttpURLConnection;
import java.net.URI;
import java.net.URLEncoder;
import java.nio.charset.StandardCharsets;
import java.security.SecureRandom;
import java.time.Instant;
import java.util.HexFormat;
import java.util.Locale;
import java.util.UUID;

public final class WarextVerifyPlugin extends JavaPlugin {
    private static final SecureRandom RANDOM = new SecureRandom();

    @Override
    public void onEnable() {
        saveDefaultConfig();
        if (getCommand("warextverify") == null) {
            throw new IllegalStateException("warextverify komutu plugin.yml içinde bulunamadı");
        }
    }

    @Override
    public boolean onCommand(CommandSender sender, Command command, String label, String[] args) {
        if (!(sender instanceof Player player)) {
            sender.sendMessage("Bu komut yalnızca oyuncular tarafından kullanılabilir.");
            return true;
        }

        if (!player.hasPermission("warextverify.use")) {
            player.sendMessage("Bu komutu kullanma izniniz yok.");
            return true;
        }

        if (args.length != 1) {
            player.sendMessage("Kullanım: /warextverify <kod>");
            return true;
        }

        String code = args[0].trim().toUpperCase(Locale.ROOT);
        if (!code.matches("^W[A-Z0-9]{15}$")) {
            player.sendMessage("Doğrulama kodu biçimi geçersiz.");
            return true;
        }

        String endpoint = getConfig().getString("endpoint", "").trim();
        String secret = getConfig().getString("bridge-secret", "").trim();
        boolean allowInsecure = getConfig().getBoolean("allow-insecure-http", false);
        int connectTimeout = clamp(getConfig().getInt("connect-timeout-ms", 5000), 1000, 15000);
        int readTimeout = clamp(getConfig().getInt("read-timeout-ms", 5000), 1000, 15000);

        if (endpoint.isEmpty() || secret.isEmpty()) {
            player.sendMessage("WarextVerify henüz yapılandırılmamış. Sunucu yöneticisine bildirin.");
            return true;
        }

        URI uri;
        try {
            uri = URI.create(endpoint);
        } catch (IllegalArgumentException e) {
            player.sendMessage("Doğrulama endpoint adresi geçersiz.");
            return true;
        }

        String scheme = uri.getScheme() == null ? "" : uri.getScheme().toLowerCase(Locale.ROOT);
        if (!scheme.equals("https") && !(allowInsecure && scheme.equals("http"))) {
            player.sendMessage("Doğrulama endpoint'i HTTPS kullanmalıdır.");
            return true;
        }

        String username = player.getName();
        String minecraftUuid = player.getUniqueId().toString().toLowerCase(Locale.ROOT);
        long timestamp = Instant.now().getEpochSecond();
        String nonce = randomNonce();

        player.sendMessage("Minecraft hesabınız doğrulanıyor...");

        getServer().getScheduler().runTaskAsynchronously(this, () -> {
            VerificationResult result = sendVerification(
                uri,
                secret,
                timestamp,
                nonce,
                code,
                minecraftUuid,
                username,
                connectTimeout,
                readTimeout
            );

            getServer().getScheduler().runTask(this, () -> {
                if (!player.isOnline()) {
                    return;
                }
                if (result.success()) {
                    player.sendMessage("Minecraft hesabınız XenForo hesabınızla doğrulandı.");
                } else {
                    player.sendMessage("Doğrulama başarısız: " + result.message());
                }
            });
        });

        return true;
    }

    private VerificationResult sendVerification(
        URI endpoint,
        String secret,
        long timestamp,
        String nonce,
        String code,
        String minecraftUuid,
        String username,
        int connectTimeout,
        int readTimeout
    ) {
        HttpURLConnection connection = null;
        try {
            String canonical = timestamp + "\n" + nonce + "\n" + code + "\n" + minecraftUuid + "\n" + username;
            String signature = hmacSha256(secret, canonical);
            String body = form(
                "timestamp", Long.toString(timestamp),
                "nonce", nonce,
                "code", code,
                "minecraft_uuid", minecraftUuid,
                "minecraft_username", username,
                "signature", signature
            );

            connection = (HttpURLConnection) endpoint.toURL().openConnection();
            connection.setRequestMethod("POST");
            connection.setConnectTimeout(connectTimeout);
            connection.setReadTimeout(readTimeout);
            connection.setDoOutput(true);
            connection.setUseCaches(false);
            connection.setRequestProperty("Content-Type", "application/x-www-form-urlencoded; charset=UTF-8");
            connection.setRequestProperty("Accept", "text/html, application/json;q=0.9, */*;q=0.8");
            connection.setRequestProperty("User-Agent", "WarextVerify/0.16.0");

            byte[] bytes = body.getBytes(StandardCharsets.UTF_8);
            connection.setFixedLengthStreamingMode(bytes.length);
            connection.getOutputStream().write(bytes);

            int status = connection.getResponseCode();
            InputStream stream = status >= 200 && status < 400
                ? connection.getInputStream()
                : connection.getErrorStream();
            if (stream != null) {
                try (stream) {
                    stream.readNBytes(8192);
                }
            }

            if (status >= 200 && status < 300) {
                return new VerificationResult(true, "ok");
            }
            if (status == 400) {
                return new VerificationResult(false, "kod geçersiz, kullanılmış veya süresi dolmuş olabilir");
            }
            if (status == 403) {
                return new VerificationResult(false, "bridge anahtarı veya bridge durumu geçersiz");
            }
            return new VerificationResult(false, "XenForo HTTP " + status + " yanıtı verdi");
        } catch (Exception e) {
            getLogger().warning("Minecraft hesap doğrulama isteği başarısız: " + e.getClass().getSimpleName() + ": " + e.getMessage());
            return new VerificationResult(false, "XenForo doğrulama servisine ulaşılamadı");
        } finally {
            if (connection != null) {
                connection.disconnect();
            }
        }
    }

    private static String hmacSha256(String secret, String canonical) throws Exception {
        Mac mac = Mac.getInstance("HmacSHA256");
        mac.init(new SecretKeySpec(secret.getBytes(StandardCharsets.UTF_8), "HmacSHA256"));
        return HexFormat.of().formatHex(mac.doFinal(canonical.getBytes(StandardCharsets.UTF_8)));
    }

    private static String randomNonce() {
        byte[] bytes = new byte[16];
        RANDOM.nextBytes(bytes);
        return HexFormat.of().formatHex(bytes);
    }

    private static String form(String... values) {
        StringBuilder out = new StringBuilder();
        for (int i = 0; i < values.length; i += 2) {
            if (out.length() > 0) {
                out.append('&');
            }
            out.append(URLEncoder.encode(values[i], StandardCharsets.UTF_8));
            out.append('=');
            out.append(URLEncoder.encode(values[i + 1], StandardCharsets.UTF_8));
        }
        return out.toString();
    }

    private static int clamp(int value, int min, int max) {
        return Math.max(min, Math.min(max, value));
    }

    private record VerificationResult(boolean success, String message) {}
}
