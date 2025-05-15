import configPrettier from 'eslint-config-prettier';
import globals from 'globals';
import pluginImportX from 'eslint-plugin-import-x';
import pluginJs from '@eslint/js';
import pluginJsdoc from 'eslint-plugin-jsdoc';
import pluginVue from 'eslint-plugin-vue';
import pluginPrettier from 'eslint-plugin-prettier-vue';
import pluginStylistic from '@stylistic/eslint-plugin';
import tseslint from 'typescript-eslint';

const confIncludes = {
    files: ['*.js', 'resources/js/**/*.{js,mjs,cjs,ts,vue}'],
};

const confIgnores = {
    ignores: [
        'app',
        'bootstrap',
        'config',
        'database',
        'etc',
        'node_modules',
        'public',
        'resources/lang',
        'resources/views',
        'routes',
        'scripts',
        'storage',
        'tests',
        'vendor',
    ],
};

const confStylistic = {
    plugins: { '@stylistic': pluginStylistic },
    rules: {
        '@stylistic/line-comment-position': ['error'],
        // Until this style has an exception rule for markdown pre-formatted
        // comments (lots of spaces at the beginning of the line), we have to
        // keep this at a warning level.
        '@stylistic/max-len': [
            'warn',
            {
                code: 100,
                // Also, although prettier and the jsdoc plugin uses `printWidth`
                // of 80 characters as default, it's scewed if there is a prefix
                // on the line, e.g. list items: " - Some comment".  Adjust for this.
                comments: 90,
                ignoreRegExpLiterals: true,
                ignoreStrings: true,
                ignoreTemplateLiterals: true,
                ignoreUrls: true,
            },
        ],
        '@stylistic/spaced-comment': [
            'error',
            'always',
            {
                line: { markers: ['/'] },
                block: { markers: ['*'], balanced: true },
            },
        ],
    },
};

const confGlobals = {
    languageOptions: {
        globals: {
            ...globals.browser,
            L: 'readonly',
            axios: 'readonly',
            dayjs: 'readonly',
            globalThis: 'writable',
        },
    },
};

const confImports = {
    name: 'rtm-imports',
    rules: {
        'import-x/named': 'off',
        'import-x/no-unresolved': 'off',
        'import-x/order': [
            'warn',
            {
                groups: [
                    'builtin',
                    'external',
                    'internal',
                    'parent',
                    'sibling',
                    'index',
                ],
                pathGroups: [{ pattern: '@/**', group: 'internal' }],
            },
        ],
    },
};

const confJsOnly = {
    name: 'rtm-js',
    files: ['resources/js/**/*.js'],
    rules: { 'jsdoc/no-types': 'off' },
};

const confVueComponents = {
    name: 'rtm-vue-js',
    files: ['resources/js/components/**/*.{js,vue}'],
    rules: { '@typescript-eslint/no-this-alias': 'off' },
};

const confVueSinglefileComponents = {
    name: 'rtm-vue-only',
    files: ['resources/js/**/*.vue'],
    plugins: { 'prettier-vue': pluginPrettier },
    languageOptions: { parserOptions: { parser: tseslint.parser } },
    rules: {
        'vue/html-comment-content-newline': [
            'error',
            { singleline: 'never', multiline: 'always' },
        ],
        'vue/component-name-in-template-casing': [
            'warn',
            'kebab-case',
            { registeredComponentsOnly: false },
        ],
        'vue/html-comment-content-spacing': ['error', 'always'],
        'vue/html-comment-indent': ['warn', 5],
        'vue/html-self-closing': ['error', { html: { void: 'any' } }],
        'vue/max-attributes-per-line': [
            'error',
            { singleline: 3, multiline: 1 },
        ],
        'vue/no-mutating-props': 'off',
        'vue/no-v-html': 'off',
        'prettier-vue/prettier': ['error'],
    },
    settings: {
        'prettier-vue': {
            SFCBlocks: {
                template: false,
                script: true,
                style: false,
            },
        },
    },
};

/** @type {import('eslint').Linter.Config[]} */
export default tseslint.config(
    confIncludes,
    confIgnores,
    pluginJs.configs.recommended,
    tseslint.configs.recommended,
    tseslint.configs.stylistic,
    pluginImportX.flatConfigs.recommended,
    pluginJsdoc.configs['flat/contents-typescript-flavor'],
    pluginJsdoc.configs['flat/logical-typescript-flavor'],
    confGlobals,
    confStylistic,
    confImports,
    confJsOnly,
    confVueComponents,
    configPrettier,
    ...pluginVue.configs['flat/recommended'],
    confVueSinglefileComponents,
);
