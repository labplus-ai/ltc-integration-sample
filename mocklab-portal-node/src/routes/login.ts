// Login page. The form is pre-filled with the demo credentials.
import { Router, type Response } from 'express';
import { DEMO_PASSWORD, DEMO_USER, attemptLogin, isLoggedIn } from '../auth.js';

export const login = Router();

function renderLogin(res: Response, error: string): void {
  res.render('login', {
    pageTitle: 'Log in',
    heroEyebrow: 'Patient portal',
    heroTitle: 'Your lab results',
    heroSubtitle: 'Log in to see your orders and test results.',
    error,
    DEMO_USER,
    DEMO_PASSWORD,
  });
}

login.get('/', (req, res) => {
  // Already logged in: go straight to the orders list.
  if (isLoggedIn(req)) {
    res.redirect('/orders');
    return;
  }
  renderLogin(res, '');
});

login.post('/', (req, res) => {
  // Input validation and CSRF token check omitted for demo simplicity.
  if (attemptLogin(req, req.body.username ?? '', req.body.password ?? '')) {
    res.redirect('/orders');
    return;
  }
  renderLogin(res, 'Invalid username or password.');
});
