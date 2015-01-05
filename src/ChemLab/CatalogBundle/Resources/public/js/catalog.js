$(function() {
/**
 * Tipo articolo
 * @typedef {object} Item
 * @property {string} name         Nome dell'articolo
 * @property {string} description  Descrizione dell'articolo
 * @property {string} code         Codice dell'articolo
 * @property {string} type         Tipo dell'articolo (reagent|solvent|glassware|equipment|other)
 * @property {number} price        Prezzo unitario dell'articolo
 */
	var tableKey,
		typeMap = {
			reagent: "Reagente",
			solvent: "Solvente",
			glassware: "Vetreria",
			equipment: "Attrezzatura"
		},
		tableHeaders = {
			name: "Articolo",
			code: "Codice",
			type: "Tipo",
			price: "Prezzo €"
		},
		/**
		 * Collezione di template compilati
		 * @type {object.<string, Hogan>}
		 */
		templates = {},

		/**
		 * Riferimento al paginatore
		 * @type {jQuery}
		 */
		$body = $(document.body),
		/**
		 * Riferimento al paginatore
		 * @type {jQuery}
		 */
		$pages = $("#mainPagination"),
		/**
		 * Riferimento alla tabella dei risultati
		 * @type {jQuery}
		 */
		$output = $("#tableOutput"),
		/**
		 * Riferimento alla finestra di modifica articolo
		 * @type {jQuery}
		 */
		$modal = $("#itemModal"),

		/**
		 * Numero di risultati per pagina
		 * @type {number}
		 */
		itemsPerPage = 10,

		/**
		 * Elenco di articoli caricati
		 * @type {Item[]}
		 */
		loadedItems = [],

		/**
		 * Mostra o nasconde uno spinner a tutta pagina
		 * @param {?boolean} show
		 */
		globalSpinner = function(show) {
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
		theSpinner = function() {
			globalSpinner(true);

			return function() { globalSpinner(false); };
		};

	/**
	 * Funzione di caricamento di una pagina di risultati
	 * @param {number} page  Numero di pagina dei risultati da caricare
	 * @param {string} sort  Tipo di ordinamento, formato [+-]colonna. Può essere blank
	 * @returns {$.Deferred}
	 */
	function loadTable(page, sort) {
		return $.getJSON("catalog/items/" + (page - 1) * itemsPerPage + "-" + (page * itemsPerPage - 1) + (sort ? "/" + sort : ""))
			.then(function(json) {
				if (json.error) {
					$output.html(templates.alert({ type: "danger", message: "Errore di caricamento dati: " + json.error }));
					$pages.empty();
					return;
				}

				loadedItems = json.list;
				// Oggetto dati per il template della tabella
				var data = {
					hasItems: json.list.length,
					items: $.map(json.list, function(item) {
						return {
							id: item.id,
							name: item.name,
							code: item.code,
							type: typeMap[item.type] || "Altro",
							price: item.price.toFixed(2),
							description: textile.convert(item.description)
						};
					}),
					headers: $.map(tableHeaders, function(header, key) {
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
					pageAround = 1,
					totalPages = Math.ceil(json.total / itemsPerPage);
				if (page > 1) {
					pages.push({ prev: page - 1 });
					pages.push({ page: 1 });
					if (page > pageAround + 2)
						pages.push({ skip: true });
					for (var i = Math.max(2, page - pageAround); i < page; i++)
						pages.push({ page: i });
				}
				pages.push({ page: page, active: true });
				if (page < totalPages) {
					for (var i = Math.min(totalPages - page - 1, pageAround); i--;)
						pages.push({ page: totalPages - i - 1 });
					if (page + pageAround + 1 < totalPages)
						pages.push({ skip: true });
					pages.push({ page: totalPages });
					pages.push({ next: page + 1 });
				}

				$output.html(templates.table.render(data));
				$pages.html(templates.paginator.render({ pages: pages, sorting: sort ? "/" + sort : "" }));
			}, function(xhr, status, message) {
				$output.html(templates.alert({ type: "danger", message: "Errore di caricamento dati: " + xhr.status + " " + message }));
				$pages.empty();
			})
			.always(theSpinner());
	}

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
			loadTable(page, sort);
	}
	$(window).on("hashchange", hashchange);
	hashchange();

	$("[data-template-name]").each(function() {
		var name = this.getAttribute("data-template-name"),
			content = $(this).html();
		templates[name] = Hogan.compile(content);
	});

	$modal.on("show.bs.modal", function(e) {
		// Funzione di riempimento del form
		function fillForm() {
			if (item.error) {
				$this.addClass("show-error")
					.find(".alert-danger").text("Impossibile caricare l'articolo: " + item.error);
			} else {
				$this.data("itemId", id || 0)
					.removeClass("show-error")
					.find(".alert-danger").empty();
				$.each(item, function(key, value) {
					$this.find("[name='" + key + "']").val(value);
				});
				$this.find("#itemName").focus();
			}
		}

		var $this = $(this),
			id = e.relatedTarget && +e.relatedTarget.getAttribute("data-item-id"),
			item, title;

		if (id && !isNaN(id)) {
			title = "Modifica";
			$.each(loadedItems, function(i, it) {
				if (it.id === id) {
					item = it;
					return false;
				}
			});
			if (item) fillForm();
			else $.getJSON("/catalog/items/" + id)
					.then(function(json) {
						item = json;
					}, function(xhr, status, message) {
						item = { error: xhr.status + " " + message };
					})
					.always(theSpinner())
					.always(fillForm);
		} else {
			title = "Aggiungi"
			item = { name: "", description: "", code: "", type: "other", price: 0 };
			fillForm();
		}
		$this.find("#itemModalLabel").text(title + " un articolo");
	}).on("submit", "form", function(e) {
		e.preventDefault();
		var data = {}, id = $("#itemModal").data("itemId");
		$(this).find("[name]").each(function() {
			data[this.name] = $(this).val();
		});

		$modal.modal("hide");
		globalSpinner(true);
		$.ajax({ url: "/catalog/items/" + id, method: id ? "PATCH" : "POST", data: JSON.stringify(data), responseType: "json" })
			.then(function(json) {
				if (json && json.error) {
					bootbox.alert("Impossibile eseguire l'operazione: " + json.error);
					globalSpinner(false);
				} else hashchange();
			}, function(xhr, status, message) {
				bootbox.alert("Operazione fallita: " + xhr.status + " " + message);
				globalSpinner(false);
			});
	});
});