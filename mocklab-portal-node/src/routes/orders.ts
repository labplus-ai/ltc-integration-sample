// Orders list: one card per order, newest first.
import { Router } from 'express';
import { requireLogin } from '../auth.js';
import { loadOrders, patient } from '../lab.js';

export const orders = Router();

orders.get('/orders', requireLogin, (req, res) => {
  const list = Object.values(loadOrders()).sort((a, b) => b.id - a.id);

  const flash = req.session.flash ?? null;
  delete req.session.flash;

  res.render('orders', {
    pageTitle: 'Orders',
    heroEyebrow: 'Patient portal',
    heroTitle: 'Your lab results',
    heroSubtitle: 'Pick an order to see its results.',
    flash,
    patient: patient(),
    orders: list,
  });
});
