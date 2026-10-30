// Fotos del prototipo; las excursiones no tienen imagen propia, así que se reparten por id
const fotos = ['bosque', 'glaciar', 'laguna', 'refugio'];

export const fotoExcursion = (id) => `/images/excursiones/${fotos[id % fotos.length]}.jpg`;
