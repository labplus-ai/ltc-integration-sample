// Entry point: sets up the web server and mounts the pages.
import express from 'express';
import session from 'express-session';
import { join } from 'node:path';
import { env } from './services/env.js';
import { login } from './login/index.js';
import { logout } from './logout/index.js';
import { orders } from './orders/index.js';
import { order } from './order/index.js';
import { simulation } from './data-simulation__only-for-demo/index.js';

// Data kept in the session.
declare module 'express-session' {
  interface SessionData {
    user: string;
    flash: string;
  }
}

const app = express();
app.use('/styles', express.static(join(import.meta.dirname, 'styles')));
app.use(express.urlencoded({ extended: false }));

// Sessions are kept in memory (lost on restart). The secret is hardcoded for demo simplicity.
app.use(session({ secret: 'mocklab-demo-secret', resave: false, saveUninitialized: false }));

app.get('/', (_req, res) => res.redirect('/login/'));
app.use(login, logout);

// Everything below requires a logged-in user.
app.use((req, res, next) => (req.session.user ? next() : res.redirect('/login/')));
app.use(orders, order, simulation);

const port = Number(env('PORT', '3000'));
app.listen(port, () => console.log(`MockLab portal listening on http://localhost:${port}`));
