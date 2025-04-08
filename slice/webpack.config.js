const path = require('path');

module.exports = {
    entry: {
        main: './src/bootstrap.js',
    },
    output: {
        path: path.resolve(__dirname, '../htdocs/assets'),
        filename: 'bundle.js',
    },
    watch: true,
    module: {
        rules: [
            {
                test: /\.css$/i,
                use: ["style-loader", "css-loader"],
            },
        ],
    },
}