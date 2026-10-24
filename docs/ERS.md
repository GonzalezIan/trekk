 ***Documento de Especificación de Requerimientos***

Grupo Nro. 3

Ayala, Emiliano Leonardo  
Gonzalez, Ian Valentín  
Pizzonia, Carolina Belén  
Sauco Argüello, Ricardo Sebastian  
Williams, Walter

# 

## 

## 

**[1\. Introducción	2](#1.-introducción)**

[1.1. Propósito	2](#1.1.-propósito)

[1.2. Ámbito del Sistema	2](#1.2.-ámbito-del-sistema)

[1.3. Definiciones, Acrónimos y Abreviaturas	2](#1.3.-definiciones,-acrónimos-y-abreviaturas)

[1.4. Referencias	4](#1.4.-referencias)

[**2\. Descripción General	5**](#2.-descripción-general)

[2.1. Perspectiva del Producto	5](#2.1.-perspectiva-del-producto)

[2.2. Funciones del Producto	5](#2.2.-funciones-del-producto)

[2.3. Características de los Usuarios	5](#2.3.-características-de-los-usuarios)

[2.4. Restricciones	5](#2.4.-restricciones)

[2.5. Suposiciones y Dependencias	6](#2.5.-suposiciones-y-dependencias)

[2.6. Requisitos Futuros	6](#2.6.-requisitos-futuros)

[**3\. Requisitos Específicos	7**](#3.-requisitos-específicos)

[3.1. Interfaces Externas	7](#3.1.-interfaces-externas)

[3.2. Funciones	7](#3.2.-funciones)

[3.3. Requisitos de Rendimiento	26](#3.3.-requisitos-de-rendimiento)

[3.4. Atributos del Sistema	26](#3.4.-atributos-del-sistema)

[**4\. Apéndices	27**](#4.-apéndices)

[4.1. Resultados de Estimación de costos	27](#4.1.-resultados-de-estimación-de-costos)

## 

## 

## 

## **1\. Introducción** {#1.-introducción}

### **1.1. Propósito** {#1.1.-propósito}

El propósito de este documento es definir de manera clara y detallada los requerimientos para el desarrollo de un sistema de gestión de excursiones para una empresa de excursiones relevada. Este documento tiene como objetivo servir como guía principal para el equipo de desarrollo y el cliente del sistema, asegurando que ambas partes tengan conocimiento de las funcionalidades y restricciones que se van a implementar en el sistema.

### **1.2. Ámbito del Sistema**	 	 {#1.2.-ámbito-del-sistema}

Trekk es un sistema web, que tiene como propósito gestionar las operaciones de una empresa de excursiones de senderismo en la Patagonia, permitiendo administrar el catálogo de excursiones, sus Salidas Programadas y los paquetes ofrecidos sobre cada una, así como el registro de clientes y la gestión de sus reservas. El sistema permite además procesar los pagos de las reservas mediante una pasarela de pagos y controlar certificados médicos cuando la excursión lo requiera.

Como soporte a esta funcionalidad principal, Trekk permite consultar los refugios, equipamientos, servicios y empleados disponibles para asignarlos a las salidas y paquetes, generar reportes y enviar notificaciones.

Con esto se busca centralizar las operaciones de la empresa, reducir los errores de coordinación y brindar visibilidad clara sobre las reservas.

**Fuera de alcance:**

* El sistema no procesa pagos reales (la pasarela de pago es simulada).  
* El sistema no hará reintegros automáticos, sino que se informará en la notificación para que se acerquen al establecimiento para tramitar el reintegro o bien que se pongan en contacto con la organización  
* No decide automáticamente cancelaciones por clima ni valida la vigencia de los certificados médicos.  
* No funciona como un gestor de inventario general más allá del equipamiento vinculado a las excursiones.  
* No se podrá consultar la disponibilidad de los refugios en tiempo real.

### **1.3. Definiciones, Acrónimos y Abreviaturas** {#1.3.-definiciones,-acrónimos-y-abreviaturas}

***Excursión:**  Representación general de una actividad ofrecida por la empresa, con su recorrido, dificultad, duración y requisitos. No posee fecha propia: se concreta a través de sus Salidas Programadas.* 

***Salida programada:** Instancia concreta de una Excursión, calendarizada en una fecha determinada, con un guía responsable y opcionalmente uno o más asistentes, con cupo y precio base propios. Sobre una misma Salida Programada pueden ofrecerse uno o más Paquetes.*

***Paquete:** Oferta concreta de contratación sobre una Salida programada, que agrega servicios y opcionalmente alojamiento en un refugio con convenio vigente. El Paquete no posee cupo, fechas, ni guía ni precio base propios; toma estos datos de la Salida Programada a la que pertenece, por lo que cualquier cambio en la Salida Programada se refleja en todos sus paquetes.* 

***Reserva:** Solicitud de un paquete realizada por un cliente, puede incluir uno o más participantes.*

***Participante:** Persona que realizará la excursión asociada a una reserva, puede o no coincidir con el cliente.*

***Cliente:** Usuario registrado en el sistema que puede realizar reservas para uno o más participantes.*

***Administrativo:** Usuario del personal de la empresa con acceso administrativo completo a la gestión de Excursiones, Salidas Programadas, Paquetes, Reservas y Reportes.*

***Empleado:** Personal operativo de la empresa. Puede categorizarse en Guía o Asistente.*

***Guía:** Empleado de la empresa responsable de una Salida Programada.* 

***Asistente:** Personal de apoyo asignado a una Salida Programada junto al guía responsable.*

***Servicio:** Prestación adicional asociada a un paquete con costo propio, considerado en el precio del paquete.*

***Refugio:** Alojamiento que para poder ser utilizado en los paquetes que ofrezcan alojamiento, tendrá un convenio vigente con la empresa.*

***Convenio:**  Acuerdo de la empresa con un refugio, quien le ofrece camas por una determinada cantidad de tiempo.*

***Certificado médico:** Documento requerido en ciertas excursiones, el certificado debe estar vigente para las fechas de la Salida Programada correspondiente.*

***Requisito médico:** Condición asociada a un Participante que indica si debe presentar un certificado médico para realizar una excursión y cual es su situación actual respecto de dicho requisito.*

***Equipamiento obligatorio:** Elementos con los que debe contar un participante para poder realizar una excursión, puede ser traído por el participante o alquilado.*

***Equipamiento incluido:** Conjunto de elementos de equipamiento declarados en una Salida Programada, que la empresa proporcionará sin costo adicional a los participantes de cualquiera de sus paquetes.*

***Equipamiento de alquiler:** Elemento de equipamiento que la empresa ofrece en alquiler con costo, que el cliente puede incorporar a una reserva cuando un participante no dispone de un elemento obligatorio. Su disponibilidad está dada por el stock de la empresa y debe ser devuelto una vez finalizada la salida.* 

***Pasarela de Pagos:** Sistema externo responsable de procesar el cobro de una transacción y determinar su aprobación o rechazo. Para los fines de este proyecto, su comportamiento será simulado, sin conexión real a una entidad financiera.* 

***Cupo Máximo:** Capacidad máxima de participantes permitida en una Salida Programada. Se determina como el menor valor entre la cantidad ingresada por el administrativo y el stock disponible de cada elemento de equipamiento incluido declarado para la Salida Programada.*

***Cupo disponible:** Capacidad actual de participantes permitidos para agregar a una Salida programada.*

***Camas:** Unidad de capacidad de alojamiento ofrecida por un refugio a través de un convenio. La cantidad total de camas acordadas por convenio y la cantidad disponible en un momento dado determinan si un paquete con alojamiento puede seguir ofreciéndose para reservas.*

| *Código* | *Significado* |
| :---- | :---- |
| **RF** | Requisito Funcional |
| **RN** | Regla de Negocio |
| **RNF** | Requisito No Funcional |
| **CU** | Caso de Uso |

### **1.4. Referencias** {#1.4.-referencias}

IEEE Recommended Practice for Software Requirements Specification. ANSI/IEEE std. 830, 1998\.  
Entrevista de relevamiento de Gestión de excursiones para Senderismo  
Ingeniería del software “Un enfoque práctico” \- Roger S. Pressman  
UML y Patrones 2° Edición \- Craig Larman  
---

## **2\. Descripción General** {#2.-descripción-general}

### **2.1. Perspectiva del Producto** {#2.1.-perspectiva-del-producto}

El sistema de Gestión de Excursiones de Senderismo es un producto de software independiente, sin relación con sistemas preexistentes de la empresa. El sistema se comunicará con una pasarela de pagos externa para procesar el cobro de la Reserva, que para los fines de este proyecto será simulada. El sistema no integrará los siguientes sistemas externos: no realiza consultas ni reservas automáticas con refugios y no depende de servicios externos de conversión de moneda. 

### **2.2. Funciones del Producto** {#2.2.-funciones-del-producto}

El sistema proveerá las siguientes funciones:

* **Gestión de excursiones, salidas y paquetes.**  
* **Gestión de clientes.**  
* **Gestión de reservas.**  
* **Consulta de recursos operativos.**  
* **Reportes y estadísticas.**  
* **Administración de eventualidades (notificaciones).**

### **2.3. Características de los Usuarios** {#2.3.-características-de-los-usuarios}

***Administrativo:** Cuenta única provista junto con el sistema. Todos los administrativos deberán utilizar el mismo usuario en el sistema. No se puede eliminar ni modificar. Tiene asociado funciones de gestión de excursiones, salidas programadas y paquetes; también tendrá opción a verificar certificados médicos y generar reportes.*

***Cliente:** El usuario podrá ingresar los datos solicitados por el sistema para ser registrado y además podrá actualizar su información personal para modificar su perfil después de haberlo creado. Tiene asociado funciones relacionadas a la gestión de reservas.*

### **2.4. Restricciones** {#2.4.-restricciones}

2.4.1 Lenguajes de Programación

El sistema se desarrollará utilizando Laravel (PHP) para el backend, React (JavaScript) para el frontend y MySQL como sistema de gestión de base de datos relacional. 

### **2.5. Suposiciones y Dependencias** {#2.5.-suposiciones-y-dependencias}

- Se asume que los datos de Excursiones, Salidas Programadas, Paquetes, Clientes y Reservas serán gestionados exclusivamente mediante el sistema, manteniéndose actualizados por los usuarios responsables.  
- Se asume que las listas de Empleados, Equipamiento, Refugios y Servicios son listas predefinidas, provistas por la empresa e incorporadas al sistema en su puesta en marcha.  
- Se asume que la disponibilidad de cupos de las Salidas Programadas será mantenida por el sistema a partir de las reservas y cancelaciones realizadas.  
- Se asume que la disponibilidad de refugios y sus camas será registrada previamente por el administrativo, ya sea mediante convenios o a partir de la información que la empresa ya administra.   
- Se asume que la verificación de la validez del Certificado Médico será realizada manualmente por un administrativo.  
- Se asume que el sistema no tendrá una conexión real a una entidad financiera.  
- Se asume que las condiciones climáticas serán evaluadas por el personal administrativo, quien decidirá si corresponde modificar o cancelar una Salida Programada. El sistema solamente gestionará las consecuencias de dicha decisión.  
- Se asume que los equipamientos disponibles para incluir en una salida (prestados por la empresa) y los equipamientos disponibles para alquiler (alquilados por los clientes) pertenecen al stock administrado por el sistema, cuya disponibilidad será actualizada a partir de las salidas programadas, finalizaciones de las mismas, reservas y cancelaciones.  
- Se asume que los participantes devolverán en tiempo y forma, y en condiciones adecuadas, el equipamiento incluido y de alquiler utilizado durante la Salida Programada, por lo que el sistema libera automáticamente dicho stock al finalizarla. El registro individual de las devoluciones, faltantes o daños queda fuera del alcance del  sistema y se gestiona de manera externa.

### **2.6. Requisitos Futuros** {#2.6.-requisitos-futuros}

- Mejoras en la gestión de equipamientos y stock: Que el sistema brinde una interfaz para poder dar alta de productos, modificar datos de los mismos, permitir dar una baja lógica de elementos por roturas o recambio, además de permitir identificar los periodos de utilización de cada elemento, su historial de alquileres y los periodos en los que se encuentra reservado o disponible. Incorporar un mecanismo de verificación manual sobre la devolución efectiva del equipamiento (incluido y de alquiler) al finalizar una Salida Programada, permitiendo registrar roturas, faltantes o demoras en la devolución, en lugar de asumir su liberación automática e íntegra.   
- Integración con medios de pago reales: Permitir que los clientes realicen el pago de las reservas mediante una pasarela de pagos externa que sí esté conectada a una entidad financiera o bancaria, reemplazando el mecanismo de simulación utilizado actualmente.  
- Mejora en la gestión de servicios: Que el sistema pueda integrar una interfaz para poder agregar, modificar o dar de baja a los distintos servicios que ofrece el sistema (existentes o futuros).  
- Mejora en la gestión de refugios: Que el sistema pueda integrar una interfaz para poder agregar, modificar, o dar de baja en los refugios disponibles para agregar a los paquetes ya que estos pueden variar en cuanto se realicen nuevos o se venzan los convenios con los refugios. Permitir que se registren nuevos convenios.  
- Mejora en la gestión de empleados: El sistema podrá dar de alta, modificar datos y dar de bajas a los empleados dentro de la organización.  
- Optimización de los reportes: Los distintos reportes se van a poder generar de manera independientes entre sí en lugar de generarse en un mismo documento separado por secciones.

---

## **3\. Requisitos Específicos** {#3.-requisitos-específicos}

### **3.1. Interfaces Externas** {#3.1.-interfaces-externas}

* El sistema se conectará con una Pasarela de Pagos externa para procesar el cobro de los pagos registrados sobre las reservas.

### **3.2. Funciones** {#3.2.-funciones}

| Identificación | RF-01 |
| :---- | :---- |
| **Nombre** | Alta de excursión |
| **Descripción** | El sistema permitirá al administrativo registrar una nueva excursión, ingresando: Nombre, Descripción, Recorrido, Etapas, Dificultad, Duración, Experiencia requerida, si acepta menores de edad, el equipamiento obligatorio requerido y si requiere certificado médico.  La excursión debe tener al menos 1 día de duración, y el nombre de la misma debe ser diferente a la de una excursión ya registrada. La excursión quedará inicialmente en estado **“Activa”.** |
| **Datos específicos** | Nombre, descripción, recorrido, etapas, dificultad, duración, experiencia requerida, requisitos (si acepta menores o no), equipamiento obligatorio, si requiere certificado médico. |
| **Prioridad** | Alta |

| Identificación | RF-02 |
| :---- | :---- |
| **Nombre** | Baja de excursión  |
| **Descripción** | El sistema permitirá al administrativo dar de baja una excursión, siempre que ninguno de los paquetes de sus Salidas Programadas en estado **“Programada”** posea reservas activas (en estado **“Pendiente”** o **“Confirmada”**). Al confirmarse la baja, todas las Salidas Programadas de la excursión que se encuentren en estado **“Programada”** pasarán a estado **“Cancelada”** y sus Paquetes asociados pasarán a estado **“Cancelado”**. Las salidas Programadas que ya se encuentren en estado **“Finalizada”** no se ven afectadas. La excursión pasará a estado **“Inactiva”**, impidiendo que se le asignen nuevas Salidas Programadas. La baja de una excursión es definitiva: el sistema no contempla su reactivación. Para volver a ofrecer la actividad deberá registrarse una nueva excursión. |
| **Datos específicos** | Excursión Seleccionada |
| **Prioridad** | Media |

| Identificación | RF-03 |
| :---- | :---- |
| **Nombre** | Modificar excursión |
| **Descripción** | El sistema deberá permitir al administrativo modificar la información de una excursión registrada en estado **“Activa”**, siempre que ninguno de los paquetes de sus Salidas Programadas en estado **“Programada”** posea reservas activas (en estado **"Pendiente"** o **"Confirmada"**). |
| **Datos específicos** | Excursión seleccionada, nombre, descripción, recorrido, etapas, dificultad, duración, experiencia requerida, si acepta menores de edad, equipamiento obligatorio, si requiere certificado médico. |
| **Prioridad** | Media |

| Identificación | RF-04 |
| :---- | :---- |
| **Nombre** | Listar excursiones |
| **Descripción** | El sistema deberá permitir al cliente consultar las excursiones disponibles para conocer las actividades ofrecidas por la empresa y seleccionar una de ellas para ver sus salidas programadas. Asimismo, deberá permitir al administrativo consultar las excursiones existentes para seleccionar una de ellas al momento de gestionar Salidas programadas.  Para cada excursión se mostrará: nombre, recorrido, dificultad, duración y estado. |
| **Datos específicos** | Perfil de usuario. |
| **Prioridad** | Alta |

| Identificación | RF-05 |
| :---- | :---- |
| **Nombre** | Registrar Paquete  |
| **Descripción** | El sistema permitirá al administrativo registrar un paquete asociado a una Salida Programada en estado **"Programada"**, ingresando su nombre, descripción y los servicios incluidos. Opcionalmente, podrá indicar que el paquete incluye alojamiento, en cuyo caso deberá asociar un único refugio. Solo se admitirán refugios que cuenten con un convenio cuya vigencia cubra por completo el período de la Salida Programada y que no se encuentren asociados a otro paquete en estado **“Disponible”** de una Salida Programada en estado **“Programada”** cuyo período se solape con el de la Salida. El precio del paquete se calculará mediante las siguientes fórmulas: Si el paquete no incluye alojamiento: ***precio del paquete \= precio base de la Salida Programada \+ Σ precio de los servicios incluidos*** Si el paquete incluye alojamiento: ***precio del paquete \= precio base de la Salida Programada \+ (Σ precio de los servicios incluidos) \+ (precio por cama del convenio del refugio asociado x cantidad de noches de la Salida Programada)*** El paquete no define cupo propio: el cupo corresponde a la Salida Programada. Cuando el paquete incluya alojamiento, cada participante que lo contrate consumirá una cama en el convenio del refugio asociado, por lo que la cantidad de participantes que pueden contratarlo queda limitada además por las camas disponibles de dicho convenio. El paquete quedará inicialmente registrado en estado **“Disponible”.** |
| **Datos específicos** | Salida Programada asociada, nombre del paquete, descripción, servicios seleccionados, si incluye alojamiento, refugio seleccionado. |
| **Prioridad** | Alta |

| Identificación | RF-06 |
| :---- | :---- |
| **Nombre** | Cancelar paquete  |
| **Descripción** | El sistema deberá permitir al administrativo cancelar un paquete en estado **"Disponible"**, registrando el motivo de la cancelación. El paquete pasará a estado **"Cancelado"**, impidiendo nuevas reservas sobre el mismo. Las reservas activas asociadas al paquete pasarán a estado **"Cancelada"** y sus recursos serán liberados:  Los cupos que ocupaban (se devolverán al cupo disponible de la Salida Programada, quedando disponibles para los restantes paquetes de la misma). El stock de equipamiento de alquiler asociado a dichas reservas.  Las camas del convenio del refugio asociado consumidas por dichas reservas, si el paquete incluía alojamiento. La reserva de stock del equipamiento incluido no se libera, dado que pertenece a la Salida Programada y permanece vigente para sus demás paquetes. Para las reservas con pago registrado se aplicará la política de reembolso correspondiente a cancelaciones por causas ajenas al cliente. El sistema notificará a los clientes de las reservas afectadas, sin procesar la devolución. |
| **Datos específicos** | Paquete a cancelar, motivo de cancelación |
| **Prioridad** | Alta |

| Identificación | RF-07 |
| :---- | :---- |
| **Nombre** | Listar paquetes |
| **Descripción** | El sistema permitirá al cliente consultar los paquetes en estado **“Disponible”** para una Salida Programada seleccionada, con el fin de elegir una opción para realizar una reserva. Asimismo deberá permitir al administrativo consultar los paquetes registrados de una Salida Programada para gestionar su información o cancelarlos. Para cada paquete se mostrará: nombre, descripción, servicios incluidos y, si incluye alojamiento, el refugio asociado. Para el cliente se mostrará además el precio del paquete (calculado según las fórmulas definidas en el RF-05) y su disponibilidad: Para paquetes sin alojamiento: la cantidad de participantes que aún pueden contratarlo, dada por el cupo disponible de la Salida Programada. Para paquetes con alojamiento: la cantidad de participantes que aún pueden contratarlo, limitada por el cupo disponible de la Salida Programada y por las camas disponibles en el convenio del refugio asociado. Para el administrativo se mostrará el estado del paquete y la cantidad de reservas activas. |
| **Datos específicos** | Perfil de usuario, Salida Programada seleccionada |
| **Prioridad** | Alta |

| Identificación | RF-08 |
| :---- | :---- |
| **Nombre** | Registrar Salida Programada |
| **Descripción** | El sistema permitirá al administrativo calendarizar una nueva Salida Programada sobre una excursión en estado **“Activa”**, ingresando la fecha de inicio, la fecha de fin,hora de salida, el cupo máximo de participantes y el precio base. El sistema validará que el período sea futuro, consistente y acorde a la duración definida en la excursión, y que el cupo máximo y el precio base sean mayores a cero.  El administrativo deberá asignar un guía responsable y podrá asignar opcionalmente uno o más asistentes, seleccionándolos entre los empleados activos de la categoría correspondiente que no tengan otra Salida Programada asignada en el período.  Podrá además declarar el equipamiento que la empresa proveerá sin costo adicional a los participantes de todos los paquetes de esta Salida. El sistema verificará el stock disponible de cada elemento durante el período de la Salida, permitiendo seleccionar únicamente elementos cuyo stock disponible sea mayor a cero. Cuando se declaren elementos de equipamiento incluido, el cupo máximo de la Salida Programada quedará determinado por el menor valor entre el cupo máximo ingresado por el administrativo anteriormente, y el stock disponible de cada uno de los elementos declarados, avisando al usuario del resultado final del cupo máximo. De esta forma, la cantidad de participantes permitida no podrá superar la disponibilidad de ninguno de los elementos incluidos. Si no se declara ningún elemento de equipamiento incluido, el cupo máximo será el ingresado por el administrativo. Al confirmarse el registro, el sistema reservará de cada elemento declarado una cantidad equivalente al cupo máximo, por el período de la Salida. La Salida Programada quedará registrada en estado **“Programada”**, con su cupo disponible igual a su cupo máximo. |
| **Datos específicos** | Excursión seleccionada, Fecha de Inicio, Fecha de Fin, hora de salida , Cupo Máximo, Precio Base, Guía responsable asignado, Asistente(s) asignado(s), Equipamiento incluido declarado  |
| **Prioridad** | Alta |

| Identificación | RF-09 |
| :---- | :---- |
| **Nombre** | Listar Salidas Programadas |
| **Descripción** | El sistema permitirá consultar el listado de Salidas Programadas, según el perfil del usuario autenticado. Al Administrativo le permitirá consultar el listado de Salidas Programadas, pudiendo filtrar por excursión, rango de fechas o estado. Para cada Salida se mostrará la Excursión asociada, fecha de inicio y fin, hora de salida, guía responsable y asistentes asignados, equipamiento incluido, cupo máximo, cupo disponible, precio base, cantidad de paquetes asociados y estado (**"Programada", "Cancelada" o "Finalizada"**). Al seleccionar una Salida se dará acceso a modificarla, cancelarla, listar los paquetes asociados o registrar un nuevo paquete. Al cliente le permitirá consultar el listado de Salidas Programadas en estado **“Programada”** y que tenga más de 48 hs disponibles antes del inicio de la salida, mostrando para cada una: fecha de inicio, fecha de fin, hora de salida, equipamiento incluido, precio base y cupo disponible. Al seleccionar una Salida se dará acceso a consultar sus paquetes asociados. |
| **Datos específicos** | Perfil de usuario, Criterios de filtrado: excursión, rango de fechas, estado. |
| **Prioridad** | Alta |

| Identificación | RF-10 |
| :---- | :---- |
| **Nombre** | Modificar Salida Programada |
| **Descripción** | El sistema permitirá al administrativo modificar los datos de una Salida Programada en estado **"Programada"**: fecha de inicio, fecha de fin, hora de salida, cupo máximo, precio base, guía responsable, asistentes y equipamiento incluido declarado. El sistema revalidará sobre los datos modificados las condiciones exigidas al registrarla y, cuando se altere el período, que los convenios de los refugios asociados a sus paquetes sigan siendo admisibles según el RF-05. No aplicará el cambio si alguna condición no se cumple. El cupo máximo de la Salida Programada se recalculará cada vez que el administrativo lo modifique o modifique el equipamiento incluido, aplicando la misma regla definida en RF-08.  El sistema no permitirá que el nuevo cupo máximo sea inferior a la cantidad de participantes registrados en reservas activas. Al modificarse el cupo máximo, el sistema ajustará el cupo disponible y la cantidad de unidades de cada elemento de equipamiento incluido reservadas para la Salida Programada.  La modificación del precio base recalculará el precio de todos los paquetes de la Salida según las fórmulas definidas en RF-05, rigiendo para las reservas posteriores a la modificación. Toda modificación que altere el período, el personal asignado o el equipamiento incluido disparará la notificación a los clientes con reservas activas. |
| **Datos específicos** | Salida Programada seleccionada, Fecha de Inicio, Fecha de Fin, hora de salida, Cupo Máximo, Precio Base, Guía responsable asignado, Asistente(s) asignado(s), Equipamiento incluido declarado |
| **Prioridad** | Media |

| Identificación | RF-11 |
| :---- | :---- |
| **Nombre** | Cancelar Salida Programada |
| **Descripción** | El sistema permitirá al Administrativo cancelar una Salida Programada en estado **“Programada”** previamente seleccionada, indicando el motivo. La cancelación se aplicará en cascada a todos los Paquetes y Reservas asociados, liberando los cupos de la Salida Programada, camas en los convenios de los refugios asociados a sus paquetes y el stock de equipamiento comprometido (ya sea prestado, alquilado, o ambos). Para las reservas con pago registrado se aplicará la política de reembolso del 100% correspondiente a cancelaciones por causas ajenas al cliente, situación que el sistema informará en la notificación enviada, sin procesar la devolución. El estado de la Salida Programada y de las reservas asociadas pasará a **"Cancelada"**, se notificará a todos los clientes con reservas afectadas.  |
| **Datos específicos** | Salida Programada seleccionada, motivo de cancelación, fecha y hora de cancelación, paquetes y reservas asociados  |
| **Prioridad** | Alta |

| Identificación | RF-12 |
| :---- | :---- |
| **Nombre** | Registrar cliente |
| **Descripción** | El sistema permitirá a una persona registrarse como cliente en el mismo, incluyendo el registro de sus datos básicos y el ingreso de sus credenciales de acceso para poder acceder a las funcionalidades disponibles para dicho usuario. Los datos serán obligatorios: DNI, Nombre, Apellido, Fecha de Nacimiento (deberá ser mayor de 18 años), Email, Teléfono, Nombre de usuario y Contraseña. |
| **Datos específicos** | DNI cliente, Nombre, Apellido, Email, Fecha de nacimiento, Teléfono, Contraseña, Usuario cliente |
| **Prioridad** | Alta |

| Identificación | RF-13 |
| :---- | :---- |
| **Nombre** | Modificar datos personales |
| **Descripción** | El sistema permitirá a un cliente modificar sus propios datos personales registrados a su cuenta. |
| **Datos específicos** | Datos personales modificables (Email, Teléfono, Nombre, Apellido), Identificación del cliente (dni) |
| **Prioridad** | Baja |

| Identificación | RF-14 |
| :---- | ----- |
| **Nombre** | Registrar reserva |
| **Descripción** | El sistema permitirá al cliente registrar una reserva asociada a un paquete en la cual se debe especificar Cantidad de participantes, junto con los siguientes datos de cada uno: DNI, Nombre, Apellido y Fecha de Nacimiento. Además, el sistema alertará al cliente de los objetos obligatorios necesarios para la excursión, y dará opción de seleccionar equipamientos disponibles para alquilar sobre el catálogo de los mismos, mediante el RF-26. Luego se realizará el costo total de la reserva mediante las siguientes fórmulas:\- Si el cliente no agregó equipamiento de alquiler a la reserva: ***Importe total \= precio del paquete × cantidad de participantes*** \- Caso contrario: ***Importe total \= (precio del paquete x cantidad de participantes) \+ Σ costos de los equipamientos de alquiler adicionales seleccionados*** El sistema llama al RF-23 para registrar el pago. Una vez registrado el pago correspondiente, el sistema registra la reserva y determina su estado según la situación de pago y el estado de los requisitos médicos, habilitando la opción de cargar el certificado médico y/o de registrar el pago total si así lo requiera la situación. |
| **Datos específicos** | Paquete seleccionado, cantidad total de participantes, datos de los participantes. |
| **Prioridad** | Alta |

| Identificación | RF-15 |
| :---- | :---- |
| **Nombre** | Listar reservas |
| **Descripción** | El sistema permitirá al cliente consultar las reservas que figuren a su nombre donde se mostrarán los datos completos de las mismas y estados (de la reserva y situación de pago). Además, se le permitirá seleccionar alguna de las reservas para poder visualizar el listado de participantes de la misma (RF-22).  El sistema habilitará diferentes funciones según el estado de las reservas: **“Pendiente”** → Cancelar la reserva (RF-16) y Registrar pago (RF-23) **“Confirmada”** → Cancelar la reserva (RF-16) **“Realizada”** → Registrar conformidad (RF-21) En el caso del administrador, podrá consultar todas las reservas para visualizar sus datos y los estados de las mismas, teniendo opción también a cancelarlas si así lo requiera y el estado de la reserva lo permita. |
| **Datos específicos** | Perfil de usuario |
| **Prioridad** | Alta |

| Identificación | RF-16 |
| :---- | :---- |
| **Nombre** | Cancelar reserva |
| **Descripción** | El sistema permitirá al cliente y al administrativo cancelar una reserva previamente seleccionada. Cuando la cancelación sea solicitada y se haya efectuado al menos un pago de la misma, no se realizará devolución del importe abonado. La reserva pasará al estado **”Cancelada”** y se sumará nuevamente al stock los elementos alquilados asociados a la reserva, se habilitará a la venta nuevamente los cupos consolidados de la Salida Programada, las camas de los refugios vuelven a estar disponibles si corresponde; y se notificará al cliente. |
| **Datos específicos** | Reserva seleccionada, motivo de cancelación, Perfil de usuario |
| **Prioridad** | Media |

| Identificación | RF-17 |
| :---- | :---- |
| **Nombre** | Cancelar reserva automáticamente |
| **Descripción** | El sistema cancelará automáticamente una reserva cuando: No se haya abonado el importe total de la reserva pasadas las 24 horas de registrada la misma. Alcanzada la fecha límite de verificación del certificado médico, algún participante de la reserva mantenga su requisito médico en estado pendiente. En todos los casos, se sumará nuevamente al stock los elementos alquilados asociados a la reserva, se habilitará a la venta nuevamente los cupos consolidados, vuelven a estar disponibles las camas consolidadas de los refugios si corresponde, se cambiará el estado de la reserva a “cancelada” y se notificará al cliente, sin realizar reembolsos de los pagos abonados. |
| **Datos específicos** | Identificación de Reserva |
| **Prioridad** | Alta |

| Identificación | RF-18 |
| :---- | :---- |
| **Nombre** | Cargar certificado médico |
| **Descripción** | El sistema permitirá al cliente cargar un certificado médico correspondiente a un participante de una reserva, cuando la excursión requiera certificado médico, y el requisito médico del participante se encuentre en estado “pendiente”. El cliente deberá adjuntar el archivo. El certificado quedará en estado “pendiente de verificación” (para ser verificado por un usuario administrativo), y el requisito médico seguirá en estado “pendiente” hasta que sea validado. La carga del certificado deberá realizarse como máximo 24 horas antes del inicio de la salida programada. |
| **Datos específicos** | Reserva asociada, dni del Participante, archivo del certificado médico. |
| **Prioridad** | Alta |

| Identificación | RF-19 |
| :---- | :---- |
| **Nombre** | Listar certificados médicos pendientes |
| **Descripción** | El sistema permitirá al administrativo consultar el listado de certificados médicos que se encuentren pendientes de verificación, mostrando para cada uno el nombre, apellido y DNI del participante, la reserva asociada, la excursión y el paquete correspondiente, y la fecha de carga del certificado, permitiendo acceder a la opción de verificarlo (RF-20). |
| **Datos específicos** | — |
| **Prioridad** | Alta |

| Identificación | RF-20 |
| :---- | :---- |
| **Nombre** | Verificar certificado médico |
| **Descripción** | El sistema permite al administrativo consultar y verificar el certificado médico cargado para un participante, ingresando la fecha de emisión para que el sistema calcule su vigencia mediante la fórmula: **vigencia del certificado \= fecha de emisión \+ 30 días;** y determine si cubre el periodo de la Salida Programada. Según el resultado, actualiza el estado del certificado (“valido” o “invalido”), y la situación del requisito médico del participante (“cumplido” o “pendiente”), debiendo completarse hasta 12 horas antes del inicio de la salida. |
| **Datos específicos** | Reserva asociada, dni del Participante, estado del certificado médico, certificado médico seleccionado, fecha de emisión, descripción del certificado. |
| **Prioridad** | Alta |

| Identificación | RF-21 |
| :---- | :---- |
| **Nombre** | Registrar conformidad |
| **Descripción** | El sistema permitirá al cliente registrar su grado de conformidad respecto de una reserva propia en estado “realizada”, mediante una valoración numérica entera de 1 a 5, donde 1 representa la menor conformidad y 5 la mayor. Opcionalmente podrá acompañarla con un comentario. La conformidad podrá registrarse una única vez por reserva.  |
| **Datos específicos** | Reserva seleccionada, valoración, comentarios |
| **Prioridad** | Media |

| Identificación | RF-22 |
| :---- | :---- |
| **Nombre** |  Listar participantes |
| **Descripción** | El sistema permitirá consultar los participantes de una reserva (para el cliente) o de una Salida Programada (para el administrativo). Muestra Nombre, Apellido, DNI y estado del requisito médico (“no requerido”, “pendiente”, “cumplido”). El cliente ve los participantes de sus propias reservas y se habilita la opción de cargar certificado médico (RF-18) si el requisito médico está “pendiente”; el administrativo ve la nómina de participantes de las reservas “confirmadas” de una Salida Programada. |
| **Datos específicos** | Reserva seleccionada o Salida Programada seleccionada, Perfil de usuario. |
| **Prioridad** | Alta |

| Identificación | RF-23 |
| :---- | ----- |
| **Nombre** | Registrar pago de reserva |
| **Descripción** | El sistema permitirá procesar el pago de una reserva mediante una Pasarela de Pago de dos formas; La seña, que permitirá al cliente abonar el 50% para registrar la reserva; si es aprobada, la situación de pago queda en **"Pendiente de pago total"** y deberá abonar el 50% restante dentro de las 24 horas posteriores al registro. La otra forma permite al cliente abonar el 100% del importe directamente, quedando su situación de pago en **“Pago completo”**. El importe de la reserva se expresará en dólares estadounidenses (USD). Si cliente opta por abonar en pesos argentinos (ARS) el sistema calculará el monto de la conversión con la siguiente fórmula: 1 USD \= 1500 ARS  Una vez abonada la reserva en su totalidad pasa la situación de pago a **“Pago completo”**, y si corresponde que todos los participantes cumplen el requisito médico, la reserva se confirma. El incumplimiento del plazo de pago deriva en la cancelación automática de la reserva por vencimiento de pago (RF-17). |
| **Datos específicos** | Reserva seleccionada, monto, moneda (USD o ARS), pagador, medio de pago, fecha y hora del pago, tipo de pago (seña o saldo completo), resultado de la transacción (aprobada o rechazada). |
| **Prioridad** | Alta |

| Identificación | RF-24 |
| :---- | :---- |
| **Nombre** | Listar empleados |
| **Descripción** | El sistema permitirá al Administrativo consultar el listado de empleados registrados, con el objetivo de seleccionar aquellos que puedan ser asignados a una Salida Programada como responsable a cargo o bien para gestionar modificaciones en las Salidas Programadas que ya tienen guías asignados. El listado podrá filtrarse por categoría ("Guía" / "Asistente"), por estado y fecha (mostrando aquellos que están disponibles en el periodo seleccionado), facilitando la selección del guía responsable o de los asistentes al momento de armar una Salida Programada. Para cada empleado se mostrará: nombre, apellido, DNI, categoría y estado. |
| **Datos específicos** | Categoría, Estado, Fecha. |
| **Prioridad** | Alta |

| Identificación | RF-25 |
| :---- | :---- |
| **Nombre** | Listar servicios |
| **Descripción** | El sistema deberá permitir al administrativo consultar el detalle de los servicios registrados (tipo de servicio, descripción, precio) para incorporarlos a los paquetes correspondientes. |
| **Datos específicos** | — |
| **Prioridad** | Alta |

| Identificación | RF-26 |
| :---- | :---- |
| **Nombre** | Listar equipamientos |
| **Descripción** | El sistema deberá permitir al Administrativo listar los equipamientos (código de elemento, descripción, stock disponible) disponibles para verificar la disponibilidad de los mismos a la hora de crear una salida programada.Por el lado del Cliente permitirá ver una lista de los elementos disponibles para alquilar antes de registrar la Reserva (RF-14). |
| **Datos específicos** | Perfil del usuario |
| **Prioridad** | Alta |

| Identificación | RF-27 |
| :---- | :---- |
| **Nombre** | Listar refugios |
| **Descripción** | El sistema permitirá al administrativo consultar los refugios que cuenten con convenio vigente, mostrando para cada uno su capacidad total de camas, su disponibilidad de camas y el periodo de vigencia del convenio, para seleccionarlos al momento de definir el alojamiento de un paquete. |
| **Datos específicos** | — |
| **Prioridad** | Alta |

| Identificación | RF-28 |
| :---- | :---- |
| **Nombre** | Generar reporte periodico |
| **Descripción** | El sistema permitirá al administrativo generar un reporte entre dos fechas que incluya:  ***Reporte de clientes frecuentes:*** clientes que hayan realizado mayor cantidad de excursiones dentro de un período determinado, considerando únicamente las reservas en estado **"Realizada"** cuya Salida Programada asociada tenga fecha de fin dentro del período indicado. El reporte mostrará, para cada cliente, su Nombre, Apellido, DNI y la cantidad de excursiones realizadas, ordenados de mayor a menor cantidad, permitiendo identificar a los clientes más frecuentes de la empresa.  ***Reporte de actividades frecuentes:*** reporte de las excursiones que hayan registrado mayor cantidad de participantes dentro de un período determinado, considerando únicamente los participantes de reservas programadas en estado **"Realizada”** cuya salida programada tenga fecha de fin dentro del período indicado. El reporte mostrará, para cada excursión, su nombre y la cantidad total de participantes, ordenadas de mayor a menor cantidad, permitiendo identificar las actividades más demandadas.  ***Reporte de conformidad:*** reporte de las valoraciones y comentarios registrados por los clientes sobre las excursiones realizadas dentro de un período determinado, considerando únicamente las reservas en estado **"Realizada"** cuya Salida Programada asociada tenga fecha de fin dentro del período indicado. El reporte mostrará, para cada excursión, su nombre, la valoración promedio en escala de 1 a 5, la cantidad de valoraciones registradas y el detalle de los comentarios asociados, ordenadas de mayor a menor valoración promedio, permitiendo evaluar el grado de conformidad con los servicios ofrecidos.  |
| **Datos específicos** | Fecha de inicio del período, Fecha de fin del período.  |
| **Prioridad** | Media |

| Identificación | RF-29 |
| :---- | :---- |
| **Nombre** | Notificar eventualidades |
| **Descripción** | El sistema notificará automáticamente por correo electrónico al cliente responsable de una reserva ante las siguientes situaciones: **Confirmación de reserva:** paquete, fecha y monto abonado. **Recordatorio de salida:** fecha, punto de encuentro y equipamiento obligatorio pendiente, si lo hubiera. **Modificación o reprogramación de salida programada:** datos modificados, incluyendo fecha, hora de salida, guía responsable, asistente(s) o equipamiento incluido. **Cancelación de salida programada, paquete o de reserva:** motivo de la cancelación (decisión sobre el paquete, cascada por cancelación de la Salida Programada, o cancelación de la reserva) y, cuando corresponda un reembolso, la indicación de gestionarlo ante la empresa. **Certificado médico inválido:** motivo de invalidez.  |
| **Datos específicos** | Reserva asociada, Paquete Asociado, dni del cliente destinatario, tipo de evento, información del evento |
| **Prioridad** |  Baja |

| Identificación | RF-30 |
| :---- | :---- |
| **Nombre** | Login de usuario |
| **Descripción** | El sistema deberá permitir a los usuarios registrados iniciar sesión mediante un formulario de autenticación. Para ello deberán ingresar su Usuario y Contraseña válidos. |
| **Datos específicos** | Usuario, contraseña |
| **Prioridad** | Alta |

| Identificación | RF-31 |
| :---- | :---- |
| **Nombre** | Finalizar Salida Programada  |
| **Descripción** | El sistema finalizará automáticamente las Salidas Programadas en estado **"Programada"** cuya fecha de fin haya transcurrido, actualizando su estado a **"Finalizada"**. Las reservas asociadas que se encuentren en estado **"Confirmada"** pasarán a estado **"Realizada"**, habilitando el registro de conformidad por parte del cliente. Al finalizar la Salida Programada, el sistema liberará las camas reservadas en los convenios de los refugios asociados a sus paquetes, el stock de equipamiento incluido reservado por la Salida, y el stock de equipamiento de alquiler comprometido por sus reservas, quedando todos estos recursos nuevamente disponibles.  |
| **Datos específicos** | Salida Programada, fecha de fin, fecha actual  |
| **Prioridad** | Media |

### **3.3. Requisitos de Rendimiento** {#3.3.-requisitos-de-rendimiento}

**RNF-01 – Tiempo de respuesta:** El sistema deberá responder a las operaciones de consulta y navegación en un tiempo máximo de 3 segundos bajo condiciones normales de funcionamiento.

**RNF-02 – Operaciones críticas:** Las operaciones críticas de registro y actualización, como el registro de reservas, pagos y cancelaciones, deberán completarse en un tiempo máximo de 5 segundos.

**RNF-03 – Usuarios concurrentes:** El sistema deberá permitir la utilización simultánea por parte de al menos 20 usuarios, manteniendo los tiempos de respuesta establecidos.

**RNF-04 – Diseño Responsivo:** La interfaz de usuario debe adaptarse dinámicamente a diferentes resoluciones de pantalla, garantizando su uso tanto en computadoras como en dispositivos móviles.

### **3.4. Atributos del Sistema** {#3.4.-atributos-del-sistema}

**RNF-05 – Seguridad:** El sistema deberá proteger la información de los usuarios y controlar el acceso a las funcionalidades según el rol asignado.

* El sistema implementará un control de acceso basado en roles. Existirán dos tipos de usuario: Administrativo y Cliente, cada uno con un conjunto de funcionalidades habilitadas según lo especificado en la sección 3.2.  
* El acceso al sistema requerirá **autenticación mediante usuario y contraseña**.   
* El sistema deberá impedir que un usuario acceda a funcionalidades que no correspondan a su rol.  
* Las comunicaciones entre el cliente y el sistema deberán realizarse mediante el protocolo **HTTPS**, protegiendo la información transmitida.  
* Las credenciales de los usuarios deberán almacenarse de forma segura, evitando conservar las contraseñas en texto plano.

**RNF-06 – Mantenibilidad:** El sistema deberá desarrollarse utilizando una arquitectura modular y estándares de codificación que faciliten su mantenimiento y evolución.

* Las funcionalidades deberán encontrarse organizadas en **módulos independientes**, reduciendo el acoplamiento entre componentes.  
* La incorporación de nuevas funcionalidades deberá poder realizarse sin requerir modificaciones extensivas sobre funcionalidades existentes.  
* El código deberá mantener una estructura y documentación suficientes para facilitar la **detección, corrección y mantenimiento de errores**.  
* Los componentes que implementen funcionalidades comunes deberán poder ser **reutilizados**, favoreciendo la evolución y escalabilidad del sistema.

**RNF-07 – Portabilidad:** El sistema deberá ser accesible desde diferentes dispositivos y plataformas mediante un navegador web.

* El sistema deberá funcionar en **navegadores web modernos**.  
* La aplicación deberá ser compatible con los principales sistemas operativos de escritorio que dispongan de un navegador web moderno.  
* La interfaz deberá adaptarse a diferentes resoluciones de pantalla, permitiendo su utilización desde computadoras y dispositivos móviles.

**RNF-08 – Fiabilidad y consistencia:** El sistema deberá mantener la consistencia de la información ante errores durante la ejecución de las operaciones.

* El sistema deberá validar las operaciones críticas antes de registrar cambios permanentes.  
* Las operaciones de reserva deberán garantizar que **no se confirme una reserva si no existe disponibilidad suficiente de cupos o de los recursos requeridos**.  
* Ante un error durante el registro de una reserva, el sistema deberá evitar que se produzcan actualizaciones parciales que dejen información inconsistente.  
* El sistema deberá mantener la consistencia entre las **reservas, cupos disponibles, pagos, equipamiento y demás recursos asociados**.  
* Las operaciones que modifiquen información deberán registrar los cambios necesarios para permitir la recuperación de un estado consistente ante una falla.

---

## **4\. Apéndices** {#4.-apéndices}

### **4.1. Resultados de Estimación de costos** {#4.1.-resultados-de-estimación-de-costos}

- **Tiempo de realización**

2,34 meses

- **Costo total**

	**\$**22105440 

