import { HttpClient } from '@angular/common/http';
import { Injectable, inject, signal } from '@angular/core';
import { firstValueFrom } from 'rxjs';
import type { Order, Patient } from './types';

// Talks to the API. In development the dev server forwards /api to it (see proxy.conf.js).
// Error handling omitted for demo simplicity.
@Injectable({ providedIn: 'root' })
export class Api {
  private http = inject(HttpClient);

  // The logged-in user (null when nobody is logged in).
  user = signal<string | null>(null);

  // Asks the API who is logged in. Returns the user name or null.
  async me(): Promise<string | null> {
    try {
      const res = await firstValueFrom(this.http.get<{ user: string }>('/api/me'));
      this.user.set(res.user);
    } catch {
      this.user.set(null);
    }
    return this.user();
  }

  // Returns true when the credentials were accepted.
  async login(username: string, password: string): Promise<boolean> {
    try {
      const res = await firstValueFrom(this.http.post<{ user: string }>('/api/login', { username, password }));
      this.user.set(res.user);
      return true;
    } catch {
      return false;
    }
  }

  async logout(): Promise<void> {
    await firstValueFrom(this.http.post('/api/logout', {}));
    this.user.set(null);
  }

  getPatient(): Promise<Patient> {
    return firstValueFrom(this.http.get<Patient>('/api/patient'));
  }

  // All orders, newest first.
  getOrders(): Promise<Order[]> {
    return firstValueFrom(this.http.get<Order[]>('/api/orders'));
  }

  // One order, or null when it does not exist.
  async getOrder(id: string): Promise<Order | null> {
    try {
      return await firstValueFrom(this.http.get<Order>(`/api/orders/${encodeURIComponent(id)}`));
    } catch {
      return null;
    }
  }
}
