LifterLMS REST API Changelog
============================

v1.0.0-beta.6 - 2019-08-27
--------------------------

+ Fix issue causing certain webhooks to not trigger as a result of action load order.
+ Change "access_plans" to "Access Plans" for better human reading.


v1.0.0-beta.5 - 2019-08-22
--------------------------

+ Load all required files and functions when authentication is triggered.
+ Access `$_SERVER` variables via `filter_var` instead of `llms_filter_input` to work around PHP bug https://bugs.php.net/bug.php?id=49184.


v1.0.0-beta.4 - 2019-08-21
--------------------------

+ Load authentication handlers as early as possible. Fixes conflicts with numerous plugins which load user information earlier than expected by the WordPress core.
+ Harden permissions associated with viewing student enrollment information.
+ Returns a 400 Bad Request when invalid dates are supplied.
+ Student Enrollment objects return student and post id's as integers instead of strings.
+ Fixed references to an undefined function.


v1.0.0-beta.3 - 2019-08-19
--------------------------

##### Interface and Experience improvements during API Key creation

+ Better expose that API Keys are never shown again after the initial creation.
+ Allow downloading of API Credentials as a `.txt` file.
+ Add `required` properties to required fields.

##### Updates

+ Added the ability to CRUD webhooks via the REST API.
+ Conditionally throw `_doing_it_wrong` on server controller stubs.
+ Improve performance by returning early when errors are encountered for various methods.
+ Utilizes a new custom property `show_in_llms_rest` to determine if taxonomies should be displayed in the LifterLMS REST API.
+ On the webhooks table the "Delivery URL" is trimmed to 40 characters to improve table readability.

##### Bug fixes

+ Fixed a formatting error when creating webhooks with the default auto-generated webhook name.
+ On the webhooks table a translatable string is output for the status instead of the database value.
+ Fix an issue causing the "Last" page pagination link to display for lists with 0 possible results.
+ Don't output the "Last" page pagination link on the last page.


v1.0.0-beta.2 - 2019-08-15
--------------------------

+ Filter course taxonomies by the `public` property instead of the `show_in_rest` property.
+ Fixed bug preventing async webhooks from being delivered properly.
+ Only load the main plugin function when loading the main plugin file. Fixes issue when running plugin alongside LifterLMS core with bundled API.


v1.0.0-beta.1 - 2019-08-15
--------------------------

+ Initial public beta release.
