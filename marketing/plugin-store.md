Zen moves selected Craft content between environments with a review step in the middle. Export the elements you changed, inspect how they will map at the destination, and let the queue handle the import instead of copy-and-pasting fields by hand.

Choose entries, categories, and other supported Craft elements on one environment and create a portable export with their field data and relationships. The bundle reflects the content you intend to move rather than an entire database.

## Features

- **Selective exports:** Package the content that needs to move instead of copying a database.
- **Guided imports:** Walk through destination mapping and configuration before applying data.
- **Before-and-after review:** Inspect how incoming values differ from the destination element.
- **Craft elements:** Move entries, categories, and other supported native element types.
- **Structured fields:** Preserve supported field content and relationships during transfer.
- **Queued processing:** Run larger imports through Craft’s background job system.
- **Review before import:** Map sites, sections, fields, and related configuration during import, then compare the existing and incoming element data. That preview gives the operator a chance to resolve differences before work is queued.
- **Complex content preserved:** Zen handles native Craft fields and established structured field types, with provider extension points for additional elements and fields. Background importing keeps a substantial transfer away from the web request.
