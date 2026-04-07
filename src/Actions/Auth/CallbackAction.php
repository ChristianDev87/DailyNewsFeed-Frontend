<?php
declare(strict_types=1);

namespace App\Actions\Auth;

use App\Auth;
use App\Discord;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Response;

class CallbackAction
{
    public function __construct(
        private Auth    $auth,
        private Discord $discord
    ) {}

    public function __invoke(ServerRequestInterface $request, Response $response): ResponseInterface
    {
        $params = $request->getQueryParams();
        $code   = $params['code']  ?? null;
        $state  = $params['state'] ?? null;

        $expectedState = $request->getCookieParams()['oauth_state'] ?? '';
        if (!$code || !$state || !hash_equals($expectedState, $state)) {
            return $response->withHeader('Location', '/')->withStatus(302);
        }

        $tokens = $this->discord->exchangeCode($code);
        if (empty($tokens['access_token'])) {
            return $response->withHeader('Location', '/')->withStatus(302);
        }

        $user = $this->discord->getUser($tokens['access_token']);
        if (empty($user['id'])) {
            return $response->withHeader('Location', '/')->withStatus(302);
        }

        // Guilds abrufen — Admin-Check via permissions Bitmaske
        $guilds      = $this->discord->getUserGuilds($tokens['access_token']);
        $adminGuilds = array_filter($guilds, fn($g) => ((int)($g['permissions'] ?? 0) & 0x8) !== 0);

        // Session anlegen
        $sessionToken = $this->auth->createSession(
            $user['id'],
            $user['global_name'] ?? $user['username'] ?? 'Unbekannt',
            $tokens['access_token'],
            $tokens['refresh_token'] ?? '',
            (int)($tokens['expires_in'] ?? 604800)
        );

        $sessionCookie = sprintf(
            'session_token=%s; Path=/; HttpOnly; SameSite=Strict; Max-Age=%d',
            $sessionToken,
            60 * 60 * 24 * 7
        );
        $clearState = 'oauth_state=; Path=/auth/callback; HttpOnly; Max-Age=0';

        return $response
            ->withAddedHeader('Set-Cookie', $sessionCookie)
            ->withAddedHeader('Set-Cookie', $clearState)
            ->withHeader('Location', '/dashboard')
            ->withStatus(302);
    }
}
