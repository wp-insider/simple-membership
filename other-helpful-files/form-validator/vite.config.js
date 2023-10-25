import { defineConfig } from "vite";

export default defineConfig({
	plugins: [],
	build: {
		minify: false, // 'esbuild' | 'terser' | boolean 
		target: "modules", // 'modules' |  'es6' | 'es2020' | 'esnext' 
		commonjsOptions: {
			sourceMap: false
		},

		// outDir: "./dist",
		// lib: {
		// 	name: "formvalidator",
		// 	entry: "./src/index.ts",
		// 	formats: ["esm", 'es', 'iife'],
		// 	fileName: "bundle"
		// }

		// output: {
		// 	format: "umd",
		// 	strict: false,
		// 	chunkFileNames: `[name].[hash].js`,
		// 	entryFileNames: "[name].bundle.umd.js",
		// 	dir: "dist"
		// }

		rollupOptions: {
			input: "./src/index.ts",
			output: [
				// output bundle files to test folder
				{
					format: 'es',
					entryFileNames: "index.js",
				},

				// output bundle files to plugin's folder
				{
					format: 'es',
					entryFileNames: "sub/../../../../simple-membership/js/swpm.reg-form-validator.js",
				},
			]
		}
	}
});