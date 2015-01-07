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
	entity: {
		item: 0,
		owner: 0,
		quantity: 0,
		total: 0,
		status: "issued"
	},
	entityMapper: (function() {
		var statusMap = {
				issued: "Richiesto",
				approved: "Approvato",
				cencelled: "Annullato",
				working: "In elaborazione",
				complete: "Completato"
			};
		return function(entity) {
			var date = entity.datetime ? new Date(entity.datetime) : null;
			date = date ? date.getDate() + "/" + (date.getMonth() + 1) + "/" + date.getFullYear()
					+ " " + date.getHours() + ":" + ("0" + date.getMinutes()).slice(-2) : "-";

			return {
				id: entity.id,
				item: entity.item,
				owner: entity.owner,
				quantity: entity.quantity,
				total: entity.total.toFixed(2),
				status: statusMap[entity.status],
				datetime: date
			};
		}
	})(),
	sortFields: [ "id", "item", "owner", "quantity", "total", "status" ]
});

$(function() {
	var $modal = $("#entityModal");
	$modal.on("fill.form", function(e, id, entity) {
		if (entity.error) return;
		var $oo = $modal.find("#orderOwner");
		if (id) {
			$modal.find("#orderItemEditName").html(entity.item.name);
			$modal.find("#orderItemDescription").html(textile.convert("*" + entity.item.price.toFixed(2) + "€* - " + entity.item.description));
			$oo.text(entity.owner.name + " " + entity.owner.surname);
		} else {
			$oo.text($oo.attr("data-default-owner"));
		}
	}).on("change", "#orderItem", function() {
		var descr, price, option;
		if (this.selectedIndex === -1) {
			price = 0;
			descr = "";
		} else {
			option = this.options[this.selectedIndex];
			price = +option.getAttribute("data-item-price");
			descr = textile.convert("*" + price.toFixed(2) + "€* - " + option.getAttribute("data-item-description"));
		}
		$(this).nextAll("#orderItemDescription").html(descr);
		$(this.form).find("#orderQuantity").data("currentPrice", price).change();
	}).on("change", "#orderQuantity", function() {
		var price = $(this).data("currentPrice");
		if (price)
			$(this.form).find("#orderTotal").val(price * this.value);
	});
});