/**
 * Tipo ordine
 * @typedef {object} Order
 * @property {Item} item        Tipo dell'articolo
 * @property {number} quantity  Quantità
 * @property {number} total     Totale
 * @property {string} status    Stato dell'ordine (issued|approved|cancelled|working|complete)
 * @property {User} owner       Richiedente dell'ordine
 */

ListManager.init({
	url: "/request/orders/",
	tableHeaders: {
		item: "Articolo",
		user: "Richiedente",
		quantity: "Quantità",
		total: "Totale",
		status: "Stato"
	},
	entity: {
		item: 0,
		user: 0,
		quantity: 0,
		total: 0,
		status: "issued"
	}
});