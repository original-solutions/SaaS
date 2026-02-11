# VitePress Documentation

This directory contains the VitePress-powered documentation for the Laravel SaaS Base Template.

## Usage

### 1. Install VitePress

```bash
npm install -D vitepress
```

### 2. Run the Docs Locally

```bash
npx vitepress dev docs/vitepress
```

Open [http://localhost:5173](http://localhost:5173) (or the port shown) to view the docs.

### 3. Build for Production

```bash
npx vitepress build docs/vitepress
```

The static site will be output to `docs/vitepress/.vitepress/dist`.

### 4. Add/Update Docs

- Edit or add Markdown files in this directory.
- Update `config.ts` for navigation/sidebar changes.

## Structure

- `index.md` — Home page
- `getting-started.md` — Setup guide
- `architecture.md` — Core architecture
- `multi-tenancy.md` — Multi-tenancy model
- `authentication.md` — Auth & security
- `frontend.md` — Frontend guide
- `testing.md` — Testing with Pest
- `deployment.md` — Deployment guide
- `backup.md` — Backup & restore
- `real-life-saas-guide.md` — Step-by-step SaaS build guide
- `config.ts` — VitePress config

---

For more, see the [Real-Life SaaS Guide](./real-life-saas-guide.md).
