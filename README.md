# scoop
[![Latest Stable Version](https://poser.pugx.org/mirdware/scoop/v/stable)](https://packagist.org/packages/mirdware/scoop) [![Latest Unstable Version](https://poser.pugx.org/mirdware/scoop/v/unstable)](https://packagist.org/packages/mirdware/scoop) [![License](https://poser.pugx.org/mirdware/scoop/license)](https://opensource.org/licenses/MIT)

El framework PHP open source para crear aplicaciones modernas, escalables y desacopladas, alineada con los mejores estándares de la industria.

---

## :star: ¿Qué hace a scoop diferente?

Scoop es un framework PHP diseñado para desarrolladores y equipos que buscan control total, alto rendimiento y una arquitectura limpia y profesional desde el inicio.

Creemos en el código limpio, los principios SOLID y en darte las herramientas para que te concentres en la lógica de negocio, no en luchar contra el framework.

- **Libertad Arquitectónica:** Implementa DDD, CQRS, Event Sourcing, Arquitectura Hexagonal, Cebolla o por Capas. ¡Tú eliges!
- **Rendimiento Excepcional:** Un núcleo minimalista, sin dependencias innecesarias, para un consumo de recursos optimizado.
- **Modularidad y Reutilización:** Construye con componentes desacoplados tanto para UI como para lógica de backend.
- **Persistencia Desacoplada:** Define tu dominio (Entidades, Value Objects, Relaciones) de forma agnóstica al motor de base de datos.
- **Validaciones Intuitivas:** Sistema de validación declarativo y potente para modelos y formularios.
- **Routing Elegante y Potente:** Definición de rutas basada en archivos, clara, con soporte para middlewares y parámetros dinámicos.
- **Inyección de Dependencias Nativa:** Fomenta el código desacoplado, testeable y mantenible.
- **CLI Integrada (app/ice):** Potente consola de comandos para scaffolding, tareas administrativas y automatización.
- **Sistema de Eventos Avanzado:** Un robusto Event Broker para gestionar eventos, comandos, consultas y suscriptores de forma eficiente.
- **Configuración Flexible por Entornos:** Clara separación entre la configuración de la aplicación y los parámetros del entorno.
- **Entorno de Desarrollo Moderno:** Soporte nativo para Docker y DevContainers, ideal para equipos y onboarding rápido.
- **Testing y CI/CD Listos para Usar:** Herramientas y estructura pensadas para la calidad del software y la integración continua.
- **Logging y Caché Nativos:** Observabilidad y rendimiento mejorados desde el core.
- **Diseñado para la Perdurabilidad:** Soporte desde PHP 5.4+, asegurando que tus aplicaciones tengan una vida útil extensa y operen en una amplia gama de entornos, sin sacrificar los principios de una arquitectura moderna.

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

## :rocket: Primeros pasos

Consulta la [documentación oficial](http://getscoop.org/docs/) para comenzar tu primer proyecto en minutos y descubrir todas las posibilidades de scoop.

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
