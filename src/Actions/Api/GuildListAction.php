<?php
declare(strict_types=1);

namespace App\Actions\Api;

use App\Discord;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Response;

class GuildListAction
{
    public function __construct(private Discord $discord) {}

    public function __invoke(ServerRequestInterface $request, Response $response): ResponseInterface
    {
        $session = $request->getAttribute('session');
        $guilds  = $this->discord->getUserGuilds($session['access_token']);

        // Nur Server wo User Admin oder Owner ist (ADMINISTRATOR=0x8, MANAGE_GUILD=0x20)
        $filtered = array_filter($guilds, function ($g) {
            $perms = (int)($g['permissions'] ?? 0);
            return ($g['owner'] ?? false) || ($perms & 0x8) || ($perms & 0x20);
        });

        // Nur Server wo der Bot auch ist
        $botGuilds  = $this->discord->getBotGuilds();
        $botGuildIds = array_column($botGuilds, 'id');
        $filtered = array_values(array_filter($filtered, fn($g) => in_array($g['id'], $botGuildIds, true)));

        $result = array_map(fn($g) => [
            'id'   => $g['id'],
            'name' => $g['name'],
            'icon' => $g['icon'],
        ], $filtered);

        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
