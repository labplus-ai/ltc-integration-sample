// Data types used across the portal.

// One measured parameter, e.g. "Hemoglobin". Norms are null when open-ended (e.g. only an upper limit).
export interface Param {
  paramId: number;
  name: string;
  value: number | string; // text for qualitative results, e.g. "detected"
  unit: string | null;
  normLow: number | null;
  normHigh: number | null;
}

export interface Examination {
  examinationId: number;
  name: string;
  params: Param[];
}

export interface Order {
  id: number;
  number: string;
  date: string; // YYYY-MM-DD, the day the lab released the results
  collectedAt: string; // YYYY-MM-DD HH:MM:SS, when the sample was taken
  doctor: string;
  examinations: Examination[];
}

// What the lab stores about the person. The national ID identifies them (like PESEL in Poland).
export interface Patient {
  firstName: string;
  lastName: string;
  gender: string;
  birthDate: string;
  nationalId: string;
  email: string;
  phone: string;
}
