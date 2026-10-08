// DEMO ONLY: handles the buttons from views/partials/demo_box.ejs.
// CSRF protection and other validation omitted for demo simplicity.
import { Router } from 'express';
import { requireLogin } from '../auth.js';
import { releaseNextResults, resetDemo } from '../simulator.js';
import { orderTitle } from '../view.js';

export const demo = Router();

demo.post('/demo', requireLogin, (req, res) => {
  if (req.body.action === 'reset') {
    resetDemo();
    req.session.flash = 'Demo data was reset to the initial 2 orders.';
  } else {
    const order = releaseNextResults();
    req.session.flash = `New results arrived: order ${order.number} (${orderTitle(order)}).`;
  }
  res.redirect('/orders');
});
