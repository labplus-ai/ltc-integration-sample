import { Component } from '@angular/core';
import { RouterOutlet } from '@angular/router';

// The root component only shows the page that matches the address (see app.routes.ts).
@Component({
  selector: 'app-root',
  imports: [RouterOutlet],
  template: '<router-outlet />',
})
export class App {}
