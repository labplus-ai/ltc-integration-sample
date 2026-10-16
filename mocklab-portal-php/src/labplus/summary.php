<?php
// "Results summary" on the order page (included by src/order/template.php): the preliminary interpretation made
// by Labplus, the LabTest Checker panel, and the bar about tests that can still be done on the collected sample.
// Expects $order, $preinterpretation (PreinterpretationService::get) and $ltcStatus (LtcService::getStatus).
// The interactive part (polling, the LTC iframe, the countdown and the dialogs) is in labplus.js.
?>
<section class="labplus-section" id="labplus" data-order-id="<?= (int) $order['id'] ?>">
    <div class="eyebrow eyebrow-line" id="labplus-eyebrow">Summary</div>
    <h2 class="section-title" id="labplus-title">Results summary</h2>

    <!-- Two columns: the interpretation on the left, LabTest Checker on the right (it takes the whole card when open). -->
    <div class="card labplus-card summary" id="labplus-summary">
        <div class="summary-text" id="labplus-preinterpretation" data-status="<?= e($preinterpretation['status']) ?>">
            <?php include __DIR__ . '/summary_preinterpretation.php'; ?>
        </div>

        <div class="ltc" id="labplus-ltc">
            <?php if ($ltcStatus === 'not_configured'): ?>
                <p class="muted">LabTest Checker is not configured (see .env.dist).</p>
            <?php elseif ($ltcStatus === 'not_supported'): ?>
                <!-- Adults only, so there is no button (see LtcService::isSupported). -->
                <p class="muted">LabTest Checker is available for adults only.</p>
            <?php elseif ($ltcStatus === 'error'): ?>
                <p class="muted">LabTest Checker is not available right now.</p>
            <?php else: ?>
                <div id="labplus-ltc-intro">
                    <h3 class="ltc-title">See what your results mean for you</h3>
                    <p>Answer a few questions about your health and get a Health Report with an interpretation of your results.</p>
                    <button class="btn btn-teal ltc-button" type="button" id="labplus-ltc-start">
                        <?= ['finished' => 'See your Health Report', 'in_progress' => 'Continue the questionnaire'][$ltcStatus] ?? 'Start the interpretation' ?>
                    </button>
                </div>
            <?php endif; ?>

            <!-- Hidden until started. The adapter script and the handshake work on this element. -->
            <iframe class="ltc-frame" id="labplus-ltc-frame" title="LabTest Checker" referrerpolicy="no-referrer" hidden></iframe>
        </div>
    </div>

    <!-- Shown only when some recommended tests can still be done on the already collected sample. -->
    <div id="labplus-tests">
        <?php include __DIR__ . '/summary_tests.php'; ?>
    </div>
</section>

<!-- Labplus adapter for the LabTest Checker iframe (its height and scrolling), then our script. -->
<script type="text/javascript" src="https://cdn.labplus.pl/libs/v1/adapter/1.4.0/adapter.min.js" integrity="sha384-r2HiyVSR/jj5Rl0onmpgq/k0YyX3I4ViC4MILpx79j+/UE8dnyJspujAPTH+G308" crossorigin="anonymous"></script>
<script src="/labplus/labplus.js"></script>
