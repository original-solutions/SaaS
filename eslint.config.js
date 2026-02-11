import js from '@eslint/js';
import pluginVue from 'eslint-plugin-vue';
import tsParser from '@typescript-eslint/parser';
import tsPlugin from '@typescript-eslint/eslint-plugin';

export default [
    js.configs.recommended,
    ...pluginVue.configs['flat/recommended'],
    {
        files: ['resources/js/**/*.{ts,vue}'],
        languageOptions: {
            parser: tsParser,
            parserOptions: {
                ecmaVersion: 'latest',
                sourceType: 'module',
            },
        },
        plugins: {
            '@typescript-eslint': tsPlugin,
        },
        rules: {
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
