<?php
declare(strict_types=1);

namespace App\Actions;

use App\Database;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Response;

use function App\render;

class AdminAction
{
    public function __construct(private Database $db) {}

    public function __invoke(ServerRequestInterface $request, Response $response): ResponseInterface
    {
        if (!$request->getAttribute('is_superadmin')) {
            $response->getBody()->write('Kein Zugriff.');
            return $response->withStatus(403);
        }

        $session   = $request->getAttribute('session');
        $csrfToken = $request->getAttribute('csrf_token');

        $recentCommands = $this->db->fetchAll(
            'SELECT * FROM bot_commands ORDER BY created_at DESC LIMIT 30'
        );

        $stats = $this->db->fetchOne(
            'SELECT
                (SELECT COUNT(*) FROM channels      WHERE active = 1)          AS active_channels,
                (SELECT COUNT(*) FROM known_guilds  WHERE active = 1)          AS active_guilds,
                (SELECT COUNT(*) FROM seen_articles WHERE DATE(seen_at) = CURDATE()) AS articles_today,
                (SELECT COUNT(*) FROM bot_commands  WHERE status = "pending")  AS pending_commands'
        );

        return render('admin', [
            'title'          => 'Admin',
            'session'        => $session,
            'csrfToken'      => $csrfToken,
            'isSuperAdmin'   => true,
            'botOnline'      => $request->getAttribute('bot_online'),
            'recentCommands' => $recentCommands,
            'stats'          => $stats,
        ]);
    }
}
