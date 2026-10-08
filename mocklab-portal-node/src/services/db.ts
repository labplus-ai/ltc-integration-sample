// The lab's own data: the patient and their orders.
// In a real lab this would be a database (LIS). Here the orders live in a JSON file (storage/orders.json),
// so the demo needs no database. Error handling for an unwritable storage folder omitted for demo simplicity.
import { existsSync, mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import { join } from 'node:path';
import { DataSimulator } from '../data-simulation__only-for-demo/simulator.js';
import type { Order, Patient } from './types.js';

const storageDir = join(import.meta.dirname, '../../storage');
const ordersFile = join(storageDir, 'orders.json');

export class Database {
  constructor() {
    // DEMO ONLY: on the first run put the starting orders in place.
    if (!existsSync(ordersFile)) {
      new DataSimulator(this).reset();
    }
  }

  // The patient who logs in. DEMO ONLY: read from the demo data, a real system reads its own database.
  getPatient(): Patient {
    const file = join(import.meta.dirname, '../data-simulation__only-for-demo/patient.json');
    return JSON.parse(readFileSync(file, 'utf8'));
  }

  // All orders, keyed by order id.
  getOrders(): Record<number, Order> {
    return JSON.parse(readFileSync(ordersFile, 'utf8'));
  }

  // One order, or undefined when it does not exist.
  getOrder(id: number): Order | undefined {
    return this.getOrders()[id];
  }

  // Stores one order (creates it or overwrites the existing one with the same id).
  saveOrder(order: Order): void {
    const orders = this.getOrders();
    orders[order.id] = order;
    this.saveOrders(orders);
  }

  saveOrders(orders: Record<number, Order>): void {
    mkdirSync(storageDir, { recursive: true });
    writeFileSync(ordersFile, JSON.stringify(orders, null, 2));
  }

  // Adds a new order and gives it an id and an order number. Returns the stored order.
  addOrder(order: Omit<Order, 'id' | 'number'>): Order {
    const ids = Object.keys(this.getOrders()).map(Number);
    const id = ids.length ? Math.max(...ids) + 1 : 1;
    const number = `ML-${new Date().getFullYear()}-${String(200 + id).padStart(6, '0')}`;
    const saved: Order = { id, number, ...order };
    this.saveOrder(saved);
    return saved;
  }
}
