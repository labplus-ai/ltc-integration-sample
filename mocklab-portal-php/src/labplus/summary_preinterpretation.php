<?php
// The preinterpretation part of the results summary. Expects $preinterpretation.
// Also returned by labplus/api/preinterpretation/ while the page waits for the result.
$preStatus = $preinterpretation['status'];
?>
<span class="pill pill-mint summary-label">Preliminary interpretation</span>
<?php if ($preStatus === 'not_configured'): ?>
    <p class="muted">The preliminary interpretation is not configured (see .env.dist).</p>
<?php elseif ($preStatus === 'processing'): ?>
    <div class="skeleton" aria-hidden="true"><span></span><span></span><span></span></div>
    <p class="muted" id="labplus-processing-text">Your interpretation is being prepared&hellip;</p>
<?php elseif ($preStatus === 'error'): ?>
    <p class="muted">The preliminary interpretation is not available right now.</p>
<?php else: ?>
    <!-- "done" or "fallback" (a simplified version). -->
    <div class="preinterpretation-content"><?= labplus_safe_html($preinterpretation['content'] ?? '') ?></div>
    <p class="summary-note">An automatic interpretation of your results. It does not replace a consultation with a doctor.</p>
<?php endif; ?>
