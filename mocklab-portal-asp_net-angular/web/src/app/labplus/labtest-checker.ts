import { Component, ElementRef, OnDestroy, OnInit, inject, input, output, signal, viewChild } from '@angular/core';
import { Api } from '../services/api';
import { LabplusApi, type LtcStatus } from './labplus-api';

// Defined by the adapter script loaded in index.html. It is a global variable but not a property of
// window (the script declares it with "let"), so it is reached by its name.
declare const labplus: { adapter(iframe: HTMLIFrameElement, options?: object): void } | undefined;

// Card that opens LabTest Checker (LTC) in an iframe: a short medical interview about the results
// and a Health Report at the end. The interview itself runs inside the iframe, on Labplus' side.
@Component({
  selector: 'app-labtest-checker',
  templateUrl: './labtest-checker.html',
})
export class LabtestChecker implements OnInit, OnDestroy {
  private api = inject(Api);
  private labplusApi = inject(LabplusApi);

  orderId = input.required<number>();

  // Tells the results summary that LabTest Checker was opened (from then on it takes the whole card).
  started = output<void>();

  protected status = signal<LtcStatus | null>(null);
  protected starting = signal(false);
  protected opened = signal(false);

  private frame = viewChild.required<ElementRef<HTMLIFrameElement>>('frame');

  // Known only after starting: where the iframe comes from, and the single-use token it waits for.
  private origin = '';
  private initToken: string | null = null;

  ngOnInit() {
    this.loadStatus();
  }

  ngOnDestroy() {
    window.removeEventListener('message', this.onMessage);
  }

  private async loadStatus() {
    this.status.set(await this.labplusApi.getLtcStatus(this.orderId()));
  }

  // The button label depends on how far the patient got.
  protected buttonLabel(): string {
    if (this.status() === 'finished') return 'See your Health Report';
    return this.status() === 'in_progress' ? 'Continue the questionnaire' : 'Start the interpretation';
  }

  protected async start() {
    this.starting.set(true);
    // A fresh initToken is fetched only now, right before opening the iframe (it expires after 180 s).
    const session = await this.labplusApi.startLtc(this.orderId());
    this.starting.set(false);
    if (!session) {
      this.status.set('error');
      return;
    }
    this.origin = session.origin;
    this.initToken = session.initToken;

    // Listen first, then load the iframe, so that its "ready" message is not missed.
    window.addEventListener('message', this.onMessage);
    const iframe = this.frame().nativeElement;
    this.opened.set(true);
    this.started.emit();
    // Lets the adapter script from index.html take care of the iframe (its height and scrolling).
    // It is skipped when the script did not load.
    if (typeof labplus !== 'undefined') labplus.adapter(iframe, { handleScrolling: true });
    iframe.src = session.iframeUrl;
  }

  // Messages from the LTC iframe. Anything that is not trusted or not from the LabTest Checker iframe host (LABPLUS_LTC_IFRAME_URL) is ignored.
  private onMessage = (event: MessageEvent) => {
    if (!event.isTrusted || event.origin !== this.origin) return;
    // Both messages we handle are objects. The iframe also sends JSON strings (e.g. lp_height_change),
    // those are for the adapter script and are ignored here.
    const data: { event?: string; type?: string } | null = typeof event.data === 'object' ? event.data : null;

    // Handshake: the widget is ready ({ event: "lp_init_ready" }), so we hand it the initToken
    // (it waits for it at most 30 s). Only once: the token is single use.
    if (data?.event === 'lp_init_ready' && this.initToken) {
      this.frame().nativeElement.contentWindow?.postMessage({ event: 'lp_init_token', initToken: this.initToken }, this.origin);
      this.initToken = null;
    }

    // The patient is active in the iframe ({ type: "lp_user_keepalive" }): keep our own session alive
    // (ASP.NET sessions expire after 20 minutes without requests). Any request to our API does it.
    if (data?.type === 'lp_user_keepalive') {
      this.api.me();
    }
  };
}

