import { inject } from '@angular/core';
import { CanActivateFn, Router, Routes } from '@angular/router';
import { LoginView } from './login/login';
import { OrderView } from './order/order';
import { OrdersView } from './orders/orders';
import { Api } from './services/api';

// Lets logged-in users through, sends everyone else to the login page.
const loggedIn: CanActivateFn = async () => {
  const loggedInUser = await inject(Api).me();
  return loggedInUser ? true : inject(Router).parseUrl('/login');
};

// One entry per page. The address of a page is its path: /login, /orders, /order?id=3.
export const routes: Routes = [
  { path: '', pathMatch: 'full', redirectTo: 'login' },
  { path: 'login', component: LoginView },
  { path: 'orders', component: OrdersView, canActivate: [loggedIn] },
  { path: 'order', component: OrderView, canActivate: [loggedIn] },
  { path: '**', redirectTo: 'login' },
];
