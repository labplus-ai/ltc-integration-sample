// Orders list: one card per order, newest first.
import { Router } from 'express';
import { Database } from '../services/db.js';
import { ordersTemplate } from './template.js';

export const orders = Router();

orders.get('/orders', (req, res) => {
  const db = new Database();
  const patient = db.getPatient();
  const list = Object.values(db.getOrders()).sort((a, b) => b.id - a.id);

  // Patient description, e.g. "Male, 33 years".
  const born = new Date(patient.birthDate);
  const now = new Date();
  let age = now.getFullYear() - born.getFullYear();
  if (now < new Date(now.getFullYear(), born.getMonth(), born.getDate())) age--;
  const patientText = `${patient.gender.charAt(0).toUpperCase()}${patient.gender.slice(1)}, ${age} years`;

  // DEMO ONLY: message shown after using the simulation buttons.
  const flash = req.session.flash ?? null;
  delete req.session.flash;

  res.send(ordersTemplate({
    header: {
      pageTitle: 'Orders',
      heroEyebrow: 'Patient portal',
      heroTitle: 'Your lab results',
      heroSubtitle: 'Pick an order to see its results.',
      user: req.session.user,
    },
    flash,
    patient,
    patientText,
    orders: list,
  }));
});
