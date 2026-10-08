// Order details with the results table.
import { Router } from 'express';
import { requireLogin } from '../auth.js';
import { findOrder, patient } from '../lab.js';
import { orderTitle } from '../view.js';

export const order = Router();

order.get('/orders/:id', requireLogin, (req, res) => {
  const found = findOrder(Number(req.params.id)); // Further input validation omitted for demo simplicity.
  if (!found) {
    res.status(404);
  }

  res.render('order', {
    pageTitle: found?.number ?? 'Order not found',
    heroEyebrow: `Order ${found?.number ?? ''}`,
    heroTitle: found ? orderTitle(found) : 'Order not found',
    heroSubtitle: found ? `Results from ${found.date}` : '',
    patient: patient(),
    order: found ?? null,
  });
});
