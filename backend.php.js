Mavo.Backend.register(Bliss.Class({
	extends: Mavo.Backend,
	id: "php",
	constructor: function() {
	    this.permissions.on(["login", "read"]);
		this.key = this.mavo.id;
		this.url = new URL(this.key + '.json', Mavo.base);
		this.phpFile = new URL('mavo-backend.php', Mavo.base);
		
		this.user = false;
		this.login(true);
	},
	
	// Low-level saving code.
	// serialized: Data serialized according to this.format
	// path: Path to store data
	// o: Arbitrary options
	put: function(serialized, path = this.path, o = {}) {
		//new URL() to clone phpFile url
		var postUrl = new URL(this.phpFile);
		postUrl.searchParams.set('id', this.key);
		//Default action to 'putData'
		postUrl.searchParams.set('action', 'putData');
		for (var opt in o) {
		    postUrl.searchParams.set(opt, o[opt]);
		}
		return this.request(postUrl, serialized, 'POST');
	},
    //Src : https://github.com/mavoweb/mavo/blob/master/src/backend.github.js
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
			//Resolve with url to file
			.then(() => { return path });
	},

	//Try to login. If passive, on resolve with user data
	login: function(passive) {
		var loginUrl;
		
		// Returns promise that resolves when the user has successfully authenticated
		if (passive) {
		    return Promise.resolve(this.user);
		} else {
			//new URL() to clone phpFile url
			loginUrl = new URL(this.phpFile);
			//loginUrl.searchParams.set('id', this.key);
			loginUrl.searchParams.set('action', 'login');
			return this.request(loginUrl)
				.then((userData) => {
					if (userData.status) {
						this.user = userData.data;
						if (this.isAuthenticated()) {
							this.permissions.on(["edit", "save", "logout"]).off("login");
							//Picked this from another backend, don't know if it has effect
							this.mavo.element._.fire("mavo:login", { backend: this });
						} else {
							return Promise.resolve(false);
						}
					} else {
						return window.location.href = userData.data.loginUrl;
					}
				});
		}
	},

	// Log current user out
	logout: function() {
		// Returns promise
		this.user = false;
		this.permissions.off(["edit", "add", "delete", "save", "logout"]).on("login");
		//Picked this from another backend, don't know if it has effect
		this.mavo.element._.fire("mavo:logout", { backend: this });
		
		return Promise.resolve(true);
	},
	
	isAuthenticated: function() {
		return (typeof this.user.isLogged !== 'undefined' && this.user.isLogged);
	},

	static: {
		test: function(value) {
		    return (value === 'php');
		}
	}
}));
