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
	tableHeaders: {
		name: "Locazione",
		position: "Posizione",
		capacity: "Capacità"
	},
	entity: {
		name: "",
		position: "",
		capacity: 0,
		notes: ""
	},
	sortFields: [ "id", "name", "position", "capacity", "notes" ]
});