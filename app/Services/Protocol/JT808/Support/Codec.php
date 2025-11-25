<?php

declare(strict_types=1);

namespace App\Services\Protocol\JT808\Support;

class Codec
{
    /**
     * Remove 0x7D escaping and trim 0x7E markers.
     */
    public static function decodeFrame(string $hex): array
    {
        $hex = strtolower($hex);

        // Trim leading/trailing 7e if present
        if (str_starts_with($hex, '7e')) {
            $hex = substr($hex, 2);
        }

        if (str_ends_with($hex, '7e')) {
            $hex = substr($hex, 0, -2);
        }

        // Unescape 7d01 -> 7d, 7d02 -> 7e
        $hex = str_replace(['7d01', '7d02'], ['7d', '7e'], $hex);

        $len = strlen($hex);

        if ($len < 26) { // minimal header + checksum
            return [null, null, null, null, null, null];
        }

        // Last byte is checksum
        $checksumHex = substr($hex, -2);
        $payload = substr($hex, 0, -2);

        $calc = self::checksum($payload);

        // Even if checksum fails, continue parsing to try extracting serial
        $buffer = $payload;

        $messageId = substr($buffer, 0, 4);
        $propsHex = substr($buffer, 4, 4);
        $props = hexdec($propsHex);
        $phoneBcd = substr($buffer, 8, 12);
        $flowIdHex = substr($buffer, 20, 4);

        $index = 24;

        $hasSub = (bool)($props & 0x2000);

        if ($hasSub) {
            // total and index (2 bytes each)
            $index += 8;
        }

        $bodyLen = ($props & 0x03FF);
        $bodyHex = substr($buffer, $index, $bodyLen * 2);

        return [
            $messageId,
            $propsHex,
            self::bcdToDigits($phoneBcd),
            hexdec($flowIdHex),
            $bodyHex,
            $checksumHex,
        ];
    }

    /**
     * Build a full escaped frame with start/end 0x7E.
     */
    public static function encodeFrame(string $messageIdHex, string $phoneDigits, string $bodyHex, int $flowId = 1, bool $sub = false, string $subInfoHex = ''): string
    {
        $bodyLen = strlen($bodyHex) / 2 + ($sub ? 4 : 0);
        $props = $bodyLen & 0x03FF; // no encryption, no reserved
        if ($sub) {
            $props |= 0x2000;
        }

        $header = '';
        $header .= self::padHex($messageIdHex, 4);
        $header .= self::padHex(dechex($props), 4);
        $header .= self::digitsToBcd($phoneDigits, 12);
        $header .= self::padHex(dechex($flowId & 0xFFFF), 4);
        if ($sub) {
            $header .= $subInfoHex; // 4 bytes: total and index
        }

        $payload = $header . $bodyHex;
        $checksum = self::checksum($payload);
        $msg = $payload . $checksum;

        // Escape
        $msg = str_replace(['7d', '7e'], ['7d01', '7d02'], strtolower($msg));

        return '7e' . $msg . '7e';
    }

    public static function checksum(string $hex): string
    {
        $sum = 0;
        $len = strlen($hex);
        for ($i = 0; $i < $len; $i += 2) {
            $sum ^= hexdec(substr($hex, $i, 2));
        }

        return self::padHex(dechex($sum & 0xFF), 2);
    }

    public static function bcdToDigits(string $hex): string
    {
        $digits = '';
        $len = strlen($hex);
        for ($i = 0; $i < $len; $i += 2) {
            $b = substr($hex, $i, 2);
            $hi = hexdec($b[0]);
            $lo = hexdec($b[1]);
            $digits .= (string)$hi . (string)$lo;
        }

        return ltrim($digits, '0');
    }

    public static function digitsToBcd(string $digits, int $totalDigits = 12): string
    {
        $digits = preg_replace('/\D+/', '', $digits ?? '');
        $digits = substr(str_pad($digits, $totalDigits, '0', STR_PAD_LEFT), -$totalDigits);

        $hex = '';
        for ($i = 0; $i < $totalDigits; $i += 2) {
            $hi = dechex(intval($digits[$i]));
            $lo = dechex(intval($digits[$i + 1]));
            $hex .= $hi . $lo;
        }

        return $hex;
    }

    public static function dtFromBcd(string $hex): string
    {
        // YYMMDDhhmmss (6 bytes -> 12 digits)
        $d = self::bcdToDigits($hex);
        $yy = substr($d, 0, 2);
        $MM = substr($d, 2, 2);
        $dd = substr($d, 4, 2);
        $hh = substr($d, 6, 2);
        $mm = substr($d, 8, 2);
        $ss = substr($d, 10, 2);
        return sprintf('20%s-%s-%s %s:%s:%s', $yy, $MM, $dd, $hh, $mm, $ss);
    }

    public static function buildGeneralResp(string $phoneDigits, int $flowId, string $originalIdHex, int $result = 0): string
    {
        $body = self::padHex(dechex($flowId & 0xFFFF), 4)
            . self::padHex(strtolower($originalIdHex), 4)
            . self::padHex(dechex($result & 0xFF), 2);

        return self::encodeFrame('8001', $phoneDigits, $body, $flowId);
    }

    public static function buildRegisterResp(string $phoneDigits, int $flowId, int $result = 0, string $auth = 'OK'): string
    {
        $authHex = bin2hex($auth);
        $body = self::padHex(dechex($flowId & 0xFFFF), 4)
            . self::padHex(dechex($result & 0xFF), 2)
            . $authHex;

        return self::encodeFrame('8100', $phoneDigits, $body, $flowId);
    }

    protected static function padHex(string $hex, int $len): string
    {
        $hex = strtolower($hex);
        return str_pad(substr($hex, -$len), $len, '0', STR_PAD_LEFT);
    }
}
