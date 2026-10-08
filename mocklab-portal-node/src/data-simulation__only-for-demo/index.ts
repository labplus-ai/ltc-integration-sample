// DEMO ONLY: handles the buttons from demo_box.ts (simulate new results / reset).
// There is no need to read this when studying the integration.
// CSRF protection and other validation omitted for demo simplicity.
import { Router } from 'express';
import { Database } from '../services/db.js';
import { orderTitle } from '../services/helpers.js';
import { DataSimulator } from './simulator.js';

export const simulation = Router();

simulation.post('/data-simulation__only-for-demo', (req, res) => {
  const simulator = new DataSimulator(new Database());

  if (req.body.action === 'reset') {
    simulator.reset();
    req.session.flash = 'Demo data was reset to the initial 2 orders.';
  } else {
    const order = simulator.releaseNextResults();
    req.session.flash = `New results arrived: order ${order.number} (${orderTitle(order)}).`;
  }
  res.redirect('/orders/');
});
