/**
 * Tipo oggetto
 * @typedef {object} Entry
 */

ListManager.init({
	url: "/inventory/entries/",
	tableHeaders: {
		item: "Oggetto",
		location: "Locazione",
		quantity: "Quantità"
	},
	entity: {
		item: 0,
		location: 0,
		quantity: 0,
		notes: ""
	}
});