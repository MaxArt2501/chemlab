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
	}
});

$(function() {
	var $modal = $("#entityModal");

	$modal.on("entity.fill", function(e, id, entity) {

		if (entity.error) return;

		if (id) {
			$modal.find("#entryItem").val(entity.item.id).change();
			$modal.find("#entryLocation").val(entity.location.id).change();
		};

	}).on("change", "select", function() {
		var option = this.options[this.selectedIndex];
		if (!option) return;

		if (this.name === "item")
			$modal.find("#entryItemDescription").html(textile.convert(option.getAttribute("data-item-description")));
		else if (this.name === "location")
			$modal.find("#entryLocationPosition").text(option.getAttribute("data-location-position"));

	});

});