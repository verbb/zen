# Import
Once you've created your [export](docs:feature-tour/export) and have a `.zip` file ready to go, head to your destination install where you want to import content _to_. Select the export `.zip` and upload it.

## Configure Your Import
Once uploaded, you'll see a configuration screen where you can configure more about your content. You'll see an overview of changes, along with tabs for each element type to review. Each element will feature a table for each element about to be imported. You can opt-out of importing any element by un-ticking the checkbox next to an element.

Each element in the table will show what sort of state this element is, and a summary of the changeset. The state will be either an "Add", "Change", "Delete" or "Restore", depending on whether there is an existing element on the install.

You can also see a preview of the changes for the element. Expand the "Preview" pane, and you'll see a side-by-side view of the existing element, and the soon-to-be imported element.

### Review Your Import
Once you're happy with the import configuration, proceed to the final "Review" step, where you can do a final check before running the import.

### Run the Import
Now, to run your import. Zen will use the Craft Queue to run your import, so it will be processed in the background, so you can do other things while you wait. Be sure to keep the tab open however, just in case any errors are encountered, which will be displayed here.

Your content should now be imported!

After the queue completes, open the imported entry on the destination and compare its title, field values and relations with the source. Open a related asset too, checking both the element and its file. Preview the entry on the destination site. A completed queue job confirms that processing finished; these checks confirm that the transferred content is usable in your project.
