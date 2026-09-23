# Grovera Studio CRM

Influencer management CRM for **Grovera Studio** — campaigns, pricing, follow-ups, payments, and reporting.

## Stack

- Laravel 10 + Inertia.js (React) + Breeze
- MySQL (`influencercrm`)
- Spatie Permission, Maatwebsite Excel, Vite + Tailwind

## Setup (WAMP)

1. Create MySQL database `influencercrm`.
2. Copy env and install dependencies:

```bash
cp .env.example .env
composer install
npm install
php artisan key:generate
php artisan migrate --seed
npm run build
```

3. Open: [http://localhost/influencercrm/public](http://localhost/influencercrm/public)

For local Vite HMR during development: `npm run dev`.

## Login

Public registration is disabled. Use seeded accounts:

| Role | Email / username | Password |
|------|------------------|----------|
| Admin | `admin@grovera.studio` / `admin` | `password` |
| User | `user@grovera.studio` / `user` | `password` |
| Influencer (Riya) | `riya@example.com` | `password` |
| Client (Satvam) | `satvam@example.com` | `password` |

Sign in with email **or** username. Role redirects:
- Staff → CRM dashboard
- Influencer → `/influencer/dashboard`
- Client → `/client/dashboard`

## Demo data

`DatabaseSeeder` also runs `DemoSeeder`, which creates:

- Influencers: Riya Patel, Neha Shah, Priya Mehta, Aarav Desai
- Client: Satvam Foods (+ portal login)
- Campaign: Satvam Diwali Campaign (Riya, partial payment, deliverables)
- Requirement + shared shortlist with client responses
- Today / upcoming / overdue follow-ups

Re-seed demo only:

```bash
php artisan db:seed --class=DemoSeeder
```

## Fresh reset

```bash
php artisan migrate:fresh --seed
```

Seeds permissions, settings, staff users, and demo CRM data.