/**
 * Channel letter designs / Order artwork / Customer uploads tabs for the admin
 * media popup. Server side: inc/media-source-tabs.php.
 */
(function () {
	'use strict';

	var media = window.wp && window.wp.media;
	var cfg = window.sfsMediaSources;
	if (!media || !cfg || !cfg.tabs || !media.view || !media.view.MediaFrame || !media.view.MediaFrame.Select) {
		return;
	}
	var $ = window.jQuery;
	var _ = window._;
	var Select = media.view.MediaFrame.Select;

	// Tabs, after "Upload files" (20) and "Media Library" (40).
	var originalRouter = Select.prototype.browseRouter;
	Select.prototype.browseRouter = function (routerView) {
		originalRouter.apply(this, arguments);
		_.each(cfg.tabs, function (tab, i) {
			var item = {};
			item[tab.mode] = { text: tab.label, priority: 60 + i * 10 };
			routerView.set(item);
		});
	};

	var originalBind = Select.prototype.bindHandlers;
	Select.prototype.bindHandlers = function () {
		originalBind.apply(this, arguments);
		var frame = this;
		_.each(cfg.tabs, function (tab) {
			frame.on('content:create:' + tab.mode, function (region) {
				frame.sfsSourceContent(tab, region);
			});
		});
	};

	Select.prototype.sfsSourceContent = function (tab) {
		var frame = this;
		var state = frame.state();
		var library = state.get('library');
		var props = library && library.props ? library.props.toJSON() : {};
		// Keep the window's file type limit (e.g. images only), drop the search text.
		var query = media.query({
			type: props.type,
			uploadedTo: undefined,
			orderby: 'date',
			order: 'DESC',
			sfs_source: tab.source
		});

		frame.$el.removeClass('hide-toolbar');
		var view = new media.view.AttachmentsBrowser({
			controller: frame,
			collection: query,
			selection: state.get('selection'),
			model: state,
			sortable: false,
			search: true,
			filters: false,
			date: false,
			display: state.has('display') ? state.get('display') : state.get('displaySettings'),
			dragInfo: false,
			idealColumnWidth: state.get('idealColumnWidth'),
			suggestedWidth: state.get('suggestedWidth'),
			suggestedHeight: state.get('suggestedHeight'),
			AttachmentView: state.get('AttachmentView')
		});
		frame.content.set(view);

		var note = $('<p class="sfs-source-empty"></p>').text((cfg.empty && cfg.empty[tab.source]) || 'Nothing here yet.').css({
			display: 'none', position: 'absolute', top: '70px', left: '16px', right: '16px', margin: 0, color: '#50575e', fontSize: '14px'
		});
		view.$el.append(note);
		// Show the "nothing here" note once the first page has loaded empty.
		var source = query.mirroring || query;
		var loaded = false;
		var toggle = function () {
			note.toggle(loaded && query.length === 0);
		};
		source.on('sync', function () { loaded = true; toggle(); });
		query.on('add remove reset', toggle);
	};

	// Media > Library (grid view, upload.php) has no tab row, so the same sources are a
	// row of buttons in its toolbar. They set sfs_source on the library query.
	var Browser = media.view.AttachmentsBrowser;
	var SourceButtons = media.View.extend({
		className: 'sfs-source-buttons',
		events: { 'click button': 'pick' },
		initialize: function () {
			media.View.prototype.initialize.apply(this, arguments);
			this.collection.props.on('change:sfs_source', this.render, this);
		},
		render: function () {
			var current = this.collection.props.get('sfs_source') || '';
			var tabs = [{ source: '', label: 'All files' }].concat(cfg.tabs);
			this.$el.empty();
			_.each(tabs, function (tab) {
				$('<button type="button" class="button"></button>')
					.attr('data-source', tab.source)
					.toggleClass('button-primary', tab.source === current)
					.text(tab.label)
					.appendTo(this.$el);
			}, this);
			return this;
		},
		pick: function (event) {
			this.collection.props.set({ sfs_source: $(event.currentTarget).attr('data-source') || '' });
		}
	});
	var originalToolbar = Browser.prototype.createToolbar;
	Browser.prototype.createToolbar = function () {
		originalToolbar.apply(this, arguments);
		if (this.controller.isModeActive && this.controller.isModeActive('grid') && this.collection && this.collection.props) {
			this.toolbar.set('sfsSources', new SourceButtons({
				priority: -50,
				controller: this.controller,
				collection: this.collection
			}).render());
			$(this.toolbar.el).addClass('sfs-has-sources');
		}
	};

	// Order / sender details in the right-hand sidebar.
	var Details = media.view.Attachment.Details;
	if (Details) {
		media.view.Attachment.Details = Details.extend({
			render: function () {
				Details.prototype.render.apply(this, arguments);
				var info = this.model.get('sfsInfo');
				if (info && info.length) {
					var box = $('<div class="sfs-source-info"></div>').css({
						background: '#f6f7f7', border: '1px solid #dcdcde', borderRadius: '4px', padding: '8px 10px', margin: '0 0 12px', fontSize: '12px', lineHeight: '1.5', clear: 'both'
					});
					_.each(info, function (row) {
						$('<div></div>')
							.append($('<strong></strong>').text(row[0] + ': '))
							.append(document.createTextNode(row[1]))
							.appendTo(box);
					});
					var link = this.model.get('sfsLink');
					if (link) {
						$('<div></div>').css('margin-top', '6px').append($('<a target="_blank" rel="noopener"></a>').attr('href', link).text('Open in admin')).appendTo(box);
					}
					this.$('.attachment-info').prepend(box);
					// Customer files belong to orders and leads: no one-click delete from here.
					this.$('.delete-attachment').remove();
				}
				return this;
			}
		});
	}
})();
