import { footer } from '../partials/footer.js';
import { header, type HeaderData } from '../partials/header.js';
import { e, referenceText, resultFlag } from '../services/helpers.js';
import type { Examination, Order, Param, Patient } from '../services/types.js';

function paramRow(param: Param): string {
  const flag = resultFlag(param);
  const status = flag === 'none'
    ? `<span class="muted">-</span>`
    : `<span class="badge badge-${flag}">${flag.charAt(0).toUpperCase() + flag.slice(1)}</span>`;
  return `
                <tr>
                    <td><strong>${e(param.name)}</strong></td>
                    <td>${e(param.value)}</td>
                    <td>${e(param.unit)}</td>
                    <td>${e(referenceText(param))}</td>
                    <td>${status}</td>
                </tr>`;
}

function examinationRows(exam: Examination): string {
  return `
                <tr class="exam-row"><td colspan="5">${e(exam.name)}</td></tr>${exam.params.map(paramRow).join('')}`;
}

export function orderTemplate(data: { header: HeaderData; patient: Patient; order?: Order }): string {
  const { order, patient } = data;

  if (!order) {
    return `${header(data.header)}

<a class="back-link" href="/orders/">&larr; Back to orders</a>

<div class="card"><p>This order does not exist.</p></div>

${footer()}`;
  }

  return `${header(data.header)}

<a class="back-link" href="/orders/">&larr; Back to orders</a>

<div class="card info-card">
    <div><span class="label">Order number</span>${e(order.number)}</div>
    <div><span class="label">Date</span>${e(order.date)}</div>
    <div><span class="label">Patient</span>${e(patient.firstName + ' ' + patient.lastName)}</div>
    <div><span class="label">Doctor</span>${e(order.doctor)}</div>
</div>

<div class="eyebrow eyebrow-line">Results</div>
<h2 class="section-title">Test results</h2>

<div class="table-wrap">
    <table>
        <thead>
            <tr><th>Test</th><th>Result</th><th>Unit</th><th>Reference range</th><th>Status</th></tr>
        </thead>
        <tbody>${order.examinations.map(examinationRows).join('')}
        </tbody>
    </table>
</div>

<!-- Integration extension point: the LabTest Checker integration will be added below the results. -->

${footer()}`;
}
