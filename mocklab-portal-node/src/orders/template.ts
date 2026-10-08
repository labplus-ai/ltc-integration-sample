import { demoBox } from '../data-simulation__only-for-demo/demo_box.js';
import { footer } from '../partials/footer.js';
import { header, type HeaderData } from '../partials/header.js';
import { countOutOfRange, e, orderParams, orderTitle } from '../services/helpers.js';
import type { Order, Patient } from '../services/types.js';

function orderCard(order: Order): string {
  const outOfRange = countOutOfRange(order);
  return `
        <a class="card order-card" href="/order/?id=${order.id}">
            <span class="num-badge">${String(order.id).padStart(2, '0')}</span>
            <h3>${e(orderTitle(order))}</h3>
            <p class="muted">${e(order.number)} &middot; ${e(order.date)}</p>
            <div class="tags">
                <span class="pill pill-grey">${orderParams(order).length} results</span>
                ${outOfRange
                  ? `<span class="pill pill-alert">${outOfRange} out of range</span>`
                  : `<span class="pill pill-mint">All in range</span>`}
            </div>
        </a>`;
}

export function ordersTemplate(data: {
  header: HeaderData;
  flash: string | null;
  patient: Patient;
  patientText: string;
  orders: Order[];
}): string {
  const { patient } = data;
  return `${header(data.header)}

${data.flash ? `<div class="notice">${e(data.flash)}</div>` : ''}

<div class="card info-card">
    <div><span class="label">Patient</span>${e(patient.firstName + ' ' + patient.lastName)}</div>
    <div><span class="label">Gender, age</span>${e(data.patientText)}</div>
    <div><span class="label">Date of birth</span>${e(patient.birthDate)}</div>
    <div><span class="label">National ID</span>${e(patient.nationalId)}</div>
</div>

<div class="eyebrow eyebrow-line">Orders</div>
<h2 class="section-title">Your orders</h2>
<p class="muted">All tests done for you at MockLab.</p>

${demoBox() /* DEMO ONLY */}

<div class="grid">${data.orders.map(orderCard).join('')}
</div>

${footer()}`;
}
