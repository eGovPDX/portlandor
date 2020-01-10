# Accessibility Guide

When developing new components it's important to remember that considering inclusivity consistantly leads to a better design overall. This is a resource guide that includes some obstacles users face when navigating the internet, best semantic practices, and links for further reading.

## User Personas

### Hearing

  If there are any audio files, such as sound bites or podcasts, including a transcript for those who have deafness or difficulty hearing is crucial. Also with any video media it's important to provide an option to turn on captions so people can follow along, but including a transcript as well will go one step further for people who may have a hard time reading quickly enough to follow along with captions easily.

### Vision

  * Low-Vision

  Many people with low-vision rely on tools that zoom in parts of a page so they can be read, but this means they can only view that page a little at a time. Making sure that elements are in predictable places is important, as well as making sure that related elements are close enough together. When building a new layout or component consider using 'The Straw Test' to see how well the elements are flowing together. An excellent video discribing how to perform this test and it's benefits can be found here: https://www.youtube.com/watch?v=S1j6CYT3kWA

  * Color-Blindness

  There are several types of color blindess, the most common being red/green color blindness, this means that some combinations of colors blur together or go unseen entirely. A good rule of thumb when considering foreground and background colors is seeing if those colors have a high enough 'contrast ratio'. The standard for regular text is 4.5:1, for large text and graphic objects it is 3:1. There are several free websites that will check the ratio of your colors, such as this one: https://webaim.org/resources/contrastchecker/

  For people with color-blindness, and people with low-vision, it's also important to consider using more than just color changes to indicate hover, focus, or active states. If the color doesn't have sufficiant contrast from the previous one it will be difficult to tell there's been any change at all. Consider using underlines, borders, or other indicators for your focusable elements.


  * Blindness

  The blind rely on screen readers and keyboard interactivity to navigate through a website, the use of clear, semantic HTML is very important. When building a new component make sure you understand the native functionality of elements such as buttons or radio groups, and if making a custom element ensure they can mimic those functions.

  Understand the use of landmarks(HTML5) and roles(ARIA), some screen readers can navigate between landmarks, and aria provides valuable verbal context to an element-  https://www.scottohara.me/blog/2019/04/05/landmarks-exposed.html

  Know when to provide hidden, screen reader specific elements and when to hide elements from the screen reader. If a button reveals an element hidden off screen, but nothing was done to prevent the element from coming into focus via keyboard it can cause confusion- https://www.scottohara.me/blog/2017/04/14/inclusively-hidden.html


  ### Motion

  Some users have motion sensitivity that can cause them to feel ill when dealing with certain kinds of animation. If you do use animations that will last more than 5 seconds long be careful of including large and sudden zooming, multiple elements going in different directions or at different speeds, and continuous scrolling or rotation. If those features are neccessary than ensure that alternatives are provided for users with 'reduce motion' turned on.

  ### Mobility

  There are many people who need to make use of keyboard navigation that are sighted as well, such as people with athritis, paralysis, tremors, etc. They aren't relying on the functionality of screen readers to move about the page so ensuring that all elements are reachable via tab keys, arrows, and spacebar is essential. Understand the value of `tabindex` and how that can make otherwise inaccessible parts of a project come into focus for keyboard users.

  ### Cognitive

  Many people can suffer from a wide range of cognative difficulites either temporaily or permanently, it's not realistic to plan for every case of cognative imparment, but creating layout based on the following can cover a wide swathe of issues from simple fatigue to something more severe.

  * Literacy and Reading

  Whether or not it's because someone is not familiar with the langauge or has difficulty with comprehension, it's good to write things out in clear and simple vernacular. A tool that can test for differing levels of readability can be found here- http://www.hemingwayapp.com/

  * Attention and Focus

  Layouts should be clear and to the point, any non-relavent information could prove distracting. You should tell a user how long an action, such as filling out a form, might take. Let a user know if something unexpected might happen, such as a link opening a new window or tab. Inconsistancies in a layout can confuse users and make it harder to get things done. Markers such as navigation links, pagination, forms, should all look consitent across a project.

  * Memory

  Relavent information should be laid out close together, and any instances where elements are hidden that might be forgotten should be omitted or placed somewhere that can be recalled later. Input placeholders that disappear when typing into them can be difficult, someone may need to type in their birthday but forget the format that is required so they must delete everything to remind themselves. Popups or alerts should be put somewhere that a person can reference later if needed.

## Best Practices

  ### Document Structure

  ![ Landmarks Diagram ](https://image.slidesharecdn.com/themes-plugins-accessibility-wcldn-march-2015-150325031854-conversion-gate01/95/themes-plugins-and-accessibility-wordcamp-london-march-2015-17-638.jpg?cb=1427254256)

  Landmarks such as header, main, and footer should only be placed once on a page, where as, section, article, and nav can be used multiple times.

  Avoid redundant information that clutters a page. There is no reason to have nav links that go to the same pages on the header, footer, and an aside.

  It's also very important to not skip heading levels. Heading tags should only be used to signify level of importance not how the heading should be styled.

  ### HTML Semantics

  Use container elements (div, article, section) for layout only. There is no reason there should be a div tag with the functionality of a button.

  `blockquote` — Some people use the `blockquote` tag for indenting text that is not a quotation. This is because blockquotes are indented by default. If you simply want to indent text that's not a blockquote, use CSS margins instead.

  `p` — Some web editors use a non-breaking space contained in a paragraph to add extra space between page elements, rather than defining actual paragraphs for the text of that page. As in the previous example, you should use the margin or padding style property instead to add space.​

  `ul` — As with `blockquote`, enclosing text inside a `ul` tag indents that text in most browsers. This is both semantically incorrect and invalid HTML, because only `li` tags are valid within a `ul` tag. Again, use the margin or padding style to indent text.

  ### Keyboard Interactivity

  Many elements have standard keyboard accessibility, when making a custom element ensure that they have the same expected functionality as found here- https://webaim.org/techniques/keyboard/

  ### Color Contrast

  Along with using a color checker, you can use a webpage tester that filters the colors based on various levels of color-blindness- https://www.toptal.com/designers/colorfilter/

  ### Focus Management

  Avoid removing native focus outlines, or make sure to include accessible, custom focus effects. 

  Avoid severely nested elements, as it makes tabbing through to desired elements difficult or some may become inaccessible.

## Further Reading

Accessibility testing:
https://www.smashingmagazine.com/2018/09/importance-manual-accessibility-testing/
https://www.smashingmagazine.com/2018/12/voiceover-screen-reader-web-apps/

Disability Consultation:
https://www.disabledlist.org/?1576795128128

Considerations you may not have thought of:
https://alistapart.com/article/accessibility-for-vestibular/
https://alistapart.com/article/designing-for-cognitive-differences/
