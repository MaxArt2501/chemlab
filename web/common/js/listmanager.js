(function (root, factory) {
	if (typeof define === 'function' && define.amd) {
		// AMD. Register as an anonymous module.
		define(["jquery", "hogan"], factory);
	} else if (typeof exports === 'object') {
		// Node. Does not work with strict CommonJS, but
		// only CommonJS-like environments that support module.exports,
		// like Node.
		module.exports = factory(require("jquery"), require("hogan"));
	} else {
		// Browser globals (root is window)
		root.ListManager = factory(root.jQuery, root.Hogan);
  }
}(this, function($, Hogan) {

	var ListManager = {
		defaults: {
			entitiesPerPage: 10,
			pagesAround: 1,
			tableHeaders: { name: "Nome" },
			entityMapper: function(x) { return x; },
			entity: { name: "" }
		},

		/**
		 * Collezione di template compilati
		 * @type {object.<string, Hogan>}
		 */
		templates: {},

		init: function(options) {
			this.options = $.extend({}, this.defaults, options);

			$(function() {
				$(window).on("hashchange", hashchange);
				hashchange();
			});
		},

		
		/**
		 * Mostra o nasconde uno spinner a tutta pagina
		 * @param {?boolean} show
		 */
		spinner: function(show) {
			var $spinner = $body.children(".global-spinner");
			if (show) {
				if (!$spinner.length)
					$body.append("<div class='global-spinner'>");
			} else if ($spinner.length)
				$spinner.remove();
		},
		/**
		 * Helper per $.Deferred.always
		 * @returns {function}
		 */
		useSpinner: function() {
			ListManager.spinner(true);

			return function() { ListManager.spinner(false); };
		},

		/**
		 * Funzione di caricamento di una pagina di risultati
		 * @param {number} page  Numero di pagina dei risultati da caricare
		 * @param {string} sort  Tipo di ordinamento, formato [+-]colonna. Può essere blank
		 * @returns {$.Deferred}
		 */
		loadTable: function loadTable(page, sort) {
			var opts = this.options,
				templates = this.templates;
			return $.getJSON(opts.url + (page - 1) * opts.entitiesPerPage + "-" + (page * opts.entitiesPerPage - 1) + (sort ? "/" + sort : ""))
				.then(function(json) {
					if (json.error) {
						$output.html(templates.alert.render({ type: "danger", message: "Errore di caricamento dati: " + json.error }));
						$pages.empty();
						return;
					}

					loadedItems = json.list;
					// Oggetto dati per il template della tabella
					var data = {
						hasEntities: json.list.length,
						entities: $.map(json.list, opts.entityMapper),
						headers: $.map(opts.tableHeaders, function(header, key) {
							var obj = { text: header, sorting: "+" + key };
							if (sort && sort.substring(1) === key) {
								var asc = sort[0] === "+";
								obj.sorted = asc ? "ascending" : "descending";
								obj.sorting = (asc ? "-" : "+") + key;
							}
							return obj;
						}),
						page: page
					};

					// Elenco pagine per il template del paginatore
					var pages = [],
						totalPages = Math.ceil(json.total / opts.entitiesPerPage);
					if (totalPages) {
						if (page > 1) {
							pages.push({ prev: page - 1 });
							pages.push({ page: 1 });
							if (page > opts.pagesAround + 2)
								pages.push({ skip: true });
							for (var i = Math.max(2, page - opts.pagesAround); i < page; i++)
								pages.push({ page: i });
						}
						pages.push({ page: page, active: true });
						if (page < totalPages) {
							for (var i = Math.min(totalPages - page - 1, opts.pagesAround); i--;)
								pages.push({ page: totalPages - i - 1 });
							if (page + opts.pagesAround + 1 < totalPages)
								pages.push({ skip: true });
							pages.push({ page: totalPages });
							pages.push({ next: page + 1 });
						}
					}

					$output.html(templates.table.render(data));
					$pages.html(templates.paginator.render({ pages: pages, sorting: sort ? "/" + sort : "" }));
				}, function(xhr, status, message) {
					$output.html(templates.alert.render({ type: "danger", message: "Errore di caricamento dati: " + xhr.status + " " + message }));
					$pages.empty();
				})
				.always(this.useSpinner());
		}
	};

	var tableKey,
		/**
		 * Riferimento al paginatore
		 * @type {jQuery}
		 */
		$body,
		/**
		 * Riferimento al paginatore
		 * @type {jQuery}
		 */
		$pages,
		/**
		 * Riferimento alla tabella dei risultati
		 * @type {jQuery}
		 */
		$output,
		/**
		 * Riferimento alla finestra di modifica entità
		 * @type {jQuery}
		 */
		$modal,

		/**
		 * Elenco di entità caricate
		 * @type {Item[]}
		 */
		loadedEntities = [];

	// Gestione del fragment dell'URL
	function hashchange() {
		var hash = location.hash.substring(1).split("/"),
			page, sort = "", reload = false;

		if (/^[1-9]\d*$/.test(hash[0])) {
			page = +hash[0];
		} else {
			hash[0] = 1;
			reload = true;
		}

		if (hash[1]) {
			if (/^[\+\-](?:id|name|description|code|type|price)$/i.test(hash[1])) {
				sort = hash[1].toLowerCase();
				if (sort !== hash[1]) {
					hash[1] = sort;
					reload = true;
				}
			} else {
				hash.length = 1;
				reload = true;
			}
		}

		if (reload) location.replace("#" + hash.join("/"));
		else if (tableKey !== page + sort)
			ListManager.loadTable(page, sort);
	}

	$(function() {
		$body = $(document.body);
		$pages = $("#mainPagination");
		$output = $("#tableOutput");
		$modal = $("#entityModal");

		$("[data-template-name]").each(function() {
			var name = this.getAttribute("data-template-name"),
				content = $(this).html();
			ListManager.templates[name] = Hogan.compile(content);
		});

		$modal.on("show.bs.modal", function(e) {
			// Funzione di riempimento del form
			function fillForm() {
				$this.find(".alert-warning").remove();
				$this.find(".has-error").removeClass("has-error");
				if (entity.error) {
					$this.addClass("show-error")
						.find(".alert-danger").text("Impossibile caricare l'entità: " + entity.error);
				} else {
					$this.data("entityId", id || 0)
						.removeClass("show-error")
						.find(".alert-danger").empty();
					$.each(entity, function(key, value) {
						$this.find("[name='" + key + "']").val(value);
					});
					$this.find(".form-control").first().focus();
				}
			}

			var $this = $(this),
				opts = ListManager.options,
				id = e.relatedTarget && +e.relatedTarget.getAttribute("data-entity-id"),
				entity, title,
				$title = $this.find("#entityModalLabel");

			if (id && !isNaN(id)) {
				title = "edit";
				$.each(loadedItems, function(i, ent) {
					if (ent.id === id) {
						entity = ent;
						return false;
					}
				});
				if (entity) fillForm();
				else $.getJSON(opts.url + id)
						.then(function(json) {
							entity = json;
						}, function(xhr, status, message) {
							entity = { error: xhr.status + " " + message };
						})
						.always(theSpinner())
						.always(fillForm);
			} else {
				title = "new"
				entity = $.extend({}, opts.entity);
				fillForm();
			}
			$title.text($title.attr("data-" + title + "-title"));
		}).on("submit", "form", function(e) {
			e.preventDefault();

			var data = {},
				$this = $(this),
				id = $modal.data("entityId");

			$this.find("[name]").each(function() {
				data[this.name] = $(this).val();
			});

			ListManager.spinner(true);
			$.ajax({ url: ListManager.options.url + id, method: id ? "PATCH" : "POST", data: JSON.stringify(data), responseType: "json" })
				.then(function(json) {
					if (json && json.error) {
						ListManager.spinner(false);
						if (json.fields) {
							$this.find(".alert-warning").remove();
							$this.find(".has-error").removeClass("has-error");
							$.each(json.fields, function(key, message) {
								$field = $this.find("[name='" + key + "']");
								if ($field.length) {
									$field.parent().append(ListManager.templates.alert.render({ type: "warning", message: message }));
									$field.closest(".form-group").addClass("has-error");
								}
							});
							$this.find(".has-error").first().focus();
						} else {
							$modal.modal("hide");
							bootbox.alert("Impossibile eseguire l'operazione: " + json.error);
						}
					} else {
						$modal.modal("hide");
						hashchange();
					}
				}, function(xhr, status, message) {
					bootbox.alert("Operazione fallita: " + xhr.status + " " + message);
					ListManager.spinner(false);
				});
		});
	});

	return ListManager;

}));