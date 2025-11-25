<?php

declare(strict_types=1);

namespace App\Services\Protocol\JT808\Parser;

use App\Services\Protocol\ParserAbstract;
use App\Services\Protocol\JT808\Support\Codec;

class Heartbeat extends ParserAbstract
{
    /**
     * @return array
     */
    public function resources(): array
    {
        if ($this->messageIsValid() === false) {
            return [];
        }

        $this->addIfValid($this->resourceHeartbeat());

        return $this->resources;
    }

    protected function messageIsValid(): bool
    {
        [$id, $props, $phone, $flow] = Codec::decodeFrame($this->message);

        // Check if decoding failed
        if ($id === null || $phone === null) {
            logger()->warning('JT808 Heartbeat: Frame decoding failed');
            return false;
        }

        logger()->debug('JT808 Heartbeat: Frame decoded', [
            'id' => $id,
            'phone' => $phone,
            'flow' => $flow,
        ]);

        if ($id !== '0002') { // heartbeat
            return false;
        }

        logger()->info('JT808 Heartbeat: Heartbeat received', [
            'serial' => $phone,
            'flow' => $flow,
        ]);

        $this->cache['serial'] = $phone;
        $this->cache['flow'] = $flow;
        $this->cache['id'] = $id;

        $this->cache['response'] = hex2bin(Codec::buildGeneralResp($phone, $flow, $id, 0));

        logger()->debug('JT808 Heartbeat: Response prepared', [
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
