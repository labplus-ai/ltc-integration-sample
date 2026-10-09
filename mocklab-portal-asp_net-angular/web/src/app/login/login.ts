import { Component, inject, signal } from '@angular/core';
import { Router } from '@angular/router';
import { Page } from '../layout/page';
import { Api } from '../services/api';

// Login page. The form is pre-filled with the demo credentials.
@Component({
  selector: 'app-login',
  imports: [Page],
  templateUrl: './login.html',
})
export class LoginView {
  private api = inject(Api);
  private router = inject(Router);

  // Demo credentials, pre-filled on purpose (demo only). Never do this in a real application.
  protected username = 'patient123';
  protected password = 'veryStrongPassword';

  protected error = signal('');

  constructor() {
    // Already logged in: go straight to the orders list.
    this.api.me().then((user) => {
      if (user) this.router.navigateByUrl('/orders');
    });
  }

  protected async submit(event: Event, username: string, password: string) {
    event.preventDefault();
    // Input validation omitted for demo simplicity.
    if (await this.api.login(username, password)) {
      this.router.navigateByUrl('/orders');
    } else {
      this.error.set('Invalid username or password.');
    }
  }
}
