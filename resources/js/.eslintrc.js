module.exports = {
    extends: [
        'plugin:vue/vue3-recommended',
    ],
    plugins: [
        'vue',
    ],
    rules: {
        'max-len': 'off',
        'vue/multi-word-component-names': 'off',
        'vue/max-attributes-per-line': ['error', {
            singleline: 3,
            multiline: 1,
        }],
        'vue/valid-v-slot': ['error', {
            allowModifiers: true,
        }],
    },
};
