# CloudInsure — Vulnerable Web App (VAPT Practice Lab)

A realistic auto‑insurance claims & dealer‑operations **dashboard**, built as an
intentionally vulnerable target for practising web application penetration
testing (VAPT). Runs fully in Docker.

> ⚠️ **This application is deliberately insecure.** Run it only on an isolated
> machine. **Never** expose it to the internet or a shared/production network.

---

## Requirements

- [Docker](https://www.docker.com/products/docker-desktop/) + Docker Compose
- ~500 MB disk and an open local port **8888**

That's it — PHP and MySQL run inside containers, nothing to install on the host.

---

## Install & run

```bash
# 1. clone
git clone https://github.com/<your-username>/cloudinsure-vapt-lab.git
cd cloudinsure-vapt-lab

# 2. build and start
docker compose up --build
```

Then open: **http://localhost:8888**

To run it in the background, use `docker compose up --build -d`.

### Stop / reset

```bash
docker compose down            # stop containers (keeps data)
docker compose down -v         # stop AND wipe the database/uploads (fresh seed)
```

> **Port note:** the app is published on **8888** (not 8080) because 8080 sits
> inside a Windows reserved port range on some machines. To change it, edit the
> `ports:` line for the `web` service in `docker-compose.yml`.

---

## Login credentials

The database is seeded automatically on first run with these accounts:

| Role  | Email                          | Password     |
|-------|--------------------------------|--------------|
| Admin | `admin@cloudinsure.local`      | `Admin@123!` |
| User  | `iiiishan@cloudinsure.local`   | `Password@1` |
| User  | `analyst@cloudinsure.local`    | `Analyst@1`  |
| User  | `raj.patel@cloudinsure.local`  | `Welcome@1`  |
| User  | `sara.lopez@cloudinsure.local` | `Welcome@1`  |

Seed data also includes 15 customers, 8 dealers, and 22 claims.

---

## Project structure

```
docker-compose.yml      Docker services (web + MySQL)
db/init.sql             database schema
app/                    PHP application (Apache + PHP 8.3)
  src/controllers/      one file per feature
  src/views, view.php   UI / layout
WALKTHROUGH.md          step-by-step exploitation guide (12 vulns)
VAPT-Report.md          formal penetration-test report
```

> The exploitation walkthrough and findings report are kept in
> **WALKTHROUGH.md** and **VAPT-Report.md** — this README is install‑only.

---

## Disclaimer

For education and authorized security testing only. The authors are not
responsible for misuse. Do not deploy publicly.
