const path = require('path');

module.exports = {
    watch: true,
    mode: 'development',
    entry: './src/index.js',
    output: {
        filename: 'app.js',
        path: path.resolve(__dirname+'/../htdocs/dist'),
    },
};