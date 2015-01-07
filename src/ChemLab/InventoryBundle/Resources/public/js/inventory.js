/**
 * Tipo oggetto
 * @typedef {object} Entry
 */

ListManager.init({
	url: "/inventory/entries/",
	entity: {
		item: 0,
		location: 0,
		quantity: 0,
		notes: ""
	},
	sortFields: [ "id", "item", "location", "quantity", "notes" ]
});