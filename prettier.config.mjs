export default {
    // Include parentheses around a sole arrow function parameter.
    arrowParens: 'always',

    // Which end of line characters to apply: <lf|crlf|cr|auto>
    endOfLine: 'lf',

    // How to handle whitespaces in HTML.
    // htmlWhitespaceSensitivity: css,

    // Wrap markdown lines at printWidth characters.
    proseWrap: 'always',

    // Use semicolon at eol where optional.
    semi: true,

    // Favor single quoted strings.
    singleQuote: true,

    // Default indentation width.
    tabWidth: 4,

    // Print trailing commas wherever possible when multi-line.
    trailingComma: 'all',

    // Never use actual tabs for indentation.
    useTabs: false,

    vueIndentScriptAndStyle: false,

    overrides: [
        {
            files: '**/*.{css,scss,less}',
            options: {
                tabWidth: 2,
            },
        },
    ],

    plugins: ['./node_modules/prettier-plugin-jsdoc/dist/index.js'],

    // For compatibility's sake, don't convert single line comment in multi line
    // encasulation to single line, keep them as they are.
    jsdocCommentLineStrategy: 'keep',

    // Separate sensible tag groups with space.
    jsdocSeparateTagGroups: true,

    // Return statement deserves own space.
    jsdocSeparateReturnsFromParam: true,
};
