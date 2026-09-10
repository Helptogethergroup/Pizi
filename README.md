# PGFind — PG Aggregator for Delhi NCR & Noida

A complete PG / hostel / co-living aggregator built on **Laravel 11**. Front-end fully connected to backend, SEO-optimised, with a 5-module architecture:

1. **Guest Module** — Public-facing site (search, filters, property detail, lead capture, blog)
2. **Owner Module** — Self-service portal for landlords (add property, manage listings, view leads)
3. **Tele-caller Module** — Lead inbox with one-click WhatsApp send, status tracking, visit scheduling
4. **Field Executive Module** — Database & routes ready (UI is basic; expand as needed)
5. **Admin Module** — Manage everything (properties, leads, users, verification, featuring)

---

## ⚡ Quick Setup (5 min)

### Prerequisites
- PHP 8.2+
- Composer
- (Optional) MySQL 8 — SQLite is default for zero-setup local dev

### Steps

```bash
# 1. Install dependencies
composer install

# 2. Copy env file and generate app key
cp .env.example .env
php artisan key:generate

# 3. Run migrations and seed sample data (Delhi NCR cities, ~120 PGs, 3 blogs)
php artisan migrate:fresh --seed

# 4. Symlink storage for image uploads
php artisan storage:link

# 5. Start dev server
php artisan serve
```

Visit **http://localhost:8000** 🎉

---

## 🔑 Default Login Credentials

| Role               | Email                    | Password    |
|--------------------|--------------------------|-------------|
| Admin              | admin@pgfind.in          | admin123    |
| Owner              | owner@pgfind.in          | owner123    |
| Tele-caller        | telecaller@pgfind.in     | caller123   |
| Field Executive    | field@pgfind.in          | field123    |

---

## 🗂 What's Included

### Public site (SEO-friendly)
- Homepage with hero, search bar, featured + latest properties, city directory, how-it-works, blog teasers, CTAs
- `/search` — filterable property listing (city, locality, gender, budget, amenities, sorting)
- `/pg-in-{city}` — auto-generated city landing pages (e.g. `/pg-in-delhi`, `/pg-in-noida`)
- `/pg-in-{city}/{locality}` — auto-generated locality pages (e.g. `/pg-in-noida/sector-62`)
- `/pg/{slug}` — property detail with gallery, amenities, sharing options, Google Maps embed, lead form, schema markup
- `/blog`, `/blog/{slug}` — blog with SEO meta + JSON-LD
- `/sitemap.xml` — auto-generated sitemap
- `/robots.txt`
- Open Graph + Twitter Card meta tags on every page
- `LocalBusiness` / `LodgingBusiness` JSON-LD schema on home & property pages

### Backend modules
- **Owner**: dashboard, list/create/edit/delete properties, image upload, amenity selection, sharing types, view stats
- **Tele-caller**: dashboard, lead inbox with filters, lead detail page with status update, follow-up scheduling, **one-click WhatsApp deeplink** that pre-fills property details, site-visit scheduling
- **Admin**: full dashboard with stats + conversion rate, property verification & featuring, lead assignment to tele-callers, user management with role creation
- **Auth**: login, register (with role selection: tenant or owner), logout, role-based redirect & access control

### Database
- 5 migrations covering: users (with roles), cities & localities, properties + amenities + images, leads + visits + reviews + wishlists, blogs
- Lead de-duplication (same phone + same property within 24hrs is treated as single lead)
- Round-robin lead auto-assignment to active tele-callers

### Tech & SEO
- Tailwind CSS via CDN (no build step needed)
- Fraunces (display) + Plus Jakarta Sans (body) — distinctive, characterful typography
- Mobile-first, responsive
- Custom navy (`#0f2748`) + coral (`#ff6b5b`) palette
- Slug-based URLs, schema markup, sitemap, OG tags

---

## 📦 Switching to MySQL (production)

In `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pgfind
DB_USERNAME=root
DB_PASSWORD=your_password
```

Create the DB: `mysql -uroot -p -e "CREATE DATABASE pgfind"` then re-run `php artisan migrate:fresh --seed`.

---

## 🎯 What's Honest About This Build

This is a **production-ready MVP**, not a finished SaaS product. Here's what's complete vs scaffolded:

