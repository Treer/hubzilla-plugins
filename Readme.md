# Hubzilla Plugins
[Hubzilla](http://hubzilla.org) is a powerful platform for creating interconnected websites featuring a decentralized identity, communications, and permissions framework built using common webserver technology. 

This is a repository of **plugins** (also known as **addons**) that extend the functionality of the core Hubzilla installation in various ways.

### Installation


To install, use the following commands (assuming `/var/www/` is your hub's web root):

```
cd /var/www/
util/add_addon_repo https://gitlab.com/zot/hubzilla-plugins.git plugins
util/update_addon_repo plugins
```
Then enable the individual plugins through the admin settings interface.

* [Embed Photos](#Embed Photos)
* [Keyboard Shortcuts](#Keyboard Shortcuts)
* [ownMapp](#ownMapp)
* [Hubsites](#Hubsites)


## <a name="Embed Photos"></a>Embed Photos
Adds a button to the post editor that lets you browse album galleries and select linked images to embed in the post.
## <a name="Keyboard Shortcuts"></a>Keyboard Shortcuts
Provides keyboard shortcuts to some Hubzilla pages to aid post navigation and post composition.
## <a name="ownMapp"></a>ownMapp
<img src="map/img/map-plugin-screenshot-1.png" width="500px"/>

[ownMapp](https://grid.reticu.li/page/ownmapp/ownmapp) is an open-source web app for private, self-hosted geolocation services. Originally implemented as an independent web app, current development of the project is in the form of a robust plugin for [Hubzilla](http://hubzilla.org) ([source code](https://github.com/redmatrix/hubzilla)), leveraging the platforms powerful array of decentralized identity services, global access control and communications network.

Current capabilities include 

  * interactive personal location tracking, with ability to share your location securely with your existing contacts or groups of contacts
  * creating static markers that you can share with others
  * integration with Hubzilla calendar events

[Follow project updates and join the discussion on the grid](https://grid.reticu.li/channel/ownmapp).


#### Installation


To install, use the following commands (assuming `/var/www/` is your hub's web root):

```
cd /var/www/
util/add_addon_repo https://gitlab.com/zot/ownmapp.git map
util/update_addon_repo map
```

Then enable the plugin through the admin settings interface.

## <a name="Hubsites"></a>Hubsites
[Hubzilla](https://grid.reticu.li/hubzilla) is a powerful platform for creating interconnected websites featuring a decentralized identity, communications, and permissions framework built using common webserver technology. Hubzilla supports identity-aware webpages, using a modular system of website construction using shareable elements including pages, layouts, menus, and blocks. This plugin defines a schema for defining webpage elements in a typical git repository folder structure for external development of Hubzilla webpages as well as tools to simplify and automate deployment on a hub.

### Git repo folder structure
Element definitions must be stored in the repo root under folders called 

```
/pages/
/blocks/
/layouts/
```

Each element of these types must be defined in an individual subfolder using two files: one JSON-formatted file for the metadata and one plain text file for the element content.

### Page elements
Page element metadata is specified in a JSON-formatted file called `page.json` with the following properties:

 * title
 * pagelink
 * mimetype
 * layout
 * contentfile

Example: 

```
/pages/my-page/page.json
/pages/my-page/my-page.bbcode
```

```
{
    "title": "My Page",
    "pagelink": "mypage",
    "mimetype": "text/bbcode",
    "layout": "my-layout",
    "contentfile": "my-page.bbcode"
}
```

### Layout elements
Layout element metadata is specified in a JSON-formatted file called `layout.json` with the following properties:

 * name
 * description
 * contentfile

Example: 

```
/layouts/my-layout/layout.json
/layouts/my-layout/my-layout.bbcode
```

```
{
    "name": "my-layout",
    "description": "Layout for my project page",
    "contentfile": "my-layout.bbcode"
}
```

### Block elements
Block element metadata is specified in a JSON-formatted file called `block.json` with the following properties:

 * name
 * title
 * mimetype
 * contentfile

Example: 

```
/blocks/my-block/block.json
/blocks/my-block/my-block.html
```

```
{
    "name": "my-block",
    "title": "",
    "mimetype": "text/html",
    "contentfile": "my-block.html"
}
```