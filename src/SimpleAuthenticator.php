<?php

namespace Comes\SimpleAuthenticator;

use Carbon\Carbon;

class SimpleAuthenticator
{
    public function __construct(private string $secret)
    {
    }

    public function generateOTP(): string
    {
        // tokens are only available for 30 seconds.
        $time = floor($time = Carbon::now()->floorSecond()->timestamp / 30);
        $secretKey = $this->base32Decode($this->secret);

        // Pack time into binary string
        $time = chr(0).chr(0).chr(0).chr(0).pack('N*', $time);

        // Generate HMAC-SHA1
        $hash = hash_hmac('SHA1', $time, $secretKey, true);

        // Get offset
        $offset = ord(substr($hash, -1)) & 0x0F;

        // Calculate OTP
        $otp = (
            (ord($hash[$offset + 0]) & 0x7F) << 24 |
            (ord($hash[$offset + 1]) & 0xFF) << 16 |
            (ord($hash[$offset + 2]) & 0xFF) << 8 |
            (ord($hash[$offset + 3]) & 0xFF)
        ) % pow(10, 6);

        // Zero-padding if necessary
        $otp = str_pad($otp, 6, '0', STR_PAD_LEFT);

        return $otp;
    }

    private function base32Decode($base32): string
    {
        $base32chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $base32charsFlipped = array_flip(str_split($base32chars));

        $output = '';

        $i = 0;
        $buffer = 0;
        $bufferSize = 0;

        // Reverse the base32 encoding and convert the secret key back to its original binary form.
        // Accumulate bits into a buffer and convert them into bytes, appending them to the output.
        // Throw an exception if an invalid base32 character is encountered.
        while ($i < strlen($base32)) {
            $char = strtoupper($base32[$i]);

            if (! isset($base32charsFlipped[$char])) {
                throw new \Exception('Invalid base32 character: '.$char);
            }

            $buffer <<= 5;
            $buffer |= $base32charsFlipped[$char];
            $bufferSize += 5;

            if ($bufferSize >= 8) {
                $bufferSize -= 8;
                $output .= chr(($buffer & (0xFF << $bufferSize)) >> $bufferSize);
            }

            $i++;
        }

        return $output;
    }
}
