// Entry point: sets up the web server and mounts the pages.
import express from 'express';
import session from 'express-session';
import './env.js';
import { env } from './env.js';
import { isLoggedIn } from './auth.js';
import { ordersExist } from './lab.js';
import { resetDemo } from './simulator.js';
import * as view from './view.js';
import { login } from './routes/login.js';
import { logout } from './routes/logout.js';
import { orders } from './routes/orders.js';
import { order } from './routes/order.js';
import { demo } from './routes/demo.js';

// Data kept in the session.
declare module 'express-session' {
  interface SessionData {
    user: string;
    flash: string;
  }
}

const app = express();
app.set('view engine', 'ejs');
app.set('views', 'views');
app.use('/assets', express.static('assets'));
app.use(express.urlencoded({ extended: false }));

// Sessions are kept in memory (lost on restart). The secret is hardcoded for demo simplicity.
app.use(session({ secret: 'mocklab-demo-secret', resave: false, saveUninitialized: false }));

// Make the logged-in state and the display helpers available in every template.
app.use((req, res, next) => {
  res.locals.loggedIn = isLoggedIn(req);
  res.locals.user = req.session.user;
  Object.assign(res.locals, view);
  next();
});

app.use(login, logout, orders, order, demo);

// First run: put the starting orders in place.
if (!ordersExist()) {
  resetDemo();
}

const port = Number(env('PORT', '3000'));
app.listen(port, () => console.log(`MockLab portal listening on http://localhost:${port}`));
