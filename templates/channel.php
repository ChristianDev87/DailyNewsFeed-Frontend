<?php
/**
 * @var array  $channel
 * @var array  $categories
 * @var bool   $hasCustomToken
 * @var string $csrfToken
 */
$chId = htmlspecialchars($channel['channel_id'], ENT_QUOTES);
?>
<a href="/dashboard" style="color:var(--muted);font-size:14px;display:inline-block;margin-bottom:16px">← Dashboard</a>
<h1>⚙️ <?= htmlspecialchars($channel['guild_name'] ?? 'Server', ENT_QUOTES) ?> — #<?= htmlspecialchars($channel['channel_name'] ?? $channel['channel_id'], ENT_QUOTES) ?></h1>

<div id="cat-list" style="margin:24px 0">
<?php foreach ($categories as $cat): ?>
<div class="accordion" data-cat-id="<?= (int)$cat['id'] ?>">
    <div class="accordion-header">
        <span>
            <span class="handle" title="Ziehen zum Sortieren">⠿</span>
            <?= htmlspecialchars($cat['emoji'], ENT_QUOTES) ?>
            <strong><?= htmlspecialchars($cat['label'], ENT_QUOTES) ?></strong>
            <span style="color:var(--muted);font-size:13px;margin-left:8px">(<?= htmlspecialchars((string)count($cat['feeds']), ENT_QUOTES) ?> Feeds)</span>
        </span>
        <span style="color:var(--muted)">▼</span>
    </div>
    <div class="accordion-body">
        <div id="feeds-<?= (int)$cat['id'] ?>">
        <?php foreach ($cat['feeds'] as $feed): ?>
            <div class="feed-item" data-feed-id="<?= (int)$feed['id'] ?>">
                <span>
                    <?= htmlspecialchars($feed['name'], ENT_QUOTES) ?>
                    <span style="color:var(--muted);font-size:12px"><?= htmlspecialchars($feed['url'], ENT_QUOTES) ?></span>
                </span>
                <button class="btn btn-danger" style="padding:4px 10px;font-size:12px"
                    onclick="deleteFeed(<?= (int)$feed['id'] ?>, this.closest('.feed-item'))">✕</button>
            </div>
        <?php endforeach; ?>
        </div>
        <div class="feed-add-row">
            <input type="url"  id="furl-<?= (int)$cat['id'] ?>" placeholder="Feed-URL...">
            <input type="text" id="fname-<?= (int)$cat['id'] ?>" placeholder="Name" style="max-width:140px">
            <button class="btn btn-ghost"   onclick="testFeed(<?= (int)$cat['id'] ?>)">Testen</button>
            <button class="btn btn-primary" onclick="addFeed(<?= (int)$cat['id'] ?>)">+ Hinzufügen</button>
        </div>
        <div id="fstatus-<?= (int)$cat['id'] ?>" style="font-size:13px;margin-top:6px;min-height:18px"></div>
    </div>
</div>
<?php endforeach; ?>
</div>

<button class="btn btn-ghost" onclick="newCategory('<?= $chId ?>')">+ Neue Kategorie</button>

<!-- Bot-Token -->
<div style="margin-top:40px;padding-top:24px;border-top:1px solid var(--border)">
    <h2>Eigener Bot-Token (optional)</h2>
    <p style="color:var(--muted);font-size:14px;margin-bottom:16px">Wird AES-256 verschlüsselt gespeichert. Nur der Bot entschlüsselt ihn.</p>
    <div style="display:flex;gap:8px;max-width:500px">
        <input type="password" id="bot-token" placeholder="<?= $hasCustomToken ? '••••••••' : 'Token eingeben...' ?>">
        <button class="btn btn-primary" onclick="saveBotToken('<?= $chId ?>')">Speichern</button>
        <?php if ($hasCustomToken): ?>
        <button class="btn btn-danger" onclick="removeBotToken('<?= $chId ?>')">Entfernen</button>
        <?php endif; ?>
    </div>
    <div id="token-status" style="font-size:13px;margin-top:8px"></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.3/Sortable.min.js"></script>
<script>
const CSRF = '<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>';

new Sortable(document.getElementById('cat-list'), {
    handle: '.handle',
    animation: 150,
    onEnd: async () => {
        const ids = [...document.querySelectorAll('#cat-list .accordion')]
            .map(el => parseInt(el.dataset.catId));
        await fetch('/api/category/order', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
            body: JSON.stringify({ ids }),
        });
    },
});

async function testFeed(catId) {
    const url    = document.getElementById(`furl-${catId}`).value.trim();
    const status = document.getElementById(`fstatus-${catId}`);
    if (!url) return;
    status.textContent = 'Prüfe...';
    const data = await apiPost('/api/feed/test', { url });
    status.textContent  = data.valid ? '✓ Gültiger Feed' : `✗ ${data.error}`;
    status.style.color  = data.valid ? 'var(--success)' : 'var(--danger)';
}

async function addFeed(catId) {
    const url    = document.getElementById(`furl-${catId}`).value.trim();
    const name   = document.getElementById(`fname-${catId}`).value.trim();
    const status = document.getElementById(`fstatus-${catId}`);
    if (!url || !name) { status.textContent = 'URL und Name erforderlich.'; return; }

    const data = await apiPost('/api/feed/save', { category_id: catId, url, name });
    if (data.success) {
        document.getElementById(`feeds-${catId}`).insertAdjacentHTML('beforeend',
            `<div class="feed-item" data-feed-id="${data.id}">
                <span>${escHtml(name)} <span style="color:var(--muted);font-size:12px">${escHtml(url)}</span></span>
                <button class="btn btn-danger" style="padding:4px 10px;font-size:12px"
                    onclick="deleteFeed(${data.id}, this.closest('.feed-item'))">✕</button>
             </div>`
        );
        document.getElementById(`furl-${catId}`).value  = '';
        document.getElementById(`fname-${catId}`).value = '';
        status.textContent = '✓ Feed hinzugefügt';
        status.style.color = 'var(--success)';
    } else {
        status.textContent = `✗ ${data.error ?? 'Fehler'}`;
        status.style.color = 'var(--danger)';
    }
}

async function newCategory(channelId) {
    const label = prompt('Kategorie-Name:');
    if (!label) return;
    const emoji = prompt('Emoji (z.B. 🤖):', '📰');
    const data  = await apiPost('/api/category/save', { channel_id: channelId, label, emoji: emoji || '📰' });
    if (data.success) location.reload();
    else alert(`Fehler: ${data.error}`);
}

async function saveBotToken(channelId) {
    const token = document.getElementById('bot-token').value.trim();
    if (!token) return;
    const data = await apiPost('/api/category/save', { action: 'save_token', channel_id: channelId, token });
    const el   = document.getElementById('token-status');
    el.textContent = data.success ? '✓ Token gespeichert.' : `✗ ${data.error}`;
    el.style.color = data.success ? 'var(--success)' : 'var(--danger)';
    if (data.success) document.getElementById('bot-token').value = '';
}

async function removeBotToken(channelId) {
    if (!confirm('Token wirklich entfernen?')) return;
    const data = await apiPost('/api/category/save', { action: 'remove_token', channel_id: channelId });
    if (data.success) location.reload();
}
</script>
