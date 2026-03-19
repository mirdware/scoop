# scoop
[![Latest Stable Version](https://poser.pugx.org/mirdware/scoop/v/stable)](https://packagist.org/packages/mirdware/scoop) [![Latest Unstable Version](https://poser.pugx.org/mirdware/scoop/v/unstable)](https://packagist.org/packages/mirdware/scoop) [![License](https://poser.pugx.org/mirdware/scoop/license)](https://opensource.org/licenses/MIT)

El framework PHP para arquitecturas que priorizan
rigor técnico sobre conveniencia.

Implementa DDD, Hexagonal, Event Sourcing o Capas con libertad
arquitectónica total. Sin magia, sin facades, sin shortcuts.

---

## :star: ¿Qué hace a scoop diferente?

Scoop es un framework PHP diseñado para desarrolladores y equipos que buscan **control total, alto rendimiento y arquitectura limpia** desde el primer commit.

A diferencia de los frameworks de conveniencia masiva, Scoop respeta la **soberanía del arquitecto**, proporcionando herramientas de precisión sin imponer dependencias intrusivas en tu lógica de negocio.

- **Libertad Arquitectónica Real:** Implementa DDD, CQRS, Arquitectura Hexagonal o por Capas. Scoop es agnóstico y diseñado para proteger tu dominio sin dependencias de framework.

- **Persistencia Agnóstica con Quoting Universal:** Modelado de dominio (Entidades, Value Objects) independiente del motor (MySQL, PostgreSQL, SQL Server). Sistema de quoting universal `[column]` que se auto-convierte a sintaxis específica de cada motor, neutralizando inyecciones SQL por diseño.

- **Cifrado Versionado:** Sistema único de versiones criptográficas que permite migrar algoritmos (AES-256-CBC → AES-256-GCM) sin re-encriptar la base de datos. Cada valor almacenado preserva su versión, permitiendo evolución gradual.

- **Inyección de Dependencias Explícita:** Resolución recursiva basada en tipos que unifica clases y factories. Sin escaneo de componentes pesado, sin configuraciones opacas. Control absoluto sobre el grafo de dependencias y el ciclo de vida (Scopes).

- **CSRF Timing-Safe Integrado:** Protección CSRF con directivas `@csrf` que discriminan automáticamente entre contextos (meta tag en `<head>` para AJAX, hidden input en forms). Validación timing-safe nativa.

- **Lazy Connection Loading:** Conexiones a base de datos que NO se abren hasta su uso real. Reducción medida de hasta 80% en conexiones por request, crítico para microservicios y arquitecturas multi-tenant.

- **Routing Basado en Sistema de Archivos:** Definición jerárquica con `app/routes/middlewares.php` que permite cadenas de middlewares aditivos. Defensa en profundidad totalmente granular sin boilerplate.

- **Structs como Código:** Migraciones SQL nativas con filosofía forward-only. Sin rollback mágico, sin abstracciones que ocultan el SQL real. Control total sobre tu esquema.

- **CLI de Ingeniería (app/ice):** Consola integrada para scaffolding, gestión de structs, automatización de tareas y orchestración del ciclo de vida del software.

- **Entorno de Desarrollo Inmutable:** Soporte nativo para Docker y DevContainers. Paridad total entre desarrollo y producción, eliminando "funciona en mi máquina".

- **Compatibilidad Extendida:** Soporte desde PHP 5.4+ hasta las versiones más recientes, asegurando que tus aplicaciones operen en cualquier entorno sin sacrificar arquitectura moderna.

---

## :no_entry_sign: ¿Para quién NO es Scoop?

Scoop requiere inversión en aprendizaje arquitectónico. **No es ideal para:**

- Proyectos "quick and dirty" con deadlines de días
- Developers buscando "5 minutos a CRUD"
- Equipos que necesitan ecosystem masivo (1000+ packages)
- Freelancers buscando maximum job market

**Scoop es para equipos que priorizan:**
- Arquitectura limpia sobre velocidad inicial
- Control total sobre conveniencia
- Mantenibilidad a largo plazo sobre shortcuts
- Calidad de código sobre cantidad de features

---

## :zap: instalación rápida

¿Listo para probar scoop? En solo unos minutos tendrás tu primer proyecto funcionando.

Scoop está diseñado para ser compatible con versiones de PHP desde la 5.4 en adelante. Este amplio soporte busca ofrecer una solución robusta y perdurable, permitiendo que tus proyectos se mantengan operativos a lo largo del tiempo y en diversos entornos.
Si bien el núcleo es compatible con versiones anteriores, **te alentamos a utilizar las versiones más recientes de PHP (recomendamos 8.1+) para tus nuevos desarrollos** para aprovechar al máximo las mejoras de rendimiento, seguridad y las últimas características del lenguaje al construir tus aplicaciones.

Requisitos previos:

* PHP >=5.4 (Recomendado PHP 8.1+ para nuevos proyectos)
* Composer
* Node + npm

Crea un nuevo proyecto y ejecuta el servidor de desarrollo.

````bash
composer create-project mirdware/scoop {project-name} -s dev
cd {project-name}
npm install && composer install && npm run dev
````

---

## :classical_building: Arquitectura y Fundamentos

No te limites a usar Scoop; domina el motor. Invitamos a explorar [nuestra documentación](https://scoop.ct.ws/docs/) para descubrir el rigor detrás de cada pieza: desde el blindaje de la persistencia atómica hasta un sistema de seguridad adaptativo diseñado para otorgar soberanía total al arquitecto.

---

## :handshake: Únete a la Comunidad scoop

Scoop es un proyecto vivo y en desarrollo continuo. ¡Tu participación es fundamental para su éxito y evolución!

Creemos en el poder del código abierto y la colaboración. Hay muchas formas de contribuir y ser parte del crecimiento de scoop:

* :bug: **Reporta Bugs:** ¿Encontraste un error? Abre un [Issue en GitHub](https://github.com/mirdware/scoop/issues) detallando el problema.
* :bulb: **Sugiere Mejoras y Nuevas Funcionalidades:** ¿Tienes una idea genial? ¡Compártela en los [Issues de GitHub](https://github.com/mirdware/scoop/issues) o inicia una [Discusión en GitHub](https://github.com/mirdware/scoop/discussions)
* :memo: **Mejora la Documentación:** Una buena documentación es clave. Si ves algo que se puede explicar mejor o falta información, no dudes en proponer cambios o enviar un Pull Request. Puedes encontrar el [**repositorio de nuestra documentación aquí**](https://github.com/marlonramirez/getscoop.org) para enviar tus mejoras.
* :wrench: **Envía Pull Requests:** ¿Corregiste un bug o implementaste una nueva característica? ¡Nos encantaría revisar tu PR! Considera abrir un [Issue en GitHub](https://github.com/mirdware/scoop/issues) primero para discutir cambios mayores, especialmente si son nuevas funcionalidades.
* :question: **Ayuda a Otros:** Responde preguntas en los Issues, Discusiones o en nuestros canales de comunicación.
* :loudspeaker: **Corre la Voz:** Habla de scoop en tus redes sociales, blogs, podcasts o con tus colegas. ¡Cada mención ayuda!

Canales de Comunicación:
* **GitHub Issues:** [https://github.com/mirdware/scoop/issues](https://github.com/mirdware/scoop/issues) (Para bugs y propuestas concretas)
* **GitHub Discussions:** [https://github.com/mirdware/scoop/discussions](https://github.com/mirdware/scoop/discussions) (Para preguntas, ideas generales, mostrar lo que has construido y debates)
* **Twitter(X):** [@sespesoft](https://x.com/sespesoft)

Estamos construyendo algo grande juntos. ¡Cualquier contribución, por pequeña que sea, es increíblemente valiosa y bienvenida!

---

## :page_facing_up: Licencia

Scoop es un software de código abierto licenciado bajo la [MIT License](https://opensource.org/licenses/MIT).
