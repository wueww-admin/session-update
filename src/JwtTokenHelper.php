<?php


namespace SessionUpdate;


use SessionUpdate\Exception\MalformedJwtTokenException;
use TopicAdvisor\Lambda\RuntimeApi\Http\HttpRequestInterface;

class JwtTokenHelper
{

    /**
     * @var string[]
     */
    private $payload;

    private function __construct(string $jwtToken)
    {
        $jwtParts = \explode('.', $jwtToken);

        if (\count($jwtParts) !== 3) {
            throw new MalformedJwtTokenException('Malformed JWT Token');
        }

        $decodedPayload = \base64_decode($jwtParts[1]);

        if ($decodedPayload === false) {
            throw new MalformedJwtTokenException('Failed to base64-decode payload data. Invalid JWT token.');
        }

        $jwtPayload = \json_decode($decodedPayload, true);

        if (!is_array($jwtPayload)) {
            throw new MalformedJwtTokenException('Malformed JWT Subject Record');
        }

        $this->payload = $jwtPayload;
    }

    public static function fromHttpRequest(HttpRequestInterface $request): self
    {
        $authHeaderParts = \explode(' ', $request->getHeaderLine('Authorization'), 2);

        if (\count($authHeaderParts) !== 2 || $authHeaderParts[0] !== 'Bearer') {
            throw new MalformedJwtTokenException('Invalid Authorization');
        }

        return new self($authHeaderParts[1]);
    }

    public function getSub(): string
    {
        if (!isset($this->payload['sub']) || !is_string($this->payload['sub'])) {
            throw new \RuntimeException('Malformed JWT Subject Record');
        }

        return $this->payload['sub'];
    }
}