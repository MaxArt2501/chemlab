/**
 * Locazione per l'inventario
 * @typedef {object} Location
 * @property {string} name      Nome della locazione
 * @property {string} position  Posizione della locazione
 * @property {number} capacity  Capacità in unità della locazione
 * @property {string} notes     Note varie
 */

ListManager.init({
	url: "/locations/locs/",
	entity: {
		name: "",
		position: "",
		capacity: 0,
		notes: ""
	}
});