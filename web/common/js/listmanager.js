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
			entity: { name: "" },
			sortFields: [ "id", "name" ]
		},

		/**
		 * Collezione di template compilati
		 * @type {object.<string, Hogan>}
		 */
		templates: {},

		/**
		 * Funzione di inizializzazione del gestore della lista
		 * @param {object.<string, *>} options
		 */
		init: function(options) {
			this.options = $.extend({}, this.defaults, options);
			sortRE = new RegExp("^[+\\-]?(?:" + this.options.sortFields.join("|") + ")$", "i");

			$(function() {
				$(window).on("hashchange", hashchange);
				hashchange();
			});
		},

		setPage: function(page) {
			page = +page;
			if (page === currentPage, page < 0 || page !== Math.floor(page)) return;

			var hash = location.hash ? location.hash.substring(1).split("/") : [];
			hash[0] = page;
			location.href = "#" + hash.join("/");
		},

		setSort: function(sort) {
			if (sort === currentSort || !sortRE.test(sort)) return;

			var hash = location.hash ? location.hash.substring(1).split("/") : [];
			if (!hash[0]) hash[0] = 1;
			hash[1] = sort;
			location.href = "#" + hash.join("/");
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
		 * @returns {$.Deferred}
		 */
		loadTable: function() {
			var opts = this.options,
				templates = this.templates,
				qstring = $.map(filters, function(value, key) {
					return value == 0 ? null : encodeURIComponent(key) + "=" + encodeURIComponent(value);
				}),
				sort = currentSort ? "/" + currentSort : "";

			return $.getJSON(opts.url + (currentPage - 1) * opts.entitiesPerPage + "-" + (currentPage * opts.entitiesPerPage - 1)
					+ sort + (qstring.length ? "?" + qstring.join("&") : ""))
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
						page: currentPage
					};

					// Elenco pagine per il template del paginatore
					var pages = [],
						totalPages = Math.ceil(json.total / opts.entitiesPerPage);
					if (totalPages) {
						if (currentPage > 1) {
							pages.push({ prev: currentPage - 1 });
							pages.push({ page: 1 });
							if (currentPage > opts.pagesAround + 2)
								pages.push({ skip: true });
							for (var i = Math.max(2, currentPage - opts.pagesAround); i < currentPage; i++)
								pages.push({ page: i });
						}
						pages.push({ page: currentPage, active: true });
						if (currentPage < totalPages) {
							for (var i = Math.min(totalPages - currentPage - 1, opts.pagesAround); i--;)
								pages.push({ page: totalPages - i - 1 });
							if (currentPage + opts.pagesAround + 1 < totalPages)
								pages.push({ skip: true });
							pages.push({ page: totalPages });
							pages.push({ next: currentPage + 1 });
						}
					}

					$output.html(templates.table.render(data))
						.find("[data-sort-by]").each(function() {
							var sortBy = this.getAttribute("data-sort-by");
							if (currentSort && currentSort.substring(1) === sortBy) {
								$(this).parent().addClass("sort-" + (currentSort[0] === "+" ? "ascending" : "descending"));
								sortBy = (currentSort[0] === "+" ? "-" : "+") + sortBy;
							}
							this.setAttribute("href", "#" + currentPage + "/" + sortBy);
						});
					$pages.html(templates.paginator.render({ pages: pages, sorting: sort }));
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
		 * Riferimento al form dei filtri della lista
		 * @type {jQuery}
		 */
		$filters,

		/**
		 * Espressione regolare di test per l'espressione di ordinamento
		 * @type {RegExp}
		 */
		sortRE,

		/**
		 * Pagina corrente dell'elenco
		 * @type {number}
		 */
		currentPage,
		/**
		 * Ordinamento corrente dell'elenco
		 * @type {string}
		 */
		currentSort,

		/**
		 * Elenco di entità caricate
		 * @type {Entity[]}
		 */
		loadedEntities = [],

		/**
		 * Filtri impostati
		 * @type {object.<string, string>}
		 */
		filters = {};

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
			if (sortRE.test(hash[1])) {
				sort = hash[1].toLowerCase();
				if ("+-".indexOf(sort[0]) === -1) {
					hash[1] = (currentSort === "+" + sort  ? "-" : "+") + sort;
					reload = true;
				} else if (sort !== hash[1]) {
					hash[1] = sort;
					reload = true;
				}
			} else {
				hash.length = 1;
				reload = true;
			}
		}
		if (reload) {
			location.href = "#" + hash.join("/");
			return;
		}

		currentPage = page;
		currentSort = sort;
		ListManager.loadTable();
	}

	$(function() {
		$body = $(document.body);
		$pages = $("#mainPagination");
		$output = $("#tableOutput");
		$modal = $("#entityModal");
		$filters = $("#listFilters");

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
				$this.toggleClass("edit-entity", !!id)
					.toggleClass("new-entity", !id);
				if (entity.error) {
					$this.addClass("show-error")
						.find(".alert-danger").text("Impossibile caricare l'entità: " + entity.error);
				} else {
					$this.data("entityId", id || 0)
						.removeClass("show-error")
						.find(".alert-danger").empty();
					$.each(entity, function(key, value) {
						var $field = $this.find("[name='" + key + "']");
						if ($field.length) {
							$field.val(value)
								.prop("disabled", $field.hasClass("hide-if-" + (id ? "edit" : "new")));
						}
					});
					$this.find(".form-control").first().focus();
				}
				$modal.trigger("entity.fill", [ id || 0, entity ]);
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
				if (!this.disabled)
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
						$modal.trigger("entity.saved", [ id || 0, data ]);
					}
				}, function(xhr, status, message) {
					bootbox.alert("Operazione fallita: " + xhr.status + " " + message);
					ListManager.spinner(false);
				});
		});

		$filters.on("change", "select", function() {
			filters[this.name] = $(this).val();
			if (currentPage !== 1)
				ListManager.setPage(1);
			else ListManager.loadTable();
		}).on("input keyup", "input", function() {
			var $this = $(this);
			clearTimeout($this.data("loadTimeout"));
			$this.data("loadTimeout", setTimeout(function() {
				filters[$this.prop("name")] = $this.val();
				if (currentPage !== 1)
					ListManager.setPage(1);
				else ListManager.loadTable();
			}, 750));
		}).on("click", ".reset-field", function() {
			var $field = $(this).prev("input");
			if ($field.length) {
				$field.val("");
				filters[$field.prop("name")] = "";
				if (currentPage !== 1)
					ListManager.setPage(1);
				else ListManager.loadTable();
			}
		});
	});

	return ListManager;

}));