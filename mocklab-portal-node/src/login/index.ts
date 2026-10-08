// Login page. The form is pre-filled with the demo credentials.
import { Router } from 'express';
import { loginTemplate } from './template.js';

export const login = Router();

// Demo credentials, hardcoded and compared in plain text.
// Password hashing, rate limiting and CSRF protection omitted for demo simplicity.
const username = 'patient123';
const password = 'veryStrongPassword';

function page(error: string): string {
  return loginTemplate({
    header: {
      pageTitle: 'Log in',
      heroEyebrow: 'Patient portal',
      heroTitle: 'Your lab results',
      heroSubtitle: 'Log in to see your orders and test results.',
    },
    error,
    username,
    password,
  });
}

login.get('/login', (req, res) => {
  // Already logged in: go straight to the orders list.
  if (req.session.user) {
    res.redirect('/orders/');
    return;
  }
  res.send(page(''));
});

login.post('/login', (req, res) => {
  // Input validation omitted for demo simplicity.
  if (req.body.username === username && req.body.password === password) {
    req.session.user = username;
    res.redirect('/orders/');
    return;
  }
  res.send(page('Invalid username or password.'));
});
