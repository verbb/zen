# Export
The first step with Zen is to visit the install where you want to export your content _from_. Here, you'll be able to pick the elements you want to export, and the date range to include modified elements from. Once selected, this data will be downloaded as a `.zip` for you to upload when importing on your destination install.

Exports will contain a `.json` file which is a serialized collection of your content for all elements. It'll also contain any local assets for any asset fields or asset elements. These are so that they can be uploaded along with the asset element itself.

## Elements
Most elements are grouped in some form. Entries have sections, Categories have groups, Users have groups, etc. When exporting, you can pick all element "groups", or pick just what you need.

## Date Range
For particularly large installs, it might be helpful to select a date range of content to export, rather than export _everything_. Using the date range controls, this will select only elements whose `dateUpdated` value falls between the two dates.

## Start with a Small Round Trip

Use related installations of the same Craft project with matching project configuration. Zen identifies content and its related configuration using identifiers such as UIDs; it is not a field-layout migration tool for unrelated sites. Ensure the destination has the required sections, fields, sites and plugins before exporting content.

For a first test, choose one recognisable entry and its related asset content. Export the ZIP, then [review the import](docs:feature-tour/import) on the destination. This makes it easier to check field values, relations and files before transferring a larger selection.
