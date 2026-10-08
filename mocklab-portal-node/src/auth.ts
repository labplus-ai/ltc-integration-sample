// Login handling of the portal.
import type { NextFunction, Request, Response } from 'express';

// Demo credentials, hardcoded and compared in plain text.
// Password hashing, rate limiting and CSRF protection omitted for demo simplicity.
export const DEMO_USER = 'patient123';
export const DEMO_PASSWORD = 'veryStrongPassword';

export function isLoggedIn(req: Request): boolean {
  return Boolean(req.session.user);
}

// Redirect to the login page unless the user is logged in.
export function requireLogin(req: Request, res: Response, next: NextFunction): void {
  if (!isLoggedIn(req)) {
    res.redirect('/');
    return;
  }
  next();
}

// Logs the user in when the credentials match. Returns whether it succeeded.
export function attemptLogin(req: Request, username: string, password: string): boolean {
  if (username !== DEMO_USER || password !== DEMO_PASSWORD) {
    return false;
  }
  req.session.user = DEMO_USER;
  return true;
}
