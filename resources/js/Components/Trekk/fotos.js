// Fotos del prototipo, repartidas por id entre las excursiones que no tienen imagen propia
const fotos = ['bosque', 'glaciar', 'laguna', 'refugio'];

// `v` cambia al reemplazar la imagen, para que el navegador no siga mostrando la anterior
export const fotoExcursion = (excursion) =>
    excursion.imagen
        ? route('excursiones.imagen', { excursion: excursion.id, v: excursion.imagen.split('/').pop() })
        : `/images/excursiones/${fotos[excursion.id % fotos.length]}.jpg`;
