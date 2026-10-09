import { Component, OnDestroy, OnInit, inject, input, signal } from '@angular/core';
import { AdditionalTests } from './additional-tests';
import { LabplusApi, type Preinterpretation as Result } from './labplus-api';
import { LabtestChecker } from './labtest-checker';

// Polling schedule proposed by Labplus: wait 3 s, 5 s, 10 s, then every 30 s, for at most 10 minutes.
const POLL_DELAYS_SECONDS = [3, 5, 10];
const POLL_EVERY_SECONDS = 30;
const POLL_MAX_MS = 10 * 60 * 1000;

// "Results summary" on the order page: the preliminary interpretation made by Labplus, the button that
// opens LabTest Checker, and the bar about tests that can still be done on the collected sample.
// Our API started the preinterpretation when the results were released, so it is usually ready by now.
@Component({
  selector: 'app-preinterpretation',
  imports: [LabtestChecker, AdditionalTests],
  templateUrl: './preinterpretation.html',
})
export class Preinterpretation implements OnInit, OnDestroy {
  private labplus = inject(LabplusApi);

  orderId = input.required<number>();

  protected result = signal<Result | null>(null);
  protected timedOut = signal(false);

  // While LabTest Checker is open it takes the whole card: it is a separate service, not part of the summary.
  protected ltcOpen = signal(false);

  private attempt = 0;
  private pollingSince = 0;
  private timer?: ReturnType<typeof setTimeout>;
  private destroyed = false;

  ngOnInit() {
    this.load();
  }

  // Stop polling when the patient leaves the page.
  ngOnDestroy() {
    this.destroyed = true;
    clearTimeout(this.timer);
  }

  private async load() {
    const result = await this.labplus.getPreinterpretation(this.orderId());
    if (this.destroyed) return;
    this.result.set(result);
    if (result.status === 'processing') this.scheduleNextPoll();
  }

  // Asks again later while Labplus is still processing (it answers 202), following the schedule above.
  private scheduleNextPoll() {
    this.pollingSince ||= Date.now();
    const delayMs = (POLL_DELAYS_SECONDS[this.attempt++] ?? POLL_EVERY_SECONDS) * 1000;
    if (Date.now() + delayMs - this.pollingSince > POLL_MAX_MS) {
      this.timedOut.set(true);
      return;
    }
    this.timer = setTimeout(() => this.load(), delayMs);
  }
}
