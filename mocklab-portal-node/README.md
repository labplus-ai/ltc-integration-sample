# MockLab Patient Portal (demo) - Node.js / TypeScript

A tiny results-pickup portal for a fictional laboratory, **MockLab**. Written in TypeScript on Node.js with Express and EJS templates (no database, no frontend framework). Orders are kept in a small JSON file.

This is the **same portal as the PHP version** (`../mocklab-portal-php`): same screens, same data, same behaviour. Use whichever is easier to read for you.

It is intentionally simple. It is **not** a real portal: validation and security are skipped on purpose (marked with comments in the code). It is the baseline for a later step that shows how to integrate **LabTest Checker (LTC)**. The LTC integration is **not** included yet; `views/order.ejs` only has a placeholder for it.

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

`.env` is git-ignored. Nothing needs to be configured yet; keys will be added later for the LabTest Checker integration. In code use `env('KEY', 'default')` (from `src/env.ts`). You can also set `PORT` there (default `3000`). With Docker the file is picked up automatically because the project is mounted into the container.

## Project structure

The code is split into the **portal** (what a real patient portal would have) and the **demo simulator** (fake lab, only for this demo).

```
mocklab-portal-node/
├── src/
│   ├── server.ts        Entry point: web server, session, mounts the pages
│   ├── routes/
│   │   ├── login.ts     Login page
│   │   ├── orders.ts    List of orders
│   │   ├── order.ts     Order details with the results table
│   │   ├── logout.ts    Ends the session
│   │   └── demo.ts      DEMO ONLY: handles the "simulate" / "reset" buttons
│   ├── env.ts           env() - reads the optional .env file
│   ├── auth.ts          Demo credentials, login helpers
│   ├── lab.ts           The lab's data: patient and orders (stored in storage/orders.json)
│   ├── view.ts          Display helpers (result flags, reference ranges, titles)
│   ├── types.ts         TypeScript types of the data
│   └── simulator.ts     DEMO ONLY: fake lab that releases new results from data/results/
├── views/               EJS page templates
│   ├── login.ejs, orders.ejs, order.ejs
│   └── partials/        header.ejs, footer.ejs, demo_box.ejs (DEMO ONLY)
├── data/
│   ├── patient.json     The patient (incl. national ID)
│   └── results/         Result sets the simulator releases one by one (001.json, 002.json, ...)
├── storage/             Demo state (orders.json, created automatically, git-ignored)
├── assets/style.css     All styles (colors as CSS variables)
├── .env.dist            Template for the optional .env settings file
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

The source code is mounted into the container and the server restarts automatically when you edit a file (no rebuild needed). The demo state (`orders.json`) lives in a Docker volume, so it survives restarts.

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

### b) Compiled JavaScript

```bash
npm install
npm run build      # compiles src/ to dist/
npm run start:built
```

Run it from the project directory (it reads `data/`, `views/`, `assets/` and `storage/` relative to it).

### c) Behind Nginx (reverse proxy)

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

LabTest Checker (LTC) integration will be added in a later step, on a separate branch, so that `git diff` shows everything the integration requires. The marked place is in `views/order.ejs`.
