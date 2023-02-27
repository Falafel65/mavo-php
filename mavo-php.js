(function () {
	const Mavo = window.Mavo;

	Mavo.Backend.register(class php extends Mavo.Backend {
		id = 'php';

		constructor (url, o) {
			super(url, o);

			this.key = this.mavo.id || 'mavo';
			this.phpFile = new URL('mavo-backend.php', this.url.origin);

			this.secure = typeof o.secure !== 'undefined';

			// Default permissions
			this.permissions.on(['login', 'read']);
			// Try to get the user
			this.user = false;
			this.login(true);
		};

		// Low-level functions for reading data. You don’t need to implement this
		// if the mv-storage/mv-source value is a URL and reading the data is just
		// a GET request to that URL.
		get (url = new URL(this.url)) {
			// Should return a promise that resolves to the data as a string or object
			if (this.secure) {
				const postUrl = new URL(this.phpFile);
				// Send appID
				postUrl.searchParams.set('id', this.key);
				// Send filename
				postUrl.searchParams.set('source', this.source);
				// Set action to 'getData'
				postUrl.searchParams.set('action', 'getData');

				return this.request(postUrl)
					.then((data) => {
						if (typeof (data.status) !== 'undefined') {
							if (data.status === false) {
								if (this.isAuthenticated()) {
									this.mavo.error('Mavo-PHP : data fetch error', data.data);
								} else {
									this.mavo.error('Mavo-PHP : you need to be logged in to fetch this data', data.data);
								}
							}

							return data.data;
						}

						return data;
					});
			} else {
				return Mavo.Backend.prototype.get(url);
			}
		};

		/**
		 * Low-level saving code.
		 * @param {*} serialized Data serialized according to this.format
		 * @param {*} path Path to store data (not used)
		 * @param {object} o Arbitrary options
		 * @returns {Promise}
		 */
		put (serialized, path = this.path, o = {}) {
			// new URL() to clone phpFile url to set searchParams
			const postUrl = new URL(this.phpFile);
			// Send appID
			postUrl.searchParams.set('id', this.key);
			// Send filename
			postUrl.searchParams.set('source', this.source);
			// Default action to 'putData'
			postUrl.searchParams.set('action', 'putData');
			// Add all the arbitrary things
			for (const opt in o) {
				postUrl.searchParams.set(opt, o[opt]);
			}
			// Return POST request to server
			return this.request(postUrl, serialized, 'POST')
				.then((data) => {
					if (typeof (data.status) !== 'undefined') {
						if (data.status === false) {
							this.mavo.error('Mavo-PHP : save error', data.data);
						}
					}

					return data;
				});
		};

		/**
		 * File upload function.
		 * Src : https://github.com/mavoweb/mavo/blob/master/src/backend.github.js
		 * @param {File} file The file
		 * @param {string} path (not really used, passed to this.put)
		 * @returns {string} File name on server
		 */
		upload (file, path = this.path) {
			return Mavo.readFile(file)
				.then(dataURL => {
					let base64 = dataURL.slice(5); // remove data:
					const media = base64.match(/^\w+\/[\w+]+/)[0];
					base64 = base64.replace(RegExp(`^${media}(;base64)?,`), "");

					return this.put(base64, path, {
						action: 'putFile',
						file: file.name,
						path: path
					});
				})
				// Resolve with file name on server
				.then((fileData) => fileData.data.file);
		};

		/**
		 * Try to login.
		 * @param {boolean} passive If passive, resolve with user data if set
		 * @returns {Promise}
		 */
		login (passive) {
			// new URL() to clone phpFile url to set searchParams
			const loginUrl = new URL(this.phpFile);
			// Action is login
			loginUrl.searchParams.set('action', 'login');
			// Login from what ?
			loginUrl.searchParams.set('id', this.key);
			// Return request which can do 3 things :
			//  - Resolve with user data (if logged)
			//  - Resolve with false (if login failed)
			//  - Resolve with a navigation to login page if not logged
			return this.request(loginUrl)
				.then((userData) => {
					if (userData.status) {
						this.user = userData.data;
						if (this.isAuthenticated()) {
							// Update permissions if logged
							this.permissions.on([
								'edit', 'save', 'logout'
							]).off('login');

							return this.user;
						} else {
							return false;
						}
					} else {
						if (passive) {
							// Resolve with the user data
							return Promise.resolve(this.user);
						} else {
							// Redirect to login page
							return window.location.href = new URL(userData.data.loginUrl, Mavo.base);
						}
					}
				});
		};

		/**
		 * Log current user out.
		 * @returns {Promise}
		 */
		logout () {
			// new URL() to clone phpFile url to set searchParams
			const loginUrl = new URL(this.phpFile);
			// Say we logout
			loginUrl.searchParams.set('action', 'logout');
			// Logout from what ?
			loginUrl.searchParams.set('id', this.key);
			// Return if PHP unset $_SESSION['user']
			return this.request(loginUrl)
				.then((userData) => {
					this.user = false;
					// Reset permissions
					this.permissions.off([
						'edit', 'add', 'delete', 'save', 'logout'
					]).on('login');
				});
		};

		/**
		 * Check isLogged value in this.user.
		 * @returns {boolean}
		 */
		isAuthenticated () {
			if (typeof this.user.isLogged !== 'undefined') {
				return this.user.isLogged;
			}

			return false;
		};

		static test (value, backendInfo) {
			return backendInfo.type === 'php';
		};
	});
})();
