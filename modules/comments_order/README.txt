--------------------
-- Comments order --
--------------------

This module provides to change comments order (sorting functionality)
in Drupal 8. You can pick comment order (newest first or oldest first)
per node type, editing your comment type field on the "Manage fields"
tab (node type administration page).

Not only in flat, but also in threaded display! If you use threaded
display, you can pick how will able to sort children comments. Examples
below.


-----------
-- Usage --
-----------

Configurable per content type on the "Manage fields" tab, editing your
comment type field.

You can set the "Comments order":
- Oldest first (ascending order)
- Newest first (descending order)

Also you can set or unset "Natural order for children" if you pick
descending order.


------------------
-- How it works --
------------------

If you use threaded comments Drupal 8 have ascending order for comments
by default, like this:

- comment 1
-- comment 1.1
-- comment 1.2
--- comment 1.2.1
--- comment 1.2.2
--- comment 1.2.3
- comment 2
-- comment 2.1
-- comment 2.2

If you change order to descending with "Natural order for children"
by current module, you'll get the following result:

- comment 2
-- comment 2.1
-- comment 2.2
- comment 1
-- comment 1.1
-- comment 1.2
--- comment 1.2.1
--- comment 1.2.2
--- comment 1.2.3

It means you reverse order for parent comments, but use natural order
for children comments. Children comments are sorted like default.

But if you pick descending order without "Natural order for children"
by current module, you'll get:

- comment 2
-- comment 2.2
-- comment 2.1
- comment 1
-- comment 1.2
--- comment 1.2.3
--- comment 1.2.2
--- comment 1.2.1
-- comment 1.1

It means you reverse order for parent comments and reverse order
for children comments.

If you don't use threaded comment lists your comments will be sorted
by date creation (ascending or descending).
