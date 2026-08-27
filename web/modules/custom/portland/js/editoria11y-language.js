// Our language overrides for Editoria11y
const PORTLAND_STRINGS = {
  panelHelp: `
      <p>
        Editoria11y checks for common accessibility needs, such as image alternative text, meaningful heading outlines, and well-named links. It doesn't check proofreading, accessibility, or usability.
      </p>
      <p>
        Some issues are flagged with a red exclamation point and will require a fix. Other issues are flagged with a yellow question mark and need your interpretation. For these yellow issues, use the "Mark as OK" button only after you verify an issue's accessibility. This will hide the alert for all editors.
      </p>
      <p>
        For embedded content, like a document or video, use its <a href="https://employees.portland.gov/web-support/portlandgov-editor-resources/accessibility-checker#toc-meets-accessibility-requirements-checkbox" target="_blank" title="Opens in new tab">meets accessibility checkbox</a> so it passes Editoria11y review whenever it's embedded on the site.
      </p>
      <p>
        For an overview of accessibility issues relevant to Portland.gov, see our <a href="https://employees.portland.gov/digital-accessibility/accessibility-checklist" target="_blank" title="Opens in new tab">accessibility checklist for web editors</a>.
      </p>
      <p>
        You can report bugs and request support by emailing <a href="mailto:website@portlandoregon.gov">website@portlandoregon.gov</a>.
      </p>
    `,

  headingLevelSkipped: {
    title: "Missing heading level.",
    tip: (prevLevel, level) =>
      `<p>To fix: This heading skipped from level ${prevLevel} to level ${level}. For screen readers, it will seem like content is missing. Adjust levels to form an accurate outline without gaps.</p>
          <p>See the City Web Editors documentation for <a href="https://employees.portland.gov/web-support/portlandgov-editor-resources/rich-text-editor#toc-headings" target="_blank" title="Opens in new tab">more on creating headings</a>.</p>
          <p>Heading levels create a <a href="https://www.w3.org/WAI/tutorials/page-structure/headings/" target="_blank" title="Opens in new tab">table of contents and page structure</a> for assistive devices:</p>
          ${Ed11y.M.headingExample}
          `,
  },

  headingEmpty: {
    title: "Heading tags with no text.",
    tip: () =>
      `<p>To fix:</p>
          <ul>
            <li>If heading tags aren't needed, remove them.</li>
            <li>If heading tags are needed, add text to the heading.</li>
          </ul>
          <p>Heading levels create a <a href="https://www.w3.org/WAI/tutorials/page-structure/headings/" target="_blank" title="Opens in new tab">table of contents and page structure</a> for assistive devices. Use heading levels in order to show the page structure:</p>
          ${Ed11y.M.headingExample}
          <p>Empty headings create confusing gaps in this outline.</p>
          `,
  },

  headingIsLong: {
    title: "This heading might be too long.",
    tip: () =>
      `<p>To fix: Shorten this heading if possible, or remove the heading style if it's only for visual emphasis.</p>
          <p>See the City Web Editors documentation for <a href="https://employees.portland.gov/web-support/portlandgov-editor-resources/rich-text-editor#toc-headings" target="_blank" title="Opens in new tab">more on creating headings</a>.</p>
          <p>Headings should be brief and clear. Heading levels create a <a href="https://www.w3.org/WAI/tutorials/page-structure/headings/" target="_blank" title="Opens in new tab">table of contents and page structure</a> for assistive devices:</p>
          ${Ed11y.M.headingExample}
          `,
  },

  blockquoteIsShort: {
    title: "Is this really a blockquote?",
    tip: () =>
      `<p>Screen readers will announce this as a quotation. Since this text is short, verify that it's a quote and not a heading. If it's a heading, use heading formatting so it appears in the page outline.</p>
          <p>See the City Web Editors documentation for <a href="https://employees.portland.gov/web-support/best-practices-accessibility/how-does-html-work" target="_blank" title="Opens in new tab">more on headings and blockquotes</a>.</p>
          `,
  },

  // Tooltips for image tests =========================

  altMissing: {
    title: "This image has no alt text.",
    tip: () =>
      `<p>To fix: Add alt text that describes the meaning of the image as you would to someone over the phone. If this image is purely for decoration, <a href="https://employees.portland.gov/web-support/portlandgov-editor-resources/media-types-overview/images#toc-decorative-images" target="_blank" title="Opens in new tab">mark it as decorative</a> so screen readers will skip it.</p>
          <p>See the City Web Editors documentation for <a href="https://employees.portland.gov/web-support/best-practices-accessibility/writing-effective-alt-text" target="_blank" title="Opens in new tab">more resources on effective alt text</a>.</p>
          `,
  },

  altNull: {
    title: "This image has no alt text.",
    tip: () =>
      `<p>To fix: Add alt text that describes the meaning of the image as you would to someone over the phone. If this image is purely for decoration, <a href="https://employees.portland.gov/web-support/portlandgov-editor-resources/media-types-overview/images#toc-decorative-images" target="_blank" title="Opens in new tab">mark it as decorative</a> so screen readers will skip it.</p>
          <p>See the City Web Editors documentation for <a href="https://employees.portland.gov/web-support/best-practices-accessibility/writing-effective-alt-text" target="_blank" title="Opens in new tab">more resources on effective alt text</a>.</p>
          `,
  },

  altURL: {
    title: "The image alt text is a URL.",
    tip: (alt) =>
      `<p>To fix: Revise the alt text, describing the meaning of the image as you would to someone over the phone. See the City Web Editors documentation for <a href="https://employees.portland.gov/web-support/portlandgov-editor-resources/media-types-overview/images#toc-alt-text" target="_blank" title="Opens in new tab">more on alt text</a>.</p>
          <p>Right now, the alt text is <em>"${alt}"</em>.</p>
          `,
  },

  altLinkedExample: (alt) =>
    `<p>To fix: Change the alt text to convey the content of the image <strong>and</strong> the function of the link.</p>
        <p>Link text <a href="https://webaim.org/techniques/hypertext/link_text" target="_blank" title="Opens in new tab">should be meaningful and concise</a>. When a link is wrapped around an image and there is no other text, the <a href="https://webaim.org/techniques/hypertext/link_text#alt_link" target="_blank" title="Opens in new tab">image's alt text becomes the link text</a> announced by screen readers. </p>
        <p>See the City Web Editors documentation for <a href="https://employees.portland.gov/web-support/portlandgov-editor-resources/rich-text-editor#toc-add-accessible-links-and-bookmarks" target="_blank" title="Opens in new tab">more on creating links</a>.</p>
        ${alt ? `<p>The image's current alt text is: <em>"${alt}"</em></p>` : ""}
        `,

  altMeaningless: {
    title: "This image has no meaningful alt text.",
    tip: (alt) =>
      `<p>To fix: Add alt text that describes the meaning of the image as you would to someone over the phone. If this image is purely for decoration, <a href="https://employees.portland.gov/web-support/portlandgov-editor-resources/media-types-overview/images#toc-decorative-images" target="_blank" title="Opens in new tab">mark it as decorative</a> so screen readers will skip it.</p>
          <p>See the City Web Editors documentation for <a href="https://employees.portland.gov/web-support/best-practices-accessibility/writing-effective-alt-text" target="_blank" title="Opens in new tab">more resources on effective alt text</a>.</p>
          <p>The image's current alt text is: <em>"${alt}"</em></p>
          `,
  },
  altMeaninglessLinked: {
    title: "This linked image has no meaningful alt text.",
    tip: (alt) => Ed11y.M.altLinkedExample(alt),
  },

  altURLLinked: {
    title: "Linked image has a URL in the alt text.",
    tip: (alt) => Ed11y.M.altLinkedExample(alt),
  },

  altImageOf: {
    title: "Redundant description in alt text.",
    tip: (alt) =>
      `<p>To fix: Describe only what the image contains. You don't need to include "image," "photo," or "graphic" unless the image contains an actual photo.</p>
          <p>Screen readers announce that they're describing an image when reading alt text, so words like those are redundant. </p>
          <p>See the City Web Editors documentation for <a href="https://employees.portland.gov/web-support/best-practices-accessibility/writing-effective-alt-text" target="_blank" title="Opens in new tab">more on writing effective alt text</a>.</p>
          <p>The image's current alt text is: <em>"${alt}"</em></p>
          `,
  },
  altImageOfLinked: {
    title: "Redundant description in linked image.",
    tip: (alt) => Ed11y.M.altLinkedExample(alt),
  },

  altDeadspace: {
    title: "Image's text alternative is unpronounceable",
    tip: (alt) =>
      `<p>This image's alt text is "${alt}," which only contains unpronounceable symbols and/or spaces. Screen readers will announce that an image is present, and then pause awkwardly: "image: ____."</p>
      <p><strong>To fix:</strong> add a descriptive alt, or provide a <em>completely</em> empty alt (alt="") if this is just an icon or spacer, and screen readers should ignore it.</p>
          ${Ed11y.M.altAttributeExample}`,
  },

  altEmptyLinked: {
    title: "Linked image has no alt text.",
    tip: (alt) => Ed11y.M.altLinkedExample(alt),
  },

  altLong: {
    title: "This image's alt text is too long.",
    tip: (alt) =>
      `<p>To fix: Shorten the alt text. To write effective alt text, describe the meaning of the image as you would to someone over the phone. </p>
          <p>If the image is especially complex, such as a chart or map, create <a href="https://www.w3.org/WAI/tutorials/images/complex/" target="_blank" title="Opens in new tab">a text alternative for the image</a> by describing it elsewhere on the page and referencing it in the alt text. </p>
          <p>For example:</p>
          <ul>
            <li>"Event poster. Details follow in caption."</li>
            <li>"Chart showing our issues going to zero. Details follow in table."</li>
          </ul>
          <p>See the City Web Editors documentation for <a href="https://employees.portland.gov/web-support/best-practices-accessibility/writing-effective-alt-text" target="_blank" title="Opens in new tab">more on writing effective alt text</a>.</p>
          <p>This image's alt text is: <em>"${alt}"</em></p>
          `,
  },

  altLongLinked: {
    title: "The alt text on this linked image is too long.",
    tip: (alt) =>
      `<p>To fix: Shorten the alt text. Since the image is a link, the alt text needs to describe the destination of the link. </p>
          <p>For more details, see <a href="https://webaim.org/techniques/alttext/#functional" target="_blank" title="Opens in new tab">WebAIM on functional images</a>.</p>
          <p>The current alt text is <em>"${alt}"</em>.</p>
          `,
  },

  altPartOfLinkWithText: {
    title: "This link contains both text and an image.",
    tip: (alt) =>
      `<p>To fix: Check that the combined link text and image alt text are concise and meaningful. Screen readers will include the alt text in the link description. If this image is purely for decoration, <a href="https://employees.portland.gov/web-support/portlandgov-editor-resources/media-types-overview/images#toc-decorative-images" target="_blank" title="Opens in new tab">mark it as decorative</a> so screen readers will skip it.</p>
          <p>For more details, see <a href="https://webaim.org/techniques/alttext/#functional" target="_blank" title="Opens in new tab">WebAIM on functional images</a> and the City Web Editors documentation for <a href="https://employees.portland.gov/web-support/best-practices-accessibility/writing-effective-alt-text" target="_blank" title="Opens in new tab">more resources on effective alt text</a>.</p>
          <p>The current alt text is <em>"${alt}"</em>.</p>
          `,
  },

  linkNoText: {
    title: "This link has no accessible text.",
    tip: () =>
      `<p>To fix: </p>
          <ul>
            <li>If this is a typo, fix it. This error shows if an empty space has a link.</li>
            <li>If it's an intentional link, add text to describe where the link goes.</li>
          </ul>
          <p>Without these fixes, screen readers will either pause or read the full URL to the user.</p>
          `,
  },

  linkTextIsURL: {
    title: "Is this link text a URL?",
    tip: (text) =>
      `<p>To fix: Add the hyperlink to text that describes the link destination. </p>
          <p>Link text <a href="https://webaim.org/techniques/hypertext/link_text" target="_blank" title="Opens in new tab">should be meaningful and concise</a>. On its own, a linked URL doesn't give enough information about the link's purpose. URLs are usually pronounced by the screen reader one letter at a time.</p>
          <p>See the City Web Editors documentation for <a href="https://employees.portland.gov/web-support/portlandgov-editor-resources/rich-text-editor#toc-add-accessible-links-and-bookmarks" target="_blank" title="Opens in new tab">more on creating links</a>.</p>
          <p>The link's text is <em>"${text}"</em>.</p>
          `,
  },

  linkTextIsGeneric: {
    title: "Is this link meaningful and concise?",
    tip: (text) =>
      `<p>To fix: Change the linked text to make the link's purpose clear to the reader. The link's text is ${text}.</p>
          <p>Generic links like "click here," "read more," or "download" expect the reader to figure out the link's purpose from context.</p>
          <ul>
            <li>Ideal: "Learn about <a href="https://webaim.org/techniques/hypertext/link_text" target="_blank" title="Opens in new tab">meaningful links</a>"</li>
            <li>Not meaningful: "Click <a href="https://webaim.org/techniques/hypertext/link_text" target="_blank" title="Opens in new tab">here</a> to learn about meaningful links."</li>
            <li>Not concise: "<a href="https://webaim.org/techniques/hypertext/link_text" target="_blank" title="Opens in new tab">Click here to learn more about meaningful links</a>"</li>
          </ul>
          `,
  },

  linkDocument: {
    title: "Check this document for accessibility.",
    tip: () =>
      `<p>PDFs and other documents are hard to use on mobile devices and with screen readers. Whenever possible, put content on a web page instead of in a downloadable file. </p>
          <p>If you must upload a document, you likely need to <a href="https://employees.portland.gov/digital-accessibility" target="_blank" title="Opens in new tab">manually check that it's well-structured</a> with headings, lists, table headers, and alt text for images.</p>
          <p>Check the <a href="https://employees.portland.gov/web-support/portlandgov-editor-resources/accessibility-checker#toc-meets-accessibility-requirements-checkbox" target="_blank" title="Opens in new tab">meets accessibility requirements checkbox</a> once you've verified the document. This will prevent the error whenever the document appears on the site.</p>
          <p>Some documents posted before the DOJ compliance deadline may be exempt from accessibility requirements if the public doesn't use them to access City services. Read <a href="https://www.ecfr.gov/current/title-28/chapter-I/part-35/subpart-H/section-35.201" target="_blank" title="Opens in new tab">more about these exceptions</a>.</p>
          `,
  },

  linkNewWindow: {
    title: "Check if this should open in a new window or tab.",
    tip: () =>
      `<p>To fix: Add text indicating that the link will open in a new window.</p>
          <p>You can dismiss this warning if:</p>
          <ul>
            <li>The user is filling out a form.</li>
            <li>The page already warns the user that the link will open a new window.</li>
          </ul>
          `,
  },

  // Tooltips for Text QA ===============================

  tableNoHeaderCells: {
    title: "This table needs header cells so that screen readers can understand it.",
    tip: () =>
      `<p>To fix: Add table headers to the cells that are row and column headers. </p>
          <p>See the City Web Editors documentation on <a href="https://employees.portland.gov/web-support/create-accessible-and-usable-tables#toc-tips-for-creating-tables" target="_blank" title="Opens in new tab">tips for creating tables</a>.</p>
          `,
  },

  tableContainsContentHeading: {
    title: "This table contains a heading tag.",
    tip: () =>
      `<p>To fix: Remove the heading formatting from the text. If the text is for a row or column header, <a href="https://employees.portland.gov/web-support/create-accessible-and-usable-tables#toc-tips-for-creating-tables" target="_blank" title="Opens in new tab">make it a table header instead</a>.</p>
          <p>Table headers label specific columns or rows within a table. Content headings (H1, H2, etc.) organize content in the body text of a page.</p>
          `,
  },

  tableEmptyHeaderCell: {
    title: "This table has an empty header cell.",
    tip: () =>
      `<p>To fix: Make sure each header cell contains text.</p>
          <p>Headers help screen readers navigate rows and columns of a table. See the City Web Editors documentation for <a href="https://employees.portland.gov/web-support/create-accessible-and-usable-tables#toc-tips-for-creating-tables" target="_blank" title="Opens in new tab">more on creating accessible tables</a>.</p>
          `,
  },

  textPossibleList: {
    title: "Is this part of a list?",
    tip: (text) =>
      `<p>To fix: If this <em>"${text}"</em> is part of a list, use list formatting to make it accessible to screen readers.</p>
          <p>See the City Web Editors documentation for <a href="https://employees.portland.gov/web-support/portlandgov-editor-resources/rich-text-editor#toc-lists" target="_blank" title="Opens in new tab">more on lists</a>. If this text is part of an official document and requires specific formatting other than numbers/bullets, read <a href="https://employees.portland.gov/web-support/portlandgov-editor-resources/rich-text-editor#lists-in-official-documents" target="_blank" title="Opens in new tab">more about code-ordered lists</a>.</p>
          `,
  },

  textPossibleHeading: {
    title: "Should this text be a heading?",
    tip: () =>
      `<p>To fix: Don't use bold text to create a visual heading. If this bold line is meant to be a heading for the content that follows, change it to the correct heading level. </p>
          <p>See the City Web Editors documentation for <a href="https://employees.portland.gov/web-support/portlandgov-editor-resources/rich-text-editor#toc-headings" target="_blank" title="Opens in new tab">more on creating headings</a>.</p>
          <p>Heading levels create a <a href="https://www.w3.org/WAI/tutorials/page-structure/headings/" target="_blank" title="Opens in new tab">table of contents and page structure</a> for assistive devices:</p>
          ${Ed11y.M.headingExample}
          `,
  },

  textUppercase: {
    title: "This text is all uppercase.",
    tip: () =>
      `<p>To fix: Rewrite in sentence case. Use bold for emphasis.</p>
          <p>Examples:</p>
          <ul>
            <li>THIS SENTENCE IS IN UPPERCASE.</li>
            <li>This sentence is in sentence case.</li>
          </ul>
          <p>Uppercase text is more difficult to read and presents problems for screen readers.</p>
          `,
  },

  embedVideo: {
    title: "Does this video have accurate captions?",
    tip: () =>
      `<p>If a recorded video contains speech or meaningful sounds, it <a href="https://www.w3.org/WAI/media/av/captions/" target="_blank" title="Opens in new tab">must have captions</a>. Automatic captions must be proofread, and speakers must be identified.</p>
          <p>See the City's digital accessibility pages for <a href="https://employees.portland.gov/digital-accessibility/create-accessible-documents/audio-and-video-guidelines" target="_blank" title="Opens in new tab">more on audio and video guidelines</a>.</p>
          <p>Check the <a href="https://employees.portland.gov/web-support/portlandgov-editor-resources/accessibility-checker#toc-meets-accessibility-requirements-checkbox" target="_blank" title="Opens in new tab">meets accessibility requirements checkbox</a> once you've verified the video. This will prevent the error whenever the video appears on the site.</p>
          `,
  },

  embedAudio: {
    title: "Does this audio have an accurate transcript?",
    tip: () =>
      `<p>If this audio contains speech or meaningful sounds, it <a href="https://www.w3.org/WAI/media/av/captions/" target="_blank" title="Opens in new tab">must have captions</a>. Automatic transcripts must be proofread, and speakers must be identified.</p>
          <p>See the City's digital accessibility pages for <a href="https://employees.portland.gov/digital-accessibility/create-accessible-documents/audio-and-video-guidelines" target="_blank" title="Opens in new tab">more on audio and video guidelines</a>.</p>
          <p>Check the <a href="https://employees.portland.gov/web-support/portlandgov-editor-resources/accessibility-checker#toc-meets-accessibility-requirements-checkbox" target="_blank" title="Opens in new tab">meets accessibility requirements checkbox</a> once you've verified the audio. This will prevent the error whenever the audio appears on the site.</p>
          `,
  },

  embedVisualization: {
    title: "Is this visualization accessible?",
    tip: () =>
      `<p>Widgets like this are hard to make accessible to assistive devices and readers with low vision. Provide an alternate format unless this has high visual contrast, is keyboard accessible, and works with a screen reader.</p>
          <p>To fix: Provide a text description, data table, or accessible spreadsheet for download.</p>
          `,
  },

  embedCustom: {
    title: "Is this embedded content accessible?",
    tip: () =>
      `<p>To fix: Make sure any images in the embed have alt text, videos have captions, and users can <a href="https://webaim.org/techniques/keyboard/" target="_blank" title="Opens in new tab">operate it with a keyboard</a>.</p>
          <p>Check the <a href="https://employees.portland.gov/web-support/portlandgov-editor-resources/accessibility-checker#toc-meets-accessibility-requirements-checkbox" target="_blank" title="Opens in new tab">meets accessibility requirements checkbox</a> once you've verified the embed. This will prevent the error whenever the embed appears on the site.</p>
          `,
  },
};

// By setting to true, the Editoria11y module calls this global function after options are initialized.
window.editoria11yOptionsOverride = true;
window.editoria11yOptions = (options) => {
  // Merge our custom strings on top of the default English strings.
  ed11yLang["portland"] = Object.assign({}, ed11yLang["en"], PORTLAND_STRINGS);
  options.lang = "portland";

  return options;
};
