// DEMO ONLY: pretends to be the lab that produces new test results.
// There is no need to read this when studying the integration: a real portal has nothing like it,
// orders would simply appear when the lab releases the results.
//
// The files in results/ stand for what a lab keeps in its own database: the examinations of one
// order with their measured parameters, as plain data. They are used in file name order.
import { readdirSync, readFileSync } from 'node:fs';
import { join } from 'node:path';
import type { Database } from '../services/db.js';
import type { Order } from '../services/types.js';

const DOCTOR = 'Dr. Emily Carter';
const resultsDir = join(import.meta.dirname, 'results');

// YYYY-MM-DD in local time, a number of days back from today.
function dateDaysAgo(daysAgo: number): string {
  const d = new Date();
  d.setDate(d.getDate() - daysAgo);
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

export class DataSimulator {
  constructor(private db: Database) {}

  // Starting point of the demo: the first two result sets are already in the patient's account.
  reset(): void {
    const files = this.resultFiles();
    this.db.saveOrders({});
    this.db.addOrder(this.orderFromResults(files[0], 14));
    this.db.addOrder(this.orderFromResults(files[1], 7));
  }

  // "The lab has just released new results": adds the next result set in the queue (3rd, 4th, ...,
  // and from the beginning again after the last one) as a new order. Returns the new order.
  releaseNextResults(): Order {
    const files = this.resultFiles();
    const next = Object.keys(this.db.getOrders()).length % files.length;
    return this.db.addOrder(this.orderFromResults(files[next], 0));
  }

  private resultFiles(): string[] {
    return readdirSync(resultsDir)
      .filter((name) => name.endsWith('.json'))
      .sort()
      .map((name) => join(resultsDir, name));
  }

  // Builds an order (without id and number) from a results file. daysAgo is how long ago the lab released it.
  private orderFromResults(file: string, daysAgo: number): Omit<Order, 'id' | 'number'> {
    return {
      date: dateDaysAgo(daysAgo),
      collectedAt: `${dateDaysAgo(daysAgo + 1)} 08:15:00`, // sample taken the day before
      doctor: DOCTOR,
      examinations: JSON.parse(readFileSync(file, 'utf8')).examinations,
    };
  }
}
