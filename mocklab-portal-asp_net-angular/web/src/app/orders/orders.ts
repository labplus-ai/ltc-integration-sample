import { Component, inject, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { DemoBox } from '../data-simulation__only-for-demo/demo-box';
import { Page } from '../layout/page';
import { Api } from '../services/api';
import { countOutOfRange, orderParams, orderTitle, patientText } from '../services/helpers';
import type { Order, Patient } from '../services/types';

// Orders list: one card per order, newest first.
@Component({
  selector: 'app-orders',
  imports: [Page, RouterLink, DemoBox],
  templateUrl: './orders.html',
})
export class OrdersView {
  private api = inject(Api);

  protected patient = signal<Patient | null>(null);
  protected orders = signal<Order[]>([]);

  // DEMO ONLY: message shown after using the simulation buttons.
  protected flash = signal('');

  // Helpers used by the template.
  protected orderTitle = orderTitle;
  protected orderParams = orderParams;
  protected countOutOfRange = countOutOfRange;
  protected patientText = patientText;

  constructor() {
    this.load();
  }

  private async load() {
    this.patient.set(await this.api.getPatient());
    this.orders.set(await this.api.getOrders());
  }

  // DEMO ONLY: called by the demo box after the simulation changed the orders.
  protected onSimulationChange(message: string) {
    this.flash.set(message);
    this.load();
  }
}
