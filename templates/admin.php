<?php
/**
 * @var array  $stats
 * @var array  $recentCommands
 * @var string $csrfToken
 */
?>
<h1>Admin</h1>

<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-value"><?= (int)($stats['active_channels'] ?? 0) ?></div>
        <div class="stat-label">Aktive Kanäle</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= (int)($stats['active_guilds'] ?? 0) ?></div>
        <div class="stat-label">Aktive Server</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= (int)($stats['articles_today'] ?? 0) ?></div>
        <div class="stat-label">Artikel heute</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= (int)($stats['pending_commands'] ?? 0) ?></div>
        <div class="stat-label">Ausstehende Befehle</div>
    </div>
</div>

<div class="bot-panel">
    <h2>Bot-Verwaltung</h2>
    <div class="actions">
        <button class="btn btn-primary" onclick="botCmd('run_digest')">▶ Digest ausführen</button>
        <button class="btn btn-ghost"   onclick="botCmd('restart_bot')">🔄 Bot neu starten</button>
        <button class="btn btn-danger"  onclick="botCmd('stop_bot')">⏹ Bot stoppen</button>
    </div>
    <p id="bot-msg" style="margin-top:10px;font-size:14px"></p>
</div>

<h2 style="margin-top:32px">Letzte Befehle</h2>

<?php if (empty($recentCommands)): ?>
    <p style="color:var(--muted)">Noch keine Befehle.</p>
<?php else: ?>
<div class="table-wrap">
<table class="data-table">
    <thead>
        <tr>
            <th>#</th>
            <th>Befehl</th>
            <th>Von</th>
            <th>Status</th>
            <th>Erstellt</th>
            <th>Ausgeführt</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($recentCommands as $cmd): ?>
        <tr>
            <td><?= (int)$cmd['id'] ?></td>
            <td><code><?= htmlspecialchars($cmd['command'], ENT_QUOTES) ?></code></td>
            <td><?= $cmd['created_by'] === 'scheduler' ? '🕐 Scheduler' : '👤 Admin' ?></td>
            <td>
                <span class="status-badge status-<?= htmlspecialchars($cmd['status'], ENT_QUOTES) ?>">
                    <?= match($cmd['status']) {
                        'done'    => '✅ done',
                        'pending' => '⏳ pending',
                        'failed'  => '❌ failed',
                        default   => htmlspecialchars($cmd['status'], ENT_QUOTES),
                    } ?>
                </span>
            </td>
            <td><?= htmlspecialchars(date('d.m.Y H:i', strtotime($cmd['created_at'])), ENT_QUOTES) ?></td>
            <td><?= $cmd['executed_at'] ? htmlspecialchars(date('d.m.Y H:i', strtotime($cmd['executed_at'])), ENT_QUOTES) : '—' ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>

<script>
async function botCmd(command) {
    const msgEl = document.getElementById('bot-msg');
    msgEl.textContent = 'Sende…';
    const res  = await fetch('/api/bot/command', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': '<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>' },
        body: JSON.stringify({ command }),
    });
    const data = await res.json();
    msgEl.textContent = data.message ?? (data.success ? 'Befehl gesendet.' : `Fehler: ${data.error}`);
    if (res.ok) setTimeout(() => location.reload(), 2000);
}
</script>
