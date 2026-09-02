/*!
 * jQuery eModal Plugin
 * https://github.com/Reload-Lab/jquery-eModal
 *
 * @updated September 22, 2023
 * @version 2.1.2
 *
 * @author Domenico Gigante <domenico.gigante@reloadlab.it>
 * @copyright (c) 2023 Reload Laboratorio Multimediale <info@reloadlab.it> (https://www.reloadlab.it)
 * @license MIT
 */
 
(function($){
	
	function eModal()
	{
		var $modal; // Modal jQuery object
		var defer; // Promise
		var options = {}; // Modal options
		var settings = {}; // eModal settings
		var resolver; // Function
		
		// Some costants
		var DIV = '<div>';
		var BUTTON = '<button>';
		var LOADING = '<h5>Loading...</h5>' + 
			'<div class=progress>' + 
			'<div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>' + 
			'</div>';
		var EMPTY = '';
		
		var HIDE = 'hide';
		var EVENT_CLICK = 'click';
		var EVENT_SUBMIT = 'submit';
		var EVENT_SHOW = 'show.bs.modal';
		var EVENT_SHOWN = 'shown.bs.modal';
		var EVENT_HIDE = HIDE + '.bs.modal';
		var EVENT_HIDDEN = 'hidden.bs.modal';
		
		var MODAL_ID = 'eModal';
		var STYLE_ID = 'eStyle';
		
		var MODAL_BODY = 'modal-body';
		var MODAL_DIALOG = '.modal-dialog';
		var MODAL_BACKDROP = '.modal-backdrop';
		var MODAL_CONTENT = '.modal-content';
		
		var BIN_ID = 'eRecyclebin';
		var REC_MODAL_CONTENT = 'modal-rec';
		var TMP_MODAL_CONTENT = 'modal-tmp';
		
		var ANIMATION = 'fade';
		var BTN_DANGER = 'btn-danger';
		var INPUT = 'input[type=text], ' + 
			'input[type=password], ' + 
			'input[type=email], ' + 
			'input[type=tel], ' + 
			'input[type=date], ' + 
			'input[type=time], ' + 
			'input[type=datetime-local], ' + 
			'input[type=url], ' + 
			'input[type=number], ' + 
			'input[type=search], ' + 
			'input[type=color], ' + 
			'textarea, ' + 
			'select';
		var LABELS = {
			OK: 'Cancel'
		};
		var SIZE = {
			sm: 'sm',
			lg: 'lg',
			xl: 'xl'
		};
		var MODAL_CLASS = [
			'modal-dialog',
			'modal-eModal',
			'modal-dialog-scrollable'
		];
		var POSX = {
			'left': 'start',
			'center': 'center',
			'right': 'end'
		};
		var POSY = {
			'top': 'start',
			'middle': 'center',
			'bottom': 'end'
		};
		
		// Default settings
		var defaults = {
			'animation': ANIMATION, // string
			'async': false, // boolean
			'ajax': {
				'dataType': false, // string
				'error': false, // function
				'loading': false, // boolean
				'loadingHtml': LOADING, // string
				'success': false, // function
				'url': false, // string
				'xhr': {} // object
			},
			'binId': false, // string
			'bodyStyles': false, // object
			'buttons': null, // array
			'confirm': {
				'label': Object.keys(LABELS)[0], // string
				'style': [] // array
			},
			'cssClass': false, // string
			'footer': true, // boolean
			'header': true, // boolean
			'headerClose': true, // boolean
			'headerCloseHtml': '<button type="button" class="x close" data-dismiss="modal" aria-label="Close">' +
				'<span aria-hidden="true">&times;</span>' + 
				'</button>', // string
			'height': false, // number
			'id': false, // string
			'iframe': {
				'attributes': {},
				'loadingHtml': LOADING, // string
				'url': false // string
			},
			'message': false, // jquery
			'modalOptions': false, // object
			'overlayClose': true, // boolean
			'onHide': false, // function
			'position': ['top', 'center'], // array
			'prompt': {
				'autocomplete': false, // boolean
				'autofocus': false, // boolean
				'checkValidity': false, // boolean
				'label': Object.keys(LABELS)[0], // string
				'pattern': false, // string
				'placeholder': false, // string
				'required': true, // boolean
				'style': [], // array
				'type': 'text', // string
				'value': false // string
			},
			'size': EMPTY, // string
			'subtitle': false, // string
			'title': 'Attention', // string
			'useBin': false, // boolean
			'width': false, // number
			'wrapSubtitle': '<small>', // string
			'wrapTitle': '<h5>' // string
		};
        
		// Export Public function
		var root = {};
		
		// Modal types function
		root.alert = alert;
		root.ajax = ajax;
		root.confirm = confirm;
		root.prompt = prompt;
		root.iframe = iframe;
		root.embed = embed;
		
		// Utility function
		root.close = close;
		root.label = addLabel;
		root.emptyBin = emptyBin;
		root.modal = getModal;
		root.defer = getDefer;
		root.size = addSize;
		root.version = '2.1.2';

		return root;

		//#region /////////////////////////* Private Logic */////////////////////////
		/**
		 * Basic modal da utilizzare per tutti gli altri modal.
		 * @param {Object | String} data - this can be the message string or the full detailed object.
		 * @param {String} title - the string that will be shown in modal header.
		 * @returns {Promise} Promise with modal $DOM element
		 */
		function _modal(data, title)
		{
			// Set eModal settings
			_setEModalOptions(typeof data === 'object'? data: {});
			
			// Set modal options
			_setModalOptions(settings.modalOptions);
			
			// If 'overlayClose' is false...
			if(!settings.overlayClose){
				
				// Disable close modal on click over backdrop
				options.backdrop = 'static';
			}
			
			// If 'data' is string...
			if(typeof data === 'string'){
				
				// Set 'message'
				settings.message = data;
			}
			
			// If 'title' is set...
			if(title && title != ''){
				
				// Set 'title'
				settings.title = title;
			}
			
			// Create modal structure
			_setup(settings);
			
			// Set promise object
			_createDeferred();
			
			// Append Header, Body and Footer
			_build({
				header: _getHeader(settings), 
				footer: _getFooter(settings), 
				body: _getBody(settings)
			});
			
			// If 'header' is false and 'overlayClose' is true...
			if(!data.header && data.overlayClose){
				
				// Add close button
				$modal.find(MODAL_CONTENT)
					.append('<button class="x btn btn-sm btn-dark position-absolute" style="top: -10px; left: -10px; width: 20px; height: 20px; padding: 0; line-height: 1;" data-dismiss="modal" type="button">&times;</button>');
			}
			
			// If the modal does not wait for the resolution of a promise...
			if(defer.promise && !settings.async){
				
				// The promise is reject when the modal is displayed
				$modal.on(EVENT_SHOWN, defer.reject);
			}

			return defer.promise;
		}

		/**
		 * Set or change eModal options.
		 * @param {Object} overrideOptions
		 * @returns {Object} eModal
		 */
		function _setEModalOptions(overrideOptions)
		{
			// Reset 'setting' for new eModal
			settings = {};
			
			$.extend(true, settings, defaults, overrideOptions);
			
			console.log(settings);
		}

		/**
		 * Set or change bootstrap modal options.
		 * @param {Object} overrideOptions
		 * @returns {Object} eModal
		 */
		function _setModalOptions(overrideOptions)
		{
			// Reset 'options' for new modal
			options = {};
			
			if(typeof overrideOptions === 'object'){
				
				$.extend(options, overrideOptions);
			}
		}
		
		/**
		 * Handle default values and toggle between {Object | String}.
		 * Create or get Modal element
		 * @param {Object} obj - This is a full detailed object
		 */
		function _setup(obj)
		{
            // If 'obj' is empty...
			if(!obj){
				
				throw new Error('First argument cannot be blank!');
			}
			
			// Reset modal
			_resetModal(obj);

			// Set $modal variable
			_createModal(obj);
			
			// If resolver function exists...
			if(typeof resolver === 'function'){
				
				// Bind resolver to hide event
				$modal.on(EVENT_HIDE, resolver);
			}
			
			// If onHide function exists...
			if(typeof obj.onHide === 'function'){
				
				// Attach on hide event to modal
				$modal.on(EVENT_HIDE, obj.onHide);
			}
		}

		/**
		 * Reset modal to default state
		 */
		function _resetModal()
		{
			// If '$modal' doesn't exist
			if(!$modal){
				
				return;
			}
			
			$modal
				// Delete events
				.off(EVENT_SHOW)
				.off(EVENT_SHOWN)
				.off(EVENT_HIDE)
				.off(EVENT_HIDDEN)
				.off(EVENT_CLICK, '.x')
				// Remove animation
				.removeClass(ANIMATION)
				// Search for modal-dialog and remove all classes
				// and id attribute 
				.find(MODAL_DIALOG)
				.removeAttr('id')
				.removeClass(function(i, cls)
				{
					return cls.split(' ')
						.map(function(item)
						{
							if(MODAL_CLASS.indexOf(item) == -1){
								
								return item;
							}
						})
						.join(' ');
				})
				// Search for modal-content and remove all children 
				// (header, body, footer, etc.)
				.find(MODAL_CONTENT)
				.children()
				.remove();
			
			// Empty css style container
			$modal.find('#' + STYLE_ID)
				.empty();
			
			// If backdrop is in Dom...
			if($(MODAL_BACKDROP).length){
				
				// Remove from Dom
				$(MODAL_BACKDROP).remove();
			}
			
			// Destroy the current instance of the modal component
			$modal.modal('dispose');
		}

		/**
		 * Return a new modal object if is the first request or the already created modal.
		 * @param {Object | Boolean} obj
		 */
		function _createModal(obj)
		{
			// If modal exists...
			if($('#' + MODAL_ID).length){
				
				// Get jQuery object
				$modal = $('#' + MODAL_ID);
			} 
			
			// Else...
			else{
				
				// Create modal
				$modal = $('<div id="' + MODAL_ID + '" class="modal eModal" tabindex="-1">' +
					
					// dialog
					'<div class="' + (MODAL_CLASS.join(' ')) + '">' +
					'<div class="modal-content">' +
					'</div>' +
					'</div>' +
					// § dialog 
					
					// style
					'<style id="' + STYLE_ID + '"></style>' +
					// § style 
	
					'</div>')
				
					// Append to body
					.appendTo('body');
			}

			
			// Set 'cssClass', 'size' and 'animation'
			$modal
				.addClass(obj.animation)
				.find(MODAL_DIALOG)
				.prop('id', function()
				{
					return obj.id || undefined;
				})
				.addClass(obj.cssClass || null)
				.addClass(obj.size && SIZE[obj.size]? 
					'modal-' + obj.size: 
					null
				);
			
			// Style inline
			$modal
				.find('#' + STYLE_ID)
				.html(
					_setStyle(obj)
				);
			
			// On hidden event call recycleModal
			$modal
				.on(EVENT_HIDDEN, function(e)
				{
					_recycleModal(e);
				})
				
				// On click close button event call closeModal
				.on(EVENT_CLICK, '.x', function(e)
				{
					_closeModal(e);
				});
			
			// If 'autofocus' is set...
			if(obj.prompt && obj.prompt.autofocus){
				
				// On modal shown, set focus on first input 
				$modal
					.on(EVENT_SHOWN, function()
					{
						$(this).find(INPUT)
							.first()
							.focus();
					});
			}
		}

		/**
		 * Return inline style of eModal
		 * @param {Object} obj
		 * @returns {String}
		 */
		function _setStyle(obj)
		{
			// If 'width' is set...
			if(obj.width){
				
				// Calculate modal width
				var width = _calcSize(obj.width);
			}
			
			// If 'height' is set...
			if(obj.height){
				
				// Calculate modal height
				var height = _calcSize(obj.height, 'height');
			}
			
			return '.modal-eModal {display: flex; align-items: ' + 
				(POSY[obj.position[0]]? POSY[obj.position[0]]: defaults.position[0]) + 
				'; justify-content: ' + 
				(POSX[obj.position[1]]? POSX[obj.position[1]]: defaults.position[1]) + 
				'; width: calc(100% - 1rem)!important; max-width: none; height: calc(100% - 1rem)!important;}' + 
				'.modal-eModal.modal-dialog-scrollable .modal-content {overflow: visible !important;}' + 
				'@media (min-width: 576px) {.modal-eModal {width: calc(100% - 3.5rem)!important; height: calc(100% - 3.5rem)!important;}}' + 
				'.modal-eModal .modal-content {max-width: 500px;}' + 
				'.modal-eModal.modal-sm .modal-content {max-width: 300px;}' + 
				'.modal-eModal.modal-lg .modal-content {max-width: 800px;}' + 
				'.modal-eModal.modal-xl .modal-content {max-width: 1140px;}' + 
				(width? '.modal-eModal .modal-content {max-width: ' + width + ' !important; width: 100%;}': '') + 
				(height? '.modal-eModal .modal-content {max-height: ' + height + ' !important; height: 100%;}': '');
		}

		/**
		 * Calculate size of modal (width/height)
		 * @param {String} size
		 * @param {String} type
		 * @returns {String} Size in pixel
		 */
		function _calcSize(size, type)
		{
			// Root font-size
			var fontSize = Number(
				window.getComputedStyle(document.documentElement)
					.getPropertyValue('font-size')
					.match(/\d+/)[0]
			);
			
			// If window width is major than 576...
			if(window.innerWidth >= 576){
				
				var factor = 3.5;
			} 
			
			// Else...
			else{
				
				var factor = 1;
			}
			
			// If 'type' is height...
			if(type && type == 'height'){
				
				// Window height minus factor * fontSize
				var ws = window.innerHeight - (factor * fontSize);
			} 
			
			// Else...
			else{
				
				// Window width minus factor * fontSize
				var ws = window.innerWidth - (factor * fontSize);
			}
			
			// If size is in pixel...
			if((''+size).indexOf('px') != -1){
				
				size = +size.replace('px', '');
				
				// If 'size' is major than window size...
				if(size > ws){
					
					size = ws;
				} 
				
				return size + 'px';
			} 
			
			// If size is in percentage...
			else if((''+size).indexOf('%') != -1){
				
				size = +size.replace('%', '');
				
				// If 'size' is major than 100...
				if(size > 100){
					
					size = ws;
				}
				
				// Else...
				else{
					
					size = parseInt(ws * size / 100);
				}
				
				return size + 'px';
			} 
			
			// If 'size' is numeric...
			else if(!isNaN(+size)){
				
				size = +size;
				
				// If 'size' is major than window size...
				if(size > ws){
					
					size = ws;
				} 
				
				// If 'size' is equal to 0...
				else if(size == 0){
					
					size = 300;
				}
				
				return size + 'px';
			}
			
			return;
		}

		/**
		 * Will use Promises from jQuery.
		 * @returns {Promise}
		 */
		function _createDeferred()
		{
			// If '$modal' doesn't exist
			if(!$modal){
				
				return;
			}
			
			// jQuery defer object
			defer = $.Deferred();
			
			// Call promise()
			defer.promise = defer.promise();
		
			// Set promise.element to modal-dialog jQuery object
			defer.promise.element = $modal.find(MODAL_DIALOG);
			
			return defer;
		}

		/**
		 * Will create modal DOM header with titles and close X.
		 * @param {Object} obj - this is a full detailed object
		 * @returns {jQuery Object} header element
		 */
		function _getHeader(obj)
		{
			// If 'header' is set...
			if(obj.header){
				
				// Create div container
				var $messageHeader = $(DIV).addClass('modal-header')
					.prop('id', 'eHeader');
				
				// If 'title' is set...
				if(obj.title){
					
					// Append title to header
					var $title = $(obj.wrapTitle)
						.appendTo($messageHeader)
						.addClass('modal-title')
						.html(obj.title);
					
					// If 'subtitle' is set...
					if(obj.subtitle){
						
						// Append subtitle to title
						$(obj.wrapSubtitle).appendTo($title)
							.html(' ' + obj.subtitle);
					}
				}
				
				// If 'headerClose' is set...
				if(obj.headerClose){
					
					// Append close button to header
					$(obj.headerCloseHtml)
						.appendTo($messageHeader);
				}
				
				return $messageHeader;
			}
			
			return EMPTY;
		}

		/**
		 * Will create modal DOM footer with all buttons.
		 * @param {Object} obj - this is a full detailed object
		 * @returns {jQuery Object} footer element
		 */
		function _getFooter(obj)
		{
			// If 'footer' is set...
			if(obj.footer){
				
				// If 'buttons' is strict equal to false...
				if(obj.buttons === false){
					
					// Return an empty footer
					return EMPTY;
				}
				
				// Create div container
				var $messageFooter = $(DIV).addClass('modal-footer')
					.prop('id', 'eFooter');
				
				// If 'buttons' is array...
				if(Array.isArray(obj.buttons)){
					
					for(var i = 0; i < obj.buttons.length; i++){
						
						// Button options
						var btnOp = obj.buttons[i];
						
						// Create button
						var $btn = $(BUTTON).attr('type', 'button')
							.addClass('btn ' + (btnOp.style || 'btn-primary'));
						
						// Unset 'style' property
						if(btnOp.style){
							
							delete btnOp.style;
						}
						
						for(var index in btnOp){
							
							if(btnOp.hasOwnProperty(index)){
								
								switch(index){
									
									// Button closes the modal
									case 'close':
										
										// If 'close' is true...
										if(btnOp[index]){
											
											// Add data-dismiss to close modal
											$btn.attr('data-dismiss', 'modal')
												.addClass('x');
										}
										break;
									
									// 'Click' option is a function,
									// which is called when the button is clicked.
									// Value of 'this' is a jQuery object 
									// corresponding to the selection of 'modal-content'.
									case EVENT_CLICK:
										
										// If click is a function...
										if(typeof btnOp.click == 'function'){
											
											// Get click function
											var fn = btnOp.click
												.bind(
													$modal.find(MODAL_CONTENT)
												);
											
											// Assign click event to button
											$btn.on('click', fn);
										}
										break;
									
									// I set the value of resolve to true or false.
									// Based on the button clicked there will be a resolve 
									// or reject of the Promise
									case 'resolve':
										
										// Assign 'resolve' status to button
										$btn.data(index, btnOp[index]);
										break;
									
									// Button text
									case 'text':
										
										// Button text
										$btn.html(btnOp[index]);
										break;
									
									default:
										
										// All other possible HTML attributes to button element
										$btn.attr(index, 
											$('<div/>').text(btnOp[index])
												.html()
										);
								}
							}
						}
						
						// Append button to footer
						$messageFooter.append($btn);
					}
				} 
				
				// If 'buttons' is a string...
				else if(typeof obj.buttons === 'string'){
					
					// Add string
					$messageFooter.html(obj.buttons);
				}
				
				// If no buttons defined by user...
				else{
					
					// Add a standard close button
					$messageFooter.append('<button class="x btn btn-primary" data-dismiss="modal" type="button">Close</button>');
				}
				
				return $messageFooter;
			}
			
			return EMPTY;
		}

		/**
		 * Extract message from arguments.
		 * @param {Object} obj - this is a full detailed object
		 * @returns {jQuery Object}
		 */
		function _getBody(obj)
		{
			// If 'message' is a jQuery object...
			if(obj.message && obj.message instanceof jQuery){
				
				// Clone 'message' from Dom
				var content = obj.message.clone(true);
			} 
			
			// Else...
			else{
				
				var content = obj.message;
			}
			
			// Try to create a jQuery object
			try{
				
				// If 'useBin' is true, 'binId' is set,
				// and recycle modal is in bin...
				if(obj.useBin && obj.binId && 
					$('#' + BIN_ID).find('#' + obj.binId + '-bin').length
				){
					
					var $bin_content = $('#' + BIN_ID).find('#' + obj.binId + '-bin');
					
					// Clone '$content' from bin
					var $content = $bin_content.clone(true);
					
					// Remove recycled modal from bin
					$bin_content.remove();
				} 
				
				// Else...
				else{
					
					// Html content
					var $content = $(obj.ajax.loading?
							obj.ajax.loadingHtml:
							content
						);
				}
				
				// If $content is empty...
				if(!$content.length){
					
					// This is a string
					throw new Error('This is a string!');
				}
				
				// If $content contain modal-body...
				if($content.hasClass(MODAL_BODY) || 
					$content.find('.' + MODAL_BODY).length
				){
					
					var $message = $content
						.addClass(obj.useBin? 
							REC_MODAL_CONTENT: 
							TMP_MODAL_CONTENT
						);
				} 
				
				// Else...
				else{
					
					// Create div container
					var $message = $(DIV)
						.addClass(MODAL_BODY)
						.addClass(obj.useBin? 
							REC_MODAL_CONTENT: 
							TMP_MODAL_CONTENT
						)
						.attr('style', 'word-wrap: break-word;')
						.append($content);
				}
			} 
			
			// Else 'message' is a string...
			catch(e){
				
				// Get error
				console.log(e);
				
				// Create div container
				var $message = $(DIV)
					.addClass(MODAL_BODY)
					.attr('style', 'word-wrap: break-word;')
					.html(obj.ajax.loading?
						obj.ajax.loadingHtml:
						content
					);
			}
			
			// Set Body ID
			$message.prop('id', (obj.binId || 'eBody'));

			return obj.bodyStyles && (obj.bodyStyles !== $message.css && $message.css(obj.bodyStyles)), $message;
		}

		/**
		 * Find modal content and append parts to it.
		 * @param {String | DOM} parts
		 */
		function _build(parts)
		{
			// If '$modal' doesn't exist
			if(!$modal){
				
				return;
			}
			
			// Find modal-content
			$modal.find(MODAL_CONTENT)
				
				// Append header, body and footer
				.append(
					parts.header, 
					parts.body, 
					parts.footer
				);
				
			// Set modal options: 
			// backdrop: Includes a modal-backdrop element. 
			// 			Alternatively, specify 'static' for a backdrop 
			//			which doesn't close the modal on click.
			// 			Default: true
			// keyboard: Closes the modal when escape key is pressed. Default: true
			// focus: Puts the focus on the modal when initialized. Default: true
			// show: Shows the modal when initialized. Default: true
			$modal.modal(options);
		}

		/**
		 * Move content to recycle bin if is a DOM object defined by user,
		 * delete it if is a simple string message.
		 * All modal messages can be deleted if default setting "allowContentRecycle" = false.
		 * @param {Object} event
		 */
		function _recycleModal(event)
		{
			// If '$modal' doesn't exist
			if(!$modal){
				
				return;
			}
			
			// If recycle bin must be created...
			if(!$('#' + BIN_ID).length){
				
				// Add recycle bin container to body
				$(DIV).appendTo('body')
					.prop('id', BIN_ID)
					.hide();
			}
			
			// If 'event' is an event handler...
			if(event instanceof $.Event){
				
				var obj = settings;
			} 
			
			// Else...
			else{
				
				var obj = event;
			}
			
			// Look for a modal content with class css 'modal-rec'
			var $content = $modal.find('.' + REC_MODAL_CONTENT)
				// Removes the css class 'modal-rec'
				.removeClass(REC_MODAL_CONTENT)
				// Appends the contents to the recycle bin container
				.appendTo('#' + BIN_ID);
			
			// If 'binId' is set...
			if(obj.binId){
				
				// Set id of modal in recycle bin
				$content.prop('id', obj.binId + '-bin');
			}

			// If recycle bin is disabled or 'binId' is unset...
			if(!obj.useBin || !obj.binId){
				
				// Remove content from Dom
				$content.remove();
			}
		}

		/**
		 * Close modal on X button press
		 * @param {Object} event
		 */
		function _closeModal(event)
		{
			event.preventDefault();
			
			// Clicked element
			var btn = $(event.currentTarget);
			
			// If button type is different than submit...
			if(btn.prop('type') !== EVENT_SUBMIT){

				// Hide modal
				return $modal.modal(HIDE);
			}
			
			try{
				
				// Check validity of form element
				if(obj.prompt && obj.prompt.checkValidity && 
					btn.closest('form')[0].checkValidity()
				){

					// Than close
					close();
				}
			} catch(e){
				
				// Hide modal
				close();
			}
		}

		/**
		 * @param {String} version 
		 * @returns {Boolean}
		 */
		function _jQueryMinVersion(version)
		{
			var $ver = $.fn.jquery.split('.');
			var ver = version.split('.');
			var $major = $ver[0];
			var $minor = $ver[1];
			var $patch = $ver[2];
			var major = ver[0];
			var minor = ver[1];
			var patch = ver[2];
        
			return !(
				(major > $major) ||
				(major === $major && minor > $minor) ||
				(major === $major && minor === $minor && patch > $patch)
			);
		}
		//#endregion

		//#region /////////////////////////* Public Methods */////////////////////////
		/**
		 * Non blocking alert whit bootstrap.
		 * @param {Object | String} data - this can be the message string or the full detailed object.
		 * @param {String} title - the string that will be shown in modal header.
		 * @returns {Promise} Promise with modal element
		 */
		function alert(data, title)
		{
			// Modal settings
			var params = {
				'message': data.message || data
			};
			
			// Merge 'params' in 'data'
			params = $.extend({}, typeof data === 'object'? data: {}, params);

			return _modal(params, title);
		}

		/**
		 * Gets data from URL to eModal body
		 * @param {Object | String} data - this can be the message string or the full detailed object
		 * @param {String} title - the string that will be shown in modal header
		 * @returns {Promise} Promise with modal element
		 */
		function ajax(data, title)
		{
			// Modal settings
			var params = {
				'ajax': {
					'loading': data.ajax && data.ajax.loading != undefined? data.ajax.loading: true,
					'url': (data.ajax && data.ajax.url) || data.message || data,
					'xhr': {
						'dataType': (data.ajax && data.ajax.dataType) || 'html',
						'xhrFields': {
							'withCredentials': false
						}
					}
				}
			};
			
			// Merge 'params' in 'data'
			params = $.extend(true, {}, typeof data === 'object'? data: {}, params);
			
			// Send ajax request
			$.ajax(params.ajax.url, params.ajax.xhr)
				.done(success)
				.fail(error);

			return _modal(params, title);

			/**
			 * Function call on ajax success.
			 * @returns {Promise} Promise with modal element
			 */
			function success(resp)
			{
				// If modal is just shown...
				if(($('#' + MODAL_ID).data('bs.modal') || {})._isShown){
					
					// data.ajax.success is a funtion. 
					// It get HTML and must return HTML.
					$modal.find('.' + MODAL_BODY)
						.html(params.ajax && typeof params.ajax.success == 'function'? 
							params.ajax.success(resp): 
							resp);
					
					return defer && settings.async? 
						defer.resolve($modal): 
						false;
				}
				
				// Else...
				else{
					
					$modal.on(EVENT_SHOWN, function(e)
					{
						// data.ajax.success is a funtion. 
						// It get HTML and must return HTML.
						$modal.find('.' + MODAL_BODY)
							.html(params.ajax && typeof params.ajax.success == 'function'? 
								params.ajax.success(resp): 
								resp);
	
						return defer && settings.async? 
							defer.resolve($modal): 
							false;
					});
				}
			}

			/**
			 * Function call on ajax error.
			 * @returns {Promise} Promise with modal element
			 */
			function error(error, status, thrown)
			{
				// If modal is just shown...
				if(($('#' + MODAL_ID).data('bs.modal') || {})._isShown){
					
					// data.ajax.error is a funtion. 
					// It get error, status, thrown e params and must return HTML.
					var msg = params.ajax && typeof params.ajax.error == 'function'?
						params.ajax.error(error, status, params):
						'<div class="alert alert-danger mb-0">' +
						'<strong>XHR Fail: </strong>URL [ ' + 
							params.ajax.url + 
							'] load fail.' +
						'</div>';
	
					$modal.find('.' + MODAL_BODY)
						.html(msg);
				
					return defer && settings.async? 
						defer.reject(error): 
						false;
				}
				
				// Else...
				else{
						
					$modal.on(EVENT_SHOWN, function(e)
					{
						// data.ajax.error is a funtion. 
						// It get error, status, thrown e params and must return HTML.
						var msg = params.ajax && typeof params.ajax.error == 'function'?
							params.ajax.error(error, status, params):
							'<div class="alert alert-danger mb-0">' +
							'<strong>XHR Fail: </strong>URL [ ' + 
								params.ajax.url + 
								'] load fail.' +
							'</div>';
		
						$modal.find('.' + MODAL_BODY)
							.html(msg);
					
						return defer && settings.async? 
							defer.reject(error): 
							false;
					});
				}
			}
		}

		/**
		 * Non blocking confirm dialog with bootstrap.
		 * @param {Object | String} data - this can be the message string or the full detailed object.
		 * @param {String} title - the string that will be shown in modal header.
		 * @returns {Promise} Promise with modal element
		 */
		function confirm(data, title)
		{
			// Modal settings
			var params = {
				'async': true,
				'footer': true,
				'message': data.message || data,
				'buttons': [
					{ // Confirm
						'close': true,
						'click': resolve,
						'resolve': true,
						'text': data.confirm && LABELS[data.confirm.label]? data.confirm.label: defaults.confirm.label,
						'style': data.confirm && data.confirm.style && data.confirm.style[0]
					},
					{ // Cancel
						'close': true,
						'text': data.confirm && LABELS[data.confirm.label]? LABELS[data.confirm.label]: LABELS[defaults.confirm.label],
						'style': data.confirm && data.confirm.style && data.confirm.style[1] || BTN_DANGER
					} 
				]
			};
			
			// Merge 'params' in 'data'
			params = $.extend({}, typeof data === 'object'? data: {}, params);
			
			// Set resolver on hide event
			resolver = resolve;

			return _modal(params, title);

			/**
			 * Function call on Confirm buttons click or on modal hide.
			 * Resolve the promise.
			 * @returns {Promise} Promise with modal element
			 */
			function resolve(e)
			{
				resolver = null;
				
				return $(e.currentTarget).data('resolve')? 
					defer.resolve($modal): 
					defer.reject($modal);
			}
		}

		/**
		 * Provides one value form.
		 * @param {Object | String} data - this can be the value string label or the full detailed object.
		 * @param {String} title - the string that will be shown in modal header.
		 * @returns {Promise} Promise with modal element
		 */
		function prompt(data, title)
		{
			// Modal settings
			var params = {
				'async': true,
				'footer': false,
				'message': data.message || data
			};
			
			// Create footer and buttons
			var buttons = _getFooter({
					'footer': true,
					'buttons': [
						{ // Confirm
							'close': false,
							'type': EVENT_SUBMIT,
							'text': data.prompt && LABELS[data.prompt.label]? data.prompt.label: defaults.prompt.label,
							'style': data.prompt && data.prompt.style && data.prompt.style[0]
						},
						{ // Cancel
							'close': true,
							'type': 'reset',
							'text': data.prompt && LABELS[data.prompt.label]? LABELS[data.prompt.label]: LABELS[defaults.prompt.label],
							'style': data.prompt && data.prompt.style && data.prompt.style[1] || BTN_DANGER
						} 
					]
				});
			
			// Merge 'params' in 'data'
			params = $.extend({}, typeof data === 'object'? data: {}, params);
			
			// Create form
			params.message = $('<form class="m-0 p-0" role="form">' +
				'<div class="' + MODAL_BODY + '">' +
				
				// label
				'<label for="prompt-input" class="control-label">' + 
					(params.message || EMPTY) + 
					'</label>' +
				// § label
				
				// input
				'<input class="prompt-input form-control"' + 
					' type="' + ((params.prompt && params.prompt.type) || 'text') + '"' + 
					' value="' + ((params.prompt && params.prompt.value) || EMPTY) + '"' + 
					(params.prompt && params.prompt.required? ' required': EMPTY) + 
					(params.prompt && params.prompt.pattern? ' pattern="' + params.prompt.pattern + '"': EMPTY) + 
					' autocomplete="' + (params.prompt && params.prompt.autocomplete? 'on': 'off') + '"' + 
					(params.prompt && params.prompt.placeholder? ' placeholder="' + params.prompt.placeholder + '"': EMPTY) + 
					'">' +
				// § input
				
				'</div>' +
				'</form>')
				
				// Append footer and buttons inside the form
				.append(buttons)
				
				// Add submit event
				.on(EVENT_SUBMIT, submit);
			
			// Set resolver on hide event
			resolver = resolve;
			
			return _modal(params, title);

			/**
			 * Function call on form submit.
			 * Resolve the promise.
			 * @returns false
			 */
			function submit(e)
			{
				e.preventDefault();
				
				// Get input value
				var value = $modal.find('.prompt-input')
					.val();

				// Resolve the promise
				e.type === EVENT_SUBMIT?
					defer.resolve(value):
					false;

				// Close modal
				return $modal.modal(HIDE);
			}

			/**
			 * Function call on Confirm buttons click or on modal hide.
			 * Resolve the promise.
			 * @returns {Promise} Promise with modal element
			 */
			function resolve()
			{
				resolver = null;
				
				return defer.promise? defer.reject($modal): false;
			}
		}

		/**
		 * Will load a URL in iFrame inside the modal body.
		 * @param {Object | String} data - this can be the URL string or the full detailed object.
		 * @param {String} title - the string that will be shown in modal header.
		 * @returns {Promise} Promise with modal element
		 */
		function iframe(data, title)
		{
			// Modal settings
			var params = {
				'footer': false,
				'iframe': {
					'url': (data.iframe && data.iframe.url) || data.message || data
				}
			};
			
			// Merge 'params' in 'data'
			params = $.extend(true, {}, typeof data === 'object'? data: {}, params);
			
			// Set modal body
			params.message = $('<div class="' + MODAL_BODY + ' p-0">' +
				
				// loading
				'<div class="eLoading d-flex flex-column justify-content-center position-absolute w-100 h-100 p-3 text-center bg-white" style="z-index: 100"></div>' +
				// § loading
				
				// iframe
				'<iframe class="d-block w-100 h-100" frameborder="0" style="z-index: 10"></iframe>' +
				// § iframe
				
				'</div>');
			
			// Set iframe src
			var iframe = params.message.find('iframe')
				.attr('src', params.iframe.url);
			
			// If 'attributes' exists...
			if(params.iframe && typeof params.iframe.attributes == 'object'){
				
				// Set iframe attribute
				$.each(params.iframe.attributes, function(index, value)
				{
					if(index == 'width' || index == 'height'){
						
						return;
					}
					
					iframe.attr(index, $('<div/>').text(value).html());
				});
			}
			
			// Set loadingHtml
			params.message.find('.eLoading')
				.append((data.iframe && data.iframe.loadingHtml) || defaults.iframe.loadingHtml);
			
			// On load iframe
			_jQueryMinVersion('3.0.0')? 
				params.message.find('iframe')
					.on('load', iframeReady):
				params.message.find('iframe')
					.load(iframeReady);

			return _modal(params, title);

			/**
			 * Function call when iframe is loaded.
			 * Resolve the promise.
			 * @returns false
			 */
            function iframeReady()
			{
				$(this).parents('.' + MODAL_BODY)
					.find('.eLoading')
					.fadeOut(400, function()
					{
						$(this).remove();
					});

				// If 'async' is true...
				if(defer.promise && settings.async){
					
					// Resolve promise
					defer.resolve($modal);
				}
			}
		}

		/**
		 * Will load a URL to embed inside the modal body.
		 * @param {Object | String} data - this can be the URL string or the full detailed object.
		 * @param {String} title - the string that will be shown in modal header.
		 * @returns {Promise} Promise with modal element
		 */
		function embed(data, title)
		{
			// Modal settings
			var params = {
				'iframe': {
					'url': (data.iframe && data.iframe.url) || data.message || data
				}
			};
			
			// Merge 'params' in 'data'
			params = $.extend(true, {}, typeof data === 'object'? data: {}, params);
			
			// Set modal body
			params.message = $('<div class="' + MODAL_BODY + ' p-0">' +
				
				// loading
				'<div class="eLoading d-flex flex-column justify-content-center position-absolute w-100 h-100 p-3 text-center bg-white" style="z-index: 100"></div>' +
				// § loading
				
				// iframe
				'<div class="embed-responsive embed-responsive-16by9" style="z-index: 10">' +
				'<iframe class="embed-responsive-item d-block w-100" frameborder="0"></iframe>' +
				'</div>' +
				// § iframe
				
				'</div>');
			
			// Set iframe src
			var iframe = params.message.find('iframe')
				.attr('src', params.iframe.url);
			
			// If 'attributes' exists...
			if(params.iframe && typeof params.iframe.attributes == 'object'){
				
				// Set iframe attribute
				$.each(params.iframe.attributes, function(index, value)
				{
					if(index == 'width' || index == 'height'){
						
						return;
					}
					
					iframe.attr(index, $('<div/>').text(value).html());
				});
			}
			
			// Set loadingHtml
			params.message.find('.eLoading')
				.append((data.iframe && data.iframe.loadingHtml) || defaults.iframe.loadingHtml);
			
			// On load iframe
			_jQueryMinVersion('3.0.0')? 
				params.message.find('iframe')
					.on('load', iframeReady):
				params.message.find('iframe')
					.load(iframeReady);

			return _modal(params, title);

			/**
			 * Function call when iframe is loaded.
			 * Resolve the promise.
			 * @returns false
			 */
            function iframeReady()
			{
				$(this).parents('.' + MODAL_BODY)
					.find('.eLoading')
					.fadeOut(400, function()
					{
						$(this).remove();
					});

				// If 'async' is true...
				if(defer.promise && settings.async){
				
					defer.resolve($modal);
				}
			}
		}

		/**
		 * Close the current open eModal
		 * @returns {Object} eModal
		 */
		function close()
		{
			// If '$modal' exist...
			if($modal){
				
				$modal
					// Reset hide event
					.off(EVENT_HIDE)
					// Hide modal
					.modal(HIDE);
			}
		}

		/**
		 * Get the modal jQuery object
		 * @returns {jQuery Object}
		 */
		function getModal()
		{
			// If '$modal' exist...
			if($modal){
				
				return $modal;
			}
		}

		/**
		 * Get the promise jQuery object
		 * @returns {Promise} Promise with modal element
		 */
		function getDefer()
		{
			// If 'defer' exist...
			if(defer.promise){
				
				return defer;
			}
		}

		/**
		 * Add/Change button labels.
		 * @returns {Object} eModal
		 */
		function addLabel(yes, no)
		{
			LABELS[yes] = no;
			
			return root;
		}

		/**
		 * Add/Change modal size.
		 * @returns {Object} eModal
		 */
		function addSize(size)
		{
			SIZE[size] = size;
			
			return root;
		}

		/**
		 * Remove all Dom elements in recycle bin.
		 * @returns {Object} eModal
		 */
		function emptyBin()
		{
			$('#' + BIN_ID + ' > *').remove();
			
			return root;
		}
		//#endregion
	}
	
	// Add eModal to jquery
	if(!$.eModal){
			
		$.extend($, {
			eModal: eModal()
		});
	} else{
		
		throw new Error('$.eModal already exists!');
	}
}(jQuery));