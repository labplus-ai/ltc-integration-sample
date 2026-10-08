import { e } from '../services/helpers.js';
import { footer } from '../partials/footer.js';
import { header, type HeaderData } from '../partials/header.js';

export function loginTemplate(data: { header: HeaderData; error: string; username: string; password: string }): string {
  return `${header(data.header)}

<div class="card login-card">
    <h2>Log in</h2>
    <p class="muted">Demo credentials are already filled in. Just press the button.</p>

    ${data.error ? `<div class="alert">${e(data.error)}</div>` : ''}

    <form method="post">
        <label for="username">Username</label>
        <input id="username" name="username" type="text" value="${e(data.username)}">

        <label for="password">Password</label>
        <!-- Pre-filled on purpose (demo only). Never do this in a real application. -->
        <input id="password" name="password" type="password" value="${e(data.password)}">

        <button class="btn btn-primary" type="submit">Log in</button>
    </form>
</div>

${footer()}`;
}
