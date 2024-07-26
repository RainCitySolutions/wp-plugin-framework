/**
*
*/
jQuery(document).ready(function($) {           //wrapper
	let tableContainer = $('#raincityWpfShortUrlTable');

	$('[data-raincity-wpf-shorturlcode]').each(function () {
		new DeleteShortUrlHandler($(this), tableContainer);
	});

	$('input[id="raincityWpfShortUrlAddBtn"]').each(function () {
		let textCodeCtrl = $('input[id="raincityWpfShortUrlCode"]');
		let textInputCtrl = $('input[id="raincityWpfShortUrlInput"]');
		new AddShortUrlHandler($(this), tableContainer, textCodeCtrl, textInputCtrl);
	});
});


class BaseShortUrlHandler {
	constructor (elem, tableContainer) {
		this.table = tableContainer;

		this.ajaxurl = elem.data('url');
		this.nonce = elem.data('nonce');
		this.action = elem.data('action');
	}

	reloadTable(tableHtml) {
		let obj = this;
		this.table.html(tableHtml);

		jQuery('[data-raincity-wpf-shorturlcode]').each(function () {
			new DeleteShortUrlHandler(jQuery(this), obj.table);
		});
	}
}

class DeleteShortUrlHandler
	extends BaseShortUrlHandler {
	constructor (elem, tableContainer) {
		super(elem, tableContainer);

		// extract url data
		this.code = elem.data('raincity-wpf-shorturlcode');

		// on click event
		elem.click(jQuery.proxy(this.deleteUrl, this));
	}

	deleteUrl(event) {
		let obj = this;

		document.body.style.cursor = 'wait';
		jQuery.post(
			this.ajaxurl,
			{	//POST request
				action: this.action,
				_ajax_nonce: this.nonce,
				shortcode: this.code
			},
			function(data) { //callback
				let json = JSON.parse(data);
				if (200 == json.code) {
					obj.reloadTable(json.table);
				}
				else {
					alert ("Unable to delte URL: " + json.error);
				}
				document.body.style.cursor = 'default';
			}
		);
	}
}

class AddShortUrlHandler
	extends BaseShortUrlHandler {
	constructor (elem, tableContainer, inputCode, inputUrl) {
		super(elem, tableContainer);

		this.inputCode = inputCode;
		this.inputUrl = inputUrl;

		// on click event
		elem.click(jQuery.proxy(this.addUrl, this));
	}

	addUrl(event) {
		let obj = this;

		document.body.style.cursor = 'wait';
		jQuery.post(
			this.ajaxurl,
			{	//POST request
				action: this.action,
				_ajax_nonce: this.nonce,
				short_code: this.inputCode.val().trim(),
				new_url: this.inputUrl.val().trim()
			},
			function(data, textStatus, jqXHR) { //callback
				let json = JSON.parse(data);
				if (200 == json.code) {
					obj.inputCode.val('');
					obj.inputUrl.val('');
					obj.reloadTable(json.table);
				}
				else {
					alert ("Unable to add URL: " + json.error);
				}
				document.body.style.cursor = 'default';
			}
		);
	}
}
