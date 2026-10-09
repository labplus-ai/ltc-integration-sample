import { TitleCasePipe } from '@angular/common';
import { Component, inject, signal } from '@angular/core';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { Page } from '../layout/page';
import { Api } from '../services/api';
import { orderTitle, referenceText, resultFlag } from '../services/helpers';
import type { Order, Patient } from '../services/types';

// Order details with the results table. The order is chosen by the address: /order?id=3
@Component({
  selector: 'app-order',
  imports: [Page, RouterLink, TitleCasePipe],
  templateUrl: './order.html',
})
export class OrderView {
  private api = inject(Api);

  protected patient = signal<Patient | null>(null);
  protected order = signal<Order | null>(null);
  protected loaded = signal(false);

  // Helpers used by the template.
  protected orderTitle = orderTitle;
  protected resultFlag = resultFlag;
  protected referenceText = referenceText;

  constructor() {
    this.load(inject(ActivatedRoute).snapshot.queryParamMap.get('id') ?? ''); // Further input validation omitted for demo simplicity.
  }

  private async load(id: string) {
    this.patient.set(await this.api.getPatient());
    this.order.set(await this.api.getOrder(id));
    this.loaded.set(true);
  }
}
