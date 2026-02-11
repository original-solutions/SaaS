/// <reference types="vite/client" />

declare module '*.vue' {
  import type { DefineComponent } from 'vue';
  const component: DefineComponent<object, object, unknown>;
  export default component;
}

// Augment vue-router meta fields
import 'vue-router';

declare module 'vue-router' {
  interface RouteMeta {
    guest?: boolean;
    requiresAuth?: boolean;
    requiresSuperAdmin?: boolean;
  }
}
