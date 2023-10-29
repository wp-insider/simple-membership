const path = require('path');
const typescript = require("@rollup/plugin-typescript");
const resolve = require('@rollup/plugin-node-resolve');
const commonjs = require('@rollup/plugin-commonjs');
const terser = require('@rollup/plugin-terser');
module.exports = [
	{
		input: './src/reg-form.ts',
		output: [
			{
				dir: 'dist',
				format: 'es',
				name: 'development',
			},
			{
				file: path.join(__dirname, '..', '..', 'simple-membership', 'js', 'swpm.reg-form-validator.js'),
				format: 'es',
				name: 'production',
				// plugins: [terser()], // Uncomment to enable minification
			}
		],
		plugins: [
			commonjs(),
			resolve(),
			typescript({
				tsconfig: "./tsconfig.json"
			}),
		]
	}
]