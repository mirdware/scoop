import { defineConfig } from 'vite';
import { viteStaticCopy } from 'vite-plugin-static-copy';
import scalarHmrPlugin from './app/scripts/scoop/vite-plugin-scalar-hmr';
import babel from 'vite-plugin-babel';
import path from 'path';
import pkg from './package.json';

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
        filter: /\.js$/,
        babelConfig: {
          babelrc: false,
          configFile: false,
          plugins: [
            ["@babel/plugin-proposal-decorators", { "version": "legacy" }],
            ["@babel/plugin-proposal-class-properties", { "loose": true }]
          ]
        }
      }),
      viteStaticCopy({
        targets: [
          {
            src: normalizePath(path.resolve(__dirname, 'node_modules/fa-stylus/fonts/**/*')),
            dest: 'fonts'
          }
        ]
      })
    ],
    root: './',
    server: {
      strictPort: true,
      host: '0.0.0.0',
      port: 8000,
      origin: 'http://localhost:8000',
      proxy: {
        '^/(?!@vite|@fs|app/scripts|app/styles|node_modules|public|fonts).*$': {
          target: phpHost,
          changeOrigin: true,
          secure: false,
        },
      }
    },
    publicDir: false,
    build: {
      outDir: 'public',
      assetsDir: 'assets',
      emptyOutDir: false,
      sourcemap: isProduction,
      manifest: false,
      rollupOptions: {
        input: {
          main: normalizePath(path.resolve(__dirname, pathScripts, 'app.js')),
          styles: normalizePath(path.resolve(__dirname, pathStyles, 'app.styl'))
        },
        output: {
          entryFileNames: `js/${appName}.min.js`,
          chunkFileNames: `js/${appName}-chunk-[hash].min.js`,
          assetFileNames: (assetInfo) => {
            if (assetInfo.name === 'main.css' || assetInfo.name === 'styles.css' || assetInfo.name.endsWith('app.css')) {
              return `css/${appName}.min.css`;
            }
            if (assetInfo.name && /\.(woff2?|eot|ttf|otf|svg|png|jpe?g|gif)$/i.test(assetInfo.name)) {
              return `assets/[name]-[hash][extname]`;
            }
            return `assets/[name]-[hash][extname]`;
          }
        }
      }
    },
    css: {
      preprocessorOptions: {
        styl: {
          paths: [
            normalizePath(path.resolve(__dirname, 'node_modules')),
            normalizePath(path.resolve(__dirname, 'app/styles'))
          ],
          additionalData: isProduction ? `` : `$fa-font-path = "/fonts"`
        }
      }
    },
    resolve: {
      alias: {
        '@': normalizePath(path.resolve(__dirname, './app/scripts')),
        '~styles': normalizePath(path.resolve(__dirname, './app/styles')),
      }
    }
  };
});
