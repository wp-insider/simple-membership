// import path from 'path';
// import typescript from "@rollup/plugin-typescript";
const path = require('path');
const typescript = require("@rollup/plugin-typescript");
const resolve = require('@rollup/plugin-node-resolve');
const commonjs = require('@rollup/plugin-commonjs');
const terser = require('@rollup/plugin-terser');
const babel = require('@rollup/plugin-babel');
module.exports = [
	{
		input: './src/index.ts',
		output: [
			{
				file: 'dist/index.js',
				format: 'esm',
				name: 'development',
			},
			{
				file: path.join(__dirname, '..', '..', 'simple-membership', 'js', 'swpm.reg-form-validator.js'),
				format: 'esm',
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
			babel({ babelHelpers: 'bundled' })
		]
	}
]