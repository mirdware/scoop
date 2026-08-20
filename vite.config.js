import { defineConfig } from 'vite';
import { viteStaticCopy } from 'vite-plugin-static-copy';
import scalarHmrPlugin from './app/scripts/scoop/vite-plugin-scalar-hmr.js';
import babel from '@rolldown/plugin-babel';
import legacy from '@vitejs/plugin-legacy';
import path from 'path';
import browserslist from 'browserslist';
import browserslistToEsbuild from 'browserslist-to-esbuild';
import { browserslistToTargets } from 'lightningcss';
import pkg from './package.json' with { type: 'json' };

const BROWSERSLIST_QUERY = 'defaults, not IE 11';
const appName = pkg.name;
const pathScripts = 'app/scripts/';
const pathStyles = 'app/styles/';
const phpHost = process.env.PHP_HOST || 'http://localhost:8001';

function normalizePath(p) {
  return p.replace(/\\/g, '/');
}

export default defineConfig(({ command, mode }) => {
  const isProduction = mode === 'production';
  return {
    plugins: [
      !isProduction && scalarHmrPlugin(),
      babel({
        plugins: [
          ["@babel/plugin-proposal-decorators", { "version": "2023-11" }]
        ]
      }),
      viteStaticCopy({
        targets: [
          {
            src: normalizePath(path.resolve(import.meta.dirname, 'node_modules/fa-stylus/fonts/**/*')),
            dest: 'fonts'
          }
        ]
      }),
      legacy({
        targets: ['defaults', 'not IE 11']
      })
    ],
    root: './',
    server: {
      strictPort: true,
      host: '0.0.0.0',
      port: 8000,
      origin: 'http://localhost:8000',
      cors: false,
      proxy: {
        '^/(?!@vite|@fs|app/scripts|app/styles|node_modules|public|fonts).*$': {
          target: phpHost,
          changeOrigin: true,
          secure: false,
          xfwd: true
        },
      }
    },
    publicDir: false,
    css: {
      transformer: 'lightningcss',
      lightningcss: {
        targets: browserslistToTargets(browserslist(BROWSERSLIST_QUERY))
      },
      preprocessorOptions: {
        styl: {
          paths: [
            normalizePath(path.resolve(import.meta.dirname, 'node_modules')),
            normalizePath(path.resolve(import.meta.dirname, 'app/styles'))
          ],
          additionalData: `
            $fa-font-path = "../../node_modules/fa-stylus/fonts"
            $public = "${isProduction ? '../' : '/public/'}"
          `
        }
      }
    },
    build: {
      outDir: 'public',
      assetsDir: 'assets',
      emptyOutDir: false,
      sourcemap: isProduction,
      manifest: false,
      cssMinify: 'lightningcss',
      cssTarget: browserslistToEsbuild(BROWSERSLIST_QUERY),
      rollupOptions: {
        input: {
          main: normalizePath(path.resolve(import.meta.dirname, pathScripts, 'app.js')),
          styles: normalizePath(path.resolve(import.meta.dirname, pathStyles, 'app.styl'))
        },
        output: {
          entryFileNames: (chunkInfo) => chunkInfo.name === 'main' ? `js/${appName}.min.js` : `js/${appName}-[name].min.js`,
          chunkFileNames: `js/${appName}-chunk-[hash].min.js`,
          assetFileNames: (assetInfo) => {
            if (assetInfo.name === 'main.css' || assetInfo.name === 'styles.css' || assetInfo.name.endsWith('app.css')) {
              return `css/${appName}.min.css`;
            }
            if (assetInfo.name && /\.(woff2?|eot|ttf|otf)$/i.test(assetInfo.name)) {
              return `fonts/[name]-[hash][extname]`;
            }
            if (assetInfo.name && /\.(svg|png|jpe?g|gif)$/i.test(assetInfo.name)) {
              return `images/[name]-[hash][extname]`;
            }
            return `assets/[name]-[hash][extname]`;
          }
        }
      }
    },
    resolve: {
      alias: {
        '@': normalizePath(path.resolve(import.meta.dirname, './app/scripts')),
        '#': normalizePath(path.resolve(import.meta.dirname, './app/styles'))
      }
    }
  };
});
