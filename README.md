# mavo-php
PHP adaptor for [Mavo](http://mavo.io/).

Mavo read json. PHP can write json. So I made them work together.

PHP is only used to write to files, and store uploads.

My main goal was to not use external data providers like Dropbox or Github, as great as they are, if you have access to your own server.

## Usage
To use it, PHP need to have write access to files and some folders (for uploads).
MV-APP name should be the same as json file name. So if you have `mv-app="myApp"`, the json file will be `myApp.json`.

## Example
A navbar in BS4 :
```
<div id="mainMenu" mv-app="main-menu" mv-storage="php" class="collapse navbar-collapse justify-content-end">
    <ul class="navbar-nav">
        <li class="nav-item" property="menus" mv-multiple>
            <a class="nav-link" property="url" href="#">
                <span property="title"></span>
            </a>
        </li>
    </ul>
</div>
```

## Disclaimer
As I'm lazy, it's not battle tested. But it mostly works.

Any feedbacks or bug reports are appreciated !
