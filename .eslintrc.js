module.exports = {
    env: {
        browser: true,
        es2021: true,
    },
    extends: [
        'airbnb-base',
        'eslint:recommended',
    ],
    overrides: [
    ],
    parserOptions: {
        ecmaVersion: 'latest',
        sourceType: 'module',
    },
    rules: {
        'import/no-unresolved': [2, {
            // Don't know why, but imported sub-modules in vuetify is not
            // properly detected.
            ignore: ['vuetify/.+$'],
        }],
        indent: ['error', 4],
        'no-param-reassign': 'off',
        'no-return-assign': 'off',
        semi: 'error',
        'comma-dangle': ['warn', {
            arrays: 'always-multiline',
            objects: 'always-multiline',
            imports: 'always-multiline',
            exports: 'always-multiline',
            functions: 'never',
        }],
        'object-curly-newline': ['error', {
            multiline: true,
            minProperties: 5,
            consistent: true,
        }],
    },
};
