# Elements
The vast majority of content on your site sits within an element. This could be an Entry, Category or even a Global Set. Because this content is stored in the database, Zen makes it easy to migrate content around.

Zen is primarily geared towards synchronising content between the same project for multiple environments. That means you can populate entries locally, and have them imported into your staging and production environments. 

You _can_ use Zen to import content into other projects, but that's not it's primary purpose. You will need to ensure that the source and destination elements have the same fields for example.

## How it works
Exporting elements is done by creating a serialized copy of an element. This includes custom field values, element attributes which is stored via JSON. When importing this content, the same serialization process is done on the destination install, and the difference between the two chunks of data is compared.

Zen uses UIDs to compare content across multiple environments. That means that just because you have an entry with the title "About Us" on your local environment, and a similar entry on your production enviroment, doesn't mean the two are synced. This is by design, as entries can have the same title but different content. As such, the UID is used as the unique identifier for an element, which stays true to the idea of "syncing" content across environments.

## Supported Elements
Zen supports all native Craft elements, along with a few other common ones.

- Category
- Entry
- Global Set
- Tag
- Users
- Commerce Product
- Commerce Variant

:::tip
Looking to add support for other elements? Consult the [Element Type](docs:developers/element-type) docs.
:::