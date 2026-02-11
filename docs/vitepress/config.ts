import { defineConfig } from 'vitepress';

export default defineConfig({
  title: 'Laravel SaaS Base Template',
  description: 'Production-grade SaaS foundation with Laravel 12, Vue 3, and more.',
  themeConfig: {
    nav: [
      { text: 'Guide', link: '/getting-started' },
      { text: 'Architecture', link: '/architecture' },
      { text: 'Multi-Tenancy', link: '/multi-tenancy' },
      { text: 'Authentication', link: '/authentication' },
      { text: 'Frontend', link: '/frontend' },
      { text: 'Testing', link: '/testing' },
      { text: 'Deployment', link: '/deployment' },
      { text: 'Backup', link: '/backup' },
      { text: 'SaaS Guide', link: '/real-life-saas-guide' },
    ],
    sidebar: [
      { text: 'Getting Started', link: '/getting-started' },
      { text: 'Architecture', link: '/architecture' },
      { text: 'Multi-Tenancy', link: '/multi-tenancy' },
      { text: 'Authentication', link: '/authentication' },
      { text: 'Frontend Guide', link: '/frontend' },
      { text: 'Testing', link: '/testing' },
      { text: 'Deployment', link: '/deployment' },
      { text: 'Backup & Restore', link: '/backup' },
      { text: 'Real-Life SaaS Guide', link: '/real-life-saas-guide' },
    ],
  },
});
