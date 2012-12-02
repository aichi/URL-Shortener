# URL Shortener #

I made URL Shortener because I want own URL shortener for CZECHDESIGN.CZ site with own click statistics. This software uses bit.ly API to hold statistics but it usese own database for storing shorten version and original URLs. So it is possible to switch to another provider as easy as implement new URL shortener provider API.

URL Shortener has its own PHP/JS administration. In this version you could create new link and show list of all links. Because all frontend is managed by Javascript only I use [history.js](https://github.com/balupton/history.js) to handle browser back button. Second foreign library is [Proto.js](https://github.com/rauschma/proto-js) which helps me create class hiearchy.

Whole JS and PHP code is modular so you can easily introduce e.g. your own login manager which uses your database with credentials.

-------------------------------

(c) Michal Aichinger ([www.czechdesign.cz/blogs/aichi](http://www.czechdesign.cz/blogs/aichi)). Published under [MIT licence](http://www.opensource.org/licenses/mit-license.php).

