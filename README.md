# Grav Users Page Ownership

## UPDATE:

I have decided to change the direction of this plugin due to the advancements of the other "Admin Users Managment" plugin by david-szabo97. This plugin no longer adds the user managment functionallity, but instead is best used in conjunction with the aforementioned plugin. It now simply adds the page ownership functionallity, useful for blog style implementations of grav on blogs with multiple authors. I will expand on more detailed information in the future. For now, here is a basic use case:


All pages now have a 'creator' frontmatter that can be used in a twig template like so:

~~~~
{{ page.header.creator }}
~~~~

However, this only returns the username of the creator. Fear not! You can use the username to retrive the grave user object of that user with the "ownerUtils" twig variable:

~~~~
{{ ownerUtils.getUser(page.header.creator) }}
~~~~

Other information about this user can be fetched like so:

~~~~
{{ ownerUtils.getUser(page.header.creator).fullname }}
{{ ownerUtils.getUser(page.header.creator).username }}
{{ ownerUtils.getUser(page.header.creator).email }}
{{ ownerUtils.getUser(page.header.creator).title }}
{{ ownerUtils.getUser(page.header.creator).about }}
{{ ownerUtils.getUser(page.header.creator).avatar }}
{{ ownerUtils.getUser(page.header.creator).language }}
~~~~

This information can be used in any twig template. I have included an "Authors Bio" page with the plugin (named 'userpage.html.twig') but you should probably override it for your needs, as I used bootstrap and matched it to my pre-existing website. Accessing this page is easy enough, it works exactly like the 'simple search' plugin, but without the searchbar. Just travel to YOURBLOG.com/authors/query:YOURUSERNAME.
I may add the ability to change this link in the future if it is requested enough. This page holds a collection of all 'item' pages created by the specified user.

You can create links back to this page from eg: a blog post, like so:

~~~~
<strong>Posted By: </strong><a href="{{ home_url }}/authors/query{{ config.system.param_sep }}{{ ownerUtils.getUser(page.header.creator).username }}">{{ ownerUtils.getUser(page.header.creator).fullname }}</a>
~~~~

Users can be restricted to only see pages that they have created. Pages can be set to be gloably viewable and/or editable by a group. Users can only create subpages to pages they can view or edit. Allowing Users to create pages at -ROOT- is optional. Users can be banned from deleting pages.





# OLD VERSION
ok, so I saw that someone released a "users plugin" not too long ago. Im uploading this one anyway because I had been working on it for a while. I could have uploaded it months ago, but I took a hiatus on programming, so I didn't bother to upload knowing that I wouldn't be back to it for a while. If someone ever wanted to take over this one, go for it.

## Basic rundown:

This will allow you to create, view, and edit users. It also adds some page managment functionality. Users can be restricted to only see pages that they have created. Pages all have a "creator" twig header variable. Pages can be set to be gloably viewable and/or editable by a group. Users can only create subpages to pages they can view or edit. Allowing Users to create pages at -ROOT- is optional. Users can be banned from deleting pages.

A simple example of how this is used is a blog with multiple users. A posts page is set to be viewable by all users in an authors group. This allows these users to make subpages under this page. Users are restricted to only seeing there own posts. In your posts template, you can create a "posted by: " line that has the specific authors name.

This plugin (and readme) is not complete, but is in stable working order. I have more things I would like to add, such as managing groups and superadmins "mimicing" other users for troubleshooting errors.