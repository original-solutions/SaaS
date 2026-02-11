import js from '@eslint/js';
import pluginVue from 'eslint-plugin-vue';
import tsParser from '@typescript-eslint/parser';
import tsPlugin from '@typescript-eslint/eslint-plugin';
import vueParser from 'vue-eslint-parser';

export default [
    js.configs.recommended,
    ...pluginVue.configs['flat/recommended'],
    {
        files: ['resources/js/**/*.{ts,js,vue}'],
        languageOptions: {
            parser: vueParser,
            parserOptions: {
                parser: tsParser,
                ecmaVersion: 'latest',
                sourceType: 'module',
                extraFileExtensions: ['.vue'],
            },
            globals: {
                window: 'readonly',
                document: 'readonly',
                localStorage: 'readonly',
                sessionStorage: 'readonly',
                FormData: 'readonly',
                File: 'readonly',
                Blob: 'readonly',
                navigator: 'readonly',
                setTimeout: 'readonly',
                clearTimeout: 'readonly',
                setInterval: 'readonly',
                clearInterval: 'readonly',
                crypto: 'readonly',
                HTMLElement: 'readonly',
                HTMLInputElement: 'readonly',
                Event: 'readonly',
                URL: 'readonly',
                confirm: 'readonly',
                btoa: 'readonly',
            },
        },
        plugins: {
            '@typescript-eslint': tsPlugin,
        },
        rules: {
            'no-unused-vars': 'off',
            // Security: disallow eval, Function constructors, v-html (§2.3.1.X9-X11)
            'no-eval': 'error',
            'no-new-func': 'error',
            'vue/no-v-html': 'error',

            // TypeScript
            '@typescript-eslint/no-unused-vars': ['warn', { argsIgnorePattern: '^_' }],

            // Vue
            'vue/multi-word-component-names': 'off',
        },
    },
    {
        ignores: ['node_modules/', 'public/', 'vendor/', 'bootstrap/ssr/'],
    },
];
