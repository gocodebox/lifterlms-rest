LifterLMS REST API Changelog
============================

v1.0.0-beta.8 - 2019-10-24
--------------------------

+ Return links to those taxonomies which have an accessible rest route.
+ Initialize `$prepared_item` array before adding values to it. Thanks [@pondermatic](https://github.com/pondermatic)!
+ Fixed `sales_page_type` not returned as `none` if course's `sales_page_content_type` property is empty.
+ Load webhook actions a little bit later, to avoid PHP warnings on first plugin activation.
+ Renamed `sales_page_page_type` and `sales_page_page_url` properties, respectively to `sales_page_type` and `sales_page_url` according to the specs.
+ Add missing quotes in enrollment/access default messages shortcodes.
+ Call `set_bulk()` llms post method passing `true` as second parameter, so to instruct it to return a WP_Error on failure.
+ Add missing quotes in enrollment/access default messages shortcodes.
+ `sales_page_page_id` and `sales_page_url` always returned in edit context.
+ Call `set_bulk()` llms post method passing `true` as second parameter, so to instruct it to return a WP_Error on failure.


v1.0.0-beta.7 - 2019-09-27
--------------------------

##### Updates

+ Added the following properties to the lesson schema and response object: `drip_date`, `drip_days`, `drip_method`, `public`, `quiz`.
+ Added the following links to lesson objects: `prerequisite` and `quiz`.
+ Use `WP_Error::$errors` in place of `WP_Error::has_errors()` to support WordPress version prior to 5.1.
+ Added `llms_rest_lesson_item_schema`, `llms_rest_pre_insert_lesson`, `llms_rest_prepare_lesson_object_response`, `llms_rest_lesson_links` filter hooks.
+ Course properties `access_opens_date`, `access_closes_date`, `enrollment_opens_date`, `enrollment_closes_date` are now nullable.
+ Course properties `prerequisite` and `prerequisite_track` can be be cleared (set to `0` to signify no prerequisite exists).
+ Added prerequisite validation for courses, if `prerequisite` is not a valid course the course `prerequisite` will be set to `0` and if `prerequisite_track` is not a valid course track, the course `prerequisite_track` will be set to `0`.

##### Bug Fixes

+ Fixed lesson `siblings` link that was using the parent course's id instead of the parent section's id.
+ Fixed lesson `parent` link href, replacing 'section' with 'sections'.
+ Fixed lesson progression callback name when defining the filters to be removed while preparing the item for response.
+ Fixed description of the `post_id` path parameter for student enrollments resources. Thanks [@pondermatic](https://github.com/pondermatic).
+ Fixed section parent course object retrieval method when building the resource links.


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
