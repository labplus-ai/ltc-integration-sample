import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { firstValueFrom } from 'rxjs';

// Calls to OUR API for the Labplus integration. The browser never talks to the Labplus API itself:
// the keys stay on the server, which signs the requests (see api/Labplus/).

// Preinterpretation of an order. The fields after "status" are copied by our API from Labplus' response.
// There is no "not started": our API starts it on the first request when that did not happen earlier.
export interface Preinterpretation {
  status: 'not_configured' | 'processing' | 'done' | 'fallback' | 'error';
  content?: string; // HTML, ready for display
  recommendations?: { recommendedExaminations: RecommendedExamination[] };
  examinations?: PreinterpretedExamination[];
}

// A test Labplus recommends to the patient.
export interface RecommendedExamination {
  storeExaminationId: string | number; // ID of the test in the lab's online store
  name: string;
  bbpValidUntil: string | null; // ISO 8601 UTC: until when it can be done on the already collected sample
  reason: string; // may be shown to the patient
}

// How the preinterpretation flagged each parameter of the analysed tests.
export interface PreinterpretedExamination {
  id: string | number;
  name: string;
  params: { id: string | number; name: string; indicator: 'HIGH' | 'LOW' | 'NORM' | 'COMPLEX' | 'NOT_SUPPORTED' }[];
}

// State of LabTest Checker for an order ("new" = the patient has not started the interview yet).
export type LtcStatus = 'not_configured' | 'not_supported' | 'new' | 'in_progress' | 'finished' | 'error';

// Everything needed to open LabTest Checker in the iframe.
export interface LtcSession {
  iframeUrl: string;
  origin: string; // the only origin we accept messages from and send the initToken to
  initToken: string; // single use, expires 180 s after it was created, so it is never stored
  interviewStatus: 'new' | 'in_progress' | 'finished';
}

@Injectable({ providedIn: 'root' })
export class LabplusApi {
  private http = inject(HttpClient);

  // Our API answers {status: "error"} (HTTP 502) when Labplus fails, so any error becomes that status.
  async getPreinterpretation(orderId: number): Promise<Preinterpretation> {
    try {
      return await firstValueFrom(this.http.get<Preinterpretation>(`/api/orders/${orderId}/preinterpretation`));
    } catch {
      return { status: 'error' };
    }
  }

  async getLtcStatus(orderId: number): Promise<LtcStatus> {
    try {
      const res = await firstValueFrom(this.http.get<{ status: LtcStatus }>(`/api/orders/${orderId}/ltc/status`));
      return res.status;
    } catch {
      return 'error';
    }
  }

  // Asks our API for a fresh initToken. Call it right before opening the iframe. Returns null on error.
  async startLtc(orderId: number): Promise<LtcSession | null> {
    try {
      return await firstValueFrom(this.http.post<LtcSession>(`/api/orders/${orderId}/ltc/start`, {}));
    } catch {
      return null;
    }
  }
}
