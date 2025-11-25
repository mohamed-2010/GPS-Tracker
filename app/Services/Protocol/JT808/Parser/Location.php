<?php

declare(strict_types=1);

namespace App\Services\Protocol\JT808\Parser;

use App\Services\Buffer\Byte as BufferByte;
use App\Services\Buffer\Bit as BufferBit;
use App\Services\Protocol\ParserAbstract;
use App\Services\Protocol\JT808\Support\Codec;

class Location extends ParserAbstract
{
    /**
     * @return array
     */
    public function resources(): array
    {
        logger()->debug('JT808 Location: Starting resources extraction');

        if ($this->messageIsValid() === false) {
            logger()->warning('JT808 Location: Message validation failed');
            return [];
        }

        logger()->debug('JT808 Location: Message validated, creating resource');

        $resource = $this->resourceLocation();
        
        if ($resource) {
            logger()->info('JT808 Location: Resource created', [
                'resource_type' => get_class($resource),
                'resource_id' => $resource->id ?? 'pending',
            ]);
        } else {
            logger()->error('JT808 Location: Resource creation returned null');
        }

        $this->addIfValid($resource);

        logger()->debug('JT808 Location: Resources count', [
            'count' => count($this->resources),
        ]);

        return $this->resources;
    }

    protected function messageIsValid(): bool
    {
        try {
            logger()->debug('JT808 Location: Raw message received', [
                'message_hex' => $this->message,
                'message_length' => strlen($this->message ?? ''),
            ]);

            [$id, $props, $phone, $flow, $body] = Codec::decodeFrame($this->message);

            // Check if body is null or empty
            if ($body === null || $body === '') {
                logger()->warning('JT808 Location: Body is null or empty');
                return false;
            }

            logger()->debug('JT808 Location: Frame decoded', [
                'id' => $id,
                'props' => $props,
                'phone' => $phone,
                'flow' => $flow,
                'body_hex' => $body,
                'body_length' => strlen($body) / 2,
            ]);

            if ($id !== '0200') { // location report
                logger()->warning('JT808 Location: Not a location message', ['id' => $id]);
                return false;
            }

            if (strlen($body) < 56) { // minimum 28 bytes = 56 hex chars
                logger()->error('JT808 Location: Body too short', [
                    'expected_min' => 56,
                    'actual' => strlen($body),
                ]);
                return false;
            }

            $buffer = new BufferByte($body);

            // Alarm flags (4), Status (4)
            $alarm = $buffer->int(4);
            $status = $buffer->int(4);

            logger()->debug('JT808 Location: Alarm and Status', [
                'alarm' => $alarm,
                'status' => $status,
            ]);

            // Latitude/Longitude in 1e-6 deg
            $lat = $buffer->int(4);
            $lng = $buffer->int(4);

            $this->cache['latitude'] = round($lat / 1000000, 5);
            $this->cache['longitude'] = round($lng / 1000000, 5);

            $this->cache['altitude'] = $buffer->int(2); // meters, unused
            $speedRaw = $buffer->int(2);
            $this->cache['speed'] = round($speedRaw * 0.1 * 1.852, 2); // 0.1 km/h -> km/h
            $this->cache['direction'] = $buffer->int(2);

            $timeBcd = $buffer->string(6);
            $this->cache['datetime'] = Codec::dtFromBcd($timeBcd);

            logger()->info('JT808 Location: Parsed successfully', [
                'serial' => $phone,
                'latitude' => $this->cache['latitude'],
                'longitude' => $this->cache['longitude'],
                'speed' => $this->cache['speed'],
                'direction' => $this->cache['direction'],
                'datetime' => $this->cache['datetime'],
                'altitude' => $this->cache['altitude'],
            ]);

            $this->cache['serial'] = $phone;
            $this->cache['flow'] = $flow;
            $this->cache['id'] = $id;

            $this->cache['signal'] = 1; // treat as valid fix; could be derived from status bits in extensions

            $this->cache['response'] = hex2bin(Codec::buildGeneralResp($phone, $flow, $id, 0));

            logger()->debug('JT808 Location: Response prepared', [
                'response_hex' => bin2hex($this->cache['response']),
            ]);

            return true;

        } catch (\Exception $e) {
            logger()->error('JT808 Location: Parse exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    protected function serial(): string
    {
        return (string)$this->cache['serial'];
    }

    protected function latitude(): ?float
    {
        return $this->cache['latitude'] ?? null;
    }

    protected function longitude(): ?float
    {
        return $this->cache['longitude'] ?? null;
    }

    protected function speed(): ?float
    {
        return $this->cache['speed'] ?? null;
    }

    protected function signal(): ?int
    {
        return $this->cache['signal'] ?? null;
    }

    protected function direction(): ?int
    {
        return $this->cache['direction'] ?? null;
    }

    protected function datetime(): ?string
    {
        return $this->cache['datetime'] ?? null;
    }

    protected function timezone(): ?string
    {
        return $this->cache[__FUNCTION__] ??= helper()->latitudeLongitudeTimezone(
            $this->latitude(),
            $this->longitude(),
        );
    }

    protected function response(): ?string
    {
        return $this->cache['response'] ?? null;
    }
}
