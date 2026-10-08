// DEMO ONLY: pretends to be the lab that produces new test results.
// A real portal has nothing like this; orders would simply appear when the lab releases the results.
//
// The files in data/results/ stand for what a lab keeps in its own database: the examinations of one
// order with their measured parameters, as plain data. They are used in file name order.
import { readdirSync, readFileSync } from 'node:fs';
import { addOrder, loadOrders, saveOrders } from './lab.js';
import type { Order } from './types.js';

const DOCTOR = 'Dr. Emily Carter';

function resultFiles(): string[] {
  return readdirSync('data/results')
    .filter((name) => name.endsWith('.json'))
    .sort()
    .map((name) => `data/results/${name}`);
}

// YYYY-MM-DD in local time, $daysAgo days back from today.
function dateDaysAgo(daysAgo: number): string {
  const d = new Date();
  d.setDate(d.getDate() - daysAgo);
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

// Builds an order (without id and number) from a results file. $daysAgo is how long ago the lab released it.
function orderFromResults(file: string, daysAgo: number): Omit<Order, 'id' | 'number'> {
  return {
    date: dateDaysAgo(daysAgo),
    collectedAt: `${dateDaysAgo(daysAgo + 1)} 08:15:00`, // sample taken the day before
    doctor: DOCTOR,
    examinations: JSON.parse(readFileSync(file, 'utf8')).examinations,
  };
}

// Starting point of the demo: the first two result sets are already in the patient's account.
export function resetDemo(): void {
  const files = resultFiles();
  saveOrders({});
  addOrder(orderFromResults(files[0], 14));
  addOrder(orderFromResults(files[1], 7));
}

// "The lab has just released new results": adds the next result set in the queue (3rd, 4th, ...,
// and from the beginning again after the last one) as a new order. Returns the new order.
export function releaseNextResults(): Order {
  const files = resultFiles();
  const next = Object.keys(loadOrders()).length % files.length;
  return addOrder(orderFromResults(files[next], 0));
}
