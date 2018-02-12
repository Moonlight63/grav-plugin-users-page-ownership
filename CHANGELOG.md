# v0.9.5
## 02/11/18
1. [](#feature)
    * Added a collaboration feature. Pages can now be shared with a specific user, and that user will be able to see that page in the pages view, and will be able to modify it's contents. Possibly useful for co-authoring or just as a debugging feature.
2. [](#bug-fix)
    * Fixed non authorized users from being able to access pages through the "page ordering." I still recommend modifying the page blueprints of page types that should not be reordered to not include the ordering section at all. Any user can still re-order pages or enable/disable numeric folder ordering for a page that they own or have control over.
    * Removes being able to move a page to be the child of a page you do not control. -Thanks noxify!
        

# v0.9.4
## 08/05/17
1. [](#feature)
    * Can now hide pages and child pages/folders from the parent selection menu. (In my case, useful because my site has a section with hundreds of sub-folders for client data and media files, sevearly clutering this dropdown menu. This does not affect visability on the admin pages page (what?), so it is completly non-destructive.)

# v0.9.3
## 08/05/17
1. [](#bug-fix)
    * Hopefully fixed a bug by adding an integrity check.
2. [](#feature)
    * Users and Groups can be limited to specific page types.

# v0.9.2
## 08/03/17
1. [](#bug-fix)
    * Changed parentfilter to use ownerUtils instead of users twig var.
2. [](#improvment)
    * Page filtering uses a repeatable macro.
3. [](#bug-fix)
    * Page delete now properly checks permissions.
4. [](#improvment)
    * Page next/prev buttons will skip over pages that cant be edited.
    
# v0.9.1
## 08/01/17
1. [](#bug-fix)
    * Changed name of the twig var to avoid a conflict with another plugin.
    
# v0.9.0
## 08/01/17
1. [](#new)
    * Changed the whole plugin structure and name.

# v0.8.8
## 07/07/17
1. [](#improved)
    * New change and cosmetic fix to pages.
    
# v0.8.7
## 07/05/17
1. [](#new)
    * First public release.