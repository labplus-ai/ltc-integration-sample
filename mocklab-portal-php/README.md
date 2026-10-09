# MockLab Patient Portal (demo)

A tiny results-pickup portal for a fictional laboratory, **MockLab**. Written in plain PHP (no framework, no database, no Composer). Orders are kept in a small JSON file.

There is also an **Angular + ASP.NET Core version** of the same portal in `../mocklab-portal-asp_net-angular`.

It is intentionally simple. It is **not** a real portal: validation and security are skipped on purpose (marked with comments in the code). It shows how to integrate the Labplus services: the **preliminary interpretation** and **LabTest Checker (LTC)** on the order page (see [The Labplus integration](#the-labplus-integration)).

## Demo credentials

| Username     | Password             |
|--------------|----------------------|
| `patient123` | `veryStrongPassword` |

The login form is pre-filled, so you only need to click **Log in**.

## How the demo works

1. Log in (the form is pre-filled), you land on the **list of orders** and the patient's profile.
2. Click an order to see its **results**: examinations, values, units, reference ranges and a status badge (normal / high / low).
3. A real patient cannot create orders, so the orders page has a clearly marked **"Demo only" box**. **Simulate new test results** adds a new order with a complete set of results, as if you had just had new tests done and the lab had released them. The results come from the files in `data/results/`, which stand for what a lab keeps in its own database (examinations with measured parameters, nothing else). They are used in a fixed order: the demo starts with the first 2 already loaded, each click adds the next one (3rd, 4th, 5th, ...) and after the last one it starts from the beginning. **Reset demo data** goes back to the initial 2 orders.

The patient profile is what the lab system stores about the person: name, gender, birth date, **national ID** (the equivalent of PESEL), e-mail and phone. All orders belong to this one patient. The result files contain no patient data, so every new set of results simply becomes this patient's order.

Note: the result files are in the form the lab keeps them, so some text results (e.g. urinalysis: "wykryto", "nieobecne") are in Polish.

## Configuration (.env)

Settings are optional and read from environment variables or from a `.env` file in the project root (next to `src/`). Copy the template and edit it:

```bash
cp .env.dist .env
```

`.env` is git-ignored. The Labplus integration needs the `LABPLUS_*` keys (provided by Labplus) and your own `PATIENT_HASH_SECRET`, described in `.env.dist`; without them the portal works and the integration is switched off. In code use `env('KEY', 'default')` (from `src/services/env.php`). With Docker the file is passed to the container automatically.

## Project structure

Every page is a folder in `src/` with `index.php` (logic) and `template.php` (HTML), and the folder name is its URL (`/orders/`, `/order/?id=3`, ...). No rewrite rules are needed.

Everything fake is in one folder, **`data-simulation__only-for-demo/`**: it pretends to be the lab that produces new results. You do not need to read it when studying the integration.

```
mocklab-portal-php/
├── src/                                  The web root
│   ├── index.php                         Redirects to /login/
│   ├── login/        index.php, template.php
│   ├── orders/       index.php, template.php     List of orders
│   ├── order/        index.php, template.php     Order details with the results table
│   ├── logout/       index.php
│   ├── labplus/                          Labplus integration: request signing, platform token, preinterpretation, LTC,
│   │                                     the results summary on the order page (+ labplus.js and api/ for the browser)
│   ├── services/
│   │   ├── db.php                        The lab's data: patient and orders (storage/orders.json)
│   │   ├── helpers.php                   Display helpers (escaping, result flags, reference ranges)
│   │   └── env.php                       env() - reads environment variables / .env
│   ├── partials/     header.php, footer.php
│   ├── styles/       style.css           All styles (colors as CSS variables)
│   └── data-simulation__only-for-demo/   DEMO ONLY, not part of a real portal
│       ├── index.php                     Handles the "simulate" / "reset" buttons
│       ├── demo_box.php                  The box with the buttons
│       ├── simulator.php                 Releases the next result set from results/
│       ├── patient.php                   The patient (incl. national ID)
│       └── results/                      Result sets (001.json, 002.json, ...)
├── storage/                              Demo state (orders.json, created automatically, git-ignored)
├── .env.dist                             Template for the optional .env file
├── Dockerfile
├── docker-compose.yml
└── README.md
```

## 1. Run with Docker

Requires Docker with Docker Compose.

```bash
docker compose up --build
```

Open <http://localhost:8080>.

The `src/` folder is mounted into the container, so edits are visible after a page refresh (no rebuild needed). The demo state (`orders.json`) lives in a Docker volume, so it survives restarts.

Stop it with `Ctrl+C`, or, if it runs in the background (`docker compose up -d --build`):

```bash
docker compose down        # stop (demo state is kept)
docker compose down -v     # stop and also wipe the demo state
```

## 2. Run without Docker

Prerequisites: **PHP 8.1 or newer** with the `session` and `json` extensions (enabled by default in standard PHP builds). Check with `php -v` and `php -m`.

The web server user must be able to **write to the `storage/` folder** in the project root (the built-in server uses your own user, so it just works; for Apache/Nginx run e.g. `chown www-data storage`, or `chmod 777 storage` for a quick local test). Point the web server at the **`src/`** folder, not at the project root.

### a) PHP built-in server (easiest)

From the project directory:

```bash
php -S localhost:8080 -t src
```

Open <http://localhost:8080>.

### b) Apache

Point a virtual host at the `src/` folder (`mod_php` or PHP-FPM must be enabled; `mod_rewrite` is not needed):

```apache
<VirtualHost *:8080>
    ServerName mocklab.local
    DocumentRoot /path/to/mocklab-portal-php/src

    <Directory /path/to/mocklab-portal-php/src>
        Require all granted
        DirectoryIndex index.php
    </Directory>
</VirtualHost>
```

Add `Listen 8080` if needed, reload Apache and open <http://localhost:8080>.

### c) Nginx + PHP-FPM

```nginx
server {
    listen 8080;
    server_name mocklab.local;
    root /path/to/mocklab-portal-php/src;
    index index.php;

    location / {
        try_files $uri $uri/ =404;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;   # or 127.0.0.1:9000
    }
}
```

Adjust the `fastcgi_pass` value to your PHP-FPM socket, then reload Nginx and open <http://localhost:8080>.

## Troubleshooting

- **Port already in use**: change the port (`8080:80` in `docker-compose.yml`, or the port in the `php -S` command).
- **"Failed to open stream" / warnings about `storage/orders.json`**: the `storage/` folder is not writable by the web server user (see above).
- **Blank page or "Session" errors**: make sure PHP 8.1+ is installed with the `session` extension and that the session save path is writable.
- **Redirected to login all the time**: cookies must be enabled in your browser.
- **Fonts look different**: the Lexend font is loaded from Google Fonts, so it needs internet access.

## The Labplus integration

The bare portal without the integration is on the `baseline` branch, so `git diff baseline...integration/php` shows everything the integration requires: `src/labplus/` (request signing, platform token, preinterpretation, LabTest Checker, the results summary and its script) and a few lines in existing files. The browser only talks to our pages; the keys stay on the server, which signs every request to Labplus. The event bus (section 5 of the Labplus documentation) is not included.
