Universidad Nacional de la Patagonia San Juan Bosco
Aplicaciones Web
Primer cuatrimestre de 2026
Pautas para el examen final
El proyecto final de la asignatura consiste en la continuidad del proyecto iniciado en la cursada, cuyos
requerimientos ya fueron satisfechos. Los déficits técnicos existentes, como algunas fallas en el flujo de compra,
imperfecciones en las acciones CRUD, inconsistencias de stock, pobre visualización responsiva u otras similares,
deben ser todos resueltos para el final. El examen consiste en la defensa del proyecto, con todos los integrantes
presentes, en dia y hora a acordar. Además deben realizar dos agregados especiales:
1. PWA – Progressive Web Application
• La aplicación frontend en React debe ser una aplicación PWA con notificaciones push. Una PWA es una
aplicación web que puede instalarse en el celular como si fuera una aplicación nativa y permite ciertas
funcionalidades propias de las apps, como la posibilidad de usarla (hasta cierto punto) offline y recibir
notificaciones como las que solemos recibir en apps anunciando promociones, vencimientos u ofertas. En
nuestro caso la aplicación React debe:
o permitir notificaciones push sobre nuestros productos, como alguna promoción nueva o algún
producto recién incorporado.
o permitir navegación offline en un grado razonable. Obviamente no se podrán hacer compras, pero
al menos debe poder verse parte del catálogo de productos que ha sido cacheado correctamente.
Estas funcionalidades no son de una gran complicación técnica y se codifican de forma bastante standard,
por lo que no tendrán inconvenientes.
• La aplicación React debe dar 100 en el test de accesibilidad de Lighthouse o similar según el navegador
usado.
• La API del backend debe estar protegida apropiadamente para que sólo la aplicación React pueda usarla.
2. Auditoria IA
En esta instancia podemos incursionar en un análisis más técnico de nuestros desarrollos. Aquí deben confrontar
sus dos aplicaciones (Laravel y React) con una auditoría IA. Pueden usar la que deseen y no es necesario pagar por
ninguna. El objetivo de este ejercicio es enfrentarnos a una auditoría lo más formal posible. Las respuestas
seguramente variarán según los modelos y es normal. Con un modelo es suficiente. El prompt es el mismo para
todos y es obligatoriamente el que está listado al final del documento. Al momento de la defensa analizaremos la
experiencia y el último resultado obtenido de la auditoría, de acuerdo a los siguientes parámetros:
• Comprensión del hallazgo. Para cada hallazgo relevante, ¿pueden explicar con sus palabras qué señala y
por qué importa? Esto vale incluso para los que NO van a resolver — entender es siempre el objetivo.
• Cosas nuevas que aprendieron ¿Qué temas/prácticas de la auditoría NO vimos en la cursada?
• Postura crítica frente a la IA. ¿Detectaron algún hallazgo mal fundamentado, exagerado o directamente
erróneo?
Prompt para auditoria (usar todos siempre el mismo prompt)
Actuá como un auditor de código senior. Vas a auditar este repositorio siguiendo
estrictamente esta estructura. No omitas ninguna sección aunque no encuentres
problemas graves — en ese caso indicá explícitamente "sin hallazgos relevantes".
Para cada hallazgo indicá SIEMPRE:
- Archivo y línea (o componente) exacto
- Severidad: Crítica / Alta / Media / Baja
- Descripción del problema en 1-2 líneas
- Impacto concreto (no genérico)
- Sugerencia de corrección (breve, sin reescribir el archivo entero)
CATEGORÍAS A CUBRIR (en este orden. El detalle de cada una es ilustrativo y no es
exhaustivo):
1. Seguridad (inyección, autenticación/autorización, exposición de datos, CORS,
validación de inputs)
2. Arquitectura y organización (separación de responsabilidades, acoplamiento
back/front, estructura de carpetas)
3. Calidad de código (duplicación, funciones/componentes sobrecargados, manejo de
errores, nombres poco claros)
4. Rendimiento (queries N+1, renders innecesarios en React, tamaño de bundle, lazy
loading)
5. PWA (manifest completo, service worker, estrategia de caché, comportamiento
offline, instalabilidad)
6. Deuda técnica (código muerto, dependencias desactualizadas o sin uso, TODOs
olvidados)
FORMATO DE SALIDA: markdown, con una tabla resumen al inicio (categoría | cantidad
de hallazgos |
ordenadas por
impacto/esfuerzo.
severidad máxima) y el detalle después. Al final, un párrafo de "Top 3 prioridades"
No inventes hallazgos que no puedas justificar con una referencia concreta al código.
Si una categoría no aplica o no hay código suficiente para evaluarla, decilo
explícitamente en vez de completar con generalidades.