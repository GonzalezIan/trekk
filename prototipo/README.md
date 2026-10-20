# Trekk – Prototipo de interfaz (TP4)

Prototipo navegable de la interfaz de Trekk, el sistema de gestión de excursiones de senderismo (Grupo 3).
Muestra las pantallas del **Cliente** y del **Administrativo** siguiendo los casos de uso del modelo.

> Es un prototipo: no tiene base de datos ni backend. Los datos son de ejemplo y los cambios se guardan
> solo en la pestaña del navegador (al cerrarla, vuelve todo al estado inicial).

## Requisitos

- **Node.js 20 o superior** (versión LTS): https://nodejs.org
- Conexión a internet la primera vez, para instalar las dependencias.

## Cómo descargarlo

**Opción A – con git**

```bash
git clone https://github.com/GonzalezIan/trekk.git
cd trekk
git switch prototipo-interfaz
cd prototipo
```

**Opción B – sin git (ZIP)**

1. Abrir https://github.com/GonzalezIan/trekk/tree/prototipo-interfaz
2. Botón verde **Code → Download ZIP** y descomprimirlo.
3. Abrir una terminal dentro de la carpeta **`prototipo`** (tiene que estar el archivo `package.json`).

## Cómo correrlo

Dentro de la carpeta `prototipo`:

```bash
npx pnpm install
npx pnpm dev
```

Abrir **http://localhost:3000** en el navegador.

Si `pnpm` da problemas, se puede usar npm:

```bash
npm install
npm run dev
```

## Usuarios de prueba

| Perfil | Usuario | Contraseña |
|---|---|---|
| Cliente | `carlam` | cualquiera |
| Administrativo | `admin` | cualquiera |

En la portada también están los accesos rápidos *"Demo: entrar como cliente · como admin"*.

## Qué se puede probar

**Cliente**

- Consultar excursiones → salidas programadas → paquetes (CU 4, 9 y 7).
- Registrar una reserva en 5 pasos: participantes, datos, equipamiento (con alquiler, CU 25), resumen y pago con seña o total en USD o ARS (CU 14 y CU 22).
- Mis reservas: participantes y carga de certificado médico, pago del saldo, cancelación y conformidad (CU 15, 16, 17, 20, 21 y 22).

**Administrativo**

- Oferta → Excursiones: registrar, modificar, dar de baja y registrar salida (CU 1, 2, 3 y 8).
- Oferta → Salidas programadas: modificar, cancelar, registrar paquete, listar paquetes (y cancelar paquete) y listar participantes (CU 10, 11, 5, 7, 6 y 21).
- Reservas: listar y cancelar (CU 15 y 16).
- Certificados: listar pendientes y verificar (CU 18 y 19).
- Reportes: generar el reporte periódico (CU 27).

## Para tener en cuenta

- **La fecha del sistema está fija en el 26/09/2026**, para que los datos de ejemplo (salidas, plazos de 48 h, vigencia de certificados) tengan sentido.
- La opción **"Demo: simular que la pasarela rechaza la transacción"** del pago sirve para mostrar el caso de pago rechazado (CU 22).
- La pasarela de pagos es simulada y el certificado médico se muestra con una vista previa de ejemplo.

## Problemas comunes

- **"dev not found" / "Missing script"**: la terminal no está en la carpeta `prototipo`. Ejecutar `dir` (Windows) o `ls` y verificar que aparezca `package.json`.
- **"port 3000 already in use"**: hay otra app usando el puerto. Cerrarla, o correr `npx pnpm dev -p 3001` y abrir http://localhost:3001.
- **"npx no se reconoce"**: falta instalar Node.js (ver Requisitos) y volver a abrir la terminal.
