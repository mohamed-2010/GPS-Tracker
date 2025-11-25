<?php

declare(strict_types=1);

namespace App\Services\Protocol\JT808\Parser;

use App\Services\Protocol\ParserAbstract;
use App\Services\Protocol\JT808\Support\Codec;

class Register extends ParserAbstract
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

        logger()->debug('JT808 Register: Frame decoded', [
            'id' => $id,
            'phone' => $phone,
            'flow' => $flow,
            'body' => $body,
        ]);

        if ($id !== '0100') { // terminal register
            return false;
        }

        logger()->info('JT808 Register: Registration request received', [
            'serial' => $phone,
            'flow' => $flow,
        ]);

        $this->cache['serial'] = $phone;
        $this->cache['flow'] = $flow;
        $this->cache['id'] = $id;
        $this->cache['body'] = $body;

        // reply with 0x8100 register response (result=0 OK, auth token 'OK')
        $this->cache['response'] = hex2bin(Codec::buildRegisterResp($phone, $flow, 0, 'OK'));

        logger()->debug('JT808 Register: Response prepared', [
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