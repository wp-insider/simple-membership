import * as esbuild from 'esbuild'
import path from "path"

// const outDir = path.join(__dirname, '..', '..', 'simple-membership', 'js', 'swpm.reg-form-validator.js'),;
await esbuild.build({
  entryPoints: ['src/index.ts'],
  bundle: true,
  minify: true,
  sourcemap: false,
//   target: ['chrome58', 'firefox57', 'safari11', 'edge16'],
  target: 'chrome58',
  outfile: 'dist/bundle-esbuild.js',
})