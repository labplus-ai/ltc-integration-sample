import { Component, OnDestroy, OnInit, computed, input, signal } from '@angular/core';
import type { RecommendedExamination } from './labplus-api';

// The lab's online store. The address and the name of the parameter with the test codes are agreed with
// Labplus during the integration; bbp=1 tells the store that no new sample collection is needed.
const STORE_CART_URL = 'https://shop.mocklab.example/cart';

// Bar with a countdown and a list of recommended tests that can still be done on the sample the lab keeps
// ("tests without collection", BBP). Labplus gives the deadline of each test in bbpValidUntil.
@Component({
  selector: 'app-additional-tests',
  templateUrl: './additional-tests.html',
})
export class AdditionalTests implements OnInit, OnDestroy {
  recommended = input.required<RecommendedExamination[]>();

  // Ticks every second for the countdown.
  private now = signal(Date.now());
  private timer?: ReturnType<typeof setInterval>;

  // Recommended tests whose deadline has not passed yet.
  protected available = computed(() =>
    this.recommended().filter((exam) => exam.bbpValidUntil && Date.parse(exam.bbpValidUntil) > this.now()),
  );

  // Time left until the last of these deadlines, as HH:MM:SS.
  protected countdown = computed(() => {
    const until = Math.max(...this.available().map((exam) => Date.parse(exam.bbpValidUntil!)));
    const seconds = Math.max(0, Math.floor((until - this.now()) / 1000));
    const pad = (n: number) => String(n).padStart(2, '0');
    return `${pad(Math.floor(seconds / 3600))}:${pad(Math.floor(seconds / 60) % 60)}:${pad(seconds % 60)}`;
  });

  protected listOpen = signal(false);
  protected storeOpen = signal(false);
  protected selected = signal<(string | number)[]>([]);

  // DEMO: the link the patient would be sent to, e.g. https://shop.mocklab.example/cart?codes=A23,B12&bbp=1
  protected storeLink = computed(() => `${STORE_CART_URL}?codes=${this.selected().join(',')}&bbp=1`);

  ngOnInit() {
    this.timer = setInterval(() => this.now.set(Date.now()), 1000);
  }

  ngOnDestroy() {
    clearInterval(this.timer);
  }

  // All recommended tests are selected at first.
  protected openList() {
    this.selected.set(this.available().map((exam) => exam.storeExaminationId));
    this.listOpen.set(true);
  }

  protected toggle(id: string | number) {
    this.selected.update((ids) => (ids.includes(id) ? ids.filter((x) => x !== id) : [...ids, id]));
  }

  protected addToCart() {
    this.listOpen.set(false);
    this.storeOpen.set(true);
  }
}
