// Destroy the session and go back to the login page.
import { Router } from 'express';

export const logout = Router();

logout.get('/logout', (req, res) => {
  req.session.destroy(() => res.redirect('/'));
});