### ✅ Complete & functional
- Public site (homepage, search, city/locality pages, property detail, blog, contact)
- Owner property CRUD with image upload
- Tele-caller workflow including WhatsApp one-click send
- Admin dashboard with stats and property verification
- Authentication with 4 roles
- Lead capture, de-dup, round-robin assignment
- SEO infrastructure (sitemap, schema, slug URLs, meta tags)

### ⚠️ Scaffolded (DB & routes ready, expand as needed)
- **Field Executive module** — database (`visits` table with geo-fence fields), routes, but mobile UI is basic. Build a Flutter/React Native app or PWA against the existing API.
- **Payment gateway** — placeholder. Integrate Razorpay or Cashfree for token payments.
- **WhatsApp Business API** — using `wa.me` deep-links (free, works day-1). Upgrade to Gupshup/AiSensy/Interakt when volume justifies (~500+ leads/month).
- **Email/SMS notifications** — `MAIL_MAILER=log` by default. Configure SES/MSG91 for production.
- **Image optimisation / CDN** — files store locally. For production, use S3 or Cloudflare R2 + image resizing.
- **Search relevance** — basic LIKE queries. For 10k+ properties, add Meilisearch or Algolia.

### 🚫 Deliberately NOT included (you don't need them on day 1)
- Property scraping from competitors (legally risky, breaks owner trust)
- Complex commission/payout logic (start with manual, automate after 100 closures)
- Real-time chat (use WhatsApp — it's where leads already are)
- Mobile native apps (PWA + WhatsApp gets you 95% of the way)

---

## 🛣 Recommended Next Steps

In order of business impact:

1. **Switch to MySQL** and deploy on a basic VPS (₹500/mo) or Forge + DigitalOcean.
2. **Get 10 real PGs listed** by walking up to local owners with the listing form.
3. **Run programmatic SEO** — generate dedicated landing pages for `/pg-near-{college}-{city}` patterns. The DB schema supports this; add a `landmarks` table.
4. **Add Razorpay** for token payments (their Standard checkout is ~30 min of integration).
5. **Hire 1 tele-caller** and configure their account; the dashboard is ready.
6. **Run Meta ads** pointing to specific city/locality pages. Track UTM via the `source` field on leads.
7. **Build the Field Exec PWA** when you have 5+ daily site visits.

---

## 📁 Project Structure

```
pgfind/
├── app/
│   ├── Http/
│   │   ├── Controllers/       # Home, Property, Lead, Blog, Auth, Admin/, Owner/, TeleCaller/
│   │   └── Middleware/        # RoleMiddleware
│   ├── Models/                # User, City, Locality, Property, Amenity, Lead, Visit, Review, Wishlist, Blog, PropertyImage
│   └── Providers/             # AppServiceProvider
├── bootstrap/
│   ├── app.php
│   └── providers.php
├── config/                    # app, auth, database, session, cache, logging, view, filesystems
├── database/
│   ├── migrations/            # 5 migration files covering all 5 modules
│   ├── seeders/               # User, City (Delhi NCR + localities), Amenity, Property, Blog
│   └── database.sqlite
├── public/
│   ├── index.php
│   ├── .htaccess
│   └── robots.txt
├── resources/views/
│   ├── layouts/               # app (public), dashboard (admin/owner/telecaller)
│   ├── public/                # home, search, property-detail, city, locality, about, contact, sitemap
│   ├── auth/                  # login, register
│   ├── owner/                 # dashboard, properties (index/create/edit, _form)
│   ├── admin/                 # dashboard, properties, leads, users
│   ├── telecaller/            # dashboard, leads, lead-detail
│   ├── blog/                  # index, show
│   └── components/            # property-card
├── routes/
│   ├── web.php                # All public + auth + owner + admin + telecaller routes
│   └── console.php
└── README.md
```

---

## 🤝 Honest Disclaimer

A complete production PG aggregator (with full Field Exec mobile app, payment gateway, advanced LMS analytics, multi-tenant property management, dispute resolution, and so on) is realistically a 2–3 month dev project with a team. This codebase gives you a strong, opinionated foundation that **runs out of the box and demonstrates the full architecture** — but it's an MVP starting line, not a finish line. Hire a Laravel dev to expand it as you grow.

— Built with ❤️ for the Indian PG market.
