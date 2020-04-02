# Morsel

<ul>
  <li>can contain Badge, featured image, a heading, text, contact information, and links</li>
</ul>

## Props

- `is_draft`: boolean - If set to true, morsel gets a "Unpublished" danger badge
- `heading`: object - {} 
    - `text`: string
    - `level`: number — default is 3
    - `style_override`: number - if you want a different size tag for semantic reasons but would like styling to be overridden
- `url`: string - the url for the node of content
- `posted_on`: string - A date that the node was posted on
- `updated_on`: string - An updated date that supersedes the posted_on value
- `image`: string - Pass in the rendered entity from Drupal
- `text`: string - The content summary
- `type`: string - The content/node/group type the content belongs to

## Usage

```twig
{% include "@components/morsel/morsel.twig" with {
  image: '<img src="https://placeimg.com/600/300/any" alt="" />',
  posted_on: 'March 17, 2020',
  heading: {
    text: "Have you received a City of Portland notice in the mail about your single-family home?",
    level: 4,
    style_override: 3
  },
  url: "https://www.google.com",
  text: "Here's what it means and what you can do.",
  type: "News Article",
} %}
```
