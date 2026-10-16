<?php
// Bar with a countdown and a list of recommended tests that can still be done on the sample the lab keeps
// ("tests without collection", BBP). Labplus gives the deadline of each test in bbpValidUntil. Expects $preinterpretation.
// Also returned by labplus/api/preinterpretation/ while the page waits for the result.
$recommendedTests = $preinterpretation['recommendations']['recommendedExaminations'] ?? [];
// Recommended tests whose deadline has not passed yet.
$availableTests = array_values(array_filter($recommendedTests,
    fn($test) => !empty($test['bbpValidUntil']) && strtotime($test['bbpValidUntil']) > time()));
?>
<?php if ($availableTests): ?>
    <!-- data-until: the last of the deadlines (Unix time), the countdown in labplus.js runs until then. -->
    <div class="bbp-bar" id="labplus-bbp" data-until="<?= max(array_map(fn($test) => strtotime($test['bbpValidUntil']), $availableTests)) ?>">
        <strong>Your sample is still available:</strong>
        <span class="bbp-countdown"><span class="bbp-dot"></span><span id="labplus-countdown">--:--:--</span></span>
        <span class="bbp-text">Use the blood sample we keep and order the missing tests without another visit.</span>
        <button class="btn btn-mint" type="button" data-open="labplus-tests-dialog">See what is worth ordering</button>
    </div>

    <div class="modal-backdrop" id="labplus-tests-dialog" hidden>
        <div class="modal">
            <button class="modal-close" type="button" aria-label="Close" data-close>&times;</button>
            <h2 class="section-title">Order new results</h2>

            <div class="modal-info">
                <strong>Additional results from your last sample</strong>
                <p>Your blood from the last collection will be used for further tests. Choose the tests you want results for.</p>
            </div>

            <div class="modal-group">
                <h3>Related</h3>
                <p class="muted">Tests recommended together with your current results.</p>
                <?php foreach ($availableTests as $test): ?>
                    <label class="test-option">
                        <!-- All recommended tests are selected at first. The value is the test's code in the lab's store. -->
                        <input type="checkbox" name="tests" value="<?= e($test['storeExaminationId']) ?>" checked>
                        <span>
                            <strong><?= e($test['name']) ?></strong>
                            <span class="muted"><?= e($test['reason']) ?></span>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>

            <div class="modal-actions">
                <button class="btn btn-teal" type="button" id="labplus-add-to-cart">Add to cart</button>
            </div>
        </div>
    </div>

    <!-- DEMO: a real portal would redirect to the store here. -->
    <div class="modal-backdrop" id="labplus-store-dialog" hidden>
        <div class="modal modal-small">
            <button class="modal-close" type="button" aria-label="Close" data-close>&times;</button>
            <h2 class="section-title">Redirect to the store</h2>
            <p>Here the patient should be redirected to the online store. The list of selected tests is passed in a query parameter of the link:</p>
            <code class="store-link" id="labplus-store-link"></code>
            <div class="modal-actions">
                <button class="btn btn-outline" type="button" data-close>Close</button>
            </div>
        </div>
    </div>
<?php endif; ?>
