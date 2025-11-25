<?php

declare(strict_types=1);

namespace App\Services\Protocol\JT808;

use App\Services\Protocol\JT808\Parser\Register as RegisterParser;
use App\Services\Protocol\JT808\Parser\Auth as AuthParser;
use App\Services\Protocol\JT808\Parser\Heartbeat as HeartbeatParser;
use App\Services\Protocol\JT808\Parser\Location as LocationParser;
use App\Services\Protocol\ProtocolAbstract;
use App\Services\Server\Socket\Server;

class Manager extends ProtocolAbstract
{
    /**
     * @return string
     */
    public function code(): string
    {
        return 'jt808';
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return 'JT808';
    }

    /**
     * @param int $port
     *
     * @return \App\Services\Server\Socket\Server
     */
    public function server(int $port): Server
    {
        // Defaults are TCP stream over IP
        return Server::new($port);
    }

    /**
     * @param string $message
     *
     * @return array
     */
    public function messages(string $message): array
    {
        logger()->debug('JT808 Manager: Raw message received', [
            'message' => $message,
            'length' => strlen($message),
        ]);

        if ($this->messageIsValidHex($message) === false) {
            logger()->warning('JT808 Manager: Invalid hex message', [
                'message' => substr($message, 0, 100),
            ]);
            return [];
        }

        // Extract all 7E-framed packets (inclusive)
        preg_match_all('/7e[0-9a-f]+7e/i', $message, $matches);

        $messages = $matches[0] ?: [$message];

        logger()->info('JT808 Manager: Messages extracted', [
            'count' => count($messages),
            'messages' => $messages,
        ]);

        return $messages;
    }

    /**
     * @return array
     */
    protected function parsers(): array
    {
        return [
            RegisterParser::class,
            AuthParser::class,
            HeartbeatParser::class,
            LocationParser::class,
        ];
    }
}
