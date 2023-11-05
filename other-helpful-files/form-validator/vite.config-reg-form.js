import { defineConfig } from "vite";

export default defineConfig({
	plugins: [],
	build: {
		minify: false, // 'esbuild' | 'terser' | boolean 
		target: "modules", // 'modules' |  'es6' | 'es2020' | 'esnext' 
		commonjsOptions: {
			sourceMap: false
		},

		rollupOptions: {
			input: "src/swpm-reg-form-validator.ts",
			output: [
				// output bundle files to test folder
				{
					format: 'es',
					entryFileNames: "[name].js"
				},

				// output bundle files to plugin's folder
				{
					format: 'es',
					entryFileNames: "sub/../../../../simple-membership/js/[name].js",
				},
			]
		},
		manualChunks(id) {
			// Define manual chunks to prevent shared dependencies
			if (id.includes('node_modules')) {
			  return 'vendor';
			}
		  },
	}
});