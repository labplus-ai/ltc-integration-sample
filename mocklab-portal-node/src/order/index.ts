// Order details with the results table.
import { Router } from 'express';
import { Database } from '../services/db.js';
import { orderTitle } from '../services/helpers.js';
import { orderTemplate } from './template.js';

export const order = Router();

order.get('/order', (req, res) => {
  const db = new Database();
  const found = db.getOrder(Number(req.query.id)); // Further input validation omitted for demo simplicity.
  if (!found) {
    res.status(404);
  }

  res.send(orderTemplate({
    header: {
      pageTitle: found?.number ?? 'Order not found',
      heroEyebrow: `Order ${found?.number ?? ''}`,
      heroTitle: found ? orderTitle(found) : 'Order not found',
      heroSubtitle: found ? `Results from ${found.date}` : '',
      user: req.session.user,
    },
    patient: db.getPatient(),
    order: found,
  }));
});
