const COMPONENT_REGEX = /app\/scripts\/.*\.js$/;

export default function scalarHmrPlugin() {
  return {
    name: 'vite-plugin-scalar-hmr',
    apply: 'serve',
    transform(code, id) {
      if (COMPONENT_REGEX.test(id)) {
        return {
          code: code + `
            if (import.meta.hot) {
                import.meta.hot.dispose(async (data) => {
                    const self = await import("${id}");
                    data["${id}"] = self.default;
                });
                import.meta.hot.accept((newModule) => {
                    if (newModule && newModule.default) {
                        window.dispatchEvent(new CustomEvent('scalar-hmr-update', {
                            detail: {
                                _old: import.meta.hot.data["${id}"],
                                _new: newModule.default
                            }
                        }));
                    }
                });
            }`,
          map: null // Desactivamos sourcemaps para esta transformación simple
        };
      }
    }
  };
}
