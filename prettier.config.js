/**
 * @see https://prettier.io/docs/en/configuration.html
 * @type {import("prettier").Config}
 */
const config = {
  singleQuote: true,
  tabWidth: 2,
  trailingComma: 'none',
  plugins: ['@prettier/plugin-xml'],
  overrides: [
    {
      files: '*.xml',
      options: {
        tabWidth: 4,
        xmlQuoteAttributes: 'double',
        xmlWhitespaceSensitivity: 'ignore'
      }
    },
    {
      files: 'data/cli.xml',
      options: {
        xmlWhitespaceSensitivity: 'preserve'
      }
    }
  ]
};

export default config;
