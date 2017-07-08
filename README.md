# Grav Users Admin Managment Plugin

ok, so I saw that someone released a "users plugin" not too long ago. Im uploading this one anyway because I had been working on it for a while. I could have uploaded it months ago, but I took a hiatus on programming, so I didn't bother to upload knowing that I wouldn't be back to it for a while. If someone ever wanted to take over this one, go for it.

## Basic rundown:

This will allow you to create, view, and edit users. It also adds some page managment functionality. Users can be restricted to only see pages that they have created. Pages all have a "creator" twig header variable. Pages can be set to be gloably viewable and/or editable by a group. Users can only create subpages to pages they can view or edit. Allowing Users to create pages at -ROOT- is optional. Users can be banned from deleting pages.

A simple example of how this is used is a blog with multiple users. A posts page is set to be viewable by all users in an authors group. This allows these users to make subpages under this page. Users are restricted to only seeing there own posts. In your posts template, you can create a "posted by: " line that has the specific authors name.

This plugin (and readme) is not complete, but is in stable working order. I have more things I would like to add, such as managing groups and superadmins "mimicing" other users for troubleshooting errors.