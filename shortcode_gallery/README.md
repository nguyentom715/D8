#Shortcode Gallery

This is a submodule of shortcode that
converts related gallery shortcodes to HTML.

All gallery shortcodes require the following attributes:

1. Entity reference to a node.
2. View mode

## Configuration

Configuration is located at /admin/config/shortcode/shortcode_gallery.
1. Entity browser - Choose an entity browser that will allow a user to choose gallery nodes
2. View Modes - Select which view modes should be available to the user when creating gallery shortcodes

## WYSIWYG Usage

1. Add the 'Gallery Shortcode' button to text formats at /admin/config/content/formats.
2. Create or Edit a node. Make sure the WYSIWYG uses the proper text format.
3. Click on the 'Gallery Shortcode' button. An entity brower modal will appear.
4. Select from the available nodes displayed in the entity browser.
5. Click on "Select Gallery" to select the node and close the entity browser modal.
6. Customize the title of the gallery node (optional)
7. Choose the appropriate view mode.
8. The back button will return you to the entity browser modal to re-select the gallery node.
9. Click Embed.
10. The gallery will render as the gallery title in CKEditor.

You can edit the gallery in one of three ways:

1. Double click on the gallery.
2. Right click on the gallery and click "Edit Gallery".
3. Click on the gallery. Click on the Gallery button.

### Example Display

The HTML shortcode looks like this:

    <div class="shortcode"
        data-entity-type="node"
        data-entity-uuid="5c26e837-32fd-43bc-a60c-2b5090d8dd11"
        data-shortcode-id="gallery"
        data-title="Customized Title"
        data-view-mode="related">
        <span>&nbsp;</span>
    </div>
