# Export
The first step with Zen is to visit the install where you want to export your content _from_. Here, you'll be able to pick the elements you want to export, and the date range to include modified elements from. Once selected, this data will be downloaded as a `.zip` for you to upload when importing on your destination install.

Exports will contain a `.json` file which is a serialized collection of your content for all elements. It'll also contain any local assets for any asset fields or asset elements. These are so that they can be uploaded along with the asset element itself.

## Elements
Most elements are grouped in some form. Entries have sections, Categories have groups, Users have groups, etc. When exporting, you can pick all element "groups", or pick just what you need.

## Date Range
For particularly large installs, it might be helpful to select a date range of content to export, rather than export _everything_. Using the date range controls, this will select only elements whose `dateUpdated` value falls between the two dates.