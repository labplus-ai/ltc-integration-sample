// Small helpers for displaying orders and results.
import type { Order, Param, Patient } from './types';

export type Flag = 'low' | 'high' | 'normal' | 'none';

function isNumeric(value: number | string): boolean {
  return value !== '' && Number.isFinite(Number(value));
}

// Flag for one parameter: 'low', 'high', 'normal', or 'none' when it cannot be judged
// (a text result such as "detected", or no norms at all).
export function resultFlag(param: Param): Flag {
  if (!isNumeric(param.value) || (param.normLow === null && param.normHigh === null)) {
    return 'none';
  }
  const value = Number(param.value);
  if (param.normLow !== null && value < param.normLow) return 'low';
  if (param.normHigh !== null && value > param.normHigh) return 'high';
  return 'normal';
}

// Human readable reference range, e.g. "13.5 - 17.5", "< 190" or "> 40".
export function referenceText(param: Param): string {
  const { normLow: low, normHigh: high } = param;
  if (low === null && high === null) return '-';
  if (low === null) return `< ${high}`;
  return high === null ? `> ${low}` : `${low} - ${high}`;
}

// All parameters of an order as a flat list.
export function orderParams(order: Order): Param[] {
  return order.examinations.flatMap((exam) => exam.params);
}

// Number of parameters outside their reference range.
export function countOutOfRange(order: Order): number {
  return orderParams(order).filter((p) => ['low', 'high'].includes(resultFlag(p))).length;
}

// Title of an order for lists and headings: the examination names, or the first one plus "and N more".
export function orderTitle(order: Order): string {
  const names = order.examinations.map((exam) => exam.name);
  return names.length <= 2 ? names.join(', ') : `${names[0]} and ${names.length - 1} more`;
}

// Short patient description, e.g. "Male, 33 years".
export function patientText(patient: Patient): string {
  const born = new Date(patient.birthDate);
  const now = new Date();
  let age = now.getFullYear() - born.getFullYear();
  if (now < new Date(now.getFullYear(), born.getMonth(), born.getDate())) age--;
  return `${patient.gender.charAt(0).toUpperCase()}${patient.gender.slice(1)}, ${age} years`;
}
