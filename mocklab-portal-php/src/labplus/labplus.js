// Labplus integration in the browser, on the order page. The results summary is rendered by PHP
// (src/labplus/summary.php); this script adds only what needs JavaScript:
//   1. asking again for the preinterpretation while Labplus is still preparing it,
//   2. opening LabTest Checker in the iframe (the initToken handshake and the keep-alive),
//   3. the countdown and the dialogs of the tests that can be done on the collected sample.
// The browser never talks to the Labplus API, only to our pages in /labplus/api/ (the keys stay on the server).
(function () {
    const section = document.getElementById('labplus');
    if (!section) return;
    const orderId = section.dataset.orderId;

    const preinterpretationBox = document.getElementById('labplus-preinterpretation');
    const testsBox = document.getElementById('labplus-tests');

    // ---- 1. Preinterpretation ----

    // Polling schedule proposed by Labplus: wait 3 s, 5 s, 10 s, then every 30 s, for at most 10 minutes.
    const POLL_DELAYS_SECONDS = [3, 5, 10];
    const POLL_EVERY_SECONDS = 30;
    const POLL_MAX_MS = 10 * 60 * 1000;
    const pollingSince = Date.now();
    let attempt = 0;

    function scheduleNextPoll() {
        const delayMs = (POLL_DELAYS_SECONDS[attempt++] ?? POLL_EVERY_SECONDS) * 1000;
        if (Date.now() + delayMs - pollingSince > POLL_MAX_MS) {
            document.getElementById('labplus-processing-text').textContent =
                'Your interpretation is taking longer than usual. Please come back later.';
            return;
        }
        setTimeout(loadPreinterpretation, delayMs);
    }

    // Our page answers with { status, html, testsHtml }: the same parts the order page renders.
    async function loadPreinterpretation() {
        const response = await fetch(`/labplus/api/preinterpretation/?id=${orderId}`);
        const result = await response.json();
        preinterpretationBox.dataset.status = result.status;
        preinterpretationBox.innerHTML = result.html;
        testsBox.innerHTML = result.testsHtml;
        startCountdown();
        if (result.status === 'processing') scheduleNextPoll();
    }

    if (preinterpretationBox.dataset.status === 'processing') scheduleNextPoll();

    // ---- 2. LabTest Checker ----

    const startButton = document.getElementById('labplus-ltc-start');
    const frame = document.getElementById('labplus-ltc-frame');
    // Known only after starting: where the iframe comes from, and the single-use token it waits for.
    let origin = '';
    let initToken = null;

    startButton?.addEventListener('click', async () => {
        startButton.disabled = true;
        // A fresh initToken is fetched only now, right before opening the iframe (it expires after 180 s).
        const response = await fetch(`/labplus/api/ltc-start/?id=${orderId}`, { method: 'POST' });
        if (!response.ok) {
            document.getElementById('labplus-ltc-intro').innerHTML =
                '<p class="muted">LabTest Checker is not available right now.</p>';
            return;
        }
        const session = await response.json(); // { iframeUrl, origin, initToken, interviewStatus }
        origin = session.origin;
        initToken = session.initToken;

        // Listen first, then load the iframe, so that its "ready" message is not missed.
        window.addEventListener('message', onMessage);

        // LabTest Checker is a separate service, not part of the summary: from now on it takes the whole card.
        document.getElementById('labplus-eyebrow').textContent = 'LabTest Checker';
        document.getElementById('labplus-title').textContent = 'Your Health Report';
        document.getElementById('labplus-summary').classList.add('summary-ltc-open');
        document.getElementById('labplus-ltc').classList.add('ltc-open');
        document.getElementById('labplus-ltc-intro').hidden = true;
        preinterpretationBox.hidden = true;
        testsBox.hidden = true;
        frame.hidden = false;

        // Lets the adapter script (loaded before this one) take care of the iframe: its height and scrolling.
        // It is skipped when the script did not load.
        if (typeof labplus !== 'undefined') labplus.adapter(frame, { handleScrolling: true });
        frame.src = session.iframeUrl;
    });

    // Messages from the LTC iframe. Anything that is not trusted or not from the iframe host is ignored.
    function onMessage(event) {
        if (!event.isTrusted || event.origin !== origin) return;
        // Both messages we handle are objects. The iframe also sends JSON strings (e.g. lp_height_change),
        // those are for the adapter script and are ignored here.
        const data = typeof event.data === 'object' ? event.data : null;

        // Handshake: the widget is ready ({ event: "lp_init_ready" }), so we hand it the initToken
        // (it waits for it at most 30 s). Only once: the token is single use.
        if (data?.event === 'lp_init_ready' && initToken) {
            frame.contentWindow.postMessage({ event: 'lp_init_token', initToken }, origin);
            initToken = null;
        }

        // The patient is active in the iframe ({ type: "lp_user_keepalive" }): keep our own session alive,
        // so the patient is not logged out of the portal while answering the questionnaire.
        if (data?.type === 'lp_user_keepalive') {
            fetch('/labplus/api/keepalive/', { method: 'POST' });
        }
    }

    // ---- 3. Tests on the collected sample ----

    // The lab's online store. The address and the name of the parameter with the test codes are agreed with
    // Labplus during the integration; bbp=1 tells the store that no new sample collection is needed.
    const STORE_CART_URL = 'https://shop.mocklab.example/cart';

    // Time left until the last deadline, as HH:MM:SS. The bar disappears when it runs out.
    let countdownTimer;
    function startCountdown() {
        clearInterval(countdownTimer);
        const bar = document.getElementById('labplus-bbp');
        if (!bar) return;
        const until = Number(bar.dataset.until) * 1000;
        const pad = (n) => String(n).padStart(2, '0');
        const tick = () => {
            const seconds = Math.floor((until - Date.now()) / 1000);
            if (seconds <= 0) {
                bar.remove();
                clearInterval(countdownTimer);
                return;
            }
            document.getElementById('labplus-countdown').textContent =
                `${pad(Math.floor(seconds / 3600))}:${pad(Math.floor(seconds / 60) % 60)}:${pad(seconds % 60)}`;
        };
        tick();
        countdownTimer = setInterval(tick, 1000);
    }
    startCountdown();

    // Dialogs: [data-open="<dialog id>"] opens one, [data-close] or a click outside closes it.
    testsBox.addEventListener('click', (event) => {
        const target = event.target;
        if (target.dataset.open) {
            document.getElementById(target.dataset.open).hidden = false;
        } else if (target.hasAttribute('data-close') || target.classList.contains('modal-backdrop')) {
            target.closest('.modal-backdrop').hidden = true;
        } else if (target.id === 'labplus-add-to-cart') {
            // DEMO: shows the store link with the codes of the selected tests instead of redirecting.
            const codes = [...testsBox.querySelectorAll('input[name="tests"]:checked')].map((input) => input.value);
            document.getElementById('labplus-store-link').textContent = `${STORE_CART_URL}?codes=${codes.join(',')}&bbp=1`;
            document.getElementById('labplus-tests-dialog').hidden = true;
            document.getElementById('labplus-store-dialog').hidden = false;
        }
    });
})();
