/**
 * Tipo articolo
 * @typedef {object} Item
 * @property {string} name         Nome dell'articolo
 * @property {string} description  Descrizione dell'articolo
 * @property {string} code         Codice dell'articolo
 * @property {string} type         Tipo dell'articolo (reagent|solvent|glassware|equipment|other)
 * @property {number} price        Prezzo unitario dell'articolo
 */

(function() {

var typeMap = {
		reagent: "Reagente",
		solvent: "Solvente",
		glassware: "Vetreria",
		equipment: "Attrezzatura",
		other: "Altro"
	}

ListManager.init({
	url: "/catalog/items/",
	tableHeaders: {
		name: "Articolo",
		code: "Codice",
		type: "Tipo",
		price: "Prezzo €"
	},
	entity: {
		name: "",
		description: "",
		code: "",
		type: "other",
		price: 0
	},
	entityMapper: function(item) {
		return {
			id: item.id,
			name: item.name,
			code: item.code,
			type: typeMap[item.type] || "Altro",
			price: item.price.toFixed(2),
			description: textile.convert(item.description)
		};
	}
});

})();