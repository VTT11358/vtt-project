<?php

class VnPay
{
    public static function createPaymentUrl(array $params): string
    {
        $params["vnp_Version"] = VNPAY_VERSION;
        $params["vnp_Command"] = "pay";
        $params["vnp_TmnCode"] = VNPAY_TMN_CODE;
        $params["vnp_CurrCode"] = "VND";
        $params["vnp_Locale"] = $params["vnp_Locale"] ?? "vn";

        ksort($params);

        $hashData = [];
        $query = [];

        foreach ($params as $key => $value) {
            if ($value === "" || $value === null) {
                continue;
            }

            $encodedKey = urlencode((string) $key);
            $encodedValue = urlencode((string) $value);
            $hashData[] = $encodedKey . "=" . $encodedValue;
            $query[] = $encodedKey . "=" . $encodedValue;
        }

        $secureHash = hash_hmac(
            "sha512",
            implode("&", $hashData),
            VNPAY_HASH_SECRET
        );

        return VNPAY_URL . "?" . implode("&", $query)
            . "&vnp_SecureHash=" . $secureHash;
    }

    public static function verify(array $input): bool
    {
        $secureHash = $input["vnp_SecureHash"] ?? "";
        unset($input["vnp_SecureHash"], $input["vnp_SecureHashType"]);

        ksort($input);

        $hashData = [];
        foreach ($input as $key => $value) {
            if (substr((string) $key, 0, 4) !== "vnp_") {
                continue;
            }
            if ($value === "" || $value === null) {
                continue;
            }
            $hashData[] = urlencode((string) $key) . "=" . urlencode((string) $value);
        }

        $computed = hash_hmac(
            "sha512",
            implode("&", $hashData),
            VNPAY_HASH_SECRET
        );

        return hash_equals($computed, (string) $secureHash);
    }

    public static function clientIp(): string
    {
        $ip = $_SERVER["HTTP_X_FORWARDED_FOR"] ?? $_SERVER["REMOTE_ADDR"] ?? "127.0.0.1";
        $ip = explode(",", (string) $ip)[0];
        $ip = trim($ip);

        return $ip !== "" ? $ip : "127.0.0.1";
    }

    public static function returnUrl(): string
    {
        $https = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off")
            || (($_SERVER["SERVER_PORT"] ?? "") === "443");
        $scheme = $https ? "https" : "http";
        $host = $_SERVER["HTTP_HOST"] ?? "localhost";

        return $scheme . "://" . $host . BASE_URL
            . "index.php?controller=checkout&action=vnpayReturn";
    }
}
