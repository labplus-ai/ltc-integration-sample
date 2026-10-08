// The lab's own data: the patient and their orders.
// In a real lab this would be a database (LIS). Here the orders live in a JSON file,
// so the demo needs no database. Error handling for an unwritable storage folder omitted for demo simplicity.
import { existsSync, mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import type { Order, Patient } from './types.js';

export const ORDERS_FILE = 'storage/orders.json';

export function patient(): Patient {
  return JSON.parse(readFileSync('data/patient.json', 'utf8'));
}

// All orders, keyed by order id.
export function loadOrders(): Record<number, Order> {
  return JSON.parse(readFileSync(ORDERS_FILE, 'utf8'));
}

// Returns one order, or undefined when it does not exist.
export function findOrder(id: number): Order | undefined {
  return loadOrders()[id];
}

// Stores one order (creates it or overwrites the existing one with the same id).
export function saveOrder(order: Order): void {
  const orders = loadOrders();
  orders[order.id] = order;
  saveOrders(orders);
}

export function saveOrders(orders: Record<number, Order>): void {
  mkdirSync('storage', { recursive: true });
  writeFileSync(ORDERS_FILE, JSON.stringify(orders, null, 2));
}

export function ordersExist(): boolean {
  return existsSync(ORDERS_FILE);
}

// Adds a new order and gives it an id and an order number. Returns the stored order.
export function addOrder(order: Omit<Order, 'id' | 'number'>): Order {
  const ids = Object.keys(loadOrders()).map(Number);
  const id = ids.length ? Math.max(...ids) + 1 : 1;
  const number = `ML-${new Date().getFullYear()}-${String(200 + id).padStart(6, '0')}`;
  const saved: Order = { id, number, ...order };
  saveOrder(saved);
  return saved;
}
