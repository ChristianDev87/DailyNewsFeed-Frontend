<?php
/**
 * @var array      $channels
 * @var bool       $isSuperAdmin
 * @var array|null $botStatus
 * @var string     $csrfToken
 */
?>
<h1>Dashboard</h1>

<?php if ($isSuperAdmin): ?>
<div class="bot-panel">
    <h2>Bot-Verwaltung</h2>
    <?php if ($botStatus): ?>
        <p><span class="dot online"></span>Letzter Lauf: <?= htmlspecialchars(date('d.m.Y H:i', strtotime($botStatus['executed_at'])), ENT_QUOTES) ?></p>
    <?php else: ?>
        <p><span class="dot offline"></span>Kein Lauf bisher aufgezeichnet.</p>
    <?php endif; ?>
    <div class="actions">
        <button class="btn btn-ghost"    onclick="botCmd('restart_bot')">🔄 Bot neu starten</button>
        <button class="btn btn-primary"  onclick="botCmd('run_digest')">▶ Digest jetzt ausführen</button>
    </div>
</div>
<script>
async function botCmd(command) {
    const res  = await fetch('/api/bot/command', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': '<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>' },
        body: JSON.stringify({ command }),
    });
    const data = await res.json();
    alert(data.message ?? (data.success ? 'Befehl gesendet.' : `Fehler: ${data.error}`));
}
</script>
<?php endif; ?>

<?php if (empty($channels)): ?>
    <p style="color:var(--muted)">Noch keine Kanäle konfiguriert. Nutze <code>/dnews setup</code> im Discord.</p>
<?php else: ?>
<div class="card-grid">
    <?php foreach ($channels as $ch): ?>
    <div class="card">
        <h3><?= htmlspecialchars($ch['guild_name'] ?? 'Unbekannter Server', ENT_QUOTES) ?></h3>
        <div class="meta">
            #<?= htmlspecialchars($ch['channel_name'] ?? $ch['channel_id'], ENT_QUOTES) ?>
            · <?= htmlspecialchars((string)$ch['feed_count'], ENT_QUOTES) ?> Feeds
            · <?= $ch['last_digest_at']
                ? 'Letzter Digest: ' . htmlspecialchars(date('d.m.Y H:i', strtotime($ch['last_digest_at'])), ENT_QUOTES)
                : 'Noch kein Digest' ?>
        </div>
        <a href="/channel/<?= htmlspecialchars($ch['channel_id'], ENT_QUOTES) ?>" class="btn btn-primary">Konfigurieren</a>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
