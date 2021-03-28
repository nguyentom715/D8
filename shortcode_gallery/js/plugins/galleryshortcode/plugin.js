/**
 * @file
 * Drupal Entity embed plugin.
 */

(function ($, Drupal, CKEDITOR) {

  "use strict";

  CKEDITOR.plugins.add('galleryshortcode', {
    // This plugin requires the Widgets System defined in the 'widget' plugin.
    requires: 'widget',

    // The plugin initialization logic goes inside this method.
    beforeInit: function (editor) {
      // Generic command for adding/editing entities of all types.
      editor.addCommand('editgalleryshortcode', {
        allowedContent: 'div[data-entity-type,data-entity-uuid,data-shortcode-id,data-view-mode](!shortcode)',
        requiredContent: 'div[data-entity-type,data-entity-uuid,data-shortcode-id,data-view-mode](!shortcode)',
        modes: { wysiwyg : 1 },
        canUndo: true,
        exec: function (editor, data) {
          data = data || {};

          var existingElement = getSelectedEmbeddedEntity(editor);

          var existingValues = {};
          if (existingElement && existingElement.$ && existingElement.$.firstChild) {
            var embedDOMElement = existingElement.$.firstChild;
            // Populate array with the entity's current attributes.
            var attribute = null;
            var attributeName;
            for (var key = 0; key < embedDOMElement.attributes.length; key++) {
              attribute = embedDOMElement.attributes.item(key);
              attributeName = attribute.nodeName.toLowerCase();
              if (attributeName.substring(0, 15) === 'data-cke-saved-') {
                continue;
              }
              existingValues[attributeName] = existingElement.data('cke-saved-' + attributeName) || attribute.nodeValue;
            }
          }

          var dialogSettings = {
            dialogClass: 'entity-select-dialog',
            resizable: false
          };

          var saveCallback = function (values) {
            var entityElement = editor.document.createElement('div');
            entityElement.addClass('shortcode');
            var attributes = values.attributes;
            for (var key in attributes) {
              entityElement.setAttribute(key, attributes[key]);
            }

            editor.insertHtml(entityElement.getOuterHtml());
            if (existingElement) {
              // Detach the behaviors that were attached when the entity content
              // was inserted.
              Drupal.runShortcodeEmbedBehaviors('detach', existingElement.$);
              existingElement.remove();
            }
          };

          // Open the entity embed dialog for corresponding EmbedButton.
          Drupal.ckeditor.openDialog(editor, Drupal.url('shortcode-gallery/dialog/' + editor.config.drupal.format), existingValues, saveCallback, dialogSettings);
        }
      });

      // Register the entity embed widget.
      editor.widgets.add('galleryshortcode', {
        // Minimum HTML which is required by this widget to work.
        allowedContent: 'div[data-entity-type,data-entity-uuid,data-shortcode-id,data-view-mode](!shortcode)',
        requiredContent: 'div[data-entity-type,data-entity-uuid,data-shortcode-id,data-view-mode](!shortcode)',

        // Simply recognize the element as our own. The inner markup if fetched
        // and inserted the init() callback, since it requires the actual DOM
        // element.
        upcast: function (element) {
          var attributes = element.attributes;
          if (attributes['data-shortcode-id'] !== 'gallery') {
            return;
          }

          if (
            !attributes['data-entity-type'] ||
            (!attributes['data-entity-id'] && !attributes['data-entity-uuid']) ||
            (!attributes['data-view-mode'])) {
            return;
          }
          // Generate an ID for the element, so that we can use the Ajax
          // framework.
          element.attributes.id = generateEmbedId();
          return element;
        },

        // Fetch the rendered entity.
        init: function () {
          /** @type {CKEDITOR.dom.element} */
          var element = this.element;
          // Use the Ajax framework to fetch the HTML, so that we can retrieve
          // out-of-band assets (JS, CSS...).
          var entityEmbedPreview = Drupal.ajax({
            base: element.getId(),
            element: element.$,
            url: Drupal.url('shortcode-gallery/preview/' + editor.config.drupal.format + '?' + $.param({
                value: element.getOuterHtml()
              })),
            progress: {type: 'none'},
            // Use a custom event to trigger the call.
            event: 'gallery_shortcode_dummy_event'
          });
          entityEmbedPreview.execute();
        },

        // Downcast the element.
        downcast: function (element) {
          // Only keep the wrapping element. Must place an HTML tag
          // into the element so it doesn't disappear when clicking 'Source'
          element.setHtml('<span>&nbsp;</span>');
          element.attributes['data-shortcode-id'] = 'gallery';
          // Remove the auto-generated ID.
          delete element.attributes.id;
          return element;
        }
      });

      // Register the toolbar buttons.
      if (editor.ui.addButton) {
        editor.ui.addButton('Galleryshortcode', {
          label: 'Embed Gallery',
          click: function(editor) {
            editor.execCommand('editgalleryshortcode', this.data);
          },
          icon: this.path + 'icons/galleryshortcode.png'
        });
      }

      // Register context menu option for editing widget.
      if (editor.contextMenu) {
        editor.addMenuGroup('Galleryshortcode');
        editor.addMenuItem('Galleryshortcode', {
          label: Drupal.t('Edit Gallery'),
          command: 'editgalleryshortcode',
          group: 'Galleryshortcode'
        });

        editor.contextMenu.addListener(function(element) {
          if (isEditableEntityWidget(editor, element)) {
            return {Galleryshortcode: CKEDITOR.TRISTATE_OFF};
          }
        });
      }

      // Execute widget editing action on double click.
      editor.on('doubleclick', function (evt) {
        var element = getSelectedEmbeddedEntity(editor) || evt.data.element;

        if (isEditableEntityWidget(editor, element)) {
          editor.execCommand('editgalleryshortcode');
        }
      });
    }
  });

  /**
   * Get the surrounding galleryshortcode widget element.
   *
   * @param {CKEDITOR.editor} editor
   */
  function getSelectedEmbeddedEntity(editor) {
    var selection = editor.getSelection();
    var selectedElement = selection.getSelectedElement();
    if (isEditableEntityWidget(editor, selectedElement)) {
      return selectedElement;
    }

    return null;
  }

  /**
   * Checks if the given element is an editable galleryshortcode widget.
   *
   * @param {CKEDITOR.editor} editor
   * @param {CKEDITOR.htmlParser.element} element
   */
  function isEditableEntityWidget (editor, element) {
    var widget = editor.widgets.getByElement(element, true);
    if (!widget || widget.name !== 'galleryshortcode') {
      return false;
    }

    return true;
  }

  /**
   * Generates unique HTML IDs for the widgets.
   *
   * @returns {string}
   */
  function generateEmbedId() {
    if (typeof generateEmbedId.counter == 'undefined') {
      generateEmbedId.counter = 0;
    }
    return 'gallery-embed-' + generateEmbedId.counter++;
  }

})(jQuery, Drupal, CKEDITOR);
