import { Component, inject, input } from '@angular/core';
import { Router, RouterLink } from '@angular/router';
import { Api } from '../services/api';

// The frame shared by all pages: header with the logo and logout, the green hero, and the footer.
// The content of a page goes inside <app-page>...</app-page>.
@Component({
  selector: 'app-page',
  imports: [RouterLink],
  templateUrl: './page.html',
})
export class Page {
  protected api = inject(Api);
  private router = inject(Router);

  eyebrow = input.required<string>();
  title = input.required<string>();
  subtitle = input('');

  protected async logout() {
    await this.api.logout();
    this.router.navigateByUrl('/login');
  }
}
