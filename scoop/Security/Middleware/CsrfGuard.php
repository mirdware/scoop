<?php

namespace Scoop\Security\Middleware;

class CsrfGuard
{
    public function process($request, $next)
    {
        if (in_array($request->getMethod(), array('get', 'head', 'options', 'trace'))) {
            return $next->handle($request);
        }
        if (!isset($_SESSION['csrf-token'])) {
            throw new \Scoop\Http\Exception\Forbidden('CSRF token not found in session');
        }
        $token = $_SESSION['csrf-token'];
        $tokenSent = $request->getHeaderLine('X-CSRF-Token');
        if (!$tokenSent) {
            $query = $request->getQueryParams();
            $tokenSent = isset($query['csrf-token']) ? $query['csrf-token'] : null;
        }
        if (!$tokenSent) {
            $payload = $request->getParsedBody();
            $tokenSent = isset($payload['csrf-token']) ? $payload['csrf-token'] : null;
        }
        if (!self::equals($token, $tokenSent)) {
            throw new \Scoop\Http\Exception\Forbidden('CSRF token mismatch');
        }
        return $next->handle($request);
    }

    private static function equals($known, $user)
    {
        if (function_exists('hash_equals')) {
            return hash_equals($known, $user);
        }
        if (!is_string($known) || !is_string($user)) {
            return false;
        }
        $knownLen = strlen($known);
        $userLen = strlen($user);
        if ($knownLen !== $userLen) {
            $user = $known;
            $result = 1;
        } else {
            $result = 0;
        }
        for ($i = 0; $i < $knownLen; $i++) {
            $result |= ord($known[$i]) ^ ord($user[$i]);
        }
        return $result === 0;
    }
}
