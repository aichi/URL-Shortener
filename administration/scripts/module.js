(function(Shortener){
	/**
	 * This super class describes needed methods and properties for each
	 * module which is used by UrlShortener.Application class.
	 * @interface
	 * @implenets {JAK.ISignals}
	 * @public
	 */
	Shortener.Module = Proto.extend({
		constructor: function (app) {

			//********** DEFINE A MEMBER VARIABLES *************************//
			/**
			 * Unique name of the module, used as a key in array
			 * of active modules of application
			 * @type {String}
			 * @readonly
			 * @public
			 */
			this.name = "";

			/**
			 * Events id cache
			 * @type {String[]}
			 */
			this._ec = [];

			/**
			 * DOM elements cache
			 * @type {Object}
			 */
			this._dom = {};

			/**
			 * Property which holds parent application instance
			 * @type {UrlShortener.Application}
			 */
			this._app = app;

			/**
			 * Array of objects {what: "eventName", callback: callback_func}. Callback function is
			 * called with one parameter: data literal object.
			 */
			this._internalEventListeners = [];
		},

		/**
		 * Attaching module to the view and starting interaction with user
		 * @param {HTMLElement} node
		 * @param {Object} params
		 * @public
		 */
		attach: function(node, params) {},

		/**
		 * Hiding module from the user view and stopping interactions
		 * @param {HTMLElement} node
		 * @public
		 */
		detach: function(node) {},

		/**
		 * This method is called when module would be activated. Module have to
		 * set in this method new history item and on history change callback is
		 * called attach method
		 * @param {Object} params
		 * @public
		 */
		registerHistory: function(params) {},

		//---------- Internal Event Listener interface - heavy (implemented interface) ------------
		/**
		 * Adds local listener to some event name
		 * @param {String} what Event name
		 * @param {Function} callback Callback called when event is emitted.
		 */
		addListener: function(what, callback) {
			var listener = {what: what, callback: callback};
			if (this._internalEventListeners.indexOf(listener) === -1) {
				this._internalEventListeners.push(listener);
			}
		},

		/**
		 * Removes a listener from listener list
		 * @param {String} what Event name
		 * @param {Function} callback
		 */
		removeListener: function(what, callback) {
			var listener = {what: what, callback: callback},
				index = this._internalEventListeners.indexOf(listener);
			if (index !== -1) {
				this._internalEventListeners.splice(index, 1);
			}
		},

		/**
		 * Local function for given class instance which emits a local event
		 * @param {String} what Event name
		 * @param {Object} [data] Optional data object which is passed to event listeners
		 */
		makeEvent: function(what, data) {
			var i,
				listeners = this._internalEventListeners,
				length = listeners.length;
			for (i = 0; i < length; i++) {
				if (what === listeners[i].what) {
					listeners[i].callback(data);
				}
			}
		}
	});
}(UrlShortener));