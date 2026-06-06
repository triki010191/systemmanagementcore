<?php

namespace App\Support\Sor;

/**
 * Little-endian binary cursor for Bellcore SR-4731 .sor files.
 */
class BellcoreBlockReader
{
    private int $pos = 0;

    public function __construct(private readonly string $data) {}

    public function length(): int
    {
        return strlen($this->data);
    }

    public function tell(): int
    {
        return $this->pos;
    }

    public function seek(int $pos): void
    {
        $this->pos = max(0, min($pos, $this->length()));
    }

    public function readString(): string
    {
        $str = '';

        while ($this->pos < $this->length() && $this->data[$this->pos] !== "\0") {
            $str .= $this->data[$this->pos];
            $this->pos++;
        }

        if ($this->pos < $this->length()) {
            $this->pos++;
        }

        return $str;
    }

    public function readUint(int $bytes): int
    {
        $chunk = substr($this->data, $this->pos, $bytes);
        $this->pos += $bytes;

        return match ($bytes) {
            2 => unpack('v', $chunk)[1],
            4 => unpack('V', $chunk)[1],
            default => throw new \InvalidArgumentException("Unsupported uint width: {$bytes}"),
        };
    }

    public function readSigned(int $bytes): int
    {
        $chunk = substr($this->data, $this->pos, $bytes);
        $this->pos += $bytes;

        if ($bytes === 2) {
            $val = unpack('v', $chunk)[1];

            return $val >= 32768 ? $val - 65536 : $val;
        }

        if ($bytes === 4) {
            $val = unpack('V', $chunk)[1];

            return $val >= 2147483648 ? $val - 4294967296 : $val;
        }

        throw new \InvalidArgumentException("Unsupported signed width: {$bytes}");
    }

    public function readBytes(int $length): string
    {
        $chunk = substr($this->data, $this->pos, $length);
        $this->pos += $length;

        return $chunk;
    }

    public function skip(int $length): void
    {
        $this->pos = min($this->pos + $length, $this->length());
    }
}
