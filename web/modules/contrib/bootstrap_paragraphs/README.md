# Bootstrap Paragraphs
A suite of Paragraph bundles made with the Boostrap framework.

For content creators, attempts to use wysiwyg editors to create structured
layouts typically lead to frustration and compromise. With this module you can
easily position chunks of content (Paragraph bundles) within a structured
layout of your own design.

This suite of [Paragraphs](https://www.drupal.org/project/paragraphs) bundles
works within the [Bootstrap](http://getbootstrap.com) framework.

This module is built on the premise that all good things in Drupal 8 are
entities, and we can use Paragraphs and Reference fields to allow our content
creators to harness the power of the Bootstrap framework for functionality
and layout.

**Bundle Types:**

  * Simple HTML
  * Image
  * Blank
  * Accordion
  * Carousel
  * Columns (Equal, up to 6)
  * Columns (Three Uneven)
  * Columns (Two Uneven)
  * Contact Form
  * Drupal Block
  * Modal
  * Tabs
  * View

**Backgrounds:**

Each Paragraph has width and background color options. Included are over 50
background colors and five empty background classes for you to customize in
your own theme.

**Widths:**

  * Tiny - col-4, offset-4
  * Narrow - col-6, offset-3
  * Medium - col-8, offset-2
  * Wide - col-10, offset-1
  * Full - col-12

**Installation:**

  * Install the module as you normally would.
  * Verify installation by visiting /admin/structure/paragraphs_type and seeing
  your new Paragraph bundles.
  * On the Simple and Blank bundles, click Manage fields and choose which Text
  formats to use.  We recommend a *Full HTML* for the Simple, and a
  *Full HTML - No Editor* for the Blank.
  * Go to your content type and add a new field to type Entity revisions,
  Paragraphs.
  * Allow unlimited so creators can add more that one Paragraph to the node.
  * On the field edit screen, you can add instructions, and choose which
  bundles you want to allow for this field. Check all but Accordion Section and
  Tab Section. Those should only be used inside Accordions and Tabs.
  * Arrange them as you see fit. I prefer Simple, Image, and Blank at the top,
  then the rest in Alphabetical order. Click Save Settings.
  * Adjust your form display, placing the field where you want it.
  * Add the field into the Manage display tab.
  * Start creating content!

**Requirements:**

  * Contact
  * [Contact Formatter](https://www.drupal.org/project/contact_formatter)
  * [Entity Reference Revisions](
  https://www.drupal.org/project/entity_reference_revisions)
  * Field
  * File
  * Filter
  * Image
  * Options
  * [Paragraphs](https://www.drupal.org/project/paragraphs)
  * System
  * Text
  * User
  * Views
  * [Views Reference Field](https://www.drupal.org/project/viewsreference)
  * Bootstrap framework's CSS and JS included in your theme

**Supporting Organizations:**

  * [Xeno Media, Inc.](http://www.xenomedia.com)
  * [Zoomdata, Inc.](http://www.zoomdata.com)
