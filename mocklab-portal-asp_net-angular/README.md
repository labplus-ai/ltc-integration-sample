# MockLab Patient Portal (demo) - Angular + ASP.NET Core

A tiny results-pickup portal for a fictional laboratory, **MockLab**. This version has two parts:

- **`web/`** - the web app, written in **Angular** (standalone components, signals, TypeScript),
- **`api/`** - the lab/portal API, written in **ASP.NET Core (.NET 10, C#, minimal APIs)** (no database, orders are kept in a small JSON file).

It is the **same portal as the PHP version** (`../mocklab-portal-php`): same screens, same data, same behaviour.

It is intentionally simple. It is **not** a real portal: validation and security are skipped on purpose (marked with comments in the code). It is the baseline for a later step that shows how to integrate **LabTest Checker (LTC)**. The LTC integration is **not** included yet; `web/src/app/order/order.html` only has a placeholder for it.

## Demo credentials

| Username     | Password             |
|--------------|----------------------|
| `patient123` | `veryStrongPassword` |

The login form is pre-filled, so you only need to click **Log in**.

## How the demo works

1. Log in (the form is pre-filled), you land on the **list of orders** and the patient's profile.
2. Click an order to see its **results**: examinations, values, units, reference ranges and a status badge (normal / high / low).
3. A real patient cannot create orders, so the orders page has a clearly marked **"Demo only" box**. **Simulate new test results** adds a new order with a complete set of results, as if you had just had new tests done and the lab had released them. The results come from the files in `api/src/data-simulation__only-for-demo/results/`, which stand for what a lab keeps in its own database (examinations with measured parameters, nothing else). They are used in a fixed order: the demo starts with the first 2 already loaded, each click adds the next one (3rd, 4th, 5th, ...) and after the last one it starts from the beginning. **Reset demo data** goes back to the initial 2 orders.

The patient profile is what the lab system stores about the person: name, gender, birth date, **national ID** (the equivalent of PESEL), e-mail and phone. All orders belong to this one patient. The result files contain no patient data, so every new set of results simply becomes this patient's order.

Note: the result files are in the form the lab keeps them, so some text results (e.g. urinalysis: "wykryto", "nieobecne") are in Polish.

## Configuration (.env)

Settings are optional and read from environment variables or from a `.env` file in the project root (next to `api/` and `web/`). Copy the template and edit it:

```bash
cp .env.dist .env
```

`.env` is git-ignored. Nothing needs to be configured yet; keys will be added later for the LabTest Checker integration. In the API use `Env.Get("KEY", "default")` (from `api/Services/Env.cs`; ASP.NET's own configuration also reads environment variables). With Docker the file is passed to the API container automatically.

## Project structure

Both parts follow the same idea: **one folder per page/view**, shared things in `services/`, and everything fake in **`data-simulation__only-for-demo/`** (you do not need to read it when studying the integration).

```
mocklab-portal-asp_net-angular/
├── api/                                      The API (ASP.NET Core, C#)
│   ├── Program.cs                            Entry point: web server, session, mounts the endpoints
│   ├── Auth/AuthEndpoints.cs                 POST /api/login, POST /api/logout, GET /api/me
│   ├── Orders/OrdersEndpoints.cs             GET /api/patient, /api/orders, /api/orders/{id}
│   ├── Services/
│   │   ├── Database.cs                       The lab's data: patient and orders (storage/orders.json)
│   │   ├── Env.cs                            Env.Get() - reads environment variables / .env
│   │   └── Models.cs                         Types of the data (Order, Examination, Param, Patient)
│   ├── data-simulation__only-for-demo/       DEMO ONLY, not part of a real portal
│   │   ├── SimulationEndpoints.cs            Endpoints behind the "simulate" / "reset" buttons
│   │   ├── Simulator.cs                      Releases the next result set from results/
│   │   ├── patient.json                      The patient (incl. national ID)
│   │   └── results/                          Result sets (001.json, 002.json, ...)
│   ├── storage/                              Demo state (orders.json, created automatically, git-ignored)
│   ├── Mocklab.Api.csproj
│   └── Dockerfile
├── web/                                      The web app (Angular)
│   ├── proxy.conf.js                         Forwards /api requests to the API in development
│   └── src/
│       ├── index.html, styles.css            Page shell and all styles (colors as CSS variables)
│       └── app/
│           ├── app.routes.ts                 The pages and their addresses (+ the "logged in" check)
│           ├── login/    login.ts, login.html      Login page
│           ├── orders/   orders.ts, orders.html    List of orders
│           ├── order/    order.ts, order.html      Order details with the results table
│           ├── layout/   page.ts, page.html        Header, hero and footer shared by all pages
│           ├── services/
│           │   ├── api.ts                    Calls to the API
│           │   ├── helpers.ts                Display helpers (result flags, reference ranges, titles)
│           │   └── types.ts                  TypeScript types of the data
│           └── data-simulation__only-for-demo/
│               └── demo-box.ts, demo-box.html  DEMO ONLY: the box with the buttons
├── docker-compose.yml                        Starts the API and the web app together
├── .env.dist                                 Template for the optional .env file
└── README.md
```

In the web app each page has a `.ts` file (logic) and a `.html` file (template), next to each other. In the API each area has one file with its endpoints.

## 1. Run with Docker (whole stack, one command)

Requires Docker with Docker Compose. From the project directory:

```bash
docker compose up --build
```

This installs all packages, starts the API (`dotnet watch`) and the Angular dev server, and takes a few minutes the first time (it downloads the .NET SDK and the npm packages). Then open <http://localhost:4200>.

The `api` and `web/src` folders are mounted into the containers, so edits are applied automatically (the API restarts, the browser reloads). The API is not published to your machine; the web app forwards `/api/...` requests to it, so you can also try e.g. <http://localhost:4200/api/me>. The demo state (`orders.json`) lives in a Docker volume, so it survives restarts.

Stop it with `Ctrl+C`, or, if it runs in the background (`docker compose up -d --build`):

```bash
docker compose down        # stop (demo state is kept)
docker compose down -v     # stop and also wipe the demo state
```

## 2. Run without Docker

Prerequisites: the **.NET 10 SDK** (`dotnet --version`) for the API and **Node.js 22.22+ or 24.15+** with npm (`node -v`) for the web app. The current Angular requires that Node version.

Start the two parts in two terminals.

**Terminal 1 - API** (listens on port 3001, set `API_PORT` to change it):

```bash
cd api
dotnet run
```

**Terminal 2 - web app** (listens on port 4200):

```bash
cd web
npm install
npm start
```

Open <http://localhost:4200>. The dev server forwards `/api` to `http://localhost:3001` (set `API_URL` to use another address, e.g. `API_URL=http://localhost:3002 npm start`).

### Production-like build behind Nginx

Build the web app into static files and let Nginx serve them and forward `/api` to the API (which runs as above):

```bash
cd web
npm install
npm run build        # output: web/dist/web/browser
```

```nginx
server {
    listen 8080;
    server_name mocklab.local;

    root /path/to/mocklab-portal-asp_net-angular/web/dist/web/browser;
    index index.html;

    location /api/ {
        proxy_pass http://127.0.0.1:3001;
        proxy_set_header Host $host;
    }

    location / {
        try_files $uri $uri/ /index.html;   # the Angular app handles its own addresses
    }
}
```

Reload Nginx and open <http://localhost:8080>. Apache works the same way: serve the folder, add `ProxyPass /api/ http://127.0.0.1:3001/api/` and a fallback to `index.html`.

## Troubleshooting

- **Port already in use**: the web app uses `4200` (change `4200:4200` in `docker-compose.yml`, or run `npx ng serve --port 4300`), the API uses `3001` when run without Docker (`API_PORT=...`).
- **Node version errors / `Unsupported engine`**: install Node.js 22.22+ or 24.15+ for the web app.
- **`Cannot find module ...`**: run `npm install` in `web/`.
- **`The framework 'Microsoft.NETCore.App', version '10.0.0' was not found`**: install the .NET 10 SDK for the API.
- **The page loads but you see no data or get logged out**: make sure the API is running; sessions are kept in memory, so restarting the API logs you out.
- **Fonts look different**: the Lexend font is loaded from Google Fonts, so it needs internet access.

## What's next

LabTest Checker (LTC) integration will be added in a later step, on a separate branch, so that `git diff` shows everything the integration requires. The marked place is in `web/src/app/order/order.html` (plus a new endpoint file in `api/`).
