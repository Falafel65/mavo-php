# mavo-php
PHP adaptor for [Mavo](http://mavo.io/).

Mavo read files. PHP can write files. So I made them work together.

PHP is used to manage login and write to files, as adapters should only do.

The main goal was to not use external data providers like Dropbox or Github, as great as they are, if you have access to your own server.

## Usage
To use it, you'll have to upload the php files, login.html and mavo-users.json to same directory as you Mavo app. 

PHP need to have write access to files and some folders (for uploads).

Set `mv-storage-type` to `php` and `mv-storage` to your favorite file path, as long as it's writable on the server.

_Rumours say that `mv-storage-type` is case insensitive. So go on, write `PHP` in all caps like a maniac._

## Users
In order to login and edit, you'll need to have users. The file which contain them is `mavo-users.json`.

An user is defined by 3 things : a login, a password and a name.

To manage users, for now you'll have to manually edit the json file.
The password is a md5 hashed string, salted with `mavo-`.
If you want to change the salt, or use another method to store password, have a look into `login.php`, line 9


## Example
A navbar in BootStrap 4 :
```
<div id="mainMenu" mv-app="main-menu" mv-storage-type="php" mv-storage="datas/menu.json" class="collapse navbar-collapse justify-content-end">
    <ul class="navbar-nav">
        <li class="nav-item" property="menus" mv-multiple>
            <a class="nav-link" property="url" href="#">
                <span property="title"></span>
            </a>
        </li>
    </ul>
</div>
```
`data/menu.json` file :
```
{
    "menus": [{
        "title": "Entry 1",
        "url": "file.html"
    }, {
        "title": "Entry 2",
        "url": "file2.html"
    }]
}
```

## Disclaimer
As I'm a bit lazy, it's not 104% tested. But it my usage showed that it mostly works.

Any feedbacks or bug reports are appreciated !

_(thanks to @stalcer for #6, @joyously for #7, @GalinhaLX for #12)_
