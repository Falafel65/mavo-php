Mavo.Backend.register(Bliss.Class({
	extends: Mavo.Backend,
	id: "php",
	constructor: function() {
		this.permissions.on(["login", "read"]);
		this.key = this.mavo.id;
		this.phpFile = new URL('mavo-backend.php', Mavo.base);
		
		this.user = false;
		this.login(true);
	},
	
	// Low-level saving code.
	// serialized: Data serialized according to this.format
	// path: Path to store data
	// o: Arbitrary options
	put: function(serialized, path = this.path, o = {}) {
		// new URL() to clone phpFile url
		var postUrl = new URL(this.phpFile);
		// Send appID
		postUrl.searchParams.set('id', this.key);
		// Send filename
		postUrl.searchParams.set('source', this.source);
		// Default action to 'putData'
		postUrl.searchParams.set('action', 'putData');
		// Add all the arbitrary things
		for (var opt in o) {
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
	},
    // Src : https://github.com/mavoweb/mavo/blob/master/src/backend.github.js
	upload: function(file, path = this.path) {
		return Mavo.readFile(file)
			.then(dataURL => {
				var base64 = dataURL.slice(5); // remove data:
				var media = base64.match(/^\w+\/[\w+]+/)[0];
				base64 = base64.replace(RegExp(`^${media}(;base64)?,`), "");
				
				return this.put(base64, path, {
					action: 'putFile',
					file: file.name,
					path: path
				});
			})
			//Resolve with file name on server
			.then((fileData) => fileData.data.file);
	},

	// Try to login. If passive, resolve with user data if set
	login: function(passive) {
		// Returns promise that resolves when the user has successfully authenticated
		if (passive) {
		    return Promise.resolve(this.user);
		} else {
			// new URL() to clone phpFile url
			let loginUrl = new URL(this.phpFile);
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
							this.permissions.on(["edit", "save", "logout"]).off("login");
							//Picked this from another backend, don't know if it has effect
							this.mavo.element._.fire("mv-login", { backend: this });
							
							return this.user;
						} else {
							return false;
						}
					} else {
						return window.location.href = new URL(userData.data.loginUrl, Mavo.base);
					}
				});
		}
	},

	// Log current user out
	logout: function() {
		let loginUrl = new URL(this.phpFile);
		// Say we logout
		loginUrl.searchParams.set('action', 'logout');
		// Logout from what ?
		loginUrl.searchParams.set('id', this.key);
		// Return if PHP unset $_SESSION['user']
		return this.request(loginUrl)
			.then((userData) => {
				this.user = false;
				this.permissions.off(["edit", "add", "delete", "save", "logout"]).on("login");
				//Picked this from another backend, don't know if it has effect
				this.mavo.element._.fire("mv-logout", { backend: this });
			});
	},
	
	// Check isLogged value in this.user
	isAuthenticated: function() {
		return (typeof this.user.isLogged !== 'undefined' && this.user.isLogged);
	},

	static: {
		test: value => value == "php"
	}
}));
