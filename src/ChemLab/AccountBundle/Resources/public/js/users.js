/**
 * Tipo utente
 * @typedef {object} User
 * @property {string} username  Username
 * @property {string} name      Nome
 * @property {string} surname   Cognome
 * @property {string} email     Email
 * @property {string} gender    Sesso (F|M|N)
 * @property {boolean} admin    Amministratore
 * @property {boolean} active   Utente attivo
 */

ListManager.init({
	url: "/access/users/",
	entity: {
		username: "",
		name: "",
		surname: "",
		email: "",
		gender: "N",
		admin: false,
		active: true
	},
	entityMapper: function(user) {
		return $.extend({}, user, {
			admin: user.admin ? "<span class='glyphicon glyphicon-star'></span>" : "-",
			active: "<span class='glyphicon glyphicon-" + (user.active ? "ok" : "remove") + "'></span>"
		});
	}
});

$(function() {
	$("#entityModal").on("show.bs.modal", function() {
		$("#userPassword", this).val("");
	});
});