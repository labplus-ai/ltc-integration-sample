import { HttpClient } from '@angular/common/http';
import { Component, inject, output } from '@angular/core';
import { firstValueFrom } from 'rxjs';
import type { Order } from '../services/types';
import { orderTitle } from '../services/helpers';

// DEMO ONLY box with the buttons that simulate the lab. Not part of a real patient portal.
// There is no need to read this when studying the integration.
@Component({
  selector: 'app-demo-box',
  templateUrl: './demo-box.html',
})
export class DemoBox {
  private http = inject(HttpClient);

  // Tells the orders page that the orders changed, with a message to show.
  changed = output<string>();

  // "The lab has just released new results": the API adds the next result set as a new order.
  protected async simulate() {
    const order = await firstValueFrom(
      this.http.post<Order>('/api/data-simulation__only-for-demo/release-next-results', {}),
    );
    this.changed.emit(`New results arrived: order ${order.number} (${orderTitle(order)}).`);
  }

  // Back to the initial 2 orders.
  protected async reset() {
    await firstValueFrom(this.http.post('/api/data-simulation__only-for-demo/reset', {}));
    this.changed.emit('Demo data was reset to the initial 2 orders.');
  }
}
