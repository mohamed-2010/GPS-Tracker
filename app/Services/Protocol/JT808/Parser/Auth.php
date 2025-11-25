<?php

declare(strict_types=1);

namespace App\Services\Protocol\JT808\Parser;

use App\Services\Protocol\ParserAbstract;
use App\Services\Protocol\JT808\Support\Codec;

class Auth extends ParserAbstract
{
    /**
     * @return array
     */
    public function resources(): array
    {
        if ($this->messageIsValid() === false) {
            return [];
        }

        $this->addIfValid($this->resourceAuth());

        return $this->resources;
    }

    protected function messageIsValid(): bool
    {
        [$id, $props, $phone, $flow, $body] = Codec::decodeFrame($this->message);

        // Check if decoding failed
        if ($id === null || $phone === null) {
            logger()->warning('JT808 Auth: Frame decoding failed');
            return false;
        }

        logger()->debug('JT808 Auth: Frame decoded', [
            'id' => $id,
            'phone' => $phone,
            'flow' => $flow,
            'body' => $body,
        ]);

        if ($id !== '0102') { // terminal authentication
            return false;
        }

        logger()->info('JT808 Auth: Authentication request received', [
            'serial' => $phone,
            'flow' => $flow,
        ]);

        $this->cache['serial'] = $phone;
        $this->cache['flow'] = $flow;
        $this->cache['id'] = $id;
        $this->cache['body'] = $body;

        // Prepare response: general platform common response 0x8001 OK
        $this->cache['response'] = hex2bin(Codec::buildGeneralResp($phone, $flow, $id, 0));

        logger()->debug('JT808 Auth: Response prepared', [
            'response_hex' => bin2hex($this->cache['response']),
        ]);

        return true;
    }

    protected function serial(): string
    {
        return (string)$this->cache['serial'];
    }

    protected function response(): ?string
    {
        return $this->cache['response'] ?? null;
    }
}
