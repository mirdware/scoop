import { defineConfig } from 'vite';
import { PurgeCSS } from 'purgecss';
import scalarHmrPlugin from './app/scripts/scoop/vite-plugin-scalar-hmr.js';
import babel from '@rolldown/plugin-babel';
import legacy from '@vitejs/plugin-legacy';
import path from 'path';
import { existsSync, readFileSync } from 'node:fs';
import browserslist from 'browserslist';
import browserslistToEsbuild from 'browserslist-to-esbuild';
import { browserslistToTargets, bundle } from 'lightningcss';
import pkg from './package.json' with { type: 'json' };

function normalizePath(p) {
  return path.resolve(import.meta.dirname, p).replace(/\\/g, '/');
}

function resolveIcon(id) {
  if (!id.startsWith('@icon/')) return;
  const queryIndex = id.indexOf('?');
  const name = id.slice(6, queryIndex === -1 ? undefined : queryIndex);
  const query = queryIndex === -1 ? '' : id.slice(queryIndex);
  if (/^(?:filled|outline)\//.test(name)) {
    return normalizePath(`node_modules/@tabler/icons/icons/${name}`) + query;
  }
  const ownIcon = normalizePath(`app/styles/scoop/icons/${name}`);
  return (existsSync(ownIcon) ? ownIcon : normalizePath(`node_modules/@tabler/icons/icons/outline/${name}`)) + query;
}

export default defineConfig(async ({ command, mode }) => {
  const isProduction = mode === 'production';
  const unusedClasses = [];
  if (command === 'build') {
    const content = [
      'app/**/*.{sdt.php,html,js,mjs,cjs,ts,tsx,jsx,vue,svelte,json}'
    ];
    const flattened = bundle({
      filename: normalizePath('app/styles/app.css'),
      targets: browserslistToTargets(browserslist('defaults, not IE 11'))
    }).code.toString();
    const purge = new PurgeCSS();
    const used = await purge.extractSelectorsFromFiles(content, []);
    const safelist = normalizePath('app/styles/safelist.json');
    const results = await purge.purge({
      content,
      css: [{ raw: flattened }, 'app/**/*.css'],
      safelist: existsSync(safelist) ? JSON.parse(readFileSync(safelist, 'utf8')) : [],
      rejected: true,
      keyframes: false,
      fontFace: false,
      variables: false
    });
    for (const selector of results.flatMap(result => result.rejected || [])) {
      for (const match of selector.matchAll(/\.([\w-]+)/g)) {
        const name = match[1];
        if (!used.hasClass(name)
          && !purge.isSelectorSafelisted(name)
          && !purge.isSelectorSafelistedDeep(name)
          && !purge.isSelectorSafelistedGreedy(name)) unusedClasses.push(name);
      }
    }
  }
  let developmentBase = '/';
  return {
    base: isProduction ? './' : '/',
    plugins: [
      {
        name: 'scoop-icons',
        enforce: 'pre',
        resolveId: resolveIcon,
        generateBundle() {
          this.emitFile({
            type: 'asset',
            fileName: 'assets/icons/LICENSE',
            source: readFileSync(normalizePath('node_modules/@tabler/icons/LICENSE'))
          });
        }
      },
      {
        name: 'scoop-style-base',
        configResolved(config) { developmentBase = config.base; }
      },
      !isProduction && scalarHmrPlugin(),
      babel({
        plugins: [
          ["@babel/plugin-proposal-decorators", { "version": "2023-11" }]
        ]
      }),
      legacy({
        targets: ['defaults', 'not IE 11']
      })
    ],
    root: './',
    server: {
      watch: {
        ignored: ['**/vendor/**', '**/node_modules/**']
      },
      strictPort: true,
      host: '0.0.0.0',
      port: 8000,
      origin: 'http://localhost:8000',
      cors: false,
      proxy: {
        '^/(?!@vite|@fs|app/scripts|app/styles|node_modules|public|fonts).*$': {
          target: process.env.PHP_HOST || 'http://127.0.0.1:8001',
          changeOrigin: true,
          secure: false,
          xfwd: true,
          bypass(request) {
            const url = request.url || '';
            const relative = url.startsWith(developmentBase) ? url.slice(developmentBase.length) : url;
            if (/^\/?(?:@vite|@fs|app\/scripts|app\/styles|node_modules|public|fonts)(?:\/|$)/.test(relative)) return url;
          }
        },
      }
    },
    publicDir: false,
    css: {
      transformer: 'lightningcss',
      lightningcss: {
        targets: browserslistToTargets(browserslist('defaults, not IE 11')),
        unusedSymbols: [...new Set(unusedClasses)],
        visitor: {
          Url(url) {
            const resolved = resolveIcon(url.url);
            if (resolved) return { ...url, url: resolved };
          }
        }
      }

    },
    build: {
      assetsInlineLimit: (filePath) => {
        const normalized = normalizePath(filePath);
        if (normalized.includes('/node_modules/@tabler/icons/') || normalized.includes('/app/styles/scoop/icons/')) return false;
      },
      outDir: 'public',
      assetsDir: 'assets',
      emptyOutDir: false,
      sourcemap: isProduction,
      manifest: false,
      cssMinify: 'lightningcss',
      cssTarget: browserslistToEsbuild('defaults, not IE 11'),
      rollupOptions: {
        input: {
          main: normalizePath('app/scripts/app.js'),
          styles: normalizePath('app/styles/app.css')
        },
        output: {
          entryFileNames: (chunkInfo) => chunkInfo.name === 'main' ? `js/${pkg.name}.min.js` : `js/${pkg.name}-[name].min.js`,
          chunkFileNames: `js/${pkg.name}-chunk-[hash].min.js`,
          assetFileNames: (assetInfo) => {
            const isIcon = (assetInfo.originalFileNames || []).some(file => {
              const source = normalizePath(file);
              return source.includes('node_modules/@tabler/icons/') || source.includes('app/styles/scoop/icons/');
            });
            if (isIcon) return `assets/icons/[name]-[hash][extname]`;
            if (assetInfo.name === 'main.css' || assetInfo.name === 'styles.css' || assetInfo.name.endsWith('app.css')) {
              return `css/${pkg.name}.min.css`;
            }
            if (assetInfo.name && (/\.(woff2?|eot|ttf|otf)$/i.test(assetInfo.name))) {
              return `assets/fonts/[name]-[hash][extname]`;
            }
            if (assetInfo.name && /\.(svg|png|jpe?g|gif)$/i.test(assetInfo.name)) {
              return `assets/images/[name]-[hash][extname]`;
            }
            return `assets/[name]-[hash][extname]`;
          }
        }
      }
    },
    resolve: {
      alias: [
        { find: '@', replacement: normalizePath('app/scripts') },
        { find: '#', replacement: normalizePath('app/styles') }
      ]
    }
  };
});
