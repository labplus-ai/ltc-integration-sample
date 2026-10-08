# MockLab Patient Portal (demo) - Node.js / TypeScript

A tiny results-pickup portal for a fictional laboratory, **MockLab**. Written in TypeScript on Node.js with Express (no database, no frontend framework, no template engine: pages are plain functions returning HTML). Orders are kept in a small JSON file.

This is the **same portal as the PHP version** (`../mocklab-portal-php`): same screens, same data, same behaviour. Use whichever is easier to read for you.

It is intentionally simple. It is **not** a real portal: validation and security are skipped on purpose (marked with comments in the code). It is the baseline for a later step that shows how to integrate **LabTest Checker (LTC)**. The LTC integration is **not** included yet; `src/order/template.ts` only has a placeholder for it.

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

The app can read settings from an optional `.env` file in the project root. Copy the template and edit it:

```bash
cp .env.dist .env
```

Settings come from environment variables or from the `.env` file in the project root (next to `src/`). `.env` is git-ignored. Nothing needs to be configured yet; keys will be added later for the LabTest Checker integration. In code use `env('KEY', 'default')` (from `src/services/env.ts`). You can also set `PORT` there (default `3000`). With Docker the file is passed to the container automatically.

## Project structure

Every page is a folder in `src/` with `index.ts` (logic) and `template.ts` (HTML), and the folder name is its URL (`/orders/`, `/order/?id=3`, ...). The URLs are the same as in the PHP version.

Everything fake is in one folder, **`data-simulation__only-for-demo/`**: it pretends to be the lab that produces new results. You do not need to read it when studying the integration.

```
mocklab-portal-node/
├── src/
│   ├── server.ts                         Entry point: web server, session, mounts the pages
│   ├── login/        index.ts, template.ts
│   ├── orders/       index.ts, template.ts       List of orders
│   ├── order/        index.ts, template.ts       Order details with the results table
│   ├── logout/       index.ts
│   ├── services/
│   │   ├── db.ts                         The lab's data: patient and orders (storage/orders.json)
│   │   ├── helpers.ts                    Display helpers (escaping, result flags, reference ranges)
│   │   ├── env.ts                        env() - reads environment variables / .env
│   │   └── types.ts                      TypeScript types of the data
│   ├── partials/     header.ts, footer.ts
│   ├── styles/       style.css           All styles (colors as CSS variables)
│   └── data-simulation__only-for-demo/   DEMO ONLY, not part of a real portal
│       ├── index.ts                      Handles the "simulate" / "reset" buttons
│       ├── demo_box.ts                   The box with the buttons
│       ├── simulator.ts                  Releases the next result set from results/
│       ├── patient.json                  The patient (incl. national ID)
│       └── results/                      Result sets (001.json, 002.json, ...)
├── storage/                              Demo state (orders.json, created automatically, git-ignored)
├── .env.dist                             Template for the optional .env file
├── package.json, tsconfig.json
├── Dockerfile
├── docker-compose.yml
└── README.md
```

## 1. Run with Docker

Requires Docker with Docker Compose.

```bash
docker compose up --build
```

Open <http://localhost:3000>.

The `src/` folder is mounted into the container and the server restarts automatically when you edit a file (no rebuild needed). The demo state (`orders.json`) lives in a Docker volume, so it survives restarts.

Stop it with `Ctrl+C`, or, if it runs in the background (`docker compose up -d --build`):

```bash
docker compose down        # stop (demo state is kept)
docker compose down -v     # stop and also wipe the demo state
```

## 2. Run without Docker

Prerequisites: **Node.js 22 or newer** and npm. Check with `node -v`.

### a) Local development server (easiest)

From the project directory:

```bash
npm install
npm start          # or: npm run dev   (restarts on file changes)
```

Open <http://localhost:3000>. To use another port: `PORT=8080 npm start` (or set `PORT` in `.env`).

`npm start` runs the TypeScript sources directly (with `tsx`), so there is no build step. `npm run typecheck` checks the types.

Run the commands from the project directory. The `storage/` folder (next to `src/`) must be writable.

### b) Behind Nginx (reverse proxy)

Start the app as above (e.g. on port 3000) and let Nginx forward to it:

```nginx
server {
    listen 8080;
    server_name mocklab.local;

    location / {
        proxy_pass http://127.0.0.1:3000;
        proxy_set_header Host $host;
    }
}
```

Reload Nginx and open <http://localhost:8080>. Apache works the same way with `ProxyPass / http://127.0.0.1:3000/` (modules `proxy` and `proxy_http`).

## Troubleshooting

- **Port already in use**: set another port (`PORT=...`, or change `3000:3000` in `docker-compose.yml`).
- **`process.loadEnvFile is not a function` or syntax errors**: your Node.js is too old, install Node 22 or newer.
- **`Cannot find module ...`**: run `npm install` first.
- **Redirected to login all the time**: cookies must be enabled in your browser. Sessions are kept in memory, so restarting the server logs you out.
- **Fonts look different**: the Lexend font is loaded from Google Fonts, so it needs internet access.

## What's next

LabTest Checker (LTC) integration will be added in a later step, on a separate branch, so that `git diff` shows everything the integration requires. The marked place is in `src/order/template.ts`.
